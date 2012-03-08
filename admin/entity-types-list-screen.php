<h2 class="nav-tab-wrapper">
	<a href="<?php echo $page_url; ?>&screen=list" class="nav-tab nav-tab-active">All entity types</a>
	<!--<a href="<?php echo $page_url; ?>&screen=add-new" class="nav-tab">Add new</a>-->
</h2>

<?php if(count(\PostTypeBuilder\PostTypeBuilder::$registered_post_types) > 0){ ?>

<ul class="ul-disc">
<?php
	foreach(\PostTypeBuilder\PostTypeBuilder::$registered_post_types as $key => $post_meta){
?>
	<li><a href="<?php echo $page_url ?>&screen=show-single&post-type=<?php echo $post_meta->post_type; ?>" title="Edit <?php echo esc_attr(strtolower($post_meta->singular_name)); ?> entity type"><?php echo $post_meta->singular_name; ?></a></li>
<?php
	}
?>
</ul>

<?php } else { ?>

<p>No entity types currently exist.</p>

<p><a href="<?php echo $page_url; ?>&screen=add-new" class="button-primary">Add new</a> <a href="admin-ajax.php?action=posttypebuilder_add_example_entity_types" class="button">Add example entity types</a></p>

<?php } ?>