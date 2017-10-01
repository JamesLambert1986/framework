<?php
require_once "../../config.php";

$prefix = (isset($_GET["prefix"]) && !empty($_GET["prefix"])) ? $_GET["prefix"] : "frm";

function _generateRandom($length=6)
{
	$_rand_src = array(
		//array(48,57), //digits
		array(97,122) //lowercase chars
		// , array(65,90) //uppercase chars
	);

	srand ((double) microtime() * 1000000);
	$random_string = "";
	for($i=0;$i<$length;$i++)
	{
		$i1=rand(0,sizeof($_rand_src)-1);
		$random_string .= chr(rand($_rand_src[$i1][0],$_rand_src[$i1][1]));
	}
	return $random_string;
}

//vars 
$font = '../font/SpecialElite-webfont.ttf'; 
$size = 60;//pt 
$color = '#000000'; 
$text = _generateRandom();
setcookie($prefix . "_token",$text, 0, '/');

//over compensate dimensions! 
$cropPadding = 10; 
$fontRange = 'xgypqXi'; 
$bounds = imagettfbbox($size,0,$font,$fontRange); 
$height = abs($bounds[1]-$bounds[5])+$cropPadding+$cropPadding; 
$y = abs($bounds[7])+$cropPadding; 
$width = abs($bounds[0]-$bounds[2]); 
$bounds = imagettfbbox($size,0,$font,$text); 
$x = ($bounds[0] * -1)+$cropPadding; 

//create transparent image 
$image = imagecreatetruecolor($width,$height); 
imagesavealpha($image, true); 
imagealphablending($image, false); 
$background = imagecolorallocatealpha($image, 255, 255, 255, 127); 
imagefilledrectangle($image, 0, 0, $width, $height, $background); 
imagealphablending($image, true); 

//make color 
$rgb = str_split(ltrim($color,'#'),2); 
$color = imagecolorallocatealpha($image,hexdec($rgb[0]),hexdec($rgb[1]),hexdec($rgb[2]),0);

//render text to image 
imagettftext($image,$size,0,$x,$y,$color,$font,$text.'     '.$fontRange); 

//do something with image 
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
header("Cache-Control: no-store, no-cache, must-revalidate"); 
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache"); 
header ('Content-type: image/png');


$im = @imagecreatefrompng('captcha/captcha.png'); 
imagecopymerge($image,$im, 0, 0, 0, 0, 600, 200, 50); 
imagepng($image); 

//free image 
imagedestroy($image); 
?>