<?php
/**
 * Base hook provider.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright (c) 2016, AudioTheme, LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 */

/**
 * Base hook provider class.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
abstract class AudioTheme_Agent_AbstractProvider {
	/**
	 * Plugin instance.
	 *
	 * @since 1.0.0
	 * @var AudioTheme_Agent_Plugin
	 */
	protected $plugin;

	/**
	 * Set a reference to the main plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @param AudioTheme_Agent_Plugin $plugin Main plugin instance.
	 */
	public function set_plugin( $plugin ) {
		$this->plugin = $plugin;
		return $this;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	abstract public function register_hooks();
}
