=== Grav_export ===
Contributors: jgonyea
Tags: export, gravcms
Requires at least: 4.5
Tested up to: 5.2.3
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Exports Wordpress content for use in a GravCMS (markdown) website.

== Description ==

This is a Wordpress plugin port of my Drupal 7 module [grav_export](https://www.drupal.org/project/grav_export).  This plugin exports to flat-file the following:
* users, 
* roles, 
* basic posts/ page content
* base stub of a theme

The exported content can be directly dropped into to a GravCMS project.

== Installation ==

1. Upload `grav_export` plugin folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use the command line [wp-cli](https://wp-cli.org/) to call any of the following exports:
    *  

== Frequently Asked Questions ==

= Is this fully working yet? =

No, currently only user and user roles are functioning.


== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 0.1.0 =
Proof of porting concept
