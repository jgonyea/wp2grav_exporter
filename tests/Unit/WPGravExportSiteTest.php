<?php declare(strict_types=1);
/**
 * Class WPGravExportSiteTest
 *
 * @package Wp2grav_exporter
 */

use PHPUnit\Framework\OutputError;

/**
 * Username conversion test case.
 */
class WPGravExportSiteTest extends WP_UnitTestCase {


    /**
     * Test the 'wp my-command greet' command with a specific name.
     */
    public function test_greet_with_name() {
        // We use ob_start() and ob_get_clean() to capture the output of the command
        // todo: remove ob_start and load YAML file after the fact.
        ob_start();

        // todo: Fix this hardcoded path.
        $plugin_dir = '/var/www/html/wp-content/plugins/wp2grav_exporter/plugins';
        // Load plugins.

        $files = glob( $plugin_dir . '/wp2grav-*.php' );

        foreach ( $files as $file ) {
            // PHP require source file.
            require_once $file;

        }

        update_option( 'blogname', 'WP PHPUnit Test Site');

        wp2grav_export_site();


        //$command->greet( [], [ 'name' => 'TestUser' ] );

        $output = ob_get_clean();

        // The expected output will include "Success: " prefix used by WP_CLI::success()
        $expected_output = "Success: Hello, TestUser!\n";

        // Assert that the actual output matches the expected output
        $this->assertEquals( $expected_output, $output );
    }



}
