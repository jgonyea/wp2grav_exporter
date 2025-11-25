<?php
/**
 * Class ConvertUsernamesTest
 *
 * @package Wp2grav_exporter
 */

use PHPUnit\Framework\OutputError;

/**
 * Username conversion test case.
 */
class ConvertUsernamesTest extends WP_UnitTestCase {

	/**
	 * Convert a basic username.
	 *
	 * @return void
	 */
	public function testConvertUsername_John_Doe() {
		// Mock test user.

		$args = array(
			'user_login' => 'John Doe',
		);
		$user = $this->factory->user->create_and_get( $args );

		$this->assertEquals( 'john_doe', convert_username_wp_to_grav( $user ) );
	}

	/**
	 * Convert hyphenated username.
	 *
	 * @return void
	 */
	public function testConvertUsername_John__Doe() {
		// Mock test user.

		$args = array(
			'user_login' => 'John-Doe',
		);
		$user = $this->factory->user->create_and_get( $args );

		$this->assertEquals( 'john-doe', convert_username_wp_to_grav( $user ) );
	}

	/**
	 * Convert username with apostrophe.
	 *
	 * @return void
	 */
	public function testConvertUsername_JohnsDoe() {
		// Mock test user.

		$args = array(
			'user_login' => "John's Doe",
		);
		$user = $this->factory->user->create_and_get( $args );

		$this->assertEquals( 'johns_doe', convert_username_wp_to_grav( $user ) );
	}

	/**
	 * Convert username with a period.
	 *
	 * @return void
	 */
	public function testConvertUsername_JohndotDoe() {
		// Mock test user.

		$args = array(
			'user_login' => 'John.Doe',
		);
		$user = $this->factory->user->create_and_get( $args );

		$this->assertEquals( 'john_doe', convert_username_wp_to_grav( $user ) );
	}

	/**
	 * Convert extra-short username.
	 *
	 * @return void
	 */
	public function testConvertUsername_Jo() {
		// Mock test user.

		$args = array(
			'user_login' => 'Jo',
		);
		$user = $this->factory->user->create_and_get( $args );
		$id   = $user->ID;

		$this->assertEquals( 'jo' . $id . '_', convert_username_wp_to_grav( $user ) );
	}

	/**
	 * Convert short username.
	 *
	 * @return void
	 */
	public function testConvertUsername_Joj() {
		// Mock test user.

		$args = array(
			'user_login' => 'Joj',
		);
		$user = $this->factory->user->create_and_get( $args );
		$id   = $user->ID;

		// Test basic conversion.
		$this->assertEquals( 'joj' . $id . '__', convert_username_wp_to_grav( $user, 6, 16 ) );
	}

	/**
	 * Convert long username.
	 *
	 * @return void
	 */
	public function testConvertUsername_JohnJacobJingleheimerSchmidt() {
		// Mock test user.

		$args = array(
			'user_login' => 'John Jacob Jingleheimer Schmidt',
		);
		$user = $this->factory->user->create_and_get( $args );
		$id   = $user->ID;

		$this->assertEquals( 'john_jacob_jing' . $id, convert_username_wp_to_grav( $user ) );
	}
}
