# Auto Archive - corrected website copy (paste-ready)

The core fix: your site sells **every-form automation** and **annual/date cutoffs** as Pro, but both are **free**. Below, each block is labeled with the section it replaces on the page. The through-line: free is generous (every form, annual cutoffs, audit trail, rule preview); Pro is about *smarter targeting and safe deletion at scale* (conditional + date-field rules, retention delete, backups).

---

## A. "What's Included" cards (free toolkit)

Two corrections: the **Automatic Age Rule** card wrongly says "one form," and the **Annual & Date-Cutoff** card is wrongly badged Pro. Replace those cards; the other free cards are fine. Add the two new free cards below.

**Automatic Rules - Every Form** *(replaces "Automatic Age Rule")*
Give any form its own rule and let a daily check do the work - "archive entries older than N days/months," or a recurring annual cutoff. A **Run now** button applies the rule on demand, and an activity log records every change.

**Annual & Date-Cutoff Rules** *(remove the Pro badge - this is free)*
Clear last cycle automatically: archive everything submitted before a set day each year - September 1, say - so a new program year starts clean. No Pro required.

**Rule Preview** *(new free card)*
Before you enable anything, see exactly how many entries the saved rule would archive today. No surprises, no guesswork.

**Archive Audit Trail** *(new free card)*
Every archived entry records who archived it and when - a staff member's name, or "Automatic (rule: …)." Built for teams and institutions that need a paper trail.

> Keep the existing free cards: **Soft Archive & Restore**, **Dedicated Archived View**, **Per-Entry & Bulk Restore**, **Accurate Entry Counts**, **Your Data Stays Yours**. Remove the **Cloud Backup Before Delete Pro** card from the *free* toolkit section - it belongs under Go Pro.

---

## B. Go Pro section - re-pitch around targeting + safety

Your current Pro pitch leads with "unlimited forms" and "annual cutoffs," which are free. Lead instead with what Pro genuinely adds.

**Section heading:** Built for programs that run on a calendar
**Subhead (replace):** Free keeps every form tidy and automates archiving by age or annual date. Pro is about the harder problems: archiving the *right* entries automatically, and reclaiming space safely - with a backup behind every deletion.

**Archive by decision, not just by date Pro** *(new - your strongest Pro hook)*
Conditional rules archive only the entries that match - "Decision is Declined," "Status is Withdrawn," any field on the form. Last cycle's rejected applications clear themselves the moment they're marked, while everything active stays put.

**Archive by a date on the form Pro** *(new)*
Date-field rules key off a date *in the submission* - a program end date, an event date, a graduation date - not the day it was submitted. Perfect when "old" depends on the applicant's own timeline.

**Never delete without a safety net Pro**
Before a single entry is removed, Pro writes a CSV/Excel backup to your own S3 or Backblaze bucket. If the upload fails, the whole run stops - so a backup is never optional and never silent.

**Reclaim real space on a schedule Pro**
Soft archiving keeps entries in the database. When you need the space back, Pro moves entries to trash or permanently deletes them on a retention schedule - always after the backup succeeds. Plus **Bulk Archive All** to clear an entire form in one click, and per-run email summaries with the backup attached.

---

## C. Pricing table - feature lists

**Free column (replace bullets):**

- Per-entry and bulk archive & restore, with one-click undo
- Dedicated "Archived" view with accurate counts
- Automatic rules on **every** form - age *or* recurring annual cutoff
- Rule preview and a **Run now** button for on-demand archiving
- Archive audit trail + full activity log
- On-demand permanent delete when you want the space back
- No phone-home - your data stays on your site

**Pro column (replace bullets):**

- Everything in Free
- **Conditional rules** - archive only entries matching field values (e.g. "Declined")
- **Date-field rules** - archive by a date on the form, not the submission date
- **Bulk Archive All** on a form in one click
- Trash & permanent delete on a retention schedule, export-first
- Off-site cloud backup to S3 / Backblaze B2 (abort-on-failure)
- CSV & Excel (.xlsx) export + email summaries
- Priority support

---

## D. FAQ - corrections and additions

**Replace this answer (it contradicts the plugin):**

*How many forms can the free version archive?*
All of them. Free gives **every** form its own automatic rule - by age or by a recurring annual cutoff - plus manual archiving anywhere. Pro adds conditional and date-field rules, Bulk Archive All, and retention-based deletion with backups.

**Strengthen the cron answer** - add the concrete fix:

*My scheduled run didn't happen. What should I check?*
Daily runs fire on WP-Cron, which only runs when your site gets traffic - so low-traffic annual sites can stall. The plugin warns you on its settings screens and via Tools › Site Health when that happens. The reliable fix is a real server cron: add `define( 'DISABLE_WP_CRON', true );` to `wp-config.php`, then have your host request `wp-cron.php` on a schedule (many managed hosts have a one-click "cron job" setting). In the meantime, **Run now** applies any rule instantly. For Pro summary emails, if other WordPress email isn't arriving, an SMTP plugin like WP Mail SMTP usually fixes it.

**Add these FAQs (all verified against the plugin's code):**

*Are archived entries still included in exports?*
Yes. Archiving only hides entries from the admin Entries list - Gravity Forms' Export Entries tool still includes them, since they remain ordinary entries in your database. Pro can additionally export just the archived set as CSV or Excel.

*Will archiving break my integrations?*
No. Archiving is a display flag on the Entries list. The Gravity Forms REST API, webhooks, Zapier, and payment/CRM feeds all still see archived entries normally, and notifications fire at submission time - long before archiving - so nothing is re-triggered or suppressed.

*What happens to archived entries if I remove the plugin?*
Nothing is lost. Uninstalling clears the plugin's own settings, but the archive flag on each entry is deliberately preserved - so your archived entries survive an uninstall and come back if you reinstall.

*Who can archive or restore entries?*
Anyone who can already manage Gravity Forms entries (the delete-entries capability, or an admin). Permanent deletion adds a confirmation step because it can't be undone.

> Keep your existing strong FAQs (requires Gravity Forms, does archiving delete, free trial of Pro, where to find settings, archive vs trash vs delete, test before you trust, is archiving reversible). A **Multisite** FAQ is drafted in `auto-archive-new-faqs.md` - confirm behavior on a real network before adding it.
