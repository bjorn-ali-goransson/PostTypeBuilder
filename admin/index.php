<?php

namespace PostTypeBuilder;

require "post-screen.php";
require "entity-types-screen.php";

function posttypebuilder_get_default_label($entity, $name){
	$default_label = $name;
	
	$default_label = str_replace("_", " ", $default_label);
	
	if(is_array($entity->$name)){
		if(posttypebuilder_string_ends_with($default_label, " ids")){
			$default_label = posttypebuilder_string_trim_from_end($default_label, " ids");
			$default_label .= "s";
		}
	} else {
		if(posttypebuilder_string_ends_with($default_label, " id")){
			$default_label = posttypebuilder_string_trim_from_end($default_label, "id");
		} else {
			$default_label = preg_replace("@\bid\b@", "ID", $default_label);
		}
	}
	
	$default_label = ucwords($default_label);
	
	return $default_label;
}

add_action('wp_ajax_posttypebuilder_create_default_directory_structure', function(){
	if(!is_dir(POSTTYPEBUILDER_CLASSPATH)){
		mkdir(POSTTYPEBUILDER_CLASSPATH);
	}
	
	header("Location: tools.php?page=entity-types");
});

add_action('wp_ajax_posttypebuilder_add_example_entity_types', function(){
	if(!is_dir(POSTTYPEBUILDER_CLASSPATH)){
		die("PostTypeBuilder fatal error: Directory " . POSTTYPEBUILDER_CLASSPATH . " does not exist");
	}
	
	if(count(glob(POSTTYPEBUILDER_CLASSPATH . "*.php")) > 0){
		die("Can't create example entity types if PostTypeBuilder classpath is not empty");
	}
	
	WP_Filesystem();
	
	unzip_file(__DIR__ . "/example-entity-types.zip", POSTTYPEBUILDER_CLASSPATH);
	
	header("Location: tools.php?page=entity-types");
});