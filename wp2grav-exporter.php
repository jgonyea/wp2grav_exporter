<?php
/**
 * Plugin Name: WP2Grav Exporter
 * Plugin URI:  https://www.github.com/jgonyea/wp2grav_exporter
 * Description: Converts WordPress content for use in a GravCMS instance.
 * Version:     0.4.2
 * Author:      Jeremy Gonyea
 * Author URI:  https://www.gonyea.io
 * License:     MIT
 * License URI: https://mit-license.org/
 *
 * @package wp2grav
 */

/**
 * Loads composer dependencies.
 */
require 'vendor/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'classes/class-wp2grav-noop-progress.php';

// Custom plugin actions.
add_action( 'admin_menu', 'wp2grav_admin_menu' );
add_action( 'admin_enqueue_scripts', 'wp2grav_enqueue_admin_assets' );
add_action( 'wp_ajax_wp2grav_run_export', 'wp2grav_ajax_run_export' );
add_action( 'wp_ajax_wp2grav_delete_export', 'wp2grav_ajax_delete_export' );

// Load plugin files for both CLI and admin contexts.
$plugin_dir = plugin_dir_path( __FILE__ ) . 'plugins';
$files      = glob( $plugin_dir . '/wp2grav-*.php' );

foreach ( $files as $file ) {
	require_once $file;
}

// Register WP-CLI commands when in CLI context.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	foreach ( $files as $file ) {
		// Derive expected function names from filenames.
		$plugin_name          = substr( $file, strlen( $plugin_dir ) + 1 );
		$plugin_name          = substr( $plugin_name, 0, -4 );
		$plugin_name_exploded = explode( '-', $plugin_name );
		array_shift( $plugin_name_exploded );
		$plugin_name_imploded = implode( '_', $plugin_name_exploded );

		// Register commands with wp-cli.
		WP_CLI::add_command( $plugin_name, 'wp2grav_export_' . $plugin_name_imploded );
	}
}


/**
 * Enqueues CSS and JS assets for the WP2Grav admin page.
 *
 * Loads only on the plugin's own admin page and passes the security nonce
 * to JavaScript via wp_localize_script.
 *
 * @param string $hook The current admin page hook suffix.
 */
function wp2grav_enqueue_admin_assets( $hook ) {
	if ( 'tools_page_wp2grav-exporter' !== $hook ) {
		return;
	}
	$plugin_url = plugin_dir_url( __FILE__ );
	wp_enqueue_style( 'wp2grav-admin', $plugin_url . 'assets/admin/admin.css', array(), '1.0.0' );
	wp_enqueue_script( 'wp2grav-admin', $plugin_url . 'assets/admin/admin.js', array(), '1.0.0', true );
	wp_localize_script(
		'wp2grav-admin',
		'wp2gravAdmin',
		array(
			'nonce' => wp_create_nonce( 'wp2grav_export_nonce' ),
		)
	);
}

/**
 * Registers the WP2Grav Exporter submenu page under the Tools menu.
 */
function wp2grav_admin_menu() {
	add_submenu_page(
		'tools.php',
		'WP2Grav Exporter Main Page',
		'WP2Grav Exporter',
		'manage_options',
		'wp2grav-exporter',
		'wp2grav_admin_page_callback',
		15
	);
}

/**
 * Renders the WP2Grav Exporter admin page.
 *
 * Outputs the export directory, exporter buttons, and a results
 * area that is populated via AJAX after each export action.
 */
function wp2grav_admin_page_callback() {
	$export_dir = get_export_dir();
	include plugin_dir_path( __FILE__ ) . 'assets/admin/admin-page.php';
}

/**
 * Handles the AJAX request to run a named exporter.
 *
 * Validates the nonce and user capability, dispatches to the appropriate
 * export function, and returns a JSON success or error response.
 */
function wp2grav_ajax_run_export() {
	check_ajax_referer( 'wp2grav_export_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Permission denied.' ) );
	}

	$exporter = isset( $_POST['exporter'] ) ? sanitize_text_field( wp_unslash( $_POST['exporter'] ) ) : '';

	$exporters = array(
		'roles'      => 'wp2grav_export_roles',
		'users'      => 'wp2grav_export_users',
		'posts'      => 'wp2grav_export_posts',
		'post_types' => 'wp2grav_export_post_types',
		'site'       => 'wp2grav_export_site',
		'all'        => 'wp2grav_export_all',
	);

	if ( ! isset( $exporters[ $exporter ] ) ) {
		wp_send_json_error( array( 'message' => 'Invalid exporter: ' . $exporter ) );
	}

	try {
		call_user_func( $exporters[ $exporter ] );
	} catch ( Exception $e ) {
		wp_send_json_error( array( 'message' => $e->getMessage() ) );
		return;
	}
	wp_send_json_success( array( 'message' => ucfirst( str_replace( '_', ' ', $exporter ) ) . " export(s) completed successfully!\nOutput saved to: " . get_export_dir() ) );
}

