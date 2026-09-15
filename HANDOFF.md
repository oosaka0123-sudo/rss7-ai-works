# RSS7 AI Works — HANDOFF

Updated: 2026-09-15 15:47 JST
Repository: `oosaka0123-sudo/rss7-ai-works`
Branch: `main`
Site: `https://rss7.net`

## Current state

The site is in production. The latest `main` before this handoff was `0ecd3769b142874d8c6731cb4eaf5fabc78cf506` (`chore: retrigger subpage video production deploy`).

The current task was to place the three supplied promotional videos on subpages only, without changing the top page.

Implemented mapping:

- `ABOUT` → `assets/video/about.mp4`
- `SERVICES` → `assets/video/services.mp4`
- `WORKS` → `assets/video/works.mp4`
- TOP page → no video added by this task

The source pages already reference the correct files:

- `about.html` → `assets/video/about.mp4`
- `services.html` → `assets/video/services.mp4`
- `works.html` → `assets/video/works.mp4`

## Video presentation decisions

Do not use a strong full-frame overlay over these videos. The user was concerned that an overlay would make text difficult to read.

Current direction:

- Keep video visually clear.
- Keep headings/descriptions outside the video frame rather than placing large text over the footage.
- 16:9 presentation.
- `autoplay muted loop playsinline preload="metadata"`.
- Pause off-screen video with `IntersectionObserver` where implemented.
- Respect `prefers-reduced-motion`.
- Maintain mobile readability and avoid horizontal overflow.

## Production deploy

A dedicated workflow exists:

`.github/workflows/deploy-subpage-videos-one-shot.yml`

It uploads:

- `about.html`
- `services.html`
- `works.html`
- `sitemap.xml`
- `assets/video/about.mp4`
- `assets/video/services.mp4`
- `assets/video/works.mp4`

The workflow was retriggered from commit `0ecd3769b142874d8c6731cb4eaf5fabc78cf506`.

Run ID: `34934631455`
Job ID: `104269789354`
Result: **success**

The verification step checked the live pages on `https://rss7.net` for the corresponding video references and performed HTTP range requests against all three live MP4 files. The job log ended with `Subpage videos are live.`

## Connectivity at handoff

Remote Desktop Commander was rechecked after a temporary disconnect.

Last verified status:

- PC01 / `TABLET-KLJ5CN5I` — online, auth token valid
- PC02 / `ks-pc02` — online, auth token valid

Do not assume a connection failure is permanent. If a remote operation fails, recheck device state and continue through GitHub Actions or another available route instead of stopping the whole task.

## User's execution rule

The user explicitly requested autonomous completion:

- Do not stop waiting for confirmation for ordinary implementation decisions.
- On errors or connection failures, switch methods and continue whatever can be completed.
- Repeat `investigate → implement → verify → fix → reverify` until the site is in a completed state.
- Check PC/mobile layout, links, menu, horizontal overflow, and errors.
- Only separate items at the end when they are truly blocked by external permissions or access that cannot be bypassed.

## Next session — start here

1. Read this `HANDOFF.md` first.
2. Confirm `main` is current and inspect any commits after this handoff.
3. Verify the three live pages visually on desktop and mobile widths, especially video sizing, text readability, menu operation, and horizontal overflow.
4. Verify `ABOUT`, `SERVICES`, and `WORKS` videos autoplay muted where allowed and loop correctly; ensure off-screen pausing does not break resume behavior.
5. Check TOP has not unintentionally received one of these three videos.
6. If visual issues are found, fix directly, redeploy, and repeat verification without waiting for user confirmation.

## Important design context

Brand direction is dark / futuristic / premium digital, with white/light-gray typography and cyan-blue accents. Preserve a modern high-end look. Avoid making sections feel like generic template blocks.

The user prefers motion and strong visual impact, but usability and text legibility take priority over decorative overlays.
