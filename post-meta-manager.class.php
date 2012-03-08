<?php

namespace PostTypeBuilder;

use \ReflectionProperty;

class PostMetaManager{
	private $keys = array();
	
	private $registered_property_types = null;
	
	function __construct($qualified_class_name){
		global $wpdb;
		
		$class_meta = PostTypeBuilder::$registered_class_names[$qualified_class_name];
		
		$qualified_class_name = $class_meta->qualified_class_name;
		
		$instance = new $qualified_class_name();
		
		foreach($class_meta->class_reflector->getProperties(ReflectionProperty::IS_PUBLIC) as $property){
			if(!$property->hasAnnotation("Addendum\\Property")){
				continue;
			}
			
			$key = $property->getName();
			
			$this->keys[] = $key;
		}
		
		//
		
		$this->registered_property_types = array();
		
		foreach($class_meta->class_reflector->getProperties(ReflectionProperty::IS_PUBLIC) as $property){
			if(!$property->hasAnnotation("Addendum\\Property")){
				continue;
			}
			
			$property_annotation = $property->getAnnotation("Addendum\\Property");
			
			$this->registered_property_types[$property->getName()] = $property_annotation->type;
		}
	}
	
	public function save($instance){
		foreach($this->keys as $key){
			$value = $instance->$key;
			
			delete_post_meta($instance->ID, $key);
			add_post_meta($instance->ID, $key, $value);
		}
	}
	
	public function load($instance){
		foreach($this->keys as $key){
			$values = get_post_meta($instance->ID, $key);
			
			foreach($values as $value){
				if(is_string($value)){
					$value = stripslashes_deep($value);
				}
				
				// handle legacy data stored as separate meta entries - otherwise $value is an unserialized array
				if(is_string($value) && is_array($instance->$key)){
					$property = &$instance->$key;
					$property[] = $value;
					continue;
				}
				
				$instance->$key = $value;
			}
		}
	}
	
	public function lazy_load_single($instance, $property_name){
		$id = $instance->$property_name;
		
		if(is_numeric($id) == false){
			return null;
		}
		
		$type_name = $this->registered_property_types[$property_name];
		
		if($type_name == "Image"){
			return get_post($id);
		}
		
		if($type_name == "User"){
			return get_userdata($id);
		}
		
		if(array_key_exists($type_name, PostTypeBuilder::$registered_class_names)){
			$class_meta = PostTypeBuilder::$registered_class_names[$type_name];
			
			$qualified_class_name = $class_meta->qualified_class_name;
			
			return new $qualified_class_name($id);
		}
		
		return null;
	}
	
	public function lazy_load_multiple($instance, $property_name){
		$type_name = $this->registered_property_types[$property_name];
		
		$ids = $instance->$property_name;
		
		if($type_name == "Image"){
			return get_posts(array("post__in" => $ids, "post_type" => "attachment", "post_status" => "any")); // working ?
		}
		
		if($type_name == "User"){
			$users = array();
			
			foreach($ids as $id){
				$users[] = get_userdata($id);
			}
			
			return $users;
		}
		
		if(array_key_exists($type_name, PostTypeBuilder::$registered_class_names)){
			$class_meta = PostTypeBuilder::$registered_class_names[$type_name];
			
			$qualified_class_name = $class_meta->qualified_class_name;
			
			$instances = array();
			
			foreach($ids as $id){
				$instances[] = new $qualified_class_name($id);
			}
			
			return $instances;
		}
		
		return null;
	}
	
	public function has_property($property_name){
		return array_key_exists($property_name, $this->registered_property_types);
	}
}