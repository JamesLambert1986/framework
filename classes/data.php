<?php
class data
{
	public function __construct ($args) {
		
		foreach($args as $var_name => $var){
			
			$this->{$var_name} = $var;
		}
		
		$this->data = array();
		$this->comments = array();
		
		// Add configuration variables to page data
		if(file_exists($this->site_root."/config.json"))
			$this->add_json($this->site_root."/config.json");

		if(!empty($this->existing_vars) && is_array($this->existing_vars)){
			
			foreach($this->existing_vars as $var_name => $var){
				$this->add_var($var_name,$var);
			}
		}
		
		// Add path variables to page data
		$this->add_var("path_root",$this->path_root);
		$this->add_var("path_assets",$this->path_assets);
			
		$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
		$canonical_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https' : 'http' )."://".$_SERVER['HTTP_HOST'].$uri_parts[0];
		
		
		$this->add_var("_canonical_url",$canonical_url);

		// add svg files to page data
		$this->add_SVGS();
		
		// Author dummy data
		$author = array();
		$author['name'] = "Author Full Name";
		$author['short_name'] = "Author";
		$author['email'] = "author@company.co.uk";
		$author['url'] = $this->path_root."?author=1";
		$author['bio'] = "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.";
		
		$this->add_var('author', $author);
	}
	
	public function combine_arrays($arr1,$arr2,$debug=false){

		$arr_comments = array();
		$arr1 = is_array($arr1) ? $arr1 : array();
		$arr2 = is_array($arr2) ? $arr2 : array();
		
		
		// Check Array 1 for comments
		foreach($arr1 as $index => $value){

			preg_match('/<!--([\s\S]*?)-->/',$index,$comment);
			$correct_index = trim(preg_replace('/<!--([\s\S]*?)-->/', "", $index));

			if($comment){
				$comment = $comment[0];
				
				
				if($comment != null){
					$arr1 = replace_key($arr1, $index, $correct_index);
					
					$arr_comments[$correct_index] = $comment;
				}
			}
		}
		
		// Check Array 2 for comments
		foreach($arr2 as $index => $value){

			preg_match('/<!--([\s\S]*?)-->/',$index,$comment);
			$correct_index = trim(preg_replace('/<!--([\s\S]*?)-->/', "", $index));

			if($comment){
				$comment = $comment[0];
				
				if($comment != null){
					$arr2 = replace_key($arr2, $index, $correct_index);
					
					$arr_comments[$correct_index] = $comment;
				}
			}
		}
		
		
		
		// Combing the two arrays
		foreach($arr2 as $index => $value){


			if(isset($arr1[$index]) && !in_array($index,array("_elements","_modules","_items")) && is_array($value) && is_array($arr1[$index])){
				
				// TO do : is this right?
				$value = $this->combine_arrays($arr1[$index],$value);
			}
			else if(in_array($index,array("_modules","_elements","_items"))){
				
				// TO DO: check this isn't breaking stuff
				$value = $this->check_for_html_blocks($value,$this->html_files);
			}
				
				
			
			$arr1[$index] = $value;
		}
		
		
		
		
		if(count($arr_comments) && $this->cmd == "wordpress_admin"){
			
			foreach($arr_comments as $index => $comment){

				if(isset($arr1[$index])){
					
					$arr1 = replace_key($arr1, $index, $index." ".$comment);
				}
			}
		}
		
		
		return $arr1;
	}
	
	public function add_json($file){
	
		if(file_exists($file)){
			$config = file_get_contents($file);
			$config = json_decode($config,true);
		}
		
		
		$this->data = $this->combine_arrays($this->data,$config);
    }
	
