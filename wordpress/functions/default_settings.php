<?php


//************************************************************************************************ Default Settings

//Set WordPress permalink structure via functions.php
remove_filter('template_redirect','redirect_canonical');


if(is_admin()){

	update_option( 'page_on_front', 2);
	update_option( 'show_on_front', 'page' );

	$wpurl = get_option( 'siteurl' );
	update_option( 'home', str_replace("/wordpress","",$wpurl));
}

Function ccw_set_permalinks() {
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure( '/%postname%/' );
}
add_action( 'init', 'ccw_set_permalinks' );





?>