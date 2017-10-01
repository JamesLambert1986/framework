<?php

// post id
// type
// name




$_FIELDS = new cms_fields(array("ajax"=>array("_name"=>$_POST['_name'],
												"_type"=>$_POST['_type'],
												"_extras" => array(
													'is_open' => true,
													'in_group' => true
												)
											   )),array(),$_POST['fieldname']);




echo $_FIELDS->page_fields_html;



?>