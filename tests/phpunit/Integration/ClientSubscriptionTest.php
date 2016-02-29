<?php

namespace AudioTheme\Agent\Test\Integration;

use AudioTheme_Agent_Client;


class ClientSubscriptionTest extends \WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->client = $this->getMockBuilder( '\AudioTheme_Agent_Client' )
			->setMethods( array( 'wp_remote_request' ) )
			->getMock();

		$this->client->method( 'wp_remote_request' )
			->will( $this->returnCallback( array( $this, '_mock_subscribe_response' ) ) );
	}

	public function tearDown() {
		delete_option( AudioTheme_Agent_Client::CLIENT_OPTION_NAME );
		delete_option( AudioTheme_Agent_Client::TOKEN_OPTION_NAME );
		parent::tearDown();
	}

	public function test_subscribe() {
		$client = $this->client->subscribe( 'abcdef' );

		$this->assertTrue( $client->is_authorized() );
		$this->assertTrue( $client->is_registered() );

		$this->assertSame( '123456789', $client->get_client_id() );
		$this->assertSame( '987654321', $client->get_client_secret() );

		$this->assertArraySubset( $client->get_client_metadata(), $client->get_registered_metadata() );

		$method = new \ReflectionMethod( '\AudioTheme_Agent_Client', 'get_access_token' );
		$method->setAccessible( true );

		$this->assertSame( 'abcdef', $method->invoke( $client ) );
	}

	public function _mock_subscribe_response( $url, $args ) {
		$metadata = json_decode( $args['body'] );

		$register = array(
			'client_id'                => '123456789',
			'client_secret'            => '987654321',
			'client_id_issued_at'      => time(),
			'client_secret_expires_at' => 0,
		);

		$body = array(
			'register'  => array_merge( $register, (array) $metadata ),
			'authorize' => array(
				'access_token'  => 'abcdef',
				'token_type'    => 'bearer',
				'expires_in'    => 120,
				'scope'         => 'read',
				'refresh_token' => 'zyxwvu',
				'user_id'       => 1,
			)
		);

		return array(
			'body'     => wp_json_encode( $body ),
			'headers'  => array(
				'content-type' => 'application/json',
			),
			'response' => array(
				'code' => 201,
			),
		);
	}
}
