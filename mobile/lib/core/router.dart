import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../features/auth/application/auth_controller.dart';
import '../features/auth/domain/auth_state.dart';
import '../features/auth/presentation/forgot_password_screen.dart';
import '../features/auth/presentation/login_screen.dart';
import '../features/auth/presentation/register_screen.dart';
import '../features/home/presentation/home_shell.dart';
import '../features/video/presentation/video_detail_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final notifier = _AuthChangeNotifier(ref);
  ref.onDispose(notifier.dispose);

  // Read auth state inside the redirect — do NOT ref.watch it at the
  // provider level. Watching would recreate the entire GoRouter (and force
  // MaterialApp.router to rebuild its Navigator) on every isWorking flip,
  // which throws away in-flight ScaffoldMessenger SnackBars and any
  // mid-tap visual feedback. refreshListenable is the right hook for
  // re-running the redirect when auth changes.
  return GoRouter(
    initialLocation: '/',
    refreshListenable: notifier,
    redirect: (context, state) {
      final auth = ref.read(authControllerProvider);
      final loggedIn = auth.status == AuthStatus.authenticated;
      final loggingIn = state.matchedLocation == '/login' ||
          state.matchedLocation == '/register' ||
          state.matchedLocation == '/forgot-password';

      if (auth.status == AuthStatus.unknown) return null;
      if (!loggedIn && !loggingIn) return '/login';
      if (loggedIn && loggingIn) return '/';
      return null;
    },
    routes: [
      GoRoute(
        path: '/',
        builder: (_, __) => const HomeShell(),
      ),
      GoRoute(
        path: '/login',
        builder: (_, __) => const LoginScreen(),
      ),
      GoRoute(
        path: '/register',
        builder: (_, __) => const RegisterScreen(),
      ),
      GoRoute(
        path: '/forgot-password',
        builder: (_, __) => const ForgotPasswordScreen(),
      ),
      GoRoute(
        path: '/video/:slug',
        builder: (_, state) => VideoDetailScreen(slug: state.pathParameters['slug']!),
      ),
    ],
  );
});

/// Bridges Riverpod's `authControllerProvider` to go_router's
/// `refreshListenable`. Only fires when the auth *status* transitions —
/// firing on every state change (incl. isWorking / errorMessage) would
/// retrigger the redirect on every button tap, which both re-evaluates
/// routes unnecessarily and racing with the LoginScreen's own snackbar.
class _AuthChangeNotifier extends ChangeNotifier {
  _AuthChangeNotifier(Ref ref) {
    ref.listen<AuthState>(
      authControllerProvider,
      (prev, next) {
        if (prev?.status != next.status) notifyListeners();
      },
    );
  }
}
