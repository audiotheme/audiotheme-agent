<?php
/**
 * Support screen.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright (c) 2016, AudioTheme, LLC
 * @license   GPL-2.0+
 * @since     1.1.0
 */

/**
 * Support screen class.
 *
 * @package AudioTheme\Agent
 * @since   1.1.0
 */
class AudioTheme_Agent_Screen_Main_Support extends AudioTheme_Agent_Screen_Main {
	/**
	 * Register hooks.
	 *
	 * @since 1.1.0
	 */
	public function register_hooks() {
		if ( 'support' !== $this->get_current_tab_id() ) {
			return;
		}

		parent::register_hooks();
	}

	/**
	 * Set up the screen.
	 *
	 * @since 1.1.0
	 */
	public function load_screen() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets for the screen.
	 *
	 * @since 1.1.0
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'audiotheme-agent-admin' );
	}

	/**
	 * Display the screen.
	 *
	 * @since 1.1.0
	 */
	public function display_screen() {
		$client = $this->plugin->client;
		$theme  = wp_get_theme( get_template() );
		$user   = wp_get_current_user();

		$iframe_url = add_query_arg( array(
			'f' => 13,
			'first_name'      => rawurlencode( $user->first_name ),
			'last_name'       => rawurlencode( $user->last_name ),
			'email'           => rawurlencode( $user->user_email ),
			'environment'     => rawurlencode( $this->get_environment_string() ),
			'theme'           => rawurlencode( sprintf( '%s %s', $theme->get( 'Name' ), $theme->get( 'Version' ) ) ),
			'has_child_theme' => is_child_theme() ? 'Yes' : 'No',
			'website'         => home_url(),
		), 'https://audiotheme.com/gfembed/?f=13' );

		$this->display_screen_header();
		include( $this->plugin->get_path( 'views/screen/support.php' ) );
		$this->display_screen_footer();
	}

	/**
	 * Retrieve information about the current environment.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_environment_string() {
		global $wpdb;

		return sprintf(
			'PHP %s; MySQL %s; WordPress %s',
			phpversion(),
			$wpdb->db_version(),
			get_bloginfo( 'version' )
		);
	}
}
