<?php

class data__prototype extends data 
{ 
    public function __construct($args) 
    { 
		parent::__construct($args);
		

		$this->add_json($this->site_root."/prototype.json");
		
		$this->create_prototype_data();
		
		$this->add_vars_from_html($this->html_files);

		
    }
	
	

	
	public function update_sitemap($level, $arr_pages, $root){
			
		foreach($arr_pages as $index => $page){

			$page_path = str_replace(" ","_",strtolower($page['title']));

			$page_path = str_replace("&","",$page_path);

			
			
			$page['slug'] = $page_path;
			$page['href'] = $root.$page_path;
				
			$url = $this->url != $root ? $this->url : $root."home";
			
			// If the the page path matches
			if(strpos($url,$root.$page_path) === 0){

				$page['class'] = isset($page['page_class']) ? $page['page_class']." current" : "current";
			}
			else {

				$page['class'] = isset($page['page_class']) ? $page['page_class'] : ""; 
			}


			if(isset($page['level_'.($level+1)])){
				$page['level_'.($level+1)] = $this->update_sitemap($level+1, $page['level_'.($level+1)], $root.$page_path."/");
			}
			
			unset($arr_pages[$index]);
			$arr_pages[$page_path] = $page;
		}

		return $arr_pages;
	}
	
	
	
	
	public function create_prototype_data(){
		
		// Create a sitemap
		$this->data['sitemap']['level_1'] = $this->update_sitemap($level = 1, $this->data['sitemap']['level_1'], $this->data['path_root']);
		
		// Create Breadcrumb Trail
		$this->create_breadcrumb();
		
		
		// Get variables for the current page
		$page_config = count($this->data['breadcrumb']['pages']) ? end($this->data['breadcrumb']['pages']) : current($this->data['sitemap']['level_1']);
		
		
		if(!isset($page_config['page_title']))
			$page_config['page_title'] = $page_config['title'];
		
		
		if(!isset($page_config['page_heading']))
			$page_config['page_heading'] = $page_config['page_title'];
		
		
		// Unset Variables from sitemap that could impact rest of the page
		unset($page_config['class']);
		unset($page_config['title']);
		
		
		
		if(isset($_REQUEST['author'])){
			
			$page_config["page_type"] = "author";
			$page_config["title"] = "Author";
		}
		
		
		// Merge with the existing json config
		$this->data = $this->combine_arrays($this->data,$page_config);
		
		
		if(isset($this->data['categories'])){
			
			foreach($this->data['categories'] as $index => $category){
				
				if(isset($_REQUEST['category']) && $category['category'] == $_REQUEST['category']){
					
					$this->data['categories'][$index]['selected'] = " selected";
				}
			}
		}
		
		//$this->create_children();
	}
	
	
	public function create_breadcrumb(){
		
		$root = $this->data['path_root'];
		$sitemap = $this->data['sitemap'];
		$arr_url = explode("/",trim(str_replace($root,"",$this->url),"/"));
		
		
		$breadcrumb = array();
		
		
		foreach($arr_url as $index => $path){
			if($path == "")
				unset($arr_url[$index]);
		}
		
		
		$request = $sitemap;
		$depth = 0;
		
		foreach($arr_url as $level => $path){
			
			if(isset($request['level_'.($level+1)][$path])){
				
				
				$breadcrumb['level_'.($level+1)] = $request['level_'.($level+1)][$path];
				
				
				if(isset($breadcrumb['level_'.($level+1)]['level_'.($level+2)])){
					
					$this->data['children'] = $breadcrumb['level_'.($level+1)]['level_'.($level+2)];
				}
				else {
					$this->data['children'] = array();
				}
				
				unset($breadcrumb['level_'.($level+1)]['level_'.($level+2)]);
				
				$breadcrumb['level_'.($level+1)]['level'] = $level+1;
				
				$request = $request['level_'.($level+1)][$path];
				
				$depth++;
			}
			else {
				$breadcrumb['level_'.($level+1)] = array(
					"title" => "404",
					"page_title" => "Page not Found"
				);
			}
		}
		
		
		$breadcrumb_module = array(
			"_type" => "module",
			"_name" => "breadcrumb",
			"_depth" => $depth
		);
		$breadcrumb_module['pages'] = $breadcrumb;
		
		
		
		$this->data['breadcrumb'] = $breadcrumb_module;
	}
	
}


?>