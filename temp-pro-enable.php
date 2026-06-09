<?php
/**
 * Plugin Name: Temp Pro Enable for Entry Digest
 * Description: Forces Entry Digest plugin to treat site as Pro for testing.
 */
defined( 'ABSPATH' ) || exit;
add_filter( 'dsagfe_is_pro', '__return_true' );
?>