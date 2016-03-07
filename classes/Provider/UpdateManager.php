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

		/*add_action( 'in_theme_update_message-americanaura', function( $theme, $r ) {
			echo 'Message';
		}, 10, 2 );

		add_filter( 'wp_prepare_themes_for_js', function( $themes ) {
			$themes['americanaura']['update'] .= '<p>Message</p>';
			return $themes;
		} );*/
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
}