/**
 * Handles the AJAX request to delete a previous export directory.
 *
 * Validates the nonce, user capability, and folder name before
 * recursively removing the directory.
 */
function wp2grav_ajax_delete_export() {
	check_ajax_referer( 'wp2grav_export_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Permission denied.' ) );
	}

	$folder = isset( $_POST['folder'] ) ? sanitize_text_field( wp_unslash( $_POST['folder'] ) ) : '';

	// Only allow the known pattern to prevent path traversal.
	if ( ! preg_match( '/^user-\d{8}$/', $folder ) ) {
		wp_send_json_error( array( 'message' => 'Invalid folder name.' ) );
	}

	$target = WP_CONTENT_DIR . '/uploads/wp2grav-exports/' . $folder;

	if ( ! is_dir( $target ) ) {
		wp_send_json_error( array( 'message' => 'Directory not found.' ) );
	}

	if ( wp2grav_rmdir_recursive( $target ) ) {
		wp_send_json_success( array( 'message' => 'Deleted export: ' . $folder ) );
	} else {
		wp_send_json_error( array( 'message' => 'Failed to delete: ' . $folder ) );
	}
}

/**
 * Recursively deletes a directory and all its contents.
 *
 * @param string $dir Absolute path to the directory to remove.
 * @return bool True on success, false on failure.
 */
function wp2grav_rmdir_recursive( $dir ) {
	$items = array_diff( scandir( $dir ), array( '.', '..' ) );
	foreach ( $items as $item ) {
		$path = $dir . '/' . $item;
		if ( is_dir( $path ) ) {
			wp2grav_rmdir_recursive( $path );
		} else {
			wp_delete_file( $path );
		}
	}

	$action = $GLOBALS['wp_filesystem']->rmdir( $dir );
	return $action;
}

/**
 * Converts a WordPress role name to a valid Grav group name.
 *
 * @param string $role_name WordPress role to be converted for Grav group name.
 * @return string Grav compatible group name.
 */
function convert_role_wp_to_grav( $role_name ) {
	return preg_replace( '/\ /', '_', $role_name );
}

/**
 * Converts a WordPress username to Grav.
 *
 * @param WP_USER $user WordPress user to be converted.
 * @param int     $user_char_min_limit Minimum username character limit.
 * @param int     $user_char_max_limit Maximum username character limit.
 * @return string Grav username.
 */
function convert_username_wp_to_grav( $user, $user_char_min_limit = 4, $user_char_max_limit = 16 ) {

	// Default Grav settings are 4 to 16. Make sure to reflect changes in Grav's system.yaml.

	$username = $user->user_login;

	$username = mb_strtolower( $username, 'UTF-8' );

	// Replace invalid characters with underscore.
	$patterns     = array(
		'space'      => '/\ /',
		'period'     => '/\./',
		'apostrophe' => '/\'/',
	);
	$replacements = array(
		'space'      => '_',
		'period'     => '_',
		'apostrophe' => '_',
	);
	$username     = preg_replace( $patterns, $replacements, $username );

	// Pad short usernames.
	if ( strlen( $username ) < $user_char_min_limit ) {
		$username .= $user->ID;
		$username  = str_pad( $username, $user_char_min_limit, '_' );
	}

	// Trim long usernames.
	if ( strlen( $username ) > $user_char_max_limit ) {
		$uid_length = strlen( $user->ID );
		$username   = substr( $username, 0, ( $user_char_max_limit - $uid_length ) );
		$username  .= $user->ID;
	}

	return $username;
}

/**
 * Finds all posts of a post_type.
 *  This is used to find all posts of a certain post type.  The built-in function of get_post doesn't find drafts/ scheduled.
 *
 * @param string $type Specific post type to search for.
 * @return array WP posts search results.
 */
function wp2grav_find_posts_of_type( $type = 'post' ) {
	$stati = get_post_stati();
	// Don't export auto-drafts.
	unset( $stati['auto-draft'] );
	$posts = get_posts(
		array(
			'post_type'   => $type,
			'numberposts' => -1,
			'post_status' => $stati,
		)
	);

	return $posts;
}

/**
 * Get the default export directory.
 *
 * @return string Export directory.
 */
function get_export_dir() {
	return WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
}
