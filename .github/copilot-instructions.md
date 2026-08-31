# GitHub Copilot review instructions

RSS7 AI Works is a public Japanese website. Review pull requests as an independent reviewer before merge. Most routine implementation work is expected to come from Jules.

## First check

1. Read the related Issue and verify the PR stays inside its Goal, Scope, Acceptance Criteria, and Do Not Change sections.
2. Read `AGENTS.md` and apply the repository safety rules.
3. Prefer the smallest safe fix. Flag unrelated refactors or scope expansion.
4. `PROJECT_STATE.json` and `STATUS.md` are Actions-owned after bootstrap. Flag direct edits from normal agent PRs.

## Review priorities

1. Security: XSS, unsafe `innerHTML`, untrusted URLs, inline event handlers, secrets, credentials, upload endpoints, and accidentally published archives or backups.
2. Data loss / destructive behavior: file deletion, mass replacement, `.htaccess`, API/auth/deploy changes, database changes, secrets, domain or billing changes.
3. User trust: never claim a form was sent when it only opens `mailto:`; avoid unsupported guarantees about delivery dates, price, quality, rights, AI output, or partnerships.
4. Privacy: flag unnecessary personal data collection, unclear third-party AI processing, or missing consent and retention explanations.
5. Accessibility: semantic forms and buttons, keyboard access, labels, ARIA state, focus behavior, reduced motion, and content hidden when JavaScript fails.
6. SEO and integrity: canonical URLs, OG metadata, sitemap consistency, broken local links, structured data, and stale article URLs.
7. Performance: oversized images, blocking resources, duplicate scripts/styles, and avoidable network requests.
8. Regression risk: verify blog API fallback, article sanitization, mobile navigation, contact flow, and all GitHub Actions checks.

## High-risk rule

If a PR changes `.htaccess`, `api/`, GitHub Actions workflows, deletes files, alters authentication/payment/domain/deployment behavior, or exposes secrets, treat it as high risk. It must not be recommended for automatic merge and should require `needs-human-approval`.

## Review output

- Base findings on the actual diff and repository behavior.
- Mark security, data-loss, privacy, false-success, and broken-navigation issues as high priority.
- Do not approve claims that cannot be verified from repository evidence.
- Suggest the smallest safe fix and mention the affected path.
- Confirm whether the PR is suitable for low-risk automation.
- If no blocking problem exists, state that clearly.
