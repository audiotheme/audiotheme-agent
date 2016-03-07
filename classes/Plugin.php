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
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var AudioTheme_Agent_Logger
	 */
	protected $logger;

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
	 * Proxy access to read-only properties.
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
			case 'logger' :
				return $this->logger;
			case 'packages' :
				return $this->packages;
		}
	}

	/**
	 * Set a logger.
	 *
	 * @since 1.0.0
	 *
	 * @param  AudioTheme_Agent_Logger $logger Logger instance.
	 * @return $this
	 */
	public function set_logger( $logger ) {
		$this->logger = $logger;
		$this->client->set_logger( $logger );
		return $this;
	}

	/**
	 * Load the plugin.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin() {
		add_action( 'admin_post_authorize-audiotheme-agent', array( $this->client, 'handle_callback' ) );

		if ( is_admin() ) {
			add_filter( 'wp_redirect', array( $this, 'filter_redirects' ), 1 );
		}
	}

	/**
	 * Redirect back to the Agent screen after activating a plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string $location Default redirection location.
	 * @return string
	 */
	public function filter_redirects( $location ) {
		if (
			false !== strpos( $location, 'plugins.php' ) &&
			! empty( $_REQUEST['referrer'] ) &&
			'audiotheme-agent' === $_REQUEST['referrer'] &&
			false === strpos( $location, 'error=true' )
		) {
			$redirect = self_admin_url( 'index.php?page=audiotheme-agent' );
			$redirect = wp_sanitize_redirect( $redirect );
			$location = wp_validate_redirect( $redirect, $location );
		}

		return $location;
	}
}
