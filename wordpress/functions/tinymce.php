<?php

function tinymce_config( $init )
{
	global $global_vars;
	
	// Don't remove line breaks
	$init['remove_linebreaks'] = true; 
	// Convert newline characters to BR tags
	$init['convert_newlines_to_brs'] = true; 
	// Do not remove redundant BR tags
	$init['remove_redundant_brs'] = false;

	$init['plugins'] .= ',table';

	//$init['toolbar1'] = 'styleselect,table,bold,italic,underline,bullist,numlist,blockquote,hr,link,unlink,removeformat,charmap';

	$init['toolbar1'] = 'styleselect,table,bold,italic,underline,bullist,numlist,blockquote,hr,link,unlink,removeformat,charmap';
	$init['content_css'] = get_template_directory_uri() . "/tinymce.css";
	$init['toolbar2'] = '';

	
	if(isset($global_vars['tinymce_style_formats']))
	{
		$style_formats = $global_vars['tinymce_style_formats'];
	}
	else
	{
		$style_formats = array(
			// Each array child is a format with it's own settings
			array(  
				'title' => 'Main Heading',  
				'block' => 'h1',
				'wrapper' => false
			),
			array(  
				'title' => 'Secondary Heading',  
				'block' => 'h2',
				'wrapper' => false
			),
			array(  
				'title' => '.intro',  
				'block' => 'p',  
				'classes' => 'intro',
				'wrapper' => false
			),
			array(  
				'title' => 'Paragraph',  
				'block' => 'p',  
				'wrapper' => false
			),
			array(  
				'title' => 'Cite',  
				'block' => 'cite',  
				'wrapper' => false
			)
		);
	}
	
//	$init['wpautop'] = false;
	$init['style_formats'] = json_encode( $style_formats );  

	// Pass $init back to WordPress
	return $init;
}
add_filter('tiny_mce_before_init', 'tinymce_config');


function js_libs()
{
	add_editor_style(get_template_directory_uri() . "/tinymce.css");
	wp_enqueue_script('tiny_mce');
}
add_action("admin_print_scripts", "js_libs");


?>