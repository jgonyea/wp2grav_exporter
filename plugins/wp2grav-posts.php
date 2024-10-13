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
 * @param array $args CLI arguments.
 * @param array $assoc_args Associated CLI arguments.
 * @throws Exception Error if output folder isn't writeable.
 *
 * [--id=<POST_ID>]
 * : Exports a single page of id.
 */
function wp2grav_export_posts( $args, $assoc_args ) {
	WP_CLI::line( WP_CLI::colorize( '%YBeginning posts export%n ' ) );
	$export_plugins_dir  = plugin_dir_path( __FILE__ );
	$export_dir          = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
	$pages_export_folder = $export_dir . 'pages/';
	$files_export_folder = $export_dir . 'data/wp-content/';

	if (
		! wp_mkdir_p( $export_dir ) ||
		! wp_mkdir_p( $pages_export_folder ) ||
		! wp_mkdir_p( $files_export_folder )
	) {
		WP_CLI::error( 'Post Types: Could not create export folders ' );
		die();
	}

	// Allow exporting of single post.
	if ( isset( $assoc_args['id'] ) ) {
		$post = get_post( $assoc_args['id'] );
		WP_CLI::line( 'Exporting post: "' . $post->post_title . '"' );
		if ( null !== $post ) {
			$page_output = render_post( get_post( $post->ID ), $export_dir );
			save_post( $post, $page_output, $pages_export_folder );
			$posts = array( $post );
		} else {
			$posts = array();
		}
	} else {
		// Find all custom post_types.
		$post_types = get_post_types( array( 'public' => true ) );
		unset( $post_types['attachment'] );

		// Iterate through all post types.
		foreach ( $post_types as $post_type ) {
			$posts = wp2grav_find_posts( $post_type );
			// Creates a new progress bar.
			$progress_type = \WP_CLI\Utils\make_progress_bar( ' |- Generating ' . count( $posts ) . ' Grav pages from post_type "' . $post_type . '".', count( $posts ), $interval = 100 );

			// Iterate through posts of post_type.
			foreach ( $posts as $post ) {
				$progress_type->tick();
				if ( ! $post->post_name ) {
					continue;
				}
				$page_output = render_post( get_post( $post->ID ), $export_dir );
				save_post( $post, $page_output, $pages_export_folder );
			}
			$progress_type->finish();
		}
	}

	WP_CLI::success( 'Saved Complete!  ' . count( $posts ) . " posts exported to $pages_export_folder" );
}

/**
 * Save rendered markdown to new folder.
 *
 * @param WP_Post $post WordPress post.
 * @param string  $page_render Markdown content rendered from WordPress Post.
 * @param string  $pages_export_folder Destination directory.
 * @return void
 */
function save_post( $post, $page_render, $pages_export_folder ) {
	if ( 'trash' === $post->post_status ) {
		$page_folder = $pages_export_folder . 'z_trashed/' . $post->post_name . '/';
	} else {
		$page_folder = $pages_export_folder . $post->post_name . '/';
	}

	// Create directory.
	wp_mkdir_p( $page_folder );

	// Save content.
	file_put_contents( $page_folder . $post->post_type . '.md', $page_render );
}


/**
 * Converts WP post to markdown text.
 *
 * @param WP_Post $post WP page.
 * @param string  $export_dir Destination folder.
 * @return string Converted page.
 */
