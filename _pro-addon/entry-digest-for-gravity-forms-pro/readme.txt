=== Entry Digest for Gravity Forms - Pro ===
Requires at least: 6.1
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pro add-on for Entry Digest for Gravity Forms. Adds multi-form aggregation, conditional filtering, CSV/Excel exports, role-based recipients, and custom email branding.

== Description ==

**Entry Digest for Gravity Forms - Pro** extends the free [Entry Digest for Gravity Forms](https://wordpress.org/plugins/entry-digest-for-gravity-forms/) plugin with advanced features for teams, agencies, and high-volume sites.

The free plugin must be installed and active for this add-on to work. All free features remain unchanged - Pro simply hooks in and unlocks additional capabilities.

= What's included =

**Multi-form aggregation** - combine entries from several Gravity Forms into a single digest email. Instead of separate emails for each form, one digest covers them all.

**Conditional filtering** - include only entries that match your rules. Filter by field value, comparison operator, and target value so recipients see only the submissions that matter to them.

**CSV and Excel (.xlsx) attachments** - automatically attach the period's entries as a downloadable spreadsheet. Choose CSV, Excel, or both, per digest.

**Role-based recipients** - deliver a digest to every WordPress user in a chosen role (Administrator, Editor, a custom role, etc.), without maintaining a manual address list.

**Custom email branding** - upload your logo, set an accent color, and replace the default footer credit with your own text. Every digest your site sends reflects your brand.

**Notification control** - switch a form's Gravity Forms notifications on or off from inside the digest editor, so you can stop per-entry emails for any form a digest already covers - without leaving the page.

**Extended send-log history** - configure how many past sends to retain per digest (default 5 in the free plugin). Useful for auditing or proving delivery over time.

= Requirements =

* [Entry Digest for Gravity Forms](https://wordpress.org/plugins/entry-digest-for-gravity-forms/) (free, installed and active)
* Gravity Forms
* WordPress 6.1+
* PHP 7.4+

== Changelog ==

= 1.5.0 =
* New: **Max entries in email** - cap the inline entry table at N rows per digest. The email notes the total entry count so recipients always know how many entries arrived, even when the table is capped or suppressed (0 = no table, attachment only). Pairs naturally with the CSV/Excel attachment, which always carries the full dataset regardless of the cap.
* Requires free plugin 2.9.0 or later (uses the new `edfgf_editor_entries_options` action to inject the setting into the editor).

= 1.4.0 =
* New: **Form and field ordering** - use up/down arrows in the digest editor to set the order forms appear (in multi-form digests) and the order fields appear as columns in the entry table. Selected items are listed first so they're easy to arrange.

= 1.3.0 =
* New: **Email font** - choose the font family for the whole email from a list of email-safe stacks (system sans-serif, Helvetica/Arial, Georgia, Times, and more). Set it under Email branding > Advanced colors & typography.
* New: **Header text color** - set the color of the digest title and subtitle in the email header.
* New: **Footer colors** - set a custom footer background color and footer text color.
* Fix: the "Right of title" logo position now places the logo flush against the right edge of the header. Previously the logo stayed on the left regardless of this setting.
* The finer styling controls are grouped under a collapsible "Advanced colors & typography" section so the branding panel stays uncluttered.

= 1.2.1 =
* Fix: Email preview now correctly displays logo images (removed iframe sandbox restriction that blocked external image URLs).
* New: Logo position control - choose whether the logo appears above, to the left of, or to the right of the digest title in the email header. Set it under Email branding in the digest editor.

= 1.2.0 =
* New: Email Preview - a "Preview Email" card in the digest editor renders the full styled email using your saved branding, fields, and settings, with realistic sample data. No real entries are used; Pro logo, accent color, and footer are applied so the preview is pixel-perfect to what recipients receive.
* New: Dynamic filter rows - conditional filter rules can now be added and removed live in the editor without a page reload. Row indices are automatically resequenced so saved rules are always clean and sequential.

= 1.1.0 =
* New: manage a form's Gravity Forms notifications right from the digest editor - switch any notification off so its per-entry email stops and only the digest goes out. Toggles edit Gravity Forms directly, are user-owned (never auto-reverted), and include a link to each notification.

= 1.0.1 =
* Compatibility with Entry Digest for Gravity Forms 2.6.1 - multi-form selection now uses the new dsagfe_form_selector_multiple filter.

= 1.0.0 =
* Initial Pro release.
* Multi-form aggregation: combine entries from multiple forms into one digest.
* Conditional filtering: include only entries matching field-level rules.
* CSV and Excel (.xlsx) attachments.
* Role-based recipients: deliver to all users in a chosen WordPress role.
* Custom email branding: logo, accent color, and white-label footer.
* Extended send-log history with configurable retention.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
