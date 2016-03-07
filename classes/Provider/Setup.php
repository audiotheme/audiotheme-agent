<?php
/**
 * Plugin setup.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright (c) 2016, AudioTheme, LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 */

/**
 * Plugin setup class.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
class AudioTheme_Agent_Provider_Setup extends AudioTheme_Agent_AbstractProvider {
	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {
		register_activation_hook( $this->plugin->get_file(),   array( $this, 'activate' ) );
	}

	/**
	 * Activation routine.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		$this->plugin->logger->setup();
	}
}
