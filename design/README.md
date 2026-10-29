# BuwisBuddy UI design

Every screen of the BIR tax app, in a mobile (Android) and a desktop (Windows/macOS) version.

- `index.html`: open it in a browser to browse all screens by section.
- `screens/mobile/`: 71 mobile screens, PNG, 2x.
- `screens/desktop/`: 52 desktop screens, PNG.
- `screens/developer/`: the rule pack editor (developer tool, not shipped in the app).
- `source/`: the editable Claude Design source (`*.dc.html`, one per screen) and `canvas.json` (page layout and order). These need the Claude Design runtime to render; the PNGs are the static export.

File names follow the user journey: `<section>-<position>-<Screen>.png`.

| Section | Covers |
| --- | --- |
| 1 · First launch and setup | 11 mobile · 3 desktop |
| 2 · Home and your tax | 5 mobile · 4 desktop |
| 3 · Record what happened | 7 mobile · 3 desktop |
| 4 · Stay compliant | 3 mobile · 2 desktop |
| 5 · File and keep records | 7 mobile · 4 desktop |
| 6 · Ask, settings, backup, app lock | 4 mobile · 4 desktop |
| 7 · States and errors | 2 mobile · 1 desktop |
| 8 · Phase 2: VAT, withholding agent, job + business | 8 mobile · 7 desktop |
| 9 · Phase 3: amendments, late payment, DST, payroll, BIR letters | 7 mobile · 7 desktop |
| 10 · MVP additions: first run, annual return, sworn declaration, backup, books | 6 mobile · 6 desktop |
| 11 · Phase 2 additions: withholding returns, marketplace, SLSP, 2316 | 8 mobile · 8 desktop |
| 12 · Details: search, rule updates, erase | 3 mobile · 3 desktop |

Live, editable canvas: https://claude.ai/artifact/8B89wDomaK9WXYsr9RQgca

Sample figures on screen follow the tax engine's math and the rules verified in the blueprint doc; items marked [from rule pack] or [TIN] are placeholders.
