<?php

/**
 * @file
 * Handles wp-cli command "wp graveu".
 */

use Symfony\Component\Yaml\Yaml;

 /**
 * Exports WP users to account files for GravCMS.
 */
function grav_export_users () {
    WP_CLI::log("Beginning user export\n");
    $args = [];
    $users = get_users($args);
    $export_folder = "grav_export/user-" . date('Ymd') . '/';




    $progress_bar = \WP_CLI\Utils\make_progress_bar( 'Generating users', count($users) );
    // Process each user.
    foreach ($users as $key => $user) {
        $progress_bar->tick();
        $username = convert_wp_name_to_grav($user);
        $account_content = NULL;
        $account_content['email'] = $user->user_email;
        $account_content['wordpress']['ID'] = $user->ID;
        $account_content['fullname'] = $user->user_nicename;
        $account_content['title'] = $user->displayname;
        // Set random password
        $account_content['password'] = wp_generate_password( 16, false, false );
        $account_content['state'] = "enabled";
        $account_content['language'] = "en";

        // Add user roles.
        $roles = $user->roles;
        foreach($roles as $role) {
            $account_content['groups'][] = "wp_" . convert_wp_role_to_grav($role);
        }
        $account = Yaml::dump($account_content, 20, 4);
        $filename = $username . ".yaml";
        write_file_content($account, $filename, $export_folder, "accounts/");
    }
    $progress_bar->finish();

    WP_CLI::log("\nSave Complete!  " . count($users) . " users exported");
}
