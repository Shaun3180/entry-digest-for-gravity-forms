<?php
/**
 * Uninstall handler for Entry Digest for Gravity Forms.
 *
 * Runs only when the user deletes the plugin from the WordPress admin. Removes
 * the plugin's stored settings and clears any scheduled digest cron events so
 * nothing is left behind in the database. Mirrors the constants defined in the
 * main plugin file (EDFGF_OPTION_KEY / EDFGF_CRON_HOOK).
 */

// Exit if accessed directly or not invoked by the WordPress uninstall process.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

const EDFGF_OPTION_KEY     = 'edfgf_settings';
const EDFGF_LOG_OPTION_KEY = 'edfgf_send_log';
const EDFGF_CRON_HOOK      = 'edfgf_run_export';

/**
 * Remove this plugin's options and clear all of its scheduled cron events for the
 * current site.
 */
function edfgf_uninstall_cleanup() {
	delete_option( EDFGF_OPTION_KEY );
	delete_option( EDFGF_LOG_OPTION_KEY );

	// Remove the per-user meta the plugin stores (Pro-panel and overdue-notice
	// dismissals) for every user on this site, so nothing is left behind.
	delete_metadata( 'user', 0, 'edfgf_pro_panel_dismissed', '', true );
	delete_metadata( 'user', 0, 'edfgf_cron_notice_dismissed', '', true );

	// Clear every scheduled instance of our cron hook, regardless of args.
	$crons = function_exists( '_get_cron_array' ) ? _get_cron_array() : array();
	if ( ! empty( $crons ) ) {
		foreach ( $crons as $timestamp => $hooks ) {
			if ( isset( $hooks[ EDFGF_CRON_HOOK ] ) ) {
				foreach ( $hooks[ EDFGF_CRON_HOOK ] as $event ) {
					wp_unschedule_event( $timestamp, EDFGF_CRON_HOOK, $event['args'] );
				}
			}
		}
	}
	// Belt and suspenders for any no-arg legacy event.
	wp_clear_scheduled_hook( EDFGF_CRON_HOOK );
}

/**
 * Run cleanup across the install. On multisite, clean every site in the network;
 * otherwise just the current site. Wrapped in a function so its loop variables
 * stay out of the global scope.
 */
function edfgf_run_uninstall() {
	if ( is_multisite() ) {
		$edfgf_site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
		foreach ( $edfgf_site_ids as $edfgf_site_id ) {
			switch_to_blog( $edfgf_site_id );
			edfgf_uninstall_cleanup();
			restore_current_blog();
		}
	} else {
		edfgf_uninstall_cleanup();
	}
}

edfgf_run_uninstall();
