<?php
defined( 'ABSPATH' ) || exit;

/**
 * Pro: enable multi-form aggregation. With this on, the free plugin's editor
 * shows checkboxes (instead of a single radio) and a digest may combine entries
 * from several forms into one email.
 */
add_filter( 'dsagfe_allow_multiform', '__return_true' );

/**
 * Pro: apply per-form conditional filtering during a digest run.
 *
 * @param array $entries Entries fetched for this form.
 * @param array $d       Digest configuration.
 * @param int   $fid     Form ID.
 * @return array
 */
add_filter( 'dsagfe_run_entries', 'edfgfp_filter_run_entries', 10, 3 );
function edfgfp_filter_run_entries( array $entries, array $d, int $fid ): array {
	$conf = $d['filters'][ (string) $fid ] ?? [];
	if ( empty( $conf['rules'] ) ) {
		return $entries;
	}
	$logic = ( 'any' === ( $conf['logic'] ?? 'all' ) ) ? 'any' : 'all';
	$rules = $conf['rules'];

	return array_values( array_filter(
		$entries,
		static fn( $e ) => dsagfe_entry_matches( $e, $rules, $logic )
	) );
}

/**
 * Pro: build CSV/XLSX attachment file(s) for the run.
 *
 * @param string[] $attachments Paths gathered so far (empty from core).
 * @param array    $sections    Per-form section data.
 * @param array    $d           Digest configuration.
 * @param int      $total_count Total entries across sections.
 * @return string[] Temp file paths (deleted by core after sending).
 */
add_filter( 'dsagfe_attachments', 'edfgfp_build_attachments', 10, 4 );
function edfgfp_build_attachments( array $attachments, array $sections, array $d, int $total_count ): array {
	$format = $d['attach_format'] ?? 'none';
	if ( 'none' === $format || $total_count < 1 ) {
		return $attachments;
	}
	return array_merge( $attachments, dsagfe_build_attachments( $format, $sections ) );
}

/**
 * Pro: tell the email renderer that an attachment is present so it shows the
 * "complete set is in the attachment" note.
 *
 * @param bool  $has_attachment
 * @param array $d
 * @return bool
 */
add_filter( 'dsagfe_email_has_attachment', 'edfgfp_email_has_attachment', 10, 2 );
function edfgfp_email_has_attachment( bool $has_attachment, array $d ): bool {
	return 'none' !== ( $d['attach_format'] ?? 'none' );
}

/**
 * Pro: extend send-log retention. The free plugin keeps the last few sends; Pro
 * retains a configurable, much larger history. The count is stored in the
 * 'edfgfp_log_max' option (set via the control in log-settings.php) and defaults
 * to 1000 — effectively a complete history for any normal site. A stored value
 * of 0 falls back to the free plugin's default.
 *
 * @param int $max The free plugin's retention default.
 * @return int
 */
add_filter( 'dsagfe_log_max', 'edfgfp_log_max' );
function edfgfp_log_max( $max ): int {
	$pro_max = (int) get_option( 'edfgfp_log_max', 1000 );
	return $pro_max > 0 ? $pro_max : (int) $max;
}
