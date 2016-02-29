<?php

namespace AudioTheme\Agent\Test\Integration;

use AudioTheme_Agent_Client;
use WP_Error;


class ClientAuthorizationTest extends \WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->client = $this->getMockBuilder( '\AudioTheme_Agent_Client' )
			->setMethods( array( 'authorize', 'report_error', 'send_to_application' ) )
			->getMock();

		$this->client->method( 'report_error' )
			->will( $this->returnArgument( 0 ) );
	}

	public function test_is_authorized() {
		$this->assertFalse( $this->client->is_authorized() );
	}

	public function test_callback_without_action() {
		$result = $this->client->handle_callback();
		$this->assertNull( $result );
	}

	public function test_callback_with_invalid_state() {
		$_GET['action'] = 'authorize-audiotheme-agent';
		$result = $this->client->handle_callback();
		$this->assertWPError( $result );
		$this->assertSame( 'invalid_state', $result->get_error_code() );

		$_GET['state'] = 'aaaaaa';
		$this->assertWPError( $result );
		$this->assertSame( 'invalid_state', $result->get_error_code() );
	}

	public function test_callback_with_user_denied_error() {
		$_GET = array(
			'action' => 'authorize-audiotheme-agent',
			'state'  => wp_create_nonce( 'authorize-client_' . get_current_user_id() ),
			'error'  => 'access_denied',
		);

		$result = $this->client->handle_callback();
		$this->assertWPError( $result );
		$this->assertSame( 'access_denied', $result->get_error_code() );
	}

	public function test_callback_with_response_error() {
		$_GET = array(
			'action' => 'authorize-audiotheme-agent',
			'state'  => wp_create_nonce( 'authorize-client_' . get_current_user_id() ),
			'error'  => 'generic_error',
		);

		$result = $this->client->handle_callback();
		$this->assertWPError( $result );
		$this->assertSame( 'generic_error', $result->get_error_code() );
		$this->assertSame( 'The authorization server returned an error.', $result->get_error_message() );

		$message = 'Error message supplied in response.';
		$_GET['error_description'] = $message;
		$result = $this->client->handle_callback();
		$this->assertSame( $message, $result->get_error_message() );

		$_GET['error_uri'] = 'https://example.com/error_uri';
		$result = $this->client->handle_callback();
		$data = $result->get_error_data();
		$this->assertArrayHasKey( 'error_uri', $data );
	}

	public function test_callback_with_code_exchange_error() {
		$_GET = array(
			'action' => 'authorize-audiotheme-agent',
			'state'  => wp_create_nonce( 'authorize-client_' . get_current_user_id() ),
			'code'   => 'abc123',
		);

		$this->client->method( 'authorize' )
			->willReturn( new WP_Error() );

		$result = $this->client->handle_callback();
		$this->assertWPError( $result );
	}

	public function test_successful_callback() {
		$_GET = array(
			'action' => 'authorize-audiotheme-agent',
			'state'  => wp_create_nonce( 'authorize-client_' . get_current_user_id() ),
			'code'   => 'abc123',
		);

		$response = new \stdClass;
		$response->access_token = 'abcdef';
		$response->refresh_token = 'zyxwvu';
		$response->expires_in = 100;

		$this->client->method( 'authorize' )
			->willReturn( $response );

		$result = $this->client->handle_callback();

		$this->assertNull( $result );
		$this->assertTrue( $this->client->is_authorized() );
	}
}
