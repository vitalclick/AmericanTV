# ProGuard rules for AmericanTV Android release builds.
#
# Drop into android/app/proguard-rules.pro after `flutter create`, then
# reference from android/app/build.gradle:
#
#   buildTypes {
#     release {
#       minifyEnabled true
#       shrinkResources true
#       proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'),
#                     'proguard-rules.pro'
#     }
#   }

# ----- Flutter core -----
-keep class io.flutter.app.** { *; }
-keep class io.flutter.plugin.** { *; }
-keep class io.flutter.util.** { *; }
-keep class io.flutter.view.** { *; }
-keep class io.flutter.embedding.** { *; }
-dontwarn io.flutter.embedding.**

# ----- Firebase / FCM -----
-keep class com.google.firebase.** { *; }
-keep class com.google.android.gms.** { *; }
-dontwarn com.google.firebase.**

# ----- Crashlytics — keep line numbers so stack traces are legible -----
-keepattributes SourceFile,LineNumberTable
-keep public class * extends java.lang.Exception

# ----- OkHttp (used by the background upload Kotlin shim) -----
-dontwarn okhttp3.**
-dontwarn okio.**
-dontwarn javax.annotation.**

# ----- RevenueCat / Play Billing -----
-keep class com.revenuecat.purchases.** { *; }
-keep class com.android.vending.billing.** { *; }
-dontwarn com.revenuecat.purchases.**

# ----- Better Player / video_player -----
-keep class com.google.android.exoplayer2.** { *; }
-dontwarn com.google.android.exoplayer2.**

# ----- Background upload Kotlin shim -----
-keep class com.americantv.app.upload.** { *; }
-keep class androidx.work.** { *; }

# ----- Reflection used by JSON deserialization (Dart side handles this,
#       but freezed/json_serializable use mirrors via build_runner output) -----
-keepattributes Signature
-keepattributes InnerClasses
-keepattributes EnclosingMethod

# ----- Strip debug / verbose logging from release builds -----
-assumenosideeffects class android.util.Log {
    public static int v(...);
    public static int d(...);
}
