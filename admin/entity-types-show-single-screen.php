<?php

namespace PostTypeBuilder;
	
use \ReflectionProperty;

$post_type = $_GET["post-type"];

if(array_key_exists($post_type, \PostTypeBuilder\PostTypeBuilder::$registered_post_types)){
	$class_meta = \PostTypeBuilder\PostTypeBuilder::$registered_post_types[$post_type];
}
?>
<h2 class="nav-tab-wrapper">
	<a href="<?php echo $page_url; ?>&screen=list" class="nav-tab">All entity types</a>
	
	<?php if(is_object($class_meta)){ ?>
		<a href="<?php echo $page_url; ?>&screen=show-single&post-type=<?php echo $post_type; ?>" class="nav-tab nav-tab-active">Edit <?php echo $class_meta->singular_name; ?></a>
	<?php } ?>
	
	<!--<a href="<?php echo $page_url; ?>&screen=add-new" class="nav-tab">Add new</a>-->
</h2>
<?php
if(!array_key_exists($post_type, \PostTypeBuilder\PostTypeBuilder::$registered_post_types)){
	echo "<p><em>Entity type does not exist!</em></p>";
	
	die();
}

function annotation_value_tostring($value){
	if(is_array($value) || is_object($value)){
		$array = $value;
		
		$resulting_string = array();
		
		$i = 0;
		
		foreach($array as $key => $value){
			if($value !== NULL){
				if($key === $i){
					$resulting_string[] = annotation_value_tostring($value);
				} else {
					$resulting_string[] = $key . " = " . annotation_value_tostring($value);
				}
			}
			
			$i++;
		}
		
		return "{" . implode(", ", $resulting_string) . "}";
	}
	
	if(is_string($value)){
		return "\"{$value}\"";
	}
	
	if(is_bool($value)){
		return $value ? "true" : "false";
	}
}

function annotation_canonical_name($annotation){
	return preg_replace("@[^a-z]+@", "_", strtolower(get_class($annotation)));
}

function annotation_name_tostring($annotation){
	$name = get_class($annotation);
	
	$name = "@" . substr($name, strpos($name, "\\") + 1);
	
	return $name;
}

function annotation_tostring($annotation){
	$name = annotation_name_tostring($annotation);
	
	$value = annotation_value_tostring($annotation->value);
	
	if(strlen($value) > 0){
		return $name . "(" . $value . ")";
	} else {
		return $name;
	}
}
?>
<?php

$annotations = $class_meta->class_reflector->getAnnotations();

if(count($annotations) > 0){
?>
<h3>Class annotations</h3>

<table class="form-table">
	<tbody>
		<?php
			foreach($class_meta->class_reflector->getAnnotations() as $annotation){
		?>
		<tr class="form-field">
			<th scope="row"><label for="<?php echo annotation_canonical_name($annotation); ?>"><?php echo annotation_name_tostring($annotation); ?></label></th>
			<td><input name="<?php echo annotation_canonical_name($annotation); ?>" type="text" id="<?php echo annotation_canonical_name($annotation); ?>" value="<?php echo esc_attr(annotation_value_tostring($annotation)); ?>"></td>
		</tr>
		<?php
			}
		?>
	</tbody>
</table>
<?php
}

?>

<?php

$class_name = $class_meta->qualified_class_name;
$entity = new $class_name($post);

function property_row($name, $value, $label){
	$name = "posttypebuilder_{$name}";
	?>
		<tr class="form-field">
			<th scope="row"><label for="<?php echo $name; ?>"><?php echo $label; ?></label></th>
			<td><input name="<?php echo $name; ?>" type="text" id="<?php echo $name; ?>" value="<?php echo esc_attr(annotation_value_tostring($value)); ?>" disabled="disabled"></td>
		</tr>
	<?php
}

function common_property_row($property, $name){
	$property_annotation = $property->getAnnotation("Addendum\\Property");
	
	property_row($property->getName() . "_" . $name, $property_annotation->$name, ucwords($name));
}

foreach($class_meta->class_reflector->getProperties(ReflectionProperty::IS_PUBLIC) as $property){
	if(!$property->hasAnnotation("Addendum\\Property")){
		continue;
	}
	
	$property_annotation = $property->getAnnotation("Addendum\\Property");
	
	$name = $property->getName();
	
?>
	<h3>Property "<?php echo posttypebuilder_get_default_label($entity, $name); ?>"</h3>
	
	<table class="form-table">
		<tbody>
			<?php common_property_row($property, "type"); ?>
			<?php common_property_row($property, "label"); ?>
			<?php common_property_row($property, "required"); ?>
			<?php common_property_row($property, "panel"); ?>
			<?php common_property_row($property, "visible"); ?>
			<?php if($property_annotation->type == "Enum"){common_property_row($property, "enum_options");} ?>
			<?php property_row($property->getName() . "_" . $name, annotation_value_tostring($entity->$name), "Default value"); ?>
		</tbody>
	</table>
<?php
}
?>

<!--
<h3>New property?</h3>

<p><a href="<?php echo $page_url; ?>&screen=add-new-property&post-type=<?php echo $post_type; ?>" class="button">Add</a></p>
-->