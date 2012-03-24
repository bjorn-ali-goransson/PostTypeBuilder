=== PostTypeBuilder ===
Contributors: bornemix
Tags: orm, posttypebuilder, bornemix, database, class
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 0.5
License: GPL2

Maps @annotated PHP classes to custom post types, automatically adds relevant fields to the Admin GUI, and LINQ/ActiveRecord-like queries.

== Description ==

PostTypeBuilder is an Object Relational Mapper connecting directly into the Wordpress engine, and provides handy scaffolding through the Wordpress GUI, as well as *data querying* similar to LINQ and ActiveRecord (Book::find()->where(...)).

`
class Book extends Entity{
    /** @Property */
    public $number_of_pages;
}
`

`
while(have_posts()){
	the_post(); $book = new Book($post);
	
	echo "<dt>" . $book->post_title . "</dt>";
	echo "<dd>" . $book->number_of_pages . "</dd>";
}
`

Included is the [Addendum](http://code.google.com/p/addendum/) library to support @annotations.

This plugin saves no information on your system (no database tables, no temporary files). All information is supplied by you in your class files.

*See [Other notes](http://wordpress.org/extend/plugins/posttypebuilder/other_notes/) for more info*

= Register classes =

Any classes in `wp-content/classes`, where the filename (like `class_name.php`) corresponds to the classname (like `ClassName`), will be registered as a Wordpress Custom Post Type.

Following example needs to be defined in `wp-content/classes/book.php`, and results in a registered post type being shown in the admin UI (but not publicly queryable):

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

class Book extends Entity{
}
`

*See [Other notes](http://wordpress.org/extend/plugins/posttypebuilder/other_notes/) for more info*

= Define properties =

Any class properties prefixed by the annotational comment `/** @Property */` will be registered as Wordpress metadata, and form fields will appear in the edit/create screen of the post type corresponding to the property type.

Following example, building on the previous `Book` example class, will display a text form field in the edit/create post screen:

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

class Book extends Entity{
	/** @Property */
	public $number_of_pages;
}
`

To enable the property to carry multiple values, use the `= array()` assignment:

`
/** @Property */
public $authors = array();
`

*See [Other notes](http://wordpress.org/extend/plugins/posttypebuilder/other_notes/) for more info*

= Find entities =

You can load entities in three ways.

(1) By post ID:

`
$book = new Book(21);
`

(2) By post object (useful in the loop):

`
global $post;

$book = new Book($post);
`

(3) By query:

`
$books = Book::find()->where("pages > (NUMERIC)", 10);

foreach($books as $book){ ... }
`

(Note that the query is executed lazily - ie. at the foreach statement)

Following code shows how to manipulate your entities:

`
$book = new Book();
$book->post_title = "Foo";
$book->save();
`

(Both post_object members and class properties are accessed in a unified way, but class properties have precedence)

*See [Other notes](http://wordpress.org/extend/plugins/posttypebuilder/other_notes/) for more info*

= Extend the functionality =

The plugin is designed to be extensible, so you can override form field generation for your classes, text representations (by overriding __toString), add your own property types, their form field generation, their text representation, you can hook into save events (and more...).

== Screenshots ==

1. Automatic form field generation of first and second example (in the description tab)

== Register classes ==

At Wordpress initialization stage, when an HTTP request is made, PostTypeBuilder searches `wp-content/classes` for PHP files.

Each file is then included, and PostTypeBuilder determines if class `\MyEntities\α` was defined as a result of that inclusion. `MyEntities` is the required namespace where you need to define your entities, and `α` is the filename converted to CamelCase.

For example, if file `wp-content/classes/newsletter_issue.php` exists, PostTypeBuilder will include that and then check if the class `\MyEntities\NewsletterIssue` exists.

*To change the default namespace, define POSTTYPEBUILDER_ENTITIES_NAMESPACE before PostTypeBuilder gets ahold of it.*

= Options =

There are a number of options available (defined in `annotations.php`):

* `@CanonicalName("")`
* `@Name("")`
* `@PluralName("")`
* `@Labels({})`
* `@Options({})`
* `@Panels({})`

To use these, include those you want in a single `/**  */` annotational "DocBlock" comment, with each annotation separated by some whitespace (preferrably, each on a separate line).

*`@CanonicalName("")`* defines that the wordpress-internal name should equal the string that is supplied between the parenthesis. By default, this will be equal to the PHP filename, excluding the `.php` extension. (ie. "book")

The following example registers a Post class, with the internal, canonical name "content_post":

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

/** @CanonicalName("content_post") */
class Post extends Entity{
}
`

*`@Name("")`* defines the singular name (used in labels, menus) to be the string supplied between the parenthesis. By default, this will be equal to the CamelCase version of the canonical name, each Camel segment being separated by space. (ie. "Book")

*`@PluralName("")`* defines the plural name (used in labels, menus) to be the string supplied between the parenthesis. By default, this will be equal to the implicit (or explicit) singular name + "s". (ie. "Books")

The following example defines the irregular plural name "Virii" for the class "Virus":

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

/** @PluralName("Virii") */
class Virus extends Entity{
}
`

*`@Labels({})`* is a way to override the PostTypeBuilder label determination mechanism, which is partly described above (see `generate_class_meta` in `posttypebuilder.php`). Supplied values will override the values provided by PostTypeBuilder. Possible keys correspond to the keys in the labels object documented in [register_post_type()](http://codex.wordpress.org/Function_Reference/register_post_type).

The following example overrides the menu name (subsequent label assignations need to separated by comma):

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

/** @Labels({menu_name = "Foo"}) */
class Book extends Entity{
}
`

*`@Supports({})`* is a way to specify the post type supports array.

Since post types do not work without a title, it is automatically added to whatever you supply here. Editor is not enabled by default.

Possible values are:

* 'title'
* 'editor' (content)
* 'author'
* 'thumbnail' (featured image, current theme must also support post-thumbnails)
* 'excerpt'
* 'trackbacks'
* 'custom-fields'
* 'comments' (also will see comment count balloon on edit screen)
* 'revisions' (will store revisions)
* 'page-attributes' (menu order, hierarchical must be true to show Parent option)
* 'post-formats' add post formats, see [Post Formats](http://wordpress.org/Post_Formats).

The following example enables support for text editor and comments:

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

/** @Supports({"editor","comments"}) */
class Book extends Entity{
}
`

*`@IsPublic({})`* makes the post type publicly queryable.

By default, entity types are not enabled to be visible to outside users. As such, you will get a 404 error when clicking "View post".

The following example enables "publicly queryable" for the entity type:

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

/** @IsPublic */
class Book extends Entity{
}
`

*`@Options({})`* is a way to override the option defaults that are passed to WP's [register_post_type()](http://codex.wordpress.org/Function_Reference/register_post_type).

The following example enables post archives:

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

/**
@IsPublic
@Options({
	has_archive = "books"
})
*/
class Book extends Entity{
}
`

*`@Panels`* specifies the form field groups ("panels") that are to be shown on the edit/create post screen. By default, only one panel is available ("properties"). Supplying @Panels will clear the default panel, so you need to redeclare it if you use this annotation.

The following code specifies that two panels, "Properties" (in main column) and "Options" (in side column) should be used:

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

/**
@Panels({
	{
		id="properties",
		label="Properties",
		position="normal"
	},
	{
		id="options",
		label="Options",
		position="side"
	}
})
*/
class Book extends Entity{
	/** @Property(panel="properties") */
	public $author_name;
	/** @Property(type="Boolean",panel="options") */
	public $show_on_startpage;
}
`

== Define properties ==

Each property that PostTypeBuilder is to be aware of, needs to be prefixed with the annotational comment `/** @Property */`. Otherwise it will be a plain old PHP class property (not managed by PostTypeBuilder).

By default, the property type is "Text", which can be declared explicitly by including `/** @Property(type="Text") */`.

The following example defines a property called `number_of_pages` in the entity Book:

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

class Book extends Entity{
	/** @Property */
	public $number_of_pages;
}
`

*Note: The specific visibility `public` is not required - but without any modifier, [the variable name will not be registered as a class property](http://php.net/manual/en/language.oop5.visibility.php). Either of `public`, `private` or `protected` will do (`var` evaluates to `public`), but note that only public properties will show up in the edit/create post screen.*

By prefixing the property declaration with the `@Property` annotation, PostTypeBuilder will:

1. Sync it when relevant instances are loaded/saved from the database.
2. Generate a form field for it when showing the edit/create post screen.
3. Make it available for searching/filtering/ordering when using the PostTypeBuilder query mechanism.

= Multiple values =

A property can hold multiple values if declared with `= array()` in the class definition. Form field will then be preceded by a checkbox-list of current values that can be rearranged by drag and drop, and unchecked to be removed. Adding input to form field will add a new value.

Since 0.4, PostTypeBuilder uses WP's post_meta functions which takes care of array serialization when saving.

The following example enables the "author_names" property to hold multiple values:

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

class Book extends Entity{
	/** @Property */
	public $author_names = array();
}
`

= Options =

To supply options to this annotation, include them in a comma-separated list of equals-separated name-value pairs, the list surrounded by braces between the optional parenthesis after the annotation keyword: `/** Property({option1="Foo", option2="Bar"}) */`

There are a few options you can supply to this annotation:

* `type` (default `"Text"`)
* `label` (default `null`)
* `required` (default `false`)
* `panel` (default `"properties"`)
* `visible` (default `true`)

*`type`* specifies the class property type, either a built-in PostTypeBuilder type, an entity declared in MyEntities, or a custom type supplied by you (look in `types.php` for inspiration).

PostTypeBuilder supplies a number of built-in types:

* `Text` (default) - Long string.
* `LongText` - Long string, but the form field will be a multiline `<textarea>` as opposed to `Text`.
* `Boolean` - True/false. Form field will be a checkbox. Saved value will be (string) `"true"` for true, or `null` for false.
* `GenericPost` - Link to a post ID without specifying type. Form field will be a text input field. Saved value will be post ID.
* `Image` - Link to an attachment image. Form field will be a rudimentary image chooser. Saved value will be attachment ID.
* `User` - Link to a user in the Wordpress database. Form field will be a `<select>` dropdown box. Saved value will be user ID.
* `Enum` - Long string. Form field will be a `<select>` dropdown box. Saved value will be the value part (not the label part) of the selected enum-options item.

The following example defines a `Book` which contains a collection of `User`s who "liked" it:

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

class Book extends Entity{
	/** @Property({type="User"}) */
	public $users_who_liked_this_book = array();
}
`

*`Enum`* is kind of special, because it requires another parameter to be specified (`enum_options`). It is a comma-separated list of equals-separated value-label pairs, enclosed by curly braces, like: `{value_one="Label 1",value_two="Label 2"}`

The following example defines a `Book` which contains a property specifying the difficulty of the books' language:

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

class Book extends Entity{
	/** @Property({type="Enum",enum_options={easy="Easy",medium="Medium",hard="Hard"}}) */
	public $users_who_liked_this_book = array();
}
`

*It is also possible to define an entity as your property type.* This part is kind of special.

If you have an entity class called "Book", then you can make the property link to it by specifying "Book" as its type.

*You need to name your property with an ending "_id". For properties with multiple values, it must end with _ids.*

`
/** @Property(type="Book") */
public $favorite_book_id;
`

Now, when accessing your entity object, you can get its associated object by accessing the property name *without id*:

`
$entity = ...

$book = $entity->favorite_book;
`

The preceding code will give you the whole Entity object, loaded with properties and all. To get just the id, use the proper property name:

`
$entity = ...

$book_id = $entity->favorite_book_id;
`

When having properties with multiple values, name your code with the singular name + "_ids":

`
/** @Property(type="Book") */
public $favorite_book_ids;
`

PostTypeBuilder listens for the property name minus "_ids" plus "s", like the following code:

`
$entity = ...

$books = $entity->favorite_books;
`

To only get the IDs, use the following code:

`
$entity = ...

$book_ids = $entity->favorite_book_ids;
`

*Note: To change the collection, you need to manipulate the IDs array, not the lazily loaded proxy properties.*

*`label`* specifies the text used for labelling the form field on the edit/create post screen. Currently, PostTypeBuilder does not support i18n for any of its interface functionality.

The following example specifies the label for two properties:

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

class Book extends Entity{
	/** @Property(label="Number of pages (do not use this one!)") */
	public $pages;
	/** @Property(type="Image", label="Image of first page") */
	public $first_page_image;
}
`

*`required`* specifies that the property must not be empty. Currently, the only thing happening when this option is set to `true`, a red star comes up next to the label. No validation of any kind is made.

*`panel`* specifies which panel should contain the form field on the edit/create post screen. Look for the Panels section below.

*`visible`* specifies wether the form field should be shown on screen or not. If not, the only way to interact with the property is through code.

== Find entities == 

*This section is not done!*

To start using the query mechanism, you will need to retrieve a Query object. This is done like so:

`
$query_object = Book::find();
`

This object can then be used to add parameters to what will add up to a WP Query object, which will be executed when accessed.

To limit the amount of hits in your query to 5, use the following code:

`
$query_object = Book::find(5);
`

`
$first_item = $query_object[0]; // executes search, caches result, and returns first match
$second_item = $query_object[1]; // by now, result is already cached in $query_object, and another query will not be executed
`

`
foreach($query_object as $item){ // executes search and returns iterator
	...
}
`

When having executed the search query, changes to the query object will have no effect.

When making the query, the query acts as a proxy for its found instances. Even things like `$book_title = Book::find()->where("author_name", "Björn Ali Göransson")->post_title` will work. To get the real entity object, use `get($index = 0)` or array notation `[$index]`.

The following example does a search for book author name:

`
$query_object = Book::find();
$query_object->where("author_name", "Björn Ali Göransson");
$book = $query_object[0];
`

Almost each query method returns the query object itself, by the way, which makes way for chaining:

`
$book = Book::find()->where("author_name", "Björn Ali Göransson")->get(0);
`

= Where =

The syntax is as following:

`
$query_object->where(key, value);
`

To make a search for a property, you use the same syntax. When searching for properties, though, you have more options.

The following example searches for books where `year` is between 1950 and 2000:

`
$query_object = Book::find();

$query_object->where("year > (NUMERIC)", 1950);

$query_object->where("year < (NUMERIC)", 2000);
`

The first parameter can be either `name`, `name operator` or `name operator (format)`.

`name` should be a valid PostTypeBuilder property.

Operator should be one of the following:

* `=`
* `!=`
* `>`
* `>=`
* `<`
* `<=`
* `LIKE`
* `NOT LIKE`
* `IN`
* `NOT IN`
* `BETWEEN`
* `NOT BETWEEN`

*If you don't specify an operator, then (like WP_Query) it will default to `=`.*

`format` specifies to which data type the value should be casted.

Possible values are:

* `NUMERIC`
* `BINARY`
* `CHAR`
* `DATE`
* `DATETIME`
* `DECIMAL`
* `SIGNED`
* `TIME`
* `UNSIGNED`.

*If you don't specify a format, then like WP_Query, it will default to `CHAR`.*

If you want, you can specify WP Query parameters here as well. Also, you can specify post_object columns in the where clause.

== Extend default functionality ==

= Custom form fields =

To get custom form fields for a type, extend the type in your own file (maybe `functions.php` in your theme?) and implement your own `generate_input_field` method. See `types.php` for inspiration.

In order to implement custom form fields for choosing an entity, override the `generate_input_field` in your entity class.

The following example overrides the `<select>` dropdown form field for selecting Books, as it would be too long to load such a list, and instead delegates the `generate_input_field` to the `Text` type (and thereby exposing the ID):

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

class Book extends Entity{
	public function generate_input_field($name_and_id, $value = null, $property = null, $property_annotation = null, $many = false){
		\Addendum\TextPropertyType::generate_input_field($name_and_id, $value, $property, $property_annotation, $many);
	}
}
`

See `entity.class.php` and `types.php` for inspiration on how to implement this method.

= Text representation of entities =

When listing entities for form fields, the text representation of the entity is used to represent it. It defaults to the post title, but if that doesn't exist, it shows other things (see `entity.class.php`). This functionality can be changed by overriding the `__toString()` method.

The following example shows the authors in the books' text representation:

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

class Book extends Entity{
	/** @Property */
	public $author_names = array();
	
	public function __toString(){
		return $this->post_title . " by " . implode(", ", $this->author_names);
	}
}
`

= Class methods =

Class methods can be added without any "plumbing".

The following example adds a "sort" method to the Book class:

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

class Book extends Entity{
	/** @Property */
	public $author_names;
	
	public function sort_names(){
		sort($this->author_names);
	}
}
`

The method can then be used like the following code:

`
$book = ...

$book->sort();

$book->save();
`

= Event hooks =

You may "hook on" certain post events by defining corresponding class methods. They need to be static, as an entity instance isn't always desirable in these events. All methods receive the associated post object as argument.

Following is a list of all methods that can be implemented:

* `clean_post_cache` - Runs when post cache is cleaned.
* `delete_post` - Runs when a post or page is about to be deleted.
* `edit_post` - Runs when a post or page is updated/edited, including when a comment is added or updated (which causes the comment count for the post to update).
* `save_post` - Runs whenever a post or page is created or updated, which could be from an import, post/page edit form, xmlrpc, or post by email.
* `publish_post` - Runs when a post is published, or if it is edited and its status is "published".
* `pending_post` - Same as `publish_post` but with status "pending".
* `draft_post` - Same as `publish_post` but with status "draft".
* `auto_post` - Same as `publish_post` but with status "auto".
* `future_post` - Same as `publish_post` but with status "future".
* `private_post` - Same as `publish_post` but with status "private".
* `inherit_post` - Same as `publish_post` but with status "inherit".
* `trash_post` - Same as `publish_post` but with status "trash".

The following example sorts author names on save:

`
namespace MyEntities;
use \PostTypeBuilder\Entity;

class Book extends Entity{
	/** @Property */
	public $author_names;
	
	public function sort_names(){
		sort($this->author_names);
	}
	
	static function save_post($post_object){
		$book = new Book($post_object);
		
		$book->sort_names();
		
		$book->save();
	}
}
`

*Note: The save_post will not fire recursively into itself, which means that the last call to `$book->save()` will not make the `save_post()` execute again.*









