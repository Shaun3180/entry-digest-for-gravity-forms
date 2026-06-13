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
	// trial is the single source of truth in production.
	$is_pro = function_exists( 'edfgf_fs' ) && edfgf_fs()->can_use_premium_code();

	// Developer-only overrides for local testing. Honored ONLY when WP_DEBUG is
	// enabled, so a live (production-config) site can never be unlocked via a
	// filter — only by a genuine license.
	//
	// - 'dsagfe_is_pro'     : per-plugin override.
	// - 'gf_dev_force_pro'  : shared override honored by all of our Freemius
	//                         plugins, so one dev helper can unlock them all.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$is_pro = (bool) apply_filters( 'dsagfe_is_pro', $is_pro );
		$is_pro = (bool) apply_filters( 'gf_dev_force_pro', $is_pro );
	}

	return $is_pro;
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
