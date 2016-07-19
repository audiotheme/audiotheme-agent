<?php
/**
 * Child theme generator.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright 2016 AudioTheme
 * @license   GPL-2.0+
 * @link      https://audiotheme.com/
 * @since     1.1.0
 */

/**
 * Child theme generator class.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
class AudioTheme_Agent_Generator_ChildTheme {
	/**
	 * Parent theme slug.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	protected $template;

	/**
	 * Create a child theme generator.
	 *
	 * @since 1.1.0
	 *
	 * @param string $parent_slug Slug of the parent theme.
	 */
	public function __construct( $parent_slug ) {
		$this->template = $parent_slug;
	}

	/**
	 * Generate a child theme.
	 *
	 * @since 1.1.0
	 */
	public function generate() {
		global $wp_filesystem;

		WP_Filesystem();

		$parent = wp_get_theme( $this->template );

		if ( ! $parent->exists() ) {
			return new WP_Error( 'invalid_template', esc_html__( 'Invalid parent theme slug.', 'audiotheme-agent' ) );
		}

		$parts     = explode( '/', $parent->get_template() );
		$slug      = sprintf( '%s-child', reset( $parts ) );
		$directory = path_join( $parent->get_theme_root(), $slug );

		if ( $wp_filesystem->exists( $directory ) ) {
			return new WP_Error( 'directory_exists', esc_html__( 'Child theme directory already exists.', 'audiotheme-agent' ) );
		}

		if ( false === $wp_filesystem->mkdir( $directory ) ) {
			return new WP_Error( 'fs_error', esc_html__( 'Could not create child theme directory.', 'audiotheme-agent' ) );
		}

		$source = audiotheme_agent()->get_path( 'data/child-theme/' );
		copy_dir( $source, $directory );

		if ( $parent->get_screenshot() ) {
			$wp_filesystem->copy(
				path_join( $parent->get_template_directory(), $parent->get_screenshot( 'relative' ) ),
				path_join( $directory, $parent->get_screenshot( 'relative' ) )
			);
		}

		$data = array(
			'{{author}}'     => wp_get_current_user()->display_name,
			'{{author_url}}' => wp_get_current_user()->user_url,
			'{{name}}'       => $parent->get( 'Name' ),
			'{{slug}}'       => $parent->get_template(),
			'{{url}}'        => esc_url( home_url() ),
		);

		$files = array( 'functions.php', 'style.css' );
		foreach ( $files as $file ) {
			$filename = path_join( $directory, $file );
			$contents = $wp_filesystem->get_contents( $filename );

			$contents = str_replace(
				array_keys( $data ),
				array_values( $data ),
				$contents
			);

			$wp_filesystem->put_contents( $filename, $contents );
		}

		return true;
	}
}
