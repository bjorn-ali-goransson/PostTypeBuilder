<?php

namespace Addendum;

abstract class Type{
	public static abstract function generate_input_field($name_and_id, $value = null, $property = null, $property_annotation = null, $many = false);
	public static function to_string($value){
		return $value;
	}
}

class TextPropertyType extends Type{
	public static function generate_input_field($name_and_id, $value = null, $property = null, $property_annotation = null, $many = false){
		if($many){
			$many = "[]";
		} else {
			$many = "";
		}
		
		if($value == null){
			echo "<input type=\"text\" name=\"{$name_and_id}{$many}\" id=\"{$name_and_id}\" class=\"text\"/>";
		} else {
			echo "<input type=\"text\" name=\"{$name_and_id}{$many}\" id=\"{$name_and_id}\" class=\"text\" value=\"" . esc_attr(htmlspecialchars($value)) . "\"/>";
		}
	}
}

class LongTextPropertyType extends Type{
	public static function generate_input_field($name_and_id, $value = null, $property = null, $property_annotation = null, $many = false){
		if($many){
			$many = "[]";
		} else {
			$many = "";
		}
		
		if($value == null){
			echo "<textarea name=\"{$name_and_id}{$many}\" id=\"{$name_and_id}\"></textarea>";
		} else {
			echo "<textarea name=\"{$name_and_id}{$many}\" id=\"{$name_and_id}\">" . esc_attr(htmlspecialchars($value)) . "</textarea>";
		}
	}
}

class BooleanPropertyType extends Type{
	public static function generate_input_field($name_and_id, $value = null, $property = null, $property_annotation = null, $many = false){
		if($many){
			$many = "[]";
		} else {
			$many = "";
		}
		
		if($value == "true"){
			echo "<input type=\"checkbox\" name=\"{$name_and_id}{$many}\" id=\"{$name_and_id}\" value=\"true\" checked=\"checked\"/>";
		} else {
			echo "<input type=\"checkbox\" name=\"{$name_and_id}{$many}\" id=\"{$name_and_id}\" value=\"true\"/>";
		}
	}
}

class GenericPostPropertyType extends Type{
	public static function generate_input_field($name_and_id, $value = null, $property = null, $property_annotation = null, $many = false){
		if($many){
			$many = "[]";
		} else {
			$many = "";
		}
		
		if($value == null){
			echo "<input type=\"text\" name=\"{$name_and_id}{$many}\" id=\"{$name_and_id}\" class=\"text\"/>";
		} else {
			echo "<input type=\"text\" name=\"{$name_and_id}{$many}\" id=\"{$name_and_id}\" class=\"text\" value=\"{$value}\"/>";
			echo " ";
			echo "<a class=\"button\" href=\"/wp-admin/post.php?post={$value}&action=edit\" target=\"_blank\" title=\"Edit this instance\">Edit</a>";
		}
	}
}

class ImagePropertyType extends Type{
	public static function generate_input_field($name_and_id, $value = null, $property = null, $property_annotation = null, $many = false){
		if($many){
			$many = "[]";
		} else {
			$many = "";
		}
		
		$query_images = new \WP_Query(array(
			'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'posts_per_page' => -1
		));
		
		echo "<select name=\"{$name_and_id}{$many}\" id=\"{$name_and_id}\">";
		
		if($value == null){
			echo "<option value=\"\" selected=\"selected\"></option>";
		} else {
			echo "<option value=\"\"></option>";
		}
		
		foreach($query_images->posts as $post){
			if($value == $post->ID){
				echo "<option value=\"{$post->ID}\" selected=\"selected\">{$post->post_title}</option>";
			} else {
				echo "<option value=\"{$post->ID}\">{$post->post_title}</option>";
			}
		}
		echo "</select>";
		
		echo " ";
		
		echo "<a class=\"button\" href=\"/wp-admin/media-new.php" . "\" target=\"_blank\" title=\"Upload new image\">New</a>";
	}
}

class AttachmentPropertyType extends Type{
	public static function generate_input_field($name_and_id, $value = null, $property = null, $property_annotation = null, $many = false){
		if($many){
			$many = "[]";
		} else {
			$many = "";
		}
		
		$query_images = new \WP_Query(array(
			'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1
		));
		
		echo "<select name=\"{$name_and_id}{$many}\" id=\"{$name_and_id}\">";
		
		if($value == null){
			echo "<option value=\"\" selected=\"selected\"></option>";
		} else {
			echo "<option value=\"\"></option>";
		}
		
		foreach($query_images->posts as $post){
			if($value == $post->ID){
				echo "<option value=\"{$post->ID}\" selected=\"selected\">{$post->post_title}</option>";
			} else {
				echo "<option value=\"{$post->ID}\">{$post->post_title}</option>";
			}
		}
		echo "</select>";
		
		echo " ";
		
		echo "<a class=\"button\" href=\"/wp-admin/media-new.php" . "\" target=\"_blank\" title=\"Upload new attachment\">New</a>";
	}
}

class UserPropertyType extends Type{
	public static function generate_input_field($name_and_id, $value = null, $property = null, $property_annotation = null, $many = false){
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
		
		foreach($wpdb->get_results("SELECT ID, user_login FROM {$wpdb->users}") as $user){
			if($value == $user->ID){
				echo "<option value=\"{$user->ID}\" selected=\"selected\">{$user->user_login}</option>";
			} else {
				echo "<option value=\"{$user->ID}\">{$user->user_login}</option>";
			}
		}
		echo "</select>";
		
		echo "<a class=\"button\" href=\"/wp-admin/user-new.php" . "\" target=\"_blank\" title=\"Create new user\">New</a>";
	}
}

class EnumPropertyType extends Type{
	public static function generate_input_field($name_and_id, $value = null, $property = null, $property_annotation = null, $many = false){
		if(!$property_annotation->enum_options){
			echo "No enum_options set!";
			return;
		}
		
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
		
		foreach($property_annotation->enum_options as $array_key => $array_value){
			if($value == $array_key){
				echo "<option value=\"" . esc_attr(htmlspecialchars($array_key)) . "\" selected=\"selected\">{$array_value}</option>";
			} else {
				echo "<option value=\"" . esc_attr(htmlspecialchars($array_key)) . "\">{$array_value}</option>";
			}
		}
		
		echo "</select>";
	}
}