<?php
/**
 * Class WP2GravRolesTest
 *
 * @package wp2grav
 */

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests for `wp2grav-roles` command.
 */
class WP2GravRolesTest extends TestCase {

	private $export_dir;
	private $groups_yaml;


	public static function setUpBeforeClass(): void {
		// Find wp2grav_exporter plugin path.
		$test_plugin_dir = explode( '/', plugin_dir_path( __FILE__ ) );
		$needle          = array_search( 'wp2grav_exporter', $test_plugin_dir, true );
		$test_plugin_dir = array_slice( $test_plugin_dir, 0, $needle + 1 );
		$test_plugin_dir = implode( '/', $test_plugin_dir );

		include_once $test_plugin_dir . '/plugins/wp2grav-roles.php';

		wp2grav_export_roles(array(), array());
	}

	public static function tearDownAfterClass(): void {
		global $wp_filesystem;
		$groups_yaml = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/config/groups.yaml';
		$wp_filesystem->delete( $groups_yaml );
	}

	protected function setUp(): void {
		$this->export_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$this->groups_yaml  = $this->export_dir . 'config/groups.yaml';
        $this->assertFileExists(
            $this->groups_yaml,
            "Missing config/groups.yaml"
        );
    }

	public function testValidateGroupsYaml(): void {
		$groups_yaml = Yaml::parseFile( $this->groups_yaml );

		// There are six built-in roles.
		$this->assertSame( 6, count($groups_yaml) );

        $login_access = array (
            "site" => array (
                "login" => true
            ),
        );
        $admin_access = array (
            "site" => array (
                "login" => true
            ),
            "admin" => array (
                "login" => true,
                "super" => true
            ),
        );
        $authenticated_access = array (
            "admin" => array (
                "login" => true,
            ),
        );

        $roles = array(
            "wp_administrator" => $admin_access,
            "wp_editor" => $login_access,
            "wp_author" => $login_access,
            "wp_contributor" => $login_access,
            "wp_subscriber" => $login_access,
            "wp_authenticated_user" => $authenticated_access,
        );

        foreach ($roles as $name => $role) {
            $this->assertArrayHasKey(
                $name,
                $groups_yaml,
                "Missing $name"
            );
            $this->assertSame(
                $role,
                $groups_yaml[$name]['access'],
                "Failed access for $name"
            );
        }
	}
}

