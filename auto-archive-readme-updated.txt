=== Auto Archive for Gravity Forms ===
Contributors: Shaun3180
Tags: gravity forms, archive, form entries, entry management, data retention
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically archive older Gravity Forms entries on a schedule — built for annual programs clearing out last cycle's submissions.

== Description ==

**Auto Archive for Gravity Forms** keeps your entry lists clean by moving older submissions into a dedicated archive instead of deleting them. It is designed for organizations — universities, registration programs, seasonal applications — that run on an annual cycle and want the current cohort front-and-center while last year's submissions are tucked away but recoverable.

Archived entries are flagged and hidden from the default Gravity Forms entries view, but remain safely in the database under a dedicated "Archived" tab, where they can be browsed and restored at any time. Archiving never deletes anything.

= Free features =

* **Archive & restore individual entries** — an "Archive" link on every entry in the Entries list, and on the entry detail screen. Restore just as easily.
* **Dedicated "Archived" view** — a filter tab on the Entries list that shows archived entries, with accurate counts kept out of your active totals.
* **Automatic rules on every form** — each form can archive itself on a daily check, with its own rule. A "Run now" button lets you apply a rule on demand.
* **Rolling age rule** — "archive entries older than N days/months."
* **Recurring annual cutoff** — archive everything submitted before a set day each year (e.g. Aug 1), ideal for clearing last cycle at the start of a new program year.
* **Rule preview** — see how many entries the saved rule would archive today, before you enable it or hit "Run now."
* **Archive audit trail** — every archived entry records who archived it and when: a person's name for a by-hand archive, or "Automatic (rule: …)" for a rule. Shown on the entry detail screen and in an "Archived" column on the Archived tab.
* **One-click undo** — each automatic or "Run now" run is grouped, so the whole batch can be restored in a single click straight from the activity log.
* **Activity log** — a running record of what was archived, when, and by which rule, under Forms › Settings › Auto Archive.
* **Automation health** — the per-form settings card shows when the next daily check is due, and a Site Health check plus a warning notice flag it if scheduled runs stop firing (common on low-traffic sites where WordPress cron can stall).
* **Permanent delete on demand** — archiving itself never deletes, but a confirm-guarded "Delete Permanently" bulk action on the Archived tab lets you clear entries for good when you choose to. Free deletes on request; the Pro add-on exports a backup first (and aborts if the backup fails).

= Pro add-on =

The optional **Auto Archive for Gravity Forms - Pro** add-on is a separate plugin (available from the developer) that hooks into this one to add:

* **Date-field rules** — archive based on a date field on the form itself (e.g. a program end date), not the submission date.
* **Conditional rules** — only archive entries matching field values (e.g. "Decision is Declined").
* **Bulk Archive All** — archive every active entry on a form in one click.
* **Trash & permanent delete** — go beyond soft archive on a retention schedule, always exporting a backup first.
* **CSV / Excel export** — on demand, or automatically before a destructive run.
* **Off-site cloud backup** — push a backup to Amazon S3 or Backblaze B2 before deleting.
* **Email summaries** — a per-run report with the backup attached.

The free plugin is fully functional on its own; nothing on its screens is locked or disabled.

== Installation ==

1. Install and activate Gravity Forms.
2. Upload the `auto-archive-for-gravity-forms` folder to `/wp-content/plugins/`, or install through the Plugins screen.
3. Activate the plugin.
4. Archive entries from any form's **Entries** list using the per-row "Archive" link, or open a form's **Settings › Auto Archive** tab to set up the automatic age rule. The global activity log lives at **Forms › Settings › Auto Archive**.

== Frequently Asked Questions ==

= Does this require Gravity Forms? =

Yes. Auto Archive reads and updates entries through the Gravity Forms API, so Gravity Forms must be installed and active. If Gravity Forms is deactivated, your Auto Archive settings are kept safe so nothing is lost when it's reactivated.

= Does archiving delete my entries? =

No. Archiving flags entries and hides them from the default view. They stay in the database and can be restored at any time from the Archived tab.

= Is archiving reversible? =

Always, for archived entries — restore any single entry from its row, or pull a whole run back at once with one-click undo from the activity log. Only the optional "Delete Permanently" action (free, on request) or the Pro add-on's retention deletion removes data, and both are separate, deliberate steps.

= What's the difference between archive, trash, and delete? =

