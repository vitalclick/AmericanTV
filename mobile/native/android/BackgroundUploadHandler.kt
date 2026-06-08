package com.americantv.app.upload

import android.content.Context
import androidx.work.*
// Explicit import disambiguates from kotlin.Result. Without it, K2 (Kotlin
// 2.x) resolves the unqualified `Result` inside UploadChunkWorker as
// kotlin.Result, which then needs a type parameter and a Throwable arg
// for .failure() — cascading into ~25 spurious errors across doWork().
// Kotlin 1.9 happened to prefer the inherited nested class; K2 doesn't.
import androidx.work.ListenableWorker.Result
import io.flutter.embedding.engine.plugins.FlutterPlugin
import io.flutter.plugin.common.EventChannel
import io.flutter.plugin.common.MethodCall
import io.flutter.plugin.common.MethodChannel
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject
import java.io.File
import java.io.RandomAccessFile
import java.util.concurrent.TimeUnit

/**
 * Android counterpart to `BackgroundUploadHandler.swift`. Exposes the same
 * `americantv/background_upload` method + event channels but schedules each
 * chunk POST as a WorkManager job. WorkManager survives app death and
 * resumes the queue when the device comes back online.
 *
 * Wiring (do this once after `flutter create`):
 *
 * 1. Drop this file into `android/app/src/main/kotlin/com/americantv/app/upload/`.
 *    (The package declaration above matches our applicationId of
 *     com.americantv.app — change both together if you ever rename the app.)
 * 2. In `MainActivity.kt`, register the plugin against the engine:
 *
 *    ```kotlin
 *    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
 *        super.configureFlutterEngine(flutterEngine)
 *        BackgroundUploadHandler().onAttachedToEngine(
 *          flutterEngine.dartExecutor.binaryMessenger,
 *          applicationContext
 *        )
 *    }
 *    ```
 *
 * 3. Add the WorkManager dependency in `android/app/build.gradle`:
 *
 *    ```gradle
 *    dependencies {
 *      implementation 'androidx.work:work-runtime-ktx:2.9.1'
 *      implementation 'com.squareup.okhttp3:okhttp:4.12.0'
 *    }
 *    ```
 */
class BackgroundUploadHandler : FlutterPlugin {

    private var methodChannel: MethodChannel? = null
    private var eventChannel: EventChannel? = null
    private var eventSink: EventChannel.EventSink? = null
    private lateinit var appContext: Context

    fun onAttachedToEngine(messenger: io.flutter.plugin.common.BinaryMessenger, context: Context) {
        appContext = context.applicationContext
        methodChannel = MethodChannel(messenger, "americantv/background_upload").also {
            it.setMethodCallHandler(::onMethodCall)
        }
        eventChannel = EventChannel(messenger, "americantv/background_upload/events").also {
            it.setStreamHandler(object : EventChannel.StreamHandler {
                override fun onListen(arguments: Any?, events: EventChannel.EventSink?) {
                    eventSink = events
                }
                override fun onCancel(arguments: Any?) {
                    eventSink = null
                }
            })
        }
        EventBridge.subscribe(::emit)
    }

    override fun onAttachedToEngine(binding: FlutterPlugin.FlutterPluginBinding) {
        onAttachedToEngine(binding.binaryMessenger, binding.applicationContext)
    }

    override fun onDetachedFromEngine(binding: FlutterPlugin.FlutterPluginBinding) {
        methodChannel?.setMethodCallHandler(null)
        eventChannel?.setStreamHandler(null)
        EventBridge.unsubscribe(::emit)
        eventSink = null
    }

    private fun emit(payload: Map<String, Any?>) {
        eventSink?.success(payload)
    }

    private fun onMethodCall(call: MethodCall, result: MethodChannel.Result) {
        when (call.method) {
            "scheduleChunk" -> {
                val args = call.arguments as? Map<*, *>
                if (args == null) {
                    result.error("bad_args", "Expected arguments", null)
                    return
                }

                val data = workDataOf(
                    KEY_UNIQUE_ID   to args["uniqueId"] as String,
                    KEY_INDEX       to (args["index"] as Number).toInt(),
                    KEY_FILE_PATH   to args["filePath"] as String,
                    KEY_OFFSET      to (args["offset"] as Number).toLong(),
                    KEY_LENGTH      to (args["length"] as Number).toLong(),
                    KEY_ENDPOINT    to args["endpointUrl"] as String,
                    KEY_HEADERS_JSON   to JSONObject(args["headers"] as Map<*, *>).toString(),
                    KEY_FORM_FIELDS_JSON to JSONObject(args["formFields"] as Map<*, *>).toString(),
                )

                val request = OneTimeWorkRequestBuilder<UploadChunkWorker>()
                    .setInputData(data)
                    .setConstraints(
                        Constraints.Builder()
                            .setRequiredNetworkType(NetworkType.CONNECTED)
                            .build()
                    )
                    .setBackoffCriteria(
                        BackoffPolicy.EXPONENTIAL,
                        WorkRequest.MIN_BACKOFF_MILLIS,
                        TimeUnit.MILLISECONDS,
                    )
                    .addTag(args["uniqueId"] as String)
                    .build()

                WorkManager.getInstance(appContext).enqueue(request)
                result.success(request.id.toString())
            }

            "cancel" -> {
                val uniqueId = (call.arguments as? Map<*, *>)?.get("uniqueId") as? String
                if (uniqueId != null) {
                    WorkManager.getInstance(appContext).cancelAllWorkByTag(uniqueId)
                }
                result.success(null)
            }

            else -> result.notImplemented()
        }
    }

