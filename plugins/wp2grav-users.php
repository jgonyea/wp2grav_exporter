<?php
/**
 * WP-CLI custom command: Exports WP roles in GravCMS format.
 * Syntax: wp wp2grav-users
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Exports WP user content as GravCMS account yaml files.
 *
 * @throws Exception
 */
function wp2grav_export_users() {
	WP_CLI::line( WP_CLI::colorize( '%YBeginning user export%n ' ) );
	$export_folder = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/accounts/';
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
	$progress = \WP_CLI\Utils\make_progress_bar( 'Generating users data', count( $users ), $interval = 100 );

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
		if ( $user->get( 'language' ) !== null && $user->get( 'language' ) !== '' ) {
			$account_content['language'] = convert_wp_locale( get_user_locale( $user->id ) );
		} else {
			$account_content['language'] = convert_wp_locale( get_locale() );
		}
		foreach ( $user->roles as $role ) {
			$account_content['groups'][] = 'wp_' . convert_role_wp_to_grav( $role );
		}
		$account_content['groups'][] = 'wp_authenticated_user';
		$account_content['password'] = wp_generate_password( 16, false, false );
		$account                     = Yaml::dump( $account_content, 20, 4 );
		$account                    .= 'login_attempts: {  }';
		$filename                    = convert_username_wp_to_grav( $user );
		try {
			if ( ! file_put_contents( $export_folder . $filename . '.yaml', $account ) ) {
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
		case 'en_US':
			$default_locale = 'en';
			break;
		default:
			$default_locale = 'en';
	}

	return $default_locale;
}
