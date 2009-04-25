<?php defined('SYSPATH') or die('No direct script access.');

/*=====================================

Formo Plugin Success

Version 0.9

Run successive on_success or on_failure
functions automatically upon validation success
and failure.

Formo Functions Added:
	add_success
	add_failure

Formo Properties Bound:
	
Formo Events Used:
	post_validate

=====================================*/


class Formo_success {

	public $form;
	
	public $on_success = array();
	public $on_failure = array();
	
	public function __construct( & $form)
	{
		$this->form =& $form;
		Event::add('formo.post_validate', array($this, 'do_functions'));
		
		$this->form
			->add_function('add_success', array($this, 'add_success'))
			->add_function('add_failure', array($this, 'add_failure'));
	}
	
	public static function load( & $form)
	{
		return new Formo_success($form);
	}
			
	public function add_success($function)
	{
		$this->on_success[] = $function;
	}
	
	public function add_failure($function)
	{
		$this->on_failure[] = $function;
	}
	
	public function do_functions()
	{
		$use = ( ! $this->form->validated) ? 'on_failure' : 'on_success';
		
		foreach ($this->$use as $string)
		{
			list($function, $args) = Formo::into_function($string);
			array_unshift($args, $this->form->get_values());

			call_user_func_array($function, $args);
		}
	}

}