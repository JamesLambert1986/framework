<?php

class data__styleguide extends data 
{ 
    public function __construct($args) 
    {
        parent::__construct($args);
		
		
		
		$this->add_var("_template","styleguide");
	
		$this->create_styleguide_data($this->html_files);

		$html = new template($this->data,$this->html_files);

		if(!empty($this->data['modules']))
		{
			foreach($this->data['modules'] as $index => $module)
			{
				$file = $module['file'];

				$module_data = get_json_from_html(file_get_contents($file));

				if(isset($module_data['variation']) && is_array($module_data['variation']))
				{
					$module_html = "";

					foreach($module_data['variation'] as $variation)
					{
						$variation_module_data = $module_data;
						$variation_module_data['variation'] = $variation;
						$variation_module_data = data::check_for_html_blocks($variation_module_data,$this->html_files);

						$variation_html = $html->process_html(file_get_contents($file),$variation_module_data);
						$variation_html = $html->strip_data($variation_html);

						$module_html .= $variation_html;
					}
				}
				else
				{
					$module_data = data::check_for_html_blocks($module_data,$this->html_files);
					$module_html = $html->process_html(file_get_contents($file),$module_data);
					$module_html = $html->strip_data($module_html);
				}

				
				if(in_array(strtolower($module['title']),array("breadcrumb","form"))){
					$module_html = "<div class='container'>".$module_html."</div>";
				}
				
				$this->data['modules'][$index]['html'] = $module_html;
			}
		}


		
    }
	
	public function create_styleguide_data($files){
		
		
		$fonts = !empty($this->data['_fonts']) ? $this->data['_fonts'] : array();
		
		$style_data = $this->data['_style'];
		$style_config = array();
		
		
		
		$template_config = file_get_contents($files->template['default']);
		$template_config = get_json_from_html($template_config);
		
		
		$style_config = $this->combine_arrays($style_config,$template_config);
		
		
		
		foreach($fonts as $index => $font_data){
			
			
			$font_html = '<div class="styleguide__font" style="font-family: '.$font_data['family'].';">';
			
			
			$font_html .= '<span class="styleguide__heading--second">'.$font_data['title'].'</span>';
			
			
			$font_html .= '<span class="styleguide__font__sample">AaBcDdEeFf 123456 !#@£&*</span>';
			$font_html .= '<span class="styleguide__font__sample"><em>AaBcDdEeFf 123456 !#@£&*</em></span>';
			$font_html .= '<span class="styleguide__font__sample"><strong>AaBcDdEeFf 123456 !#@£&*</strong></span>';
			
			$font_html .= '<span class="styleguide__font__sample"><strong><em>AaBcDdEeFf 123456 !#@£&*</em></strong></span>';
			
			if(!empty($font_data['url'])){
				$font_html .= '<br/><a href="'.$font_data['url'].'" target="_blank">Sample url</a>';
			}
			
			
			$font_html .= '</div>';
			
			$style_config['font'][$index]['html'] = $font_html;
		}
		
		
		
		
		
		foreach($style_data['colour'] as $colour_name => $colour_value){
			
			$style_config['colours'][] = array(
				'title' => ucfirst($colour_name),
				'value' => $colour_value
			);
		}
		
		if($style_data['grid']){
			
			$lrg__grid = $style_data['grid']['lrg-cols'];
			$med__grid = $style_data['grid']['med-cols'];
			$sml__grid = $style_data['grid']['sml-cols'];
			
			for($i= 1 ; $i <= $lrg__grid ; $i++ ){
				
				$class = "sml--1 med--1 lrg--1";
				
				if($i > $sml__grid)
					$class = "sml--hide med--show med--1 lrg--1";
				
				if($i > $med__grid)
					$class = "sml--hide lrg--show lrg--1";
				
				
				
				$style_config['grid'][]['html'] = '<div class="'.$class.'"></div>';
			}
		}
		
		$style_config['media'] = array(
			"_type" => "element_group",
			"_name" => "media",
			"_wrapper" => array('<div class="med--3">','</div>'),
			"_element" => array()
		);
		
		$elements_to_load = array('video','image');
		
		foreach($elements_to_load as $element){

			$element_data = array(
				"_type" => "element",
				"_name" => $element
			);

			$element_config = file_get_contents($files->element[$element]);
			$element_config = get_json_from_html($element_config);
			$element_config = $this->combine_arrays($element_config,$element_data);
			$element_config = data::check_for_html_blocks($element_config,$files);
			
			$style_config['media']['_elements'][] = $element_config;
		}
		
		// Form fields
		$style_config['form'] = array(
			"_type" => "element_group",
			"_name" => "form_fields",
			"_element" => array()
		);
		
		
		// Basic Input
		$element_data = array(
			"_type" => "element",
			"_name" => "input"
		);

		$element_config = file_get_contents($files->element["input"]);
		$element_config = get_json_from_html($element_config);
		$element_config = $this->combine_arrays($element_config,$element_data);
		$element_config = data::check_for_html_blocks($element_config,$files);
		$style_config['form']['_elements'][] = $element_config;

		
		// Input with validation
		$element_data = array(
			"_type" => "element",
			"_name" => "input",
			"_validation"	=> "error",
			"value"	=> "A wrong value"
		);
		$element_config = file_get_contents($files->element["input"]);
		$element_config = get_json_from_html($element_config);
		$element_config = $this->combine_arrays($element_config,$element_data);
		$element_config = data::check_for_html_blocks($element_config,$files);
		$style_config['form']['_elements'][] = $element_config;

		
		// Input with Icon
		$element_data = array(
			"_type" => "element",
			"_name" => "input",
			"icon"	=> "email"
		);
		$element_config = file_get_contents($files->element["input"]);
		$element_config = get_json_from_html($element_config);
		$element_config = $this->combine_arrays($element_config,$element_data);
		$element_config = data::check_for_html_blocks($element_config,$files);
		$style_config['form']['_elements'][] = $element_config;

		$elements_to_load = array('select','textarea','radiogroup','checkbox');
		
		foreach($elements_to_load as $element){

			$element_data = array(
				"_type" => "element",
				"_name" => $element
			);

			$element_config = file_get_contents($files->element[$element]);
			$element_config = get_json_from_html($element_config);
			$element_config = $this->combine_arrays($element_config,$element_data);
			$element_config = data::check_for_html_blocks($element_config,$files);

			$style_config['form']['_elements'][] = $element_config;
		}
		
		
		if(!empty($files->module )){
		
			foreach($files->module as $module_name => $module_file){

				$style_config['modules'][] = array(
					"title" => str_replace("_"," ",ucfirst($module_name)),
					"file" =>$module_file
				);

			}
		}
		
		
		
		// Merge with the existing json config
		$this->data = $this->combine_arrays($this->data,$style_config);
	}
	
	
}


?>