<?php

namespace AudioTheme\Agent\Test\Integration;

use AudioTheme_Agent_Client;
use WP_Error;


class ClientRequestTest extends \WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->client = $this->getMockBuilder( '\AudioTheme_Agent_Client' )
			->setMethods( array( 'deauthorize', 'refresh_access_token', 'wp_remote_request' ) )
			->getMock();

		add_option( AudioTheme_Agent_Client::CLIENT_OPTION_NAME, array(
			'client_id'     => '123456789',
			'client_secret' => '987654321',
		) );

		add_option( AudioTheme_Agent_Client::TOKEN_OPTION_NAME, array(
			'access_token'  => 'abcdef',
			'refresh_token' => 'zyxwvu',
			'expires_at'    => time() + 300,
			'token_type'    => 'bearer',
		) );
	}

	public function tearDown() {
		delete_option( AudioTheme_Agent_Client::CLIENT_OPTION_NAME );
		delete_option( AudioTheme_Agent_Client::TOKEN_OPTION_NAME );
		parent::tearDown();
	}

	public function test_unauthenticated_request() {
		$client = $this->getMockBuilder( '\AudioTheme_Agent_Client' )
			->setMethods( array( 'is_authorized', 'deauthorize', 'refresh_access_token', 'wp_remote_request' ) )
			->getMock();

		$client->method( 'is_authorized' )->willReturn( false );

		$client->expects( $this->exactly( 0 ) )->method( 'deauthorize' );
		$client->expects( $this->exactly( 0 ) )->method( 'refresh_access_token' );
		$client->expects( $this->exactly( 1 ) )->method( 'wp_remote_request' )->will( $this->returnArgument( 1 ) );

		$args = $client->request( 'https://example.com/api/v1/public' );

		$this->assertArrayNotHasKey( 'Authorization', $args['headers'] );
	}

	public function test_authenticated_request() {
		$this->client->expects( $this->exactly( 0 ) )->method( 'deauthorize' );
		$this->client->expects( $this->exactly( 0 ) )->method( 'refresh_access_token' );
		$this->client->expects( $this->exactly( 1 ) )->method( 'wp_remote_request' )->will( $this->returnArgument( 1 ) );

		$args = $this->client->request( 'https://example.com/api/v1/protected' );

		$this->assertSame( 'GET', $args['method'] );
		$this->assertArrayHasKey( 'Authorization', $args['headers'] );
		$this->assertSame( $args['headers']['Authorization'], 'Bearer abcdef' );
	}

	public function test_authenticated_request_with_expired_access_token() {
		$token = get_option( AudioTheme_Agent_Client::TOKEN_OPTION_NAME );
		$token['expires_at'] = time() - 3600;
		update_option( AudioTheme_Agent_Client::TOKEN_OPTION_NAME, $token );

		$this->client->expects( $this->exactly( 0 ) )->method( 'deauthorize' );
		$this->client->expects( $this->exactly( 1 ) )->method( 'refresh_access_token' )->willReturn( 'a1b2c3' );
		$this->client->expects( $this->exactly( 1 ) )->method( 'wp_remote_request' )->will( $this->returnArgument( 1 ) );

		$args = $this->client->request( 'https://example.com/api/v1/protected' );

		$this->assertSame( $args['headers']['Authorization'], 'Bearer a1b2c3' );
	}

	public function test_authenticated_request_with_expired_access_token_and_refresh_error() {
		$token = get_option( AudioTheme_Agent_Client::TOKEN_OPTION_NAME );
		$token['expires_at'] = time() - 3600;
		update_option( AudioTheme_Agent_Client::TOKEN_OPTION_NAME, $token );

		$this->client->expects( $this->exactly( 1 ) )->method( 'deauthorize' );
		$this->client->expects( $this->exactly( 1 ) )->method( 'refresh_access_token' )->willReturn( new WP_Error( 'error' ) );
		$this->client->expects( $this->exactly( 0 ) )->method( 'wp_remote_request' );

		$response = $this->client->request( 'https://example.com/api/v1/protected' );

		$this->assertWPError( $response );
	}

	public function test_authenticated_request_with_401_response_code() {
		$this->client = $this->getMockBuilder( '\AudioTheme_Agent_Client' )
			->setMethods( array( 'deauthorize', 'refresh_access_token', 'wp_remote_request', 'wp_remote_retrieve_response_code' ) )
			->getMock();

		$this->client->expects( $this->exactly( 0 ) )->method( 'deauthorize' );
		$this->client->expects( $this->exactly( 1 ) )->method( 'refresh_access_token' )->willReturn( 'a1b2c3' );

		$this->client->expects( $this->exactly( 2 ) )->method( 'wp_remote_retrieve_response_code' )
			->will( $this->onConsecutiveCalls( 401, 200 ) );

		$this->client->expects( $this->exactly( 2 ) )->method( 'wp_remote_request' )
			->will( $this->returnCallback( array( $this, '_mock_response' ) ) );

		$response = $this->client->request( 'https://example.com/api/v1/protected' );
		$args = json_decode( $response['body'], true );

		$this->assertSame( $args['headers']['Authorization'], 'Bearer a1b2c3' );
	}

	public function test_authenticated_request_with_401_response_code_and_refresh_error() {
		$this->client = $this->getMockBuilder( '\AudioTheme_Agent_Client' )
			->setMethods( array( 'deauthorize', 'refresh_access_token', 'wp_remote_request', 'wp_remote_retrieve_response_code' ) )
			->getMock();

		$this->client->expects( $this->exactly( 1 ) )->method( 'deauthorize' );
		$this->client->expects( $this->exactly( 1 ) )->method( 'refresh_access_token' )->willReturn( new WP_Error( 'error' ) );
		$this->client->expects( $this->exactly( 1 ) )->method( 'wp_remote_retrieve_response_code' )->willReturn( 401 );

		$response = $this->client->request( 'https://example.com/api/v1/protected' );

		$this->assertWPError( $response );
	}

	public function test_authenticated_request_with_invalid_token() {
		$this->client = $this->getMockBuilder( '\AudioTheme_Agent_Client' )
			->setMethods( array( 'deauthorize', 'refresh_access_token', 'wp_remote_request', 'wp_remote_retrieve_response_code' ) )
			->getMock();

		$this->client->expects( $this->exactly( 1 ) )->method( 'deauthorize' );
		$this->client->expects( $this->exactly( 1 ) )->method( 'refresh_access_token' );
		$this->client->expects( $this->exactly( 2 ) )->method( 'wp_remote_retrieve_response_code' )->willReturn( 401 );
		$this->client->expects( $this->exactly( 2 ) )->method( 'wp_remote_request' );

		$this->client->request( 'https://example.com/api/v1/protected' );
	}

	public function _mock_response( $url, $args ) {
		return array(
			'body'     => wp_json_encode( $args ),
			'headers'  => array(),
			'response' => array(
				'code' => 200,
			),
		);
	}
}
