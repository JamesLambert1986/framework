<?php 
class template
{
//	protected $data; // module replacement data array
	
	
	function __construct($data, $files){
		
		$this->data = $data;
		$this->files = $files;
		$this->string = "";
		$this->counter = 0;
		
		$html = "";
		$page_html = "";
		$meta_html = "";
		
		if(isset($this->data['_template'])){

			$folder = "template";
			$file_list = $this->files->template;
			$file = $file_list[$this->data['_template']];
			$html = file_get_contents($file);
			$html = $this->strip_data($html);
		}
		
		
		$html = str_replace(array("\r\n", "\r", "\n", "\t", '   ', '    '), ' ', $html);
		
		$html = $this->process_html($html,$this->data);
	
		if(isset($this->data['page_type'])){

			
			$file_list = $this->files->page_type;
			$file = $file_list[$this->data['page_type']];
			
			
			$page_html = file_get_contents($file);
			
			$page_html = str_replace(array("\r\n", "\r", "\n", "\t", '   ', '    '), ' ', $page_html);
			$page_html = $this->strip_data($page_html);
			$page_html = $this->process_html($page_html,$this->data);
		}
		
		$file_list = $this->files->meta;
		$file = $file_list['meta'];
		$meta_html = file_get_contents($file);
		$meta_html = $this->strip_data($meta_html);
		$meta_html = $this->process_html($meta_html,$this->data);

		
		$html = str_replace('[meta]', $meta_html, $html);
		$html = str_replace('[page]', $page_html, $html);

		
		$this->string = $html;
	}
	
	public function strip_data($html){
		
		$html = preg_replace('/<!-- DATA -->([\s\S]*?)<!-- ENDDATA -->/', "", $html); // remove data
		return $html;
	}
	
	public function process_html($html,$data){

		$html = str_replace(array("\r\n", "\r", "\n", "\t", '   ', '    '), ' ', $html);
		
		// Check for Functions like IF and FOREACH
		// TO DO when the html is too large this just breaks completely
		// Need to process the html some how before doing the callback function, maybe split it up into chucks and then re-stich
		
		$html = preg_replace_callback(
			'/(?:<!-- )(IF|FOREACH):([\s\S]*?)(?:(==|===|!=|!==|<>|>=|<=|<|>|%|=\$)([\s\S]*?))?(?: -->)([\s\S]*?)(?:(?:<!-- )ELSE:(?:\2)(?: -->)([\s\S]*?))?(?:<!-- )(?:END(?:\1)):(?:\2)(?: -->)/',
			function($match) use($data)
			{
				
				$function = $match[1];
				$var = $match[2];
				$operator = $match[3];
				$to_match = $match[4];
				$content = $match[5];
				$else_content = isset($match[6]) ? $match[6] : "";
				$html = '';
				
				switch ($function)
				{
					case "IF":

						$var = $this->get_var($var, $data);
						
						if($operator != "" && $to_match != ""){
						
							if($var == NULL){ $var = "blank"; }
							
							
							if(is_array($var))
								$var = $var[0];
							
							if(!is_numeric($var) && $operator != "=$"){
								$var = "'".$var."'";
							}
							
							if(!is_numeric($to_match) && $operator != "=$"){
								$to_match = "'".$to_match."'";
							}
							
							if($operator == "%"){
								
								if($var % $to_match == 0)
									$html = $content;
								else
									$html = $else_content;		
							}
							else if($operator == "=$"){ // check postfix

								$length = strlen($to_match);

								if($length != 0 && is_string($var) && substr($var, -$length) === $to_match)
									$html = $content;
								else
									$html = $else_content;	
							}
							else if(eval('if('.$var.$operator.$to_match.') {return true;} else {return false;}')){
								
								$html = $content;
							}
							else {
								
								$html = $else_content;
							}
						}
						else {
							
							$html = !empty($var) ? $content : $else_content;
						}
						
						
						$html = $this->process_html($html, $data);
						
					break;

					case "FOREACH":
						
						$var = $this->get_var($var, $data);
						
						if(!empty($var))
						{
							$this->counter = 1;
							
							foreach($var as $key => $row)
							{
								if(!is_array($row)){
									$row = array("value"=>$row);
								}
								
								$for_data = array_merge($data, $row); // merge data arrays
								$html .= $this->process_html($content, $for_data);
								
								$this->counter++;
							}
							
							$this->counter = 1;
						}
					break;
				}
			
				return $html;
			},
			$html
		);
		
		
		
		// Check Vars
		$html = preg_replace_callback(
			'/(?:<!-- |\[)Var:([\s\S]*?)(?: -->|\])/',
			function($match) use($data)
			{
				
				$html = "";
				$var = $this->get_var($match[1], $data);
				
				
				if(is_string($var)){
					$html = $var;
				}
				else if(is_array($var) && isset($var['_type']) && isset($var['_name'])){
					
					$html = $this->build_html_from_array($var);
				}
				else if(is_array($var)){
					
					$html = "";
					
					foreach($var as $index => $block){
						
						
						if(is_array($block) && isset($block['_type']) && isset($block['_name'])){
					
							$html .= $this->build_html_from_array($block);
						}
						else if(!is_array($block) && $index == 0){
							
							$html .= $block;
						}
						else {
							$html .= "";
						}
						
					}
				}
				
				return $html;
			},
			$html
		);
		
		// Check Date
		$html = preg_replace_callback(
			'/(?:<!-- |\[)Date:([\s\S]*?):([\s\S]*?)(?: -->|\])/',
			function($match) use($data)
			{
				
				$html = "";
				$format = $match[1];
				$var = $this->get_var($match[2], $data);
				
				
				if(is_string($var))
				{
					// convert UK to US date format
					$var = preg_replace('%([0-3]?[0-9]{1})\s*?[\./ ]\s*?((?:1[0-2])|0?[0-9])\s*?[./ ]\s*?(\d{4}|\d{2})%', '${2}/${1}/${3}', $var);  
					
					$timestamp = strtotime($var);
					
					$html = date($format,$timestamp);
				}
				
				
				return $html;
			},
			$html
		);
		
		// Check for attr
		$html = preg_replace_callback(
			'/\[attr\]/',
			function($match) use($data)
			{
				
				$html = "";
				
				if(isset($data['attr']) && is_array($data['attr'])){
					
					foreach($data['attr'] as $attr => $value){
						
						$html .= ' '.$attr.'="'.$value.'"';
					}
				}
				
				return $html;
			},
			$html
		);
		
		// Check for attr
		$html = preg_replace_callback(
			'/\[count\]/',
			function($match) use($data)
			{
				
				$html = $this->counter;
				
				
				
				return $html;
			},
			$html
		);

		// modify relative links on the test sites
		global $live;
		
		if(!$live)
		{
			$html = str_replace('"../../','"../',$html);
		}

		return $html;
	}
	