**Archive** hides an entry from your active list but keeps it in the database, fully reversible. **Trash** (via Gravity Forms, or Pro's retention schedule) moves it to Gravity Forms' recoverable trash. **Permanent delete** removes it for good to reclaim space — the free plugin offers this as a confirm-guarded bulk action on the Archived tab, and the Pro add-on exports a backup first (aborting if the backup fails).

= Can I archive more than one form automatically? =

Yes — every form can have its own automatic rule, free. The Pro add-on adds date-field and conditional rules, retention-based deletion with backups, exports, and more.

= Can I test a rule before I trust it? =

Yes. The **Run now** button on a form's Auto Archive tab applies your rule on demand, and the rule preview shows how many entries it would archive today — so you can confirm exactly what will happen before relying on the daily schedule.

= Where did the entries go? =

Open the form's **Entries** list and click the **Archived** filter tab to see and restore them.

= Are archived entries still included in exports? =

Yes. Archiving only hides entries from the admin Entries list; it doesn't touch Gravity Forms' Export Entries tool, which still includes archived entries because they remain ordinary entries in your database. (Pro can also export just the archived or about-to-be-deleted set as CSV or native Excel.)

= Does archiving affect notifications, feeds, webhooks, the REST API, or other add-ons? =

No. Archiving is a display flag on the admin Entries list. Anything that reads entries directly — the Gravity Forms REST API, GFAPI, webhooks, Zapier, and payment or CRM feeds — continues to see archived entries normally, and your form's entry limits and reporting are unaffected because the entries stay in the database. Notifications and feeds run at submission time, long before an entry is archived, so archiving never re-triggers or suppresses them.

= Who can archive, restore, or delete entries? =

Anyone with permission to manage Gravity Forms entries — the `gravityforms_delete_entries` capability, or a site administrator. The permanent-delete action is additionally guarded by a confirmation dialog because it can't be undone.

= What happens to archived entries if I uninstall the plugin? =

Nothing is lost. Deleting the plugin removes its own settings (rules, schedules, and activity log), but the archive flag on each entry is deliberately preserved — so archived entries survive an uninstall and reappear in the Archived tab if you reinstall.

= Is there a free trial of Pro? =

Yes. Every Pro feature is free to try for 14 days, no credit card required. Use the **Run now** button during your trial to watch the full pipeline (archive → export → cloud backup → summary email) run in seconds instead of waiting for a scheduled cutoff. If you cancel, you drop back to the fully-functional free version.

= The daily check doesn't seem to run. Why not? =

Automatic archiving runs on WordPress's built-in scheduler (WP-Cron), which only fires when someone visits your site. Sites that run on an annual cycle often get very little traffic between programs, so the scheduler can sit idle for days and the daily check never happens. If that occurs, the plugin shows a warning on its settings screens and a **Tools › Site Health** check flags it.

The reliable fix is to have your server run WP-Cron on a real schedule instead of relying on visits:

1. Add this line to `wp-config.php` to turn off the visit-triggered scheduler: `define( 'DISABLE_WP_CRON', true );`
2. Create a server cron job that requests `wp-cron.php` on a schedule, for example once an hour: `wget -q -O - https://your-site.example/wp-cron.php?doing_wp_cron >/dev/null 2>&1`

Many managed WordPress hosts offer a "real cron" or "cron job" setting that does this for you — check your host's documentation or support. You can always apply a rule immediately in the meantime with the **Run now** button on a form's Auto Archive tab.

= When will automatic archiving run next? =

The per-form **Auto Archive** tab shows the next scheduled daily check (for example, "Next daily check: tomorrow at 6:12 am"). All forms share a single daily check.

== Changelog ==

= 1.3.0 =
* New: next-run visibility. The per-form Auto Archive card now shows when the daily check is next due (e.g. "Next daily check: tomorrow at 6:12 am"), so it's clear at a glance that automation is alive.
* New: WP-Cron reliability handling. A lightweight heartbeat records each daily run, so the plugin can tell "cron is firing but nothing was due" apart from "cron isn't firing." If automatic archiving is enabled but the daily check hasn't run in over 48 hours, a warning notice appears on the plugin's screens and a Tools › Site Health check flags it, with guidance on setting up a real server cron — the common failure mode on low-traffic annual-program sites.
* New: FAQ entries covering why the daily check might not run and how to make it reliable.

= 1.2.0 =
* New: audit trail for archives. Every archived entry now records who archived it and when — an automatic rule shows as "Automatic (rule: …)", a by-hand archive shows the acting user. This appears as an "Archived on {date} by {actor}" line on the entry detail page and as an "Archived" column on the Archived tab. Entries archived before this update show "actor not recorded", and the actor stays readable even if the user account is later deleted.
* New: Undo for soft-archive runs. Each automatic or "Run now" archive run is grouped so the whole batch can be restored in one click, straight from the activity log.
* New: rule preview on the per-form settings screen — see how many entries the saved rule would archive today, before enabling it or running it.

= 1.1.0 =
* New: "Delete Permanently" bulk action on the Archived tab, guarded by a confirmation dialog. Before removing anything it fires the gfaa_pre_bulk_delete_archived filter so the Pro add-on can export a backup first (and abort the deletion if that backup fails). Without the add-on the delete proceeds — free deletes on request, Pro adds the backup safety net.
* Fix: archived entries that were also trashed or marked as spam could become invisible in every view. The active-view exclusion no longer applies on the Trash and Spam views, so such entries remain visible where they belong.
* Security/robustness: notices now distinguish success from error states.

= 1.0.0 =
* Initial release.
* Archive and restore individual entries from the Entries list and the entry detail screen — archiving never deletes anything.
* Dedicated "Archived" filter tab, with archived counts kept out of your active totals.
* Automatic rules on every form — rolling age ("archive entries older than N days/months") or a recurring annual cutoff — on a daily check, plus a "Run now" button to apply them on demand.
* Activity log of what was archived, when, and by which rule, under Forms &rsaquo; Settings &rsaquo; Auto Archive.
* Contextual Help tab on the Entries screen.
* Built to WordPress.org security and coding standards: output escaping, capability checks, and nonce verification throughout.
* Pro features ship as a separate add-on plugin; nothing on the free screens is locked or disabled.
