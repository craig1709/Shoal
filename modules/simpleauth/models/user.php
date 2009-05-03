<?php defined('SYSPATH') OR die('No direct access allowed.');

class User_Model extends ORM {

	protected $has_one = array('dob' => 'date', 'joined' => 'date');
	
}