function render_post( $post, $export_dir ) {
	$header      = null;
	$frontmatter = null;
	$base_url    = get_site_url();

	// Configure export directories.
	$pages_export_folder = $export_dir . 'pages/';
	wp_mkdir_p( $pages_export_folder );

	// Normal WP meta.
	$post_meta = get_post_meta( $post->ID );
	foreach ( $post_meta as $key => $meta ) {
		$header['wp']['meta'][ $key ] = $meta;
	}

	// ACF plugin meta field data.
	if ( is_plugin_active( 'advanced-custom-fields/acf.php' ) ) {
		$acf_fields = get_field_objects( $post->ID );
		if ( $acf_fields ) {
			foreach ( $acf_fields as $field_name => $acf_field ) {
				unset( $header['wp']['meta'][ $acf_field['name'] ] );
				unset( $header['wp']['meta'][ '_' . $acf_field['_name'] ] );
				if ( $acf_field['value'] ) {
					$header['wp']['meta']['acf'][ $field_name ] = convert_acf_field_data_to_grav( $acf_field, $post, $export_dir );
				}
			}
		}
	}

	// Grav page header.
	$header['wp']['post']['ID']   = $post->ID;
	$header['wp']['post']['guid'] = $post->guid;
	$header['title']              = $post->post_title;
	$header['modified']           = $post->post_modified;
	$header['date']               = get_the_modified_date( $post->ID );
	if ( 'publish' === $post->post_status ) {
		$header['publish_date'] = $post->post_date;
		$header['published']    = true;
	} elseif ( 'future' === $post->post_status ) {
		$header['published']    = false;
		$header['publish_date'] = $post->post_date;
	} else {
		$header['published'] = false;
	}
	$header['wp']['post']['author_id'] = $post->post_author;
	$header['wp']['post']['author']    = get_the_author_meta( 'display_name', $post->post_author );
	$header['wp']['post']['excerpt']   = $post->post_excerpt;

	// Grav taxonomy.
	$categories = get_the_category( $post->ID );
	foreach ( $categories as $category ) {
		$header['taxonomy']['category'][] = $category->name;
	}

	$tags = wp_get_post_tags( $post->ID );
	foreach ( $tags as $tag ) {
		$header['taxonomy']['tag'][] = $tag->name;
	}
	$header['wp']['post']['author']  = get_the_author_meta( 'display_name', $post->post_author );
	$header['wp']['post']['excerpt'] = $post->post_excerpt;

	// Initial Frontmatter conversion.
	$converter   = new HtmlConverter();
	$converter->getConfig()->setOption('hard_break', true);
	$html = apply_filters('the_content', get_post_field('post_content', $post->ID));
	$frontmatter = $converter->convert( $html );

	// Copy featured image.
	$featured_image = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) );
	if ( $featured_image ) {
		copy_media( $featured_image, $export_dir );
	}

	// Copy attached media.
	$attached_media = get_attached_media( '', $post->ID );
	foreach ( $attached_media as $media ) {
		$source = wp_get_attachment_image_src( $media->ID, 'full' );
		copy_media( $source[0], $export_dir );
	}

	// Copy in-line images.
	$images = get_original_images_from_post( $post->ID );
	if ( $images ) {
		foreach ( $images as $image ) {
			copy_media( $image['truncated'], $export_dir );
			if ( isset( $image['width'] ) ) {
				// Remove image size suffix from image url, and re-apply image size in markdown.
				$frontmatter = str_replace( $image['original'], $image['truncated'] . '?resize=' . $image['width'] . ',' . $image['height'], $frontmatter );
			}
		}
	}

	// Remove base url and convert to Grav data location.
	$frontmatter = str_replace( $base_url . '/wp-content/uploads', 'user://data/wp-content/uploads', $frontmatter );

	// Replace extranneous WP tags.
	$frontmatter = wp_strip_all_tags( $frontmatter );

	if ( substr( $frontmatter, 0, 12 ) === '<html><body>' ) {
		$frontmatter = substr( $frontmatter, 12 );
	}

	$full_header = "---\n" . Yaml::dump( $header, 20, 4 ) . "---\n";
	$page_output = $full_header . $frontmatter;
	return $page_output;
}


/**
 * Convert Advanced Custom Field data to Grav header fields.
 *
 * @param array   $field_data ACF field data.
 * @param WP_Post $post WordPress Post.
 * @param string  $export_dir Data export directory.
 */
