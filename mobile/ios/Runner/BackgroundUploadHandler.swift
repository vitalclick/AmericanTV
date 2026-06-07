import Flutter
import Foundation

/// iOS background upload bridge for the `americantv/background_upload`
/// channel. Uses URLSession(configuration: .background(withIdentifier:))
/// so chunk POSTs continue to run when the app is suspended, backgrounded,
/// or even after the user swipes it away.
///
/// Wiring (do this once during `flutter create`):
///
/// 1. Drop this file into `ios/Runner/`.
/// 2. In `AppDelegate.swift`:
///    ```swift
///    let backgroundUploads = BackgroundUploadHandler()
///    backgroundUploads.register(with: controller as! FlutterPluginRegistrar)
///    GeneratedPluginRegistrant.register(with: self)
///
///    func application(
///      _ application: UIApplication,
///      handleEventsForBackgroundURLSession identifier: String,
///      completionHandler: @escaping () -> Void
///    ) {
///      backgroundUploads.handleEventsForBackgroundURLSession(
///        identifier: identifier, completionHandler: completionHandler)
///    }
///    ```
/// 3. In `Info.plist`, add the background mode `fetch` (Background fetch)
///    — strictly required so the system wakes us briefly to fire delegate
///    callbacks when uploads complete out-of-process.
class BackgroundUploadHandler: NSObject, FlutterPlugin, URLSessionDataDelegate {

  private static let sessionIdentifier = "vip.americantv.upload.background"

  // The single shared background session — re-creating one with the same
  // identifier resumes previously-scheduled tasks rather than starting fresh.
  private lazy var session: URLSession = {
    let config = URLSessionConfiguration.background(withIdentifier: BackgroundUploadHandler.sessionIdentifier)
    config.allowsCellularAccess = true
    config.sessionSendsLaunchEvents = true
    config.isDiscretionary = false
    return URLSession(configuration: config, delegate: self, delegateQueue: nil)
  }()

  private var eventSink: FlutterEventSink?
  private var tasks: [Int: TaskMeta] = [:]
  private var systemCompletionHandler: (() -> Void)?

  static func register(with registrar: FlutterPluginRegistrar) {
    let instance = BackgroundUploadHandler()
    let methodChannel = FlutterMethodChannel(
      name: "americantv/background_upload",
      binaryMessenger: registrar.messenger()
    )
    let eventChannel = FlutterEventChannel(
      name: "americantv/background_upload/events",
      binaryMessenger: registrar.messenger()
    )
    registrar.addMethodCallDelegate(instance, channel: methodChannel)
    eventChannel.setStreamHandler(EventStreamHandler(owner: instance))
  }

  func handleEventsForBackgroundURLSession(identifier: String, completionHandler: @escaping () -> Void) {
    guard identifier == BackgroundUploadHandler.sessionIdentifier else { return }
    systemCompletionHandler = completionHandler
    _ = session // trigger re-creation; URLSession will replay events.
  }

  func handle(_ call: FlutterMethodCall, result: @escaping FlutterResult) {
    switch call.method {
    case "scheduleChunk":
      guard let args = call.arguments as? [String: Any] else {
        result(FlutterError(code: "bad_args", message: "Expected arguments", details: nil))
        return
      }
      scheduleChunk(args: args, result: result)
    case "cancel":
      let uniqueId = (call.arguments as? [String: Any])?["uniqueId"] as? String
      cancel(uniqueId: uniqueId, result: result)
    default:
      result(FlutterMethodNotImplemented)
    }
  }

