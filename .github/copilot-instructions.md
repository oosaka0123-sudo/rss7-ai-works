# GitHub Copilot review instructions

RSS7 AI Works is a public Japanese website. Review pull requests as an independent reviewer before merge.

Prioritize:

1. Security: XSS, unsafe `innerHTML`, untrusted URLs, inline event handlers, secrets, credentials, upload endpoints, and accidentally published archives or backups.
2. User trust: distinguish server acceptance from mailbox delivery; never claim guaranteed email delivery. Avoid unsupported guarantees about delivery dates, price, quality, rights, AI output, or partnerships.
3. Privacy: flag unnecessary personal data collection, unclear third-party AI processing, or missing consent and retention explanations.
4. Accessibility: semantic forms and buttons, keyboard access, labels, ARIA state, focus behavior, reduced motion, and content hidden when JavaScript fails.
5. SEO and integrity: canonical URLs, OG metadata, sitemap consistency, broken local links, structured data, and stale article URLs.
6. Performance: oversized images, blocking resources, duplicate scripts/styles, and avoidable network requests.
7. Regression risk: verify blog API fallback, article sanitization, mobile navigation, contact flow, and all GitHub Actions checks.

Review rules:

- Base findings on the actual diff and repository behavior.
- Mark security, data-loss, privacy, false-success, and broken-navigation issues as high priority.
- Do not approve claims that cannot be verified from repository evidence.
- Suggest the smallest safe fix and mention the affected path.
- If no blocking problem exists, state that clearly.
