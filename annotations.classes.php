<?php

namespace Addendum;

class CanonicalName extends Annotation{}
class Name extends Annotation{}
class PluralName extends Annotation{}
class Labels extends Annotation{}
class Supports extends Annotation{}
class IsPublic extends Annotation{}
class Options extends Annotation{}
class Panels extends Annotation{}

class Property extends Annotation{
	public $type = "Text";
	public $label = null;
	public $required = false;
	public $panel = "properties";
	public $visible = true;
	
	//type specific options
	public $enum_options = null;
}