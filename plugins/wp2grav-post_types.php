<?php
/**
 * WP-CLI custom command: Exports WP post types in GravCMS format.
 * Syntax: wp wp2grav-post_types
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Exports WP user content as GravCMS account yaml files.
 *
 * @throws Exception
 */
function wp2grav_export_post_types() {
	WP_CLI::line( WP_CLI::colorize( '%YBeginning post types export%n ' ) );
	$export_plugins_dir    = plugin_dir_path( __FILE__ );
	$export_folder = WP_CONTENT_DIR . '/uploads/wp2grav-exports/' . gmdate( 'Ymd' ) . '/';

	$files_export_folder      = $export_folder . 'data/wp-content/uploads/';
	$theme_export_folder      = $export_folder . 'themes/wordpress-export/';
	$templates_export_folder  = $export_folder . 'themes/wordpress-export/templates/';
	$blueprints_export_folder = $export_folder . 'themes/wordpress-export/blueprints/';

	if ( ! wp_mkdir_p( $export_folder ) ||
		! wp_mkdir_p( $files_export_folder ) ||
		! wp_mkdir_p( $theme_export_folder ) ||
		! wp_mkdir_p( $blueprints_export_folder )
	) {
		WP_CLI::error( 'Post Types: Could not create export folders ' );
		die();
	}

	// Find all custom post_types.
	$args = array(
		'public' => true,
	);

	$post_types = get_post_types( $args );
	unset( $post_types['attachment'] );

	if ( ! $post_types ) {
		WP_CLI::error( 'No post types found.  Stopping export', $exit = true );
	}

	// Creates a new progress bar.
	$progress = \WP_CLI\Utils\make_progress_bar( 'Generating post_type data', count( $post_types ), $interval = 100 );

	// Iterate through all post types.
	foreach ( $post_types as $post_type ) {
		$progress->tick();
		$blueprint_component = dirname( $export_plugins_dir ) . '/grav_components/contentType_blueprint.yaml';
		$blueprint           = Yaml::parseFile( $blueprint_component );
		$blueprint['title']  = $post_type;

		// Find all fields associated with post_type.
		$post_type_features = get_all_post_type_supports ( $post_type );

		// Reset new_fields;
		$new_fields = null;

		// Iterate through fields, porting the WP field to the Grav admin blueprint form fields.
		foreach ( $post_type_features as $field_type => $value ) {
			switch ( $field_type ){
				
				case ('comments'):
					// Do nothing for now.
				break;
				
				case ('revisions'):
					// Do nothing for now.
				break;

				case "image":
					$new_fields["header." . $field_name] = array(
					  "label" => $field_name,
					  "type" => "file",
					  "help" => strip_tags($field['description']) . " | Available file types: " . $field['settings']['file_extensions'],
					  "destination" => "user/data/" . $field['settings']['file_directory'],
					  "accept" => array('image/*'),
					);
					if ($field_info['cardinality'] != 1) {
					  $new_fields["header." . $field_name]['multiple'] = TRUE;
					}
					else {
					  $new_fields["header." . $field_name]['multiple'] = FALSE;
					}
					
					// Hard coding this for now.
					$image_extensions = [
						".jpg",
						".jpeg",
						".png",
						".gif",
					];

					$extensions = explode(" ", $image_extensions);
					foreach ($extensions as $extension) {
					  $new_fields["header." . $field_name]["accept"][] = $extension;
					}
					break;

				default:
					// Assume a text field.
					$new_fields["header." . $field_type] = [
						"help" => "Help description for $field_type",
						"label" => $field_type,
						"type" => "text"
					];
			}
		}

		if ($new_fields) {
			$blueprint['form']['fields']['tabs']['fields']['content']['fields'] = $new_fields;
		  }
		  else {
			unset($blueprint['form']);
		  }

		// Write converted post type blueprint to disk.
		$yaml_output = Yaml::dump($blueprint, 20, 4);
		file_put_contents( $blueprints_export_folder . "wp-" . $post_type . ".yaml", $yaml_output );

	}

	// Write additional static theme component files.
	$theme_files = array(
		'blueprints.yaml',
		'CHANGELOG.md',
		'wordpress-export.php',
		'wordpress-export.yaml',
		'LICENSE',
		'README.md',
		'screenshot.jpg',
		'thumbnail.jpg',
	  );
	  $theme_components_files_path = dirname( $export_plugins_dir ) . "/grav_components/";
	  foreach ($theme_files as $theme_file) {
		copy($theme_components_files_path . $theme_file, $theme_export_folder . $theme_file);
	  }

	$progress->finish();

	WP_CLI::success( 'Saved Complete!  ' . count( $post_types ) . " post types exported to $blueprints_export_folder" );
}
