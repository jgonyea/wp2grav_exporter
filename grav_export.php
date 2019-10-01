<?php
/**
 * Plugin Name:     Grav_export
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Exports content for use in GravCMS
 * Author:          Jeremy Gonyea
 * Author URI:      https://gonyea.io
 * Text Domain:     grav_export
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Grav_export
 */

// Your code starts here.

/**
 * Returns 
 */
function foo_command( $args ) {
    $args = [
        'public' => true,
    ];
    $list = get_post_types($args);
    
    foreach($list as $key => $type){
        WP_CLI::log( $type );
    }
    WP_CLI::success( "Yes." );
}

function 


WP_CLI::add_command( 'postt', 'foo_command' );
