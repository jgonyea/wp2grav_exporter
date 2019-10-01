<?php
/**
 * Plugin Name:     Grav_export
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Exports content for use in GravCMS
 * Author:          Jeremy Gonyea
 * Author URI:      https://gonyea.io
 * Text Domain:     grav_export
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Grav_export
 */

include 'vendor/autoload.php';
use Symfony\Component\Yaml\Yaml;

// Add custom exporter plugins to ./plugins folder and then include here.
include ('plugins/grav_export_roles.php');
include ('plugins/grav_export_users.php');


function grav_export_all() {
    $progress_bar = \WP_CLI\Utils\make_progress_bar( 'Exporting Content', 4 );
    $callbacks = [
        "grav_export_roles",
        "grav_export_users",
        // grav_export_posts,
        // grav_export_post_types,
    ];

    foreach ($callbacks as $callback) {
        $progress_bar->tick();
        call_user_func($callback);
    }
}

/**
 * Converts a Wordpress role to a Grav group.
 *
 * @param string $role_name
 *   Wordpress role to be converted for Grav group name.
 *
 * @return string
 *   Grav compatible group name.
 */
function convert_wp_role_to_grav($role_name) {
    $role_name = strtolower($role_name);
    return preg_replace('/\ /', '_', $role_name);
}

/**
 * Converts a Wordpress username to Grav.
 *
 * @param string $user
 *   Wordpress username to be converted.
 *
 * @return string
 *   Grav username.
 */
function convert_wp_name_to_grav($user) {
    // Default Grav settings. Make sure to reflect changes in Grav's system.yaml.

    $user_char_mim_limit = 4;
    $user_char_max_limit = 16;

    $username = strtolower($user->user_login);

    // Replace invalid characters with underscore.
    $patterns = array(
        'space' => '/\ /',
        'period' => '/\./',
        'apostrophe' => '/\'/',
    );
    $replacements = array(
        'space' => '_',
        'period' => '_',
        'apostrophe' => '_',
    );
    $username = preg_replace($patterns, $replacements, $username);

    // Pad short usernames.
    if (strlen($username) < $user_char_mim_limit) {
    $username = $username . $user->ID;
    $username = str_pad($username, $user_char_mim_limit, "_");
    }

    // Trim long usernames.
    if (strlen($username) > $user_char_max_limit) {
    $uid_length = strlen($user->ID);
    $username = substr($username, 0, ($user_char_max_limit - $uid_length));
    $username .= $user->uid;
    }

    return $username;
}

/**
 * Saves Grav data to wp_uploads location.
 *
 * @param  string $content          File contents to write to file.
 * @param  string $filename         File to dump content to.
 * @param  string $export_folder    Dated export folder, lives in wp-content/uploads.
 * @param  string $subpath          Subfolder underneath export_folder.
 *
 * @return bool
 */
function write_file_content($content, $filename, $export_folder, $subpath){
    // Catch warnings for read-only filesystems.


    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    global $wp_filesystem;
    $upload_dir = wp_upload_dir();
    $full_export_folder = trailingslashit( $upload_dir['basedir'] )  . $export_folder;
    WP_Filesystem();
    // Make the empty folders for the exporting.
    wp_mkdir_p( $full_export_folder . $subpath);

    // Set warnings as errors.
    set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext) {
        // error was suppressed with the @-operator
        if (0 === error_reporting()) {
            return false;
        }

        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    });

    try {
        // Write file content out.
        file_put_contents($full_export_folder . $subpath . $filename , $content);
    }
    catch (exception $e) {
        WP_CLI::error("Failed to save file $filename");
    }
}

// Add all of the WP_CLI commands for the grav exporter wp-cli plugin.
if ( class_exists( 'WP_CLI' ) ) {
    WP_CLI::add_command( 'gravea', 'grav_export_all' );
    WP_CLI::add_command( 'grave_export_all', 'grav_export_all' );
    WP_CLI::add_command( 'graver', 'grav_export_roles' );
    WP_CLI::add_command( 'grav-export-roles', 'grav_export_roles' );
    WP_CLI::add_command( 'graveu', 'grav_export_users' );
    WP_CLI::add_command( 'grav-export-users', 'grav_export_users' );
}
