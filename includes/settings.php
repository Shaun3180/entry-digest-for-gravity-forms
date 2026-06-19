<?php
defined( 'ABSPATH' ) || exit;

// ── Add-on pointer ───────────────────────────────────────────────
/**
 * Product-page URL for the optional Pro add-on (filterable).
 *
 * Used only to link out from the informational "Entry Digest Pro" page and the
 * editor tip. This is a plain marketing link - it gates nothing.
 */
function dsagfe_pro_url(): string {
	return (string) apply_filters(
		'dsagfe_pro_url',
		'https://addasitebuilders.com/plugins/entry-digest-for-gravity-forms/'
	);
}

// ── i18n helpers ─────────────────────────────────────────────────
/**
 * Translated weekday label for an internal English day key ('monday'…'sunday').
 * Falls back to a capitalized version of the key for anything unexpected.
 */
function dsagfe_day_label( string $day ): string {
	$map = [
		'monday'    => __( 'Monday', 'entry-digest-for-gravity-forms' ),
		'tuesday'   => __( 'Tuesday', 'entry-digest-for-gravity-forms' ),
		'wednesday' => __( 'Wednesday', 'entry-digest-for-gravity-forms' ),
		'thursday'  => __( 'Thursday', 'entry-digest-for-gravity-forms' ),
		'friday'    => __( 'Friday', 'entry-digest-for-gravity-forms' ),
		'saturday'  => __( 'Saturday', 'entry-digest-for-gravity-forms' ),
		'sunday'    => __( 'Sunday', 'entry-digest-for-gravity-forms' ),
	];
	return $map[ strtolower( $day ) ] ?? ucfirst( $day );
}

// ── Default settings ─────────────────────────────────────────────
/**
 * Defaults for a single digest configuration.
 */
function dsagfe_digest_defaults(): array {
	return [
		'id'            => '',
		'label'         => __( 'Entry digest', 'entry-digest-for-gravity-forms' ),
		'form_ids'      => [ 1 ],          // One form per digest in core; Pro add-on allows several.
		'to_email'      => '',
		'roles'         => [],             // Add-on: WP roles whose members also receive the digest.
		'email_subject' => __( 'Your Gravity Forms entry digest', 'entry-digest-for-gravity-forms' ),
		'frequency'     => 'weekly',       // 'weekly' | 'daily' | 'none' (none = no recurring digest; one-time only)
		'paused'        => false,          // true = keep config but stop all scheduled sends (manual Send Now / test still work)
		'send_day'      => 'monday',       // used only when frequency = weekly
		'send_time'     => '08:00',        // time-of-day for recurring sends (site timezone)
		'onetime_at'    => '',             // optional one-time send: 'Y-m-d H:i' in site timezone, or '' for none
		'onetime_lookback_days' => 0,      // entries window for the one-time send; 0 = everything up to the send moment
		'quiet_behavior' => 'send',        // 'send' = always email a "no new entries" note; 'skip' = stay silent when 0 entries
		'link_entries'  => true,           // link each table row to its entry in the WP admin (off for external recipients)
		'fields'        => [],             // map: form_id => [ field/input keys ]; empty = all for that form
		'filters'       => [],             // Add-on: map: form_id => [ 'logic' => all|any, 'rules' => [ {field,op,value} ] ]
		'attach_format' => 'none',         // Add-on: 'none' | 'xlsx' | 'csv'
	];
}

/**
 * Top-level option defaults.
 */
function dsagfe_defaults(): array {
	return [
		'schema'  => DSAGFE_SCHEMA_VERSION,
		'digests' => [],
	];
}

/**
 * Generate a short, unique digest id.
 */
function dsagfe_new_id(): string {
	return 'd_' . substr( md5( uniqid( '', true ) ), 0, 10 );
}

/**
 * Normalize one digest config: fill defaults, coerce types.
 */
