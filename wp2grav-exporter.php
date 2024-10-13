<?php
/*
Plugin Name: Grav Export
Plugin URI:  https://www.github.com/jgonyea/wp2grav_exporter
Description: This plugin converts WP content for use in a GravCMS instance.
Version:     0.2.2
Author:      Jeremy Gonyea
Author URI:  https://www.gonyea.io
License:     MIT
License URI: https://mit-license.org/

*/

require 'vendor/autoload.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	$plugin_dir = plugin_dir_path( __FILE__ ) . 'plugins';
	// Load plugins.

	$files = glob( $plugin_dir . '/wp2grav-*.php' );

	foreach ( $files as $file ) {
		// PHP require source file.
		require_once $file;

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
