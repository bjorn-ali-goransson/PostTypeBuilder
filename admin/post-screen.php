<?php

namespace PostTypeBuilder;

use \ReflectionProperty;

require_once "types.classes.php";

add_action('add_meta_boxes', function(){posttypebuilder_add_meta_boxes();});

function posttypebuilder_add_meta_boxes(){
	wp_enqueue_style("posttypebuilder_style", WP_PLUGIN_URL . "/posttypebuilder/admin/style.css", array(), false, "screen");
	wp_enqueue_script("posttypebuilder_script", WP_PLUGIN_URL . "/posttypebuilder/admin/script.js", array("jquery-ui-sortable"));
	
	foreach(PostTypeBuilder::$registered_post_types as $post_type => $class_name){
		$class_meta = PostTypeBuilder::$registered_post_types[$post_type];
		
		$panels_to_display = array(
			0 => array(
				"id" => "properties",
				"label" => "Properties",
				"position" => "normal"
			)
		);
		
		if($class_meta->class_reflector->hasAnnotation("Addendum\\Panels")){
			$annotation = $class_meta->class_reflector->getAnnotation("Addendum\\Panels");
			$panels_to_display = $annotation->value;
		}
		
		foreach($panels_to_display as $panel){
			if(array_key_exists("position", $panel)){
				$context = $panel["position"];
			} else {
				$context = "normal";
			}
			
			$newfunc = create_function('', "\PostTypeBuilder\posttypebuilder_add_meta_box(\"{$panel["id"]}\");");
			
			add_meta_box(
				"posttypebuilder-" . $panel["id"],
				$panel["label"],
				$newfunc,
				$post_type,
				$context,
				"default"
			);
		}
		
	}
}

function posttypebuilder_add_meta_box($panel){
	if(!defined("POSTTYPEBUILDER_HAS_OUTPUT_NONCE")){
		echo "<input type=\"hidden\" name=\"posttypebuilder_nonce\" value=\"" . wp_create_nonce( plugin_basename(__FILE__) ) . "\" />";
		define("POSTTYPEBUILDER_HAS_OUTPUT_NONCE", true);
	}
	
	global $post_type;
	
	$unique_id_index = 0;
	
	if(array_key_exists($post_type, PostTypeBuilder::$registered_post_types)){
		global $post;
		
		$class_meta = PostTypeBuilder::$registered_post_types[$post_type];
		$class_name = $class_meta->qualified_class_name;
		$entity = new $class_name($post);
		
		echo "<table class=\"posttypebuilder-property-table\"><tbody>";
		
		foreach($class_meta->class_reflector->getProperties(ReflectionProperty::IS_PUBLIC) as $property){
			if(!$property->hasAnnotation("Addendum\\Property")){
				continue;
			}
			
			$property_annotation = $property->getAnnotation("Addendum\\Property");
			
			if($property_annotation->panel == $panel){
				posttypebuilder_add_property_to_meta_box($property, $property_annotation, $entity);
			}
		}
		
		echo "</tbody></table>";
	}
}

