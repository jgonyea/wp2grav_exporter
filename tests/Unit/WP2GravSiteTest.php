<?php
/**
 * Class WP2GravSiteTest
 *
 * @package wp2grav
 */

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests for `wp2grav-site` command.
 */
class WP2GravSiteTest extends TestCase {

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

		self::generate_custom_taxonomy();

		wp2grav_export_site();
	}

	private static function generate_custom_taxonomy(): void {

		// New 'Subjects' taxonomy definition.
		$labels = array(
			'name'      => _x( 'Subjects', 'taxonomy general name' ),
			'menu_name' => __( 'Subjects' ),
		);

		// Register the taxonomy.
		register_taxonomy(
			'subjects',
			array( 'posts' ),
			array(
				'hierarchical'      => true,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'public'            => true,
				'rewrite'           => array( 'slug' => 'subject' ),
			)
		);
	}

	public static function tearDownAfterClass(): void {
		global $wp_filesystem;
		$export_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$config_dir = $export_dir . 'config/';
		$wp_filesystem->rmdir( $config_dir, true );
	}

	protected function setUp(): void {
		$this->export_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$this->site_yaml  = $this->export_dir . 'config/site.yaml';
		$this->assertFileExists(
			$this->site_yaml,
			'Missing site.yaml'
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

	public function testValidateSiteTaxonomy(): void {
		$site_yaml = Yaml::parseFile( $this->site_yaml );

		$this->assertArrayHasKey(
			'taxonomies',
			$site_yaml,
			'Missing taxonomies'
		);
		$this->assertSame(
			array( 'category', 'post_tag', 'post_format', 'subjects' ),
			$site_yaml['taxonomies'],
			'Incorrect site taxonomy'
		);
	}
}
