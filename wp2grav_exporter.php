<?php
/*
Plugin Name: Grav Export
Plugin URI:  https://www.github.com/jgonyea/wp2grav_exporter
Description: This plugin converts WP content for use in a GravCMS instance.
Version:     0.1.1
Author:      Jeremy Gonyea
Author URI:  https://www.gonyea.io
License:     MIT
License URI: https://mit-license.org/

*/

require 'vendor/autoload.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	$plugin_dir = plugin_dir_path( __FILE__ );
	// Load plugins.
	require_once $plugin_dir . 'plugins/wp2grav-all.php';
	require_once $plugin_dir . 'plugins/wp2grav-posts.php';
	require_once $plugin_dir . 'plugins/wp2grav-post_types.php';
	require_once $plugin_dir . 'plugins/wp2grav-roles.php';
	require_once $plugin_dir . 'plugins/wp2grav-users.php';

	// Register commands with wp-cli.
	WP_CLI::add_command( 'wp2grav-all', 'wp2grav_export_all' );
	WP_CLI::add_command( 'wp2grav-posts', 'wp2grav_export_posts' );
	WP_CLI::add_command( 'wp2grav-post-types', 'wp2grav_export_post_types' );
	WP_CLI::add_command( 'wp2grav-roles', 'wp2grav_export_roles' );
	WP_CLI::add_command( 'wp2grav-users', 'wp2grav_export_users' );
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
 * @return string Grav username.
 */
function convert_username_wp_to_grav( $user ) {
	// Default Grav settings. Make sure to reflect changes in Grav's system.yaml.
	$user_char_mim_limit = 4;
	$user_char_max_limit = 16;

	$username = $user->get( 'user_login' );

	mb_strtolower( $username, 'UTF-8' );

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
	if ( strlen( $username ) < $user_char_mim_limit ) {
		$username = $username . $user->uid;
		$username = str_pad( $username, $user_char_mim_limit, '_' );
	}

	// Trim long usernames.
	if ( strlen( $username ) > $user_char_max_limit ) {
		$uid_length = strlen( $user->uid );
		$username   = substr( $username, 0, ( $user_char_max_limit - $uid_length ) );
		$username  .= $user->uid;
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
function wp2grav_find_posts( $type = 'post' ) {
	$posts = get_posts(
		array(
			'post_type'   => $type,
			'numberposts' => -1,
			'post_status' => get_post_stati(),
		)
	);

	return $posts;
}
