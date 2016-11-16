<?php
/**
 * Subscriptions screen.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright (c) 2016, AudioTheme, LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 */

/**
 * Subscriptions screen class.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
class AudioTheme_Agent_Screen_Main_Subscriptions extends AudioTheme_Agent_Screen_Main {
	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {
		if ( 'default' !== $this->get_current_tab_id() ) {
			return;
		}

		parent::register_hooks();
	}

	/**
	 * Set up the screen.
	 *
	 * @since 1.0.0
	 */
	public function load_screen() {
		$this->maybe_update_client();
		$this->maybe_disconnect_client();
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		$this->add_help_tab();
	}

	/**
	 * Enqueue assets for the screen.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'audiotheme-agent-admin' );

		wp_enqueue_script(
			'audiotheme-agent-subscriptions',
			$this->plugin->get_url( 'admin/assets/js/subscriptions.js' ),
			array( 'wp-backbone', 'wp-util' ),
			'1.0.0',
			true
		);

		wp_localize_script( 'audiotheme-agent-subscriptions', '_audiothemeAgentSettings', array(
			'l10n'          => array(
				'plugins' => esc_html__( 'Plugins', 'audiotheme-agent' ),
				'themes'  => esc_html__( 'Themes', 'audiotheme-agent' ),
			),
			'nonces'        => array(
				'disconnect' => wp_create_nonce( 'disconnect-subscription' ),
				'subscribe'  => wp_create_nonce( 'subscribe' ),
			),
			'packages'      => $this->plugin->packages->prepare_packages_for_js(),
			'subscriptions' => $this->get_subscriptions(),
		) );
	}

	/**
	 * Display the screen.
	 *
	 * @since 1.0.0
	 */
	public function display_screen() {
		$client = $this->plugin->client;

		$this->display_screen_header();
		include( $this->plugin->get_path( 'views/screen/subscriptions.php' ) );
		$this->display_screen_footer();
	}

	/**
	 * Add a help tab to the subscriptions screen.
	 *
	 * @since 1.0.0
	 */
	protected function add_help_tab() {
		$client   = $this->plugin->client;
		$token    = get_option( AudioTheme_Agent_Client::TOKEN_OPTION_NAME );
		$metadata = $client->get_registered_metadata();

		$disconnect_url = add_query_arg( array(
			'page'   => 'audiotheme-agent',
			'action' => 'disconnect-client',
		), self_admin_url( 'index.php' ) );

		$disconnect_url = wp_nonce_url( $disconnect_url, 'disconnect-client' );

		$update_url = add_query_arg( array(
			'page'   => 'audiotheme-agent',
			'action' => 'update-client',
		), self_admin_url( 'index.php' ) );

		$update_url = wp_nonce_url( $update_url, 'update-client' );

		ob_start();
		include( $this->plugin->get_path( 'views/help/client-status.php' ) );
		$content = ob_get_clean();

		get_current_screen()->add_help_tab( array(
			'id'      => 'client-status',
			'title'   => esc_html__( 'Client Status', 'audiotheme-agent' ),
			'content' => $content,
		) );

		if ( $client->is_registered() ) {
			ob_start();
			include( $this->plugin->get_path( 'views/help/disconnect-client.php' ) );
			$content = ob_get_clean();

			get_current_screen()->add_help_tab( array(
				'id'      => 'disconnect-client',
				'title'   => esc_html__( 'Disconnect Site', 'audiotheme-agent' ),
				'content' => $content,
			) );
		}
	}

	/**
	 * Update client metadata.
	 *
	 * @since 1.0.0
	 */
	protected function maybe_update_client() {
		$is_update_request = isset( $_GET['action'] ) && 'update-client' === $_GET['action'];
		$is_valid_nonce = isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'update-client' );

		if ( ! $is_update_request || ! $is_valid_nonce ) {
			return;
		}

		$this->plugin->client->update_client_metadata();

		wp_safe_redirect( self_admin_url( 'index.php?page=audiotheme-agent' ) );
		exit;
	}

	/**
	 * Disconnect the client.
	 *
	 * @since 1.0.0
	 */
	protected function maybe_disconnect_client() {
		$is_disconnect_request = isset( $_GET['action'] ) && 'disconnect-client' === $_GET['action'];
		$is_valid_nonce = isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'disconnect-client' );

		if ( ! $is_disconnect_request || ! $is_valid_nonce ) {
			return;
		}

		// Flush the package caches.
		$this->plugin->flush_package_caches();

		// Disconnect subscriptions.
		foreach ( $this->get_subscriptions() as $subscription ) {
			$this->plugin->client->disconnect_subscription( $subscription->id );
		}

		// Unregister the client.
		// Attempts to delete it remotely if the registration token is valid.
		$this->plugin->client->unregister();

		wp_safe_redirect( self_admin_url( 'index.php?page=audiotheme-agent' ) );
		exit;
	}

	/**
	 * Retrieve connected subscriptions.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_subscriptions() {
		if ( ! $this->plugin->client->is_authorized() ) {
			return array();
		}

		$subscriptions = $this->plugin->client->get_subscriptions();

		if ( is_wp_error( $subscriptions ) ) {
			$subscriptions = array();
		}

		return $subscriptions;
	}
}
