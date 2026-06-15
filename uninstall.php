<?php
/**
 * Uninstall handler for Entry Digest for Gravity Forms.
 *
 * Runs only when the user deletes the plugin from the WordPress admin. Removes
 * the plugin's stored settings and clears any scheduled digest cron events so
 * nothing is left behind in the database. Mirrors the constants defined in the
 * main plugin file (DSAGFE_OPTION_KEY / DSAGFE_CRON_HOOK).
 */

// Exit if accessed directly or not invoked by the WordPress uninstall process.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

const DSAGFE_OPTION_KEY     = 'dsagfe_settings';
const DSAGFE_LOG_OPTION_KEY = 'dsagfe_send_log';
const DSAGFE_CRON_HOOK      = 'dsagfe_run_export';

/**
 * Remove this plugin's options and clear all of its scheduled cron events for the
 * current site.
 */
function dsagfe_uninstall_cleanup() {
	delete_option( DSAGFE_OPTION_KEY );
	delete_option( DSAGFE_LOG_OPTION_KEY );

	// Clear every scheduled instance of our cron hook, regardless of args.
	$crons = function_exists( '_get_cron_array' ) ? _get_cron_array() : array();
	if ( ! empty( $crons ) ) {
		foreach ( $crons as $timestamp => $hooks ) {
			if ( isset( $hooks[ DSAGFE_CRON_HOOK ] ) ) {
				foreach ( $hooks[ DSAGFE_CRON_HOOK ] as $event ) {
					wp_unschedule_event( $timestamp, DSAGFE_CRON_HOOK, $event['args'] );
				}
			}
		}
	}
	// Belt and suspenders for any no-arg legacy event.
	wp_clear_scheduled_hook( DSAGFE_CRON_HOOK );
}

// Handle multisite: clean each site in the network; otherwise just the one site.
if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		dsagfe_uninstall_cleanup();
		restore_current_blog();
	}
} else {
	dsagfe_uninstall_cleanup();
}
