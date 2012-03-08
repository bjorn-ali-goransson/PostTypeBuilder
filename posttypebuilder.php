<?php
/*
Plugin Name: PostTypeBuilder
Version: 0.5
Author: Björn Ali Göransson
Author URI: http://www.bedr.se/
*/

namespace PostTypeBuilder;

use Addendum\ReflectionAnnotatedClass;
use \ReflectionProperty;

require_once "addendum/index.php";
require_once "annotations.classes.php";
require_once "entity.class.php";
require_once "post-meta-manager.class.php";
require_once "query.class.php";

if(!defined("POSTTYPEBUILDER_ENTITIES_NAMESPACE")){
	define("POSTTYPEBUILDER_ENTITIES_NAMESPACE", "MyEntities");
}
define("POSTTYPEBUILDER_RELATIVE_CLASSPATH", 'wp-content/classes/');
define("POSTTYPEBUILDER_CLASSPATH", ABSPATH . POSTTYPEBUILDER_RELATIVE_CLASSPATH);

add_action('init', "\\PostTypeBuilder\\PostTypeBuilder::load_classes");
if(is_admin()) require __DIR__ . "/admin/index.php";

//

class PostTypeBuilder{
	public static $registered_post_types = array();
	public static $registered_class_names = array();
	
	public static $post_ids_currently_being_saved = array();
	
	public static $entity_hooks = array(
		// wp hook arguments: $post_id
		
		"clean_post_cache",
		"delete_post",
		"edit_post",
		"save_post",
		
		// wp hook arguments: $post_id, $post_object
		
		"publish_post",
		"pending_post",
		"draft_post",
		"auto_post",
		"future_post",
		"private_post",
		"inherit_post",
		"trash_post"
	);
	
	public static function load_classes(){
		if(!is_dir(POSTTYPEBUILDER_CLASSPATH)){
			$is_plugins_page = strpos($_SERVER["SCRIPT_FILENAME"], "tools.php");
			
			if(!$is_plugins_page){
				add_action('admin_notices', function(){
					echo "<div class='updated fade'><p>To use the graphical interface for PostTypeBuilder, go to <a href=\"tools.php?page=entity-types\">Tools ← Entity types</a>.</p></div>";
				});
			}
			
			return;
		}
		
		if ($directory_handle = opendir(POSTTYPEBUILDER_CLASSPATH)) {
			while (false !== ($filename = readdir($directory_handle))) {
				if($filename == '.' || $filename == '..'){
					continue;
				}
				
				if(substr($filename, strlen($filename) - 4) != ".php"){
					continue;
				}
				
				$filename_without_extension = substr($filename, 0, strlen($filename) - 4);
				$class_name = preg_replace('/(?:^|_)(.?)/e', "strtoupper('$1')", $filename_without_extension);
				
				PostTypeBuilder::load_class($class_name);
			}
			
			closedir($directory_handle);
		}
	}
	
	public static function load_class($class_name){
		$qualified_class_name = "\\" . POSTTYPEBUILDER_ENTITIES_NAMESPACE . "\\" . $class_name;
		
		if(class_exists($qualified_class_name)){
			add_action('admin_notices', create_function("", "echo \"<div class='updated fade'><p>PostTypeBuilder was going to include {$filename}, which is supposed to contain class <strong>{$class_name}</strong>, but that class was already defined elsewhere.</p></div>\";"));
			
			return;
		}
		
		$filename = strtolower(preg_replace('/([^A-Z])([A-Z])/', "$1_$2", $class_name)) . ".php";
		require_once POSTTYPEBUILDER_CLASSPATH . $filename;
		
		if(!class_exists($qualified_class_name)){
			if(class_exists("\\" . $class_name)){
				add_action('admin_notices', create_function("", "echo \"<div class='updated fade'><p>PostTypeBuilder tried to load class <strong>{$class_name}</strong> in {$filename}, but it needs to be defined in namespace " . POSTTYPEBUILDER_ENTITIES_NAMESPACE . ".</p></div>\";"));
				
				return;
			} else {
				add_action('admin_notices', create_function("", "echo \"<div class='updated fade'><p>PostTypeBuilder tried to load class <strong>{$class_name}</strong> (with namespace MyEntities) in {$filename}, but no such class is defined there.</p></div>\";"));
				
				return;
			}
		}
		
		if(!is_subclass_of($qualified_class_name, "\\PostTypeBuilder\\Entity")){
			add_action('admin_notices', create_function("", "echo \"<div class='updated fade'><p>PostTypeBuilder tried to load class <strong>{$class_name}</strong> in {$filename}, but it needs to derive (extend) from <strong>\\PostTypeBuilder\\Entity</strong>.</p></div>\";"));
			
			return;
		}
		
		PostTypeBuilder::generate_class_meta($class_name);
		
		PostTypeBuilder::register_post_type($class_name);
	}
	
