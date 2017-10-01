<?php

// Load custom Admin CSS
add_action('admin_head', 'admin_register_head');
add_action('login_head', 'admin_register_head');
function admin_register_head() {
    
	echo '<link rel="shortcut icon" href="'.get_bloginfo('url').'/_assets/favicons/favicon.ico">';
    echo '<link rel="stylesheet" href="'.get_bloginfo('template_url').'/wp_admin.css">';
}



?>