<?php defined('SYSPATH') or die('No direct script access.');

/*=====================================

Formo Plugin Orm

Version 0.9.9

Auto-generate a form using orm models

Formo Functions Added:
	orm
	save

Formo Properties Bound:
	ignores
	aliases
	auto_save
	
Formo Events Used:
	pre_addpost
	post_addpost
	post_validate

=====================================*/


class Formo_orm {

	public $form;

	public $model = array();
	public $model_array = array();
	public $ignores = array();
	public $auto_save = FALSE;
	public $aliases = array();
	public $cleared = FALSE;
	
	public function __construct( & $form)
	{
		Event::add('formo.pre_addpost', array($this, 'pre_add_posts'));
		Event::add('formo.post_addpost', array($this, 'add_posts'));
		Event::add('formo.post_validate', array($this, 'auto_save'));
		
		$this->form = $form;		
		$this->form
			->add_function('orm', array($this, 'orm'))
			->add_function('save', array($this, 'save'))
			->add_function('clear', array($this, 'clear'))
			->add_function('get_model', array($this, 'get_model'))
			->bind('form', $form, TRUE)
			->bind('ignores', $this->ignores)
			->bind('aliases', $this->aliases)
			->bind('auto_save', $this->auto_save);
	}
	
	public static function load( & $form)
	{
		return new Formo_orm($form);
	}
	
	public function orm($_model, $id=0)
	{
		$this->model[$_model] = ORM::factory($_model, $id);
		$this->model_array[$_model] = array_keys($this->model[$_model]->as_array());
		
		$settings = array
		(
			'formo_ignores'			=> 'ignores',
			'formo_globals'			=> 'globals',
			'formo_defaults'		=> 'defaults',
			'formo_rules'			=> 'auto_rules',
			'formo_label_filters'	=> 'label_filters',
			'formo_order'			=> 'order'
		);
		
		foreach ($settings as $orm_name=>$name)
		{
			if ( ! empty($this->model[$_model]->$orm_name))
			{
				$this->form->$name = array_merge($this->form->$name, $this->model[$_model]->$orm_name);
			}
		}
		
		if ( ! empty ($this->model[$_model]->formo_aliases))
		{
			$this->aliases[$_model] = (isset($this->aliases[$_model]))
									? array_merge($this->aliases[$_model], $this->model[$_model]->formo_aliases)
									: $this->model[$_model]->formo_aliases;
		}
		
		if (isset($this->model[$_model]->formo_auto_save))
		{
			$this->auto_save = $this->model[$_model]->formo_auto_save;
		}
						
		foreach ($this->model[$_model]->table_columns as $field => $value)
		{
			$alias_field = $field;
			if (isset($this->form->aliases[$_model][$field]))
			{
				$alias_field = $this->form->aliases[$_model][$field];
			}
						
			if (in_array($field, $this->form->ignores))
				continue;
			
			// relational tables
			$fkey = preg_replace('/_id/','',$field);

			if (in_array($fkey, $this->model[$_model]->belongs_to) OR in_array($fkey, $this->model[$_model]->has_one))
			{
				$values = array('_blank_'=> '');				
				$modeler = $this->model[$_model]->$fkey->find_all();
				
				foreach ($modeler as $value)
				{
					$primary_val = $value->primary_val;
					$primary_key = $value->primary_key;
					$values[$value->$primary_val] = $value->$primary_key;
				}
				$this->form->add_select($alias_field,$values,array('value'=>$this->model[$_model]->$field));
			}
			else
			{
				$this->form->add($alias_field, array('value'=>$this->model[$_model]->$field));
			}
		}
	}
	
	public function pre_add_posts()
	{
		if ($this->form->post_added)
			return;
			
		if ($this->cleared)
		{
			$this->form->post_added = TRUE;
		}
	}
	
	public function add_posts()
	{
		if ( ! $this->form->post_type)
			return;
		
		$type = $this->form->post_type;
		
		$post = Input::instance();
		foreach ($this->model_array as $model=>$array)
		{
			foreach ($array as $key)
			{
				$model_field = $key;
				if ( ! empty($this->aliases[$model]))
				{
					$model_field = (in_array($key, $this->aliases[$model]))
								 ? array_search($key, $this->aliases[$model])
								 : $model_field;
				}
				
				if ( ! isset($this->form->$model_field))
					continue;
								
				if ($value = $post->$type($model_field))
				{
					$this->model[$model]->$model_field = $value;
				}
				elseif ($this->form->$key->type == 'bool')
				{
					$this->model[$model]->$model_field
						= (isset($_POST[$key]))
						? 1
						: 0;
				}
			}
		}		
	}
	
	public function clear()
	{
		foreach ($this->form->find_elements() as $element)
		{
			if ($this->form->$element->type == 'submit')
				continue;
			if ($element == '__form_object')
				continue;
			
			$this->form->$element->value = NULL;
		}

		$this->form->post_added = FALSE;
		$this->form->was_validate = FALSE;
		$this->form->validated = FALSE;
		$this->form->sent = FALSE;
		$this->cleared = TRUE;
		
		return $this;
	}		
	
	public function save($name = '')
	{
		if ($name)
		{
			$this->model[$name]->save();
		}
		else
		{
			foreach ($this->model as $name=>$model)
			{
				$model->save();
			}
		}				
	}
	
	public function get_model($name = NULL)
	{
		return ($name) ? $this->model[$name] : $this->model;
	}
	
	public function auto_save()
	{
		// auto-save ORM models
		if ( ! $this->form->error AND $this->auto_save === TRUE AND $this->model)
		{
			foreach ($this->model as $name=>$model)
			{
				$model->save();
			}
		
		}	
	}
	

}