<?php
/**
 * Logger.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright 2016 AudioTheme
 * @license   GPL-2.0+
 * @link      https://audiotheme.com/
 * @since     1.0.0
 */

/**
 * Logger class.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
class AudioTheme_Agent_Logger {
	/**
	 * Path to the log directory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $directory;

	/**
	 * Path to the log file.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $filename;

	/**
	 * Create a logger.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename Full path to the log file.
	 */
	public function __construct( $filename ) {
		$this->directory = dirname( $filename );
		$this->filename  = $filename;
	}

	/**
	 * Log a message.
	 *
	 * @since 1.0.0
	 *
	 * @param string $level   Logger level.
	 * @param string $message Message.
	 * @param array  $context Data to interpolate into the message.
	 */
	public function log( $level, $message, $context = array() ) {
		if ( ! $this->is_debug_mode() && in_array( $level, array( 'debug', 'info' ) ) ) {
			return;
		}

		if ( ! empty( $context ) ) {
			$search = $replace = array();

			foreach ( $context as $key => $value ) {
				array_push( $search, '{' . $key . '}' );
				array_push( $replace, $this->convert_to_string( $value ) );
			}

			$message = str_replace( $search, $replace, $message );
		}

		$entry = sprintf(
			'[%s UTC] %s: %s',
			date( 'Y-m-d H:i:s', time() ),
			strtoupper( $level ),
			$message
		);

		if ( $handle = @fopen( $this->filename, 'a' ) ) {
			fwrite( $handle, trim( $entry ) . "\n" );
			fclose( $handle );
		}
	}

	/**
	 * Retrieve the contents of the log file.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_contents() {
		return file_get_contents( $this->filename );
	}

	/**
	 * Set up the log directory and create the log file.
	 *
	 * @since 1.0.0
	 *
	 * @return $this
	 */
	public function setup() {
		wp_mkdir_p( $this->directory );

		$htaccess_filename = path_join( $this->directory, '.htaccess' );
		if ( ! file_exists( $htaccess_filename ) ) {
			if ( $handle = @fopen( $htaccess_filename, 'w' ) ) {
				fwrite( $handle, 'deny from all' );
				fclose( $handle );
			}
		}

		if ( ! file_exists( $this->filename ) ) {
			if ( $handle = @fopen( $this->filename, 'w' ) ) {
				fwrite( $handle, '' );
				fclose( $handle );
			}
		}

		return $this;
	}

	/**
	 * Whether debug mode is enabled.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	protected function is_debug_mode() {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Convert a message to a string.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed $value Message.
	 * @return string
	 */
	protected function convert_to_string( $message ) {
		if ( is_wp_error( $message ) ) {
			$message = $message->get_error_message();
		} elseif ( ! is_scalar( $message ) ) {
			$message = wp_json_encode( $message );
		}

		return $message;
	}
}
