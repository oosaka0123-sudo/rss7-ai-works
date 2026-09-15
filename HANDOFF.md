# RSS7 AI Works — HANDOFF

Updated: 2026-09-15 16:05 JST
Repository: `oosaka0123-sudo/rss7-ai-works`
Branch: `main`
Site: `https://rss7.net`

## Current state

The site is in production. The three supplied promotional videos are implemented on subpages only:

- `ABOUT` → `assets/video/about.mp4`
- `SERVICES` → `assets/video/services.mp4`
- `WORKS` → `assets/video/works.mp4`
- TOP page → no video added by this task

The source pages reference the correct files and the live assets are available.

## Video presentation decisions

Do not use a strong full-frame overlay over these videos. The user was concerned that an overlay would make text difficult to read.

Current direction:

- Keep video visually clear.
- Keep headings/descriptions outside the video frame rather than placing large text over the footage.
- 16:9 presentation.
- `autoplay muted loop playsinline preload="metadata"`.
- Pause off-screen video with `IntersectionObserver`.
- Respect `prefers-reduced-motion`.
- Maintain mobile readability and avoid horizontal overflow.

## Production deploy

Dedicated workflow:

`.github/workflows/deploy-subpage-videos-one-shot.yml`

Production deploy run:

- Run ID: `34934631455`
- Job ID: `104269789354`
- Result: **success**
- Verification log ended with `Subpage videos are live.`

The workflow uploaded `about.html`, `services.html`, `works.html`, `sitemap.xml` and all three MP4 files, then verified the live pages and video assets on `https://rss7.net`.

## Post-handoff QA completed

A real Chromium/Playwright check was run on PC02 against the production site at both desktop (`1440x1000`) and mobile (`390x844`) widths.

Results for TOP / ABOUT / SERVICES / WORKS:

- HTTP status: all `200`
- horizontal overflow: `0px` on desktop and mobile
- hamburger menu: opens correctly and sets `aria-expanded="true"`
- console/page errors: none detected
- broken images after scrolling/lazy-load: none detected
- TOP page: no promotional video present
- ABOUT / SERVICES / WORKS: correct MP4 loaded, `readyState=4`, muted, looping, inline playback and actively playing while visible
- off-screen pause/resume: all three videos pause when scrolled away and resume when scrolled back into view

Internal production links checked and returning `200` include:

- `/`
- `/index.html`
- `/services.html`
- `/works.html`
- `/about.html`
- `/blog.html`
- `/contact.html`
- `/privacy.html`
- `/demos/nyoganji/`
- service anchors and contact query links checked from the main pages

The six showcase SVG assets used on TOP/WORKS also return HTTP `200`.

## Connectivity

Remote Desktop Commander was rechecked during QA. PC02 was usable for the automated browser test. PC01/PC02 may temporarily disconnect; recheck before treating a disconnect as permanent.

## User's execution rule

The user explicitly requested autonomous completion:

- Do not stop waiting for confirmation for ordinary implementation decisions.
- On errors or connection failures, switch methods and continue whatever can be completed.
- Repeat `investigate → implement → verify → fix → reverify` until the site is in a completed state.
- Check PC/mobile layout, links, menu, horizontal overflow, and errors.
- Only separate items at the end when they are truly blocked by external permissions or access that cannot be bypassed.

## Next session — start here

1. Read this `HANDOFF.md` first.
2. Confirm `main` and inspect commits after this handoff.
3. The three-video task is complete and production-verified; do not redo it unless a regression is reported.
4. Continue with the user's next RSS7 AI Works improvement request.
5. Preserve the no-heavy-overlay decision unless the user explicitly changes direction.

## Important design context

Brand direction is dark / futuristic / premium digital, with white/light-gray typography and cyan-blue accents. Preserve a modern high-end look. Avoid making sections feel like generic template blocks.

The user prefers motion and strong visual impact, but usability and text legibility take priority over decorative overlays.
