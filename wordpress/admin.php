<?php

require_once(dirname( __FILE__ ).'/functions/default_settings.php');
require_once(dirname( __FILE__ ).'/functions/remove_features.php');
require_once(dirname( __FILE__ ).'/functions/custom_css.php');
require_once(dirname( __FILE__ ).'/functions/thumbnail_sizes.php');
require_once(dirname( __FILE__ ).'/functions/admin_footer.php');
require_once(dirname( __FILE__ ).'/functions/save_variables.php');
require_once(dirname( __FILE__ ).'/functions/clear_cache.php');
require_once(dirname( __FILE__ ).'/functions/tinymce.php');
require_once(dirname( __FILE__ ).'/functions/customposttypes.php');


//add_action('admin_head', 'mytheme_remove_help_tabs');
//function mytheme_remove_help_tabs() {
//    $screen = get_current_screen();
//    $screen->remove_help_tabs();
//}
//

function meta_box($post_id,$data)
{
	global $path_root;
	
	echo '<div class="wp__variables"><div class="wp__variables__inner">';
	echo $data['args'];
	echo '</div></div>';

	if($data['id'] == "page_meta_box")
	{
		require_once(dirname( __FILE__ ).'/scripts.php');
		require_once(dirname( __FILE__ ).'/styles.php');
	}
}


function add_custom_meta_box($post_type)
{
	global $_PAGE;
	global $html_files;

	$existing = array();

	if($_GET['post'])
	{
		global $post;

		$meta = get_post_custom_keys($post->ID) ? get_post_custom_keys($post->ID) : array();
		$site_meta = get_post_custom_keys(999999999) ? get_post_custom_keys(999999999) : array();
		
		foreach($meta as $key)
		{
			$existing[$key] = get_post_meta($post->ID,$key,true);
		}
		
        foreach($site_meta as $key)
		{
			$existing[$key] = get_post_meta(999999999,$key,true);
		}

		$content_type = $post->post_type;
		$_PAGE->get_content_type_data($html_files,$content_type);

		$_PAGE->add_vars_from_html($html_files,$existing);
	}
	else
	{
		$content_type = $post_type;
		
		$site_meta = get_post_custom_keys(999999999) ? get_post_custom_keys(999999999) : array();

        foreach($site_meta as $key)
		{
			$existing[$key] = get_post_meta(999999999,$key,true);
		}

		$_PAGE->get_content_type_data($html_files,$content_type);
		$_PAGE->add_vars_from_html($html_files,$existing);
	}

	$_FIELDS = new cms_fields($_PAGE->data,$existing);

	add_meta_box("page_meta_box", "Page Variables", "meta_box", $post_type, "normal", "high", $_FIELDS->page_fields_html);
	add_meta_box("meta_meta_box", "Meta Variables", "meta_box", $post_type, "side", "low", $_FIELDS->meta_fields_html);
	//add_meta_box("site_meta_box", "Site Variables", "meta_box", $post_type, "normal", "low", $_FIELDS->site_fields_html);
	
	foreach($_FIELDS->module_groups as $index => $module_group_html)
	{
		$name = $index;
		add_meta_box($name."_meta_box", ucfirst($name), "meta_box", $post_type, "normal", "high", $module_group_html);
	}
}

add_action("add_meta_boxes", "add_custom_meta_box");

function admin_menu_items()
{
    global $menu;

    $menu[3]=$menu[20];// (pages)
    unset($menu[20]);//make original pages menu disappear
   
    $menu[60]=$menu[10];// (media)
    unset($menu[10]);//make original pages menu disappear
}

add_action('admin_menu', 'admin_menu_items');
add_action( 'admin_menu', 'my_admin_menu' );

function my_admin_menu()
{
	add_menu_page( 'Site Variables', 'Site Variables', 'manage_options', 'site_variables', 'site_variables', 'dashicons-admin-site', 2  );
	add_menu_page( 'User Guide', 'User Guide', 'manage_options', 'user_guide', 'user_guide', 'dashicons-book', 60  );
}

function site_variables()
{
	unset($_REQUEST['page']);
	
	global $_PAGE;
	global $html_files;

	if(!empty($_REQUEST['site_variables']))
	{
		$site_meta = get_post_meta(999999999);
		
		if($site_meta)
		{
			foreach($site_meta as $index => $value)
			{
				if(isset($_REQUEST['site_variables'][$index]))
				{
					update_post_meta(999999999, $index, $_REQUEST['site_variables'][$index]);
					unset($_REQUEST['site_variables'][$index]);
				}
				else
				{
					delete_post_meta(999999999, $index);
				}
			}
		}
		
		foreach($_REQUEST['site_variables'] as $index => $var){
			
			add_post_meta(999999999,$index,$var,TRUE);
		}
	}

	$site_meta = get_post_custom_keys(999999999) ? get_post_custom_keys(999999999) : array();

	foreach($site_meta as $key)
	{
		$existing[$key] = get_post_meta(999999999,$key,true);
	}

	$_PAGE->add_vars_from_html($html_files,$existing);
	
	?>

	<div hidden="hidden">
		<?php


			$_FIELDS = new cms_fields($_PAGE->data,$existing);

			$settings = array(
			'teeny' => true,
			'textarea_rows' => 15,
			'tabindex' => 1
		);
		wp_editor(esc_html( __(get_option('whatever_you_need', 'whatever'))), 'terms_wp_content', $settings);
		?>
	</div>

	<div class="site_variables wrap">
		
		<hr class="wp-header-end">
		<h1 class="wp-heading-inline">Site Variables</h1>
		<br/>
		<br/>

		<div class="postbox">
			<div class="inside">
				<div class="wp__variables">
					<div class="wp__variables__inner">
						<form method="post">

							<?php echo $_FIELDS->site_fields_html; ?>
							
							<br/>
							<hr class="clear">
							
							<input name="save" type="submit" class="button button-primary button-large" id="publish" value="Update Site Variables" style="width: auto; float: right;">
							
							<hr class="clear">
						
						</form>
					</div>
				</div>

			</div>
		</div>
	</div>

	<?php
	
	require_once(dirname( __FILE__ ).'/scripts.php');
	require_once(dirname( __FILE__ ).'/styles.php');
}

function user_guide()
{
	require_once(dirname( __FILE__ ).'/scripts.php');
	require_once(dirname( __FILE__ ).'/styles.php');
}
?>