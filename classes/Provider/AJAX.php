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
		add_action( 'wp_ajax_audiotheme_agent_subscribe',               array( $this, 'subscribe' ) );
		add_action( 'wp_ajax_audiotheme_agent_disconnect_subscription', array( $this, 'disconnect_subscription' ) );
	}

	/**
	 * Connect a client to a subscription.
	 *
	 * @since 1.0.0
	 */
	public function subscribe() {
		$token = sanitize_text_field( $_POST['token'] );

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'subscribe' ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Unauthorized request.', 'audiotheme-agent' )
			) );
		}

		$result = $this->plugin->client->subscribe( $token );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'code'    => $result->get_error_code(),
				'message' => sprintf( esc_html__( 'Error: %s', 'audiotheme-agent' ), $result->get_error_message() ),
			) );
		}

		$subscriptions = $this->plugin->client->get_subscriptions();

		if ( is_wp_error( $subscriptions ) ) {
			$subscriptions = array();
		}

		wp_send_json_success( $subscriptions );
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

		$result = $this->plugin->client->post( sprintf( '/v1/subscriptions/%d/disconnect', $_POST['id'] ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'code'    => $result->get_error_code(),
				'message' => sprintf( esc_html__( 'Error: %s', 'audiotheme-agent' ), $result->get_error_message() ),
			) );
		}

		wp_send_json_success( $result );
	}
}
