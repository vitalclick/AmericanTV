// Values mirrored from mobile/android/app/google-services.json and
// mobile/ios/Runner/GoogleService-Info.plist. If those are regenerated
// (e.g. you re-run `flutterfire configure` or download a new
// google-services.json after adding a SHA-1), update the constants here
// to match. Long-term, prefer regenerating this file via
// `flutterfire configure --project=americantv-a874c`.
//
// These are client-side identifiers — not secrets. They're shipped in
// every APK / IPA and are safe to commit. Real security comes from
// Firebase Security Rules and from your backend verifying ID tokens
// against the web OAuth client (audience).

import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart' show defaultTargetPlatform, TargetPlatform;

class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      default:
        throw UnsupportedError('Firebase configured only for iOS + Android.');
    }
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyDvFZtpHsWdraxAAPJ1eLpYa9hu9H64nNs',
    appId: '1:92747944753:android:bd667d91a772bc22241610',
    messagingSenderId: '92747944753',
    projectId: 'americantv-a874c',
    storageBucket: 'americantv-a874c.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyDZip7sK_VzL4xIeT5uTzR9lzW3s42Os3c',
    appId: '1:92747944753:ios:a64e71317d0694ed241610',
    messagingSenderId: '92747944753',
    projectId: 'americantv-a874c',
    storageBucket: 'americantv-a874c.firebasestorage.app',
    iosBundleId: 'com.americantv.userapp',
    iosClientId:
        '92747944753-hs9tda8eeusc2g3nj3c0mu2schc0dljp.apps.googleusercontent.com',
  );

  static bool get isConfigured => true;
}
