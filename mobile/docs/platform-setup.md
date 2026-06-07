# Platform setup

The `android/` and `ios/` directories are produced by `flutter create .`.
After running that, apply the snippets below.

## Android

### `android/app/build.gradle`

```gradle
android {
    namespace "com.americantv.app"
    compileSdkVersion 34

    defaultConfig {
        applicationId "com.americantv.app"
        minSdkVersion 23       // required by firebase_messaging
        targetSdkVersion 34
        multiDexEnabled true
    }

    compileOptions {
        coreLibraryDesugaringEnabled true
        sourceCompatibility JavaVersion.VERSION_17
        targetCompatibility JavaVersion.VERSION_17
    }
}

dependencies {
    coreLibraryDesugaring "com.android.tools:desugar_jdk_libs:2.1.2"
}

apply plugin: 'com.google.gms.google-services'      // for Firebase
```

### `android/build.gradle`

```gradle
buildscript {
    dependencies {
        classpath 'com.google.gms:google-services:4.4.2'
    }
}
```

### `android/app/src/main/AndroidManifest.xml`

Inside `<manifest>`:

```xml
<uses-permission android:name="android.permission.INTERNET"/>
<uses-permission android:name="com.android.vending.BILLING"/>
<uses-permission android:name="android.permission.POST_NOTIFICATIONS"/>
```

Inside `<application>`:

```xml
<!-- Cleartext for local Laravel dev only. Remove for production. -->
android:usesCleartextTraffic="true"
```

Drop `google-services.json` (from Firebase console) into `android/app/`.
It is gitignored.

## iOS

### `ios/Runner/Info.plist`

Add:

```xml
<key>NSAppTransportSecurity</key>
<dict>
  <!-- Remove this once the API is HTTPS-only. -->
  <key>NSAllowsArbitraryLoads</key><true/>
</dict>

<key>CFBundleURLTypes</key>
<array>
  <dict>
    <key>CFBundleURLSchemes</key>
    <array>
      <!-- Reverse DNS of your iOS OAuth client ID, for Google Sign-In. -->
      <string>com.googleusercontent.apps.XXXXXXX</string>
    </array>
  </dict>
</array>

<key>NSCameraUsageDescription</key>
<string>Record video for uploads.</string>
<key>NSPhotoLibraryUsageDescription</key>
<string>Choose video for uploads.</string>
<key>NSMicrophoneUsageDescription</key>
<string>Record audio for uploads.</string>
```

### Sign in with Apple

In Xcode → Runner target → Signing & Capabilities → + Capability →
**Sign in with Apple**.

### Push notifications

+ Capability → **Push Notifications** and **Background Modes** (check
"Remote notifications" and "Audio, AirPlay, and Picture in Picture" for
video background playback).

Drop `GoogleService-Info.plist` (from Firebase) into `ios/Runner/` and add
it to the Runner target in Xcode. It is gitignored.

### `ios/Podfile`

```ruby
platform :ios, '13.0'
```

### Background uploads (iOS)

The Swift bridge is checked in at `mobile/native/ios/BackgroundUploadHandler.swift`
(it would otherwise be wiped by the `ios/` entry in `.gitignore`). After
`flutter create`:

1. Copy it into the generated `ios/Runner/` directory:
   ```
   cp ../native/ios/BackgroundUploadHandler.swift ios/Runner/
   ```
2. Open `ios/Runner.xcworkspace`, drag `BackgroundUploadHandler.swift`
   into the Runner target.
2. Edit `ios/Runner/AppDelegate.swift`:

   ```swift
   @main
   @objc class AppDelegate: FlutterAppDelegate {
     private var backgroundUploads = BackgroundUploadHandler()

     override func application(
       _ application: UIApplication,
       didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?
     ) -> Bool {
       GeneratedPluginRegistrant.register(with: self)
       let controller = window?.rootViewController as! FlutterViewController
       backgroundUploads.register(with: registrar(forPlugin: "BackgroundUploadHandler")!)
       return super.application(application, didFinishLaunchingWithOptions: launchOptions)
     }

     override func application(
       _ application: UIApplication,
       handleEventsForBackgroundURLSession identifier: String,
       completionHandler: @escaping () -> Void
     ) {
       backgroundUploads.handleEventsForBackgroundURLSession(
         identifier: identifier, completionHandler: completionHandler)
     }
   }
   ```

3. In `Info.plist`, add `UIBackgroundModes` containing `fetch` — required
   so iOS will wake us briefly to fire delegate callbacks when uploads
   complete out-of-process.

Without the wiring above, the Dart side falls through cleanly to the
in-process upload (wakelock + progress notification), so the feature
degrades gracefully.

### Background uploads (Android)

`BackgroundUploadHandler.kt` is checked in at
`mobile/native/android/BackgroundUploadHandler.kt`. After `flutter create`:

1. Copy it into the generated Android Kotlin source tree:
   ```
   mkdir -p android/app/src/main/kotlin/com/americantv/app/upload
   cp ../native/android/BackgroundUploadHandler.kt \
      android/app/src/main/kotlin/com/americantv/app/upload/
   ```
2. Edit `android/app/src/main/kotlin/com/americantv/app/MainActivity.kt`:

   ```kotlin
   import io.flutter.embedding.android.FlutterActivity
   import io.flutter.embedding.engine.FlutterEngine
   import com.americantv.app.upload.BackgroundUploadHandler

   class MainActivity : FlutterActivity() {
     override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
       super.configureFlutterEngine(flutterEngine)
       BackgroundUploadHandler().onAttachedToEngine(
         flutterEngine.dartExecutor.binaryMessenger,
         applicationContext,
       )
     }
   }
   ```

3. Add WorkManager + OkHttp to `android/app/build.gradle`:

   ```gradle
   dependencies {
     implementation 'androidx.work:work-runtime-ktx:2.9.1'
     implementation 'com.squareup.okhttp3:okhttp:4.12.0'
   }
   ```

WorkManager survives app death and resumes scheduled chunk uploads when
the device comes back online — the Android counterpart to the iOS
URLSession background session.
