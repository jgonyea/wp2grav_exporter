<?php
/**
 * WP-CLI custom command: Exports WP roles in GravCMS format.
 * Syntax: wp wp2grav-users
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
function wp2grav_export_users() {
	global $wp_filesystem;

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::line( WP_CLI::colorize( '%YBeginning user export%n ' ) );
	}

	$export_folder = getExportDir() . 'accounts/';
	if ( ! wp_mkdir_p( $export_folder ) ) {
		WP_CLI::error( "Could not create accounts export folder at $export_folder" );
		die();
	}

	// Find all users.
	$users = get_users();
	if ( ! $users ) {
		WP_CLI::error( 'No users found.  Stopping export', $exit = true );
	}

	// Creates a new progress bar.
	$progress = \WP_CLI\Utils\make_progress_bar( ' |- Generating ' . count( $users ) . ' user accounts', count( $users ), $interval = 100 );

	foreach ( $users as $user ) {
		$progress->tick();

		// Reset account.
		$account_content = null;

		$account_content['email']              = $user->get( 'user_email' );
		$account_content['wp']['id']           = $user->get( 'ID' );
		$account_content['wp']['user_url']     = $user->get( 'user_url' );
		$account_content['wp']['display_name'] = $user->get( 'display_name' );
		$account_content['wp']['nickname']     = $user->nickname;
		$account_content['wp']['description']  = $user->description;
		$account_content['wp']['first_name']   = $user->first_name;
		$account_content['wp']['last_name']    = $user->last_name;
		$account_content['fullname']           = $user->nickname;
		$account_content['title']              = null;

		// Default WordPress doesn't have the concept of a disabled user.
		$account_content['state'] = 'enabled';

		$user_locale                 = get_user_locale( $user->get( 'ID' ) );
		$account_content['language'] = convert_wp_locale( $user_locale );

		foreach ( $user->roles as $role ) {
			$account_content['groups'][] = 'wp_' . convert_role_wp_to_grav( $role );
		}
		$account_content['groups'][] = 'wp_authenticated_user';
		$account_content['password'] = wp_generate_password( 16, false, false );
		$account                     = Yaml::dump( $account_content, 20, 4 );
		$account                    .= 'login_attempts: {  }';
		$filename                    = convert_username_wp_to_grav( $user );
		try {
			if ( ! $wp_filesystem->put_contents( $export_folder . $filename . '.yaml', $account ) ) {
				throw new Exception( 'Could not save ' . $filename . '.yaml export file' );
			}
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage(), $exit = true );
		}
	}
	WP_CLI::success( 'Saved Complete!  ' . count( $users ) . " user accounts exported to $export_folder" );
	$progress->finish();
}

/**
 * Converts WP locale to Grav locale.
 *
 * @param string $locale WordPress locale to lookup.
 * @return string
 */
function convert_wp_locale( $locale ) {
	switch ( $locale ) {
		case 'ar':
			$default_locale = 'ar';
			break;
		case 'da_DK':
			$default_locale = 'da';
			break;
		case 'id_ID':
			$default_locale = 'id';
			break;
		case 'ru_RU':
			$default_locale = 'ru';
			break;
		case 'sr_RS':
			$default_locale = 'sr';
			break;
		case 'zh-CN':
			$default_locale = 'zh-cn';
			break;
		case 'zh-HK':
			$default_locale = 'zh-cn';
			break;
		case 'zh-TW':
			$default_locale = 'zh-tw';
			break;
		default:
			$substring         = substr( $locale, 0, 2 );
			$substring_locales = array(
				'bg',
				'bn',
				'br',
				'ca',
				'cs',
				'cy',
				'da',
				'de',
				'de',
				'el',
				'en',
				'eo',
				'es',
				'et',
				'eu',
				'fa',
				'fi',
				'fr',
				'gl',
				'he',
				'hr',
				'hu',
				'id',
				'it',
				'ja',
				'km',
				'ko',
				'lt',
				'lv',
				'mn',
				'my',
				'nl',
				'pl',
				'pt',
				'ro',
				'si',
				'sk',
				'sl',
				'sv',
				'sw',
				'th',
				'tr',
				'uk',
				'vi',
			);

			if ( in_array( $substring, $substring_locales, true ) ) {
				$default_locale = $substring;
			} elseif ( 'nn' === $substring ) {
				$default_locale = 'no';
			} else {
				$default_locale = 'en';
			}
	}

	return $default_locale;
}
