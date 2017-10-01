<?php
class data__wordpress extends data 
{
    public function __construct($args) 
    {
		parent::__construct($args);
		
		global $post;
		
		
		$global = get_post_meta(999999999,"global",true);

		
		
		
		
		// Use variables from existing basic wordpress functionality
		
        $path_assets = get_bloginfo('url')."/_assets/";
        
		$this->add_var('url_path_assets', $path_assets);
		
        $path_assets = explode($_SERVER['HTTP_HOST'], $path_assets);
        $path_assets = end($path_assets);
        
		$this->add_var('path_assets', $path_assets);
        
        
		$this->add_var('site_title', get_bloginfo('name'));
		$this->add_var('site_description', get_bloginfo('description'));

		
		// TO DO: move into function
		
		$author_id = get_the_author_meta('ID');
		$temp_author = get_userdata($author_id);
		$temp_author = (array) $temp_author->data;
		
		$author = array();
		$author['name'] = $temp_author['display_name'];
		$author['short_name'] = $temp_author['user_nicename'];
		$author['email'] = $temp_author['user_email'];
		$author['url'] = get_bloginfo('url')."?author=".$temp_author['ID'];
		
		$this->add_var('author', $author);

		
		
		
		if(isset($post->post_title))
			$this->add_var('page_title', $post->post_title);

		if(isset($post->post_date))
			$this->add_var('date_published', $post->post_date);

		if(isset($post->post_modified))
			$this->add_var('date_modified', $post->post_modified);

		
		// If 404 
		if(is_404())
		{
			// change page template
			// change page title
			// change the page content
			// custom edit page?
		}

		// Load in the variables from the database
		$meta = get_post_custom_keys($post->ID) ? get_post_custom_keys($post->ID) : array();
		foreach($meta as $key)
		{
			$this->add_var($key,get_post_meta($post->ID,$key,true));
		}

		// Load in the global site variables from the database
		$site_meta = get_post_custom_keys(999999999) ? get_post_custom_keys(999999999) : array();
		foreach($site_meta as $key)
		{
			$this->add_var($key,get_post_meta(999999999,$key,true));
		}

		// Create the page block from the page type allowing the correct html file to be loaded ib
		$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
		$canonical_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https' : 'http' )."://".$_SERVER[HTTP_HOST].$uri_parts[0];

		$this->add_var("_canonical_url",$canonical_url);

		
		// TO DO: Create sitemap and breadcrumb 
		$this->wordpress_sitemap();
		
		//Get categories 
		if($post->post_type == "post")
			$term_name = 'category';
		else
			$term_name = $post->post_type.'_category';

		$terms = wp_get_post_terms($post->ID,$term_name);
		
		
		
		$arr_category = array();

		foreach($terms as $index => $term){

			$arr_category[$term->slug] = array(
				'category' => $term->slug,
				'category_title' => $term->name,
				'category_description' => $term->description
			);
		}

		
		$this->add_var('category', $arr_category);
		
		// AUTHOR
		if(is_author()){
			
			$this->create_author_profile();
		}

		
		$this->data = $this->update_fields($this->data);
		$this->add_vars_from_html($this->html_files);
		
		
		$this->add_var('atom_feed', get_bloginfo('atom_url'));
		
		
		if(isset($this->data['_redirect']) && $this->data['_redirect'] == "home"){
			
			
			header("Location: ".get_home_url());
			die();
		}
    }
	
	
	public function wordpress_sitemap(){
		
		$wp_sitemap['level_1'] = array();
		$wp_breadcrumb = array();
		
		$wp_breadcrumb = array(
			"_type" => "module",
			"_name" => "breadcrumb",
			"_depth" => 0
		);

		
		
		$sitemap_pages = get_pages(array(
			'sort_column' => 'menu_order',
			'parent' => 0,
			'hierarchical' => 1
		)); 


		global $post;
		
		
		
		foreach($sitemap_pages as $index => $page){

			$href = get_permalink($page->ID);

			$nav = get_post_meta($page->ID,"navigation",true);
            $nav_class = $page->ID == $post->ID ? "current" : "";
			
			$nav_class = "";
            if($page->ID == $post->ID || $page->ID == $post->post_parent || $page->ID == $this->landing_page_id){
                $nav_class = "current";
				
				$wp_breadcrumb['pages']['level_1'] = array(
					'title'=> $page->post_title,
					'href'=> $href
				);
				
				$wp_breadcrumb['_depth'] = 1;
			}
            
			$level_1 = array(
				'title'=> $page->post_title,
				'href'=> $href,
				'navigation'=> $nav,
				'nav_class'=> $nav_class
			);
			
			$secondary_nav = get_pages(array(
				'sort_column' => 'menu_order',
				'parent' => $page->ID,
				'hierarchical' => 1
			)); 
			
			
			$level_2_arr = array();
			foreach($secondary_nav as $secondary_index => $secondary_page){

				$secondary_nav_class = "";
                if($secondary_page->ID == $post->ID || $secondary_page->ID == $post->post_parent){
					
                    $secondary_nav_class = "current";
					
					$wp_breadcrumb['pages']['level_2'] = array(
						'title'=> $secondary_page->post_title,
						'href'=> get_permalink($secondary_page->ID)
					);
					
					$wp_breadcrumb['_depth'] = 2;
				}
				
				
                
				$level_2_arr[] = array(
					'title'=> $secondary_page->post_title,
					'href'=> get_permalink($secondary_page->ID),
				    'nav_class'=> $secondary_nav_class
				);
			
				$level_1['level_2'] = $level_2_arr;
			}
			
			$level_1['level_2'] = $level_2_arr;
			
			
			// Attached content replaces the secondary nav 
			
			$attached_content_type = get_post_meta($page->ID,"attached_content_type",true);

			if($attached_content_type != ""){
				
				$args = array(
					'posts_per_page'   => -1,
					'orderby'          => 'date',
					'order'            => 'DESC',
					'post_type'        => $attached_content_type,
				);

				$secondary_nav = get_posts( $args );
				
				
				foreach($secondary_nav as $secondary_index => $secondary_page){
					$secondary_nav_class = "";
					
					if($secondary_page->ID == $post->ID || $secondary_page->ID == $post->post_parent){

						$secondary_nav_class = "current";

						$wp_breadcrumb['pages']['level_2'] = array(
							'title'=> $secondary_page->post_title,
							'href'=> get_permalink($secondary_page->ID)
						);

						$wp_breadcrumb['_depth'] = 2;
					}
					
					
					$level_2_arr[] = array(
						'title'=> $secondary_page->post_title,
						'href'=> get_permalink($secondary_page->ID),
				    	'nav_class'=> $secondary_nav_class
					);
				}
				
				$level_1['level_2'] = $level_2_arr;
			}
			
			
			
			$wp_sitemap['level_1'][] = $level_1;
			
			
		}

		$this->data['breadcrumb'] = $wp_breadcrumb;
		$this->data['sitemap'] = $wp_sitemap;
	}
	
