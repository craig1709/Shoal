<?php defined('SYSPATH') or die('No direct script access.');

/* 
	Version 0.11.1
	avanthill.com/formo_manual/
		
	Requires Formo_Element and Formo_Group
	Kohana 2.2
*/

class Formo_Core {

	public $__form_name;
	public $__form_type;
	public $error;
	public $sent = FALSE;
	public $elements = array();
	
	public $functions = array();
		
	public $post_added;
	public $was_validated;
	public $validated;
	public $post_type;
		
	public $open = '<form action="{action}" method="{method}" class="{class}">';
	public $close = '</form>';
	public $action;
	public $method = 'post';
	public $class = 'standardform';
	
	public $order = array();
	
	public $current;
	
	public $auto_rules = array();
	public $pre_filters = array();
	public $label_filters = array();
		
	private $_elements_str;
	
	public $submit_name;
			
	public $globals = array();
	public $defaults = array();
		
	public function __construct($name='noname',$type='')
	{				
		$this->__form_name = ($name) ? $name : 'noname';
		$this->__form_type = $type;
		$this->add('hidden','__form_object',array('value'=>$this->__form_name));

		$this->_compile_plugins('plugins');		
		$this->_compile_settings('globals');
		$this->_compile_settings('defaults');
		$this->_compile_settings('auto_rules');
		$this->_compile_settings('label_filters');
		$this->_compile_settings('pre_filters');
	}
		
	public function bind($element, & $value)
	{
		if ( ! empty($this->$element))
			return $this;

		$this->$element =& $value;			
				
		return $this;
	}
	
	public function add_function($function, $values)
	{
		$this->functions[$function] = $values;
		
		return $this;
	}
		
	public function plugin($plugin)
	{
		$plugins = self::splitby($plugin);
		
		foreach ($plugins as $name)
		{
			include_once(Kohana::find_file('libraries', 'Formo_'.$name));
			call_user_func('Formo_'.$name.'::load', $this);
		}

		return $this;
	}
	
	/**
	 * Magic __call method. Handles element-specific stuff and
	 * set_thing and add_thing
	 *
	 * @return  object
	 */		
	public function __call($function, $values)
	{
		if ( ! empty($this->functions[$function]))
		{
			$return = call_user_func_array($this->functions[$function], $values);
			
			return ($return) ? $return : $this;
		}
				
		$element = ( ! empty($values[0])) ? $values[0] : NULL;
		if ( ! is_array($element) AND isset($this->$element) AND method_exists($this->$element, $function))
		{
			unset($values[0]);
			call_user_func_array(array($this->$element, $function), $values);
			return $this;			
		}
		elseif (preg_match('/^(set|add)_([a-zA-Z0-9_]+)$/', $function, $matches))
		{
			switch ($matches[1])
			{
				case 'add':
					$this->$matches[2] = array_merge($this->$matches[2], self::into_array($values));						
				break;
				case 'set':
					$this->$matches[2] = $values[0];
				break;
			}
		}
		elseif (isset($this->$function))
		{
			if (is_array($values[0]) AND isset($values[1]) AND is_array($values[1]))
			{
				$this->$function = $values;
			}
			elseif (is_array($values[0]))
			{
				$this->$function = $values[0];
			}
			else
			{
				$this->$function = $values;
			}
		}
		
		return $this;
	}
	
	/**
	 * Magic __set method. Keeps track of elements added to form object
	 *
	 */				
	public function __set($var, $val)
	{
		$element_classes = array('Formo_Element','Formo_Group');
		$this->$var = $val;
		if (in_array(get_class($val), $element_classes))
		{
			$this->elements[$var] = $this->$var->type;
		}
	}

	/**
	 * factory method. Creates and returns a new form object
	 *
	 * @return  object
	 */			
	public static function factory($name='noname',$type='')
	{	
		return new Formo($name, $type);
	}
	
