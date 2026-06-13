<?php
defined( 'ABSPATH' ) || exit;

/**
 * Licensing gate for the Pro add-on.
 *
 * This is a STUB. Because this add-on is distributed off WordPress.org, it is
 * free to enforce a license. Wire in your provider of choice here — for example
 * Freemius (edfgf_fs()->can_use_premium_code()), Easy Digital Downloads
 * Software Licensing, Lemon Squeezy, or your own license API — and return true
 * only for valid, active licenses.
 *
 * Until you integrate a provider, the constant below lets you toggle Pro on for
 * development. Replace this whole function with a real check before shipping.
 *
 * @return bool Whether this site is licensed to use Pro features.
 */
function edfgfp_is_licensed(): bool {
	/**
	 * Filter the Pro license status. Lets your licensing integration (or a
	 * dev/testing override) decide whether Pro features are enabled.
	 *
	 * @param bool $licensed Default false.
	 */
	$licensed = (bool) apply_filters( 'edfgfp_is_licensed', false );

	// Development convenience: define EDFGFP_DEV_LICENSE in wp-config.php to
	// enable Pro locally without a real license.
	if ( defined( 'EDFGFP_DEV_LICENSE' ) && EDFGFP_DEV_LICENSE ) {
		$licensed = true;
	}

	return $licensed;
}
