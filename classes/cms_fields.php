<?php 
class cms_fields
{
	public function __construct ($data, $existing, $page_fieldname = "page_variables")
	{
		global $cmd;
		global $path_assets;
		
		// The $_PAGE variable is loaded into the page_fields which is later transformed into html input fields for the cms
		$this->page_fields = $data;
		
		// fields that are global variables are seperated out
		$this->site_fields = array();
		
		// Meta fields are seperated out but purely for display purposes only
		$this->meta_fields = array();
		
		// Modules groups are seperated out purely for display purposes only
		$this->module_groups = array();
        
		// Remove variables that can be created using existing wordpress functionality
		if($cmd == "wordpress_admin"){
			
			$this->remove_wordpress_vars();
		}
		
		$this->split_data(); // Splits the data into the three top level arrays and the module groups defined above
		
		// Create the fully formed html blocks that are printed out in the cms
		$this->site_fields_html = $this->create_html("site_variables",$this->site_fields,$existing);
		$this->meta_fields_html = $this->create_html($page_fieldname,$this->meta_fields,$existing);
		$this->page_fields_html = $this->create_html($page_fieldname,$this->page_fields,$existing);
		
		
		foreach($this->module_groups as $index => $module_group){
			
			$module_group_existing = isset($existing[$index]) ? $existing[$index] : array();
			
			
			$name = $module_group['_name'];
			//$html = $this->create_html("page_variables[".$name."]",$module_group,$module_group_existing);
			
			$attr_title = ucfirst(str_replace("_"," ",$index)); // Nice title for display purposes
		
			$html = $this->block_fields($index,$module_group,$attr_title,"page_variables[".$name."]",$module_group_existing,array());
			
			$this->module_groups[$name] = $html;
		}
	}
	
	
	private function remove_wordpress_vars()
	{
		// Remove Variables that can be set using the default functionality
		
		foreach($this->page_fields as $attr => $value)
		{
			$wordpress_vars = array(
				"site_title",
				"site_description",
				"page_title"
			);
			
			foreach($wordpress_vars as $var_name)
			{
				if(starts_with($attr, $var_name))
					unset($this->page_fields[$attr]);
			}
		}
	}
	
	private function split_data()
	{
		foreach($this->page_fields as $attr => $value)
		{
			if(starts_with($attr,"site_"))
			{
				$this->site_fields[$attr] = $value;
				unset($this->page_fields[$attr]);
			}
			else if(starts_with($attr,"meta_"))
			{
				$this->meta_fields[$attr] = $value;
				unset($this->page_fields[$attr]);
			}
			else if(isset($value['_type']) && isset($value['_name']) && $value['_type'] == "module_group")
			{
				$this->module_groups[$attr] = $value;
				unset($this->page_fields[$attr]);
			}
		}
	}

	private function test_fields($attr,$value)
	{
		$default_fieldtype = "text";
		
		$tests = array(
		  	'author' => function($attr,$value)
			{
				if($attr == "author")
				{
				  	return "unset";
				}
			},
		  	'svg' => function($attr,$value)
			{
				if($attr == "svg")
				{
				  	return "unset";
				}
			},
		  	'path' => function($attr,$value)
			{
				if(starts_with($attr, "path_"))
				{
				  	return "unset";
				}
			},
			'useful_app_vars' => function($attr,$value)
			{
				if(in_array($attr,array('_name','_type','_allowed_modules','_allowed_elements','_feed_file','_category_file'))){
					return "hidden";
				}
			},
			'app_vars' => function($attr,$value)
			{
				if(starts_with($attr, "_"))
				{
					return "unset";
				}
			},
			'categories' => function($attr,$value)
			{
				if($attr == "categories")
				{
					return "categories";
				}
			},
			'html_block' => function($attr,$value)
			{
				if(gettype($value) == "array" && isset($value['_type']) && isset($value['_name']))
				{
					return "html_block";
				}
			},
			'array' => function($attr,$value)
			{
				if(gettype($value) == "array")
				{
					return "select";
				}
			},
			'rich_text' => function($attr,$value)
			{
				 if($attr == "content" || endsWith($attr, "content"))
				 {
					return "rich_text";
				}
			},
			'textarea' => function($attr,$value)
			{
				if($attr == "description" || endsWith($attr, "description"))
				{
					return "textarea";
				}
			},
			'image' => function($attr,$value)
			{
				 if($attr == "image" || endsWith($attr, "image") || $attr == "img" || endsWith($attr, "img"))
				 {
					return "image";
				 }
			},
			'document' => function($attr,$value)
			{
				 if($attr == "document" || endsWith($attr, "document") || $attr == "doc" || endsWith($attr, "doc"))
				 {
					return "document";
				 }
			},
			'link' => function($attr,$value)
			{
				if($attr == "link" || endsWith($attr, "link"))
				{
					return "dynamic";
				}
			}
		);
		
		$tests = custom_admin_project_changes::update_field_tests($tests);

		foreach($tests as $index => $func)
		{
			if(is_callable($func))
			{
				$fieldtype = $func($attr,$value);
				
				if($fieldtype != null)
					break;
			}
		}

		return $fieldtype ? $fieldtype : $default_fieldtype;
	}

