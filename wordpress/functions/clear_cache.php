<?php


// Clear the flat html generated files when any thing is saved on wordpress
function clear_cache( $post_id)
{
	if ( wp_is_post_revision( $post_id ) )
		return;
	
	global $site_root;

	$cache_root = $site_root."/cache";

	if(!function_exists("rrmdir"))
	{
		function rrmdir($dir)
		{
			if (is_dir($dir))
			{
				$objects = scandir($dir);
				foreach ($objects as $object)
				{
					if ($object != "." && $object != "..")
					{
						if (filetype($dir."/".$object) == "dir") 
							rrmdir($dir."/".$object); // Reload the function the next dir level
						else unlink($dir."/".$object);
					}
				}

				reset($objects);
				rmdir($dir);
			}
		}
	}
	
    rrmdir($cache_root);
}

add_action( 'save_post', 'clear_cache' );
?>