<?php defined('SYSPATH') or die('No direct script access.');

class Formo_user_Model extends ORM {
	
	protected $has_one = array('formo_location');
	protected $belongs_to = array('formo_comp');
	
	public $formo_ignores = array('id');
	public $formo_defaults = array
	(
		'formo_location_id'	=> array
		(
			'label'	=> 'Location'
		),
		'formo_comp_id'		=> array
		(
			'label'	=> 'Company'
		),
		'is_neat'			=> array
		(
			'type'		=> 'bool',
			'label'		=> 'Neat',
		),
		'likes_running'		=> array
		(
			'type'		=> 'bool',
			'label'		=> 'Likes Running'
		),
		'basketball'		=> array
		(
			'type'		=> 'bool',
			'label'		=> 'Basketball?'
		)
	);
	
}