<?php
/**
 * Class WP2GravPostCommentsTest
 *
 * @package wp2grav
 */

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests for `wp2grav-posts` command on a post that has comments.
 */
class WP2GravPostCommentsTest extends TestCase {

	/**
	 * ID of the test post.
	 *
	 * @var int
	 */
	private static $post_id;

	/**
	 * ID of the approved comment.
	 *
	 * @var int
	 */
	private static $approved_comment_id;

	/**
	 * ID of the pending comment.
	 *
	 * @var int
	 */
	private static $pending_comment_id;

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

		self::generate_post_with_comments();

		wp2grav_export_posts( array(), array() );
	}

	/**
	 * Creates a published post and adds an approved and a pending comment to it.
	 *
	 * @return void
	 */
	private static function generate_post_with_comments(): void {
		// Create the post with comments open.
		$post_id       = wp_insert_post(
			array(
				'post_title'     => 'Comments Test Post',
				'post_content'   => 'Post content for comment testing.',
				'post_status'    => 'publish',
				'post_type'      => 'post',
				'comment_status' => 'open',
			)
		);
		self::$post_id = $post_id;

		// Add an approved comment.
		self::$approved_comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => 'Alice Commenter',
				'comment_author_email' => 'alice@example.com',
				'comment_content'      => 'This is an approved comment.',
				'comment_approved'     => 1,
			)
		);

		// Add a pending comment.
		self::$pending_comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => 'Bob Commenter',
				'comment_author_email' => 'bob@example.com',
				'comment_content'      => 'This is a pending comment.',
				'comment_approved'     => 0,
			)
		);
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
	 * Verify that the comments.yaml file was created alongside the post.
	 *
	 * @return void
	 */
	public function testCommentsFileExists(): void {
		$post          = get_post( self::$post_id );
		$comments_file = $this->pages_dir . 'blog/' . $post->post_name . '/comments.yaml';

		$this->assertFileExists(
			$comments_file,
			'Missing comments file at ' . $comments_file
		);
	}

	/**
	 * Verify the comments.yaml contains both the approved and pending comments.
	 *
	 * @return void
	 */
	public function testCommentsFileContents(): void {
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();

		$post          = get_post( self::$post_id );
		$comments_file = $this->pages_dir . 'blog/' . $post->post_name . '/comments.yaml';
		$raw           = $wp_filesystem->get_contents( $comments_file );
		$data          = Yaml::parse( $raw );

		$this->assertArrayHasKey( 'comments', $data, 'Missing top-level comments key in comments.yaml' );

		// Index comments by their id for reliable lookup.
		$indexed = array();
		foreach ( $data['comments'] as $comment ) {
			$indexed[ $comment['id'] ] = $comment;
		}

		// Assert the approved comment.
		$approved_key = 'wp-' . self::$approved_comment_id;
		$this->assertArrayHasKey( $approved_key, $indexed, 'Approved comment not found in comments.yaml' );
		$this->assertEquals( 'Alice Commenter', $indexed[ $approved_key ]['author'], 'Incorrect author for approved comment' );
		$this->assertEquals( 'alice@example.com', $indexed[ $approved_key ]['email'], 'Incorrect email for approved comment' );
		$this->assertEquals( 'This is an approved comment.', $indexed[ $approved_key ]['text'], 'Incorrect text for approved comment' );
		$this->assertEquals( 'published', $indexed[ $approved_key ]['status'], 'Approved comment should have status published' );

		// Assert the pending comment.
		$pending_key = 'wp-' . self::$pending_comment_id;
		$this->assertArrayHasKey( $pending_key, $indexed, 'Pending comment not found in comments.yaml' );
		$this->assertEquals( 'Bob Commenter', $indexed[ $pending_key ]['author'], 'Incorrect author for pending comment' );
		$this->assertEquals( 'bob@example.com', $indexed[ $pending_key ]['email'], 'Incorrect email for pending comment' );
		$this->assertEquals( 'This is a pending comment.', $indexed[ $pending_key ]['text'], 'Incorrect text for pending comment' );
		$this->assertEquals( 'pending', $indexed[ $pending_key ]['status'], 'Pending comment should have status pending' );
	}
}
