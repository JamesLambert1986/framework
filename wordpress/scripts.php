<?php 

$temp_dir = get_bloginfo('template_directory');


?>

<script>

jQuery(document).ready(function()
{
	//console.log(tinymce);
	
	
	// Move some of the page fields to the page attributes box
	jQuery('#page_variables_navigation').prependTo("#pageparentdiv .inside");
	
	jQuery('[for="page_variables_navigation"]').prependTo("#pageparentdiv .inside").addClass('post-attributes-label').wrap('<p class="post-attributes-label-wrapper"></p>');
	
	jQuery('#page_variables_page_type').prependTo("#pageparentdiv .inside");
	
	jQuery('[for="page_variables_page_type"]').prependTo("#pageparentdiv .inside").addClass('post-attributes-label').wrap('<p class="post-attributes-label-wrapper"></p>');
	
	
	
	// If a box is empy then remove it
	if(jQuery('#page_meta_box .wp__variables__inner').children().length == 0){
		jQuery('#page_meta_box').hide();
	}
	
	
	
	jQuery('body').on('change','#page_variables_page_type',function(e){
		
		if (confirm('Are you sure you want to change the page type template? This may cause some data to be lost.')) {
			// Save it!
			jQuery('#post').append('<input type="hidden" name="change_of_page_type" value="true" />');
			jQuery('#post').trigger( "submit" );
		} else {
			// Do nothing!
			e.preventDefault();
		}
	});
	
	
	
	
	
	jQuery('body').on('click','.module__add__button,.element__add__button',function(e){
		
		e.preventDefault();
		$link = jQuery(this);
		
		$module = $link.parent(); // context
		
		console.log($module);
		
		$type = $link.attr('data-type');
		$fieldname = $link.attr('data-fieldname');
		$name = jQuery('#'+$link.attr('data-select'),$module).val()
		
		$count = $link.attr('data-count');
		$append_to = $link.attr('data-append-to');
		
		
		jQuery.post('<?php echo get_bloginfo('template_directory').'/ajax.php'; ?>', {
			ajax: true,
			_type: $type,
			_name: $name,
			fieldname: $fieldname,
			post_id:<?php echo isset($_GET['post']) ? $_GET['post'] : 0; ?>
		}, 
		function(data, status){
			if(status == "success"){
	
				jQuery("#"+$append_to,$module).append(data);
				
				
				jQuery($append_to,$module).append(data);
	
				$fieldname = $fieldname.replace($type+"_"+parseInt($count),$type+"_"+(parseInt($count)+1));
				
				$link.attr('data-count',parseInt($count) + 1);
				$link.attr('data-fieldname',$fieldname);				
				reinit_tinymce();
   
			}
		});
		
	});
	
	
	jQuery('body').on('click','.item__add__button',function(e){
		
		e.preventDefault();
		
		$link = jQuery(this);
		$count = $link.attr('data-count');
		
		
		
		
		$placeholder = $link.parent().find('.item_new');
		$new = $placeholder.clone();
		
		$new.removeClass('item_new');
		
		// remove tinymce inline hidden input fields
		jQuery(".rich_text_bold",$new).next("input").remove();
		
		
		$placeholder.before($new);
	
		$placeholder.find('.wp-editor-area').each(function(index){
		
			id = jQuery(this).attr('id');
			name = jQuery(this).attr('name');
			
			
			parent_id = jQuery(this).closest('.wp-editor-wrap').attr('id');
			
			console.log(parent_id);
			
	
			if(parent_id == null){

				
				parent_id = jQuery(this).parent().attr('id');
			
			console.log(parent_id);
			
				
				jQuery('#'+parent_id).replaceWith('<div><textarea name="'+name+'" id="'+id+'" class="wp-editor-area"></textarea></div>');
			}
			else {

				jQuery('#'+parent_id).prev('.wp-media-buttons').remove();
				
				jQuery('#'+parent_id).replaceWith('<div><textarea name="'+name+'" id="'+id+'" class="wp-editor-area"></textarea></div>');
			}

			
			
			
			
			//tinymce.execCommand('mceRemoveControl', true, "#"+id);
			//tinymce.remove("#wp-page_variables_modules___modules__module_1__list___items__item_1__content-wrap");
			//jQuery("#"+id).tinymce().remove();
		});
		
		
		
		
		$placeholder.find('[name]').each(function(index){
			
			name = jQuery(this).attr('name');
			name = name.replace('item_'+parseInt($count), 'item_'+(parseInt($count) + 1));
	
			jQuery(this).attr('name', name);
		});
		
		$placeholder.find('[id]').each(function(index){
			
			id = jQuery(this).attr('id');
			id = id.replace('item_'+parseInt($count), 'item_'+(parseInt($count) + 1));
			jQuery(this).attr('id', id);
		});
		
		$placeholder.find('[for]').each(function(index){
			
			for_id = jQuery(this).attr('for');
			for_id = for_id.replace('item_'+parseInt($count), 'item_'+(parseInt($count) + 1));
			jQuery(this).attr('id', id);
		});
		
		
		$new.find('[name]').each(function(index){
			
			name = jQuery(this).attr('name');
			name = name.replace('_site_variables', 'site_variables');
	
			jQuery(this).attr('name', name);
		});
		
		$new.find('[id]').each(function(index){
			
			id = jQuery(this).attr('id');
			id = id.replace('_site_variables', 'site_variables');
			jQuery(this).attr('id', id);
		});
		
		$new.find('[name]').each(function(index){
			
			name = jQuery(this).attr('name');
			name = name.replace('_page_variables', 'page_variables');
	
			jQuery(this).attr('name', name);
		});
		
		$new.find('[id]').each(function(index){
			
			id = jQuery(this).attr('id');
			id = id.replace('_page_variables', 'page_variables');
			jQuery(this).attr('id', id);
		});
		
		
		$link.attr('data-count',parseInt($count) + 1);
		
		
		reinit_tinymce();
	});
	
	
	jQuery('body').on('click','.module_delete, .field_delete',function(e){
		
		e.preventDefault();

		if(confirm("Are you sure you want to delete this module/element?"))
			jQuery(this).parent().remove();
	});
	
	jQuery('body').on('click','.module_up, .field_up',function(e){
		
		e.preventDefault();
		
		
		$parent = jQuery(this).parent();
		$before = jQuery(this).parent().prev();
		
		$parent.find('.wp-editor-area').each(function () {
			tinymce.execCommand('mceRemoveEditor', false, jQuery(this).attr('id'));
		});
		
		$parent.insertBefore($before);
		
		reinit_tinymce();
	});
		
	jQuery('body').on('click','.module_down, .field_down',function(e){
		
		e.preventDefault();
		
		$parent = jQuery(this).parent();
		$after= jQuery(this).parent().next();

		$parent.find('.wp-editor-area').each(function () {
			tinymce.execCommand('mceRemoveEditor', false, jQuery(this).attr('id'));
		});
		
		$parent.insertAfter($after);
		
		reinit_tinymce();
	});
	
	jQuery('body').on('click','.module_minify',function(e){
		
		e.preventDefault();
		
		$parent = jQuery(this).parent();
		
		$parent.find('.wp__open__save').val("");
		
		$parent.addClass('minified');
	});
		
	jQuery('body').on('click','.module_expand',function(e){
		
		e.preventDefault();
		
		$parent = jQuery(this).parent();
		
		
		$parent.find('.wp__open__save').val('true');
		
		$parent.removeClass('minified');
	});
	
	jQuery('body').on('change','.module_status',function(e){
		
		e.preventDefault();
		
		$parent = jQuery(this).parent();
		$select = jQuery(this);
		
		if($select.val() == "draft")
			$parent.addClass('draft');
		else
			$parent.removeClass('draft');
	});
	
	/* Media button */
	jQuery('body').on('click','.button--media', function(e)
	{
		var send_attachment_bkp = wp.media.editor.send.attachment;
		var button = 	jQuery(this);
		var type = button.attr('data-type');
		
		wp.media.editor.send.attachment = function(props, attachment)
		{
//			if(attachment.type == "video" && type == "video")
//			{
//				button.closest(".selectmedia").find(".preview").html('<video autoplay loop><source src="' + attachment.url + '" type="' + attachment.mime + '"></video><br />' + attachment.filesizeHumanReadable);
//			}
			if(attachment.type == "image" && type == "image")
			{
				button.closest(".selectmedia").find(".preview_image").html('<img src="' + attachment.url + '" />');
				button.closest(".selectmedia").find("input[type=hidden]").val(attachment.url);
			}
			else if(attachment.subtype == "pdf" && type == "pdf")
			{
				console.log(attachment);
				
				button.closest(".selectmedia").find(".preview_doc").html('<p>' + attachment.filename + ' (' + attachment.filesizeHumanReadable + ')</p>');
				button.closest(".selectmedia").find("input[type=hidden].url").val(attachment.url);
				button.closest(".selectmedia").find("input[type=hidden].name").val(attachment.filename);
				button.closest(".selectmedia").find("input[type=hidden].size").val(attachment.filesizeHumanReadable);
				button.closest(".selectmedia").find("input[type=hidden].type").val(attachment.subtype);
			}
			else
			{
				return false;
			}
			
			if(!button.next().hasClass("button--clear"))
			{
				button.after(' <span class="button--clear button">Remove</span>');
			}

			//button.closest(".selectmedia").find("input[type=hidden]").val(attachment.id);
			
			return true;
		}
	
		wp.media.editor.open(button);
		return false;
	});
	
	jQuery('body').on('click','.button--clear', function(e)
	{
		var button = jQuery(this);
		
		button.closest(".selectmedia").find("input[type=hidden]").val('');
		button.closest(".selectmedia").find(".preview_image").html('');
		button.remove();

		return false;
	});
	
	// inline text area
	tinymce.init({
		selector: 'p.rich_text_bold',
		inline: true,
		plugins: "paste",
		toolbar: 'redo undo | bold',
		menubar: false,
  		paste_as_text: true
	});
		
	// plain text area
	tinymce.init({
		selector: 'textarea.rich_text_mini',
		wpautop: false,
		plugins: "charmap,paste,lists,wordpress,wplink,table",
		toolbar: 'redo undo | bold italic | link | numlist bullist',
	    // spellchecker_rpc_url: 'spellchecker/spellchecker.php',
		menubar: false,
		paste_as_text: true
	});
		
	function reinit_tinymce()
	{
		
		
		
		settings = tinymce.EditorManager.editors[0].settings;
		settings.id = "";
		settings.selector = '.wp-editor-area';
		tinymce.init(settings);

		// inline text area
		tinymce.init({
			selector: 'p.rich_text_bold',
			inline: true,
			plugins: "paste",
			toolbar: 'bold redo undo',
			menubar: false,
  			paste_as_text: true
		});
		
		// plain text area
		tinymce.init({
			selector: 'textarea.rich_text_mini',
			wpautop: false,
			plugins: "charmap,paste,lists,wordpress,wplink,table",
			toolbar: 'redo undo | bold italic | link | numlist bullist',
			// spellchecker_rpc_url: 'spellchecker/spellchecker.php',
			menubar: false,
			paste_as_text: true
		});
	}
});
</script>

