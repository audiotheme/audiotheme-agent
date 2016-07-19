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
		if ( is_multisite() ) {
			return false;
		}

		return get_stylesheet() === $this->get_slug() || get_template() === $this->get_slug();
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

		if ( ! $this->is_installed() && file_exists( get_theme_root() . '/' . $this->get_slug() ) ) {
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
			ob_start();
			?>
			<div class="audiotheme-agent-dropdown-group">
				<?php echo $this->get_preview_button(); ?>

				<button class="button audiotheme-agent-dropdown-toggle"><i class="dashicons dashicons-arrow-down"></i></button>

				<div class="audiotheme-agent-dropdown-group-items">
					<ul>
						<li><a href="<?php echo esc_url( sprintf( 'https://audiotheme.com/support/%s/', $this->get_slug() ) ); ?>" target="_blank"><?php esc_html_e( 'View Documentation', 'audiotheme-agent' ); ?></a></li>
						<li><a href="<?php echo esc_url( $this->get_changelog_url() ); ?>" target="_blank"><?php esc_html_e( 'View Changelog', 'audiotheme-agent' ); ?></a></li>

						<?php if ( ! $this->child_exists() && current_user_can( 'install_themes' ) ) : ?>
							<li>
								<a href="#" class="js-create-child" data-slug="<?php echo esc_attr( $this->get_slug() ); ?>"><?php esc_html_e( 'Create Child Theme', 'audiotheme-agent' ); ?></a>
							</li>
						<?php elseif ( $this->child_exists() && ! is_multisite() ) : ?>
							<li>
								<a href="<?php echo esc_url( $this->get_customizer_url( array( 'theme' => $this->get_child_slug() ) ) ); ?>"><?php esc_html_e( 'Preview Child Theme', 'audiotheme-agent' ); ?></a>
							</li>
						<?php endif; ?>

						<?php if ( $this->child_exists() && current_user_can( 'edit_themes' ) ) : ?>
							<li>
								<a href="<?php echo esc_url( $this->get_editor_url( array( 'theme' => $this->get_child_slug() ) ) ); ?>"><?php esc_html_e( 'Edit Child Theme', 'audiotheme-agent' ); ?></a>
							</li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
			<?php
			$html = ob_get_clean();
		}

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
	 * Retrieve the package as an array.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function to_array() {
		$data = parent::to_array();
		$data['child_theme_nonce'] = wp_create_nonce( 'create-child-theme_' . $this->get_slug() );
		return $data;
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

	/**
	 * Retrieve a URL to the theme editor.
	 *
	 * @since 1.1.0
	 *
	 * @param  array  $args An array of query args to add to the editor URL.
	 * @return string
	 */
	protected function get_editor_url( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'theme' => $this->get_slug(),
		) );

		if ( is_multisite() ) {
			$url = network_admin_url( 'theme-editor.php' );
		} else {
			$url = self_admin_url( 'theme-editor.php' );
		}

		return add_query_arg( $args, $url );
	}

	/**
	 * Retrieve preview button HTML.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	protected function get_preview_button() {
		if ( $this->is_active() ) {
			$html = sprintf( '<span class="button button-disabled">%s</span>', esc_html__( 'Active', 'audiotheme-agent' ) );
		} elseif ( is_multisite() ) {
			$html = sprintf( '<span class="button button-disabled">%s</span>', esc_html__( 'Installed', 'audiotheme-agent' ) );
		} else {
			$html = sprintf(
				'<a href="%s" class="button">%s</a>',
				esc_url( esc_url( $this->get_customizer_url( array( 'theme' => $this->get_slug() ) ) ) ),
				esc_html__( 'Preview', 'audiotheme-agent' )
			);
		}

		return $html;
	}

	/**
	 * Retrieve a child theme slug.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	protected function get_child_slug() {
		$parts = explode( '/', $this->get_slug() );
		return sprintf( '%s-child', reset( $parts ) );
	}

	/**
	 * Whether a child theme exists.
	 *
	 * @since 1.1.0
	 *
	 * @return boolean
	 */
	protected function child_exists() {
		return wp_get_theme( $this->get_child_slug() )->exists();
	}
}
