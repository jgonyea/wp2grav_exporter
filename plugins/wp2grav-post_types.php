<?php
/**
 * WP-CLI custom command: Exports WP post types in for use in a GravCMS theme.
 * Syntax: wp wp2grav-post_types
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Exports WP user content as GravCMS account yaml files.
 *
 * @throws Exception
 */
function wp2grav_export_post_types() {
	WP_CLI::line( WP_CLI::colorize( '%YBeginning post_types export%n ' ) );
	$export_plugins_dir = plugin_dir_path( __FILE__ );
	$export_folder      = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';

	$theme_export_folder      = 'themes/wordpress-export/';
	$templates_export_folder  = $theme_export_folder . 'templates/';
	$blueprints_export_folder = $theme_export_folder . 'blueprints/';

	if ( ! wp_mkdir_p( $export_folder ) ||
		! wp_mkdir_p( $export_folder . $templates_export_folder ) ||
		! wp_mkdir_p( $export_folder . $blueprints_export_folder )
	) {
		WP_CLI::error( 'Post Types: Could not create export folders ' );
		die();
	}

	// Write additional static theme component files.
	$theme_files                 = array(
		'blueprints.yaml',
		'CHANGELOG.md',
		'wordpress-export.php',
		'wordpress-export.yaml',
		'LICENSE',
		'README.md',
		'screenshot.jpg',
		'thumbnail.jpg',
	);
	$theme_components_files_path = dirname( $export_plugins_dir ) . '/grav_components/';
	foreach ( $theme_files as $theme_file ) {
		copy( $theme_components_files_path . $theme_file, $export_folder . $theme_export_folder . $theme_file );
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
	$progress_type = \WP_CLI\Utils\make_progress_bar( ' |-Generating post_type data', count( $post_types ), $interval = 100 );

	// Since WordPress doesn't store its metadata in a consistent manner, we'll have to do some guessing along the way.

	// Iterate through all post types.
	foreach ( $post_types as $post_type ) {
		$progress_type->tick();
		$posts = wp2grav_find_posts( $post_type );
		// $posts = get_posts();
		// Creates a new progress bar.
		$progress_posts = \WP_CLI\Utils\make_progress_bar( ' |-Parsing ' . count( $posts ) . ' posts from post_type: ' . $post_type, count( $posts ), $interval = 100 );

		$blueprint_component = dirname( $export_plugins_dir ) . '/grav_components/contentType_blueprint.yaml';
		$blueprint           = Yaml::parseFile( $blueprint_component );
		$blueprint['title']  = $post_type;

		// Reset new_fields;
		$new_fields     = null;
		$new_acf_fields = null;

		// Iterate through all posts of type post_type, in the event that there are extra metadata fields we want to capture (i.e. ACF fields).
		foreach ( $posts as $post ) {
			$progress_posts->tick();

			// Process ACF plugin meta field data.
			if ( is_plugin_active( 'advanced-custom-fields/acf.php' ) ) {
				$acf_fields = get_field_objects( $post->ID );

				if ( $acf_fields ) {
					foreach ( $acf_fields as $field_name => $acf_field ) {
						if ( $acf_field['value'] ) {
							$new_acf_fields[ 'header.wp.meta.acf.' . $field_name ] = convert_acf_field_data_to_grav_admin( $acf_field, $post );
						}
					}
				}
			}
		}

		// Find all default meta fields associated with post_type.
		$post_type_features = get_all_post_type_supports( $post_type );

		// Iterate through fields, porting the WP field to the Grav admin blueprint form fields.
		foreach ( $post_type_features as $field_type => $value ) {
			switch ( $field_type ) {
				case 'comments':
					// Do nothing for now.
					break;

				case 'revisions':
					// Do nothing for now.
					break;

				case 'image':
					$new_fields[ 'header.' . $field_name ] = array(
						'label'       => $field_name,
						'type'        => 'file',
						'help'        => strip_tags( $field['description'] ) . ' | Available file types: ' . $field['settings']['file_extensions'],
						'destination' => 'user/data/' . $field['settings']['file_directory'],
						'accept'      => array( 'image/*' ),
					);
					if ( $field_info['cardinality'] != 1 ) {
						$new_fields[ 'header.' . $field_name ]['multiple'] = true;
					} else {
						$new_fields[ 'header.' . $field_name ]['multiple'] = false;
					}

					// Hard coding this for now.
					$image_extensions = array(
						'.jpg',
						'.jpeg',
						'.png',
						'.gif',
					);

					$extensions = explode( ' ', $image_extensions );
					foreach ( $extensions as $extension ) {
						$new_fields[ 'header.' . $field_name ]['accept'][] = $extension;
					}
					break;

				default:
					// Assume a text field.
					$new_fields[ 'header.' . $field_type ] = array(
						'help'  => "Help description for $field_type",
						'label' => $field_type,
						'type'  => 'text',
					);
			}
		}

		if ( $new_fields ) {
			$blueprint['form']['fields']['tabs']['fields']['content']['fields'] = $new_fields;
		}
		if ( $new_acf_fields ) {
			$blueprint['form']['fields']['tabs']['fields']['acf']['fields'] = $new_acf_fields;
			$blueprint['form']['fields']['tabs']['fields']['acf']['type']   = 'tab';
			$blueprint['form']['fields']['tabs']['fields']['acf']['title']  = 'ACF Fields';
		}
		if ( ! $new_acf_fields && ! $new_fields ) {
			unset( $blueprint['form'] );
		}

		// Write converted post type blueprint.
		$yaml_output = Yaml::dump( $blueprint, 20, 4 );
		file_put_contents( $export_folder . $blueprints_export_folder . $post_type . '.yaml', $yaml_output );

		// Write default page template.
		$template_content = "{% extends 'partials/base.html.twig' %}\n\n{% block content %}\n    {{ page.content }}\n{% endblock %}\n";
		file_put_contents( $export_folder . $templates_export_folder . $post_type . '.html.twig', $template_content );
	}

	$progress_type->finish();

	WP_CLI::success( 'Saved Complete!  ' . count( $post_types ) . " post types exported to $blueprints_export_folder" );
}


/**
 * Converts an advanced-custom-fields to a Grav's admin form field.
 *
 * @param array   $acf_field
 *   acf field.
 * @param WP_Post $post
 *   WordPress post.
 * @return array
 *   Converted field data.
 */
function convert_acf_field_data_to_grav_admin( $acf_field, $post ) {
	$grav_field = null;
	switch ( $acf_field['type'] ) {
		case 'email':
		case 'number':
		case 'text':
		case 'textarea':
			$grav_field = array(
				'type' => $acf_field['type'],
			);
			break;

		case 'range':
			$grav_field                     = array(
				'help'  => $acf_field['instructions'],
				'label' => $acf_field['label'],
				'type'  => 'range',
			);
			$grav_field['validate']['min']  = $acf_field['min'];
			$grav_field['validate']['max']  = $acf_field['max'];
			$grav_field['validate']['step'] = $acf_field['step'];

			break;

		// default:
			// $grav_field['error'] = 'Missing field definition: ' . $acf_field['type'];
			// $grav_field['debug']  = $acf_field;

	}

	// Generic field options.
	if ( $acf_field['required'] == '1' ) {
		$grav_field['validate']['required'] = true;
	} else {
		$grav_field['validate']['required'] = false;
	}

	if ( isset( $acf_field['instructions'] ) ) {
		$grav_field['help'] = $acf_field['instructions'];
	}

	if ( isset( $acf_field['label'] ) ) {
		$grav_field['label'] = $acf_field['label'];
	}

	if ( isset( $acf_field['default_value'] ) ) {
		$grav_field['default'] = $acf_field['default_value'];
	}

	return $grav_field;
}