  private func scheduleChunk(args: [String: Any], result: FlutterResult) {
    guard
      let uniqueId    = args["uniqueId"]    as? String,
      let index       = args["index"]       as? Int,
      let filePath    = args["filePath"]    as? String,
      let offset      = args["offset"]      as? Int,
      let length      = args["length"]      as? Int,
      let endpointUrl = args["endpointUrl"] as? String,
      let headers     = args["headers"]     as? [String: String],
      let formFields  = args["formFields"]  as? [String: String],
      let url = URL(string: endpointUrl)
    else {
      result(FlutterError(code: "bad_args", message: "Malformed", details: nil))
      return
    }

    // Read the chunk into a temp file so URLSession can do the actual upload
    // out-of-process. Background sessions require a file body, not Data.
    let tmpPath = NSTemporaryDirectory().appending("upload-\(uniqueId)-\(index).bin")
    let tmpUrl  = URL(fileURLWithPath: tmpPath)
    do {
      let source = try FileHandle(forReadingFrom: URL(fileURLWithPath: filePath))
      try source.seek(toOffset: UInt64(offset))
      let bytes = source.readData(ofLength: length)
      try source.close()
      let body = Self.buildMultipartBody(
        boundary: "chunk-\(uniqueId)-\(index)",
        fields: formFields,
        fileFieldName: "chunk",
        filename: "\(formFields["fileName"] ?? "chunk").part\(index)",
        bytes: bytes
      )
      try body.write(to: tmpUrl, options: .atomic)
    } catch {
      result(FlutterError(code: "io", message: error.localizedDescription, details: nil))
      return
    }

    var request = URLRequest(url: url)
    request.httpMethod = "POST"
    request.setValue("multipart/form-data; boundary=chunk-\(uniqueId)-\(index)", forHTTPHeaderField: "Content-Type")
    for (k, v) in headers { request.setValue(v, forHTTPHeaderField: k) }

    let task = session.uploadTask(with: request, fromFile: tmpUrl)
    tasks[task.taskIdentifier] = TaskMeta(uniqueId: uniqueId, index: index, bodyUrl: tmpUrl)
    task.resume()
    result("\(task.taskIdentifier)")
  }

  private func cancel(uniqueId: String?, result: FlutterResult) {
    session.getAllTasks { tasks in
      for t in tasks {
        if let id = self.tasks[t.taskIdentifier]?.uniqueId, id == uniqueId {
          t.cancel()
        }
      }
    }
    result(nil)
  }

  // MARK: - URLSessionTaskDelegate

  func urlSession(
    _ session: URLSession,
    task: URLSessionTask,
    didSendBodyData bytesSent: Int64,
    totalBytesSent: Int64,
    totalBytesExpectedToSend: Int64
  ) {
    guard let meta = tasks[task.taskIdentifier] else { return }
    emit([
      "uniqueId": meta.uniqueId,
      "index":    meta.index,
      "kind":     "progress",
      "bytesSent": totalBytesSent,
    ])
  }

  func urlSession(_ session: URLSession, task: URLSessionTask, didCompleteWithError error: Error?) {
    guard let meta = tasks.removeValue(forKey: task.taskIdentifier) else { return }
    try? FileManager.default.removeItem(at: meta.bodyUrl)
    if let error = error {
      emit([
        "uniqueId": meta.uniqueId,
        "index":    meta.index,
        "kind":     "failed",
        "error":    error.localizedDescription,
      ])
    } else {
      emit([
        "uniqueId": meta.uniqueId,
        "index":    meta.index,
        "kind":     "completed",
      ])
    }
  }

  func urlSessionDidFinishEvents(forBackgroundURLSession session: URLSession) {
    DispatchQueue.main.async {
      self.systemCompletionHandler?()
      self.systemCompletionHandler = nil
    }
  }

  // MARK: - Helpers

  private func emit(_ payload: [String: Any]) {
    DispatchQueue.main.async {
      self.eventSink?(payload)
    }
  }

  private static func buildMultipartBody(
    boundary: String,
    fields: [String: String],
    fileFieldName: String,
    filename: String,
    bytes: Data
  ) -> Data {
    var body = Data()
    let crlf = "\r\n"
    for (k, v) in fields {
      body.append("--\(boundary)\(crlf)".data(using: .utf8)!)
      body.append("Content-Disposition: form-data; name=\"\(k)\"\(crlf)\(crlf)".data(using: .utf8)!)
      body.append("\(v)\(crlf)".data(using: .utf8)!)
    }
    body.append("--\(boundary)\(crlf)".data(using: .utf8)!)
    body.append("Content-Disposition: form-data; name=\"\(fileFieldName)\"; filename=\"\(filename)\"\(crlf)".data(using: .utf8)!)
    body.append("Content-Type: application/octet-stream\(crlf)\(crlf)".data(using: .utf8)!)
    body.append(bytes)
    body.append("\(crlf)--\(boundary)--\(crlf)".data(using: .utf8)!)
    return body
  }

  private struct TaskMeta {
    let uniqueId: String
    let index: Int
    let bodyUrl: URL
  }

  private class EventStreamHandler: NSObject, FlutterStreamHandler {
    weak var owner: BackgroundUploadHandler?
    init(owner: BackgroundUploadHandler) { self.owner = owner }
    func onListen(withArguments arguments: Any?, eventSink events: @escaping FlutterEventSink) -> FlutterError? {
      owner?.eventSink = events
      return nil
    }
    func onCancel(withArguments arguments: Any?) -> FlutterError? {
      owner?.eventSink = nil
      return nil
    }
  }
}
