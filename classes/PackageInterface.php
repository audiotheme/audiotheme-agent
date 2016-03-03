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
 * Package interface.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
interface AudioTheme_Agent_PackageInterface {
	/**
	 * Whether the package is active.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_active();

	/**
	 * Retrieve the changelog URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_changelog_url();

	/**
	 * Set the changelog URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url Changelog URL.
	 * @return $this
	 */
	public function set_changelog_url( $url );

	/**
	 * Retrieve the version for the current release.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_current_version();

	/**
	 * Set the version for the current release.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $version Version.
	 * @return $this
	 */
	public function set_current_version( $version );

	/**
	 * Whether the package has a download URL.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function has_download_url();

	/**
	 * Retrieve the download URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_download_url();

	/**
	 * Set the download URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url Download URL.
	 * @return $this
	 */
	public function set_download_url( $url );

	/**
	 * Retrieve the homepage URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_homepage();

	/**
	 * Set the homepage URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url URL.
	 * @return $this
	 */
	public function set_homepage( $url );

	/**
	 * Whether the package is installed.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_installed();

	/**
	 * Set whether the package is installed.
	 *
	 * @since 1.0.0
	 *
	 * @param  boolean $is_installed Whether the package is installed.
	 * @return $this
	 */
	public function set_installed( $is_installed );

	/**
	 * Retrieve the installed version.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_installed_version();

	/**
	 * Set the installed version.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $version Version.
	 * @return $this
	 */
	public function set_installed_version( $version );

	/**
	 * Retrieve the name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Set the name.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $name Package name.
	 * @return $this
	 */
	public function set_name( $name );

	/**
	 * Retrieve the slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_slug();

	/**
	 * Set the slug.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $slug Slug.
	 * @return $this
	 */
	public function set_slug( $slug );

	/**
	 * Retrieve the package type.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_type();

	/**
	 * Retrieve a human-readable type label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_type_label();

	/**
	 * Whether the package is viewable.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_viewable();

	/**
	 * Set whether the package is viewable.
	 *
	 * @since 1.0.0
	 *
	 * @param  boolean $is_viewable Whether the package is viewable.
	 * @return $this
	 */
	public function set_viewable( $is_viewable );

	/**
	 * Whether an update is available.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_update_available();

	/**
	 * Retrieve a button to manage the package.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_action_button();

	/**
	 * Retrieve the package as an array.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function to_array();

	/**
	 * Install the package.
	 *
	 * @since 1.0.0
	 *
	 * @return true|null|WP_Error
	 */
	public function install();
}
