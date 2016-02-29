<?php

namespace AudioTheme\Agent\Test\Integration;

use AudioTheme_Agent_Client;
use WP_Error;


class ClientResponseTest extends \WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->client = new AudioTheme_Agent_Client();
	}

	public function test_response() {
		$response = array(
			'body'     => wp_json_encode( array(
				'a' => 1,
			) ),
			'response' => array(
				'code' => 200,
			),
		);

		$result = $this->client->parse_response( $response );

		$this->assertSame( 1, $result->a );
	}

	public function test_response_error() {
		$response = $this->client->parse_response( new WP_Error() );
		$this->assertWPError( $response );
	}

	public function test_response_with_error_in_content() {
		$response = array(
			'body'     => wp_json_encode( array(
				'error'             => 'generic_error',
				'error_description' => 'Generic error message.',
			) ),
			'response' => array(
				'code' => 400,
			),
		);

		$result = $this->client->parse_response( $response );

		$this->assertWPError( $result );
	}

	public function test_response_with_unexpected_status() {
		$response = array(
			'response' => array(
				'code' => 400,
			),
		);

		$result = $this->client->parse_response( $response );

		$this->assertWPError( $result );
		$this->assertSame( 'unexpected_status_code', $result->get_error_code() );
	}

	public function test_maybe_decode_json_with_array() {
		$method = new \ReflectionMethod( '\AudioTheme_Agent_Client', 'maybe_decode_json' );
		$method->setAccessible( true );

		$value = array( 'a' => 1, 'b' => 2 );

		$this->assertSame( $value, $method->invoke( $this->client, $value ) );
	}

	public function test_maybe_decode_json_with_json() {
		$method = new \ReflectionMethod( '\AudioTheme_Agent_Client', 'maybe_decode_json' );
		$method->setAccessible( true );

		$value = wp_json_encode( array( 'a' => 1, 'b' => 2 ) );
		$result = $method->invoke( $this->client, $value );

		$this->assertSame( 1, $result->a );
		$this->assertSame( 2, $result->b );
	}

	public function test_maybe_decode_json_with_object() {
		$method = new \ReflectionMethod( '\AudioTheme_Agent_Client', 'maybe_decode_json' );
		$method->setAccessible( true );

		$value = new \stdClass;
		$value->a = 1;

		$this->assertSame( $value, $method->invoke( $this->client, $value ) );
	}

	public function test_maybe_decode_json_with_plaintext() {
		$method = new \ReflectionMethod( '\AudioTheme_Agent_Client', 'maybe_decode_json' );
		$method->setAccessible( true );

		$value = 'a string';

		$this->assertSame( $value, $method->invoke( $this->client, $value ) );
	}
}
