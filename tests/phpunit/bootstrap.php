<?php
/**
 * Load the Composer autoloader.
 */
if ( file_exists( dirname( dirname( __DIR__ ) ) . '/vendor/autoload.php' ) ) {
	require( dirname( dirname( __DIR__ ) ) . '/vendor/autoload.php' );
}

define( 'AUDIOTHEME_AGENT_TESTS_DIR', __DIR__ );

/**
 * Load the WordPress tests.
 */
$_tests_directory = _locate_wordpress_tests_directory();
_load_wordpress_tests( $_tests_directory );

function _load_wordpress_tests( $tests_directory ) {
	$GLOBALS['wp_tests_options'] = array(
		'active_plugins'  => array(
			'audiotheme-agent/audiotheme-agent.php',
		),
		'timezone_string' => 'America/Los_Angeles',
	);

	require_once $tests_directory . '/includes/functions.php';

	tests_add_filter( 'muplugins_loaded', function() {
		#require( dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/vendor/autoload.php' );
		require( dirname( dirname( __DIR__ ) ) . '/audiotheme-agent.php' );
	} );

	require $tests_directory . '/includes/bootstrap.php';
}

function _locate_wordpress_tests_directory() {
	$directory = getenv( 'WP_TESTS_DIR' );

	if ( ! $directory ) {
		if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
			$directory = getenv( 'WP_DEVELOP_DIR' ) . 'tests/phpunit';
		} elseif ( file_exists( '../../../../../tests/phpunit/includes/bootstrap.php' ) ) {
			$directory = '../../../../../tests/phpunit';
		} elseif ( file_exists( '/tmp/wordpress-tests-lib/includes/bootstrap.php' ) ) {
			$directory = '/tmp/wordpress-tests-lib';
		}
	}

	return $directory;
}
