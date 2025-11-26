<?php
/**
 * Class WP2GravPostTypesTest
 *
 * @package wp2grav
 */

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests for `wp2grav-site` command.
 */
class WP2GravPostTypesTest extends TestCase {

	/**
	 * Primary export directory for the test site's Grav artifacts.
	 *
	 * @var string
	 */
	private $export_dir;

	/**
	 * Primary export directory for post attachments.
	 *
	 * @var string
	 */
	private $artifact_dir;

	/**
	 * Array of public WP_Post types.
	 *
	 * @var array
	 */
	private $post_types;

	/**
	 * Pre-configures the test environment before any tests are run.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		// Set custom WordPress blog name.
		update_option( 'blogname', 'Test PHPUnit Site' );
		update_option( 'blogdescription', 'A local test PHPUnit site' );

		// Find wp2grav_exporter plugin path.
		$test_plugin_dir = explode( '/', plugin_dir_path( __FILE__ ) );
		$needle          = array_search( 'wp2grav_exporter', $test_plugin_dir, true );
		$test_plugin_dir = array_slice( $test_plugin_dir, 0, $needle + 1 );
		$test_plugin_dir = implode( '/', $test_plugin_dir );

		include_once $test_plugin_dir . '/plugins/wp2grav-post-types.php';

		// Generate custom post types.

		register_post_type(
			'movies',
			array(
				'labels'       => array(
					'name'          => 'Movies',
					'singular_name' => 'Movie',
				),
				'public'       => true,
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'movies' ),
				'show_in_rest' => true,
			)
		);

		wp2grav_export_post_types();
	}

	/**
	 * Cleanup of generated account files from test site.
	 *
	 * @return void
	 */
	public static function tearDownAfterClass(): void {
		if ( ! defined( 'WP2GRAV_DELETE_ARTIFACTS' ) || WP2GRAV_DELETE_ARTIFACTS === false ) {
			return;
		}
		global $wp_filesystem;
		$export_dir   = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$artifact_dir = $export_dir . 'plugins';
		$wp_filesystem->rmdir( $artifact_dir, true );
	}

	/**
	 * Set up the test environment before each test.
	 *
	 * Also verifies the expected export directories exist before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->export_dir   = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$this->artifact_dir = $this->export_dir . 'plugins/wordpress-exporter-helper/';
		$this->assertFileExists(
			$this->artifact_dir . 'blueprints',
			'Missing blueprints directory'
		);
		$this->assertFileExists(
			$this->artifact_dir . 'templates',
			'Missing templates directory'
		);
		$this->post_types = get_post_types( array( 'public' => true ) );

		// Remove attachment type from post types.
		unset( $this->post_types['attachment'] );
	}

	/**
	 * Validates that each post type blueprint exists.
	 *
	 * @return void
	 */
	public function testBlueprintsExist(): void {
		foreach ( $this->post_types as $post_type ) {
			$blueprint_file = $this->artifact_dir . 'blueprints/wp_' . $post_type . '.yaml';

			$this->assertFileExists(
				$blueprint_file,
				'Missing blueprint file for post type: ' . $post_type
			);
		}
	}

	/**
	 * Validates that each post type TWIG template exists.
	 *
	 * @return void
	 */
	public function testTemplatesExist(): void {
		$skip_types = array(
			'attachment',
		);
		foreach ( $this->post_types as $post_type ) {
			if ( in_array( $post_type, $skip_types, true ) ) {
				continue;
			}
			$template_file = $this->artifact_dir . 'templates/wp_' . $post_type . '.html.twig';
			$this->assertFileExists(
				$template_file,
				'Missing templates file for post type: ' . $post_type
			);
		}
	}

	/**
	 * Verifies the expected public post types are being exported.
	 *
	 * @return void
	 */
	public function testPostTypesExist(): void {
		$expected = array(
			'page',
			'post',
			'movies',
		);

		foreach ( $this->post_types as $post_type ) {
			$this->assertContains( $post_type, $expected, true );
		}

		$this->assertNotContains( 'attachment', $this->post_types );
	}
}
