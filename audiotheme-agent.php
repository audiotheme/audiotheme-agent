<?php
/**
 * AudioTheme Agent
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright (c) 2016, AudioTheme, LLC
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: AudioTheme Agent
 * Plugin URI:  https://audiotheme.com/
 * Description: Connect to AudioTheme.com to directly install premium themes and plugins, automatically update installed products, manage your subscriptions, and receive priority support.
 * Version:     1.3.0
 * Author:      AudioTheme
 * Author URI:  https://audiotheme.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: audiotheme-agent
 * Domain Path: /languages
 * Network:     true
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 */
define( 'AUDIOTHEME_AGENT_VERSION', '1.3.0' );

/**
 * Autoloader callback.
 *
 * Converts a class name to a file path and requires it if it exists.
 *
 * @since 1.0.0
 *
 * @param string $class Class name.
 */
function audiotheme_agent_autoloader( $class ) {
	if ( 0 !== strpos( $class, 'AudioTheme_Agent_' ) ) {
		return;
	}

	$file  = dirname( __FILE__ ) . '/classes/';
	$file .= str_replace( array( 'AudioTheme_Agent_', '_' ), array( '', '/' ), $class );
	$file .= '.php';

	if ( file_exists( $file ) ) {
		require_once( $file );
	}
}
spl_autoload_register( 'audiotheme_agent_autoloader' );

/**
 * Autoload mapped classes.
 *
 * @since 1.0.0
 *
 * @param string $class Class name.
 */
function audiotheme_agent_autoloader_classmap( $class ) {
	$class_map = array(
		'Automatic_Upgrader_Skin' => ABSPATH . 'wp-admin/includes/class-wp-upgrader.php',
		'Plugin_Upgrader'         => ABSPATH . 'wp-admin/includes/class-wp-upgrader.php',
		'Theme_Upgrader'          => ABSPATH . 'wp-admin/includes/class-wp-upgrader.php',
	);

	if ( isset( $class_map[ $class ] ) ) {
		require_once( $class_map[ $class ] );
	}
}
spl_autoload_register( 'audiotheme_agent_autoloader_classmap' );

/**
 * Retrieve the main plugin instance.
 *
 * @since 1.0.0
 *
 * @return AudioTheme_Agent_Plugin
 */
function audiotheme_agent() {
	static $instance;

	if ( null === $instance ) {
		$upload_dir = wp_upload_dir();
		$filename   = path_join( $upload_dir['basedir'], 'audiotheme/logs/agent.log' );

		$client   = new AudioTheme_Agent_Client();
		$logger   = new AudioTheme_Agent_Logger( $filename );
		$packages = new AudioTheme_Agent_PackageManager( $client );
		$instance = new AudioTheme_Agent_Plugin( $client, $packages );
		$instance->set_logger( $logger );
	}

	return $instance;
}

$audiotheme_agent = audiotheme_agent()
	->set_basename( plugin_basename( __FILE__ ) )
	->set_directory( plugin_dir_path( __FILE__ ) )
	->set_file( __FILE__ )
	->set_slug( 'audiotheme-agent' )
	->set_url( plugin_dir_url( __FILE__ ) )
	->register_hooks( new AudioTheme_Agent_Provider_Setup() )
	->register_hooks( new AudioTheme_Agent_Provider_UpdateManager() );

if ( is_admin() ) {
	$audiotheme_agent
		->register_hooks( new AudioTheme_Agent_Provider_I18n() )
		->register_hooks( new AudioTheme_Agent_Provider_AJAX() )
		->register_hooks( new AudioTheme_Agent_Screen_Main_Subscriptions() )
		->register_hooks( new AudioTheme_Agent_Screen_Main_Support() )
		->register_hooks( new AudioTheme_Agent_Provider_AudioThemeCompatibility() );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$audiotheme_agent->register_hooks( new AudioTheme_Agent_Screen_Main_Logs() );
		}
}

/**
 * Load the plugin.
 */
add_action( 'plugins_loaded', array( $audiotheme_agent, 'load_plugin' ) );
