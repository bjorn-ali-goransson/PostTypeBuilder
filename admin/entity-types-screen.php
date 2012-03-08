<?php

add_action('admin_menu', function(){
	add_management_page( "Entity types", "Entity types", "manage_options", "entity-types", function(){
?>
<div class="wrap">
	<div id="icon-tools" class="icon32"><br></div>
	
	<?php
		$page_url = "tools.php?page={$_GET["page"]}";
		
		if(!is_dir(POSTTYPEBUILDER_CLASSPATH)){
			require "entity-types-getting-started-screen.php";
		} else {
			if(array_key_exists('screen', $_GET)){
				$screen = $_GET["screen"];
			} else {
				$screen = "list";
			}
			
			$file = "entity-types-{$screen}-screen.php";
			
			if(is_file(__DIR__ . "/" . $file)){
				require $file;
			} else {
				require "404.php";
			}
		}
	?>
</div>
<?php
	});
});