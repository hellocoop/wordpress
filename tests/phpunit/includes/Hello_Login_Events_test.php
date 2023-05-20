<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;

class Hello_Login_Events_Test extends TestCase {
	use MockeryPHPUnitIntegration;

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	private function create_invites_object(): Hello_Login_Events {
		$default_settings = array(
			'endpoint_login' => 'https://wallet.hello.coop/authorize',
			'client_id'      => '4c1ec93b-2714-4d72-9582-b09c515c5bd8',
		);

		$settings = new Hello_Login_Option_Settings( Hello_Login::OPTION_NAME, $default_settings );
		$logger = new Hello_Login_Option_Logger( Hello_Login::LOGS_OPTION_NAME, 'error', false );
		$users = new Hello_Login_Users( $logger, $settings );

		return new Hello_Login_Events( $logger, $settings, $users );
	}

	public function test_decode_event_success() {
		Monkey\Functions\Stubs(
			[
				'get_option' => array(),
			],
		);

		$hli = $this->create_invites_object();

		$decoded = $hli->decode_event( 'head.eyJpc3MiOiJodHRwczovL2lzc3Vlci5oZWxsby5jb29wIiwiYXVkIjoiNGMxZWM5M2ItMjcxNC00ZDcyLTk1ODItYjA5YzUxNWM1YmQ4IiwianRpIjoiRWtCbXdUNzRBYzI1elJHNnBmMlBQIiwiaWF0IjoxNjc5MTAzNjkyLCJzdWIiOiJlNjY1YzFmZi05OTA1LTQ0ODUtYWY4Yy04M2Y4OGM1MTMyYWUiLCJlbWFpbCI6ImpvaG5zbWl0aEBleGFtcGxlLmNvbSIsImV2ZW50cyI6eyJodHRwczovL3dhbGxldC5oZWxsby5jb29wL2ludml0ZS9jcmVhdGVkIjp7Imludml0ZXIiOnsic3ViIjoiNTlhMjcxOTMtZTkxOS00NjJiLWEzMjQtNDE1OGQ1YWQzNzJkIn0sInJvbGUiOiJzdWJzY3JpYmVyIn19fQ.sign' );

		$this->assertTrue( is_array( $decoded ) );
		$this->assertSame( 'https://issuer.hello.coop', $decoded['iss'] );
		$this->assertSame( 'e665c1ff-9905-4485-af8c-83f88c5132ae', $decoded['sub'] );
		$this->assertSame( 'subscriber', $decoded['events']['https://wallet.hello.coop/invite/created']['role']);
	}

	public function test_decode_event_invalid() {
		Monkey\Functions\Stubs(
			[
				'get_option' => array(),
				'do_action',
			],
		);

		$hli = $this->create_invites_object();

		$error = $hli->decode_event( 'abc.def' ); // only two parts
		$this->assertFalse( is_array( $error ) );
		$this->assertTrue( $error instanceof WP_Error );
		$this->assertStringContainsStringIgnoringCase( 'parts', $error->get_error_message() );

		$error = $hli->decode_event( 'abc.def.ghi.jkl' ); // more than three parts
		$this->assertFalse( is_array( $error ) );
		$this->assertTrue( $error instanceof WP_Error );
		$this->assertStringContainsStringIgnoringCase( 'parts', $error->get_error_message() );

		$error = $hli->decode_event( 'head.not^base64.sign' ); // payload is invalid base64
		$this->assertFalse( is_array( $error ) );
		$this->assertTrue( $error instanceof WP_Error );
		$this->assertStringContainsStringIgnoringCase( 'base64', $error->get_error_message() );


		$error = $hli->decode_event( 'head.aW52YWxpZCB9IGpzb24.sign' ); // payload is invalid json: "invalid } json"
		$this->assertFalse( is_array( $error ) );
		$this->assertTrue( $error instanceof WP_Error );
		$this->assertStringContainsStringIgnoringCase( 'json', $error->get_error_message() );
	}

	public function test_validate_event_success() {
		Monkey\Functions\Stubs(
			[
				'get_option' => array(),
				'do_action',
			],
		);

		$hli = $this->create_invites_object();

		$event = [
			'iss' => 'https://issuer.hello.coop',
			'aud' => '4c1ec93b-2714-4d72-9582-b09c515c5bd8',
		];

		$res = $hli->validate_event( $event );
		$this->assertFalse( $res instanceof WP_Error );
		$this->assertTrue( $res );

		$event['iss'] .= '/';
		$res = $hli->validate_event( $event );
		$this->assertTrue( $res instanceof WP_Error );
	}
}
