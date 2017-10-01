<?php

$site_root = dirname( __FILE__ );
$cmd = "styleguide";
$existing_vars = array();

require_once(dirname( __FILE__ )."/classes/parsedown.php");






$parsedown = new Parsedown();




require_once(dirname( __FILE__ )."/classes/parsedown.php");




$readme = file_get_contents(dirname( __FILE__ )."/README.md");

$compiled_readme = $parsedown->text($readme);



$existing_vars['framework_content'] = $compiled_readme;

$existing_vars['framework_content'] .= "<h2>Styleguide</h2>";




if(file_exists(dirname( __FILE__ )."/../codepen/prototype.json")){



	$existing_vars['codepen_links'] = "";



	$codepen_json = file_get_contents(dirname( __FILE__ )."/../codepen/prototype.json");
	$codepen_json = json_decode($codepen_json,true);

	if(isset($codepen_json['sitemap']['level_1']) && is_array($codepen_json['sitemap']['level_1'])){
		
		foreach($codepen_json['sitemap']['level_1'] as $index => $module){
			
			if($module['title'] != "Home"){
			$link = "../codepen/".str_replace(" ","_",strtolower($module['title']));

			$existing_vars['codepen_links'] .= '<div class="sml--2 med--2 lrg--3"><div class="styleguide__codepen_link"><a href="'.$link.'" target="_blank"><span>'.$module['title'].'</span></a></div></div>';
			}
		}
	}
	
	
	
}


require_once("load.php");

?>