<?php
/**
 * WP-CLI custom command: Exports WP site information for use in a GravCMS site.
 * Syntax: wp wp2grav-site
 *
 * @package wp2grav
 */

use Symfony\Component\Yaml\Yaml;

// Prepare to save content.
if ( ! isset( $wp_filesystem ) ) {
	require_once ABSPATH . '/wp-admin/includes/file.php';
	WP_Filesystem();
}

/**
 * Exports WP user content as GravCMS account yaml files.
 *
 * @throws Exception Error if export folder unwriteable.
 */
function wp2grav_export_site() {
	global $wp_filesystem;
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::line( WP_CLI::colorize( '%YBeginning site.yaml export%n ' ) );
	}

	$export_plugins_dir = plugin_dir_path( __FILE__ );
	$export_dir         = get_export_dir();
	$config_export_dir  = $export_dir . 'config/';

	if (
		! wp_mkdir_p( $export_dir ) ||
		! wp_mkdir_p( $config_export_dir )
	) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::error( 'Error generating site.yaml: Could not create export folders' );
		}
		throw new Exception( 'Error generating site.yaml: Could not create export folders' );
	}

	$author_email = get_bloginfo( 'admin_email' );
	$user         = get_user_by( 'email', $author_email );
	if ( $user ) {
		$author_name = get_user_meta( 1, 'nickname', true );
	} else {
		$author_name = 'Site Admin';
	}
	$site_info = array(
		'title'    => get_bloginfo( 'name' ),
		'author'   => array(
			'name'  => $author_name,
			'email' => $author_email,
		),
		'metadata' => array(
			'description' => get_bloginfo( 'description' ),
		),
	);

	// Grav stores taxonomies in the site.yaml.
	$grav_taxonomies = array();
	$taxonomies      = get_taxonomies( array(), 'objects' );
	foreach ( $taxonomies as $taxonomy ) {
		if ( $taxonomy->public ) {
			$grav_taxonomies[] = $taxonomy->name;
		}
	}
	if ( $grav_taxonomies ) {
		$site_info['taxonomies'] = $grav_taxonomies;
	} else {
		// Add default Grav taxonomies.
		$grav_taxonomies = array(
			'category',
			'tag',
		);
	}

	$site_yaml = Yaml::dump( $site_info, 20, 2 );
	$wp_filesystem->put_contents( $config_export_dir . 'site.yaml', $site_yaml );

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::success( 'config/site.yaml export complete!' );
	}
}