	private function get_var($var_name, $data){
		
		$value = null;
		
		$local_var = isset($data[$var_name]) ? $data[$var_name] : null;
		$global_var = isset($this->data[$var_name]) ? $this->data[$var_name] : null;

		if(strpos($var_name,"->"))
		{
			$string_parts = explode("->",$var_name);

			$local_var = $data;
			foreach($string_parts as $part){
				
				if(isset($local_var[$part])){
					
					$local_var = $local_var[$part];
				}
				else {
					
					$local_var = null;
					break;
				}
			}
			
			$global_var = $this->data;
			foreach($string_parts as $part){
				
				if(isset($global_var[$part])){
					
					$global_var = $global_var[$part];
				}
				else {
					
					$global_var = null;
					break;
				}
			}
			
			
		}

		
		if($local_var){
			
			$value = $local_var;
		}
		else if ($global_var){ 
			
			$value = $global_var;
		}
		
		
		// Check value itself for variables, i.e. paths
		if(is_string($value))
			$value = $this->process_html($value,$this->data);
		
		
		if(is_numeric($value))
			$value = (string) $value;
		
		return $value;
	}
	
	
	
	// Build html from variable
	private function build_html_from_array($arr) {
		
		
		$html = '';
		$file_location = '';
		
		// We always need a type and a name 
		if(!isset($arr["_type"]) || !isset($arr["_name"])){
			
			return $arr[0];
		}
		
		
		
		if(isset($this->files->{$arr["_type"]}[$arr["_name"]])){
			$file_location = $this->files->{$arr["_type"]}[$arr["_name"]];
			
			
			$html = file_get_contents($file_location);
			$html = $this->strip_data($html);

			
			if(isset($arr["_wrapper"]) && is_array($arr["_wrapper"])){
				
				$html = $arr["_wrapper"][0].$html.$arr["_wrapper"][1];
			}
			
			
			// TO DO something with the switch
			switch($arr["_type"])
			{

			}

			// TO DO add wrapper to html block
			
			$html = $this->process_html($html,$arr);

		}
		else if (isset($arr["_type"]) && $arr["_type"] == "module_group"){
			
			if(isset($arr["_wrapper"]) && is_array($arr["_wrapper"]) && isset($arr["_modules"]) && is_array($arr["_modules"])){
				
				foreach($arr["_modules"] as $index => $modules){
					
					$arr["_modules"][$index]["_wrapper"] = $arr["_wrapper"];
				}
			}
			
			$html = "[Var:_modules]";
			$html = $this->process_html($html,$arr);
		}
		else if (isset($arr["_type"]) && $arr["_type"] == "element_group"){
			
			if(isset($arr["_wrapper"]) && is_array($arr["_wrapper"]) && isset($arr["_elements"]) && is_array($arr["_elements"])){
				
				foreach($arr["_elements"] as $index => $element){
					
					$arr["_elements"][$index]["_wrapper"] = $arr["_wrapper"];
				}
			}
			
			
			$html = "[Var:_elements]";
			
			$html = $this->process_html($html,$arr);
		}
		else if (isset($arr["_type"]) && $arr["_type"] == "element_feed"){
			
			if(isset($arr["_wrapper"]) && is_array($arr["_wrapper"]) && isset($arr["_elements"]) && is_array($arr["_elements"])){
				
				foreach($arr["_elements"] as $index => $element){
					
					$arr["_elements"][$index]["_wrapper"] = $arr["_wrapper"];
				}
			}
			
			$html = "[Var:_elements]";
			$html = $this->process_html($html,$arr);
		}
		
		
		return $html;
	}
	
}
?>