	public function add_var($var_name, $var){
		
		$this->data[$var_name] = $var;
	}

	
	public function check_for_html_blocks($config,$files){
		
	// run through the html blocks
	
		if(!is_array($config))
			return $config;
		
		
		foreach($config as $index => $value){


			if(is_array($value) && isset($value['_type']) && isset($value['_name']) && $index !== "_page"){


				if(@file_exists($files->{$value['_type']}[$value['_name']])){


					$block_html = file_get_contents($files->{$value['_type']}[$value['_name']]);
					$block_config = get_json_from_html($block_html);

					$value = data::combine_arrays($block_config,$value);
					$value = data::check_for_html_blocks($value,$files);



					$config[$index] = $value;
				}
				else {
					// TO DO switch
					if($value['_type'] == "module_group"){

						
						foreach($value['_modules'] as $sub_index => $sub_value){

							$block_html = file_get_contents($files->{$sub_value['_type']}[$sub_value['_name']]);
							$block_config = get_json_from_html($block_html);

							$sub_value = data::combine_arrays($block_config,$sub_value);
							$sub_value = data::check_for_html_blocks($sub_value,$files);

							$config[$index]['_modules'][$sub_index] = $sub_value;
						}
					}

					if($value['_type'] == "element_group"){


						foreach($value['_elements'] as $sub_index => $sub_value){

							$block_html = file_get_contents($files->{$sub_value['_type']}[$sub_value['_name']]);
							$block_config = get_json_from_html($block_html);


							$sub_value = data::combine_arrays($block_config,$sub_value);
							$sub_value = data::check_for_html_blocks($sub_value,$files);

							$config[$index]['_elements'][$sub_index] = $sub_value;
						}
					}

					if($value['_type'] == "element_feed"){

						if(is_array($value['_elements'])){


							foreach($value['_elements'] as $sub_index => $sub_value){

								$block_html = file_get_contents($files->{$sub_value['_type']}[$sub_value['_name']]);
								$block_config = get_json_from_html($block_html);

								$sub_value = data::combine_arrays($block_config,$sub_value);
								$sub_value = data::check_for_html_blocks($sub_value,$files);

								$config[$index]['_elements'][$sub_index] = $sub_value;
							}
						}
						else if($value['_elements'] == "children"){
							
							$elements = array();
							
							if(!empty($this->data['children'])){

								foreach($this->data['children'] as $child){
									
									unset($child['class']);
									//unset($child['title']);
									$child["_type"] =  "element";
									$child["_name"] =  "cta";
									
									$block_html = file_get_contents($files->element["cta"]);
									$block_config = get_json_from_html($block_html);

									$child = data::combine_arrays($block_config,$child);
									$child = data::check_for_html_blocks($child,$files);

									$elements[] = $child;
								}
							}
							
                            
							$config[$index]['_elements'] = $elements;
							
						}
						
						// FILTER BY CATEGORY
						if(isset($config[$index]['_elements']) && isset($_REQUEST['category']) && $_REQUEST['category'] != "all"){

							
							foreach($config[$index]['_elements'] as $element_index => $element){

								if(!isset($element['category']) || $element['category'] != $_REQUEST['category'])
									unset($config[$index]['_elements'][$element_index]);
							}
						}
						
						// FILTER BY SEARCH
						if(isset($_REQUEST['s']) && $_REQUEST['s'] != ""){
							
							$search = $_REQUEST['s'];
							$temp_arr = array();
							$temp_arr_2 = array();
							
							foreach($config[$index]['_elements'] as $element_index => $element){

								
								if (strpos(strtolower($element['title']), strtolower($search)) !== false) {
									
									
									$temp_arr[$element_index] = $element;
								}
								else if (strpos(strtolower($element['description']), strtolower($search)) !== false) {
									
									$temp_arr_2[$element_index] = $element;
								}
							}
							
							$config[$index]['_elements'] = $temp_arr + $temp_arr_2;
						}
						
						// Add filter number var
						if(is_array($value['_elements']) && isset($config[$index]['categories'])){

							$categories = array();
							foreach($config[$index]['categories'] as $cat_index => $cat){
								
								$categories[] = $cat['category'];
							}

							
							foreach($value['_elements'] as $sub_index => $sub_value){

								
								if(isset($sub_value['category'])){
									
									$filter_number = array_search($sub_value['category'],$categories) + 1;
									
									$config[$index]['_elements'][$sub_index]['filter_numbers'] = "filter__".(string) $filter_number;
								}
								
								
							}
						}
						
						// PAGING 
						if(isset($config[$index]['_per_page'])){

							$elements_on_page = $config[$index]['_per_page'];
							$total_elements = count($config[$index]['_elements']);
							$total_pages = ceil($total_elements/$elements_on_page);
							
							$current_page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1;
							$config['current_page'] = $current_page;
							
							$prev_page = $current_page == 1 ? 1 : $current_page - 1;
							$next_page = $current_page == $total_pages ? $total_pages : $current_page + 1;
							
							
							$show_pages = $config[$index]['_per_page'];
							
							$slice_number = ($current_page - 1) * $show_pages;
							
							
							$config[$index]['_elements'] = array_slice($config[$index]['_elements'], $slice_number, $show_pages);
							
							$config[$index]['pages'] = array();
							
							// TO DO move into function
							if($total_pages > 1){
								
								$config[$index]['pages'][] = array(
									"title" => "First",
									"number" => "1",
									"disabled" => $current_page == 1 ? "disabled" : "",
									"class" => "first_page_btn"
								);
								
								$config[$index]['pages'][] = array(
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

									$config[$index]['pages'][] = $page_button;
								}
								
								$config[$index]['pages'][] = array(
									"title" => "Next",
									"number" => (string) $next_page,
									"disabled" => $current_page == $total_pages ? "disabled" : "",
									"class" => "next_page_btn"
								);
								
								$config[$index]['pages'][] = array(
									"title" => "Last",
									"number" => (string) $total_pages,
									"disabled" => $current_page == $total_pages ? "disabled" : "",
									"class" => "last_page_btn"
								);
							}
							
						}
						
						
						
						
					}

					if($value['_type'] == "loop"){
						
						
						
						foreach($value['_items'] as $sub_index => $sub_value){

							$sub_value = data::check_for_html_blocks($sub_value,$files);

							$config[$index]['_items'][$sub_index] = $sub_value;
						}
						
					}

				}
			}
			else if($index === "_modules" && is_array($value)){

				$config[$index] = data::check_for_html_blocks($value,$files);
			}
			else if($index === "_elements" && is_array($value)){

				$config[$index] = data::check_for_html_blocks($value,$files);
			}
			else if($index === "_items" && is_array($value)){

				
				$config[$index] = data::check_for_html_blocks($value,$files);
			}
		
			
		}

		
		return $config;
	}
	