	public function update_fields($arr){
		
		
		
		foreach($arr as $attr => $value){

			
			if(is_string($value) && starts_with($value, "#content:")){

				
				$id = str_replace("#content:","",$value);
				$content = (array) get_post($id);
				
				
				
				foreach($content as $key => $index) {
					
					$arr[$key] = $index;
				}

				$meta = get_post_custom_keys($id) ? get_post_custom_keys($id) : array();
				foreach($meta as $key)
				{
					$arr[$key] = get_post_meta($id,$key,true);
				}

				
				if(isset($content['post_title']))
					$arr['title'] = $content['post_title'];
				
				if(isset($content['ID']))
					$arr["url"] = get_permalink($content["ID"]);
			}
			else if(is_string($value) && starts_with($value, "#page:")){

				$arr[$attr] = get_permalink(str_replace("#page:","",$value));
			}
			else if(is_string($value) && !empty($value) && $attr == "url_override"){
				
				if(isset($arr['url']))
					$arr['url'] = $value;
			}
			else if(is_array($value) && !isset($value['_type'])){

				$arr[$attr] = $this->update_fields($value);
			}
			else if(is_array($value) && isset($value['_type'])){
				
				switch($value['_type']){


					case "module_group": /******************************************************************/

						// Make sure we dont load prototype data
						if(empty($value['_modules']))
							$value['_modules'] = array();
						
						$arr[$attr] = $this->update_fields($value);

						
						break;
					case "module": /******************************************************************/

						// add generated id to module
						if(isset($value['id'])) $id = $value['id'];
						else if(isset($value['title']) && !empty($value['title'])) $id = $value['title'];
						else $id = $attr;
							
						$value["_id"] = trim(preg_replace("/\W+/", "-", strtolower($id)), "-");
						
						$arr[$attr] = $this->update_fields($value);

						break;
					case "element_group": /******************************************************************/

						// Make sure we dont load prototype data
						if(empty($value['_elements']))
							$value['_elements'] = array();
						
						$arr[$attr] = $this->update_fields($value);

						break;
					case "element_feed": /******************************************************************/

						
						// Make sure we dont load prototype data
						if(empty($value['_elements']))
							$value['_elements'] = array();
						
						$arr[$attr] = $this->create_element_feed($value);

						break;
					case "element": /******************************************************************/
						
						// add generated id to element
						if(isset($value['id'])) $id = $value['id'];
						else if(isset($value['title']) && !empty($value['title'])) $id = $value['title'];
						else if(isset($value['label']) && !empty($value['label'])) $id = $value['label'];
						else $id = $attr;
							
						$value["_id"] = trim(preg_replace("/\W+/", "-", strtolower($id)), "-");

						$arr[$attr] = $this->update_fields($value);
						
						break;
					case "loop": /******************************************************************/

						// Make sure we dont load prototype data
						if(empty($value['_items']))
							$value['_items'] = array();
						
						if(!isset($value['id']))
						{
							// add generated id to items
							foreach($value['_items'] as $key => $item)
							{
								if(isset($item['id'])) $id = $item['id'];
								else if(isset($item['title']) && !empty($item['title'])) $id = $item['title'];
								else $id = $attr . "-" . $key;

								$value['_items'][$key]["_id"] = trim(preg_replace("/\W+/", "-", strtolower($id)), "-");
							}
						}

						$arr[$attr] = $this->update_fields($value);

						break;
				}
			}
		}

		return $arr;
		
	}
	
