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
		add_action( 'audiotheme_agent_authorized_client', array( $this, 'flush_package_caches' ) );

		if ( is_admin() ) {
			add_filter( 'wp_redirect', array( $this, 'filter_redirects' ), 1 );
			add_filter( 'plugin_action_links_' . $this->get_basename(), array( $this, 'filter_action_links' ) );
			add_filter( 'network_admin_plugin_action_links_' . $this->get_basename(), array( $this, 'filter_action_links' ) );
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

	/**
	 * Filter plugin action links.
	 *
	 * Adds a 'Manage' link pointing to the admin screen.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $actions Array of action links.
	 * @return array
	 */
	public function filter_action_links( $actions ) {
		$actions['manage'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( 'page', 'audiotheme-agent', self_admin_url( 'index.php' ) ) ),
			esc_html__( 'Manage', 'audiotheme-agent' )
		);

		return $actions;
	}

	/**
	 * Flush package caches.
	 *
	 * @since 1.3.0
	 */
	public function flush_package_caches() {
		$this->logger->log( 'notice', 'Flushing the package caches.' );
		delete_site_transient( 'update_plugins' );
		delete_site_transient( 'update_themes' );
		$packages = $this->packages->flush()->prepare_packages_for_js();
	}
}