	/**
	 * depends_on method. Mimicks Kohana's built-in helper
	 *
	 * @return bool
	 */					
	public function depends_on($field, array $fields)
	{
		foreach ($fields as $element=>$v)
		{
			if ( ! isset($fields[$element]) OR $fields[$element] == NULL )
				return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * matches method. Mimicks Kohana's built-in helper
	 *
	 * @return bool
	 */					
	public function matches($field_value, array $inputs)
	{
		foreach ($inputs as $element=>$v)
		{
			if ($field_value != $inputs[$element])
				return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * validate method. Runs validate on each element
	 *
	 * @return object
	 */						
	public function validate($append_errors=FALSE)
	{
		Event::run('pre_validate');

		if ($this->was_validated AND ! $append_errors)
			return $this->validated;
						
		if ( ! $this->post_added)
		{
			$this->add_posts();
		}
		
		if ( ! $this->sent)
			return;
				
		// validate elements
		foreach (($elements = $this->find_elements()) as $key)
		{
			if ( ! $this->validated)
			{
				if ($this->$key->validate()) {
					$this->error = TRUE;
				}
			}
			
			if ($append_errors)
			{
				$this->$key->append_errors();
			}
		}	
		
		if ($this->was_validated)
			return $this->validated;
	
		$this->was_validated = TRUE;		
		$this->validated = ($this->error) ? FALSE : TRUE;
				
		Event::run('formo.post_validate');
		
		return ($this->error) ? FALSE : TRUE;
	}

	/**
	 * _filter_labels method. Runs filters on labels
	 *
	 * NOT USABLE
	 */							
	private function _filter_label($element)
	{
		foreach ($this->label_filters as $filter)
		{
			$this->$element->label = call_user_func($filter, $this->$element->label);
		}
	}
	
	public function label_filter($function)
	{
		$this->label_filters[] = $function;
		
		return $this;
	}

	/**
	 * add_posts method. Called from _prepare, adds post/get
	 * values to elements
	 *
	 * @return bool
	 */							
	public function add_posts()
	{
		if ($this->post_added)
			return;
				
		if (strtoupper(Input::instance()->post('__form_object')) == strtoupper($this->__form_name))
		{
			$this->post_type = 'post';
		}
		elseif (strtoupper(Input::instance()->get('__form_object')) == strtoupper($this->__form_name))
		{
			$this->post_type = 'get';
		}
			
		if ( ! $this->post_type)
			return;
		
		$type = $this->post_type;

		Event::run('formo.pre_addpost');
		
		foreach ($this->find_elements() as $element)
		{
			$value = Input::instance()->$type($element);
			switch ($this->$element->type)
			{
				case 'checkbox':
					if ($value == $this->$element->value) $this->$element->checked = TRUE;
					break;
				case 'bool':
					$this->$element->checked
						= (isset($_POST[$element]))
						? TRUE
						: FALSE;
					break;
				case 'checkbox_group':
				case 'radio_group':
					$this->$element->find_checked($type);
					break;
				default:
					$this->$element->value = $value;
					
			}
		}

		Event::run('formo.post_addpost');

		$this->post_added = TRUE;
		$this->sent = TRUE;
	}

	/**
	 * _do_pre_filters method. run pre_filters on elements
	 * 
	 * USABLE, NEEDS SERIOUS CLEANUP
	 *
	 * @return bool
	 */
	 	
	private function _do_pre_filters()
	{
		foreach ($this->find_elements() as $element)
		{
			if (isset($this->pre_filters['all']))
			{
				foreach ($this->pre_filters['all'] as $filter)
				{
					if ($this->$element->type == 'select' OR $this->$element->type == 'sel')
					{
						$this->_do_select_pre_filter($element, $filter);
					}
					else
					{
						$this->$element->value = call_user_func($filter, $this->$element->value);
					}					
				}
			}
			if (isset($this->pre_filters[$element]))
			{
				foreach ($this->pre_filters[$element] as $filter)
				{
					if ($this->$element->type == 'select' OR $this->$element->type == 'sel')
					{
						$this->_do_select_pre_filter($element, $filter);
					}
					else
					{
						$this->$element->value = call_user_func($filter, $this->$element->value);
					}					
				}
			}
		}	
	}
	
	private function _do_select_pre_filter($element, $filter)
	{
		$keys = array_keys($this->$element->values);
		$values = array_values($this->$element->values);
		foreach ($keys as $k=>$key)
		{
			$keys[$k] = call_user_func($filter, $key);
		}
		
		$this->$element->values = array_combine($keys, $values);
	}
	
	/**
	 * set method. set form object value
	 * 
	 *
	 * @return object
	 */								
	public function set($tag,$value,$other='')
	{
		if (isset($this->elements[$tag]))
		{
			$this->$tag->$value = $other;
		}
		else
		{
			$value = (is_array($this->$tag)) ? self::into_array($value) : $value;
			$this->$tag = $value;
		}
		return $this;
	}

	/**
	 * splitby method. Divide a list into parts
	 *
	 * @return array
	 */													
	public static function splitby($string, $dividers = array(',', '\|'))
	{
		if (is_array($string))
			return $string;
			
		foreach ($dividers as $divider)
		{
			if (preg_match('/'.$divider.'/', $string))
			{
				return split($divider, $string);
			}
		}
		
		return array($string);
	}

	/**
	 * quicktags method. Simple method for inputting tag sets
	 *
	 * @return array
	 */											
	public static function quicktags($string)
	{	
		if ( ! $string)
			return array();
		if (is_array($string))
			return $string;
			
		$groups = self::splitby($string);
		foreach ($groups as $group)
		{
			$group_parts = split('=', $group);
			$tags[trim($group_parts[0])] = trim($group_parts[1]);
		}
		return $tags;
	}
	
	/**
	 * quicktagss method. Formats tags array into formatted html
	 *
	 * @return string
	 */												
	public static function quicktagss($string)
	{
		$tags = self::quicktags($string);
		$str = '';
		$a = 0;
		foreach ($tags as $k=>$v)
		{
			$str.= ' '.$k.'='.'"'.$v.'"';
			$a++;
		}
		return $str;
	}

	/**
	 * into_function method. Takes string like some_function[val, val2][val3, val4]
	 * and returns an array function => some_function, args => 0 => array(val, val2), 1 => array(val3, val4)
	 *
	 * @return array
	 */										
	public static function into_function($string)
	{
		preg_match('/^([^\[]++)/', $string, $matches);
		$function = $matches[0];
		$string = preg_replace('/^'.$function.'/','',$string);
		
		preg_match_all('/\[([a-z_0-9 ,]+)\]/', $string, $matches);
		$args = array();
		foreach ($matches[1] as $match)
		{
			$args[] = (preg_match('/,/',$match)) ? preg_split('/(?<!\\\\),\s*/', $match) : $match;
		}
				
		return array($function,$args);
	}

	/**
	 * into_array method. Turns $thing into an array if it isn't
	 *
	 * @return array
	 */											
	public function into_array( & $thing)
	{	
		if ( ! is_array($thing))
		{
			$thing = array($thing);
		}

		return $thing;
	}

	/**
	 * _compile_settings method. From config
	 *
	 */											
	private function _compile_settings($setting)
	{
		$settings = array
		(
			'type_values' => Kohana::config('formo.'.$this->__form_type.'.'.$setting, FALSE, FALSE),
			'values' => Kohana::config('formo.'.$setting, FALSE, FALSE)
		);
					
		if ($settings['values'])
		{
			$this->$setting = array_merge($this->$setting, $settings['values']);
		}
			
		if ($settings['type_values'])
		{
			$this->$setting = array_merge($this->$setting, $settings['type_values']);
		}
	}
	
	private function _compile_plugins()
	{
		$plugins = array();
		$settings = array
		(
			'values' => Kohana::config('formo.plugins', FALSE, FALSE),
			'type_values' => Kohana::config('formo.'.$this->__form_type.'.plugins', FALSE, FALSE)
		);
		
		foreach ($settings as $setting)
		{
			if ( ! $setting)
				continue;
			
			$this->plugin($setting);
		}
		
	}
		
	public function check($group, $element='')
	{
		if ( ! $element)
		{
			$this->$group->checked = TRUE;
		}
		elseif (is_array($element) OR $pipe = preg_match('/|/',$element) OR $comma = preg_match('/,/', $element))
		{
			if ( ! $pipe AND ! $comma)
				$elements = $element;
			elseif ($pipe)
				$elements = split('\|', $element);
			elseif ($comma)
				$elements = split(',', $element);
			
			foreach ($elements as $el)
			{
				$this->$group->$el->checked = TRUE;
			}
		}
		else
		{
			$this->$group->$element->checked = TRUE;
		}
		
		return $this;
	}

	/**
	 * pre_filter method. Add a pre_filter
	 *
	 * @return object
	 */												
	public function pre_filter($element, $function='')
	{
		$this->pre_filters[$element][] = $function;
		
		return $this;
	}
	
	/**
	 * pre_filters method. Add a pre_filter to a set of elements
	 *
	 * @return object
	 */
	public function pre_filters($function, $elements)
	{
		$_functions = self::splitby($function);
		$_elements = self::splitby($elements);
		
		foreach ($_functions as $_function)
		{
			foreach ($_elements as $_element)
			{
				$_element = trim($_element);
				$this->pre_filter(trim($_element), $_function);
			}		
		}		
		
		return $this;
	}
	
	/**
	 * add_rules. Add a rule to a set of elements
	 *
	 * @return object
	 */
	public function add_rules($rule, $elements, $message='')
	{
		$_rules = self::splitby($rule);
		$_elements = self::splitby($elements);

		foreach ($_rules as $_rule)
		{
			foreach ($_elements as $_element)
			{
				$_element = trim($_element);
				$this->$_element->add_rule($_rule, $message);
			}
		}
		
		return $this;
	}
	
		
	/**
	 * _make_defaults method. Append default tags to element
	 *
	 * @return array
	 */
	private function _make_defaults($type, $name)
	{
		$defaults = array();
		// check if this needs to change types first
		if (isset($this->defaults[strtolower($name)]['type']))
		{
			$type = $this->defaults[strtolower($name)]['type'];
		}
		
		if (isset($this->defaults[$type]))
		{
			$defaults = array_merge($this->defaults[$type], $defaults);
		}		
	
		if (isset($this->defaults[strtolower($name)]))
		{
			$defaults = array_merge($defaults, $this->defaults[strtolower($name)]);
		}
		
		
		return $defaults;
	}
				

	/**
	 * add method. Add a new Form_Element object to form
	 *
	 * @return object
	 */
	public function add($type,$name='',$info=array())
	{
		if ($type == 'submit')
		{
			if ( ! $name)
			{
				$name = "Submit";
			}
			$info['value'] = ( ! isset($info['value'])) ? $name : $info['value'];
		}
		elseif ( ! $info AND ! $name)
		{
			$name = $type;
			$type = 'text';
		}
		elseif ( ! $info AND (is_array($name) OR preg_match('/=/',$name)))
		{
			$info = $name;
			$name = $type;
			$type = 'text';
		}
		
		$obj_name = preg_replace('/ /', '_', $name);
		$obj_name = strtolower($obj_name);
		
		if (isset($this->$obj_name))
			return $this;		

		$shortcuts = array('/^ta$/','/^sel$/', '/^hid$/');
		$methods = array('textarea','select', 'hidden');
		$type = preg_replace($shortcuts,$methods,$type);
		
		
		$defaults = $this->_make_defaults($type, $name);
		$defaults_globals = array_merge($this->globals, $defaults);
		
		$info = self::quicktags($info);
		$info = array_merge($defaults_globals, $info);
				
		$el = new Formo_Element($type,$name);
		$this->$obj_name = $el;
		
		$el->add_info($info);
		
		$this->_attach_auto_rule($obj_name);

		if ($el->type == 'file')
		{
			$this->open = preg_replace('/>/',' enctype="multipart/form-data">', $this->open);
		}
		elseif ($el->type == 'bool')
		{
			if ($el->value) $this->check($obj_name);
			$this->$obj_name->value = 0;
			$this->$obj_name->required = FALSE;
		}
		

		return $this;
	}
	
	/**
	 * add_group method. Add a new Form_Group object to form
	 *
	 * @return object
	 */
	public function add_group($type, $name, $values, $info=NULL)
	{
		$add_name = strtolower(preg_replace('/\[\]/','',$name));
		$this->$add_name = Formo_Group::factory($type, $name, $values, $info);
		return $this;
	}
			
	/**
	 * _attach_auto_rule method. Attach an auto rule to appropriate element
	 *
	 */
	private function _attach_auto_rule($name)
	{
		if ( ! isset($this->auto_rules[$name]))
			return;
		
		if (is_array($this->auto_rules[$name][0]))
		{
			foreach ($this->auto_rules[$name] as $rule)
			{
				$this->add_rule($name, $rule[0], $rule[1]);
			}
		}
		elseif (is_array($this->auto_rules[$name]))
		{
			$this->add_rule($name, $this->auto_rules[$name][0], $this->auto_rules[$name][1]);
		}
		else
		{
			$this->add_rule($name, $this->auto_rules[$name]);
		}		
	}
	
	/**
	 * add_select method. Special function for adding a new select element object
	 * to form
	 *
	 * @return object
	 */
	public function add_select($name,$values,$info=array())
	{
		$info = $this->quicktags($info);
		$info['values'] = $values;
		$this->add('select',$name,$info);
		return $this;
	}
		
	/**
	 * add_html method. Special function for adding a new html element object
	 * to form
	 *
	 * @return object
	 */
	public function add_html($name,$value)
	{
		$info['value'] = $value;
		$this->add('html',$name,$info);
		return $this;
	}

	/**
	 * add_image method. Special function for adding a new image element object
	 * to form
	 *
	 * @return object
	 */
	public function add_image($name,$src,$info=array())
	{
		$info = $this->quicktags($info);
		$info['src'] = $src;
		$this->add('image',$name,$info);
		return $this;
	}

	/**
	 * remove method. Removes an element from form object
	 *
	 * @return object
	 */
	public function remove($element)
	{
		if (is_array($element))
		{
			foreach ($element as $el)
			{
				unset($this->$el);
				unset($this->elements[$el]);
			}
		}
		else
		{
			unset($this->$element);
			unset($this->elements[$element]);
		}
		
		return $this;
	}

	/**
	 * find_elements method. return all elements. Use order if applicable
	 *
	 * @return array
	 */	
	public function find_elements()
	{
		if ($this->order)
		{
			$elements[] = '__form_object';
			foreach ($this->order as $v)
			{
				$elements[] = $v;
			}
			return $elements;
		}
		else
		{
			return array_keys($this->elements);
		}
	}

	/**
	 * _elements_to_string method. Build a string of formatted text with all the
	 * form's elements
	 *
	 * @return string
	 */		
	private function _elements_to_string($return_as_array=FALSE)
	{
		foreach (($elements = $this->find_elements()) as $key)
		{
			if ($return_as_array)
			{			
				$elements_array[$key] = $this->$key;
				$elements_array[$key.'_error'] = $this->$key->error;
			}
			else
			{
				$this->_filter_label($key);
				$this->_elements_str.= $this->$key->get();			
			}
	
		}
		if ($return_as_array)
			return $elements_array;
			
		return $this->_elements_str;
	}
	
	/**
	 * _make_opentag method. Format form open tag
	 *
	 * @return string
	 */	
	private function _make_opentag()
	{
		$search = array('/{action}/','/{method}/','/{class}/');
		$replace = array($this->action,$this->method,$this->class);
		return preg_replace($search,$replace,$this->open);
	}
	
	/**
	 * _make_closetag method. Format form close tag
	 *
	 * PROBABLY AN UNNECESSARY FUNCTION
	 *
	 * @return string
	 */	
	private function _make_closetag()
	{
		return $this->close;
	}
				
	/**
	 * get method. Retun formatted array or entire form
	 *
	 * @return string or array
	 */			
	public function get($as_array=FALSE)
	{
		$this->add_posts();
		$this->_do_pre_filters();

		Event::run('formo.pre_render');

		if ($this->sent)
		{
			$this->validate(TRUE);
		}
		
		
		$return = ($as_array == TRUE) ? $this->_get_array() : $this->_get();
		
		Event::run('formo.post_render');
		
		return $return;
	}

	/**
	 * render method. Alias for get()
	 *
	 * @return string or array
	 */				
	public function render($as_array=FALSE)
	{
		$this->get($as_array);
	}	

	/**
	 * _get_array method. Used with get, processes into an array
	 *
	 * @return array
	 */			
	private function _get_array()
	{
		$form = $this->_elements_to_string(TRUE);
		$form['open'] = $this->_make_opentag()."\n".$this->__form_object->get();
		$form['close'] = $this->_make_closetag();
		
		return $form;
	}

	/**
	 * _get method. Used with get, processes into an string
	 *
	 * @return string
	 */				
	private function _get()
	{	
		$form = $this->_make_opentag()."\n";
		$form.= $this->_elements_to_string();
		$form.= $this->_make_closetag()."\n";
		
		return $form;
	}
	
	public function get_values($element=NULL)
	{
		if ($element)
			return $this->$element->get_value();

		$values = array();
		foreach (($elements = $this->find_elements()) as $element)
		{
			if (($val = $this->$element->get_value()) === FALSE)
				continue;
				
			$values[$element] = $val;
		}
		return $values;
	}	

}	// End Form