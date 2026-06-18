<?php
/**
 * PHPUnit bootstrap for Blockophon.
 *
 * Expects to run inside @wordpress/env via:
 *   wp-env run tests-cli php vendor/bin/phpunit
 *
 * WP_TESTS_DIR is set automatically by wp-env to the WordPress test library.
 *
 * @package Blockophon
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "Could not find $_tests_dir/includes/functions.php\n";
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__ ) . '/blockophon.php';
	}
);

require $_tests_dir . '/includes/bootstrap.php';
