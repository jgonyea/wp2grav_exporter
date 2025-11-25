<?php
/**
 * Class WPGravFindPostsTest
 *
 * @package Wp2grav_exporter
 */

use PHPUnit\Framework\OutputError;

/**
 * Tests finding all types of posts, not just published.
 */
class WPGravFindPostsTest extends WP_UnitTestCase {

	/**
	 * Tests finding all types of posts, not just published.
	 *
	 * @return void
	 */
	public function testFindAllPosts() {

		$published_args = array(
			'post_title'  => 'Test Published Post',
			'post_status' => 'publish',
			'post_type'   => 'post',
		);

		$unpublished_args = array(
			'post_title'  => 'Test Draft Post',
			'post_status' => 'draft',
			'post_type'   => 'post',
		);

		$future_date = gmdate( 'Y-m-d', strtotime( '+1 year' ) );
		$future_args = array(
			'post_title'    => 'Test Future Post',
			'post_status'   => 'future',
			'post_type'     => 'post',
			'post_date'     => $future_date,
			'post_date_gmt' => get_gmt_from_date( $future_date ),
		);

		$published_post   = $this->factory->post->create_and_get( $published_args );
		$unpublished_post = $this->factory->post->create_and_get( $unpublished_args );
		$future_post      = $this->factory->post->create_and_get( $future_args );

		$posts = wp2grav_find_posts();
		$this->assertEquals( 3, count( $posts ) );
	}
}
