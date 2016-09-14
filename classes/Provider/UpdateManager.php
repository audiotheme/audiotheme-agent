<?php
/**
 * Package update manager.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright (c) 2016, AudioTheme, LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 */

/**
 * Package update manager class.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
class AudioTheme_Agent_Provider_UpdateManager extends AudioTheme_Agent_AbstractProvider {
	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {
		add_filter( 'extra_plugin_headers',                  array( $this, 'register_file_headers' ) );
		add_filter( 'extra_theme_headers',                   array( $this, 'register_file_headers' ) );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'filter_plugin_update_transient' ), 20 );
		add_filter( 'pre_set_site_transient_update_themes',  array( $this, 'filter_theme_update_transient' ), 20 );
		add_filter( 'plugins_api',                           array( $this, 'filter_plugins_api' ), 5, 3 );
		add_filter( 'http_request_args',                     array( $this, 'filter_wporg_update_check' ), 5, 2 );

		// Display a blurb about connecting alongside default updates messages
		// throughout various screens in the admin panel.
		if ( ! $this->plugin->client->is_authorized() ) {
			add_action( 'load-plugins.php',         array( $this, 'register_plugin_update_message_hooks' ) );
			add_filter( 'wp_prepare_themes_for_js', array( $this, 'filter_theme_update_messages' ) );
			add_filter( 'upgrader_pre_download',    array( $this, 'filter_missing_package_reply' ), 10, 3 );
		}
	}

	/**
	 * Register file headers.
	 *
	 * Registers a 'Package Source' header for forward compatibility with
	 * plugins and themes that aren't in the whitelist.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $headers Array of file headers.
	 * @return array
	 */
	public function register_file_headers( $headers ) {
		$headers['PackageSource'] = 'Package Source';
		return $headers;
	}

	/**
	 * Check for managed plugin updates when the plugin update transient is saved.
	 *
	 * @since 1.0.0
	 *
	 * @param  object $value Plugin update transient value.
	 * @return object
	 */
	public function filter_plugin_update_transient( $value ) {
		$plugins = $this->plugin->packages->get_installed_plugins();
		$updates = $this->get_plugin_updates( $plugins );

		if ( ! isset( $value->response ) ) {
			$value->response = array();
		}

		$value->response = array_merge( $value->response, $updates );

		return $value;
	}

	/**
	 * Check for managed theme updates when the theme update transient is saved.
	 *
	 * @since 1.0.0
	 *
	 * @param  object $value Them update transient value.
	 * @return object
	 */
	public function filter_theme_update_transient( $value ) {
		$themes  = $this->plugin->packages->get_installed_themes();
		$updates = $this->get_theme_updates( $themes );

		if ( ! isset( $value->response ) ) {
			$value->response = array();
		}

		$value->response = array_merge( (array) $value->response, $updates );

		return $value;
	}

	/**
	 * Retrieve information about plugins that have an update available.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $plugins  Array of installed plugins.
	 * @return array
	 */
	protected function get_plugin_updates( $plugins ) {
		$updates = array();

		foreach ( $plugins as $slug => $plugin ) {
			$package = $this->plugin->packages->get_package( $slug );

			if ( empty( $package ) || ! $package->is_update_available() ) {
				continue;
			}

			$updates[ $package->get_file() ] = (object) array(
				'id'          => 0,
				'slug'        => $slug,
				'plugin'      => $package->get_file(),
				'new_version' => $package->get_current_version(),
				'url'         => $package->get_homepage(),
				'tested'      => $package->get_maximum_wordpress_version(),
			);

			// Add the package zip URL if available.
			if ( $package->has_download_url() ) {
				$updates[ $package->get_file() ]->package = $package->get_download_url();
			}
		}

		return $updates;
	}

	/**
	 * Retrieve information about themes that have an update avaialble.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $themes Array of installed themes.
	 * @return array
	 */
	protected function get_theme_updates( $themes ) {
		$updates = array();

		foreach ( $themes as $slug => $theme ) {
			$package = $this->plugin->packages->get_package( $slug );

			if ( empty( $package ) || ! $package->is_update_available() ) {
				continue;
			}

			$updates[ $slug ] = array(
				'theme'       => $slug,
				'new_version' => $package->get_current_version(),
				'url'         => $package->get_changelog_url(),
			);

			// Add the package zip URL if available.
			if ( $package->has_download_url() ) {
				$updates[ $slug ]['package'] = $package->get_download_url();
			}
		}

		return $updates;
	}

	/**
	 * Filter the plugin API requests for this plugin to use the external API.
	 *
	 * @see plugins_api()
	 *
	 * @since 1.0.0
	 *
	 * @param  bool|object  $data       False if the first filter, otherwise a $response object from an earlier filter.
	 * @param  string       $action     The API method name.
	 * @param  array|object $args       Arguments to serialize for the Plugin Info API.
	 * @return object plugins_api Response object on success, WP_Error on failure.
	 */
	public function filter_plugins_api( $data, $action, $args ) {
		// Bail if this isn't an information request for this plugin.
		if ( 'plugin_information' !== $action || empty( $args->slug ) ) {
			return $data;
		}

		// @todo Cache this.
		$plugins = $this->plugin->packages->get_installed_plugins();

		// Make sure the plugin is managed.
		if ( ! isset( $plugins[ $args->slug ] ) ) {
			return $data;
		}

		$package = $this->plugin->client->get_package( $args->slug, array(
			'embed' => 'changelog',
		) );

		if ( is_wp_error( $package ) ) {
			return new WP_Error(
				'plugins_api_failed',
				esc_html__( 'An unexpected error occurred. Something may be wrong with the update API or this server&#8217;s configuration.', 'audiotheme-agent' ),
				$package
			);
		}

		$info = array(
			'name'              => $package->name,
			'slug'              => $package->slug,
			'version'           => $package->version,
			'homepage'          => $package->homepage,
			'author'            => '<a href="https://audiotheme.com/">AudioTheme</a>',
			'short_description' => '',
			'added'             => $package->initial_release,
			'last_updated'      => $package->last_update,
			'author_profile'    => 'https://profiles.wordpress.org/audiotheme',
			'requires'          => $package->minimum_wordpress_version,
			'tested'            => $package->maximum_wordpress_version,
			//'downloaded'        => '',
			//'banners'           => array(
			//	'low'  => '',
			//	'high' => '',
			//),
			'sections' => array(
				'description'  => $package->description,
				//'installation' => '',
				'changelog'    => $package->changelog,
				//'faq'          => '',
				//'other_notes'  => '',
				//'custom'       => '',
			),
		);

		if ( ! empty( $package->download_url ) ) {
			$info['download_link'] = $package->download_url;
		}

		return (object) $info;
	}

	/**
	 * Disable update requests to wordpress.org for managed plugins and themes.
	 *
	 * @link http://markjaquith.wordpress.com/2009/12/14/excluding-your-plugin-or-theme-from-update-checks/
	 * @link https://github.com/cftp/external-update-api/blob/281a0efbf6c2085cbd8c3d49814fce97c59a63b4/external-update-api/euapi.php#L45
	 * @see WP_Http::request()
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $r Request args.
	 * @param  string $url URI resource.
	 * @return array Filtered request args.
	 */
	public function filter_wporg_update_check( $r, $url ) {
		// Bail if this isn't an update request.
		if (
			false === strpos( $url, 'api.wordpress.org' ) ||
			! preg_match( '#://api\.wordpress\.org/(?P<type>plugins|themes)/update-check/(?P<version>[0-9.]+)/#', $url, $matches )
		) {
			return $r;
		}

		$api_type    = $matches['type'];
		$api_version = $matches['version'];

		$entities = $r['body'][ $api_type ];
		$entities = '1.0' === $api_version ? unserialize( $entities ) : json_decode( $entities, true );

		if ( 'plugins' === $api_type ) {
			$entities = (array) $entities;

			foreach ( $this->plugin->packages->get_installed_plugins() as $plugin ) {
				unset( $entities['plugins'][ $plugin['file'] ] );
				unset( $entities['active'][ array_search( $plugin['file'], $entities['active'] ) ] );
			}

			// Cast back to an object.
			if ( '1.0' === $api_version ) {
				$entities = (object) $entities;
			}
		} elseif ( 'themes' === $api_type ) {
			foreach ( $this->plugin->packages->get_installed_themes() as $theme ) {
				unset( $entities[ $theme['slug'] ] );
			}
		}

		$r['body'][ $api_type ] = '1.0' === $api_version ? serialize( $entities ) : wp_json_encode( $entities );

		return $r;
	}

	/**
	 * Register plugin update hooks.
	 *
	 * @since 1.1.2
	 */
	public function register_plugin_update_message_hooks() {
		$plugins = $this->plugin->packages->get_installed_plugins();

		foreach ( $plugins as $slug => $plugin ) {
			add_action( 'in_plugin_update_message-' . $plugin['file'], array( $this, 'print_plugin_update_message' ), 10, 2 );
		}
	}

	/**
	 * Print a message in the plugin row on the main Plugins screen if the
	 * package URL is missing for a managed plugin.
	 *
	 * @since 1.1.2
	 *
	 * @param array $plugin_data Array of plugin data.
	 * @param array $r           An array of metadata about the available plugin update.
	 */
	public function print_plugin_update_message( $plugin_data, $r ) {
		if ( empty( $r->package ) ) {
			printf( '<br><br><strong>%s</strong>', $this->get_missing_package_message( 'plugin' ) );
		}
	}

	/**
	 * Filter the update message for managed themes.
	 *
	 * This displays in the theme modals on the main Themes screen.
	 *
	 * @since 1.1.2
	 *
	 * @param  array $themes Array of theme info.
	 * @return array
	 */
	public function filter_theme_update_messages( $themes ) {
		foreach ( $themes as $slug => $theme ) {
			if ( isset( $theme['hasUpdate'] ) && $theme['hasUpdate'] && $this->plugin->packages->is_managed_theme( $slug ) ) {
				// Strip the message about automatic update not being available.
				$themes[ $slug ]['update']  = preg_replace( '/ <em>[^<]+<\/em>/', '', $themes[ $slug ]['update'] );

				$themes[ $slug ]['update'] .= sprintf(
					'<p><strong><em>%s</em></strong></p>',
					$this->get_missing_package_message( 'theme' )
				);
			}
		}

		return $themes;
	}

	/**
	 * Filter the message displayed when a package URL is missing during bulk update.
	 *
	 * @since 1.1.2
	 *
	 * @param bool        $reply    Whether to bail without returning the package.
	 *                              Default false.
	 * @param string      $package  The package file name.
	 * @param WP_Upgrader $upgrader The WP_Upgrader instance.
	 */
	public function filter_missing_package_reply( $reply, $package, $upgrader ) {
		// Bail if a package is available.
		if ( ! empty( $package ) ) {
			return $reply;
		}

		// These should check with the package manager to see if the package is
		// managed, but the upgrader makes it difficult to get the plugin slug,
		// so the author header is being used for simplicity.
		$is_managed_plugin = isset( $upgrader->skin->plugin_info ) && 'AudioTheme' === $upgrader->skin->plugin_info['Author'];
		$is_managed_theme  = isset( $upgrader->skin->theme_info ) && $this->plugin->packages->is_managed_theme( $upgrader->skin->theme_info->get_stylesheet() );

		// Bail if this isn't a managed package.
		if ( ! $is_managed_plugin && ! $is_managed_theme ) {
			return $reply;
		}

		$message = $this->get_missing_package_message( $is_managed_plugin ? 'plugin' : 'theme' );
		return new WP_Error( 'no_package', $message );
	}

	/**
	 * Retrieve a message about connecting to receive automatic updates when a
	 * package URL is missing.
	 *
	 * @since 1.1.2
	 *
	 * @param  string $type Package type.
	 * @return string
	 */
	protected function get_missing_package_message( $type ) {
		if ( 'plugin' === $type ) {
			$message = sprintf(
				__( 'Connect the AudioTheme Agent to receive automatic updates for this plugin. <a href="%s" target="_top">Connect now</a>.', 'audiotheme-agent' ),
				esc_url( self_admin_url( 'index.php?page=audiotheme-agent' ) )
			);
		} elseif ( 'theme' === $type ) {
			$message = sprintf(
				__( 'Connect the AudioTheme Agent to receive automatic updates for this theme. <a href="%s" target="_top">Connect now</a>.', 'audiotheme-agent' ),
				esc_url( self_admin_url( 'index.php?page=audiotheme-agent' ) )
			);
		}

		return wp_kses( $message, array( 'a' => array( 'href' => true, 'target' => true ) ) );
	}
}
