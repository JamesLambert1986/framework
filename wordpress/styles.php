<style>
@font-face {
  font-family: 'FontAwesome';
  src: url('//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.2.0/fonts/fontawesome-webfont.eot?v=4.2.0');
  src: url('//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.2.0/fonts/fontawesome-webfont.eot?#iefix&v=4.2.0') format('embedded-opentype'),
  url('//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.2.0/fonts/fontawesome-webfont.woff?v=4.2.0') format('woff'),
  url('//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.2.0/fonts/fontawesome-webfont.ttf?v=4.2.0') format('truetype'),
  url('//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.2.0/fonts/fontawesome-webfont.svg?v=4.2.0#fontawesomeregular') format('svg');
  font-weight: normal;
  font-style: normal;
}

/* Basic structure */



hr.clear {
	border: none;
}

#wp-admin-bar-wp-logo
{
    display: none;
}


.wp__module,
.wp__element {
	border: 1px solid #e5e5e5;
	background: #f1f1f1;
	
    padding: 1em;
	
	margin-top: 1em;
	margin-bottom: 1em;
}


.wp__module .wp__module {
	
	background: #f5f5f5;
}

.wp__element {
	background: white;
	}

/*
.wp_element_group {
	border: 1px solid #e5e5e5;
	background: #ffffff;
	
    padding: 1em;
	
	margin-top: 1em;
	margin-bottom: 1em;
}
*/

/*
.wp__variables {
	max-width: 700px;
}


.wp__variables__inner {
	max-width: 700px;
}

*/
    .wp__module__title {
    white-space: nowrap;
    width: calc(100% - 110px);
    overflow: hidden;
        
    text-overflow: ellipsis;
    }
    
    .wp__module__title span {
        color: rgb(170, 0, 0);
    }
    
.wp__variables label
{
	display: block;
	padding-bottom: 5px;
	padding-top: 20px;
	font-weight: bold;
}

.wp__variables .comment
{
	font-size: 16px;
	margin: 0 0 20px 0;
}


.wp__variables input, .wp__variables select
{
	width: 100%;
}



.wp__variables textarea
{
	width: 100%;
	max-width: 100%;
}

.wp__variables .checkbox
{
	margin-left: 3px;
	width: auto;
	display: inline-block;
	margin-top: 1px;
}

.wp__variables label.forCheckbox
{
	display: inline-block;
	width: auto;
	margin-left: 10px;
	font-weight: normal;
	padding-top: 5px;
}

.wp__variables .checkboxTitle,
.wp__module .checkboxTitle
{
	font-weight: bold;
	margin-left: 0px !important;
	width: 100%;
}


body .wp__module__title {
	font-weight: bold!important;
	float: left!important;
	
	    padding: 0!important;
    font-size: 18px!important;
    line-height: 36px!important;
    font-weight: bold;
}



.wp__variables fieldset {
	
	border: 1px solid grey;
	margin-top: 24px;
	padding: 12px 12px 24px 12px;
	position: relative;
	background: #ffffff;
}

/*
#postdivrich,
.wp-editor-wrap,
textarea {
	max-width: 800px;
}
*/


.module_minify,
.module_expand,
.module_delete,
.module_up,
.module_down,
.field_delete,
.field_up,
.field_down {
	
	float: right;
	
	font-family: 'FontAwesome'!important;
	content: "\f041";
    font-size: 18px!important;
    line-height: 36px!important;
	margin-left: 10px;
	text-decoration: none;
	outline: none!important;
	-webkit-box-shadow: none!important;
    box-shadow: none!important;
}

.field_delete,
.field_up,
.field_down {
	margin-top:-25px;
}


.module_expand:before,
.field_expand::before {
	
	content: "\f067";
}

.module_minify:before,
.field_minify:before {
	
	content: "\f068";
}

.module_delete:before,
.field_delete:before {
	
	content: "\f1f8";
}

.module_up:before,
.field_up:before {
	
	content: "\f062";
}

