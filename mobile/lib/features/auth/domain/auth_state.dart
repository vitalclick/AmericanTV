import 'user.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

class AuthState {
  const AuthState({
    required this.status,
    this.user,
    this.isWorking = false,
    this.errorMessage,
  });

  const AuthState.unknown() : this(status: AuthStatus.unknown);

  const AuthState.unauthenticated({String? errorMessage})
      : this(status: AuthStatus.unauthenticated, errorMessage: errorMessage);

  const AuthState.authenticated(User user) : this(status: AuthStatus.authenticated, user: user);

  final AuthStatus status;
  final User? user;
  final bool isWorking;
  final String? errorMessage;

  AuthState copyWith({
    AuthStatus? status,
    User? user,
    bool? isWorking,
    String? errorMessage,
    bool clearError = false,
  }) {
    return AuthState(
      status: status ?? this.status,
      user: user ?? this.user,
      isWorking: isWorking ?? this.isWorking,
      errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
    );
  }
}
