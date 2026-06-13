<?php
defined( 'ABSPATH' ) || exit;

// ════════════════════════════════════════════════════════════════
//  Conditional filtering engine (Pro)
// ════════════════════════════════════════════════════════════════
function dsagfe_filter_operators(): array {
	return [
		'is'           => __( 'is', 'entry-digest-for-gravity-forms' ),
		'is_not'       => __( 'is not', 'entry-digest-for-gravity-forms' ),
		'contains'     => __( 'contains', 'entry-digest-for-gravity-forms' ),
		'not_contains' => __( 'does not contain', 'entry-digest-for-gravity-forms' ),
		'gt'           => __( 'greater than', 'entry-digest-for-gravity-forms' ),
		'lt'           => __( 'less than', 'entry-digest-for-gravity-forms' ),
		'empty'        => __( 'is empty', 'entry-digest-for-gravity-forms' ),
		'not_empty'    => __( 'is not empty', 'entry-digest-for-gravity-forms' ),
	];
}

/**
 * Does an entry satisfy a set of filter rules under the given logic?
 *
 * @param array  $entry GF entry.
 * @param array  $rules List of [ 'field' => key, 'op' => op, 'value' => str ].
 * @param string $logic 'all' | 'any'.
 */
function dsagfe_entry_matches( array $entry, array $rules, string $logic = 'all' ): bool {
	if ( empty( $rules ) ) {
		return true;
	}

	$results = [];
	foreach ( $rules as $rule ) {
		$field  = (string) ( $rule['field'] ?? '' );
		$op     = (string) ( $rule['op'] ?? 'is' );
		$target = (string) ( $rule['value'] ?? '' );
		$val    = (string) ( $entry[ $field ] ?? '' );

		switch ( $op ) {
			case 'is':
				$ok = ( 0 === strcasecmp( trim( $val ), trim( $target ) ) );
				break;
			case 'is_not':
				$ok = ( 0 !== strcasecmp( trim( $val ), trim( $target ) ) );
				break;
			case 'contains':
				$ok = ( '' !== $target && false !== stripos( $val, $target ) );
				break;
			case 'not_contains':
				$ok = ( '' === $target || false === stripos( $val, $target ) );
				break;
			case 'gt':
				$ok = ( is_numeric( $val ) && is_numeric( $target ) && (float) $val > (float) $target );
				break;
			case 'lt':
				$ok = ( is_numeric( $val ) && is_numeric( $target ) && (float) $val < (float) $target );
				break;
			case 'empty':
				$ok = ( '' === trim( $val ) );
				break;
			case 'not_empty':
				$ok = ( '' !== trim( $val ) );
				break;
			default:
				$ok = true;
		}
		$results[] = $ok;
	}

	if ( 'any' === $logic ) {
		return in_array( true, $results, true );
	}
	return ! in_array( false, $results, true );
}
/**
 * Parse posted filter rules (mirrors dsagfe_handle_save). Pro only.
 *
 * @return array form_id => [ 'logic' => all|any, 'rules' => [ {field,op,value} ] ]
 */
function dsagfe_parse_posted_filters( array $raw, array $form_ids, bool $is_pro ): array {
	$filters = [];
	if ( ! $is_pro ) {
		return $filters;
	}
	$filters_in = (array) ( $raw['filters'] ?? [] );
	$logic_in   = (array) ( $raw['filter_logic'] ?? [] );
	$ops        = array_keys( dsagfe_filter_operators() );
	foreach ( $form_ids as $fid ) {
		$rules_raw = (array) ( $filters_in[ (string) $fid ] ?? [] );
		$rules     = [];
		foreach ( $rules_raw as $r ) {
			$field = (string) ( $r['field'] ?? '' );
			$op    = (string) ( $r['op'] ?? '' );
			if ( '' === $field || ! in_array( $op, $ops, true ) ) {
				continue;
			}
			$rules[] = [
				'field' => preg_match( '/^\d+(\.\d+)?$/', $field ) ? $field : '',
				'op'    => $op,
				'value' => sanitize_text_field( (string) ( $r['value'] ?? '' ) ),
			];
		}
		$rules = array_values( array_filter( $rules, static fn( $r ) => '' !== $r['field'] ) );
		if ( $rules ) {
			$logic = ( 'any' === ( $logic_in[ (string) $fid ] ?? 'all' ) ) ? 'any' : 'all';
			$filters[ (string) $fid ] = [ 'logic' => $logic, 'rules' => $rules ];
		}
	}
	return $filters;
}
