<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Shoal parent controller
 *
 * @package		Shoal
 * @author		Shoal Team
 * @copyright	(c) 2009 Shoal Team
 * @license		http://www.gnu.org/licenses/gpl-3.0-standalone.html
 */

class Shoal_Controller extends Template_Controller {

	public function __construct()
	{
		parent::__construct();
		
		$menu = Kohana::config('shoal.menu');
		$this->sauth = Simpleauth::instance();
		$this->db = Database::instance();
		$this->template->menu = $menu;
		
		if (!$this->sauth->get_user() && url::current() !== 'users/login') {
			url::redirect('users/login');
		}
	}

}

