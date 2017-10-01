<?php
// Enable our two global arrays to be saved via the default wordpress functionality
function my_meta_save($post_id)
{
	if (isset($_POST['action']) && $_POST['action'] == "editpost")
	{
		//delete_post_meta($post_id,'variables');
		//delete_post_meta(999999999,'global');

		if(isset($_POST['change_of_page_type']))
		{
			foreach($_POST['page_variables'] as $index => $value)
			{
				if(is_array($value) && isset($value['_type']))
				{
					unset($_POST['page_variables'][$index]);
				}
			}
		}

		/* get current page variables */
		$post_meta = get_post_meta($post_id);

		if(!empty($post_meta))
		{
			foreach($post_meta as $index => $value)
			{
				if(isset($_POST['page_variables'][$index]))
				{
					$value = (is_string($_POST['page_variables'][$index])) ? stripslashes($_POST['page_variables'][$index]) : $_POST['page_variables'][$index];
					
					update_post_meta($post_id, $index, $value);
					unset($_POST['page_variables'][$index]);
				}
				else delete_post_meta($post_id, $index);
			}
		}
		
		/* add new page variables */
		foreach($_POST['page_variables'] as $index => $value)
		{
			$value = (is_string($value)) ? stripslashes($value) : $value;
			
			add_post_meta($post_id,$index,$value,TRUE);
		}

		/* get current site variables */
		$site_meta = get_post_meta(999999999);
		
		if(!empty($site_meta) && !empty($_POST['site_variables']))
		{
			foreach($site_meta as $index => $value)
			{
				if(isset($_POST['site_variables'][$index]))
				{
					$value = (is_string($_POST['site_variables'][$index])) ? stripslashes($_POST['site_variables'][$index]) : $_POST['site_variables'][$index];
				
					update_post_meta(999999999, $index, $value);
					unset($_POST['site_variables'][$index]);
				}
				else delete_post_meta(999999999, $index);
			}
		}
		
		/* add new site variables */
		foreach($_POST['site_variables'] as $index => $value)
		{
			$value = (is_string($value)) ? stripslashes($value) : $value;
			add_post_meta(999999999,$index,$value,TRUE);
		}
	}
}

add_action('save_post','my_meta_save');

?>