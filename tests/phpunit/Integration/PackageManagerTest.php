<?php

namespace AudioTheme\Agent\Test\Integration;

use AudioTheme_Agent_Client;
use AudioTheme_Agent_PackageManager;
use AudioTheme_Agent_Provider_UpdateManager;


class PackageManagerTest extends \WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->theme_root = AUDIOTHEME_AGENT_TESTS_DIR . '/data/themes';
		$this->original_stylesheet = get_stylesheet();
		$this->original_theme_directories = $GLOBALS['wp_theme_directories'];

		// /themes is necessary as theme.php functions assume /themes is the root if there is only one root.
		$GLOBALS['wp_theme_directories'] = array( WP_CONTENT_DIR . '/themes', $this->theme_root );

		add_filter( 'theme_root',      array( $this, '_theme_root' ) );
		add_filter( 'stylesheet_root', array( $this, '_theme_root' ) );
		add_filter( 'template_root',   array( $this, '_theme_root' ) );

		$this->plugin = audiotheme_agent();
	}

	public function teardDown() {
		$GLOBALS['wp_theme_directories'] = $this->original_theme_directories;
		switch_theme( $this->original_stylesheet );

		remove_filter( 'theme_root',      array( $this, '_theme_root' ) );
		remove_filter( 'stylesheet_root', array( $this, '_theme_root' ) );
		remove_filter( 'template_root',   array( $this, '_theme_root' ) );
	}

	public function test_packages_provider_exists() {
		$this->assertObjectHasAttribute( 'packages', $this->plugin );
		$this->assertInstanceOf( '\AudioTheme_Agent_PackageManager', $this->plugin->packages );
	}

	public function test_package_source_file_header() {
		$this->plugin->register_hooks( new AudioTheme_Agent_Provider_UpdateManager() );
		$theme = wp_get_theme( 'managed-theme', $this->theme_root );
		$this->assertSame( 'https://audiotheme.com/', $theme->get( 'Package Source' ) );
	}

	public function test_installed_plugins() {

	}

	public function test_installed_themes() {
		$this->plugin->register_hooks( new AudioTheme_Agent_Provider_UpdateManager() );
		$themes = $this->plugin->packages->get_installed_themes();
		$this->assertArrayHasKey( 'managed-theme', $themes );
	}

	public function _theme_root( $directory ) {
		return $this->theme_root;
	}
}
