package com.americantv.app

import com.americantv.app.upload.BackgroundUploadHandler
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine

class MainActivity : FlutterActivity() {
    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        // Registers the americantv/background_upload + .../events channels
        // backed by WorkManager. See native/android/BackgroundUploadHandler.kt
        // for the implementation.
        BackgroundUploadHandler().onAttachedToEngine(
            flutterEngine.dartExecutor.binaryMessenger,
            applicationContext,
        )
    }
}
