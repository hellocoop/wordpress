<?php declare(strict_types=1);
//use PHPUnit_Framework_TestCase;
use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;

class Hello_Login_Test extends TestCase {
	use MockeryPHPUnitIntegration;

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_user_agent_hook() {
		Monkey\Functions\Stubs(
			[
				'get_option' => array(),
				'add_action' => true,
				'add_filter' => true,
				'register_activation_hook',
				'register_deactivation_hook',
				'register_uninstall_hook',
			],
		);
		$default_settings = array(
			'endpoint_token' => 'https://wallet.hello.coop/oauth/token',
		);
		$settings = new Hello_Login_Option_Settings( Hello_Login::OPTION_NAME, $default_settings );
		$logger = new Hello_Login_Option_Logger( Hello_Login::LOGS_OPTION_NAME, 'error', false );

		$hl = new Hello_Login( $settings, $logger );

		$default_user_agent = 'WordPress/6.2.0;https://example.com/';
		$hello_signature = ';HelloLogin/' . Hello_Login::VERSION;

		$this->assertSame( $default_user_agent, $hl->user_agent_hook( $default_user_agent, 'https://api.example.com/some/path' ) );
		$this->assertSame( $default_user_agent . $hello_signature, $hl->user_agent_hook( $default_user_agent, 'https://wallet.hello.coop/oauth/token' ) );
	}
}