	private function create_field_html($attr,$value,$fieldtype,$fieldname,$existing,$tags = array())
	{
		$field_html = array(
		  	"unset" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				$html = '';
				return $html;
			},
			"hidden" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				$html = '<input type="hidden" name="'.$fieldname.'['.$attr.']" value="'.$value.'" />';
				return $html;
			},
			"categories" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				$html = '';
				
				if(is_array($value))
				{
					foreach($value as $index => $val)
					{
						$html .= '<input type="hidden" name="'.$fieldname.'['.$attr.'][]" value="'.$val.'" />';
					}
				}
				
				return $html;
			},
			"html_block" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				$html = $this->block_fields($attr,$value,$attr_title,$fieldname,$existing,$tags);
				
				return $html;
			},
			"select" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				$field_id = str_replace(array('[',']'),array("_",""),$fieldname)."_".$attr;
			
				$html = '<label for="'.$field_id.'">'.$attr_title.':</label>';
				$html .= isset($comment) ? "<p class='comment'>".$comment."</p>" : "";
				$html .= '<select id="'.$field_id.'" name="'.$fieldname.'['.$attr.']">';

				foreach($value as $index => $val)
				{
					$html .= '<option value="'.$val.'"'.(isset($existing[$attr]) && $existing[$attr] == $val ? ' selected="selected"' : "").'>'.$val.'</option>';
				}
				
				$html .= '</select>';
				
				return $html;
			},
			"rich_text" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				$html = '<label>'.$attr_title.':</label>';
				$html .= '</br>';
			
				$field_id = str_replace(array("[","]"),array("_","_"),$fieldname)."_".str_replace(array("[","]"),array("_","_"),$attr);

				if(isset($_POST['ajax']))
				{
					//$html .= '<div><textarea name="'.$fieldname.'['.$attr.']" id="'.$field_id.'" class="wp-editor-area">'.$value.'</textarea></div>';
					
					$html .= '<div id="' . $field_id . '_wrapper" ><textarea name="'.$fieldname.'['.$attr.']" id="'.$field_id.'" class="wp-editor-area"></textarea></div>';
				}
				else
				{
					$html .= '<div class="wp-media-buttons"><button type="button" class="button insert-media add_media" data-editor="'.$field_id.'"><span class="wp-media-buttons-icon"></span> Add Media</button></div>';

					$editer_content = isset($existing[$attr]) ? $existing[$attr] : "";
					
                    if($fieldname == "site_variables")
                        $editer_content = isset($existing[$attr]) ? $existing[$attr] : $value;

					ob_start();
					wp_editor($editer_content, str_replace(array("[","]"),"_",$fieldname.'_'.$attr), array(
						'wpautop'       => false,
						'media_buttons' => false,
						'textarea_name' => $fieldname.'['.$attr.']',
						'textarea_rows' => 10,
						'teeny'         => false
					));
					$editor = ob_get_contents();
					ob_end_clean();
					$html .= $editor;
				}


				$html .= '<hr class="clear" />';
				
				$html .= '<hr class="clear" /><br/>';
				
				return $html;
			},
			"rich_text_mini" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				$html = '<label>'.$attr_title.':</label>';
			
				$field_id = str_replace(array("[","]"),array("_","_"),$fieldname)."_".str_replace(array("[","]"),array("_","_"),$attr);
			
				if(isset($_POST['ajax']))
				{ 
					$html .= '<div id="' . $field_id . '_wrapper"><textarea id="' . $field_id . '" name="'.$fieldname.'['.$attr.']" class="rich_text_mini editable"></textarea></div>';
				}
				else
				{
					$editer_content = isset($existing[$attr]) ? $existing[$attr] : "";
					
                    if($fieldname == "site_variables")
                        $editer_content = isset($existing[$attr]) ? $existing[$attr] : $value;

					$html .= '<div><textarea id="' . $field_id . '" name="'.$fieldname.'['.$attr.']" class="rich_text_mini editable">'.$editer_content.'</textarea></div>';
				}
			
				$html .= '<hr class="clear" />';
			
				return $html;
			},
			"rich_text_bold" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				$html = '<label>'.$attr_title.':</label>';
			
				if(isset($_POST['ajax']))
				{ 
					$html .= '<div><p id="'.$fieldname.'['.$attr.']" class="rich_text_bold editable"></p></div>';
				}
				else
				{
					$editer_content = isset($existing[$attr]) ? $existing[$attr] : "";
					
                    if($fieldname == "site_variables")
                        $editer_content = isset($existing[$attr]) ? $existing[$attr] : $value;

					$html .= '<div><p id="'.$fieldname.'['.$attr.']" class="rich_text_bold editable">'.$editer_content.'</p></div>';
				}
			
				$html .= '<hr class="clear" />';
			
				return $html;
			},
			"textarea" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				//$value = isset($existing[$attr]) ? $existing[$attr] : $vlaue;
				$value = isset($existing[$attr]) ? $existing[$attr] : "";
				
                if($fieldname == "site_variables")
                    $value = isset($existing[$attr]) ? $existing[$attr] : $value;
				
				$html = '<label>'.$attr_title.':</label>';
				$html .= '<textarea name="'.$fieldname.'['.$attr.']">'.$value.'</textarea>';
				$html .= '<hr class="clear" />';
				
				return $html;
			},
			"image" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				global $path_assets;
				
				$html = '';
			
				$html .= '<label for="background_image">'.$attr_title.'</label>';
				$html .= isset($comment) ? "<p class='comment'>".$comment."</p>" : "";
				$html .= '<div class="selectmedia">';
				$html .= '<hr class="clear" />';
				$html .= '<div class="preview_image">';

				$image = isset($existing[$attr]) ? $existing[$attr] : "";
				
                if($fieldname == "site_variables")
                    $image = isset($existing[$attr]) ? $existing[$attr] : $value;
                
				$image_src = str_replace("[Var:path_assets]",$path_assets,$image);

				$html .= '<img src="'.$image_src.'" />';
				$html .= '</div>';
				$html .= '<hr class="clear" />';
				$html .= '<span class="button--media button" data-type="image">Browse</span>';
				
				if(isset($existing[$attr]) && !empty($existing[$attr]))
				{
					$html .= ' <span class="button--clear button">Remove</span>';
				}
				
				$html .= '<input '.(isset($tags['required'])?"required ":"").' name="'.$fieldname.'['.$attr.']" type="hidden"  value="'.$image.'"/>';
				$html .= '</div><br/>';

				return $html;
			},
			"document" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				global $path_assets;
				
				$html = '';
			
				$html .= '<label for="background_image">'.$attr_title.'</label>';
				$html .= isset($comment) ? "<p class='comment'>".$comment."</p>" : "";
				$html .= '<div class="selectmedia">';
				$html .= '<hr class="clear" />';
				$html .= '<div class="preview_doc">';

				$file = isset($existing[$attr]) ? $existing[$attr] : "";
				$file_src = basename ( $file );
				
				$file_name = isset($existing["_" . $attr]['_name']) ? $existing["_" . $attr]['_name'] : "";
				$file_size = isset($existing["_" . $attr]['_size']) ? $existing["_" . $attr]['_size'] : "";
				$file_type = isset($existing["_" . $attr]['_type']) ? $existing["_" . $attr]['_type'] : "";

				$html .= $file_src . " (" . $file_size . ")";
				$html .= '</div>';
				$html .= '<hr class="clear" />';
				$html .= '<span class="button--media button" data-type="pdf">Browse</span>';
				
				if(isset($existing[$attr]) && !empty($existing[$attr]))
				{
					$html .= ' <span class="button--clear button">Remove</span>';
				}
				
				$html .= '<input '.(isset($tags['required'])?"required ":"").' name="'.$fieldname.'['.$attr.']" type="hidden" class="url" value="'.$file.'"/>';
				$html .= '<input '.(isset($tags['required'])?"required ":"").' name="'.$fieldname.'[_'.$attr.'][_name]" type="hidden" class="name" value="'.$file_name.'"/>';
				$html .= '<input '.(isset($tags['required'])?"required ":"").' name="'.$fieldname.'[_'.$attr.'][_size]" type="hidden" class="size" value="'.$file_size.'"/>';
				$html .= '<input '.(isset($tags['required'])?"required ":"").' name="'.$fieldname.'[_'.$attr.'][_type]" type="hidden" class="type" value="'.$file_type.'"/>';
				$html .= '</div><br/>';

				return $html;
			},
			"dynamic" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				$content_types = ['page'];
				$content_types = get_post_types();
				
				unset($content_types['attachment']);
				unset($content_types['revision']);
				unset($content_types['nav_menu_item']);
				unset($content_types['custom_css']);
				unset($content_types['customize_changeset']);

                $html .= '<label>'.$attr_title.'</label>';
                $html .= '<select name="'.$fieldname.'['.$attr.']'.'">';
                $html .= '<option value="'.$value.'">None</option>';

                foreach($content_types as $index => $type)
				{
                    $type_array = get_posts(array(
                        'posts_per_page'   => -1,
                        'orderby'          => 'menu_order',
                        'order'            => 'ASC',
                        'post_type'        => $type
                    ));

                    if($type == "page")
					{
                        $type_array = get_pages(array(
                            'posts_per_page'   => -1,
                            'sort_column'      => 'menu_order',
                            'sort_order'       => 'ASC'
                        ));
                    }
                    
                    $html .= '<optgroup label="'.ucfirst($type).'">';
					
                    foreach($type_array as $option)
					{
                        $html .= '<option value="#page:'.$option->ID.'"'.($existing[$attr] == '#page:'.$option->ID ? ' selected="selected"' : '').'>'.($option->post_parent != 0 ? " - ": "").$option->post_title.'</option>';
                    }
					
                    $html .= '</optgroup>';
                }
				
                $html .= '</select>';

				return $html;
			},
			"dynamic_content" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				$type = (isset($tags["contenttype"])) ? $tags["contenttype"] : '';
				if(!empty($type)) $content_types = array($type => $type);
				else
				{
					$content_types = get_post_types();
				
					unset($content_types['attachment']);
					unset($content_types['revision']);
					unset($content_types['nav_menu_item']);
					unset($content_types['custom_css']);
					unset($content_types['customize_changeset']);
				}

                $html .= '<label>'.$attr_title.'</label>';
                $html .= '<select name="'.$fieldname.'['.$attr.']'.'">';
                $html .= '<option value="'.$value.'">None</option>';

                foreach($content_types as $index => $type)
				{
                    if($type == "page")
					{
                        $type_array = get_pages(array(
                            'posts_per_page'   => -1,
                            'sort_column'      => 'menu_order',
                            'sort_order'       => 'ASC'
                        ));
                    }
					else
					{
						$type_array = get_posts(array(
							'posts_per_page'   => -1,
							'orderby'          => 'menu_order',
							'order'            => 'ASC',
							'post_type'        => $type
						));
					}
                    
                    if(count($content_types) > 1)
						$html .= '<optgroup label="'.ucfirst($type).'">';
					
                    foreach($type_array as $option)
					{
                        $html .= '<option value="#content:'.$option->ID.'"'.($existing[$attr] == '#content:'.$option->ID ? ' selected="selected"' : '').'>'.($option->post_parent != 0 ? " - ": "").$option->post_title.'</option>';
                    }
					
					if(count($content_types) > 1)
                    	$html .= '</optgroup>';
                }
				
                $html .= '</select>';

				return $html;
			},
			"dynamic_contentboxes" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				$html = '';
				$type = (isset($tags["contenttype"])) ? $tags["contenttype"] : '';

				if(!empty($type))
				{
					$type = $tags["contenttype"];
					$selected = (isset($existing[$attr]) && is_array($existing[$attr])) ? $existing[$attr] : array();

					if($type == "page")
					{
						$type_array = get_pages(array(
							'posts_per_page'   => -1,
							'sort_column'          => 'menu_order',
							'sort_order'            => 'ASC'
						));
					}
					else
					{
						$type_array = get_posts(array(
							'posts_per_page'   => -1,
							'orderby'          => 'menu_order',
							'order'            => 'ASC',
							'post_type'        => $type
						));
					}

					$html .= '<label>' . $attr_title . ':</label>';
					foreach($type_array as $option)
					{
						$html .= '<input name="'.$fieldname.'['.$attr.'][]'.'" class="checkbox" type="checkbox" value="'.$option->ID.'"'.((in_array($option->ID, $selected)) ? ' checked="checked"' : '').'/>'.$option->post_title . '<br />';
					}
				}

				return $html;
			},
			"dynamic_child" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				$content_types = ['page'];
				
                $html .= '<label>'.$attr_title.'</label>';
                $html .= '<select name="'.$fieldname.'['.$attr.']'.'">';
                $html .= '<option value="'.$value.'">None</option>';

				global $post;
				$page_id = $post->ID;
				
                foreach($content_types as $index => $type)
				{
                    $type_array = get_posts(array(
                        'posts_per_page'   => -1,
                        'orderby'          => 'menu_order',
                        'order'            => 'ASC',
                        'post_type'        => $type
                    ));

                    if($type == "page")
					{
                        $type_array = get_pages(array(
                            'posts_per_page' => -1,
                            'sort_column'  => 'menu_order',
                            'sort_order' => 'ASC',
							'parent' => $page_id
                        ));
                    }
					
                    if(count($content_types) != 1)                    
						$html .= '<optgroup label="'.ucfirst($type).'">';
                    
					foreach($type_array as $option)
					{
                        $html .= '<option value="#page:'.$option->ID.'"'.($existing[$attr] == '#page:'.$option->ID ? ' selected="selected"' : '').'>'.($option->post_parent != 0 ? " - ": "").$option->post_title.'</option>';
                    }
					
                    if(count($content_types) != 1)    
                    	$html .= '</optgroup>';
                }
				
                $html .= '</select>';

				return $html;
			},
			"iframe" => function($attr,$value,$attr_title,$fieldname,$existing,$tags)
			{
				//$value = isset($existing[$attr]) ? $existing[$attr] : $vlaue;
				$value = isset($existing[$attr]) ? $existing[$attr] : "";
				
                if($fieldname == "site_variables")
                    $value = isset($existing[$attr]) ? $existing[$attr] : $value;
				
				$html = '<label>'.$attr_title.':</label>';
				$html .= '<textarea name="'.$fieldname.'['.$attr.']">'.$value.'</textarea>';
				$html .= '<hr class="clear" />';
				
				return $html;
			}
		);
		
		$field_html = custom_admin_project_changes::update_field_html($field_html);
		
		$attr_title = isset($tags['title']) ? $tags['title'] : $attr;
		$attr_title = str_replace("site_","",$attr_title);
		$attr_title = ucfirst(str_replace("_"," ",$attr_title)); // Nice title for display purposes
		
		if(isset($field_html[$fieldtype]) && is_callable($field_html[$fieldtype]))
		{
			$html = $field_html[$fieldtype]($attr,$value,$attr_title,$fieldname,$existing,$tags);
		}
		else // DEFAULT Text Field
		{
			$value = isset($existing[$attr]) ? $existing[$attr] : "";
			
			if($fieldname == "site_variables")
                $value = isset($existing[$attr]) ? $existing[$attr] : $value;

			$field_id = str_replace(array('[',']'),array("_",""),$fieldname)."_".$attr;
			
			$html = '<label for="'.$field_id.'">'.$attr_title.':</label>';
			$html .= '<input id="'.$field_id.'" type="'.$fieldtype.'" name="'.$fieldname.'['.$attr.']" value="'.$value.'" '.(isset($tags['required'])?"required ":"").' />';
		}
		
		return $html;
	}
	
	public function block_fields_from_html($data)
	{
		// Re get the html file?
		global $html_files;
		$block_config = $data;

		if(@file_exists($html_files->{$data['_type']}[$data['_name']]))
		{
			$block_html = file_get_contents($html_files->{$data['_type']}[$data['_name']]);
			$block_config = get_json_from_html($block_html);
			$block_config['_type'] = $data['_type'];
			$block_config['_name'] = $data['_name'];
		}

		return $block_config;
	}
		
	private function block_fields($attr,$data,$attr_title,$fieldname,$existing,$tags)
	{
		$html = "";

		$block_type = $data['_type'];
		
		$block_html = array(
			"module_group" => function($attr,$data,$fieldname,$existing)
			{
				// RE get the html fields
				//$data = $this->block_fields_from_html($data);
				
				if(isset($existing['_modules']))
				{
					$modules = $existing['_modules'];
					$existing_modules = $existing['_modules'];
				}
				else
				{
					//$modules = $data['_modules'];
					//$existing_modules = array();
				}
				
				unset($data['_modules']);

				$html = $this->create_html($fieldname, $data, $existing);
				$html .= '<h2>Modules:</h2>';
				$html .= '<div id="'.$attr.'_modules">';

				$module_count = 1;
				if(!empty($modules))
				{
					// re-order the existing module names, just in case the order was played around with
					
					$temp_existing_modules = array();
					$module_count = 1;
					
					foreach($existing_modules as $index => $module)
					{
						$module_attr = "module_".$module_count;
						$temp_existing_modules[$module_attr] = $module;
						
						$module_count++;
					}
					
					$existing_modules = $temp_existing_modules;
					$module_count = 1;
					
					foreach($modules as $index => $module)
					{
						$module_attr = "module_".$module_count;
						$attr_title = "";
						$module_fieldname = $fieldname.'[_modules]';
						
						$module['_extras']['in_group'] = true;
						$html .= $this->block_fields($module_attr,$module,$attr_title,$module_fieldname,$existing_modules[$module_attr],array());

						$module_count++;
					}
				}
				
				$html .= '</div>';

				if(count($data['_allowed_modules']))
				{
					$html .= '<br/><hr/><br/>';
					$html .= '<span class="module__add__label">Module type: </span>';
					$html .= '<select id="'.$attr.'_file" class="module__add__select">';

					foreach($data['_allowed_modules'] as $index => $module)
					{
						$module_title = ucfirst(str_replace("_"," ",$module)); // Nice title for display purposes
						$html .= '<option value="'.$module.'">'.$module_title.'</option>';
					}

					$html .= '</select>';
					$html .= '<a href="#add_module_modal" class="button button-primary button-large module__add__button" title="Add a Module" data-type="module" data-select="'.$attr.'_file" data-count="'.$module_count.'" data-fieldname="'.$fieldname.'[_modules][module_'.$module_count.']'.'" data-append-to="'.$attr.'_modules">Add Module</a>';
					$html .= '<hr class="clear" />';
				}
				
				return $html;
			},
		  	"module" => function($attr,$data,$fieldname,$existing)
			{
				$html = "";

				if($attr != "ajax")
					$fieldname = $fieldname.'['.$attr.']';

				if(!empty($existing[$attr]))
					$existing = $existing[$attr];

				$module_class = isset($data['_extras']['module_class']) ? $data['_extras']['module_class'] : "wp__module minified";
				$in_group = isset($data['_extras']['in_group']) ? true : false;
				$is_open = isset($data['_extras']['is_open']) && $data['_extras']['is_open'] == true ? true : false;

				if($is_open == true)
				{
					$module_class = str_replace("minified","",$module_class);
				}

				// RE get the html fields
				$data = $this->block_fields_from_html($data);
				
				$open = false;
				//$open = isset($existing['_extras']['_wp__open__save']) || isset($existing[$name]['_extras']['_wp__open__save']) ? true : "";
				$module_name = $data['_name'];
			
				$module_title = ucfirst(str_replace("_"," ",$module_name)); // Nice title for display purposes

				$html .= '<div class="'.$module_class.'">';
				$html .= '<input type="hidden" name="'.$fieldname.'[_extras][is_open]" value="'.$is_open.'" class="wp__open__save" />';
	
                if(isset($existing['title']) && $existing['title'] != "")
                    $module_title .= " <span>(".$existing['title'].")</span>";
                
				$html .= '<h2 class="wp__module__title">'.$module_title.'</h2><a href="#min" class="module_minify"></a><a href="#expand" class="module_expand"></a>';

				if($in_group)
				{
					$html .= '<a href="#delete" class="module_delete"></a><a href="#down" class="module_down"></a><a href="#up" class="module_up"></a>';
				}

				$html .= '<hr style="clear:both;"/>';
				$html .= $this->create_html($fieldname, $data, $existing);
				$html .= '<hr class="clear"></div>';
				
				return $html;
			},
		  	"element_group" => function($attr,$data,$fieldname,$existing)
			{
				// RE get the html fields
				$data = $this->block_fields_from_html($data);
				
				if(!empty($existing[$attr]))
					$existing = $existing[$attr];
				
				if(isset($existing['_elements']))
				{
					$elements = $existing['_elements'];
					$existing_elements = $existing['_elements'];
				}
				else
				{
					//$elements = $data['_elements'];
					//$existing_elements = array();
				}
			
				unset($data['_elements']);

				$html = $this->create_html($fieldname.'['.$attr.']', $data, $existing);
				$html .= '<h2>Elements:</h2>';
				$html .= '<div id="'.$attr.'_elements">';

				$element_count = 1;
                
				if(!empty($elements))
				{
					// re-order the existing module names, just in case the order was played around with
					
					$temp_existing_elements = array();
					$element_count = 1;
					foreach($existing_elements as $index => $element)
					{
						$element_attr = "element_".$element_count;
						$temp_existing_elements[$element_attr] = $element;
						
						$element_count++;
					}
					
					$existing_elements = $temp_existing_elements;
					
					$element_count = 1;
					foreach($elements as $index => $element)
					{
						$element_attr = "element_".$element_count;
						$attr_title = "";
						$element_fieldname = $fieldname.'['.$attr.'][_elements]';
						
						$element['_extras']['in_group'] = true;
						
						$html .= $this->block_fields($element_attr,$element,$attr_title,$element_fieldname,$existing_elements[$element_attr],array());

						$element_count++;
					}

				}
				$html .= '</div>';

				if(count($data['_allowed_elements']))
				{
					$html .= '<br/><hr/><br/>';
					$html .= '<span class="element__add__label">Element type: </span>';
					$html .= '<select id="'.$attr.'_file" class="element__add__select">';

					foreach($data['_allowed_elements'] as $index => $element)
					{
						$element_title = ucfirst(str_replace("_"," ",$element)); // Nice title for display purposes
						$html .= '<option value="'.$element.'">'.$element_title.'</option>';
					}

					$html .= '</select>';
					$html .= '<a href="#add_element_modal" class="button button-primary button-large element__add__button" title="Add a Element" data-type="element" data-select="'.$attr.'_file" data-count="'.$element_count.'" data-fieldname="'.$fieldname.'['.$attr.'][_elements][element_'.$element_count.']'.'" data-append-to="'.$attr.'_elements">Add Element</a>';
					$html .= '<hr class="clear" />';
				}

				return $html;
			},
		  	"element_feed" => function($attr,$data,$fieldname,$existing)
			{
				$data = $this->block_fields_from_html($data);

				unset($data['_elements']);

				$fieldname = $fieldname.'['.$attr.']';

				if(!empty($existing[$attr]))
					$existing = $existing[$attr];

				$html = "";
				
				if(!isset($existing['_per_page']) && isset($data['_per_page']))
				{
					$existing['_per_page'] = $data['_per_page'];
				}

				$html .= $this->create_html($fieldname, $data, $existing);

				if(isset($data['_content_types']))
				{
					$html .= '<label>Content Types:</label>';
					$option_num = 1;
					
					foreach($data['_content_types'] as $type)
					{
						$option_id = str_replace(array("[","]"),array("_","_"),$fieldname."[_content_types]".$option_num);

						$html .= '<input class="checkbox" id="'.$option_id.'" type="checkbox" name="'.$fieldname.'[_content_types][]" value="'.$type.'" '.(isset($existing['_content_types']) && !in_array($type,$existing['_content_types']) ? '' : 'checked ').'/>';
						$html .= '<label class="forCheckbox" for="'.$option_id.'">'.ucfirst($type).'</label><br/>';

						$option_num++;
					}
				}

				$html .= '<label>Order By:</label>';
				$html .= '<select name="'.$fieldname.'[_orderby]">';
				$html .= '<option value="menu_order"'.($existing['_orderby'] == "menu_order" ? ' selected="selected"' : '').'>Menu</option>';
				$html .= '<option value="date" '.($existing['_orderby'] == "date" ? ' selected="selected"' : '').'>Date</option>';
				$html .= '</select>';

				$html .= '<label>Order:</label>';
				$html .= '<select name="'.$fieldname.'[_order]">';
				$html .= '<option value="desc"'.($existing['_order'] == "desc" ? ' selected="selected"' : '').'>DESC</option>';
				$html .= '<option value="asc" '.($existing['_order'] == "asc" ? ' selected="selected"' : '').'>ASC</option>';
				$html .= '</select>';


				$html .= '<label>Per Page:</label>';

				$html .= '<input type="number" min="-1" max="100" name="'.$fieldname.'[_per_page]" value="'.(isset($existing['_per_page']) ? $existing['_per_page'] : "-1").'" />';


//				$html .= '<label>Parent:</label>';
//				$html .= '<select name="'.$fieldname.'[_parent]">';
//				$html .= '<option value="-1">None</option>';
//
//				$content_types = isset($existing['_content_types']) ? $existing['_content_types'] : $data['_content_types'];
//
//				foreach($content_types as $index => $type){
//
//
//					$type_array = get_posts(array(
//						'posts_per_page'   => -1,
//						'orderby'          => 'menu_oder',
//						'order'            => 'ASC',
//						'post_type'        => $type
//					));
//
//					$html .= '<optgroup label="'.ucfirst($type).'">';
//					foreach($type_array as $option){
//
//
//						$html .= '<option value="'.$option->ID.'"'.($existing['_parent'] == $option->ID ? ' selected="selected"' : '').'>'.$option->post_title.'</option>';
//
//					}
//					$html .= '</optgroup>';
//				}
//				$html .= '</select>';


				//$html.= print_fields($data,$existing[$name],$fieldname);


				
				
				return $html;
			},
		  	"element" => function($attr,$data,$fieldname,$existing)
			{
				$html = "";
								
				if($attr != "ajax")
					$fieldname = $fieldname.'['.$attr.']';
				
				if(!empty($existing[$attr]))
					$existing = $existing[$attr];

				$module_class = isset($data['_extras']['module_class']) ? $data['_extras']['module_class'] : "wp__element minified";
				$in_group = isset($data['_extras']['in_group']) ? true : false;
				$is_open = isset($data['_extras']['is_open']) && $data['_extras']['is_open'] == true ? true : false;

				if($is_open == true)
				{
					$module_class = str_replace("minified","",$module_class);
				}

				// RE get the html fields
				$data = $this->block_fields_from_html($data);

				$open = false;
				//$open = isset($existing['_extras']['_wp__open__save']) || isset($existing[$name]['_extras']['_wp__open__save']) ? true : "";
				$module_name = $data['_name'];
			
				$module_title = ucfirst(str_replace("_"," ",$module_name)); // Nice title for display purposes

				$html .= '<div class="'.$module_class.'">';
				$html .= '<input type="hidden" name="'.$fieldname.'[_extras][is_open]" value="'.$is_open.'" class="wp__open__save" />';

                if(isset($existing['title']))
                    $module_title .= " <span>(".$existing['title'].")</span>";
                
				$html .= '<h2 class="wp__module__title">'.$module_title.'</h2><a href="#min" class="module_minify"></a><a href="#expand" class="module_expand"></a>';

				if($in_group)
				{
					$html .= '<a href="#delete" class="module_delete"></a><a href="#down" class="module_down"></a><a href="#up" class="module_up"></a>';
				}

				$html .= '<hr style="clear:both;"/>';
				$html .= $this->create_html($fieldname, $data, $existing);
				$html .= '<hr class="clear"></div>';

				return $html;
			},
		  	"loop" => function($attr,$data,$fieldname,$existing)
			{
				if($attr != "ajax")
					$fieldname = $fieldname.'['.$attr.']';
			
				if(!empty($existing[$attr]))
					$existing = $existing[$attr];
				
				$loop_title = ucfirst(str_replace("_"," ",$attr)); // Nice title for display purposes
			
				$module_class = "wp__module minified";

				$open = isset($existing['_extras']['_wp__open__save']) || isset($existing[$attr]['_extras']['_wp__open__save']) ? true : "";

				if($open == true)
				{
					$module_class = str_replace("minified","",$module_class);
				}

				$data = $this->block_fields_from_html($data);
			
				unset($data['_extras']);

				$html .= '<div class="'.$module_class.'">';
				$html .= '<input type="hidden" name="'.$fieldname.'[_extras][_wp__open__save]" value="'.$open.'" class="wp__open__save" />';

                if(isset($existing['title']))
                    $loop_title .= " <span>(".$existing['title'].")</span>";

				$html .= '<h2 class="wp__module__title">'.$loop_title.'</h2><a href="#min" class="module_minify"></a><a href="#expand" class="module_expand"></a>';
				$html .= '<hr style="clear:both;"/>';

				if(isset($data['_fields']))
				{
					$item_fields = $data['_fields'];
					unset($data['_fields']);
				}

				if(isset($existing['_items']))
				{
					$items = $existing['_items'];
					$existing_items = $existing['_items'];
				}
				else
				{
					//$items = $data['_items'];
					//$existing_items = array();
				}

				unset($data['_items']);

				//$html .= $this->create_html($fieldname, $data, $existing);
				
				$item_count = 1;

				$html .= '<label>Items:</label>';
				$html .= '<div class="wp_element_group">';

				if(!empty($items))
				{
					foreach($items as $existing_item)
					{
						$html .= '<div class="wp__element wp__loop__item minified">';
						$html .= '<h2 class="wp__module__title">Item</h2><a href="#min" class="module_minify"></a><a href="#expand" class="module_expand"></a>';
						$html .= '<a href="#delete" class="module_delete"></a><a href="#down" class="module_down"></a><a href="#up" class="module_up"></a>';
						$html .= '<hr style="clear:both;"/>';
						$html .= $this->create_html($fieldname.'[_items][item_'.$item_count.']', $item_fields, $existing_item);
						$html .= '</div>';

						$item_count++;
					}
				}

				$html .= '<div class="wp__element wp__loop__item item_new">';
				$html .= '<h2 class="wp__module__title">Item</h2><a href="#min" class="module_minify"></a><a href="#expand" class="module_expand"></a>';
				$html .= '<a href="#delete" class="module_delete"></a><a href="#down" class="module_down"></a><a href="#up" class="module_up"></a>';
				$html .= '<hr style="clear:both;"/>';
				$html .= $this->create_html("_".$fieldname.'[_items][item_'.$item_count.']', $item_fields, array());
				$html .= '</div>';

				$html .= '<hr class="clear" />';
				
				$html .= '<a href="#add_item" class="button button-primary button-large item__add__button" data-count="'.$item_count.'">Add Item</a>';

				$html .= '<hr class="clear" />';
				$html .= '</div>';

				$html .= '<hr class="clear" />';

				$html .= $this->create_html($fieldname, $data, $existing);
			
				$html .= '</div>';

				return $html;
			}
		);

		$block_html = custom_admin_project_changes::update_block_html($block_html);

		if(is_callable($block_html[$block_type]))
		{
			$html = $block_html[$block_type]($attr,$data,$fieldname,$existing);
		}

		return $html;
	}
	
	public function create_html($fieldname, $data, $existing)
	{
		$html = "";

		foreach($data as $attr => $value)
		{
			preg_match('/<!--([\s\S]*?)-->/',$attr,$arr_comment);
			$attr = trim(preg_replace('/<!--([\s\S]*?)-->/', "", $attr));
			$comment = "";
			$tags = array();
			
			if($arr_comment)
			{
				$comment = $arr_comment[1];
			
				$temp_tags = array();

				// Lets now look for tags

				$comment = preg_replace_callback(
					'/(#[\S]*)/',
					function($match)
					{ 
						global $temp_tags;
						$temp_tags[] = $match[1];
						
						return "";
					},
					$comment
				);
				
				global $temp_tags;

				if($temp_tags)
				{
					foreach($temp_tags as $index => $tag)
					{
						$tag = str_replace("#","",$tag);
						$tag = explode(":",$tag);

						$tag_value = isset($tag[1]) ? $tag[1] : "true";
						$tag = $tag[0];

						$tags[$tag] = $tag_value; // Create value;
					}
				}
				
				$temp_tags = array();
				
				if(trim($comment) == "")
					unset($comment);
			}

			$fieldtype = $this->test_fields($attr,$value);

            if(isset($tags['fieldtype']))
			{
                $fieldtype = $tags['fieldtype'];
            }
			
			$html .= $this->create_field_html($attr,$value,$fieldtype,$fieldname,$existing,$tags);
			
			if($comment != "")
			{
				$html .= "<p class='comment'>".$comment."</p>";
			}
		}
		
		return $html;
	}
}
?>