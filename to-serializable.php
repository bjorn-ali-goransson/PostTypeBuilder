<?php

function posttypebuilder_camelcase($string){
	return preg_replace_callback('/_([a-z])/', function($c){return strtoupper($c[1]);}, $string);
}

function posttypebuilder_to_serializable($object){
	if(is_array($object)){
		$array = array();
		
		foreach($object as $element){
			$array[] = posttypebuilder_to_serializable($element);
		}
		
		return $array;
	}
	
	if(is_object($object) == false){
		if(is_numeric($object)){
			return $object * 1;
		} else {
			return stripslashes_deep($object);
		}
	}
	
	$serialized_object = new stdClass;
	
	foreach(get_class_vars(get_class($object)) as $property_name => $null_value){
		$camel_cased_property_name = posttypebuilder_camelcase($property_name);
		$serialized_object->$camel_cased_property_name = posttypebuilder_to_serializable($object->$property_name);
		
		if(substr($property_name, strlen($property_name) - 3) == "_id"){
			$property_name = substr($property_name, 0, strlen($property_name) - 3);
			$camel_cased_property_name = posttypebuilder_camelcase($property_name);
			$serialized_object->$camel_cased_property_name = posttypebuilder_to_serializable($object->$property_name);
		} else if(substr($property_name, strlen($property_name) - 4) == "_ids"){
			$property_name = substr($property_name, 0, strlen($property_name) - 4) . "s";
			$camel_cased_property_name = posttypebuilder_camelcase($property_name);
			$serialized_object->$camel_cased_property_name = posttypebuilder_to_serializable($object->$property_name);
		}
	}
	
	if(isset($object->post_object)){
		$serialized_object->id = $object->post_object->ID;
		
		if(isset($serialized_object->name) == false){
			$serialized_object->name = $object->post_object->post_title;
		}
		
		unset($serialized_object->postObject);
	}
	
	$is_post_object = isset($object->ID) && isset($object->post_title);
	
	if($is_post_object){
		$serialized_object->id = $object->ID;
		$serialized_object->name = $object->post_title;
		
		if($object->post_type == "attachment"){
			$serialized_object->url = $object->guid;
			
			$imagesize = wp_get_attachment_image_src($object->ID, "full");
			
			if($imagesize){
				$serialized_object->width = $imagesize[1];
				$serialized_object->height = $imagesize[2];
			}
			
			
		}
	}
	
	return $serialized_object;
}