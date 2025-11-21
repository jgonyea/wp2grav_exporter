<?php
/**
 * Class ConvertRolesTest
 *
 * @package Wp2grav_exporter
 */

use PHPUnit\Framework\OutputError;

/**
 * Sample test case.
 */
class ConvertRolesTest extends WP_UnitTestCase {

	public function test_ConvertRoleAdmin() {
        $this->assertEquals('Administrator', convert_role_wp_to_grav('Administrator'));
    }

    public function test_ConvertRoleEditor() {
        $this->assertEquals('Editor', convert_role_wp_to_grav('Editor'));
    }

    public function test_ConvertRoleLowerCaseTest() {
        $this->assertEquals('lowercasetest', convert_role_wp_to_grav('lowercasetest'));
    }

    public function test_ConvertRoleWithSpaces() {
        $this->assertEquals('Role_with_spaces', convert_role_wp_to_grav('Role with spaces'));
    }

}
