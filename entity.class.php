<?php

namespace PostTypeBuilder;

class Entity {
	public $post_object;
	
	function __construct($post_object_or_post_id = NULL){
		if(is_numeric($post_object_or_post_id)){
			$post_id = $post_object_or_post_id;
			
			$this->load_by_post_id($post_id);
		} else if(is_object($post_object_or_post_id)) {
			$post_object = $post_object_or_post_id;
			
			if($post_object->ID == 0 || is_numeric($post_object->ID) == false){
				throw new \Exception("Invalid post object supplied as entity (" . get_called_class() . ") constructor parameter \"{$post_object_or_post_id}\"");
			}
			
			$this->load_by_post_object($post_object);
		} else if($post_object_or_post_id !== NULL) {
			throw new \Exception("Unknown entity (" . get_called_class() . ") constructor parameter: \"{$post_object_or_post_id}\"");
		} else {
			$this->post_object = new \stdClass();
		}
	}
	
	private function load_by_post_id($post_id){
		$post_object = get_post($post_id);
		
		if(is_object($post_object) == false){
			throw new \Exception("Could not find (" . get_called_class() . ") entity by ID \"{$post_id}\".");
		}
		
		$this->load_by_post_object($post_object);
	}
	
	private function load_by_post_object($post_object){
		$this->post_object = $post_object;
		
		$class_meta = static::get_class_meta();
		
		$class_meta->post_meta_manager->load($this);
	}
	
	public function save(){
		$class_meta = static::get_class_meta();
		
		if($this->ID == 0){
			if(!isset($this->post_object->post_title)){
				$this->post_object->post_title = "Unnamed " . strtolower($class_meta->labels["singular_name"]) . " (#" . strtoupper(substr(md5(time()), 0, 10)) . ")";
			}
			
			if(!isset($this->post_object->post_status)){
				$this->post_object->post_status = "publish";
			}
			
			$this->post_object->post_type = $class_meta->post_type;
			
			$post_id = wp_insert_post($this->post_object);
			
			if($post_id != 0){
				$this->post_object = get_post($post_id);
			}
			
			$class_meta->post_meta_manager->save($this);
			
			return $this;
		}
		
		$class_meta->post_meta_manager->save($this);
		
		if(defined('POSTTYPEBUILDER_SAVING_' . $this->ID)){
			return $this;
		} else {
			define('POSTTYPEBUILDER_SAVING_' . $this->ID, true);
			
			wp_update_post($this->post_object);
		}
		
		return $this;
	}
	
	public function delete(){
		wp_delete_post($this->ID, true);
		
		return $this;
	}
	
	public static function get_class_meta(){
		$class_name = get_called_class();
		$class_meta = PostTypeBuilder::$registered_class_names[$class_name];
		
		return $class_meta;
	}
	
	public function __get($name)
	{
		if(isset($this->post_object->$name)){
			return $this->post_object->$name;
		} else if(isset($this->$name)){
			return $this->$name;
		} else {
			$property_name = $name . "_id";
			
			if(isset($this->$property_name)){
				$class_meta = static::get_class_meta();
				
				$value = $class_meta->post_meta_manager->lazy_load_single($this, $property_name);
				
				return $value;
			}
			
			$property_name = substr($name, 0, strlen($name) - 1) . "_ids";
			
			if(isset($this->$property_name)){
				$class_meta = static::get_class_meta();
				
				$value = $class_meta->post_meta_manager->lazy_load_multiple($this, $property_name);
				
				return $value;
			}
			
			return null;
		}
	}
	
	public function __set($name, $value)
	{
		if(isset($this->post_object->$name)){
			$this->post_object->$name = $value;
		} else {
			$this->$name = $value;
		}
	}
	
	public function generate_input_field($name_and_id, $value = null, $property = null, $property_annotation = null, $many = false){
		global $wpdb;
		
		if($many){
			$many = "[]";
		} else {
			$many = "";
		}
		
		echo "<select name=\"{$name_and_id}{$many}\" id=\"{$name_and_id}\">";
		
		if($value == null){
			echo "<option value=\"\" selected=\"selected\"></option>";
		} else {
			echo "<option value=\"\"></option>";
		}
		
		$class_meta = static::get_class_meta();
		
		foreach($wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_status != 'trash' AND post_status != 'auto-draft' AND post_status != 'inherit' AND post_type = '{$class_meta->post_type}'") as $post){
			if($value == $post->ID){
				echo "<option value=\"{$post->ID}\" selected=\"selected\">{$post->post_title}</option>";
			} else {
				echo "<option value=\"{$post->ID}\">{$post->post_title}</option>";
			}
		}
		
		echo "</select>";
	}
	
	public function has_defined_property($property_name){
		$class_meta = static::get_class_meta();
		
		return $class_meta->post_meta_manager->has_property($property_name);
	}
	
	public function __toString(){
		if($this->post_object != null){
			if($this->post_object->post_title != ""){
				return $this->post_object->post_title;
			} else {
				return "(entity with ID: {$this->post_object->ID})";
			}
		} else {
			return "(entity without ID or title)";
		}
	}
	
	public static function find($limit = -1){
		$class_name = get_called_class();
		$query = new Query(new $class_name());
		
		return $query->limit($limit);
	}
	
	public static function find_one(){
		$class_name = get_called_class();
		$query = new Query(new $class_name());
		
		return $query->limit(1);
	}
	
	public function to_serializable(){
		require_once "to-serializable.php";
		
		return posttypebuilder_to_serializable($this);
	}
}