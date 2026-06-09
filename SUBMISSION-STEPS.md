# Entry Digest for Gravity Forms — WP.org submission steps

Status as of June 6, 2026. Everything in the **Done** section is handled in the files in this folder. The **You must do** section needs your running Local site and your hands.

---

## Done (in this folder)

- **`entry-digest-for-gravity-forms.php`** — main plugin file, renamed to match the slug. Header now declares Plugin Name, Plugin URI, Description, Version (1.1.0), Requires at least (6.0), Requires PHP (7.4), Author (Add-A-Site Apps), Author URI, License (GPL-2.0-or-later), License URI, and Text Domain.
- **`readme.txt`** — WP.org-standard: keyword-rich short description, full description, FAQ, screenshots captions, changelog, Upgrade Notice. Contributors set to `Shaun3180`, Tested up to 6.9, Stable tag 1.1.0.
- **`uninstall.php`** — deletes the `dsagfe_settings` option and clears scheduled cron on delete (multisite-aware). Clean removal.
- **Security/standards review** — passed on read: all POST actions are nonce-protected and capability-gated; output escaped; input sanitized; consistent `dsagfe_` / `DSAGFE_` prefixes; no external/phone-home calls.

---

## You must do before submitting

### 1. Folder name
The plugin folder **must** be named `entry-digest-for-gravity-forms` (it becomes the slug). In your Local site, the path should be:
`.../wp-content/plugins/entry-digest-for-gravity-forms/entry-digest-for-gravity-forms.php`
Copy the three files from this folder into there, replacing the old `gravity-forms-digest.php`.

### 2. Lint + smoke test on the running site
You have PHP in Local; I don't in this sandbox. Confirm:
- `php -l entry-digest-for-gravity-forms.php` and `php -l uninstall.php` report no syntax errors.
- Activate against **WordPress 6.9** and **Gravity Forms 2.9.x** (current versions). Create a digest, hit **Send Now**, confirm the email arrives and renders.
- Deactivate, delete, and confirm the `dsagfe_settings` option is gone (uninstall.php fired).

### 3. Confirm declared versions
The readme currently claims **Tested up to: 6.9**. Only keep that if you've actually run it on 6.9 — reviewers spot-check. Adjust if you tested a different version. Minimum Gravity Forms: the readme says 2.5+ in spirit; if you want to state a hard GF minimum, add it to the FAQ.

### 4. Screenshots (drives install conversion)
Capture these on your Local site, name them exactly, and place them in an **`/assets`** folder (preferred — assets aren't shipped in the download) or the plugin root. PNG, ideally ~1280px wide:

| File | Shot |
|---|---|
| `screenshot-1.png` | A real digest email open in your mail client — summary block + entry table |
| `screenshot-2.png` | The digest **list** screen (Forms › Entry Digest) showing schedule + next run |
| `screenshot-3.png` | The digest **editor** — form, recipients, subject, schedule |
| `screenshot-4.png` | The **schedule** controls — frequency / day / time |
| `screenshot-5.png` | Per-form **field selection** checkboxes |

The numbers map to the captions already written in `readme.txt`'s Screenshots section.

### 5. Submit
Upload the plugin ZIP at https://wordpress.org/plugins/developers/add/ for manual review. After approval you'll get SVN access; the `/assets` folder (screenshots, icon, banner) goes in SVN `assets/`, not in `trunk/`. First review can take a few days to weeks.

### 6. After it goes live
- Watch the **.org support forum** daily for the first couple of weeks — early reviews matter disproportionately.
- A plugin **icon** (256×256) and **banner** (772×250) aren't required but strongly lift install rate; add them to SVN `assets/` when you can.

---

## Optional polish (not required for approval)

- **Translation-readiness (i18n):** strings aren't wrapped in `__()` yet. The Text Domain header is already declared, so this can be added in a later version without disruption.
- **Plugin URI page:** `https://addasitebuilders.com/plugins` is referenced in the header — make sure that page exists (or redirects) before launch, since it's linked from the plugin list.
- **GPL note:** consider adding a `LICENSE` file with the full GPLv2 text to the folder (the header + License URI already satisfy the requirement, but it's a nice convention).
