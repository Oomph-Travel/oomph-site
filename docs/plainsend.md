# Plainsend — newsletter signup

Newsletter signups on this site go to **Plainsend**, Eric's own email app, which
sends through his own Amazon SES account. Repo: `~/projects/PlainSend`. App:
<https://app.plainsend.net>.

This replaced a Fluent Forms embed in the sitewide footer on 2026-08-12.

## Why it is not an embed or an iframe

The Fluent Forms newsletter form brought **43 KB of stylesheet to every page on
the site** for one email field, and because it sat in the sitewide footer there
was no page without it — which blocked the conditional-dequeue fix the
2026-07-28 LCP audit called for.

An iframe would have been no better: a second document with its own render-
blocking CSS, unable to inherit the brand tokens, so it would look like another
company's form dropped into the page.

What is there now is **plain markup in the child theme**, using `.oomph-field`
and `.oomph-btn` like every other form here, posting across origins to
Plainsend. It adds no stylesheet and no plugin.

## How it works

| Piece | Where | Why there |
| --- | --- | --- |
| Endpoint and form slug | `oomph-travel-core/includes/class-plainsend.php` | Environment-aware, so it belongs in the plugin and survives a theme switch |
| Markup | `kadence-oomph-child/inc/footer.php` | Presentation |
| Styles | `kadence-oomph-child/assets/css/components.css` (`.oomph-signup`) | Presentation |
| Submit script | `kadence-oomph-child/assets/js/newsletter.js` | ~1 KB, deferred, no dependencies |

**It works with JavaScript switched off.** The markup is a real `<form>` with a
real `action`, so without the script it posts and the visitor lands on a
thank-you page at Plainsend. The script is the enhancement that keeps them here
instead.

**Nothing per-request is in the markup.** SG Optimizer serves cached pages, so a
PHP nonce or server timestamp would be the cached one. Everything the anti-spam
checks need is set by the script or held by Plainsend.

## Environments

`Plainsend::form_slug()` sends production to the `newsletter` form and
staging/local to `newsletter-staging`. Both are real submissions against the
real app — that is the point of testing — but test signups land somewhere that
can be emptied without touching a subscriber.

Filters, if either ever needs to move: `oomph_plainsend_app_url`,
`oomph_plainsend_form_slug`.

## What Plainsend does that the old form did not

- **Double opt-in.** A signup is `pending` until the person clicks a link in a
  confirmation email. Nobody joins the list without proving the address.
- **Working unsubscribe**, one click, no login, plus the one-click header Gmail
  and Yahoo require of bulk senders.
- **Automatic suppression.** An address that hard-bounces or reports spam is
  removed within seconds and never emailed again.

The old form had none of these. It emailed Eric a notification and left the
address in the WordPress database.

## The one setting outside this repo

Plainsend refuses cross-origin posts from sites not on its allowlist — an open
endpoint would let any website on the internet spend the sending budget. On
Render, the Plainsend service needs:

```
FORM_ORIGINS=https://oomphtravel.com,https://www.oomphtravel.com
```

Add the staging hostname too while testing there. Without this, submissions
from the site are refused with a 403.

## Testing it

The Playwright smoke suite deliberately never submits forms. To check the
signup path by hand on staging: submit a real address, confirm the email
arrives, click the button in it, and check the contact turns from `pending` to
`subscribed` in Plainsend under **Forms & pages**.

Note that Plainsend is still in the **Amazon SES sandbox** (support case
`178643233200952`) — until that is lifted, confirmation emails only reach
addresses verified with Amazon.