	public static function generate_class_meta($class_name){
		$class_meta = new \stdClass;
		
		$class_meta->class_name = $class_name;
		$class_meta->qualified_class_name = "\\" . POSTTYPEBUILDER_ENTITIES_NAMESPACE . "\\" . $class_meta->class_name;
		
		PostTypeBuilder::$registered_class_names[$class_meta->class_name] = $class_meta;
		PostTypeBuilder::$registered_class_names[$class_meta->qualified_class_name] = $class_meta;
		
		$qualified_class_name_without_initial_slash = substr($class_meta->qualified_class_name, 1);
		
		PostTypeBuilder::$registered_class_names[$qualified_class_name_without_initial_slash] = $class_meta;
		
		$class_meta->class_reflector = new ReflectionAnnotatedClass($class_meta->qualified_class_name);
		
		$default_singular_name = preg_replace('/(.)([A-Z])/e', "'$1' . ' ' . strtolower('$2')", $class_meta->class_name);
		
		$class_meta->post_type = PostTypeBuilder::get_annotation($class_meta, "CanonicalName", strtolower($class_meta->class_name));
		$class_meta->singular_name = PostTypeBuilder::get_annotation($class_meta, "Name", $default_singular_name);
		$class_meta->plural_name = PostTypeBuilder::get_annotation($class_meta, "PluralName", $class_meta->singular_name . "s");
		$class_meta->labels = array_merge(
			array(
				'name' => $class_meta->plural_name,
				'singular_name' => $class_meta->singular_name,
				'add_new' => "Create New",
				'add_new_item' => "Create New " . $class_meta->singular_name . "",
				'edit_item' => "Edit " . $class_meta->singular_name . "",
				'new_item' => "New " . $class_meta->singular_name . "",
				'view_item' => "View " . $class_meta->singular_name . "",
				'search_items' => "Search " . $class_meta->plural_name . "",
				'not_found' => "No " . $class_meta->plural_name . " found",
				'not_found_in_trash' => "No " . $class_meta->plural_name . " found in Trash", 
				'parent_item_colon' => '',
				'menu_name' => $class_meta->plural_name
			),
			PostTypeBuilder::get_annotation($class_meta, "Labels", array())
		);
		$class_meta->options = array_merge(
			array(
				'labels' => $class_meta->labels,
				'public' => true,
				'supports' => array_merge(
					PostTypeBuilder::get_annotation($class_meta, "Supports", array()),
					array("title")
				),
				'publicly_queryable' => $class_meta->class_reflector->hasAnnotation("Addendum\\IsPublic")
			),
			PostTypeBuilder::get_annotation($class_meta, "Options", array())
		);
		
		PostTypeBuilder::$registered_post_types[$class_meta->post_type] = $class_meta;
		
		$class_meta->post_meta_manager = new PostMetaManager($class_meta->qualified_class_name);
		
		return $class_meta;
	}
	
	public static function register_post_type($class_name){
		$class_meta = PostTypeBuilder::$registered_class_names[$class_name];
		
		$qualified_class_name = $class_meta->qualified_class_name;
		
		foreach(PostTypeBuilder::$entity_hooks as $hook_name){
			if(method_exists($qualified_class_name, $hook_name)){
				add_action($hook_name, create_function("\$post_id, \$post_object = null",
				"
					
					if(\$post_object == null){
						\$post_object = get_post(\$post_id);
					}
					
					if(\$post_object->post_type == \"{$class_meta->post_type}\"){
						if(array_key_exists(\$post_id, \PostTypeBuilder\PostTypeBuilder::\$post_ids_currently_being_saved)){
							return;
						} else {
							\PostTypeBuilder\PostTypeBuilder::\$post_ids_currently_being_saved[\$post_id] = true;
						}
						
						{$qualified_class_name}::{$hook_name}(\$post_object);
					}
					
				"));
			}
		}
		
		if($class_meta->post_type != "post" && $class_meta->post_type != "page"){
			register_post_type(
				$class_meta->post_type,
				$class_meta->options
			);
		}
	}
	
	public static function get_annotation($class_meta, $annotation_name, $default_value = false){
		if($class_meta->class_reflector->hasAnnotation("Addendum\\" . $annotation_name)){
			return $class_meta->class_reflector->getAnnotation("Addendum\\" . $annotation_name)->value;
		} else {
			return $default_value;
		}
	}
}


