<?php
/**
 * WP-CLI custom command: Exports WP post types in for use in a GravCMS plugin.
 * Syntax: wp wp2grav-post_types
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
function wp2grav_export_post_types() {
	WP_CLI::line( WP_CLI::colorize( '%YBeginning post_types export%n ' ) );
	$export_plugins_dir = plugin_dir_path( __FILE__ );
	$export_folder      = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';

	$plugin_export_folder     = 'plugins/wordpress-exporter-helper/';
	$templates_export_folder  = $plugin_export_folder . 'templates/';
	$blueprints_export_folder = $plugin_export_folder . 'blueprints/';

	if ( ! wp_mkdir_p( $export_folder ) ||
		! wp_mkdir_p( $export_folder . $templates_export_folder ) ||
		! wp_mkdir_p( $export_folder . $blueprints_export_folder )
	) {
		WP_CLI::error( 'Post Types: Could not create export folders ' );
		die();
	}

	// Write additional static plugin component files.
	$plugin_files                 = array(
		'blueprints.yaml',
		'CHANGELOG.md',
		'wordpress-exporter-helper.php',
		'wordpress-exporter-helper.yaml',
		'composer.json',
		'composer.lock',
		'LICENSE',
		'README.md',
		'screenshot.jpg',
		'thumbnail.jpg',
	);
	$plugin_components_files_path = dirname( $export_plugins_dir ) . '/grav_components/';
	foreach ( $plugin_files as $plugin_file ) {
		copy( $plugin_components_files_path . $plugin_file, $export_folder . $plugin_export_folder . $plugin_file );
	}

	// Find all custom post_types.
	$post_types = get_post_types( array( 'public' => true ) );
	unset( $post_types['attachment'] );

	if ( ! $post_types ) {
		WP_CLI::error( 'No post types found.  Stopping export', $exit = true );
	}

	// Creates a new progress bar.
	$progress_type = \WP_CLI\Utils\make_progress_bar( ' |- Discovering ' . count( $post_types ) . ' post types', count( $post_types ), $interval = 100 );

	// Iterate through all post types.
	foreach ( $post_types as $post_type ) {
		global $wp_filesystem;
		$progress_type->tick();
		$posts = wp2grav_find_posts( $post_type );

		// Creates a new progress bar.
		$progress_posts = \WP_CLI\Utils\make_progress_bar( ' |- Parsing ' . count( $posts ) . ' posts from post_type: ' . $post_type, count( $posts ), $interval = 100 );

		$blueprint_component = dirname( $export_plugins_dir ) . '/grav_components/contentType_blueprint.yaml';
		$blueprint           = Yaml::parseFile( $blueprint_component );
		$blueprint['title']  = 'wp_' . $post_type;

		// Reset new_fields.
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
				case 'author':
					$new_fields['header.wp.post.author'] = array(
						'help'  => 'WP Post author',
						'label' => $field_type,
						'type'  => 'array',
					);
					break;

				case 'autosave':
					// Skip autosave field.
					break;

				case 'comments':
					// Grav has a paid comments plugin, but exporting data to it is currently unsupported by
                    // this post-types exporter.
                    // See https://github.com/jgonyea/wp2grav_exporter/issues/27 for current status.
					break;

				case 'editor':
					// Skip editor field.
					break;

				case 'excerpt':
					$new_fields['header.wp.post.excerpt'] = array(
						'help'  => 'WP Post excerpt',
						'label' => $field_type,
						'type'  => 'text',
					);
					break;

				case 'thumbnail':
                    // Hard coding this for now.
					$image_extensions = array(
						'jpg',
						'jpeg',
						'png',
						'gif',
					);
					$new_fields[ 'header.' . $field_type ] = array(
						'label'       => $field_type,
						'type'        => 'file',
						'help'        => 'Available file types: ' . implode( ',', $image_extensions ),
                        'multiple'      => false,
                        'destination' => 'user/data/'
					);

                    // Todo: debug this next line.
                    //$new_fields[ 'header.' . $field_name ]['destination'] = 'user/data/';


					//$extensions = explode( ' ', $image_extensions );
					foreach ( $image_extensions as $extension ) {
						$new_fields[ 'header.' . $field_type ]['accept'][] = '.' . $extension;
					}
					break;

				case 'revisions':
					// Grav has a paid revisions plugin, but exporting data to it is currently unsupported by
                    // this post-types exporter.
                    // See https://github.com/jgonyea/wp2grav_exporter/issues/27 for current status.
					break;

				case 'title':
					$new_fields['header.title'] = array(
						'help'  => 'Page Title',
						'label' => 'PLUGIN_ADMIN.TITLE',
						'type'  => 'text',
					);
					break;

				default:
					// Assume a text field.
					$new_fields[ 'header.' . $field_type ] = array(
						'help'  => "Generic help description for $field_type.",
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
		$wp_filesystem->put_contents( $export_folder . $blueprints_export_folder . 'wp_' . $post_type . '.yaml', $yaml_output );

		// Write default page template.
		copy( $plugin_components_files_path . 'contentType_template.html.twig', $export_folder . $templates_export_folder . 'wp_' . $post_type . '.html.twig' );
	}

	$progress_type->finish();

	WP_CLI::success( 'Saved Complete!  ' . count( $post_types ) . " post types exported to $blueprints_export_folder" );
}


/**
 * Converts an advanced-custom-fields to a Grav's admin form field.
 *
 * @param array   $acf_field Advanced Custom Field.
 * @param WP_Post $post WordPress post.
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

		case 'image':
			$grav_field = array(
				'type'           => 'filepicker',
				'folder'         => 'user://data/wp-content/upload',
				'label'          => $acf_field['label'],
				'preview_images' => true,
				'accept'         => array( 'image/*' ),
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

		default:
			$grav_field['error'] = 'Missing field definition: ' . $acf_field['type'];
			$grav_field['debug'] = $acf_field;
	}

	// Generic field options.
	if ( 1 === $acf_field['required'] ) {
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
