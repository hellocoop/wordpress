<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;

class Hello_Login_Federation_Groups_Test extends TestCase {
	use MockeryPHPUnitIntegration;

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	private function create_federation_groups_instance(): Hello_Login_Federation_Groups {
		$logger = new Hello_Login_Option_Logger( Hello_Login::LOGS_OPTION_NAME, 'error', false );

		return new Hello_Login_Federation_Groups( $logger );
	}

	private function create_option_orgs_groups(): array {
		return array(
			array(
				'org'    => 'example.com',
				'id'     => 1,
				'groups' => array(
					array(
						'value'   => '1234',
						'id'      => 1,
						'display' => 'Admins',
					),
					array(
						'value'   => '5678',
						'id'      => 2,
						'display' => 'Marketing',
					),
				),
			),
			array(
				'org'    => 'example.net',
				'id'     => 2,
				'groups' => array(
					array(
						'value'   => '1234',
						'id'      => 1,
						'display' => 'Engineering',
						'deleted' => false,
					),
					array(
						'value'   => '5678',
						'id'      => 2,
						'display' => 'Sales',
					),
					array(
						'value'   => '9012',
						'id'      => 3,
						'display' => 'PR',
						'deleted' => true,
					),
				),
			),
			array(
				'org'    => 'example.org',
				'id'     => 3,
				'groups' => array(),
			),
			array(
				'org'    => 'example.io',
				'id'     => 4,
				'groups' => array(
					array(
						'value'   => '111',
						'id'      => 1,
						'display' => 'Ghost',
						'deleted' => true,
					),
				),
			),
		);
	}

	private function create_example_com_sync_org_groups(): array {
		return array(
			'org'    => 'example.com',
			'groups' => array(
				array(
					'value'   => '1234',
					'display' => 'Admins',
				),
				array(
					'value'   => '5678',
					'display' => 'Marketing',
				),
			),
		);
	}

	private function create_example_net_sync_org_groups(): array {
		return array(
			'org'    => 'example.net',
			'groups' => array(
				array(
					'value'   => '1234',
					'display' => 'Engineering',
				),
				array(
					'value'   => '5678',
					'display' => 'Sales',
				),
			),
		);
	}

	private function create_empty_example_com_sync_org_groups(): array {
		return array(
			'org'    => 'example.com',
			'groups' => array(),
		);
	}

	public function test_get_orgs_groups() {
		Monkey\Functions\Stubs(
			[
				'get_option' => $this->create_option_orgs_groups(),
			],
		);

		$fg = $this->create_federation_groups_instance();

		$osgs = $fg->get_orgs_groups();

		$this->assertCount( 2, $osgs );
		$this->assertCount( 2, $osgs[0]['groups'] );
		$this->assertCount( 2, $osgs[1]['groups'] );
	}

	public function test_sync_empty() {
		Monkey\Functions\Stubs(
			[
				'get_option' => array(),
				'update_option',
			],
		);

		$fg = $this->create_federation_groups_instance();

		$osgs = $fg->get_orgs_groups();

		$this->assertEmpty( $osgs );

		$this->assertNull( $fg->sync( $this->create_empty_example_com_sync_org_groups() ) );

		$osgs = $fg->get_orgs_groups();

		$this->assertEmpty( $osgs );
	}

	public function test_sync() {
		Monkey\Functions\Stubs(
			[
				'get_option' => array(),
				'update_option',
			],
		);

		$fg = $this->create_federation_groups_instance();

		$osgs = $fg->get_orgs_groups();

		$this->assertEmpty( $osgs );

		// add groups for example.com org
		$this->assertNull( $fg->sync( $this->create_example_com_sync_org_groups() ) );

		$osgs = $fg->get_orgs_groups();

		$this->assertCount( 1, $osgs );
		$this->assertCount( 2, $osgs[0]['groups'] );

		// add groups for example.net org
		$this->assertNull( $fg->sync( $this->create_example_net_sync_org_groups() ) );

		$osgs = $fg->get_orgs_groups();

		$this->assertCount( 2, $osgs );
		$this->assertCount( 2, $osgs[0]['groups'] );
		$this->assertCount( 2, $osgs[1]['groups'] );

		// remove all groups for example.com org
		$this->assertNull( $fg->sync( $this->create_empty_example_com_sync_org_groups() ) );

		$osgs = $fg->get_orgs_groups();

		$this->assertCount( 1, $osgs );
		$this->assertCount( 2, $osgs[0]['groups'] );
	}
}