	public function add_vars_from_html($files,$existing = array()){
		
		
		$template = $this->data['_template'];
		$page = $this->data['page_type'];
        
		
        if(is_array($page))
            $page = $page[0];
		
        if(isset($existing['page_type']))
            $page = $existing['page_type'];
        
        if(isset($existing['_template']))
            $template = $existing['_template'];
        
		$default_config = file_get_contents($files->template['default']);
		
		$template_config = file_get_contents($files->template[$template]);
		$meta_config = file_get_contents($files->meta['meta']);
		
		if(file_exists($files->page_type[$page]))
			$page_config = file_get_contents($files->page_type[$page]);
		else
			$page_config = "";
		
		
		// search for JSON string and convert it to an array
		
		$default_config = get_json_from_html($default_config);
		$template_config = get_json_from_html($template_config);
		$meta_config = get_json_from_html($meta_config);
		
		
		$page_config = get_json_from_html($page_config);
		
		
		
		$template_config = $this->combine_arrays($default_config,$template_config);
		
		$config = $this->combine_arrays($template_config,$meta_config);
		$config = $this->combine_arrays($config,$page_config);
		
		
		
		
		$config = $this->check_for_html_blocks($config,$files);
		
		
		
		
		// Do some kind of check of 
		
		$this->data = $this->combine_arrays($config, $this->data,true);
		
		
		//$this->data = $this->combine_arrays($this->data,$config); // Make sure the existing data takes priority
		
		//$this->data = check_for_html_blocks($this->data,$files);
	}
	
	
	
	
    public function get_content_type_data($html_files,$content_type){
        
        $html = file_get_contents($html_files->template['default']);
        preg_match('/<!-- DATA -->([\s\S]*?)<!-- ENDDATA -->/',$html,$default_data);
        $default_data = (isset($default_data[1])) ? json_decode($default_data[1],true) : array();

        
        $content_types = $default_data['_content_types'];
        
        if(isset($content_types[$content_type])){
            
            $page_config = $content_types[$content_type]; // Make sure the existing data takes priority
        }
        
        
        if(isset($page_config['page_type'])){
			
            if(is_array($page_config['page_type']))
                $page_type = $page_config['page_type'][0];
            else
                $page_type = $page_config['page_type'];
            
			$page_config['_page'] = array(
				"_type" => "page_type",
				"_name" => $page_type
			);
			
			//unset($page_config['page_type']);
		}
        
        $this->data = $this->combine_arrays($this->data,$page_config);
		
    }
    
	
	
	
	