function posttypebuilder_add_property_to_meta_box($property, $property_annotation, $entity){
	$name = $property->getName();
	$input_id = "posttypebuilder_property_" . $name;
	$is_multiple = is_array($entity->$name);
	
	$row_classes = array();
	$row_classes[] = "posttypebuilder-field";
	if($is_multiple){
		$row_classes[] = "posttypebuilder-field-multiple";
	}
	
	if($property_annotation->visible == true){
		echo "<tr>";
	} else {
		echo "<tr class=\"" . implode(" ", $row_classes) . "\">";
	}
	
	if($property_annotation->label == null){
		$property_annotation->label = posttypebuilder_get_default_label($entity, $name);
	}
	
	if($property_annotation->required){
		echo "<th><label for=\"{$input_id}\" title=\"{$name}\">{$property_annotation->label}<span class=\"posttypebuilder-required\">*</span></label></th>";
	} else {
		echo "<th><label for=\"{$input_id}\" title=\"{$name}\">{$property_annotation->label}</label></th>";
	}
	
	$cell_classes = array();	
	$cell_classes[] = "posttypebuilder-field";
	if($is_multiple){
		$cell_classes[] = "posttypebuilder-field-multiple";
	}
	
	echo "<td class=\"" . implode(" ", $cell_classes) . "\">";
	
	$value = $entity->$name;
	
	if($value == ""){
		$value = null;
	}
	
	if(array_key_exists($property_annotation->type, PostTypeBuilder::$registered_class_names)){
		$type_meta = PostTypeBuilder::$registered_class_names[$property_annotation->type];
		$type_class = $type_meta->qualified_class_name;
		
		$is_entity_reference = true;
	} else {
		$type_class = "Addendum\\" . $property_annotation->type . "PropertyType";
		
		$is_entity_reference = false;
		
		if(!class_exists($type_class)){
			echo "PostTypeBuilder fatal error: Unknown/invalid type {$property_annotation->type} defined for property \${$name} in class " . get_class($entity) . " (it is neither a PostTypeBuilder class nor a PostTypeBuilder data type)";
			
			return;
		}
	}
	
	if($is_multiple){
		echo "<ul>";
		
		foreach($entity->$name as $value){
			if($is_entity_reference){
				$post = get_post($value);
			} else {
				$post = null;
			}
			
			$unique_id_index++;
			
			if($is_entity_reference){
				$edit_link = "<a href=\"/wp-admin/post.php?post={$value}&action=edit\" title=\"Edit instance (opens in new window)\" target=\"_blank\">Edit</a>";
			} else {
				$edit_link = "";
			}
			
			if($is_entity_reference){
				if($post != null){
					$text = "<span class=\"text\" title=\"{$post->post_title} (id: {$value})\">{$post->post_title}</span>";
					$checked = "checked=\"checked\"";
				} else {
					$text = "<span class=\"text instance-does-not-exist\" title=\"(Instance does no longer exist) (id: {$value})\">(Instance does no longer exist)</span>";
					$checked = "";
				}
			} else {
				$text = "<span class=\"text\">{$value}</span>";
				$checked = "checked=\"checked\"";
			}
			
			echo "<li><span class=\"grippy\" title=\"Drag to rearrange\"></span><span class=\"checkbox-wrapper\"><input type=\"checkbox\" name=\"{$input_id}[]\" id=\"{$input_id}_{$unique_id_index}\" value=\"{$value}\" {$checked} title=\"Uncheck to remove on next save\"/></span><label for=\"{$input_id}_{$unique_id_index}\">{$text} {$edit_link}</label></li>";
		}
		
		echo "</ul>";
		
		echo $type_class::generate_input_field($input_id, null, $property, $property_annotation, true);
	} else {
		echo $type_class::generate_input_field($input_id, $value, $property, $property_annotation);
	}
	
	echo "</td>";
	echo "</tr>";
}

add_action('save_post', function($post_id = null, $post_object = null) {
	if (defined('POSTTYPEBUILDER_SAVING_' . $post_id)) {
		return $post_id;
	} else {
		define('POSTTYPEBUILDER_SAVING_' . $post_id, true);
	}
	
	if ((!array_key_exists('posttypebuilder_nonce', $_POST)) || !wp_verify_nonce($_POST['posttypebuilder_nonce'], plugin_basename(__FILE__))) {
		return $post_id;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $post_id;
	}
	
	global $post_type;
	
	if(array_key_exists($post_type, PostTypeBuilder::$registered_post_types)){
		$class_meta = PostTypeBuilder::$registered_post_types[$post_type];
		
		$class_name = $class_meta->qualified_class_name;
		
		if($post_object != null){
			$entity = new $class_name($post_object);
		} else {
			$entity = new $class_name($post_id);
		}
		
		foreach($class_meta->class_reflector->getProperties(ReflectionProperty::IS_PUBLIC) as $property){
			if(!$property->hasAnnotation("Addendum\\Property")){
				continue;
			}
			
			$name = $property->getName();
			$id = "posttypebuilder_property_" . $name;
			
			$value = $_REQUEST[$id];
			
			if(is_array($value)){
				foreach($value as $key => $my_value){
					if($my_value == ""){
						unset($value[$key]);
					}
				}
			}
			
			$entity->$name = $value;
		}
		
		$entity->save();
	}
});
