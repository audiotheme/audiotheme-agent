<?php
/**
 * Package manager
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright 2016 AudioTheme
 * @license   GPL-2.0+
 * @link      https://audiotheme.com/
 * @since     1.0.0
 */

/**
 * Package manager class.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
class AudioTheme_Agent_PackageManager extends AudioTheme_Agent_AbstractProvider {
	/**
	 * AudioTheme.com HTTP client.
	 *
	 * @since 1.0.0
	 * @var AudioTheme_Agent_Client
	 */
	protected $client;

	/**
	 * Managed plugins cache.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $plugins;

	/**
	 * Managed themes cache.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $themes;

	/**
	 * Whitelist of managed plugins.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $managed_plugins = array(
		'audiotheme',
		'cuebar',
	);

	/**
	 * Whitelist of managed themes.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $managed_themes = array(
		'americanaura',
		'encore',
		'huesos',
		'marquee',
		'nowell',
		'obsidian',
		'promenade',
		'twotone',
	);

	/**
	 * Create the manager.
	 *
	 * @since 1.0.0
	 *
	 * @param AudioTheme_Agent_Client $client AudioTheme.com HTTP client.
	 */
	public function __construct( $client ) {
		$this->client = $client;
	}

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
		$packages = $this->get_packages( 'plugin' );
		if ( is_wp_error( $packages ) ) {
			return $value;
		}

		$plugins = $this->get_installed_plugins();
		$updates = $this->get_plugin_updates( $plugins, $packages );

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
		$packages = $this->get_packages( 'theme' );
		if ( is_wp_error( $packages ) ) {
			return $value;
		}

		$themes  = $this->get_installed_themes();
		$updates = $this->get_theme_updates( $themes, $packages );

		if ( ! isset( $value->response ) ) {
			$value->response = array();
		}

		$value->response = array_merge( (array) $value->response, $updates );

		return $value;
	}

	/**
	 * Retrieve package information from the remote API for managed plugins and
	 * themes.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $type Optional. Type of packages to retrieve.
	 * @return array
	 */
	public function get_packages( $type = null ) {
		$slugs     = $this->get_installed_package_slugs();
		$cache_key = 'audiotheme_agent_packages-' . hash( 'crc32b', json_encode( $slugs ) );

		$packages = get_site_transient( $cache_key );
		if ( ! $packages ) {
			$packages = (array) $this->client->get_packages( $slugs );

			if ( is_wp_error( $packages ) ) {
				$packages = array();
			}

			set_site_transient( $cache_key, $packages, HOUR_IN_SECONDS );
		}

		if ( is_wp_error( $packages ) ) {
			return $packages;
		}

		if ( ! empty( $type ) ) {
			foreach ( $packages as $slug => $package ) {
				if ( 'wordpress-' . $type !== $package->type ) {
					unset( $packages[ $slug ] );
				}
			}
		}

		return $packages;
	}

	/**
	 * Retrieve an array of installed plugins and themes from AudioTheme.com.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_installed_packages() {
		$plugins = $this->get_installed_plugins();
		$themes  = $this->get_installed_themes();

		$packages = array_merge( $plugins, $themes );
		ksort( $packages );

		return $packages;
	}

	/**
	 * Retrieve an array of installed package slugs.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_installed_package_slugs() {
		$slugs = array_keys( $this->get_installed_packages() );
		sort( $slugs );
		return $slugs;
	}

	/**
	 * Retrieve a list of installed plugins that are managed.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_installed_plugins() {
		if ( ! empty( $this->plugins ) ) {
			return $this->plugins;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$plugins = array();

		foreach ( get_plugins() as $plugin_file => $plugin_data ) {
			$slug = basename( $plugin_file, '.php' );

			if ( ! in_array( $slug, $this->managed_plugins ) && 'https://audiotheme.com/' !== $plugin_data['Package Source'] ) {
				continue;
			}

			$plugins[ $slug ] = array(
				'name'       => $plugin_data['Name'],
				'slug'       => $slug,
				'file'       => $plugin_file,
				'version'    => $plugin_data['Version'],
				'type'       => 'Plugin',
			);
		}

		return $plugins;
	}

	/**
	 * Retrieve a list of installed themes that are managed.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_installed_themes() {
		if ( ! empty( $this->themes ) ) {
			return $themes;
		}

		$themes = array();

		foreach ( wp_get_themes() as $slug => $theme ) {
			if ( ! in_array( $slug, $this->managed_themes ) && 'https://audiotheme.com/' !== $theme->get( 'Package Source' ) ) {
				continue;
			}

			$themes[ $slug ] = array(
				'name'       => $theme->get( 'Name' ),
				'slug'       => $slug,
				'version'    => $theme->get( 'Version' ),
				'type'       => 'Theme',
			);
		}

		return $themes;
	}

	/**
	 * Retrieve information about plugins that have an update available.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $plugins  Array of installed plugins.
	 * @param  array $packages Array of package information from the resource server.
	 * @return array
	 */
	protected function get_plugin_updates( $plugins, $packages ) {
		$updates = array();

		foreach ( $packages as $slug => $package ) {
			if ( version_compare( $package->version, $plugins[ $slug ]['version'], '<=' ) ) {
				continue;
			}

			$basename = $plugins[ $slug ]['file'];

			$updates[ $basename ] = (object) array(
				'id'          => 0,
				'slug'        => $slug,
				'plugin'      => $basename,
				'new_version' => $package->version,
				'url'         => $package->homepage,
			);

			// Add the package zip URL if available.
			if ( ! empty( $package->package ) ) {
				$updates[ $basename ]->package = $package->package;
			}
		}

		return $updates;
	}

	/**
	 * Retrieve information about themes that have an update avaialble.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $themes   Array of installed themes.
	 * @param  array $packages Array of package information from the resource server.
	 * @return array
	 */
	protected function get_theme_updates( $themes, $packages ) {
		$updates = array();

		foreach ( $packages as $slug => $package ) {
			if ( version_compare( $package->version, $themes[ $slug ]['version'], '<=' ) ) {
				continue;
			}

			$updates[ $slug ] = array(
				'theme'       => $slug,
				'new_version' => $package->version,
				'url'         => $package->changelog_url,
			);

			// Add the package zip URL if available.
			if ( ! empty( $package->package ) ) {
				$updates[ $slug ]['package'] = $package->package;
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
	 * @return object       plugins_api Response object on success, WP_Error on failure.
	 */
	public function filter_plugins_api( $data, $action, $args ) {
		// Bail if this isn't an information request for this plugin.
		if ( 'plugin_information' !== $action || empty( $args->slug ) ) {
			return $data;
		}

		// @todo Cache this.
		$plugins = $this->get_installed_plugins();

		// Make sure the plugin is managed.
		if ( ! isset( $plugins[ $args->slug ] ) ) {
			return $data;
		}

		$package = $this->client->get_package( $args->slug, array(
			'include' => 'changelog',
		) );

		if ( is_wp_error( $package ) ) {
			return new WP_Error(
				'plugins_api_failed',
				esc_html__( 'An unexpected error occurred. Something may be wrong with the update API or this server&#8217;s configuration.', 'audiotheme-agent' ),
				$package
			);
		}

		return (object) array(
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
			/*'banners'           => array(
				'low'  => '',
				'high' => '',
			),*/
			'sections' => array(
				'description'  => $package->description,
				//'installation' => '',
				'changelog'    => $package->changelog,
				//'faq'          => '',
				//'other_notes'  => '',
				//'custom'       => '',
			),
		);
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
	 * @return array  Filtered request args.
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

			foreach ( $this->get_installed_plugins() as $plugin ) {
				unset( $entities['plugins'][ $plugin['file'] ] );
				unset( $entities['active'][ array_search( $plugin['file'], $entities['active'] ) ] );
			}

			// Cast back to an object.
			if ( '1.0' === $api_version ) {
				$entities = (object) $entities;
			}
		} elseif ( 'themes' === $api_type ) {
			foreach ( $this->get_installed_themes() as $theme ) {
				unset( $entities[ $theme['slug'] ] );
			}
		}

		$r['body'][ $api_type ] = '1.0' === $api_version ? serialize( $entities ) : wp_json_encode( $entities );

		return $r;
	}
}
