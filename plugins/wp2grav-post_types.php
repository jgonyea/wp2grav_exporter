<?php
/**
 * WP-CLI custom command: Exports WP post types in GravCMS format.
 * Syntax: wp wp2grav-users
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Exports WP user content as GravCMS account yaml files.
 *
 * @throws Exception
 */
function wp2grav_export_post_types() {
	WP_CLI::line( WP_CLI::colorize( '%YBeginning post types export%n ' ) );
	$plugin_dir    = plugin_dir_path( __FILE__ );
	$export_folder = WP_CONTENT_DIR . '/uploads/wp2grav-exports/' . gmdate( 'Ymd' ) . '/';

	$files_export_folder      = $export_folder . 'data/files/wp-uploads/';
	$theme_export_folder      = $export_folder . 'themes/wp-export/';
	$templates_export_folder  = $export_folder . 'themes/wp-export/templates/';
	$blueprints_export_folder = $export_folder . 'themes/wp-export/blueprints/';

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

	foreach ( $post_types as $post_type ) {
		$progress->tick();
		$blueprint_component = dirname( $plugin_dir ) . '/grav_components/contentType_blueprint.yaml';
		$blueprint           = Yaml::parseFile( $blueprint_component );
		$blueprint['title']  = $post_type;

		// Reset $new_fields for each content type.
		$new_fields = null;

		$post_type_object = get_post_type_object( $post_type );

		if ( $post_type == 'attachment' ) {
			// create custom
			continue;
		}
		$post_type_features = get_all_post_type_supports( $post_type );

		// Output post type features to blueprint yaml file for use in admin GUI.
		$yaml_output = Yaml::dump( $blueprint, 20, 4 );

		try {
			if ( ! file_put_contents( $blueprints_export_folder . $post_type . '.yaml', $yaml_output ) ) {
				throw new Exception( 'Could not save ' . $post_type . '.yaml blueprint file' );
			}
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage(), $exit = true );
		}

		$template_content = "{% extends 'partials/base.html.twig' %}\n\n{% block content %}\n    {{ page.content }}\n{% endblock %}\n";
	}
	$progress->finish();

	WP_CLI::success( 'Saved Complete!  ' . count( $post_types ) . " post types exported to $blueprints_export_folder" );
}