	public function add_SVGS(){
		
		foreach($this->svg_files->symbols as $id => $file_location){
			
			$svg_data = array(
				"_type" => "element",
				"_name" => "svg",
				"sprite"=> "vectors",
				"id"=> $id
			);
			
			$this->data['svg'][$id] = $svg_data;
		}
		
	}
	
	public function amplify(){
		
		if(empty($this->data['_amp']))
			return;
		
		$this->add_var("_template","amp");
		
		if(!empty($this->svg_files->symbols['logo']) && file_exists($this->svg_files->symbols['logo'])){
			$amp_logo = file_get_contents($this->svg_files->symbols['logo']);
			$amp_logo = str_replace("symbol", "svg", $amp_logo);		
			$this->add_var("amp_logo",$amp_logo);
		}
		
		$amp_date = "";
		$amp_date_modified = "";
		
		
		if(!empty($this->data['date_published'])){
			$amp_date = $this->data['date_published'];
			
			
		}
		
		if(!empty($this->data['date_modified'])){
			$amp_date_modified = $this->data['date_modified'];
		}
		
		
		
		$this->add_var("amp_date",$amp_date);
		$this->add_var("amp_date_modified",$amp_date_modified);
		
	
		
		
		
		$amp_image = "";
		
		
	
		$amp_content = "";
		
		if(!empty($this->data['page_content'])){
			
			$amp_content .= $this->data['page_content'];
		}
		
		if(!empty($this->data['image'])){
			
			$amp_image = $this->data['image'];
		}
		
		if(!empty($this->data['modules']['_modules'])){
			
			$modules = $this->data['modules']['_modules'];
			
			
			foreach($modules as $index => $module){
				
				if(!empty($module['title']) && !empty($module['content']))
					$amp_content .= "<h2>".$module['title']."</h2>";
				
				if(!empty($module['image']) && $module['content']){
					$amp_content .= "<img src='".$module['image']."' width='300' height='200' alt='' />";
					
					if($amp_image == "")
						$amp_image = $module['image'];
				}
				
				if(!empty($module['content']))
					$amp_content .= $module['content'];
			}
		}
		
		if(!empty($this->data['site_social']['_items'])){
			
			foreach($this->data['site_social']['_items'] as $index => $social){
				
				$social_name = !empty($social['title']) ? $social['title'] : "";
				
				$this->add_var("amp_social_".$social_name, "true");
			}
		}
		
		$amp_html = new HtmlToAmp($amp_content);

		//var_dump($amp_html->getConvertedHtml());
		
		$this->add_var("amp_content",$amp_html->getConvertedHtml());
		$this->add_var("amp_image",$amp_image);
	

	}
	
}
?>