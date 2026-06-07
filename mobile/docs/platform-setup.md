# Platform setup

The `android/` and `ios/` directories are produced by `flutter create .`.
After running that, apply the snippets below.

## Android

### `android/app/build.gradle`

```gradle
android {
    namespace "com.americantv"
    compileSdkVersion 34

    defaultConfig {
        applicationId "com.americantv"
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
