<?php
/**
 * Package manager.
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
class AudioTheme_Agent_PackageManager {
	/**
	 * Transient key for caching packages.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const PACKAGES_TRANSIENT_KEY = 'audiotheme_agent_packages';

	/**
	 * AudioTheme.com HTTP client.
	 *
	 * @since 1.0.0
	 * @var AudioTheme_Agent_Client
	 */
	protected $client;

	/**
	 * Packages cache.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $packages = array();

	/**
	 * Managed plugins cache.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $plugins = array();

	/**
	 * Managed themes cache.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $themes = array();

	/**
	 * Whitelist of managed plugin slugs.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $managed_plugins = array(
		'audiotheme',
		'audiotheme-agent',
		'cuebar',
		'cuepro',
	);

	/**
	 * Whitelist of managed theme slugs.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $managed_themes = array(
		'americanaura',
		'encore',
		'hammer',
		'huesos',
		'marquee',
		'nowell',
		'obsidian',
		'ovation',
		'promenade',
		'twotone',
		'wayfarer',
	);

	/**
	 * Retired packages.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $retired = array(
		'shaken-encore',
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
	 * Retrieve a package by its slug.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $slug Package slug.
	 * @return AudioTheme_Agent_PackageInterface
	 */
	public function get_package( $slug ) {
		$package = null;

		$packages = $this->get_packages();
		if ( isset( $packages[ $slug ] ) ) {
			$package = $packages[ $slug ];
		}

		return $package;
	}

	/**
	 * Retrieve packages.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_packages() {
		if ( ! empty( $this->packages ) ) {
			return $this->packages;
		}

		$items = $this->fetch_packages_data();
		foreach ( $items as $item ) {
			$package = $this->make( $item );

			if ( ! empty( $package ) ) {
				$this->packages[ $item->slug ] = $package;
			}
		}

		return $this->packages;
	}

	/**
	 * Prepare packages for use in JavaScript.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function prepare_packages_for_js() {
		$items = array();

		foreach ( $this->get_packages() as $package ) {
			$items[] = $package->to_array();
		}

		return $items;
	}

	/**
	 * Retrieve a list of managed plugins that are installed.
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

		foreach ( get_plugins() as $plugin_file => $plugin_data ) {
			$slug = basename( $plugin_file, '.php' );

			if ( ! in_array( $slug, $this->managed_plugins ) && 'https://audiotheme.com/' !== $plugin_data['Package Source'] ) {
				continue;
			}

			$this->plugins[ $slug ] = array(
				'name'    => $plugin_data['Name'],
				'slug'    => $slug,
				'file'    => $plugin_file,
				'version' => $plugin_data['Version'],
			);
		}

		return $this->plugins;
	}

	/**
	 * Retrieve a list of managed themes that are installed.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_installed_themes() {
		if ( ! empty( $this->themes ) ) {
			return $this->themes;
		}

		foreach ( wp_get_themes() as $slug => $theme ) {
			if ( ! in_array( $slug, $this->managed_themes ) && 'https://audiotheme.com/' !== $theme->get( 'Package Source' ) ) {
				continue;
			}

			$this->themes[ $slug ] = array(
				'name'    => $theme->get( 'Name' ),
				'slug'    => $slug,
				'version' => $theme->get( 'Version' ),
			);
		}

		return $this->themes;
	}

	/**
	 * Whether a plugin is managed.
	 *
	 * @since 1.1.2
	 *
	 * @param  string  $slug Plugin slug.
	 * @return boolean
	 */
	public function is_managed_plugin( $slug ) {
		$plugins = $this->get_installed_plugins();
		return isset( $plugins[ $slug ] );
	}

	/**
	 * Whether a theme is managed.
	 *
	 * @since 1.1.2
	 *
	 * @param  string  $slug Theme slug.
	 * @return boolean
	 */
	public function is_managed_theme( $slug ) {
		$themes = $this->get_installed_themes();
		return isset( $themes[ $slug ] );
	}

	/**
	 * Flush the cache.
	 *
	 * @since 1.0.0
	 *
	 * @return $this
	 */
	public function flush() {
		$this->packages = array();
		delete_site_transient( self::PACKAGES_TRANSIENT_KEY );
		return $this;
	}

	/**
	 * Fetch and cache package data.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function fetch_packages_data() {
		$packages = get_site_transient( self::PACKAGES_TRANSIENT_KEY );

		if ( ! $packages ) {
			$packages = $this->client->get_packages();

			if ( is_wp_error( $packages ) ) {
				$packages = array();
			}

			set_site_transient( self::PACKAGES_TRANSIENT_KEY, (array) $packages, HOUR_IN_SECONDS );
		}

		return $packages;
	}

	/**
	 * Create a package object.
	 *
	 * @since 1.0.0
	 *
	 * @param  object $item Package information from the remote API.
	 * @return AudioTheme_Agent_PackageInterface
	 */
	protected function make( $item ) {
		if ( 'wordpress-plugin' === $item->type ) {
			$package = new AudioTheme_Agent_Package_Plugin( $item );
		} elseif ( 'wordpress-theme' === $item->type ) {
			$package = new AudioTheme_Agent_Package_Theme( $item );
		}

		$package->set_current_version( $item->version );

		$installed = $this->get_installed_package( $item->slug );

		if ( ! empty( $installed ) ) {
			$package
				->set_installed( true )
				->set_installed_version( $installed['version'] );
		}

		if ( ! empty( $installed ) && 'plugin' === $package->get_type() ) {
			$package->set_file( $installed['file'] );
		}

		if ( in_array( $package->get_slug(), $this->retired ) ) {
			$package->set_viewable( false );
		}

		return $package;
	}

	/**
	 * Retrieve an installed package.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $slug Package slug.
	 * @return array|null
	 */
	protected function get_installed_package( $slug ) {
		$packages = $this->get_installed_packages();
		return isset( $packages[ $slug ] ) ? $packages[ $slug ] : null;
	}

	/**
	 * Retrieve an array of managed plugins and themes that are installed.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_installed_packages() {
		$plugins = $this->get_installed_plugins();
		$themes  = $this->get_installed_themes();

		$packages = array_merge( $plugins, $themes );
		ksort( $packages );

		return $packages;
	}
}
