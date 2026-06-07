import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../data/auth_repository.dart';
import '../data/social_auth_repository.dart';
import '../domain/auth_state.dart';

final authControllerProvider =
    StateNotifierProvider<AuthController, AuthState>((ref) {
  return AuthController(
    ref.read(authRepositoryProvider),
    ref.read(socialAuthRepositoryProvider),
  )..bootstrap();
});

class AuthController extends StateNotifier<AuthState> {
  AuthController(this._repo, this._social) : super(const AuthState.unknown());

  final AuthRepository _repo;
  final SocialAuthRepository _social;

  /// Probe stored credentials on app start. Pushes the user through the
  /// router's redirect immediately rather than blocking on a splash.
  Future<void> bootstrap() async {
    try {
      final user = await _repo.restoreSession();
      state = user == null
          ? const AuthState.unauthenticated()
          : AuthState.authenticated(user);
    } on ApiException {
      // Treat unknown-network as unauthenticated for routing purposes.
      // We could route to an "offline" splash here later.
      state = const AuthState.unauthenticated();
    }
  }

  Future<bool> login({required String identifier, required String password}) async {
    state = state.copyWith(isWorking: true, clearError: true);
    try {
      final user = await _repo.login(identifier: identifier, password: password);
      state = AuthState.authenticated(user);
      return true;
    } on ApiException catch (e) {
      state = state.copyWith(
        status: AuthStatus.unauthenticated,
        isWorking: false,
        errorMessage: e.message,
      );
      return false;
    }
  }

  Future<bool> register({
    required String email,
    required String password,
    required String firstname,
    required String lastname,
    String? username,
  }) async {
    state = state.copyWith(isWorking: true, clearError: true);
    try {
      final user = await _repo.register(
        email: email,
        password: password,
        firstname: firstname,
        lastname: lastname,
        username: username,
      );
      state = AuthState.authenticated(user);
      return true;
    } on ApiException catch (e) {
      state = state.copyWith(
        status: AuthStatus.unauthenticated,
        isWorking: false,
        errorMessage: e.message,
      );
      return false;
    }
  }

  Future<bool> signInWithApple() => _withSocial(_social.signInWithApple);

  Future<bool> signInWithGoogle() => _withSocial(_social.signInWithGoogle);

  Future<bool> _withSocial(Future Function() action) async {
    state = state.copyWith(isWorking: true, clearError: true);
    try {
      final user = await action();
      state = AuthState.authenticated(user);
      return true;
    } on ApiException catch (e) {
      state = state.copyWith(
        status: AuthStatus.unauthenticated,
        isWorking: false,
        errorMessage: e.message,
      );
      return false;
    } catch (e) {
      state = state.copyWith(
        status: AuthStatus.unauthenticated,
        isWorking: false,
        errorMessage: 'Could not complete sign-in.',
      );
      return false;
    }
  }

  Future<void> logout() async {
    await _repo.logout();
    state = const AuthState.unauthenticated();
  }

  void clearError() {
    if (state.errorMessage != null) {
      state = state.copyWith(clearError: true);
    }
  }
}
