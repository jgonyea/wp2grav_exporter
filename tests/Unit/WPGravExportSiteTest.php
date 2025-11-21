<?php
/**
 * Class WPGravExportSiteTest
 *
 * @package wp2grav
 */

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests for `wp2grav-site` command.
 */
class WPGravExportSiteTest extends TestCase {

	private $export_dir;
	private $site_yaml;


	public static function setUpBeforeClass(): void {
		// Set custom WordPress blog name.
		update_option( 'blogname', 'Test PHPUnit Site' );
		update_option( 'blogdescription', 'A local test PHPUnit site' );

		// Find wp2grav_exporter plugin path.
		$test_plugin_dir = explode( '/', plugin_dir_path( __FILE__ ) );
		$needle          = array_search( 'wp2grav_exporter', $test_plugin_dir, true );
		$test_plugin_dir = array_slice( $test_plugin_dir, 0, $needle + 1 );
		$test_plugin_dir = implode( '/', $test_plugin_dir );

		include_once $test_plugin_dir . '/plugins/wp2grav-site.php';

		wp2grav_export_site();
	}

	public static function tearDownAfterClass(): void {
		global $wp_filesystem;
		$site_yaml = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/config/site.yaml';
		$wp_filesystem->delete( $site_yaml );
	}

	protected function setUp(): void {
		$this->export_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$this->site_yaml  = $this->export_dir . 'config/site.yaml';
        $this->assertFileExists(
            $this->site_yaml,
            "Missing site.yaml"
        );
	}

	public function testValidateSiteYaml(): void {
		$site_yaml = Yaml::parseFile( $this->site_yaml );

		// Grav Site title should match WordPress blogname.
		$this->assertSame( $site_yaml['title'], 'Test PHPUnit Site' );
	}

	public function testValidateSiteAuthor(): void {
		$site_yaml = Yaml::parseFile( $this->site_yaml );

		// Grav Site author should match user 0's information.
		$this->assertSame( 'admin', $site_yaml['author']['name'] );
		$this->assertSame( 'admin@example.org', $site_yaml['author']['email'] );
	}

	public function testValidateSiteMetadata(): void {
		$site_yaml = Yaml::parseFile( $this->site_yaml );

		// Grav site description should match WordPress blogdescription.
		$this->assertSame( 'A local test PHPUnit site', $site_yaml['metadata']['description'] );
	}
}

