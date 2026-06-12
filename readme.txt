=== Entry Digest for Gravity Forms ===
Contributors: Shaun3180
Tags: gravity forms, email digest, form notifications, scheduled email, form entries
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Stop drowning in per-entry notification emails. Get one clean, scheduled digest of your Gravity Forms submissions — a summary plus an entry table.

== Description ==

**Entry Digest for Gravity Forms** replaces the flood of one-email-per-submission notifications with a single, readable digest delivered on your schedule. Each digest opens with a summary block (how many entries arrived and the date range) followed by an inline table of the actual submissions, so you can scan a day's or week's activity in one email instead of dozens.

Point it at a form, choose who receives it and when, pick which fields to show, and you're done. Digests are sent automatically via WP-Cron using your site's timezone.

= Why use it =

Gravity Forms' built-in notifications fire on every single submission. For a busy contact form, registration form, or order form, that means an inbox full of individual emails. Entry Digest rolls them up:

* A **summary block** with the new-entry count and reporting period.
* An **inline entry table** showing the fields you choose, formatted for easy reading on desktop and mobile.
* **Daily or weekly** scheduling, sent at the time and on the day you pick, in your site's timezone.
* **One-time sends** — schedule a digest for a specific future date and time, on its own or alongside a recurring schedule, with a custom lookback window.
* A **"Send Now"** button to preview a digest on demand.
* **Graceful quiet periods** — when no entries arrived you can either receive a tidy "no new entries" note (the default, so you're never left wondering) or choose to stay silent, per digest.

= Free features =

* One scheduled digest covering one form.
* Daily or weekly delivery on a chosen day/time, and/or a one-time send on a future date you pick.
* Configurable quiet-period behavior: send a "no new entries" note or stay silent.
* Per-field selection for the entry table.
* Summary block with entry count and date range.
* Clean, branded HTML email that renders well across mail clients.

= Pro features =

A separate Pro upgrade (sold off WordPress.org) adds scale, routing, and convenience for teams and agencies:

* Unlimited digests and multi-form aggregation into a single email.
* Per-recipient and per-role routing (e.g. send the admissions digest to one person, the IT digest to another).
* Conditional filtering — include only entries that match your rules.
* CSV and Excel (.xlsx) attachments of the full period's entries.

The free plugin is fully functional on its own; Pro is optional.

= Privacy =

Entry Digest does not phone home, track usage, or send any data to third parties. Digest emails are delivered through your own site's standard `wp_mail()` configuration. The plugin only reads the Gravity Forms entries you tell it to include.

== Frequently Asked Questions ==

= Does this require Gravity Forms? =

Yes. Entry Digest reads entries through the Gravity Forms API, so Gravity Forms must be installed and active for digests to send. If Gravity Forms is inactive, the plugin's settings remain available under Tools so your configuration is never lost.

= Where do I find the settings? =

Under **Forms › Entry Digest** in the WordPress admin (the Gravity Forms menu). If Gravity Forms isn't active, the page appears under **Tools › Entry Digest** instead.

= When are digests sent? =

Digests run on WP-Cron at the day/time you choose, in your site's timezone. Weekly digests cover the previous 7 days; daily digests cover the previous 24 hours. Note that WP-Cron fires on site traffic, so a very low-traffic site may benefit from a real server cron job.

= Can I send a digest just once on a specific date? =

Yes. Each digest has an optional **one-time send** field: pick a future date and time and the digest goes out once, then the date clears itself automatically. You can use it on its own (choose "One-time only") or in addition to a daily/weekly schedule. A separate lookback setting controls how far back the one-time send reaches for entries, and defaults to everything since the form was created.

= What happens when no new entries came in? =

By default the digest still sends a tidy "no new entries" note so recipients know it ran and nothing was missed. If you'd rather stay silent during quiet periods, set that digest's "When there are no new entries" option to "Don't send anything."

= Can I preview a digest without waiting for the schedule? =

Yes. Each digest has a **Send Now** button that builds and emails it immediately.

= Why didn't my digest arrive? =

Digests use your site's `wp_mail()` setup. If other WordPress emails aren't being delivered, a transactional email/SMTP plugin usually resolves it. The plugin logs delivery problems to your PHP error log.

= How many forms can the free version include? =

The free version covers one form in one digest. Multi-form aggregation and unlimited digests are part of Pro.

== Screenshots ==

1. The digest email — summary block plus the inline entry table.
2. The digest list, showing schedule and next-run time for each digest.
3. The digest editor: form, recipients, subject, and schedule.
4. The schedule picker — frequency, day, and time in your site timezone.
5. Per-form field selection for the entry table.

== Changelog ==

= 1.2.1 =
* Now translation-ready: every user-facing string is internationalized (text domain `entry-digest-for-gravity-forms`).
* Bundled translations for Spanish, Chinese (Simplified), German, French, Italian, Portuguese (Brazil), Russian, and Japanese, plus a .pot template for further translation.

= 1.2.0 =
* One-time scheduling: send a digest on a specific future date/time, on its own or alongside a daily/weekly schedule. The date clears itself after sending.
* Configurable lookback window for one-time sends, defaulting to "everything since the form was created."
* Configurable quiet-period behavior: choose per digest whether a zero-entry period sends a "no new entries" note or stays silent.

= 1.1.0 =
* Multi-digest architecture with per-digest scheduling.
* Per-form field selection and (Pro) conditional filtering and role/recipient routing.
* CSV and Excel attachment support (Pro).
* Automatic migration from the earlier single-config format.

= 1.0.0 =
* Initial release: single scheduled digest with summary block and entry table, daily/weekly delivery.

== Upgrade Notice ==

= 1.2.1 =
Adds full translation readiness and bundled translations for eight languages. No functional changes to existing digests. Safe to update.

= 1.2.0 =
Adds one-time (future-date) scheduling and a configurable quiet-period option. Existing digests are unaffected. Safe to update.

= 1.1.0 =
Adds per-digest scheduling and field selection, and migrates older settings automatically. Safe to update.
