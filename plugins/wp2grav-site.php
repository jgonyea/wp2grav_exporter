<?php
/**
 * WP-CLI custom command: Exports WP site information for use in a GravCMS site.
 * Syntax: wp wp2grav-site
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Exports WP user content as GravCMS account yaml files.
 *
 * @throws Exception
 */
function wp2grav_export_site() {
	WP_CLI::line( WP_CLI::colorize( '%YBeginning site.yaml export%n ' ) );
	$export_plugins_dir  = plugin_dir_path( __FILE__ );
	$export_dir          = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
	$config_export_dir = $export_dir . 'config/';

  if (
		! wp_mkdir_p( $export_dir ) ||
		! wp_mkdir_p( $config_export_dir )
	) {
		WP_CLI::error( 'Error generating site.yaml: Could not create export folders ' );
		die();
	}

  $author_email = get_bloginfo('admin_email');
  $user = get_user_by('email', $author_email );
  if ($user) {
    $author_name = get_user_meta(1)['nickname'][0];
  } else {
    $author_name = "Site Admin";
  }
  $site_info = array(
    'title' => get_bloginfo( 'name' ),
    'author' => array(
      'name' => $author_name,
      'email' => $author_email
    ),
    'metadata' => array (
      'description' => get_bloginfo('description')
    )
  );

  $site_yaml = Yaml::dump( $site_info, 20, 2 );
  file_put_contents ( $config_export_dir . 'site.yaml', $site_yaml );

  WP_CLI::success( 'config/site.yaml export complete!' );
}
