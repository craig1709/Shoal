<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Users Controller
 *
 * @package		Shoal
 * @author		Shoal Team
 * @copyright	(c) 2009 Shoal Team
 * @license		http://www.gnu.org/licenses/gpl-3.0-standalone.html
 */

class Users_Controller extends Shoal_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->template->loggedin = false;
	}
	
	public function index($letter = "a")
	{
		$view = new View('common/listing');
		$view->title = 'All Users';
		$view->alphanum = alphanum::alpha();
		
		$users = $this->db->from('users')->like('username', "$letter%", FALSE)->get();
		$view->entries = $users->result_array(FALSE);
		
		$this->template->content = $view;
	}
	
	public function login()
	{
		$view = new View('users/login');
		
		if (isset($_POST['username'])) {
			if ($this->sauth->login($_POST['username'], $_POST['password'])) {
				url::redirect('users');
			} else {
				$view->errors = Kohana::lang('simpleauth.login_failed');
			}
		}
		
		$this->template->content = $view;
	}
	
	public function details($user_id)
	{
		$view = new View('users/details');
		
		$user = ORM::factory('user', $user_id);
		$view->user = $user;
		
		$this->template->content = $view;
	}

}
