<?php
use Symfony\Component\Yaml\Yaml;
/**
 * @file
 * Handles wp-cli command "wp graver".
 */


/**
 * Exports WP roles to groups.yaml file for GravCMS.
 */
function grav_export_roles () {
    WP_CLI::log("Beginning user roles export.");

    $roles = get_editable_roles();
    $groups = [];
    $export_folder = "grav_export/user-" . date('Ymd') . '/';

    foreach ($roles as $key => $role) {
        $converted_role = convert_wp_role_to_grav($role['name']);
        $role_name = "wp_" . $converted_role;
        $groups[$role_name]['icon'] = "user";
        $groups[$role_name]['readableName'] = $converted_role;
        $groups[$role_name]['description'] = "Exported Wordpress role '" . $converted_role . "'";
        $groups[$role_name]['access']['site']['login'] = TRUE;

        // Grant specific Grav admin access to WP roles.
        if ($role['name'] == 'Administrator') {
            $groups[$role_name]['access']['admin'] = array(
                "login" => TRUE,
                "super" => TRUE,
            );
        } else {
            $groups[$role_name]['access']['admin'] = array(
                "login" => TRUE,
            );
        }
    }

    $group_content = Yaml::dump($groups, 20, 4);
    WP_CLI::log("Saving role export to wp-content/uploads/$export_folder");
    write_file_content($group_content, 'groups.yaml', $export_folder, 'config/');
    WP_CLI::success(count($roles) . " user role(s) exported");
}
