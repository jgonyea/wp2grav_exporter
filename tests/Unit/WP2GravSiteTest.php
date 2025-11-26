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

	/**
	 * Primary export directory for the test site's Grav artifacts.
	 *
	 * @var string
	 */
	private $export_dir;

	/**
	 * File path to YAML containing site configuration.
	 *
	 * @var string
	 */
	private $site_yaml;

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

		include_once $test_plugin_dir . '/plugins/wp2grav-site.php';

		// Set up test content for export.
		self::generate_blog_meta();
		self::generate_custom_taxonomy();

		// Run the export.
		wp2grav_export_site();
	}

	/**
	 * Generates blog meta values for testing purposes.
	 *
	 * @return void
	 */
	private static function generate_blog_meta(): void {
		// Set custom WordPress blog name.
		update_option( 'blogname', 'Test PHPUnit Site' );
		update_option( 'blogdescription', 'A local test PHPUnit site' );
	}

	/**
	 * Generates custom 'Subjects' taxonomy.
	 *
	 * @return void
	 */
	public static function generate_custom_taxonomy(): void {

		// New 'Subjects' taxonomy definition.
		$labels = array(
			'name'      => 'Subjects',
			'menu_name' => 'Subjects',
		);

		// Register the taxonomy.
		register_taxonomy(
			'subjects',
			array( 'post' ),
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
		$export_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$config_dir = $export_dir . 'config/';
		$wp_filesystem->rmdir( $config_dir, true );
	}

	/**
	 * Runs before any test in this class.
	 *
	 * Sets class member variable for export_dir and site_yaml and ensures the exported site.yaml file exists.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->export_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$this->site_yaml  = $this->export_dir . 'config/site.yaml';
		$this->assertFileExists(
			$this->site_yaml,
			'Missing site.yaml'
		);
	}

	/**
	 * Verifies site title.
	 *
	 * @return void
	 */
	public function testSiteYaml(): void {
		$site_yaml = Yaml::parseFile( $this->site_yaml );

		// Grav Site title should match WordPress blogname.
		$this->assertSame( $site_yaml['title'], 'Test PHPUnit Site' );
	}

	/**
	 * Validates site author.
	 *
	 * @return void
	 */
	public function testSiteAuthor(): void {
		$site_yaml = Yaml::parseFile( $this->site_yaml );

		// Grav Site author should match user 0's information.
		$this->assertSame( 'admin', $site_yaml['author']['name'] );
		$this->assertSame( 'admin@example.org', $site_yaml['author']['email'] );
	}

	/**
	 * Validates site metadata.
	 *
	 * @return void
	 */
	public function testSiteMetadata(): void {
		$site_yaml = Yaml::parseFile( $this->site_yaml );

		// Grav site description should match WordPress blogdescription.
		$this->assertSame( 'A local test PHPUnit site', $site_yaml['metadata']['description'] );
	}

	/**
	 * Validates site taxonomy.
	 *
	 * @return void
	 */
	public function testSiteTaxonomy(): void {
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
