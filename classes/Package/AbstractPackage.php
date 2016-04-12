<?php
/**
 * Package.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright 2016 AudioTheme
 * @license   GPL-2.0+
 * @link      https://audiotheme.com/
 * @since     1.0.0
 */

/**
 * Package class.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
abstract class AudioTheme_Agent_Package_AbstractPackage implements AudioTheme_Agent_PackageInterface {
	/**
	 * Package name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $name = '';

	/**
	 * Package slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $slug = '';

	/**
	 * Changelog URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $changelog_url = '';

	/**
	 * Current version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $current_version = '';

	/**
	 * Download URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $download_url = '';

	/**
	 * Homepage URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $homepage = '';

	/**
	 * Whether the package is installed.
	 *
	 * @since 1.0.0
	 * @var boolean
	 */
	protected $is_installed = false;

	/**
	 * Installed version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $installed_version = '';

	/**
	 * Maximum WordPress version (tested up to).
	 *
	 * Used for reporting compatibility when an upgrade is available.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $maximum_wordpress_version = '';

	/**
	 * Whether the package is viewable.
	 *
	 * @since 1.0.0
	 * @var boolean
	 */
	protected $is_viewable = true;

	/**
	 * Create a package.
	 *
	 * @since 1.0.0
	 *
	 * @param array|object $data Package data.
	 */
	public function __construct( $data = array() ) {
		if ( is_object( $data ) ) {
			$data = (array) $data;
		}

		$this->fill( $data );
	}

	/**
	 * Retrieve the changelog URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_changelog_url() {
		return $this->changelog_url;
	}

	/**
	 * Set the changelog URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url Changelog URL.
	 * @return $this
	 */
	public function set_changelog_url( $url ) {
		$this->changelog_url = $url;
		return $this;
	}

	/**
	 * Retrieve the version for the current release.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_current_version() {
		return $this->current_version;
	}

	/**
	 * Set the version for the current release.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $version Version.
	 * @return $this
	 */
	public function set_current_version( $version ) {
		$this->current_version = $version;
		return $this;
	}

	/**
	 * Whether the package has a download URL.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function has_download_url() {
		$url = $this->get_download_url();
		return ! empty( $url );
	}

	/**
	 * Retrieve the download URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_download_url() {
		return $this->download_url;
	}

	/**
	 * Set the download URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url Download URL.
	 * @return $this
	 */
	public function set_download_url( $url ) {
		$this->download_url = $url;
		return $this;
	}

	/**
	 * Retrieve the homepage URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_homepage() {
		return $this->homepage;
	}

	/**
	 * Set the homepage URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url URL.
	 * @return $this
	 */
	public function set_homepage( $url ) {
		$this->homepage = $url;
		return $this;
	}

	/**
	 * Whether the package is installed.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_installed() {
		return $this->is_installed;
	}

	/**
	 * Set whether the package is installed.
	 *
	 * @since 1.0.0
	 *
	 * @param  boolean $is_installed Whether the package is installed.
	 * @return $this
	 */
	public function set_installed( $is_installed ) {
		$this->is_installed = (bool) $is_installed;
		return $this;
	}

	/**
	 * Retrieve the installed version.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_installed_version() {
		return $this->installed_version;
	}

	/**
	 * Set the installed version.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $version Version.
	 * @return $this
	 */
	public function set_installed_version( $version ) {
		$this->installed_version = $version;
		return $this;
	}

	/**
	 * Retrieve maximum WordPress version (tested up to).
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_maximum_wordpress_version() {
		return $this->maximum_wordpress_version;
	}

	/**
	 * Set the maximum WordPress version (tested up to).
	 *
	 * @since 1.0.0
	 *
	 * @param  string $version Version.
	 * @return $this
	 */
	public function set_maximum_wordpress_version( $version ) {
		$this->maximum_wordpress_version = $version;
		return $this;
	}

	/**
	 * Retrieve the name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Set the name.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $name Package name.
	 * @return $this
	 */
	public function set_name( $name ) {
		$this->name = $name;
		return $this;
	}

	/**
	 * Retrieve the slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Set the slug.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $slug Slug.
	 * @return $this
	 */
	public function set_slug( $slug ) {
		$this->slug = $slug;
		return $this;
	}

	/**
	 * Whether the package is viewable.
	 *
	 * Installed packages should always be viewable.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_viewable() {
		return $this->is_installed() || $this->is_viewable;
	}

	/**
	 * Set whether the package is viewable.
	 *
	 * @since 1.0.0
	 *
	 * @param  boolean $is_viewable Whether the package is viewable.
	 * @return $this
	 */
	public function set_viewable( $is_viewable ) {
		$this->is_viewable = (bool) $is_viewable;
		return $this;
	}

	/**
	 * Whether an update is available.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_update_available() {
		return $this->is_installed() && version_compare( $this->get_installed_version(), $this->get_current_version(), '<' );
	}

	/**
	 * Retrieve the package as an array.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'slug'                      => $this->get_slug(),
			'name'                      => $this->get_name(),
			'changelog_url'             => $this->get_changelog_url(),
			'current_version'           => $this->get_current_version(),
			'homepage'                  => $this->get_homepage(),
			'is_installed'              => $this->is_installed(),
			'install_nonce'             => wp_create_nonce( 'install-package_' . $this->get_slug() ),
			'installed_version'         => $this->get_installed_version(),
			'type'                      => $this->get_type(),
			'type_label'                => $this->get_type_label(),
			'action_button'             => $this->get_action_button(),
			'is_active'                 => $this->is_active(),
			'is_viewable'               => $this->is_viewable(),
			'has_access'                => $this->has_download_url(),
			'has_update'                => $this->is_update_available(),
			'maximum_wordpress_version' => $this->get_maximum_wordpress_version(),
		);
	}

	/**
	 * Mass fill package properties.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $data Package properties.
	 */
	protected function fill( $data = array() ) {
		$keys = array(
			'changelog_url',
			'download_url',
			'homepage',
			'maximum_wordpress_version',
			'name',
			'slug',
		);

		foreach ( $keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				call_user_func( array( $this, 'set_' . $key ), $data[ $key ] );
			}
		}
	}
}
