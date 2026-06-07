import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:americantv/features/auth/application/auth_controller.dart';
import 'package:americantv/features/auth/domain/auth_state.dart';
import 'package:americantv/features/auth/presentation/login_screen.dart';

class _FakeAuthController extends AuthController {
  _FakeAuthController() : super(_FakeRepo(), _FakeSocial());

  String? lastIdentifier;
  String? lastPassword;
  bool succeed = true;

  @override
  Future<bool> login({required String identifier, required String password}) async {
    lastIdentifier = identifier;
    lastPassword = password;
    if (succeed) {
      // Don't drive into AuthState.authenticated — we don't want the router
      // to redirect in the widget under test. The screen reads errorMessage
      // from state when login() returns false; that's all we need to verify.
      return true;
    }
    state = state.copyWith(
      status: AuthStatus.unauthenticated,
      isWorking: false,
      errorMessage: 'Bad credentials',
    );
    return false;
  }
}

class _FakeRepo implements dynamic {
  @override
  dynamic noSuchMethod(Invocation invocation) => null;
}

class _FakeSocial implements dynamic {
  @override
  dynamic noSuchMethod(Invocation invocation) => null;
}

void main() {
  testWidgets('login screen forwards trimmed identifier + raw password', (tester) async {
    final fake = _FakeAuthController();

    await tester.pumpWidget(
      ProviderScope(
        overrides: [authControllerProvider.overrideWith((_) => fake)],
        child: const MaterialApp(home: LoginScreen()),
      ),
    );

    await tester.enterText(find.byType(TextFormField).at(0), '  user@example.com  ');
    await tester.enterText(find.byType(TextFormField).at(1), 'hunter2');
    await tester.tap(find.widgetWithText(FilledButton, 'Sign in'));
    await tester.pump();

    expect(fake.lastIdentifier, 'user@example.com');
    expect(fake.lastPassword, 'hunter2');
  });

  testWidgets('login screen surfaces server error in a snackbar', (tester) async {
    final fake = _FakeAuthController()..succeed = false;

    await tester.pumpWidget(
      ProviderScope(
        overrides: [authControllerProvider.overrideWith((_) => fake)],
        child: const MaterialApp(home: LoginScreen()),
      ),
    );

    await tester.enterText(find.byType(TextFormField).at(0), 'user@example.com');
    await tester.enterText(find.byType(TextFormField).at(1), 'wrong');
    await tester.tap(find.widgetWithText(FilledButton, 'Sign in'));
    await tester.pump(); // start submit
    await tester.pump(const Duration(milliseconds: 500)); // snackbar shows

    expect(find.text('Bad credentials'), findsOneWidget);
  });
}
