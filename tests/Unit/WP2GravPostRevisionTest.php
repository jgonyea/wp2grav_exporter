<?php
/**
 * Class WP2GravPostRevisionTest
 *
 * @package wp2grav
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests for `wp2grav-posts` command on a post that has revisions.
 */
class WP2GravPostRevisionTest extends TestCase {

	/**
	 * ID of the test post.
	 *
	 * @var int
	 */
	private static $post_id;

	/**
	 * Timestamp string of the generated revision, used to build the expected filename.
	 *
	 * @var string
	 */
	private static $revision_timestamp;

	/**
	 * Primary export directory for the test site's Grav artifacts.
	 *
	 * @var string
	 */
	private $export_dir;

	/**
	 * Pages export directory for the test site's Grav artifacts.
	 *
	 * @var string
	 */
	private $pages_dir;

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

		include_once $test_plugin_dir . '/plugins/wp2grav-posts.php';

		self::generate_post_with_revision();

		wp2grav_export_posts( array(), array() );
	}

	/**
	 * Creates a published post and then updates it to generate a revision.
	 *
	 * @return void
	 */
	private static function generate_post_with_revision(): void {
		// Create the initial post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Revision Test Post',
				'post_content' => 'Original post content.',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);
		self::$post_id = $post_id;

		// Update the post to generate a revision.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => 'Updated post content after revision.',
			)
		);

		// Retrieve the most recent revision to determine the expected filename timestamp.
		$revisions = wp_get_post_revisions( $post_id );
		$revision  = reset( $revisions );
		self::$revision_timestamp = gmdate( 'Ymd-His', strtotime( $revision->post_modified ) );
	}

	/**
	 * Cleanup of generated export files after tests.
	 *
	 * @return void
	 */
	public static function tearDownAfterClass(): void {
		if ( ! defined( 'WP2GRAV_DELETE_ARTIFACTS' ) || WP2GRAV_DELETE_ARTIFACTS === false ) {
			return;
		}
		global $wp_filesystem;
		$export_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$pages_dir  = $export_dir . 'pages';
		$data_dir   = $export_dir . 'data';
		$wp_filesystem->rmdir( $pages_dir, true );
		$wp_filesystem->rmdir( $data_dir, true );
	}

	/**
	 * Set up paths before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->export_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$this->pages_dir  = $this->export_dir . 'pages/';
	}

	/**
	 * Verify that the main post markdown file was exported.
	 *
	 * @return void
	 */
	public function testMainPostExists(): void {
		$post      = get_post( self::$post_id );
		$post_file = $this->pages_dir . 'blog/' . $post->post_name . '/wp_post.md';

		$this->assertFileExists(
			$post_file,
			'Missing main post file at ' . $post_file
		);
	}

	/**
	 * Verify that the revision file exists alongside the main post.
	 *
	 * @return void
	 */
	public function testRevisionFileExists(): void {
		$post          = get_post( self::$post_id );
		$revision_file = $this->pages_dir . 'blog/' . $post->post_name . '/wp_post.md.' . self::$revision_timestamp . '.rev';

		$this->assertFileExists(
			$revision_file,
			'Missing revision file at ' . $revision_file
		);
	}
}
