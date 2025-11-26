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

	/**
	 * Primary export directory for the test site's Grav artifacts.
	 *
	 * @var string
	 */
	private $export_dir;
	/**
	 * File path to YAML containing group configuration.
	 *
	 * @var string
	 */
	private $groups_yaml;


	/**
	 * Pre-configures the test environment before any tests are run.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		// Find wp2grav_exporter plugin path.
		$test_plugin_dir = explode( '/', plugin_dir_path( __FILE__ ) );
		$needle          = array_search( 'wp2grav_exporter', $test_plugin_dir, true );
		$test_plugin_dir = array_slice( $test_plugin_dir, 0, $needle + 1 );
		$test_plugin_dir = implode( '/', $test_plugin_dir );

		include_once $test_plugin_dir . '/plugins/wp2grav-roles.php';

		wp2grav_export_roles();
	}


	/**
	 * Deletes any exported artifacts generated from this test class.
	 *
	 * @return void
	 */
	public static function tearDownAfterClass(): void {
		if ( ! defined( 'WP2GRAV_DELETE_ARTIFACTS' ) || WP2GRAV_DELETE_ARTIFACTS === false ) {
			return;
		}
		global $wp_filesystem;
		$groups_yaml = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/config/groups.yaml';
		$wp_filesystem->delete( $groups_yaml );
	}

	/**
	 * Runs before any test in this class.
	 *
	 * Sets class member variable for export_dir and group_yaml and ensures the exported groups.yaml file exists.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->export_dir  = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$this->groups_yaml = $this->export_dir . 'config/groups.yaml';
		$this->assertFileExists(
			$this->groups_yaml,
			'Missing config/groups.yaml'
		);
	}

	/**
	 * Validates group.yaml output.
	 *
	 * @return void
	 */
	public function testGroupsYamlContent(): void {
		$groups_yaml = Yaml::parseFile( $this->groups_yaml );

		// There are six built-in roles.
		$this->assertSame( 6, count( $groups_yaml ) );

		$login_access         = array(
			'site' => array(
				'login' => true,
			),
		);
		$admin_access         = array(
			'site'  => array(
				'login' => true,
			),
			'admin' => array(
				'login' => true,
				'super' => true,
			),
		);
		$authenticated_access = array(
			'admin' => array(
				'login' => true,
			),
		);

		$roles = array(
			'wp_administrator'      => $admin_access,
			'wp_editor'             => $login_access,
			'wp_author'             => $login_access,
			'wp_contributor'        => $login_access,
			'wp_subscriber'         => $login_access,
			'wp_authenticated_user' => $authenticated_access,
		);

		foreach ( $roles as $name => $role ) {
			$this->assertArrayHasKey(
				$name,
				$groups_yaml,
				"Missing $name"
			);
			$this->assertSame(
				$role,
				$groups_yaml[ $name ]['access'],
				"Failed access for $name"
			);
		}
	}
}
