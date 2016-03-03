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
		$metadata = $this->plugin->client->get_registered_metadata();

		ob_start();
		include( $this->plugin->get_path( 'views/help.php' ) );
		$content = ob_get_clean();

		get_current_screen()->add_help_tab( array(
			'id'      => 'client',
			'title'   => esc_html__( 'Client Details', 'audiotheme-agent' ),
			'content' => $content,
		) );
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
