<?php
/**
 * Hello Invites logic and handler.
 *
 * @package   Hello_Login
 * @category  Federation
 * @author    Marius Scurtescu <marius.scurtescu@hello.coop>
 * @copyright 2023  Hello Identity Co-op
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * Hello federation groups related logic.
 *
 * @package Hello_Login
 */
class Hello_Login_Federation_Groups {
	/**
	 * Instance of the plugin logger.
	 *
	 * @var Hello_Login_Option_Logger
	 */
	private Hello_Login_Option_Logger $logger;

	/**
	 * Nested array that models the federation groups.
	 *
	 * @var array
	 */
	private array $orgs_groups;

	/**
	 * Create a new instance. Groups are loaded from the corresponding option.
	 *
	 * @param Hello_Login_Option_Logger $logger The plugin logging class object.
	 *
	 * @see self::load()
	 */
	public function __construct( Hello_Login_Option_Logger $logger ) {
		$this->logger = $logger;

		self::load();
	}

	/**
	 * Load the groups from the corresponding WordPress option.
	 *
	 * @see Hello_Login::FEDERATION_GROUPS_OPTION_NAME
	 *
	 * @return void
	 */
	private function load() {
		$this->orgs_groups = get_option( Hello_Login::FEDERATION_GROUPS_OPTION_NAME, array() );
		if ( ! is_array( $this->orgs_groups ) ) {
			$this->logger->log( "Federation groups not an array: {$this->orgs_groups}", 'Hello_Login_Federation_Groups->load' );
			$this->orgs_groups = array();
		}
	}

	/**
	 * Save the groups to the corresponding WordPress option.
	 *
	 * @see Hello_Login::FEDERATION_GROUPS_OPTION_NAME
	 *
	 * @return void
	 */
	private function save() {
		update_option( Hello_Login::FEDERATION_GROUPS_OPTION_NAME, $this->orgs_groups );
	}

	/**
	 * Return the data structure with the orgs and nested groups, appropriate for generating a settings form. Empty
	 * orgs and deleted groups are filtered out.
	 *
	 * @return array
	 */
	public function get_orgs_groups(): array {
		$orgs_groups = array();

		foreach ( $this->orgs_groups as $org_groups ) {
			$groups  = array_filter(
				$org_groups['groups'],
				function ( array $group ): bool {
					return ! ( isset( $group['deleted'] ) && $group['deleted'] );
				}
			);

			if ( empty( $groups ) ) {
				continue;
			}

			$orgs_groups[] = array(
				'org' => $org_groups['org'],
				'id'  => $org_groups['id'],
				'groups' => $groups,
			);
		}

		return $orgs_groups;
	}

	/**
	 * Update the federation groups model based on an incoming federation groups sync event.
	 *
	 * @param array $sub_event The federation groups sync nested event.
	 *
	 * @return ?WP_Error
	 */
	public function sync( array $sub_event ): ?WP_Error {
		$sync_org = $sub_event['org'];

		if ( ! is_string( $sync_org ) ) {
			return new WP_Error( 'org_not_string' );
		}

		if ( empty( $sync_org ) ) {
			return new WP_Error( 'org_missing' );
		}

		$sync_groups = $sub_event['groups'];
		if ( ! is_array( $sync_groups ) ) {
			return new WP_Error( 'groups_not_array' );
		}

		$existing_org_groups = &$this->find_or_insert_org_groups( $sync_org );

		foreach ( $sync_groups as $sync_group ) {
			$existing_group = &$this->find_or_insert_group( $existing_org_groups, $sync_group );
			$existing_group['display'] = $sync_group['display'];
		}

		$this->mark_deleted( $existing_org_groups, $sync_groups );

		$this->save();

		return null;
	}

	/**
	 * Find the org being synced, insert it first if needed.
	 *
	 * @param string $sync_org The org being synced.
	 *
	 * @return array The groups for the sync org, as a reference.
	 */
	private function &find_or_insert_org_groups( string $sync_org ): array {
		$max_org_id = 0;
		foreach ( $this->orgs_groups as &$org_groups ) {
			if ( $org_groups['org'] === $sync_org ) {
				return $org_groups;
			}

			if ( $org_groups['id'] > $max_org_id ) {
				$max_org_id = $org_groups['id'];
			}
		}

		$new_org_groups = array(
			'org'    => $sync_org,
			'id'     => $max_org_id + 1,
			'groups' => array(),
		);
		$this->orgs_groups[] = &$new_org_groups;

		return $new_org_groups;
	}

	/**
	 * Either find the group with the given value or insert a new onw.
	 *
	 * @param array $existing_org_groups The existing org groups.
	 * @param array $sync_group          The group being synced.
	 *
	 * @return array The matching existing group, as a reference.
	 */
	private function &find_or_insert_group( array &$existing_org_groups, array $sync_group ): array {
		$max_group_id = 0;

		$existing_groups = &$existing_org_groups['groups'];
		foreach ( $existing_groups as &$existing_group ) {
			if ( $existing_group['value'] == $sync_group['value'] ) {
				return $existing_group;
			}

			if ( $existing_group['id'] > $max_group_id ) {
				$max_group_id = $existing_group['id'];
			}
		}

		$new_group = array(
			'value'   => $sync_group['value'],
			'id'      => $max_group_id + 1,
			'display' => $sync_group['display'],
		);

		$existing_groups[] = &$new_group;

		return $new_group;
	}

	/**
	 * Mark existing groups that are not presented in the sync groups as being deleted.
	 *
	 * @param array $existing_org_groups The existing org groups.
	 * @param array $sync_groups         The sync groups.
	 *
	 * @return void
	 */
	private function mark_deleted( array &$existing_org_groups, array $sync_groups ) {
		$existing_groups = &$existing_org_groups['groups'];
		foreach ( $existing_groups as &$existing_group ) {
			$deleted = true;

			foreach ( $sync_groups as $sync_group ) {
				if ( $existing_group['value'] == $sync_group['value'] ) {
					$deleted = false;
					break;
				}
			}

			if ( $deleted ) {
				$existing_group['deleted'] = true;
			}
		}
	}
}
