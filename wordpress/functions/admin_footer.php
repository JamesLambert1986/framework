<?php

// Add Custom Footer 
add_filter('admin_footer_text', 'modify_footer_admin');
function modify_footer_admin () {
  echo 'Created by <a href="mailto:digital@gardiner-richardson.com">Gardiner Richardson</a>. ';
  echo 'Powered by <a href="http://WordPress.org">WordPress</a>.';
}
?>