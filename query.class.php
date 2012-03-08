<?php

namespace PostTypeBuilder;

class Query implements \Iterator, \Countable, \ArrayAccess {
	static $post_object_properties = array(
		"post_title" => true,
		"post_status" => true
	);
	
	private $query_vars = null;
	private $instance = null;
	private $instance_class = null;
	private $result = null;
	private $result_iterator_position = 0;
	
	function __construct($instance){
		$this->instance = $instance;
		$this->instance_class = get_class($instance);
		
		$class_meta = $this->instance->get_class_meta();
		
		$this->query_vars = array(
			"post_type" => $class_meta->post_type,
			"post_status" => "any"
		);
	}
	
	function execute_query(){
		if($this->result != null){
			return;
		}
		
		$this->result = get_posts($this->query_vars);
	}
	
	function where($property, $value){
		$property_split = explode(" ", $property);
		
		if(count($property_split) == 3){
			$property = $property_split[0];
			$operator = $property_split[1];
			$type = $property_split[2];
			$type = trim($type, "()");
		} else if(count($property_split) == 2){
			$property = $property_split[0];
			$operator = $property_split[1];
			$type = "CHAR";
		} else {
			$operator = "=";
			$type = "CHAR";
		}
		
		if(isset($this->instance->$property) || $this->instance->has_defined_property($property)){
			if(!array_key_exists("meta_query", $this->query_vars)){
				$this->query_vars["meta_query"] = array();
			}
			
			$this->query_vars["meta_query"][] = array(
				"key" => $property,
				"compare" => $operator,
				"value" => $value,
				"type" => $type
			);
			
			return $this;
		}
		
		if(array_key_exists($property, Query::$post_object_properties)){
			$this->query_vars[$property] = $value;
			
			return $this;
		}
		
		throw new \Exception("PostTypeBuilder Query error: unknown property \"{$property}\" in class " . get_class($this->instance));
	}
	
	function order_by($property, $direction = "desc"){
		if(isset($this->instance->$property)){
			$this->query_vars["order"] = strtoupper($direction);
			$this->query_vars["orderby"] = "meta_value_num";
			$this->query_vars["meta_key"] = $property;
			
			return $this;
		}
		
		if(array_key_exists($property, Query::$post_object_order_by_properties)){
			throw new \Exception("PostTypeBuilder Query error: Numeric sorting is only available in custom properties (meta). Use order_by_char to use alphabetic sorting");
		}
		
		throw new \Exception("PostTypeBuilder Query error: Unknown property \"{$property}\" in class " . get_class($this->instance));
	}
	
	function order_by_char($property, $direction = "desc"){
		if(isset($this->instance->$property)){
			$this->query_vars["order"] = strtoupper($direction);
			$this->query_vars["orderby"] = "meta_value";
			$this->query_vars["meta_key"] = $property;
			
			return $this;
		}
		
		if(array_key_exists($property, Query::$post_object_order_by_properties)){
			$this->query_vars["order"] = strtoupper($direction);
			$this->query_vars["orderby"] = $value;
			
			return $this;
		}
		
		throw new \Exception("PostTypeBuilder Query error: unknown property \"{$property}\" in class " . get_class($this->instance));
	}
	
	function limit($argument){
		$this->query_vars["numberposts"] = $argument;
		
		return $this;
	}
	
	function get($index = null){
		if($index !== null){
			return $this[$index];
		}
		
		if(isset($this->query_vars["numberposts"]) && $this->query_vars["numberposts"] == 1){
			return $this->first();
		} else {
			return $this->all();
		}
	}
	
	function first(){
		$this->execute_query();
		
		if(isset($this->result[0])){
			return new $this->instance_class($this->result[0]);
		} 
		
		return null;
	}
	
	function all(){
		$this->execute_query();
		
		return $this->result;
	}
	
	public function count(){
		$this->execute_query();
		
        return count($this->result);
	}
	
    function rewind() {
		$this->execute_query();
		
        $this->result_iterator_position = 0;
    }
	
    function current() {
		return new $this->instance_class($this->result[$this->result_iterator_position]);
    }
	
    function key() {
        return $this->result_iterator_position;
    }
	
    function next() {
        ++$this->result_iterator_position;
    }
	
    function valid() {
        return isset($this->result[$this->result_iterator_position]);
    }
	
    public function offsetSet($offset, $value) {
		throw new \Exception("Query arrays are read-only");
    }
	
    public function offsetExists($offset) {
		$this->execute_query();
		
        return isset($this->result[$offset]);
    }
	
    public function offsetUnset($offset) {
		throw new \Exception("Query arrays are read-only");
    }
	
    public function offsetGet($offset) {
		$this->execute_query();
		
        return isset($this->result[$offset]) ? new $this->instance_class($this->result[$offset]) : null;
    }
}