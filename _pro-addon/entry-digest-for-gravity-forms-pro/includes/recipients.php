<?php
defined( 'ABSPATH' ) || exit;

/**
 * Pro: append role-based recipients.
 *
 * Hooks the free plugin's 'edfgf_recipients' filter to add the account emails
 * of every user in the digest's selected roles. The free plugin then validates
 * and de-duplicates the combined list.
 *
 * @param string[] $emails Recipient emails gathered so far.
 * @param array    $d      The digest configuration.
 * @return string[]
 */
add_filter( 'edfgf_recipients', 'edfgfp_add_role_recipients', 10, 2 );
function edfgfp_add_role_recipients( array $emails, array $d ): array {
	$roles = array_values( array_filter( array_map( 'sanitize_key', (array) ( $d['roles'] ?? [] ) ) ) );
	if ( empty( $roles ) ) {
		return $emails;
	}

	$users = get_users( [
		'role__in' => $roles,
		'fields'   => [ 'user_email' ],
	] );
	foreach ( $users as $u ) {
		$emails[] = $u->user_email;
	}

	return $emails;
}
