<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Implements a bespoke authentication mechanism with users, roles and groups.
 *
 * @package    MIS
 * @author     Craig Roberts
 * @license    http://www.gnu.org/licenses/gpl-3.0-standalone.html
 */

class Auth_Core
{

	protected $db;

	public function __construct()
	{
		$this->db = Database::instance();
		$this->session = Session::instance();
	}
	
	public function login($username, $password)
	{
		$salt = Kohana::config('encryption.key');
		$password = md5($salt . $_POST['password']);
		
		$user = ORM::factory('user', $username)->where(array('password' => $password))->find();
		
		// Incorrect username or password, deny the login attempt
		if ($user->loaded == FALSE) {
			return FALSE;
		}
		
		foreach ($user->groups as $group) {
			foreach ($group->roles as $role) {
				if ($role->name = 'login') {
					// User has login role so let them login
					$this->session->set('login', $user->id);
					return TRUE;
				}
			}
		}
		
		// User doesn't have login role, deny the login attempt
		return FALSE;
	}
	
	public function logged_in()
	{
		if ($this->session->get('login', FALSE) !== FALSE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function logout()
	{
		$this->session->delete('login');
	}

} // End Auth Core
