# Grav Export

## Requirements

PHP v7.1 or above for the composer dependencies.
WP-CLI
Working Wordpress 5 site from which content will be exported.
R/W access to `wp-content/uploads` on the Wordpress site.

## Installation

1. Download and move this plugin to your Wordpress's `wp-content/plugins` folder.
2. Run `composer install --no-dev` within the `wp2grav_exporter` folder to install dependencies.
3. Enable it via `wp plugin activate wp2grav_exporter` or via the admin gui.
4. Run `wp wp2grav-all` to export all items.  See other options below.
5. Exported files are located at `WP_ROOT/wp-content/uploads/wp2grav-exports/DATE`
6. The Grav plugin [https://github.com/david-szabo97/grav-plugin-admin-addon-user-manager](admin-addon-user-manager) is recommended to view and manage users for Grav v1.6 sites.

## Notes

> Running `wp wp2grav-all` will run each of the following export steps at once.  Afterwards, follow each section below on how to import the new data to a Grav install.

## Exporting Users from Wordpress

### Command

`wp wp2grav-users` will generate Grav user account files.

### Results

* User accounts in the export folder under `EXPORT/accounts/`.
  * Usernames will be padded to a minimum of 3 characters, maximum of 16.
  * If a username is truncated or padded, the username will also have the Wordpress uid to avoid collisions.
  * Passwords in each account are randomly generated, and have no connection with the respective Wordpress account.  The plaintext password automatically converts to a hashed_password once the account authenticates for the first time.

### Importing Users to Grav

Copy the `EXPORT/accounts` folder to your `user` directory (e.g. username.yaml files should be placed at `user/accounts`).

## Exporting User Roles from Wordpress

### Command

`wp wp2grav-roles` will generate a Grav groups.yaml file.

### Results

Wordpress user roles export as Grav groups in a `groups.yaml` file at `config/groups.yaml`. Some notes about the role exporting:

* Each Wordpress role is converted to the Grav group `wp_<ROLE_WITH_UNDERSCORES>` (e.g. `subscriber` becomes `wp_subscriber`).
* Wordpress users with administrator roles receive the `wp_administrator` group.
* The `wp_administrator` group receives `admin.super` access along with `admin.login` access.  Accounts with these permissions are full admins on the site!
* A new Grav group called `wp_authenticated_user` group receives `admin.login` access.
* All accounts receive the "wp_authenticated_user" group.

### Importing User Roles

Copy the `EXPORT/config` folder to `users/config`.

## Exporting Post Types from Wordpress
### Command

* `wp wp2grav-post_types` will generate a basic Grav theme, along with page types that match the Wordpress post types.

### Results

* A stub of a Grav theme will be generated that will present basic

### Importing Post Types to Grav
* Copy the `EXPORT/themes` folder to your `user` directory
* Change the default theme under the new site's `System` configuration to the `Wordpress Export` theme.

## Exporting Posts from Wordpress
### Command

* `wp wp2grav-posts` will export all posts.

### Results

* Each post/page will be exported to directories matching metadata from the post, typically the post/ page title.
* Library media will be copied to the `data/wp-content` and in-line content will (eventually) be included within the page's directory.

### Importing Post Types to Grav

* Copy the `EXPORT/pages` folder to your `user` directory
* Copy the `EXPORT/data` folder to your `user` directory
