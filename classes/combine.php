<?php
class combine {
	
	public function __construct ($file_list) {

		$this->concat($file_list);
	}
	
	private function concat($file_list){
		
		$return_string = "";
		foreach($file_list as $folder){

			foreach($folder as $file_name => $file_location){


				$return_string .= @file_get_contents($file_location);
			}

		}
		
		$this->string = $return_string;
    }
	
	public function minify(){
		
		$return_string = $this->string;

		// Remove Multi line comments
		$return_string = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', ' ', $return_string);
		
		
		//Remove single line comments
		$return_string = preg_replace('!^\/\/.*|[ \t\r\n]\/\/.*!', ' ', $return_string);
		
		
		// Remove space after colons
		$return_string = str_replace(': ', ':', $return_string);
		
		// Remove whitespace
		$return_string = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), ' ', $return_string);

		$this->string = $return_string;
    }
}
?>