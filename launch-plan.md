# Entry Digest for Gravity Forms — Launch Plan

*Working name: Gravity Forms Digest. Public name/slug: **Entry Digest for Gravity Forms** (`entry-digest-for-gravity-forms`).*

Last updated: June 5, 2026

---

## 1. Positioning

**One-line pitch:** Stop drowning in per-entry notification emails. Get a clean, scheduled digest of your Gravity Forms submissions instead.

**The wedge:** The closest competitor, [GravityExport](https://www.gravitykit.com/products/gravityexport/), is a *file export* tool — it turns entries into CSV/Excel/PDF and ships those files to Dropbox/SFTP/download links. It does **not** do a readable, human-friendly periodic digest in the inbox. That's the job Entry Digest owns. We compete on the *digest experience and routing*, not on export destinations.

**What this means for the build:** Lead with the digest itself (readable summary + entry table, on a schedule). Treat file attachments and cloud destinations (Dropbox/Drive/Slack) as secondary Pro conveniences released *after* launch — never as the headline. Going head-to-head with GravityExport on exports is a losing fight; owning "the digest plugin" is a winnable one.

---

## 2. Free vs. Pro

The free tier must be genuinely complete for a single-form user (it already satisfies the CSU RamCard use case). It's also the entire marketing channel — installs, reviews, and repo SEO all come from it. Pro meters on **scale, routing, and convenience**, not on crippling core function.

| Capability | Free | Pro |
|---|---|---|
| Scheduled digest (daily / weekly) | ✅ 1 digest | ✅ Unlimited digests |
| Forms per digest | 1 form | Multiple forms aggregated into one digest |
| Recipients | 1 recipient / fixed list | Multiple recipients + per-recipient/role routing |
| Summary block (entry count, date range) | ✅ Basic | ✅ + week-over-week deltas, simple charts |
| Entry table with selected fields | ✅ | ✅ |
| Schedule options | Daily / weekly | Hourly, custom intervals, specific days/times |
| Conditional filtering (only entries matching X) | — | ✅ |
| CSV / Excel / PDF attachment of the period's entries | — | ✅ |
| Cloud destinations (Dropbox, Drive, Sheets, Slack, webhook) | — | ✅ (phased — see §3) |
| White-label / remove "powered by" | — | ✅ |
| Support | Community / forum | Priority email |

**Conversion logic:** different buyers hit different walls. The agency hits *unlimited digests / multi-site*. The busy office hits *routing* (admissions digest to one person, IT digest to another). The data person hits *attachments / destinations*. Stacking several upgrade triggers beats betting on one.

**Guardrail:** Keep the free tier at exactly **one digest, one form, one recipient list.** If free quietly does multi-form, you erase the main reason to upgrade. This is the single most important line to hold.

---

## 3. Release sequencing (don't build all of Pro for v1)

Ship Pro lean, then use later features as a marketing cadence — every "New in Pro: Slack delivery" post is a re-engagement email and a changelog bump.

**v1.0 — Free (the .org launch)**
- One scheduled digest, one form, one recipient list
- Daily/weekly schedule
- Summary block (count + date range) + entry table with field selection
- Clean, branded HTML email template
- Freemius SDK wired in (free plan only, upgrade prompts tasteful)

**v1.0 — Pro (launch the paid tier alongside)**
- Unlimited digests + multi-form aggregation
- Per-recipient / per-role routing
- Conditional filtering
- CSV/Excel attachment
- 7–14 day Pro trial via Freemius

**v1.1 — "New in Pro"**
- Dropbox + Google Drive/Sheets destinations
- Summary analytics / charts, week-over-week deltas

**v1.2 — "New in Pro"**
- Slack + generic webhook delivery
- PDF attachment + light template styling

---

## 4. Pricing

Mirror the site-tier model Gravity Forms buyers already expect, and undercut GravityExport (~$57/yr on sale, ~$85 list, single site) cleanly.

| Tier | Suggested annual price |
|---|---|
| Single site | $39 / yr |
| Up to 3 sites | $69 / yr |
| Unlimited sites | $129 / yr |

- **Subscriptions, not lifetime.** Skip lifetime licenses at launch — they kill the recurring revenue that makes the $200/mo goal possible. You can add a limited lifetime option later as a promo if you want.
- **Offer a Pro trial.** Freemius supports 7–14 day trials; they meaningfully lift conversion.
- **Annual renewals** with the standard first-year discount pattern are fine and expected.

---

## 5. Revenue math (realistic expectations)

Target: **$200/mo ≈ $2,400/yr.**

- At $39/yr single-site, that's roughly **60–70 active paying licenses** (more once you factor renewal churn — call it ~80 sales/year on an ongoing basis).
- WordPress freemium converts ~**1–3%** of *active* free installs to paid.
- At ~2% conversion, that implies roughly **3,000–4,000 active free installs.**

Very achievable for a useful niche plugin — but it's a **6–18 month ramp**, not a launch-week number. The levers that get you there: free-tier quality, a keyword-rich readme, strong screenshots, fast support, and review count. Treat the free plugin as marketing, not as a loss.

---

## 6. Freemius notes

Freemius handles licensing, payments, EU VAT, renewals, subscriptions, update delivery, in-dashboard upgrades, and trials — the stuff you don't want to build yourself.

- **Fees (as of Oct 2025 pricing):** ~4.7% base + 2.3% for the full WordPress solution = **~7%**, plus ~3.5% average payment-gateway fee. Budget roughly **~10% all-in** at low volume; the Freemius share drops as you scale (down to 0.5% at very high revenue).
- **.org compliance:** Freemius is allowed on the repo. Keep upgrade prompts tasteful — aggressive nag screens get plugins flagged in review.
- **Architecture:** Free plugin on the .org repo with the Freemius SDK bundled; Pro delivered as a separate add-on/package through Freemius (it handles the secure update channel).

---

## 7. WordPress.org submission checklist

- [ ] **Name/slug:** Submit as **"Entry Digest for Gravity Forms"**, slug `entry-digest-for-gravity-forms`. Your own brand ("Entry Digest") leads; "for Gravity Forms" follows. *Do not* lead with "Gravity Forms" / "GravityForms" / "GF" — both the [WP.org naming guidelines](https://make.wordpress.org/plugins/2015/10/05/guidelines-for-plugins-that-include-company-andor-product-names-in-the-plugin-name/) and the [Gravity Forms trademark policy](https://www.gravityforms.com/trademark/) require this, and the review team will auto-rename or reject otherwise.
- [ ] **GPL license** (v2 or later) — required for the .org repo.
- [ ] **Free plugin is genuinely functional on its own** — not a crippled ad for Pro. (Single-form digest clears this easily.)
- [ ] **No phoning home without consent** — any external calls (incl. Freemius opt-in) must be opt-in and disclosed.
- [ ] **`readme.txt`** following the [WP.org standard](https://wordpress.org/plugins/readme.txt): keyword-rich short description, full description, FAQ, screenshots section, changelog, "Tested up to" version.
- [ ] **Screenshots** — at minimum: the digest email itself, the settings screen, the schedule picker. These drive install conversion.
- [ ] **Tested against current WordPress + Gravity Forms versions**; declare minimum versions.
- [ ] **Sanitize/escape all I/O**, nonce-protect settings forms, follow WP coding + security standards (review team checks this).
- [ ] **Unique function/class prefixes** to avoid collisions.
- [ ] **Support plan:** monitor the .org support forum actively in the first weeks — early reviews are disproportionately valuable.

---

## 8. Immediate next step

Build **v1 Free, Feature 1: the single-form scheduled digest** — the core engine that queries new entries for one form over a period and emails a formatted summary + table on a daily/weekly schedule. Everything else hangs off this.

Sources:
- [GravityExport product/pricing](https://www.gravitykit.com/products/gravityexport/)
- [WP.org plugin naming guidelines](https://make.wordpress.org/plugins/2015/10/05/guidelines-for-plugins-that-include-company-andor-product-names-in-the-plugin-name/)
- [Gravity Forms trademark policy](https://www.gravityforms.com/trademark/)
- [Freemius pricing](https://freemius.com/wordpress/pricing/)
