<?php
/**
 * WP-CLI custom command: Exports WP roles in GravCMS format.
 * Syntax: wp wp2grav-roles
 */

use Symfony\Component\Yaml\Yaml;


function wp2grav_export_roles ( $args, $assoc_args ) {
    WP_CLI::line( WP_CLI::colorize( "%YBeginning role export%n " ) );
    $export_folder = WP_CONTENT_DIR . '/uploads/wp2grav-exports/' . date('Ymd') . "/config/";


    $roles = get_editable_roles();
    $groups = [];
    $progress = \WP_CLI\Utils\make_progress_bar( 'Generating roles data', count($roles), $interval = 100 );
    foreach ($roles as $key => $role) {

        

        if ( !wp_mkdir_p($export_folder) ) {
          WP_CLI::error( "Could not create export folder" );
          die();
        }

        //WP_CLI::line( " |- Exporting role '" . $role["name"] . "' (" . $key. ")" );

        $role_name = "wp_" . convert_role_wp_to_grav($key);
        $groups[$role_name]['icon'] = "cog";
        $groups[$role_name]['readableName'] = convert_role_wp_to_grav($role["name"]);
        $groups[$role_name]['description'] = "Exported Wordpress role " . convert_role_wp_to_grav($key);
        $groups[$role_name]['access']['site']['login'] = TRUE;

        // Grant administrator role further Grav Admin access.
        if ($key == 'administrator') {
          $groups[$role_name]['access']['admin'] = array(
            "login" => TRUE,
            "super" => TRUE,
          );
        }
        $progress->tick();
    }

    // Create new role of authenticated user that will grant basic admin login to all exported users.
    if ( !array_key_exists( 'wp_authenticated_user', $groups ) ) {
      $groups['wp_authenticated_user']['icon'] = "cog";
      $groups['wp_authenticated_user']['readableName'] = convert_role_wp_to_grav($role["name"]);
      $groups['wp_authenticated_user']['description'] = "Exported Wordpress role " . convert_role_wp_to_grav($key);
      $groups['wp_authenticated_user']['access']['site']['login'] = TRUE;
    }

    // Finish the progress bar.
    $progress->finish();



    WP_CLI::line( "Saving role export data to $export_folder/groups.yaml" );
    $group_content = Yaml::dump($groups, 20, 4);
    
    try {
      if ( !file_put_contents( $export_folder . '/groups.yaml', $group_content) ) {
        throw new Exception( 'Could not save groups.yaml export file' );
      }
      
    }
    catch (Exception $e) {
      WP_CLI::error( $e->getMessage(), $exit = TRUE );
    }
    WP_CLI::success( (count($roles) + 1) . " roles exported" );
}