.module_down:before,
.field_down:before {
	
	content: "\f063";
}


/* Add module */

.module__add__label,
.element__add__label {
	line-height: 30px;
}

.module__add__select {
	
}

.module__add__button,
.element__add__button,
.item__add__button {
	float: right!important;
	margin-top:20px!important;
}

@media only screen and (min-width: 568px) { 


	.module__add__label,
	.element__add__label {
		float: left;
		margin-right:20px;
	}

	.module__add__select,
	.element__add__select {
		float: left;
		width: 50%!important;
		max-width: 300px!important;
	}

	.module__add__button,
	.element__add__button,
	.item__add__button {
		float: right!important;
		margin-top:0px!important;
	}

}


/* Site Variables */
	

.site_variables .wp__variables__inner form > label:first-child{

	padding-top: 0;
}






/* Inline editors */

.wp-editor-wrap input {
	width: auto;
}



/*  Minified modules */
.wp__element.minified,
.wp__module.minified {
	overflow: hidden;
}

.wp__module .module_expand,
.wp__module.minified hr,
.wp__module.minified .module_minify,
.wp__module.minified hr ~ *,
.wp__element .module_expand,
.wp__element.minified hr,
.wp__element.minified .module_minify,
.wp__element.minified hr ~ * {
	display: none;
}


.wp__module.minified .module_expand,
.wp__element.minified .module_expand {
	display: inline-block;
}


.wp__module.draft {
	opacity: 0.5;
}



/* Meta box */

#meta_meta_box {
	
	background: #9EA3B0;
    color: white;
    font-weight: normal;
	border-color: #7f838f;
}

#meta_meta_box .hndle {
	
	color: white;
    font-weight: normal;
	border-color: transparent;
}

#meta_meta_box .handlediv {
	color: white;
}

#meta_meta_box .handlediv:hover {
	color: black;
}

#meta_meta_box label{
	
    font-weight: normal;
}

/* Site box */
#site_meta_box {
	
	background: #546A7B;
    color: white;
    font-weight: normal;
	border-color: #303d47;
}

#site_meta_box .hndle {
	
	color: white;
    font-weight: normal;
	border-color: transparent;
}

#site_meta_box .handlediv {
	color: white;
}

#meta_meta_box .handlediv:hover {
	color: black;
}

#site_meta_box label{
	
    font-weight: normal;
}





/* Select Media Box */
.selectmedia
{
	margin-bottom:0px;
	background-color:transparent;
	overflow: auto;
	zoom: 1;
}
.selectmedia .preview_image
{
	float: left;
	width: 100%;
}
.selectmedia .preview_image img
{
	
	
	max-width: 200px;
	height: auto;
}
.selectmedia .preview.file
{
	float:none;
	max-width:none;
	width:100%;
}
.selectmedia .preview *
{
	max-width:100%;
}




.item_new {
	display: none;
}



	#postdivrich {
		display: none;
	}


#postexcerpt h2 span {
	
	color: white;
	
}

#postexcerpt h2 span:before {
	content: "Page Description";
	color: black;
}


#wpbody {
	max-width: 1200px;
}
	
	
	.wp-media-buttons{	
	position: relative;
    z-index: 9999;
	}
	
	.comment {
		color: red;
	}
	

@media screen and (min-width: 1200px){
/*
	#submitdiv {
		position: fixed;
		bottom: 0;
		right: 0;
		z-index: 999999;
		margin: 0;
	}
*/
}


/* editable inline input box */
.mce-tinymce-inline {
	width:160px !important;
	overflow: hidden;
}

p.editable {
	border: 1px solid #ddd;
	-webkit-box-shadow: inset 0 1px 2px rgba(0,0,0,.07);
	box-shadow: inset 0 1px 2px rgba(0,0,0,.07);
	background-color: #fff;
	color: #32373c;
	outline: 0;
	margin: 1px;
	padding: 3px 5px;
	font-size: 14px;
}

p.editable strong, p.editable b
{
	font-weight: bold;
}

</style>