function dsagfe_normalize_digest( array $d, string $id = '' ): array {
	$def = dsagfe_digest_defaults();
	$out = wp_parse_args( $d, $def );

	$out['id']            = $id ?: ( ! empty( $d['id'] ) ? (string) $d['id'] : dsagfe_new_id() );
	$out['label']         = (string) $out['label'];
	$out['to_email']      = (string) $out['to_email'];
	$out['email_subject'] = (string) $out['email_subject'];
	$out['frequency']     = in_array( $out['frequency'], [ 'daily', 'weekly', 'none' ], true ) ? $out['frequency'] : 'weekly';
	$out['paused']        = ! empty( $out['paused'] );
	$out['send_day']      = (string) $out['send_day'];
	$out['send_time']     = (string) $out['send_time'];

	// One-time send: keep only a well-formed 'Y-m-d H:i' string; anything else clears it.
	$onetime = trim( (string) $out['onetime_at'] );
	$out['onetime_at'] = preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $onetime ) ? $onetime : '';
	$out['onetime_lookback_days'] = max( 0, (int) $out['onetime_lookback_days'] );
	$out['quiet_behavior'] = in_array( $out['quiet_behavior'], [ 'send', 'skip' ], true ) ? $out['quiet_behavior'] : 'send';
	$out['link_entries']  = ! empty( $out['link_entries'] );
	$out['attach_format'] = in_array( $out['attach_format'], [ 'none', 'xlsx', 'csv' ], true ) ? $out['attach_format'] : 'none';

	$out['form_ids'] = array_values( array_unique( array_map( 'intval', (array) $out['form_ids'] ) ) );
	$out['form_ids'] = array_values( array_filter( $out['form_ids'], static fn( $f ) => $f > 0 ) );
	if ( empty( $out['form_ids'] ) ) {
		$out['form_ids'] = [ 1 ];
	}

	$out['roles']   = array_values( array_filter( array_map( 'sanitize_key', (array) $out['roles'] ) ) );
	$out['fields']  = is_array( $out['fields'] )  ? $out['fields']  : [];
	$out['filters'] = is_array( $out['filters'] ) ? $out['filters'] : [];

	return $out;
}

/**
 * Migrate the legacy flat (v1) settings blob into a single v2 digest.
 */
function dsagfe_migrate_legacy( array $old ): array {
	$form_id = max( 1, (int) ( $old['form_id'] ?? 1 ) );
	return dsagfe_normalize_digest( [
		'label'         => __( 'Digest', 'entry-digest-for-gravity-forms' ),
		'form_ids'      => [ $form_id ],
		'to_email'      => (string) ( $old['to_email'] ?? '' ),
		'email_subject' => (string) ( $old['email_subject'] ?? __( 'Your Gravity Forms entry digest', 'entry-digest-for-gravity-forms' ) ),
		'frequency'     => (string) ( $old['frequency'] ?? 'weekly' ),
		'send_day'      => (string) ( $old['send_day'] ?? 'monday' ),
		'send_time'     => (string) ( $old['send_time'] ?? '08:00' ),
		'fields'        => [ (string) $form_id => (array) ( $old['include_fields'] ?? [] ) ],
		// Attachments are now a Pro feature. Preserve the prior choice so it
		// returns automatically if/when the site activates Pro.
		'attach_format' => ! empty( $old['attach_xlsx'] ) ? 'xlsx' : 'none',
	] );
}

/**
 * Load all settings, running migration and normalization.
 */
function dsagfe_get_settings(): array {
	$raw = get_option( DSAGFE_OPTION_KEY, [] );
	$raw = is_array( $raw ) ? $raw : [];

	// Legacy v1 detection: a flat config carried 'form_id' at the top level.
	if ( isset( $raw['form_id'] ) && ! isset( $raw['digests'] ) ) {
		$migrated = dsagfe_migrate_legacy( $raw );
		$raw      = [ 'schema' => DSAGFE_SCHEMA_VERSION, 'digests' => [ $migrated['id'] => $migrated ] ];
		update_option( DSAGFE_OPTION_KEY, $raw );
	}

	$raw = wp_parse_args( $raw, dsagfe_defaults() );
	if ( ! is_array( $raw['digests'] ) ) {
		$raw['digests'] = [];
	}

	$normalized = [];
	foreach ( $raw['digests'] as $id => $d ) {
		$id                = (string) $id;
		$normalized[ $id ] = dsagfe_normalize_digest( (array) $d, $id );
	}
	$raw['digests'] = $normalized;

	return $raw;
}

/**
 * All configured digests (associative by id, insertion order preserved).
 */
function dsagfe_get_digests(): array {
	return dsagfe_get_settings()['digests'];
}

/**
 * The digests that are actually scheduled / sent - every configured digest that
 * is not paused. Paused digests keep their configuration but are excluded from
 * cron scheduling (manual "Send Now" and test sends still work on them).
 */
function dsagfe_active_digests(): array {
	return array_filter( dsagfe_get_digests(), static fn( $d ) => empty( $d['paused'] ) );
}

/**
 * Fetch a single digest by id, or null.
 */
function dsagfe_get_digest( string $id ): ?array {
	$digests = dsagfe_get_digests();
	return $digests[ $id ] ?? null;
}

/**
 * Persist the full digests map and re-sync the cron schedule.
 */
function dsagfe_save_digests( array $digests ): void {
	update_option( DSAGFE_OPTION_KEY, [
		'schema'  => DSAGFE_SCHEMA_VERSION,
		'digests' => $digests,
	] );
	dsagfe_reschedule_all();
}
