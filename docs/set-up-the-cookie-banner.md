---
title: Set up the cookie consent banner
tags: cookies, consent, gdpr, banner, privacy, accept, reject, ribbon
page: admin/settings/cookie-consent
---

**Cookie Consent** shows a banner asking visitors to accept or reject cookies, and a status ribbon where they can review their choice. It only appears on the live site, not in development.

## Set it up

Open **Osmium Cookie Consent Settings**.

- **Enabled** switches the banner and ribbon on. It is off by default until your site's design includes it, so ask your developer to confirm.
- **Privacy Notice URL:** search for one of your pages, or paste any full address.
- **Banner Text:** what the banner says. Use `{privacyUrl}` as a placeholder for your privacy notice address. HTML is allowed, for example a link.
- **Status Text:** what the review window says. Use `{status}` and `{privacyUrl}`.
- **Accept Button Label** and **Reject Button Label**.
- **Cookie Name** is where the visitor's choice is stored. Changing it asks every visitor for consent again.

Press **Save Settings**.

Services that set cookies, such as analytics, wait for the visitor's consent.
