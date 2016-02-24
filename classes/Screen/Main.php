<?php
/**
 * Base screen functionality.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright (c) 2016, AudioTheme, LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 */

/**
 * Base screen class.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
class AudioTheme_Agent_Screen_Main extends AudioTheme_Agent_AbstractProvider {
	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu_item' ), 150 );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 0 );
	}

	/**
	 * Add the settings menu item.
	 *
	 * @since 1.0.0
	 */
	public function add_menu_item() {
		$page_hook = add_submenu_page(
			'index.php',
			esc_html__( 'AudioTheme Agent', 'audiotheme-agent' ),
			esc_html__( 'AudioTheme Agent', 'audiotheme-agent' ),
			'manage_options',
			'audiotheme-agent',
			array( $this, 'display_screen' )
		);

		add_action( 'load-' . $page_hook, array( $this, 'load_screen' ) );
	}

	/**
	 * Register assets for the screen.
	 *
	 * @since 1.0.0
	 */
	public function register_assets() {
		wp_register_style( 'audiotheme-agent-admin', $this->plugin->get_url( 'admin/assets/css/admin.css' ) );

		wp_register_script(
			'audiotheme-agent-subscriptions',
			$this->plugin->get_url( 'admin/assets/js/subscriptions.js' ),
			array( 'wp-backbone', 'wp-util' ),
			'1.0.0',
			true
		);

		wp_localize_script( 'audiotheme-agent-subscriptions', '_audiothemeAgentSettings', array(
			'nonces'        => array(
				'disconnect' => wp_create_nonce( 'disconnect-subscription' ),
				'subscribe'  => wp_create_nonce( 'subscribe' ),
			),
			'subscriptions' => $this->get_subscriptions(),
		) );
	}

	/**
	 * Display the screen header.
	 *
	 * @since 1.0.0
	 */
	public function display_screen_header() {
		include( $this->plugin->get_path( 'views/screen/header.php' ) );
	}

	/**
	 * Display the screen footer.
	 *
	 * @since 1.0.0
	 */
	public function display_screen_footer() {
		include( $this->plugin->get_path( 'views/screen/footer.php' ) );
	}

	/**
	 * Display screen tabs.
	 *
	 * @since 1.0.0
	 */
	protected function display_tabs() {
		$tabs = '';
		foreach ( $this->get_tabs() as $tab ) {
			$tabs .= sprintf(
				'<a href="%s" class="nav-tab %s">%s</a>',
				esc_url( $tab['url'] ),
				$tab['is_active'] ? 'nav-tab-active' : '',
				esc_html( $tab['label'] )
			);
		}

		printf( '<h2 class="nav-tab-wrapper">%s</h2>', $tabs );
	}

	/**
	 * Retrieve screen tabs.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_tabs() {
		return array(
			'default' => array(
				'label'     => __( 'Subscriptions', 'audiotheme-agent' ),
				'url'       => admin_url( 'admin.php?page=audiotheme-agent' ),
				'is_active' => 'default' === $this->get_current_tab_id(),
			),
			'support' => array(
				'label'     => __( 'Support', 'audiotheme-agent' ),
				'url'       => admin_url( 'admin.php?page=audiotheme-agent&tab=support' ),
				'is_active' => 'support' === $this->get_current_tab_id(),
			),
		);
	}

	/**
	 * Retrieve the current tab identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_current_tab_id() {
		return empty( $_GET['tab'] ) ? 'default' : sanitize_text_field( $_GET['tab'] );
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
