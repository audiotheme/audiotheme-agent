<?php
/**
 * Theme package.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright 2016 AudioTheme
 * @license   GPL-2.0+
 * @link      https://audiotheme.com/
 * @since     1.0.0
 */

/**
 * Theme package class.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
class AudioTheme_Agent_Package_Theme extends AudioTheme_Agent_Package_AbstractPackage {
	/**
	 * Whether the theme is active.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_active() {
		return get_stylesheet() === $this->get_slug();
	}

	/**
	 * Retrieve the package type.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_type() {
		return 'theme';
	}

	/**
	 * Retrieve a human-readable type label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_type_label() {
		return esc_html__( 'Theme', 'audiotheme-agent' );
	}

	/**
	 * Retrieve a button to manage the package.
	 *
	 * @todo Unavailable, expired/requires renewal
	 * @todo Update available
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_action_button() {
		$html = '';

		if ( $this->is_active() ) {
			// @todo Is an update available?
			$html = sprintf( '<span class="button button-disabled">%s</span>', esc_html__( 'Active', 'audiotheme-agent' ) );
		} elseif ( ! $this->is_installed() && file_exists( get_theme_root() . '/' . $this->get_slug() ) ) {
			// Directory exists, don't overwrite.
			$html = sprintf( '<span class="error-message">%s</span>', esc_html__( 'Theme directory exists.', 'audiotheme-agent' ) );
		} elseif ( ! $this->is_installed() && current_user_can( 'install_themes' ) && $this->has_download_url() ) {
			$html = sprintf(
				'<a href="%s" class="button js-install" data-slug="%s">%s</a>',
				esc_url( '' ),
				esc_attr( $this->get_slug() ),
				esc_html__( 'Install Now', 'audiotheme-agent' )
			);
		} elseif ( $this->is_installed() ) {
			$html = sprintf(
				'<a href="%s" class="button">%s</a>',
				esc_url( esc_url( $this->get_customizer_url( array( 'theme' => $this->get_slug() ) ) ) ),
				esc_html__( 'Preview', 'audiotheme-agent' )
			);
		}

		/*$status = 'install';

		$installed_theme = wp_get_theme( $theme->slug );
		if ( $installed_theme->exists() ) {
			if ( version_compare( $installed_theme->get('Version'), $theme->version, '=' ) )
				$status = 'latest_installed';
			elseif ( version_compare( $installed_theme->get('Version'), $theme->version, '>' ) )
				$status = 'newer_installed';
			else
				$status = 'update_available';
		}*/

		return $html;
	}

	/**
	 * Install the theme.
	 *
	 * @since 1.0.0
	 *
	 * @return true|null|WP_Error
	 */
	public function install() {
		if ( ! current_user_can( 'install_themes' ) ) {
			return new WP_Error( 'unauthorized', esc_html__( 'You do not have sufficient permissions to install themes on this site.', 'audiotheme-agent' ) );
		}

		$upgrader = new Theme_Upgrader( new Automatic_Upgrader_Skin() );
		$result   = $upgrader->install( $this->get_download_url() );

		if ( true === $result ) {
			$this
				->set_installed( true )
				->set_installed_version( $this->get_current_version() );
		}

		return $result;
	}

	/**
	 * Retrieve a URL to the Customizer.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $args An array of query args to add to the Customizer URL.
	 * @return string
	 */
	protected function get_customizer_url( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'return' => rawurlencode( admin_url( 'index.php?page=audiotheme-agent' ) ),
		) );

		return add_query_arg( $args, admin_url( 'customize.php' ) );
	}
}
