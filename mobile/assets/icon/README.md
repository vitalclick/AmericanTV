# App icon assets

`flutter_launcher_icons` reads from this directory to generate every
iOS + Android launcher icon. **Two PNG sources required**, neither in
version control because they're binary:

| File | Size | Notes |
|---|---|---|
| `app-icon.png` | 1024×1024 | Master icon. Used for iOS + Android legacy. No alpha (iOS rejects transparent icons). |
| `app-icon-adaptive-foreground.png` | 1024×1024 | Foreground layer for Android adaptive icons. Transparent background. Subject should fit in the centre 66% (Material adaptive-icon safe area). |

## Generating from a brand mark

If you have an SVG brand mark, the easiest path is via Inkscape or rsvg:

```sh
# Master (no alpha — bake the brand red as the background).
rsvg-convert -w 1024 -h 1024 -b "#E53935" brand-mark.svg \
  > assets/icon/app-icon.png

# Adaptive foreground (transparent background; subject centred at 66%).
rsvg-convert -w 1024 -h 1024 \
  brand-mark-padded.svg \
  > assets/icon/app-icon-adaptive-foreground.png
```

`brand-mark-padded.svg` is the same mark with extra whitespace around
it so the visible subject lands inside the safe area when Android crops
to a circle / squircle / rounded square.

## Running the generator

```sh
cd mobile
dart run flutter_launcher_icons
```

This writes:
- `ios/Runner/Assets.xcassets/AppIcon.appiconset/*.png` — every size
  iOS expects.
- `android/app/src/main/res/mipmap-*/ic_launcher.png` — every density
  bucket.
- `android/app/src/main/res/mipmap-anydpi-v26/ic_launcher.xml` — the
  adaptive-icon descriptor pointing at foreground + background.

The Codemagic pipeline runs this step automatically before the build,
so you only need to do it locally if you're testing the icon design
against a simulator.

## Brand red

`#E53935` (hex) — the `_seed` value in `lib/core/theme.dart`. Keep
launcher background + theme primary in sync so the icon foreground
matches the navigation bar accent.
