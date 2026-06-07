import 'dart:async';
import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Simple key/value JSON cache backed by SharedPreferences. Used by the feed
/// and video-detail repositories as a read-through cache so cold launches
/// without a network show last-known content while the live fetch runs.
///
/// Not appropriate for sensitive data (tokens belong in TokenStorage which
/// uses Keychain/EncryptedSharedPrefs). Fine for public video metadata.
final cacheServiceProvider = Provider<CacheService>((_) => CacheService());

class CacheService {
  Future<SharedPreferences>? _prefs;

  Future<SharedPreferences> _instance() {
    return _prefs ??= SharedPreferences.getInstance();
  }

  Future<Map<String, dynamic>?> readJson(String key) async {
    final prefs = await _instance();
    final raw = prefs.getString(key);
    if (raw == null) return null;
    try {
      return json.decode(raw) as Map<String, dynamic>;
    } catch (_) {
      // Bad cached payload from an older app version — wipe it.
      await prefs.remove(key);
      return null;
    }
  }

  Future<void> writeJson(String key, Map<String, dynamic> value) async {
    final prefs = await _instance();
    await prefs.setString(key, json.encode(value));
  }

  Future<void> remove(String key) async {
    final prefs = await _instance();
    await prefs.remove(key);
  }

  /// Clear everything we've cached. Called on logout so a different user
  /// signing in on the same device doesn't see the previous user's content.
  Future<void> clearAll() async {
    final prefs = await _instance();
    final keys = prefs.getKeys().where((k) => k.startsWith('cache:')).toList();
    for (final k in keys) {
      await prefs.remove(k);
    }
  }
}
