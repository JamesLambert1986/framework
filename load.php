<?php
/***********************************************************************************************/
/************************************ BASIC VARIABLES ******************************************/
/***********************************************************************************************/

$root = dirname( __FILE__ );
$live = (in_array($_SERVER['HTTP_HOST'],array('localhost','gr109','phill-pc','grtest.co.uk','www.grtest.co.uk'))) ? false  : true;


/***********************************************************************************************/
/*************************************** LOAD CACHE ********************************************/
/***********************************************************************************************/

if($live)
{
	if(count($_REQUEST) == 0)
	{
		$url = $_SERVER['REQUEST_URI'];
		$url = trim($url,"/");
		$url = $url == "" ? "index" : $url; // default to the index page
		$cache_root = $site_root."/cache";

		if(file_exists($cache_root."/".str_replace("/","_",$url).".html"))
		{
			$file = file_get_contents($cache_root."/".str_replace("/","_",$url).".html");
			echo $file;
			exit();
		}
	}
}


/***********************************************************************************************/
/************************************ HELPER FUNCTIONS *****************************************/
/***********************************************************************************************/

// check if string starts with prefix
function starts_with($haystack, $needle)
{
	$length = strlen($needle);
	if ($length == 0) return true;
	
	return (substr($haystack, 0, $length) === $needle);
}

// check if string ends with suffix
function endsWith($haystack, $needle)
{
	$length = strlen($needle);
	if ($length == 0) return true;

	return (substr($haystack, -$length) === $needle);
}

// get array from json string found in the html
function get_json_from_html($html)
{
	preg_match('/<!-- DATA -->([\s\S]*?)<!-- ENDDATA -->/',$html,$data);

	if(isset($data[1]))
		$data = json_decode($data[1],true);
	else $data = array();

	return $data;
}

function replace_key($array, $old_key, $new_key)
{
	$keys = array_keys($array);
	
	if (false === $index = array_search($old_key, $keys))
		throw new Exception(sprintf('Key "%s" does not exit', $old_key));
	
	$keys[$index] = $new_key;
	return array_combine($keys, array_values($array));
}


/***********************************************************************************************/
/************************************* LOAD CLASSES *******************************************/
/***********************************************************************************************/

$scanned_directory = array_diff(scandir(dirname( __FILE__ )."/classes"), array('..', '.'));

foreach($scanned_directory as $file)
{
	require_once(dirname( __FILE__ )."/classes/".$file);
}


/***********************************************************************************************/
/********************************** LIST ALL HTML FILES ****************************************/
/***********************************************************************************************/

$html_files = new file_list($root,$site_root,'_html',array('element','element_group','module','module_group','page_type','template','meta'));


/***********************************************************************************************/
/************************************ LIST SVG FILES *******************************************/
/***********************************************************************************************/

$svg_files = new file_list($root,$site_root,'_assets/svg',array('symbols'));


/***********************************************************************************************/
/************************************** GLOBAL VARS ********************************************/
/***********************************************************************************************/

$path_root = str_replace('index.php','',$_SERVER['PHP_SELF']);
$path_assets = str_replace('index.php','',$_SERVER['PHP_SELF'])."_assets/";

// Make sure the root path is correct when in prototype mode
if (strpos($_SERVER['REQUEST_URI'],"prototype") && !strpos($path_root,"prototype"))
	$path_root = $path_root."prototype/";

$server_request = $_SERVER['REQUEST_URI'];
$server_request = explode("?", $server_request);

$global_vars = array(
	"cmd" => $cmd,
	"site_root" => $site_root,
	"path_root" => $path_root,
	"path_assets" => $path_assets,
	"html_files" => $html_files,
	"svg_files" =>$svg_files,
	"url" => $server_request[0],
	"query_string" => !empty($server_request[1]) ? $server_request[1] : ""
);

// Override global vars with variables set within the project 
if(!empty($existing_vars))
{
	$global_vars['existing_vars'] = $existing_vars;
	
	foreach($existing_vars as $var_name => $var)
		$global_vars[$var_name] = $var;
}


/***********************************************************************************************/
/************************************* CREATE $_PAGE *******************************************/
/***********************************************************************************************/

