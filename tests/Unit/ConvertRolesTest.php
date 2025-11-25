<?php
/**
 * Class ConvertRolesTest
 *
 * @package Wp2grav_exporter
 */

use PHPUnit\Framework\OutputError;

/**
 * Converts WordPress role names to Grav group names.
 */
class ConvertRolesTest extends WP_UnitTestCase {

	/**
	 * Convert 'Administrator' role name.
	 *
	 * @return void
	 */
	public function test_ConvertRoleAdmin() {
		$this->assertEquals( 'Administrator', convert_role_wp_to_grav( 'Administrator' ) );
	}

	/**
	 * Convert 'Editor' role name.
	 *
	 * @return void
	 */
	public function test_ConvertRoleEditor() {
		$this->assertEquals( 'Editor', convert_role_wp_to_grav( 'Editor' ) );
	}

	/**
	 * Convert a lower case role.
	 *
	 * @return void
	 */
	public function test_ConvertRoleLowerCaseTest() {
		$this->assertEquals( 'lowercasetest', convert_role_wp_to_grav( 'lowercasetest' ) );
	}

	/**
	 * Convert role name with spaces.
	 *
	 * @return void
	 */
	public function test_ConvertRoleWithSpaces() {
		$this->assertEquals( 'Role_with_spaces', convert_role_wp_to_grav( 'Role with spaces' ) );
	}
}