	public function create_element_feed($arr){
		
		// TO DO: paging and filtering
		
		
		
		global $wp_query;
		
		$current_page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		
		if(!empty($wp_query->query['page']) && $wp_query->query['page'] != 0){
			$current_page = (int) $wp_query->query['page'];
			
		}
		
		
		$per_page = !empty($arr['_per_page']) ? $arr['_per_page'] : 3;
		
		$offset = ($current_page - 1) * $per_page;

		$exclude = array();
		$content_types = $arr['_content_types'];
		
		$author = '';
		
		if(isset($arr['_author']))
			$author = $arr['_author'];
		
		foreach($content_types as $index => $content_type){
			
			if($content_type == "children"){
				//unset($content_types[$index]);
				$get_children = true;
			}
			else {

				
				if($content_type == "post")
					$term_name = 'category';
				else
					$term_name = $content_type.'_category';

				register_taxonomy($term_name, $content_type,
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

				$terms = get_terms( array(
					'taxonomy' => $term_name,
					'hide_empty' => false,
				));

				
				if(!empty($terms)){
					
					$arr['categories'] = array();
					
					
					foreach($terms as $term){

						if($term->slug != "uncategorised"){

							$temp_data = array(
								'category' => $term->slug,
								'category_title' => $term->name,
								'category_description' => $term->description
							);


							if($term->slug == $_REQUEST['category'])
								$temp_data['selected'] = 'true';

							$arr['categories'][] = $temp_data;

						}
					}

				}
			}
		}
	
		
		$element_file = $arr['_allowed_elements'];

		if(isset($arr['_feed_file']))
			$element_file = $arr['_feed_file'];
		
		$args = array(
			'posts_per_page'   => $per_page,
			'offset'           => $offset,
			'category'         => '',
			'category_name'    => '',
			'orderby'          => $arr['_orderby'],
			'order'            => $arr['_order'],
			'include'          => '',
			'exclude'          => $exclude,
			'meta_key'         => '',
			'meta_value'       => '',
			'post_type'        => $content_types,
			'post_mime_type'   => '',
			'post_parent'      => '',
			'author'	   	=> $author,
			'author_name'	   => '',
			'post_status'      => 'publish',
			'suppress_filters' => true 
		);

		
		if(!isset($arr["_category_file"])) // if not loading data in 
		{

			// FILTER BY CATEGORY
			if(isset($_REQUEST['category']) && $_REQUEST['category'] != "all"){

				$args['tax_query'] = array();
				foreach($content_types as $content_type){

					if($content_type == "post")
						$term_name = 'category';
					else
						$term_name = $content_type.'_category';

					$args['tax_query'][] = array(
						'taxonomy' => $term_name,
						'field' => 'slug',
						'terms' => $_REQUEST['category'], // Where term_id of Term 1 is "1".
						'include_children' => false
					);
				}
			}

			$arr_feed = get_posts( $args );		


			if($get_children){
				global $post;

				$children = get_pages(array(
					'sort_column' => 'menu_order',
					'parent' => $post->ID,
					'hierarchical' => 1
				)); 

				// TO DO merge rather than replace and then re-order
				$arr_feed = $children;
			}



			$args['posts_per_page'] = -1;
			$arr_feed_total = count(get_posts( $args )); 

			$count = 1;
			$total = count($arr_feed);

			foreach($arr_feed as $index => $feed_post){

				$feed_meta = array();

				//$feed_meta['description'] = get_post_meta($feed_post->ID,"description",true);
				//$feed_meta['image'] = get_post_meta($feed_post->ID,"image",true);


				$element_html = file_get_contents($this->html_files->element[$element_file]);
				preg_match('/<!-- DATA -->([\s\S]*?)<!-- ENDDATA -->/',$element_html,$element_data);
				$element_data = (isset($element_data[1])) ? json_decode($element_data[1],true) : array();


				foreach($element_data as $index => $value){

					$feed_meta[$index] = get_post_meta($feed_post->ID,$index,true);

					//if($feed_meta[$index] == "")
					//	unset($feed_meta[$index]);
				}



				$date_display = date('d M Y', strtotime($feed_post->post_date));




				if($content_type == "post")
					$term_name = 'category';
				else
					$term_name = $feed_post->post_type.'_category';

				$terms = wp_get_post_terms($feed_post->ID,$term_name);
				$all_terms = get_terms( array(
					'taxonomy' => $term_name,
					'hide_empty' => false,
				));
				
				
				$temp_terms = array();
				
				foreach($all_terms as $index => $term){
					$temp_terms[] = $term->slug;
				}
				
				$category = "";

				$feed_meta['filter_numbers'] = "";
				
				foreach($terms as $index => $term){

					$category .= "category--".$term->slug." ";
					
					$feed_meta['filter_numbers'] .= 'filter__'.(array_search($term->slug,$temp_terms) + 1).' ';
					
				}

				
				

				$basic_vars = array(
					"_type" => "element",
					"_name" => $element_file,
					"category" => $category,
					"url" => get_permalink($feed_post->ID),
					"_id" => $feed_post->post_type.$feed_post->ID,
					"_content_type" => $feed_post->post_type,
					"_content_type_display" => str_replace("_"," ",$feed_post->post_type),
					"_date" => $feed_post->post_date,
					"_date_display" => $date_display,
					"_count" => $count,
					"_total" => $total
				);

				
				
				$count++;


				$feed_post = (array) $feed_post;
				$feed_meta = (array) $feed_meta;


				$module_arr = array_merge($feed_post,$feed_meta);
				$module_arr = array_merge($module_arr,$basic_vars);

				if($module_arr != false){

					if(isset($module_arr['post_title']))
						$module_arr['title'] = $module_arr['post_title'];

					$arr['_elements'][] = $this->update_fields($module_arr);
				}


				if($feed_post['ID'] == $arr_feed_last[0]->ID){

					unset($arr[$attr]['_next_page']);
				}

			
			}




			$total_pages = ceil($arr_feed_total/$per_page);




			$prev_page = $current_page == 1 ? 1 : $current_page - 1;
			$next_page = $current_page == $total_pages ? $total_pages : $current_page + 1;


			$arr['pages'] = array();


			// To do move into a function
			if($total_pages > 1){

				$arr['pages'][] = array(
					"title" => "First",
					"number" => "1",
					"disabled" => $current_page == 1 ? "disabled" : "",
					"class" => "first_page_btn"
				);

				$arr['pages'][] = array(
					"title" => "Previous",
					"number" => (string) $prev_page,
					"disabled" => $current_page == 1 ? "disabled" : "",
					"class" => "previous_page_btn"
				);


				for ($x = 1; $x <= $total_pages; $x++) {

					$page_button = array(
						"title" => (string) $x,
						"number" => (string) $x
					);


					if($x == $current_page){
						$page_button["class"] = "current_page";
						$page_button["disabled"] = "disabled";
					}
					else if($x == $current_page - 1){

						$page_button["class"] = "previous_page";
					}
					else if($x == $current_page + 1){

						$page_button["class"] = "next_page";
					}

					$arr['pages'][] = $page_button;
				}

				$arr['pages'][] = array(
					"title" => "Next",
					"number" => (string) $next_page,
					"disabled" => $current_page == $total_pages ? "disabled" : "",
					"class" => "next_page_btn"
				);

				$arr['pages'][] = array(
					"title" => "Last",
					"number" => (string) $total_pages,
					"disabled" => $current_page == $total_pages ? "disabled" : "",
					"class" => "last_page_btn"
				);
			}
		}
		else
		{
			$args['posts_per_page'] = -1;
			
			foreach($arr['categories'] as $id => $category)
			{
				// FILTER BY CATEGORY
				$args['tax_query'] = array();
				foreach($content_types as $content_type)
				{
					if($content_type == "post")
						$term_name = 'category';
					else $term_name = $content_type.'_category';

					$args['tax_query'][] = array(
						'taxonomy' => $term_name,
						'field' => 'slug',
						'terms' => $category['category'],
						'include_children' => false
					);
				}

				$arr_feed = get_posts( $args );

				$total = count($arr_feed);
				
				// add category element to feed
				if($total > 0)
				{
					$count = 1;
					
					// add category element to feed
					$category["_type"]  = "element";
					$category["_name"] = $arr["_category_file"];
					$category["_id"] = ($id+1);
					$arr['_elements'][] = $category;

					foreach($arr_feed as $index => $feed_post)
					{
						$feed_meta = array();

						$element_html = file_get_contents($this->html_files->element[$element_file]);
						preg_match('/<!-- DATA -->([\s\S]*?)<!-- ENDDATA -->/',$element_html,$element_data);
						$element_data = (isset($element_data[1])) ? json_decode($element_data[1],true) : array();

						foreach($element_data as $index => $value)
						{
							$feed_meta[$index] = get_post_meta($feed_post->ID,$index,true);
						}

						$date_display = date('d M Y', strtotime($feed_post->post_date));

						if($content_type == "post")
							$term_name = 'category';
						else $term_name = $feed_post->post_type.'_category';

						$terms = wp_get_post_terms($feed_post->ID,$term_name);
						$category = "";

						foreach($terms as $index => $term)
						{
							$category .= "category--".$term->slug." ";
						}

						$basic_vars = array(
							"_type" => "element",
							"_name" => $element_file,
							"category" => $category,
							"url" => get_permalink($feed_post->ID),
							"_id" => $feed_post->post_type.$feed_post->ID,
							"_content_type" => $feed_post->post_type,
							"_content_type_display" => str_replace("_"," ",$feed_post->post_type),
							"_date" => $feed_post->post_date,
							"_date_display" => $date_display,
							"_count" => $count,
							"_total" => $total
						);

						$count++;


						$feed_post = (array) $feed_post;
						$feed_meta = (array) $feed_meta;


						$module_arr = array_merge($feed_post,$feed_meta);
						$module_arr = array_merge($module_arr,$basic_vars);

						if($module_arr != false)
						{
							if(isset($module_arr['post_title']))
								$module_arr['title'] = $module_arr['post_title'];

							$arr['_elements'][] = $this->update_fields($module_arr);
						}
					}
				}
			}
		}


		return $arr;
	}
	
	public function create_author_profile(){
		
		$author_id = get_query_var('author');

		//$author_id = get_the_author_meta('ID');
		$temp_author = get_userdata($author_id);
		$temp_author = (array) $temp_author->data;

		$author = array();
		$author['name'] = $temp_author['display_name'];
		$author['short_name'] = $temp_author['user_nicename'];
		$author['email'] = $temp_author['user_email'];
		$author['url'] = get_bloginfo('url')."?author=".$temp_author['ID'];

		$author['bio'] = get_the_author_meta('description',$temp_author['ID']);

		$this->add_var('author', $author);

		$this->add_var("page_type","author");

		$this->add_var("page_title",$author['name']);


		if(file_exists($this->html_files->module['feed']))
			$feed_config = file_get_contents($this->html_files->module['feed']);



		$feed_config = get_json_from_html($feed_config);

		$feed_config['feed_link'] = "";
		$feed_config['elements']['_author'] = $author_id;
		$feed_config['elements']['_elements'] = array();


		$this->add_var("feed",$feed_config);

	}
}


?>