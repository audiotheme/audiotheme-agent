<?php
/**
 * Main plugin.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright (c) 2016, AudioTheme, LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 */

/**
 * Main plugin class.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
class AudioTheme_Agent_Plugin extends AudioTheme_Agent_AbstractPlugin {
	/**
	 * AudioTheme.com REST API client.
	 *
	 * @since 1.0.0
	 * @var AudioTheme_Agent_Client
	 */
	protected $client;

	/**
	 * Package manager.
	 *
	 * @since 1.0.0
	 * @var AudioTheme_Agent_PackageManager
	 */
	protected $packages;

	/**
	 * Constructor method.
	 *
	 * @since 1.0.0
	 *
	 * @param AudioTheme_Agent_Client         $client   AudioTheme.com REST API client.
	 * @param AudioTheme_Agent_PackageManager $packages Package manager.
	 */
	public function __construct( $client, $packages ) {
		$this->client = $client;
		$this->packages = $packages;
	}

	/**
	 * Proxy access to protected methods.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $name Property name.
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'client' :
				return $this->client;
			case 'packages' :
				return $this->packages;
		}
	}

	/**
	 * Load the plugin.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin() {
		add_action( 'admin_post_authorize-audiotheme-agent', array( $this->client, 'handle_callback' ) );
	}
}
