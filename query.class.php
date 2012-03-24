<?php

namespace PostTypeBuilder;



class Query implements \Iterator, \Countable, \ArrayAccess {
	static $wp_query_parameters = array(
		"author" => true,
		"author_name" => true,
		"cat" => true,
		"category_name" => true,
		"category__and" => true,
		"category__in" => true,
		"category__not_in" => true,
		"tag" => true,
		"tag_id" => true,
		"tag__and" => true,
		"tag__in" => true,
		"tag__not_in" => true,
		"tag_slug__and" => true,
		"tag_slug__in" => true,
		"tax_query" => true,
		"p" => true,
		"name" => true,
		"page_id" => true,
		"pagename" => true,
		"post_parent" => true,
		"post__in" => true,
		"post__not_in" => true,
		"post_type" => true,
		"post_status" => true,
		"posts_per_page" => true,
		"posts_per_archive_page" => true,
		"nopaging" => true,
		"paged" => true,
		"order" => true,
		"orderby" => true,
		"ignore_sticky_posts" => true,
		"year" => true,
		"monthnum" => true,
		"w" => true,
		"day" => true,
		"hour" => true,
		"minute" => true,
		"second" => true,
		"meta_key" => true,
		"meta_value" => true,
		"meta_value_num" => true,
		"meta_compare" => true,
		"meta_query" => true,
		"perm" => true,
		"update_post_meta_cache" => true,
		"cache_results" => true,
		"update_post_term_cache" => true,
	);
	
	static $post_object_properties = array(
		"ID" => true,
		"post_author" => true,
		"post_date" => true,
		"post_date_gmt" => true,
		"post_content" => true,
		"post_title" => true,
		"post_excerpt" => true,
		//"post_status" => true,
		"comment_status" => true,
		"ping_status" => true,
		"post_password" => true,
		"post_name" => true,
		"to_ping" => true,
		"pinged" => true,
		"post_modified" => true,
		"post_modified_gmt" => true,
		"post_content_filtered" => true,
		"post_parent" => true,
		"guid" => true,
		"menu_order" => true,
		//"post_type" => true,
		"post_mime_type" => true,
		"comment_count" => true,
	);
	
	static $post_object_aliases = array(
		"ID" => "p",
		"post_author" => "author",
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
			"post_status" => "any",
			"suppress_filters" => false,
		);
	}
	
	function execute_query(){
		if($this->result != null){
			return;
		}
		
		$defaults = array(
			'offset' => 0,
			'category' => 0,
			'orderby' => 'post_date',
			'order' => 'DESC',
			'include' => array(),
			'exclude' => array(),
			'meta_key' => '',
			'meta_value' =>'',
		);
		
		$r = wp_parse_args( $this->query_vars, $defaults );
		
		if ( ! empty($r['numberposts']) && empty($r['posts_per_page']) )
			$r['posts_per_page'] = $r['numberposts'];
		
		if ( ! empty($r['category']) )
			$r['cat'] = $r['category'];
		
		if ( ! empty($r['include']) ) {
			$incposts = wp_parse_id_list( $r['include'] );
			$r['posts_per_page'] = count($incposts);  // only the number of posts included
			$r['post__in'] = $incposts;
		} elseif ( ! empty($r['exclude']) )
			$r['post__not_in'] = wp_parse_id_list( $r['exclude'] );
		
		$r['ignore_sticky_posts'] = true;
		$r['no_found_rows'] = true;
		
		$wp_query = new \WP_Query;
		
		$filter_parameters = array($this, "where_filter");
		
		add_filter("posts_where", $filter_parameters);
		
		$this->result = $wp_query->query($r);
		
		remove_filter("posts_where", $filter_parameters);
	}
	
	function where_filter($where){
		global $wpdb;
		
		foreach(Query::$post_object_properties as $post_object_property => $_){
			if(array_key_exists($post_object_property, $this->query_vars)){
				$value = esc_sql($this->query_vars[$post_object_property]);
				$where .= " AND {$wpdb->posts}.{$post_object_property} = '{$value}'";
			}
		}
		
		return $where;
	}
	
	function where($property, $value){
		if(isset($this->instance->$property) || $this->instance->has_defined_property($property)){
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