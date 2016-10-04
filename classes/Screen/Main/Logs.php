<?php
/**
 * Logs screen.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright (c) 2016, AudioTheme, LLC
 * @license   GPL-2.0+
 * @since     1.2.0
 */

/**
 * Logs screen class.
 *
 * @package AudioTheme\Agent
 * @since   1.2.0
 */
class AudioTheme_Agent_Screen_Main_Logs extends AudioTheme_Agent_Screen_Main {
	/**
	 * Register hooks.
	 *
	 * @since 1.2.0
	 */
	public function register_hooks() {
		if ( 'logs' !== $this->get_current_tab_id() ) {
			return;
		}

		parent::register_hooks();
	}

	/**
	 * Set up the screen.
	 *
	 * @since 1.2.0
	 */
	public function load_screen() {

	}

	/**
	 * Display the screen.
	 *
	 * @since 1.2.0
	 */
	public function display_screen() {
		$this->display_screen_header();
		?>
		<div id="auditoheme-agent-log-viewer">
			<style type="text/css" scoped>
			#auditoheme-agent-log-viewer {
				margin-top: 20px;
			}

			#auditoheme-agent-log-viewer textarea {
				resize: vertical;
				width: 100%;
			}
			</style>
			<textarea cols="70" rows="25"><?php echo esc_textarea( $this->plugin->logger->get_contents() ); ?></textarea>
		</div>
		<?php
		$this->display_screen_footer();
	}
}
