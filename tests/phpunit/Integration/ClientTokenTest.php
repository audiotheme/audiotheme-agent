<?php

namespace AudioTheme\Agent\Test\Integration;

use AudioTheme_Agent_Client;
use WP_Error;


class ClientTokenTest extends \WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->client = $this->getMockBuilder( '\AudioTheme_Agent_Client' )
			->setMethods( array( 'wp_remote_request' ) )
			->getMock();
	}

	public function tearDown() {
		delete_option( AudioTheme_Agent_Client::TOKEN_OPTION_NAME );
		parent::tearDown();
	}

	public function test_save_token() {
		$token = new \stdClass;
		$token->access_token = 'abcdef';
		$token->refresh_token = 'zyxwvu';
		$token->expires_in = 100;

		$token = $this->client->save_token( $token );

		$option = get_option( AudioTheme_Agent_Client::TOKEN_OPTION_NAME );
		$this->assertEqualFields( $token, $option );
		$this->assertArrayHasKey( 'expires_at', $option );

		$token = $this->client->save_token( $token );
	}

	public function test_save_token_with_missing_access_token() {
		$token = new \stdClass;
		$token = $this->client->save_token( $token );

		$this->assertWPError( $token );
		$this->assertSame( 'missing_token', $token->get_error_code() );
	}

	public function test_save_token_with_missing_refresh_token() {
		$token = new \stdClass;
		$token->access_token = 'abcdef';
		$token = $this->client->save_token( $token );

		$this->assertWPError( $token );
		$this->assertSame( 'missing_token', $token->get_error_code() );
	}

	public function test_save_token_error() {
		$token = $this->client->save_token( new WP_Error() );
		$this->assertWPError( $token );
	}

	public function test_access_token_accessor() {
		$token = new \stdClass;
		$token->access_token = 'abcdef';
		$token->refresh_token = 'zyxwvu';

		$token = $this->client->save_token( $token );

		$method = new \ReflectionMethod( '\AudioTheme_Agent_Client', 'get_access_token' );
		$method->setAccessible( true );

		$this->assertSame( $token->access_token, $method->invoke( $this->client ) );
	}

	public function test_grant_value_accessor() {
		$token = new \stdClass;
		$token->access_token = 'abcdef';
		$token->refresh_token = 'zyxwvu';
		$token->expires_in = 100;

		$token = $this->client->save_token( $token );

		$method = new \ReflectionMethod( '\AudioTheme_Agent_Client', 'get_grant_value' );
		$method->setAccessible( true );

		$this->assertNull( $method->invoke( $this->client, 'unknown_attribute' ) );
		$this->assertSame( $token->refresh_token, $method->invoke( $this->client, 'refresh_token' ) );
	}
}
