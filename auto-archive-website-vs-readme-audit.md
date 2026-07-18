# Auto Archive - Website vs. WordPress.org Readme Audit

Compared: [addasitebuilders.com/plugins/auto-archive-for-gravity-forms](https://addasitebuilders.com/plugins/auto-archive-for-gravity-forms/) vs. wp.org readme trunk (Stable tag 1.3.0), July 10, 2026.

---

## 1. Critical: the two sources disagree on what is free vs. Pro

This is the one to resolve first, because it changes every other recommendation and it undercuts the Pro upsell.

**Number of forms that can auto-archive**

- Website (free card, FAQ "How many forms", "Automatic Age Rule" card): free automates **one form**, by age only. "Unlimited forms - every form archives itself" is listed as a **Pro** benefit.
- Readme (free features + FAQ "Can I archive more than one form automatically?"): "**Automatic rules on every form** - each form can archive itself on a daily check." Every form, free.

**Recurring annual / date cutoff**

- Website: "Annual & date-cutoff rules" is a **Pro** benefit ("not just age"), reinforced by the badged "Annual & Date-Cutoff Rules Pro" card.
- Readme (free features): "**Recurring annual cutoff** - archive everything submitted before a set day each year (e.g. Aug 1)." Free, and listed since 1.0.0.

So the website sells "unlimited forms" and "annual cutoffs" as the core Pro value, while the readme gives both away in the free tier. One of them is wrong. Most likely the readme reflects what the shipped free plugin actually does and the website is an older/aspirational freemium split - but you need to confirm which is true, then align both.

**What Pro actually adds, per the readme** (a cleaner, more defensible split): date-**field** rules (archive by a date field on the form, e.g. program end date), **conditional** rules (archive only entries matching field values, e.g. "Decision = Declined"), Bulk Archive All, retention-based trash/delete, CSV/Excel export, off-site cloud backup, email summaries.

---

## 2. Feature discrepancies (independent of the free/Pro question)

**Pro features on the site are missing your two strongest differentiators.** The website's Pro list does not mention **conditional rules** or **date-field rules** at all - yet "automatically archive every Declined application" is arguably the most compelling Pro use case for your exact audience (grants, awards, admissions). The site leans on "annual cutoffs / unlimited forms," which per the readme are free. Swap the emphasis: lead Pro with conditional + date-field rules.

**Free "permanent delete on demand" is hidden on the site.** Readme free includes a confirm-guarded "Delete Permanently" bulk action on the Archived tab (free deletes on request; Pro adds backup-first + abort-on-failure). The website files all trash/delete under Pro, which is a slightly different story. Clarify: free = manual, on-demand permanent delete with no backup; Pro = scheduled retention delete, always backup-first.

**Free features present in the readme but not sold on the site:**

- **Rule preview** - "see how many entries the saved rule would archive today, before you enable it." A strong trust/confidence feature; add a card or bullet.
- **Archive audit trail** - every entry records who archived it and when ("Automatic (rule: ...)" or the acting user). Great for institutional/compliance buyers; not surfaced on the site.
- **One-click undo of a whole run** - a batch run can be restored in one click from the activity log (the site mentions bulk restore, but not run-level undo).
- **Automation health / Site Health check** - next-run visibility plus a proactive warning when cron stalls. This is a reliability selling point, not just an FAQ - low-traffic annual sites are exactly where cron fails.

**Verify `Tested up to: 7.0`** in the readme is a real released WordPress version; if not, WordPress.org may flag it.

---

## 3. FAQ gaps

**The readme has only 5 FAQs; the website has 10.** WordPress.org FAQs help both SEO and support deflection - port these strong ones from the site into the readme:

- Does this require Gravity Forms?
- Is there a free trial of Pro?
- Is archiving reversible?
- What's the difference between archive, trash, and delete?
- Can I test a rule before trusting it? (Run now)

**The readme's cron fix is better than the site's - port it the other way.** The readme's "The daily check doesn't seem to run" answer includes the actionable server-cron fix (`DISABLE_WP_CRON` + a `wget` cron job). The website's version is lighter. Add the concrete fix to the website FAQ.

**New FAQs worth adding to both** (gaps neither source covers, all likely to be asked by this audience):

- What happens to archived entries if I deactivate or uninstall the plugin?
- Does archiving affect Gravity Forms notifications, feeds, or connected add-ons (Zapier, webhooks, payment feeds)?
- Can I still export archived entries?
- Does this work on WordPress Multisite?
- Does archiving change entry IDs or break existing integrations / the GF REST API?
- Does archiving affect form entry limits or confirmation logic that counts entries?
- Data-retention / GDPR angle: can I use this to enforce a retention policy?

---

## 4. Marketing tweaks

- **Feature the "archive Declined applications" story.** Conditional rules map perfectly to admissions/grants/awards workflows and are absent from the site. This is a headline-worthy Pro use case.
- **Fix the value ladder.** Once the free/Pro split is settled, make the free tier's generosity a selling point ("every form, annual cutoffs, audit trail - free") and make Pro clearly about automation + safety at scale (conditional/date-field rules, retention delete, cloud backup). A confused ladder costs conversions in both directions.
- **Cross-sell Entry Digest.** You now have matched branding across both plugins - add a small "Pairs well with Entry Digest for Gravity Forms" note on each plugin page to build the house brand and cross-traffic.
- **Sell reliability explicitly.** The Site Health / next-run / cron-stall handling is unusually thoughtful for this category. "Knows when automation stalls and tells you" is a trust differentiator worth a card.
- **Headline "New cycle. Clean Slate." is strong** - keep it. The annual-program positioning is sharp and consistent; the fixes above are about accuracy and depth, not repositioning.

---

## Recommended order of operations

1. Decide the real free/Pro split (confirm against the shipped code).
2. Align the readme and the website to that split - same feature in the same tier in both places.
3. Re-emphasize Pro around conditional + date-field rules.
4. Expand readme FAQs (port 5 from the site); strengthen the site's cron FAQ.
5. Add the net-new FAQs to both.
