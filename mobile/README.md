# AmericanTV Mobile

Flutter app for iOS + Android, talking to the Laravel API in `../core`.

## Prerequisites

- Flutter SDK 3.24+ (`flutter --version`)
- Xcode 16+ (iOS builds)
- Android Studio + Android SDK 34 (Android builds)
- A local copy of the Laravel API running on `http://localhost:8000` or a
  staging URL you can reach.

## First-time bootstrap

The platform folders (`android/`, `ios/`) are intentionally **not** committed.
They contain machine-generated Gradle and Xcode files that should be produced
by `flutter create`, not by us.

```bash
cd mobile

# Generate the native projects. The .dart source already in lib/ overrides
# the placeholder main.dart that flutter create produces.
flutter create . \
  --org com.americantv \
  --project-name americantv \
  --platforms ios,android \
  --no-overwrite

# Install Dart packages
flutter pub get

# Configure env
cp .env.example .env
# edit .env — set API_BASE_URL to your local Laravel instance
```

Then apply the platform overlays — see `docs/platform-setup.md` for the
exact snippets to paste into `android/app/src/main/AndroidManifest.xml`,
`ios/Runner/Info.plist`, and `android/app/build.gradle`. They cover:

- INTERNET permission + cleartext for dev
- Camera/photo library permissions (for upload — Phase 2)
- App Transport Security exception for HTTP staging
- Sign in with Apple capability
- minSdkVersion 23, targetSdkVersion 34

## Generating the typed API client

We do **not** check in the generated client. Regenerate after each OpenAPI
change:

```bash
./tools/generate_api.sh
```

Output goes to `lib/api/generated/` (gitignored). The hand-written code in
`lib/api/` imports from it.

## Run it

```bash
flutter run                     # picks any attached device
flutter run -d "iPhone 15"      # iOS simulator
flutter run -d emulator-5554    # Android emulator
```

## Project layout

```
lib/
  main.dart                     # entry — loads env, runs App
  app.dart                      # MaterialApp.router, theme wiring
  core/
    env.dart                    # typed env access
    router.dart                 # go_router config with auth guards
    theme.dart                  # Material 3 theme
  api/
    dio_client.dart             # Dio instance with auth interceptor
    api_exception.dart          # error mapping
    generated/                  # OpenAPI codegen output (gitignored)
  features/
    auth/
      data/
        token_storage.dart      # flutter_secure_storage wrapper
        auth_repository.dart    # login / register / refresh / logout
      domain/
        user.dart
        auth_state.dart
      application/
        auth_controller.dart    # Riverpod StateNotifier
      presentation/
        login_screen.dart
        register_screen.dart
        forgot_password_screen.dart
    home/
      presentation/
        home_shell.dart         # post-login scaffold
```

## State management

Riverpod 2 with hand-written providers. Not using `riverpod_generator` —
keeps the build pipeline simple. Revisit once the codebase grows past ~30
providers.

## Why these dependencies

| Package | Why |
|---|---|
| `flutter_riverpod` | State + DI. Testable, no global singletons. |
| `go_router` | Declarative routes with redirect guards for auth. |
| `dio` | HTTP client with proper interceptor support. |
| `flutter_secure_storage` | Sanctum token belongs in Keychain / EncryptedSharedPrefs. |
| `freezed` + `json_serializable` | Immutable models + JSON; required by openapi-generator's dart-dio output anyway. |
| `purchases_flutter` (RevenueCat) | Single SDK that handles StoreKit 2 + Play Billing edge cases. |
| `firebase_messaging` | FCM push wired to existing `DeviceToken` model. |
| `firebase_analytics`, `firebase_crashlytics` | Day-1 observability. |
| `video_player` + `better_player` | HLS playback with PiP / background. |
| `sign_in_with_apple` | Apple App Store requirement when offering social login. |
| `google_sign_in` | Native Google OAuth flow → ID token to Laravel. |

See `docs/flutter-vs-react-native.md` (in the repo root `docs/mobile/`) for
the rationale behind this stack.

## CI

`.github/workflows/mobile-ci.yml` runs `flutter analyze` + `flutter test`
on every push touching `mobile/**`. Real iOS/Android builds are not run in
CI yet — add Codemagic or Bitrise when you're ready to ship to TestFlight /
Play Internal Testing.
