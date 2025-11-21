<?php declare(strict_types=1);
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
	 * A single example test.
	 */
	    public function testConvertUsername_John_Doe() {
        // Create a mock WP_User object for testing

        $args = array(
            "user_login" => "John Doe"
        );
        $user = $this->factory->user->create_and_get($args);

        // Test basic conversion
        $this->assertEquals('john_doe', convert_username_wp_to_grav($user));
    }

    public function testConvertUsername_John__Doe() {
        // Create a mock WP_User object for testing

        $args = array(
            "user_login" => "John_Doe"
        );
        $user = $this->factory->user->create_and_get($args);

        // Test basic conversion
        $this->assertEquals('john_doe', convert_username_wp_to_grav($user));
    }

    public function testConvertUsername_JohnsDoe() {
        // Create a mock WP_User object for testing

        $args = array(
            "user_login" => "John's Doe"
        );
        $user = $this->factory->user->create_and_get($args);

        // Test basic conversion
        $this->assertEquals('johns_doe', convert_username_wp_to_grav($user));
    }

    public function testConvertUsername_JohndotDoe() {
        // Create a mock WP_User object for testing

        $args = array(
            "user_login" => "John.Doe"
        );
        $user = $this->factory->user->create_and_get($args);

        // Test basic conversion
        $this->assertEquals('john_doe', convert_username_wp_to_grav($user));
    }

    public function testConvertUsername_Jo() {
        // Create a mock WP_User object for testing

        $args = array(
            "user_login" => "Jo"
        );
        $user = $this->factory->user->create_and_get($args);
        $id = $user->ID;

        // Test basic conversion
        $this->assertEquals('jo' . $id . '_', convert_username_wp_to_grav($user));
    }

    public function testConvertUsername_Joj() {
        // Create a mock WP_User object for testing

        $args = array(
            "user_login" => "Joj"
        );
        $user = $this->factory->user->create_and_get($args);
        $id = $user->ID;

        // Test basic conversion
        $this->assertEquals('joj' . $id . '__', convert_username_wp_to_grav($user, 6, 16));
    }

    public function testConvertUsername_JohnJacobJingleheimerSchmidt() {
        // Create a mock WP_User object for testing

        $args = array(
            "user_login" => "John Jacob Jingleheimer Schmidt"
        );
        $user = $this->factory->user->create_and_get($args);
        $id = $user->ID;

        // Test basic conversion
        $this->assertEquals('john_jacob_jing' . $id, convert_username_wp_to_grav($user));
    }



}
