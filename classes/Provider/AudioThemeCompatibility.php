<?php
/**
 * AudioTheme plugin integration.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright (c) 2016, AudioTheme, LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 */

/**
 * AudioTheme plugin integration class.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
class AudioTheme_Agent_Provider_AudioThemeCompatibility extends AudioTheme_Agent_AbstractProvider {
	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {
		add_action( 'init',               array( $this, 'disable_audiotheme_updater' ), 1 );
		add_action( 'admin_menu',         array( $this, 'remove_settings_menu_item' ), 21 );
		add_action( 'network_admin_menu', array( $this, 'remove_settings_menu_item' ), 21 );
	}

	/**
	 * Disables update checks in the AudioTheme plugin.
	 *
	 * @since 1.0.0
	 */
	public function disable_audiotheme_updater() {
		remove_action( 'init', 'audiotheme_update' );
	}

	/**
	 * Remove the Settings admin submenu item.
	 *
	 * @since 1.0.0
	 */
	public function remove_settings_menu_item() {
		remove_submenu_page(
			is_network_admin() ? 'settings.php' : 'audiotheme',
			'audiotheme-settings'
		);
	}
}
