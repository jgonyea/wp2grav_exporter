<?php
/**
 * WP-CLI custom command: Exports WP post content in GravCMS format.
 * Syntax: wp wp2grav-posts
 */

use Symfony\Component\Yaml\Yaml;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Exports WP user content as GravCMS account yaml files.
 *
 * @throws Exception
 */
function wp2grav_export_posts() {
	WP_CLI::line( WP_CLI::colorize( '%YBeginning posts export%n ' ) );
	$export_plugins_dir = plugin_dir_path( __FILE__ );
	$export_folder      = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';

	$pages_export_folder = $export_folder . 'pages/';
	$files_export_folder = $export_folder . 'data/wp-content/';

	if ( ! wp_mkdir_p( $export_folder ) ||
		! wp_mkdir_p( $pages_export_folder ) ||
		! wp_mkdir_p( $files_export_folder )
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

	// Iterate through all post types.
	foreach ( $post_types as $post_type ) {
		$posts = wp2grav_find_posts( $post_type );
		// Creates a new progress bar.
		$progress_type = \WP_CLI\Utils\make_progress_bar( ' |-Generating ' . count( $posts ) . ' Grav pages from post_type: ' . $post_type, count( $posts ), $interval = 100 );

		// Iterate through posts of post_type.
		foreach ( $posts as $post ) {
			$progress_type->tick();
			if ( ! $post->post_name ) {
				continue;
			}
			$page_output = export_post( get_post( $post->ID ), $export_folder );

			$page_folder = $pages_export_folder . $post->post_name . '/';
			wp_mkdir_p( $page_folder );
			file_put_contents( $page_folder . $post_type . '.md', $page_output );
		}
		$progress_type->finish();
	}

	WP_CLI::success( 'Saved Complete!  ' . count( $posts ) . " posts exported to $pages_export_folder" );
}

function export_post( $post, $export_folder ) {
	$header      = null;
	$frontmatter = null;
	$base_url    = get_site_url();

	$post_meta = get_post_meta( $post->ID );

	// Normal WP meta.
	foreach ( $post_meta as $key => $meta ) {
		$header['wp']['meta'][ $key ] = $meta;
	}

	// ACF plugin meta field data.
	if (is_plugin_active('advanced-custom-fields')){
		$acf_fields = get_field_objects( $post->ID );
		if ( $acf_fields ) {
			foreach ( $acf_fields as $field_name => $acf_field ) {
				unset( $header['wp']['meta'][ $acf_field['name'] ] );
				unset( $header['wp']['meta'][ '_' . $acf_field['_name'] ] );
				if ( $acf_field['value'] ) {
					$header['wp']['meta']['acf'][ $field_name ] = convert_acf_field_data_to_grav( $acf_field, $post, $export_folder );
				}
			}
		}
	}

	$header['wp']['ID']   = $post->ID;
	$header['wp']['guid'] = $post->guid;
	$header['title']      = $post->post_title;
	$header['modified']   = $post->post_modified;
	$header['date']       = $post->post_date;
	if ( $post->post_status == "publish" ) {
		$header['published'] = true;
	} else {
		$header['published'] = false;
	}

	$header['wp']['author_id'] = $post->post_author;

	// Frontmatter.
	$converter   = new HtmlConverter();
	$html        = get_the_content( null, false, $post->ID );
	$frontmatter = $converter->convert( $html );

	// Replace any media upload URLs with new one.
	$frontmatter = str_replace( $base_url . 'wp-content/uploads', 'user/data/wp-content/uploads', $frontmatter );

	// Replace extranneous WP tags.
	$frontmatter = str_replace( '<!-- wp:paragraph -->', '', $frontmatter );
	$frontmatter = str_replace( '<!-- wp:heading -->', '', $frontmatter );
	if ( substr( $frontmatter, 0, 12 ) === '<html><body>' ) {
		$frontmatter = substr( $frontmatter, 12 );
	}

	$full_header = "---\n" . Yaml::dump( $header, 20, 4 ) . "---\n";
	$page_output = $full_header . $frontmatter;
	return $page_output;
}


function convert_acf_field_data_to_grav( $field_data, $post, $export_folder ) {
	$pages_export_folder = $export_folder . 'pages/';
	$files_export_folder = 'data/wp-content/uploads/';
	wp_mkdir_p( $pages_export_folder );
	wp_mkdir_p( $export_folder . $files_export_folder );

	$grav_field = null;
	switch ( $field_data['type'] ) {
		case 'email':
		case 'number':
		case 'range':
		case 'text':
		case 'textarea':
			$grav_field = $field_data['value'];
			break;

		case 'file':
		case 'image':
			$grav_field['ID'] = $field_data['ID'];

			$base_url                = get_site_url();
			$file_url                = $field_data['value']['url'];
			$file_url                = substr( $file_url, strlen( $base_url ) );
			$file_url                = substr( $file_url, strlen( '/wp-content' ) );
			$file_name               = $field_data['value']['filename'];
			$grav_file_subdir        = substr( $file_url, 0, -( strlen( $file_name ) ) );
			$grav_file_subdir        = substr( $grav_file_subdir, 9 );
			$absolute_grav_file_path = $export_folder . $files_export_folder . $grav_file_subdir;

			// Save image to media export folder.
			wp_mkdir_p( $absolute_grav_file_path );
			copy( WP_CONTENT_DIR . $file_url, $absolute_grav_file_path . $file_name );

			// Page header information and metadata.
			$grav_field['name'] = $file_name;
			$grav_field['type'] = $field_data['value']['mime_type'];
			$grav_field['path'] = 'user/' . $files_export_folder . $grav_file_subdir . $file_name;
			$grav_field['size'] = $field_data['value']['filesize'];

			$meta_name = $file_name . '.meta.yaml';
			$alt_text  = $field_data['value']['alt'];
			if ( $alt_text = '' ) {
				$alt_text = $file_name;
			}
			$title_text       = $field_data['value']['title'];
			$metadata_content = "image:\nalt_text: '" . $alt_text . "'\ntitle_text: '" . $title_text . "'\n";
			file_put_contents( $absolute_grav_file_path . $meta_name, $metadata_content );
			break;

		default:
			$grav_field['error'] = 'Missing field definition: ' . $field_data['type'];
			$grav_field['data']  = $field_data;
	};
	return $grav_field;
}
