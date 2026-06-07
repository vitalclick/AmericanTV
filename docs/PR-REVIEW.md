# PR review pass — findings + actions

Self-review of `claude/analyze-codebase-PEpEp` as if I'd received it
cold. Findings categorized by severity and what was done about each.

## Critical — fixed in this pass

### `AppleReceiptVerifier::buildAppStoreServerJwt()` threw RuntimeException

The verifier's `lookupTransaction()` calls `buildAppStoreServerJwt()`
to sign the App Store Server API request. The method had a TODO and
threw, which meant every production IAP verify against a real iOS
app would crash inside the verifier's `lookupTransaction` cross-check.

The JWS-payload signature check (commit `e55a9c8`) was enough by
itself, but reaching for the API cross-check was always going to be
the production-grade move. firebase/php-jwt is already in composer, so
the implementation is a single `JWT::encode` call.

**Action**: implemented the ES256 signing with the documented Apple
claim shape; 10-minute token lifetime (Apple caps at 20).

## Cosmetic — fixed in this pass

### Orphan file: `mobile/native/ios/Info.plist.patch`

Lives at `mobile/native/ios/Info.plist.patch`, never referenced.
Originally intended as a paste-target for manual editing; the actual
production patching happens via PlistBuddy in `codemagic.yaml`. The
file had drifted into "informational duplication" — operators editing
locally now have `platform-setup.md` for guidance, and CI does the
patching automatically.

**Action**: deleted. No diff loss for the actual platform work.

## Found, decided to keep as-is

### 89 commits is a lot

Squashing would compress the history but lose the per-commit context
that's useful for future bisects ("when did the Sanctum token format
change?"). Recommendation: merge with the **rebase + merge** strategy
(GitHub UI option). Preserves commit identity, doesn't add a merge
bubble.

### Some commit messages exceed 50/72 char headlines

I prioritized describing *why* a change matters in the headline over
strict character limits. A future enforcement via commit-msg hook
would catch this; not worth a retrofit on existing commits.

### Test schema gates use `Schema::hasTable()` instead of a fixture

Decision in commit `557b33b`: this project's schema lives in the SQL
dump (`america1_strimmtv.sql.gz`), not in migrations. CI installs
running on `:memory:` SQLite don't have most tables. Each feature
test that needs a table gates on `Schema::hasTable()` and skips
gracefully when absent.

A fixture-based replacement would be cleaner long-term: convert the
SQL dump to migrations once, drop the gates. Cost: ~2 hours of schema
translation. Out of scope for this PR; flagged as a follow-up.

### `gs()` stub doesn't cover every key in the codebase

`GsStubDefaultsTest` walks every default we've set, but a future
controller introducing `gs('new_key')` would silently return null in
tests. A static-analysis pass over controllers would catch this; the
PR explicitly defers it to "Suggested next batch" in the conversation
trail.

### Native Swift / Kotlin shims aren't compile-tested in CI

The mobile CI workflow runs `flutter analyze` + `flutter test`, neither
of which compiles native code. The shims would have to be brought into
`flutter create`'s platform projects, then compiled via `flutter
build apk` / `flutter build ios --no-codesign`. That's what Codemagic
does on every release — but PR CI doesn't.

Adding a "build smoke test" to `.github/workflows/mobile-ci.yml` would
catch a Swift / Kotlin breakage at PR time instead of on the next
deploy attempt. Flagged as a follow-up.

## Other observations (no action needed)

- The OpenAPI spec (`core/docs/api/openapi-v1.yaml`) is the most
  visible "endpoint catalog" file; it doesn't auto-validate against
  the live routes. If endpoints drift, `bin/check-openapi-against-routes`
  could be a useful Phase 3 tool.
- Both feature tests (Laravel) and widget tests (Flutter) use schema
  gates / provider overrides. Consistent pattern; no review concern.
- Native shims include extensive code comments documenting why each
  block exists. Helpful for future maintainers; no need to slim.

## Files removed in this commit

- `mobile/native/ios/Info.plist.patch` (orphan).
