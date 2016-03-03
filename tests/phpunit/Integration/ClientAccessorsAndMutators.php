<?php

namespace AudioTheme\Agent\Test\Integration;

use AudioTheme_Agent_Client;


class ClientAccessorsAndMutatorsTest extends \WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->client = new AudioTheme_Agent_Client();
	}

	public function test_application_url() {
		$expected = self_admin_url( 'index.php' ) . '?page=audiotheme-agent';
		$this->assertSame( $expected, $this->client->get_application_url() );
	}

	public function test_authorization_endpoint_accessor_and_mutator() {
		$this->client->set_authorization_endpoint( 'https://example.com/oauth2/authorize' );
		$this->assertSame( 'https://example.com/oauth2/authorize', $this->client->get_authorization_endpoint() );
	}

	public function test_base_url_accessor_and_mutator() {
		$base_url = 'https://example.com/api';
		$this->client->set_base_url( $base_url );

		$this->assertSame( $base_url . '/', $this->client->get_url() );

		$this->client->set_base_url( $base_url );
		$this->assertSame( $base_url . '/', $this->client->get_url() );

		$this->assertSame( $base_url . '/path', $this->client->get_url( '/path' ) );
		$this->assertSame( $base_url . '/path', $this->client->get_url( 'path' ) );
	}

	public function test_client_id_accessor_and_mutator() {
		$this->client->set_client_id( '123456789' );
		$this->assertSame( '123456789', $this->client->get_client_id() );
	}

	public function test_client_secret_accessor_and_mutator() {
		$this->client->set_client_secret( '987654321' );
		$this->assertSame( '987654321', $this->client->get_client_secret() );
	}

	public function test_registration_endpoint_accessor_and_mutator() {
		$this->client->set_registration_endpoint( 'https://example.com/oauth2/register' );
		$this->assertSame( 'https://example.com/oauth2/register', $this->client->get_registration_endpoint() );
	}

	public function test_subscription_endpoint_accessor_and_mutator() {
		$this->client->set_subscription_endpoint( 'https://example.com/oauth2/subscribe' );
		$this->assertSame( 'https://example.com/oauth2/subscribe', $this->client->get_subscription_endpoint() );
	}

	public function test_token_endpoint_accessor_and_mutator() {
		$this->client->set_token_endpoint( 'https://example.com/oauth2/token' );
		$this->assertSame( 'https://example.com/oauth2/token', $this->client->get_token_endpoint() );
	}

	public function test_client_metadata() {
		$meta = $this->client->get_client_metadata();
		$redirect_uri = admin_url( 'admin-post.php' ) . '?action=authorize-audiotheme-agent';

		$this->assertSame( 'e3c8b4b3-3e0b-4c75-af42-0cd70202f391', $meta['software_id'] );
		$this->assertSame( $redirect_uir, $meta['redirect_uri'] );
	}
}
