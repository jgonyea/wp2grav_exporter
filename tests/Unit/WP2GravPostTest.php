<?php
/**
 * Class WP2GravPostTest
 *
 * @package wp2grav
 */

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests for `wp2grav-site` command.
 */
class WP2GravPostTest extends TestCase {

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
	 * Primary export directory for post attachments.
	 *
	 * @var string
	 */
	private $data_dir;

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

		self::generate_post();

		wp2grav_export_posts( array(), array() );
	}

	/**
	 * Creates a test text-only post for test.
	 *
	 * @return void
	 */
	private static function generate_post(): void {
		// Generate taxonomy.
		\WP2GravSiteTest::generate_custom_taxonomy();

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Inline Images.
		$inline_image_id   = self::uploadPNGImage(
			'https://lipsum.app/640x480/#.png',
			'inline-image.png',
			'Inline Image Test'
		);
		$inline_image_html = wp_get_attachment_image( $inline_image_id, 'full' );

		// Generate page.
		// todo: add taxonomy to page.
		$taxonomy_id = get_cat_ID( 'Subjects' );

		$post_text_only = array(
			'post_title'   => 'Lorem Ipsum',
			'post_content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. <br />' . $inline_image_html . '<br /> Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
			'post_status'  => 'publish',
			'post_author'  => 0,
			'post_type'    => 'post',
		);
		$post_id        = wp_insert_post( $post_text_only );
		wp_set_post_terms(
			$post_id,
			'testing post',
			'subjects',
			true
		);

		// Add featured image to post.
		$featured_image_id = self::uploadPNGImage(
			'https://lipsum.app/1024x768/#.png',
			'featured-image.png',
			'Featured Image',
			$post_id
		);

		set_post_thumbnail( $post_id, $featured_image_id );
	}


	/**
	 * Pulls an image and uploads it to the test site.
	 *
	 * @param mixed $image_url URL of image to download from the internet and upload to the test site.
	 * @param mixed $filename Name of file to be saved.
	 * @param mixed $alt_text Alternative text for the image.
	 * @param mixed $post_id ID of the post to which the image should be attached. Defaults to 0.
	 * @return int Image attachment ID.
	 */
	public static function uploadPNGImage( $image_url, $filename, $alt_text, $post_id = 0 ): int {
		$image_tmp  = download_url( $image_url );
		$image_size = filesize( $image_tmp );

		$file = array(
			'name'     => $filename,
			'type'     => 'image/png',
			'tmp_name' => $image_tmp,
			'error'    => 0,
			'size'     => $image_size,
		);

		$image_id = media_handle_sideload(
			$file,
			$post_id,
			$alt_text
		);

		// Apply alt text.
		update_post_meta(
			$image_id,
			'_wp_attachment_image_alt',
			$alt_text
		);

		// Cleanup.
		if ( file_exists( $image_tmp ) ) {
			wp_delete_file( $image_tmp );
		}

		return $image_id;
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
		$export_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$pages_dir  = $export_dir . 'pages';
		$data_dir   = $export_dir . 'data';
		$wp_filesystem->rmdir( $pages_dir, true );
		$wp_filesystem->rmdir( $data_dir, true );

		$images_upload_path = WP_CONTENT_DIR . '/uploads/' . gmdate( 'Y' );
		$wp_filesystem->rmdir( $images_upload_path, true );
	}

	/**
	 * Set up the test environment before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->export_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
		$this->pages_dir  = $this->export_dir . 'pages/';
		$this->data_dir   = $this->export_dir . 'data/wp-content';
	}

	/**
	 * Verify post attachment data directory exits.
	 *
	 * @return void
	 */
	public function testDataDirExists(): void {
		$this->assertFileExists(
			$this->data_dir,
			'Missing data directory for page images/ attachments'
		);
	}

	/**
	 * Verify pages directory exists.
	 *
	 * @return void
	 */
	public function testPagesDirExists(): void {
		$this->assertFileExists(
			$this->pages_dir,
			'Missing root directory for pages'
		);
	}

	/**
	 * Verify blog root and respective markdown file exists.
	 *
	 * @return void
	 */
	public function testBlogRootExists(): void {
		$this->assertFileExists(
			$this->pages_dir . 'blog',
			'Missing blog root directory at ' . $this->pages_dir . 'blog'
		);

		$this->assertFileExists(
			$this->pages_dir . 'blog/blog.md',
			'Missing blog.md at ' . $this->pages_dir . 'blog/blog.md'
		);
	}

	/**
	 * Verify text-only post exists.
	 *
	 * @return void
	 */
	public function testPostExists(): void {
		$this->assertFileExists(
			$this->pages_dir . 'blog/lorem-ipsum/wp_post.md',
			'Missing blog post at pages/blog/lorem-ipsum/wp_post.md'
		);
	}

	/**
	 * Verify text-only post contents.
	 *
	 * @return void
	 */
	public function testVerifyPostBodyContents(): void {
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();

		$post_path = $this->pages_dir . 'blog/lorem-ipsum/wp_post.md';
		$post      = $wp_filesystem->get_contents( $post_path );

		$grav_header_regex = '/^---([\s\S]*?)---/';

		preg_match( $grav_header_regex, $post, $matches );
		$content = str_replace( $matches[0], '', $post );
		$header  = Yaml::parse( $matches[1] );

		// Assert page header.
		$this->assertEquals(
			'Lorem Ipsum',
			$header['title'],
			'Incorrect page title'
		);

		$this->assertEquals(
			'0',
			$header['wp']['post']['author']['ID']
		);

		$this->assertEquals(
			'true',
			$header['published'],
			'Incorrect published state'
		);

		$this->assertEquals(
			gmdate( 'd-m-Y' ),
			$header['date'],
			'Incorrect published date'
		);

		$query = new WP_Query( array( 'title' => 'Lorem Ipsum' ) );
		if ( $query->have_posts() ) {
			$post = $query->posts[0];
			$this->assertEquals(
				$post->ID,
				$header['wp']['post']['ID'],
				'Incorrect Post ID'
			);
		} else {
			$this->fail( "Couldn't find text post" );
		}

		// Assert page content.
		$this->assertEquals(
			"\nLorem ipsum dolor sit amet, consectetur adipiscing elit. \n![Inline Image Test](user://data/wp-content/uploads/2025/11/inline-image.png)\n Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
			$content,
			'Incorrect page content'
		);
	}

	/**
	 * Validate that the featured image is saved to the post directory.
	 *
	 * @return void
	 */
	public function testVerifyPostFeaturedImage(): void {
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
		$post_path = $this->pages_dir . 'blog/lorem-ipsum';

		$this->assertFileExists(
			$post_path . '/featured-image.png',
			'Missing featured image'
		);
	}
}
