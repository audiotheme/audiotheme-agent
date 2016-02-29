<?php

namespace AudioTheme\Agent\Test\Integration;

use AudioTheme_Agent_Client;


class ClientRegistrationTest extends \WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->client = new AudioTheme_Agent_Client();
	}

	public function test_is_registered() {
		$this->assertFalse( $this->client->is_registered() );
	}
}
