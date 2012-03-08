jQuery(function($){
	function enableDragAndDropForFieldsCarryingMultipleValues(){
		$("#post-body-content td.posttypebuilder-field-multiple ul")
		.addClass("sortable")
		.find("input").attr("title", "Uncheck to remove from collection").end()
		.sortable({handle:"span.grippy", placeholder: 'ui-sortable-placeholder'});
	}
	function enableFrontEndAddingOfNewValuesForFieldsCarryingMultipleValues(){
		function getUniqueListId(list, idPrefix){
			var id = 1;
			var resultingId = idPrefix + "_" + id;
			
			while($("#" + resultingId).length != 0){
				id++;
				resultingId = idPrefix + "_" + id;
			}
			
			return resultingId;
		}
		
		$("<span/>")
		.addClass("button")
		.attr("tabindex", -1)
		.append("Add")
		.attr("title", "Add instance to collection")
		.mousedown(function(event){event.preventDefault();})
		.click(function(event){
			var button = $(this);
			var list = button.siblings("ul");
			var formElement = button.siblings("select, input");
			
			var value = formElement.val();
			var name = formElement.is("select") ? $(formElement[0].options[formElement[0].selectedIndex]).text() : value;
			
			if(value && value != ""){
				var listItem = $("<li/>").appendTo(list);
				var uniqueId = getUniqueListId(list, formElement.attr("id"));
				
				$("<span/>")
				.addClass("grippy")
				.attr("title", "Drag to rearrange instances")
				.appendTo(listItem);
				
				$("<input type=\"checkbox\"/>")
				.attr("name", formElement.attr("id") + "[]")
				.attr("id", uniqueId)
				.val(value)
				.attr("checked", true)
				.attr("title", "Uncheck to remove on next save")
				.appendTo(listItem)
				.wrap("<span class=\"checkbox-wrapper\" />");
				
				$("<label/>")
				.attr("for", uniqueId)
				.append("<span class=\"text\">" + name + "</span>")
				.append("<a href=\"/wp-admin/post.php?post=" + value + "&action=edit\" title=\"Edit instance (opens in new window)\" target=\"_blank\">Edit</a>")
				.appendTo(listItem);
				
				formElement.val("");
			}
		})
		.appendTo("#post-body-content td.posttypebuilder-field-multiple");
	}
	enableDragAndDropForFieldsCarryingMultipleValues();
	enableFrontEndAddingOfNewValuesForFieldsCarryingMultipleValues();
});