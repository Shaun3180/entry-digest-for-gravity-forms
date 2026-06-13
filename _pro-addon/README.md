# Entry Digest for Gravity Forms — Pro (add-on scaffold)

This is the **paid** companion to the free *Entry Digest for Gravity Forms*
plugin. It is distributed from **addasitebuilders.com**, never from
WordPress.org, and must not be bundled inside the free plugin's zip.

## Why it's separate

WordPress.org requires every plugin it hosts to be fully functional with no
locked or license-gated features. So the monetized features live here, in a
standalone plugin that activates *alongside* the free one and layers its
features on through the free plugin's documented hooks. The free plugin works
completely on its own; this add-on simply enriches it when present and licensed.

## How it hooks in

The free plugin (v2.0.0+) exposes these extension points, all used here:

| Hook | Type | Purpose |
|------|------|---------|
| `dsagfe_recipients` | filter | Append role-based recipient emails |
| `dsagfe_run_entries` | filter | Apply conditional filtering during a run |
| `dsagfe_attachments` | filter | Build CSV/XLSX attachment files |
| `dsagfe_email_has_attachment` | filter | Show the "see attachment" note in the email |
| `dsagfe_editor_after_recipients` | action | Render the "Send to roles" editor row |
| `dsagfe_editor_after_schedule` | action | Render the attachment-format editor row |
| `dsagfe_editor_form_block` | action | Render per-form conditional-filter UI |
| `dsagfe_save_digest` | filter | Persist roles, filters, and attachment format |
| `dsagfe_preview_count` | filter | Make the live count preview respect filters |

## Files

```
entry-digest-for-gravity-forms-pro/
├── entry-digest-for-gravity-forms-pro.php   Bootstrap; checks free plugin + license
└── includes/
    ├── licensing.php     License gate (STUB — wire your provider here)
    ├── filters.php       Conditional-filter engine (moved out of free plugin)
    ├── export.php        CSV/XLSX writers (moved out of free plugin)
    ├── recipients.php    Role-based recipients
    ├── run.php           Filtering + attachments during a digest run
    └── editor-ui.php     Editor controls, save handling, filtered preview count
```

## Before you ship it

1. **Move this folder to its own repository.** It only lives inside the free
   plugin's workspace here for convenience; it is excluded from the free zip via
   `.distignore`.
2. **Implement `includes/licensing.php`.** It's a stub returning `false`. Wire in
   Freemius, EDD Software Licensing, Lemon Squeezy, or your own license API and
   return `true` only for valid, active licenses. For local testing you can
   `define( 'EDFGFP_DEV_LICENSE', true );` in `wp-config.php`.
3. Set up updates/delivery through your licensing provider so buyers receive the
   plugin and its updates from your site.