switch ($cmd)
{
    case "prototype":
        
		$_PAGE = new data__prototype($global_vars);
		
		if(isset($_REQUEST['amp'])){ $_PAGE->amplify(); } // AMP
		
	break;
    case "styleguide":
		
		$_PAGE = new data__styleguide($global_vars);
		
	break;
    case "wordpress":
		
		// Load in the default wordpress functionality
		define('WP_USE_THEMES', true);
		require($site_root.'/wordpress/wp-blog-header.php' );

		// Use the default wordpress $post variable
        global $post;
		setup_postdata($post);

		// Fallback and search
        if($post == null || is_404() || isset($_REQUEST['s'])){

            global $wp_query;
            global $wpdb;
            
			// Check for pre-existing wordpress query
            $query_name = $wp_query->query['name'];
		
			// If no existing query then create one from the url
			if(empty($query_name)){

				$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
				$strPath = $uri_parts[0];
				
				$query_name = explode('/', trim($strPath,"/"));
				$query_name = end($query_name);
			}

			// Get post ID from query
            $post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_status = 'publish'", $query_name));

			// If all else fails push back to the home page
			if(empty($post_id) || isset($_REQUEST['s']))
				$post_id = get_option('page_on_front');

			// Receate the wordppress $post variable and remove the 404 status
            $post = get_post(intval($post_id));
			status_header(200);
			
			// TO DO: custom 404 check
        }

		// Load in project specific variables and overide the global vars
		if(class_exists('custom_project_vars'))
		{
			$custom_vars = new custom_project_vars($global_vars);
			$global_vars = $custom_vars->data;
		}

		$_PAGE = new data__wordpress($global_vars);


		if(isset($_REQUEST['amp'])) $_PAGE->amplify(); // AMP
		
		if(isset($_REQUEST['ajax'])) $_PAGE->add_var("_template","ajax"); // AJAX
		
		if(isset($_REQUEST['s'])) $_PAGE->add_var("page_type","search"); // Search template
		
		
		// Load in project specific functions and changes to the data structure
		if(class_exists('custom_project_changes'))
			$_PAGE = new custom_project_changes($_PAGE);

	break;
    case "wordpress_admin":

		if(class_exists('custom_admin_project_vars'))
		{
			$custom_vars = new custom_admin_project_vars($global_vars);
			$global_vars = $custom_vars->data;
		}
		
		$_PAGE = new data($global_vars);

		
		if($ajax)
		{
			define('WP_USE_THEMES', true);
			require($site_root.'/wordpress/wp-blog-header.php' );
			
			$path_assets = get_bloginfo('url')."/_assets/";
			
			// add custom post types
			require(dirname( __FILE__ ).'/wordpress/functions/customposttypes.php' );
			custom_post_types();
				
			require(dirname( __FILE__ ).'/wordpress/ajax.php' );
			exit();
		}
		else
		{
			$path_assets = get_bloginfo('url')."/_assets/";
			require(dirname( __FILE__ ).'/wordpress/admin.php' );
		}
		
		
	break;
}


if($live == false)
{
	/***********************************************************************************************/
	/*************************************** BUILD CSS *********************************************/
	/***********************************************************************************************/
	
	$scssVars = "";
	foreach($_PAGE->data['_style'] as $type => $arr)
	{
		foreach($arr as $var => $value)
		{
			$scssVars .= "$".$type."-".$var.": ".$value."; \r\n";
		}
	}

	$scss_files = new file_list($root,$site_root,'_assets/scss',array('framework','site','module','element'));
	$scss_framework_order = array(
		"_mixins" => "",
		"_boilerplate" => "",
		"_defaults" => "",
		"_grid" => "",
		"_type" => "",
		"_buttons" => "",
		"_forms" => "",
		"_tables" => "",
		"_media" => "",
		"_nav" => "",
		"_scaling" => "",
		"_lint" => ""
	);

	$scss_files->framework = array_merge($scss_framework_order,$scss_files->framework);

	$scss = new scssc();
	$css = new combine($scss_files);
	
	$font_css = "";
	
	if(!empty($_PAGE->data['_fonts']))
	{
		foreach($_PAGE->data['_fonts'] as $index => $font)
		{
			switch ($index)
			{
				case "body": $font_css .= 'body { font-family: '.$font['family'].'; } ';
				break;
				case "headings": $font_css .= 'h1,h2,h3,h4,h5,h6 { font-family: '.$font['family'].'; } ';
				break;
			}
		}
	}

	$css->string = $scss->compile($scssVars." ".$font_css." ".$css->string);
	$css->minify();

	@file_put_contents($site_root.'/_assets/scss/styles.css', $css->string );
	
	// Print stylehseet
	$scss_files = new file_list($root,$site_root,'_assets/scss',array('print'));
	$print_css = new combine($scss_files);
	
	$print_css->string = $scss->compile($scssVars." ".$font_css." ".$print_css->string);
	$print_css->minify();

	@file_put_contents($site_root.'/_assets/scss/print_styles.css', $print_css->string );

	
	/***********************************************************************************************/
	/*************************************** BUILD JS **********************************************/
	/***********************************************************************************************/
	
	$jsVars = "\r\n /* Vars */ var arrVars = {";
	foreach($_PAGE->data['_style'] as $type => $arr)
	{
		foreach($arr as $var => $value)
		{
			$jsVars .= $type."_".str_replace("-","_",$var).": '".$value."', \r\n";
		}
	}
	$jsVars .= "}; /* Vars:end */ \r\n";

	$js_files = new file_list($root,$site_root,'_assets/js',array('_libs','module','site'));
	$js = new combine($js_files);
	$js->string = $jsVars." ".$js->string;

	$js->minify();

	@file_put_contents($site_root.'/_assets/js/scripts.js', $js->string );


	/***********************************************************************************************/
	/************************************ BUILD FAVICON ********************************************/
	/***********************************************************************************************/

	$favicon_source = $site_root.'/_assets/favicons/favicon.png';
	$favicon_destination = $site_root.'/_assets/favicons/favicon.ico';

	$ico_lib = new PHP_ICO($favicon_source , array(array('16','16'),array('32','32'),array('48','48')));
	$ico_lib->save_ico($favicon_destination);
	
	
	/***********************************************************************************************/
	/*********************************** BUILD SVG FILES *******************************************/
	/***********************************************************************************************/

	$svg = new combine($svg_files);
	$svg->minify();

	// SVG tag around the symbols to make valid
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" >'.$svg->string.'</svg>';

	@file_put_contents($site_root.'/_assets/svg/vectors.svg', $svg);
	
	
	/***********************************************************************************************/
	/********************************* BUILD APP MANIFEST ******************************************/
	/***********************************************************************************************/

	$theme_colur = !empty($_PAGE->data['_style']['colour']['first']) ? $_PAGE->data['_style']['colour']['first'] : "#000000";
	
	$manifest = "{
  \"short_name\": \"".$_PAGE->data['site_title']."\",
  \"name\": \"".$_PAGE->data['site_title']." - ".$_PAGE->data['site_description']."\",
  \"start_url\": \"".$path_root."?utm_source=homescreen\",
  \"display\": \"standalone\",
  \"orientation\": \"portrait\",
  \"background_color\": \"white\",
  \"theme_color\": \"".$theme_colur."\",
  \"icons\": [{
      \"src\": \"".$path_assets."favicons/favicon.png\",
      \"sizes\": \"192x192\",
      \"type\": \"image/png\"
    }]
}";
	
	
	
	@file_put_contents($site_root.'/manifest.json', $manifest);
	
	/***********************************************************************************************/
	/********************************* Create Service Worker file ******************************************/
	/***********************************************************************************************/

	if(!file_exists($site_root."/sw.js")){
		
		$sw = file_get_contents(dirname( __FILE__ )."/sw.js");
		
		
		@file_put_contents($site_root.'/sw.js', $sw);
	}

};