    companion object {
        const val KEY_UNIQUE_ID = "uniqueId"
        const val KEY_INDEX = "index"
        const val KEY_FILE_PATH = "filePath"
        const val KEY_OFFSET = "offset"
        const val KEY_LENGTH = "length"
        const val KEY_ENDPOINT = "endpointUrl"
        const val KEY_HEADERS_JSON = "headersJson"
        const val KEY_FORM_FIELDS_JSON = "formFieldsJson"
    }
}

/**
 * Bridge between worker threads (which can't talk to the Dart-facing
 * EventSink directly) and the foreground plugin instance. The plugin
 * subscribes a callback; workers post events through `dispatch`.
 */
object EventBridge {
    private val subscribers = mutableListOf<(Map<String, Any?>) -> Unit>()

    fun subscribe(cb: (Map<String, Any?>) -> Unit) { subscribers += cb }
    fun unsubscribe(cb: (Map<String, Any?>) -> Unit) { subscribers -= cb }

    fun dispatch(payload: Map<String, Any?>) {
        subscribers.toList().forEach { it(payload) }
    }
}

class UploadChunkWorker(ctx: Context, params: WorkerParameters) : Worker(ctx, params) {
    override fun doWork(): Result {
        val uniqueId = inputData.getString(BackgroundUploadHandler.KEY_UNIQUE_ID) ?: return Result.failure()
        val index    = inputData.getInt(BackgroundUploadHandler.KEY_INDEX, -1)
        val filePath = inputData.getString(BackgroundUploadHandler.KEY_FILE_PATH) ?: return Result.failure()
        val offset   = inputData.getLong(BackgroundUploadHandler.KEY_OFFSET, 0)
        val length   = inputData.getLong(BackgroundUploadHandler.KEY_LENGTH, 0).toInt()
        val endpoint = inputData.getString(BackgroundUploadHandler.KEY_ENDPOINT) ?: return Result.failure()
        val headers  = JSONObject(inputData.getString(BackgroundUploadHandler.KEY_HEADERS_JSON) ?: "{}")
        val form     = JSONObject(inputData.getString(BackgroundUploadHandler.KEY_FORM_FIELDS_JSON) ?: "{}")

        val tempFile = File.createTempFile("chunk-$uniqueId-$index", ".bin", applicationContext.cacheDir)
        return try {
            RandomAccessFile(filePath, "r").use { raf ->
                raf.seek(offset)
                val buffer = ByteArray(length)
                raf.readFully(buffer)
                tempFile.writeBytes(buffer)
            }

            val builder = MultipartBody.Builder().setType(MultipartBody.FORM)
            form.keys().forEach { k ->
                builder.addFormDataPart(k, form.getString(k))
            }
            builder.addFormDataPart(
                "chunk",
                "${form.optString("fileName", "chunk")}.part$index",
                tempFile.asRequestBody("application/octet-stream".toMediaTypeOrNull()),
            )
            val requestBody = builder.build()

            val requestBuilder = Request.Builder().url(endpoint).post(requestBody)
            headers.keys().forEach { k ->
                requestBuilder.addHeader(k, headers.getString(k))
            }

            val client = OkHttpClient.Builder()
                .callTimeout(2, TimeUnit.MINUTES)
                .build()

            client.newCall(requestBuilder.build()).execute().use { response ->
                if (!response.isSuccessful) {
                    EventBridge.dispatch(mapOf(
                        "uniqueId" to uniqueId,
                        "index"    to index,
                        "kind"     to "failed",
                        "error"    to "HTTP ${response.code}",
                    ))
                    return Result.retry()
                }
            }

            EventBridge.dispatch(mapOf(
                "uniqueId" to uniqueId,
                "index"    to index,
                "kind"     to "completed",
            ))
            Result.success()
        } catch (t: Throwable) {
            EventBridge.dispatch(mapOf(
                "uniqueId" to uniqueId,
                "index"    to index,
                "kind"     to "failed",
                "error"    to (t.message ?: t.javaClass.simpleName),
            ))
            Result.retry()
        } finally {
            tempFile.delete()
        }
    }
}