function convert_acf_field_data_to_grav( $field_data, $post, $export_dir ) {
	$pages_export_folder = $export_dir . 'pages/';
	$files_export_folder = 'data/wp-content/uploads/';
	wp_mkdir_p( $pages_export_folder );
	wp_mkdir_p( $export_dir . $files_export_folder );

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

			$base_url = get_site_url();
			if ( isset( $field_data['value'] ) && isset( $field_data['value']['url'] ) ) {
				$file_url                = $field_data['value']['url'];
				$file_url                = substr( $file_url, strlen( $base_url ) );
				$file_url                = substr( $file_url, strlen( '/wp-content' ) );
				$file_name               = $field_data['value']['filename'];
				$grav_file_subdir        = substr( $file_url, 0, -( strlen( $file_name ) ) );
				$grav_file_subdir        = substr( $grav_file_subdir, 9 );
				$absolute_grav_file_path = $export_dir . $files_export_folder . $grav_file_subdir;

				// Save image to media export folder.
				wp_mkdir_p( $absolute_grav_file_path );
				copy( WP_CONTENT_DIR . $file_url, $absolute_grav_file_path . $file_name );

				// Page header information and metadata.
				$grav_field['name'] = $file_name;
				$grav_field['type'] = $field_data['value']['mime_type'];
				$grav_field['path'] = 'user/' . $files_export_folder . $grav_file_subdir . $file_name;
				$grav_field['size'] = $field_data['value']['filesize'];

				// Media metadata file.
				$meta_name = $file_name . '.meta.yaml';
				$alt_text  = $field_data['value']['alt'];
				if ( '' === $alt_text ) {
					$alt_text = $file_name;
				}
				$title_text       = $field_data['value']['title'];
				$metadata_content = "image:\nalt_text: '" . $alt_text . "'\ntitle_text: '" . $title_text . "'\n";
				file_put_contents( $absolute_grav_file_path . $meta_name, $metadata_content );
			} else {
				$grav_field = null;
			}
			break;

		default:
			$grav_field['error'] = 'Missing field definition: ' . $field_data['type'];
			$grav_field['data']  = $field_data;
	}

	return $grav_field;
}

/**
 * Moves a referenced WP image from the upload folder to
 *
 * @param string $url WP media url.
 * @param string $export_dir Base directory for exports.
 * @return void
 */
function copy_media( $url, $export_dir ) {
	// Source.
	$upload             = wp_get_upload_dir();
	$file_path          = str_replace( $upload['baseurl'], '', $url );
	$file_path_exploded = explode( '/', $file_path );
	$file_name          = $file_path_exploded[ count( $file_path_exploded ) - 1 ];

	// Destination.
	$export_dir              = $export_dir . 'data/wp-content/uploads';
	$grav_file_subdir        = substr( $file_path, 0, -( strlen( $file_name ) ) );
	$absolute_grav_file_path = $export_dir . $grav_file_subdir;
	$absolute_grav_file_path = $export_dir . $grav_file_subdir;

	// Copy file.
	wp_mkdir_p( $absolute_grav_file_path );
	copy( WP_CONTENT_DIR . '/uploads' . $file_path, $absolute_grav_file_path . $file_name );
}

/**
 * Function to get original image URLs from a post
 *
 * @param int $post_id WP post ID.
 * @return array Image data.
 */
function get_original_images_from_post( $post_id ) {
	$post_content = get_post_field( 'post_content', $post_id );

	// Regular expression to find all img tags and their src attributes.
	$image_pattern = '/<img[^>]+src="([^">]+)"/i';
	preg_match_all( $image_pattern, $post_content, $matches );

	$original_images = array();

	if ( ! empty( $matches[1] ) ) {
		foreach ( $matches[1] as $key => $image_url ) {
				// Save original URL.
				$original_images[ $key ]['original'] = $image_url;

				// Find sizes, if present.
				preg_match( '/-\d+x\d+(?=\.\w{3,4}$)/', $image_url, $matches );
			if ( isset( $matches[0] ) ) {
				$size_exploded                     = explode( 'x', $matches[0] );
				$original_images[ $key ]['width']  = substr( $size_exploded[0], 1 );
				$original_images[ $key ]['height'] = $size_exploded[1];
			}

			// Remove the size suffix.
			$truncated_image_url = preg_replace( '/-\d+x\d+(?=\.\w{3,4}$)/', '', $image_url );

			// Use wp_get_attachment_url if the image has an ID.
			$attachment_id = attachment_url_to_postid( $truncated_image_url );

			if ( $attachment_id ) {
					$original_url = wp_get_attachment_url( $attachment_id );
				if ( $original_url ) {
						$original_images[ $key ]['truncated'] = $original_url;
				}
			} else {
					// If no attachment ID, use the cleaned URL.
					$original_images[ $key ]['truncated'] = $truncated_image_url;
			}
		}
	}

	return $original_images;
}
