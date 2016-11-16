<?php
/**
 * AJAX handlers.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright (c) 2016, AudioTheme, LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 */

/**
 * AJAX provider class.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
class AudioTheme_Agent_Provider_AJAX extends AudioTheme_Agent_AbstractProvider {
	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_audiotheme_agent_create_child_theme',      array( $this, 'create_child_theme' ) );
		add_action( 'wp_ajax_audiotheme_agent_install_package',         array( $this, 'install_package' ) );
		add_action( 'wp_ajax_audiotheme_agent_subscribe',               array( $this, 'subscribe' ) );
		add_action( 'wp_ajax_audiotheme_agent_disconnect_subscription', array( $this, 'disconnect_subscription' ) );
	}

	/**
	 * Connect a client to a subscription.
	 *
	 * @since 1.0.0
	 */
	public function subscribe() {
		$client = $this->plugin->client;
		$token  = sanitize_text_field( $_POST['token'] );

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'subscribe' ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Unauthorized request.', 'audiotheme-agent' )
			) );
		}

		$result = $client->subscribe( $token );

		if ( is_wp_error( $result ) ) {
			$message = sprintf( esc_html__( 'Error: %s', 'audiotheme-agent' ), $result->get_error_message() );

			if ( 'rest_invalid_param' === $result->get_error_code() && false !== strpos( $result->get_error_message(), 'client_name' ) ) {
				$message = esc_html__( 'Error: Your site title must not be blank.', 'audiotheme-agent' );
			}

			wp_send_json_error( array(
				'code'    => $result->get_error_code(),
				'message' => $message,
			) );
		}

		$subscriptions = $client->get_subscriptions();

		if ( is_wp_error( $subscriptions ) ) {
			wp_send_json_error( array(
				'code'    => $subscriptions->get_error_code(),
				'message' => sprintf( esc_html__( 'Error: %s', 'audiotheme-agent' ), $subscriptions->get_error_message() ),
			) );
		}

		$this->plugin->logger->log( 'notice', 'Flushing the package caches.' );
		delete_site_transient( 'update_plugins' );
		delete_site_transient( 'update_themes' );
		$packages = $this->plugin->packages->flush()->prepare_packages_for_js();

		wp_send_json_success( array(
			'packages'      => $packages,
			'subscriptions' => $subscriptions
		) );
	}

	/**
	 * Disconnect a client from a subscription.
	 *
	 * @since 1.0.0
	 */
	public function disconnect_subscription() {
		if ( ! isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'disconnect-subscription' ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Unauthorized request.', 'audiotheme-agent' )
			) );
		}

		$result = $this->plugin->client->disconnect_subscription( absint( $_POST['id'] ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'code'    => $result->get_error_code(),
				'message' => sprintf( esc_html__( 'Error: %s', 'audiotheme-agent' ), $result->get_error_message() ),
			) );
		}

		$this->plugin->logger->log( 'notice', 'Flushing the package caches.' );
		delete_site_transient( 'update_plugins' );
		delete_site_transient( 'update_themes' );
		$packages = $this->plugin->packages->flush()->prepare_packages_for_js();

		wp_send_json_success( array(
			'packages' => $packages,
		) );
	}

	/**
	 * Install a package.
	 *
	 * @since 1.0.0
	 */
	public function install_package() {
		$slug   = sanitize_key( $_POST['slug'] );
		$status = array( 'slug' => $slug );

		check_ajax_referer( 'install-package_' . $slug, 'nonce' );

		$package = $this->plugin->packages->get_package( $slug );

		if ( empty( $package ) ) {
			$status['message'] = esc_html__( 'Invalid package.', 'audiotheme-agent' );
			wp_send_json_error( $status );
		}

		if ( 'plugin' === $package->get_type() && ! current_user_can( 'install_plugins' ) ) {
			$status['message'] = esc_html__( 'Sorry, you are not allowed to install plugins on this site.', 'audiotheme-agent' );
			wp_send_json_error( $status );
		} elseif ( 'theme' === $package->get_type() && ! current_user_can( 'install_themes' ) ) {
			$status['message'] = esc_html__( 'Sorry, you are not allowed to install themes on this site.', 'audiotheme-agent' );
			wp_send_json_error( $status );
		}

		$result = $package->install();

		if ( is_wp_error( $result ) ) {
			$status['message'] = $result->get_error_message();
			wp_send_json_error( $status );
		} elseif ( is_null( $result ) ) {
			$status['code']    = 'unable_to_connect_to_filesystem';
			$status['message'] = esc_html__( 'Unable to connect to the filesystem. Please install manually.', 'audiotheme-agent' );
			wp_send_json_error( $status );
		}

		$status['package'] = $package->to_array();

		wp_send_json_success( $status );
	}

	/**
	 * Create a child theme.
	 *
	 * @since 1.1.0
	 */
	public function create_child_theme() {
		$slug   = sanitize_key( $_POST['slug'] );
		$status = array( 'slug' => $slug );

		check_ajax_referer( 'create-child-theme_' . $slug, 'nonce' );

		if ( ! current_user_can( 'install_themes' ) ) {
			$status['message'] = esc_html__( 'Sorry, you are not allowed to install themes on this site.', 'audiotheme-agent' );
			wp_send_json_error( $status );
		}

		$generator = new AudioTheme_Agent_Generator_ChildTheme( $slug );
		$result = $generator->generate();

		if ( is_wp_error( $result ) ) {
			$status['code']    = $result->get_error_code();
			$status['message'] = $result->get_error_message();
			wp_send_json_error( $status );
		}

		$package = $this->plugin->packages->get_package( $slug );
		$status['package'] = $package->to_array();

		wp_send_json_success( $status );
	}
}
