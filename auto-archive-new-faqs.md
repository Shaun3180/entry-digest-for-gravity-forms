# Auto Archive - net-new FAQ set

Questions neither the website nor the readme currently answer. Answers are grounded in the plugin's actual source (trunk rev 3603523), so they're safe to publish - except the one flagged below.

---

**What happens to my archived entries if I deactivate or delete the plugin?**

Nothing is lost. Deactivating changes nothing about your data. If you fully delete the plugin, its own settings (rules, schedules, activity log) are removed - but the archive flag on each entry is deliberately left in place, so your archived entries survive an uninstall and reappear in the Archived tab if you reinstall. Auto Archive never silently discards entry data on removal.

**Are archived entries still included in Gravity Forms exports?**

Yes. Archiving only hides entries from the admin Entries list - it doesn't touch Gravity Forms' Export Entries tool, which still includes archived entries because they remain ordinary entries in your database. (Pro adds its own export of just the archived or about-to-be-deleted set, as CSV or native Excel, on demand or automatically before a destructive run.)

**Does archiving affect notifications, feeds, webhooks, the REST API, or my other add-ons?**

No. Archiving is a display flag on the admin Entries list. Anything that reads entries directly - the Gravity Forms REST API, GFAPI, webhooks, Zapier, and payment/CRM feeds - continues to see archived entries normally, and your form's entry limits and reporting are unaffected because the entries are still in the database. Notifications and feeds also run at submission time, long before an entry is archived, so archiving never re-triggers or suppresses them. In short: it declutters your working view without changing how the rest of your stack sees the data.

**Who is allowed to archive, restore, or delete entries?**

Anyone with permission to manage Gravity Forms entries - specifically the `gravityforms_delete_entries` capability (or a site administrator). The permanent-delete action on the Archived tab is additionally guarded by a confirmation dialog, since it can't be undone.

**Does Auto Archive work on WordPress Multisite?** &nbsp; ⚠ *verify before publishing*

Auto Archive runs per site: each site in a network keeps its own rules, Archived tab, and activity log. Network-activate it (or activate per site) and configure each form's rule on that site as usual. *[Confirm on a real Multisite install before you publish this one - I inferred it from the single-site architecture; I didn't see Multisite-specific code.]*

---

*The first four are verified against the shipped code. The Multisite answer is the only one to confirm before publishing.*
