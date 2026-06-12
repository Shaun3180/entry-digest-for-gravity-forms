<?php
defined( 'ABSPATH' ) || exit;

// ════════════════════════════════════════════════════════════════
//  Pro gate — single source of truth
//  Every Pro feature in the plugin routes through dsagfe_is_pro(), so
//  the entire monetization boundary lives in this one file.
//  Backed by Freemius: can_use_premium_code() is true when the premium
//  build is running with a valid license OR an active trial (and false
//  for expired trials — i.e. trial-abuse safe).
//  Requires: a paid plan defined in the Freemius dashboard,
//  'has_paid_plans' => true in fs_dynamic_init(), and a premium build
//  deployed via Freemius. Until then this returns false for everyone.
// ════════════════════════════════════════════════════════════════
function dsagfe_is_pro(): bool {
	// The Freemius premium build running with a valid license or an active
	// trial is the single source of truth. There is intentionally no filter or
	// constant override here, so Pro features can never be unlocked by site
	// configuration (such as WP_DEBUG) — only by a genuine license.
	return function_exists( 'edfgf_fs' ) && edfgf_fs()->can_use_premium_code();
}

/**
 * Marketing copy for the Pro upsell, shown on locked controls.
 */
function dsagfe_pro_badge(): string {
	return '<span style="display:inline-block;margin-left:6px;padding:1px 7px;border-radius:9px;background:#7c3aed;color:#fff;font-size:10px;font-weight:700;letter-spacing:.3px;vertical-align:middle;">PRO</span>';
}

function dsagfe_upgrade_url(): string {
    if ( function_exists( 'edfgf_fs' ) ) {
        return edfgf_fs()->get_upgrade_url();
    }
    return 'https://addasitebuilders.com/plugins/entry-digest-for-gravity-forms/';
}
