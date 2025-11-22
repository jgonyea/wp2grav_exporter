<?php
/**
 * Class WP2GravPostsTest
 *
 * @package wp2grav
 */

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests for `wp2grav-site` command.
 */
class WP2GravPostsTest extends TestCase {

	private $export_dir;
    private $artifact_dir;

    private $post_types;

	public static function setUpBeforeClass(): void {
		// Set custom WordPress blog name.
		update_option( 'blogname', 'Test PHPUnit Site' );
		update_option( 'blogdescription', 'A local test PHPUnit site' );

		// Find wp2grav_exporter plugin path.
		$test_plugin_dir = explode( '/', plugin_dir_path( __FILE__ ) );
		$needle          = array_search( 'wp2grav_exporter', $test_plugin_dir, true );
		$test_plugin_dir = array_slice( $test_plugin_dir, 0, $needle + 1 );
		$test_plugin_dir = implode( '/', $test_plugin_dir );

		include_once $test_plugin_dir . '/plugins/wp2grav-posts.php';

		wp2grav_export_posts(array(), array());
	}

	public static function tearDownAfterClass(): void {
		global $wp_filesystem;
        $export_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$pages_dir  = $export_dir . 'pages';
        $data_dir = $export_dir . 'data';
		$wp_filesystem->rmdir( $pages_dir, true);
		$wp_filesystem->rmdir( $data_dir, true );
	}

	protected function setUp(): void {
		$this->export_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$this->artifact_dir  = $this->export_dir . 'plugins/wordpress-exporter-helper/';
        $this->assertFileExists(
            $this->artifact_dir . 'blueprints',
            "Missing blueprints directory"
        );
        $this->assertFileExists(
            $this->artifact_dir . 'templates',
            "Missing templates directory"
        );
        $this->post_types = get_post_types( array( 'public' => true ) );
	}

}
