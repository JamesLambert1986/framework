<?php

// Change some of the common theme and post type supports
function my_custom_init() {
	
	//add_theme_support('post-thumbnails');
	
	//add_post_type_support('page','excerpt');
	remove_post_type_support('post','excerpt');
	
	remove_post_type_support('post','custom-fields');
	remove_post_type_support('page','custom-fields');

	remove_post_type_support('post','trackbacks');
	remove_post_type_support('post','comments');
	
	remove_post_type_support('page','comments');
	remove_post_type_support('page','trackbacks');
	
	remove_post_type_support('page','revisions');
	
	remove_post_type_support('page','discussion');
	
	remove_post_type_support('post','editor');
	remove_post_type_support('page','editor');
	
}
add_action( 'init', 'my_custom_init', 100);

	

// Remove the quick action buttons on the top menu bar
function nlk_admin_bar_render() {
	global $wp_admin_bar;
	
	$wp_admin_bar->remove_menu('comments');
	$wp_admin_bar->remove_menu('new-content');
}
add_action( 'wp_before_admin_bar_render', 'nlk_admin_bar_render' );  


// Remove side menu Items that we dont want to appear
add_action('admin_menu', 'remove_menu_items');
function remove_menu_items() {
      global $menu;
      
      // Prepare our list of items to hide
      $arrRemoveItems = array(
          'Comments' => 'Comments',
          //'Media' => 'Media',
          //'Posts' => 'Posts',
          //'Users' => 'Users',
          //'Tools' => 'Tools',
          //'Settings' => 'Settings',
          //'Plugins' => 'Plugins',
          'Appearance' => 'Appearance'
      );
      end ($menu);

      while (prev($menu)){
        $value = explode(' ',$menu[key($menu)][0]);
        
        if(in_array($value[0] != NULL?$value[0]:"" , $arrRemoveItems))
            unset($menu[key($menu)]);
      }
}





?>