/***********************************************************************************************/
/************************************** Form Logic *********************************************/
/***********************************************************************************************/

if(isset($_REQUEST['form']) && $_REQUEST['form'] == "true"){
	
	
	$_PAGE = new form($_REQUEST,$_PAGE->data);
	
}


/***********************************************************************************************/
/************************************* ALT CLASSES *********************************************/
/***********************************************************************************************/

if(isset($_REQUEST['alt']) ){
	
	if($_REQUEST['alt'] == "")
		$_REQUEST['alt'] = "alt";
	
	$_PAGE->data['alt_class'] = $_REQUEST['alt'];
	
}



/***********************************************************************************************/
/************************************** PRINT HTML *********************************************/
/***********************************************************************************************/

if(in_array($cmd,array("prototype","wordpress","gr","styleguide")))
{    
    $html = new template($_PAGE->data,$html_files);
    echo $html->string;
}



/***********************************************************************************************/
/************************************* CREATE CACHE ********************************************/
/***********************************************************************************************/
if(in_array($cmd,array("wordpress")))
{    
	if(count($_REQUEST) == 0)
	{
		$url = $_SERVER['REQUEST_URI'];
		$url = trim($url,"/");

		$url = $url == "" ? "index" : $url;

		$cache_root = $site_root."/cache";
		if(!is_dir($cache_root))
			mkdir($cache_root);

		$save_file = $cache_root."/".str_replace("/","_",$url).".html";

		file_put_contents($save_file, $html->string);
	}
	

/***********************************************************************************************/
/********************************* BUILD offline html ******************************************/
/***********************************************************************************************/

	$OFFLINE_PAGE = $_PAGE->data;
	$OFFLINE_PAGE['_template'] = "offline";

	
	
	
	$scssVars = "";
	foreach($_PAGE->data['_style'] as $type => $arr)
	{
		foreach($arr as $var => $value)
		{
			$scssVars .= "$".$type."-".$var.": ".$value."; \r\n";
		}
	}

	$scss_files = new file_list($root,$site_root,'_assets/scss',array('framework','offline'));
	$scss_framework_order = array(
		"_mixins" => "",
		"_boilerplate" => "",
		"_defaults" => "",
		"_grid" => "",
		"_type" => "",
		"_buttons" => "",
		"_forms" => "",
		"_tables" => "",
		"_media" => "",
		"_nav" => "",
		"_scaling" => "",
		"_lint" => ""
	);

	$scss_files->framework = array_merge($scss_framework_order,$scss_files->framework);

	$scss = new scssc();
	$css = new combine($scss_files);
	$css->string = $scss->compile($scssVars." ".$css->string);
	$css->minify();

	
	$OFFLINE_PAGE['_offline_css'] = $css->string;

	
	
	
	
	$offline_html = new template($OFFLINE_PAGE,$html_files);
	@file_put_contents($site_root.'/offline.html', $offline_html->string);

}
?>