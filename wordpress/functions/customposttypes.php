<?php

//************************************************************************************************ Add custom Taxonamies

function custom_post_types()
{
    global $html_files;

	$html = file_get_contents($html_files->template['default']);
	preg_match('/<!-- DATA -->([\s\S]*?)<!-- ENDDATA -->/',$html,$default_data);
	$default_data = (isset($default_data[1])) ? json_decode($default_data[1],true) : array();
    
	if(isset($default_data['_content_types']))
	{
		unset($default_data['_content_types']['page']);
		
		if(!isset($default_data['_content_types']['post']))
		{
			global $wp_post_types;
			unset( $wp_post_types['post'] );
		}
		
		unset($default_data['_content_types']['post']);
		
		foreach($default_data['_content_types'] as $content_type => $arr)
		{
			$singular_label = str_replace("_"," ",ucfirst($content_type));
			
			if(isset($arr['_label']) && !empty($arr['_label']))
				$label = $arr['_label'];
			else $label = $singular_label."s";

			register_post_type($content_type, array(
				'label' => $label,
				'singular_label' => $singular_label,
				'menu_position' => 20,
				'public' => true,
				'show_ui' => true, // UI in admin panel
				'_edit_link' => 'post.php?post=%d',
				'capability_type' => 'post',
				'hierarchical' => false,
				'has_archive' => false,
				'rewrite' => true,
				'_builtin' => false,
				'supports' => array(
					'title',
					'editor',
					'page-attributes'
				),
				'taxonomies' => array()
			));
            
            
			if(isset($arr['categories']) && is_array($arr['categories']))
			{
				register_taxonomy($content_type."_category", $content_type,
					array(  
						'hierarchical' => true,  
						'label' => 'Category',
						'query_var' => true,
						'rewrite' => array(
							'slug' => $content_type, // This controls the base slug that will display before each term
							'with_front' => false // Don't display the category base before 
						)
					)
				);

				foreach($arr['categories'] as $category)
				{
					wp_insert_term(
						$category, // the term 
						$content_type."_category", // the taxonomy
						array(
						'description'=> ucfirst($category),
						'slug' => $category
						)
					);
				}
			}
		}
	}
}

add_action("init", "custom_post_types");

?>