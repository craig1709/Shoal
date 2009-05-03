<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * User authorization library. Handles user login and logout, as well as secure
 * password hashing.
 *
 * @package		Simpleauth
 * @author		Craig Roberts
 * @copyright	(c) 2009 Craig Roberts
 * @license		http://www.gnu.org/licenses/gpl-3.0-standalone.html
 */
class Simpleauth_Core {

	/**
	 * Create an instance of Simpleauth.
	 *
	 * @return  object
	 */
	public static function factory()
	{
		return new Simpleauth;
	}
	
	/**
	 * Return a static instance of Auth.
	 *
	 * @return  object
	 */
	public static function instance()
	{
		static $instance;

		// Load the Auth instance
		empty($instance) and $instance = new Simpleauth;

		return $instance;
	}
	
	/**
	 * Initialises session
	 * @return	void
	 */
	public function __construct()
	{
		$this->db = Database::instance();
		$this->session = Session::instance();
		$this->config = Kohana::config('simpleauth');
		Kohana::log('debug', 'Simpleauth loaded');
	}
	
	/**
	 * Attempt to log in a user by using an ORM object and plain-text password.
	 *
	 * @param   string   username to log in
	 * @param   string   password to check against
	 * @return  boolean
	 */
	public function login($username, $password)
	{
		$password = $this->hash_password($password);
		$user = $this->db->getwhere('users', array('username' => $username, 'password' => $password));
		if ($user->count() == 1) {
			$this->session->set('loggedin', $user);
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Returns the currently logged in user, or FALSE.
	 *
	 * @return  mixed
	 */
	public function get_user()
	{
		return $this->session->get('loggedin', FALSE);
	}
	
	/**
	 * Logs a user out by destroying all session data
	 * 
	 * @return void
	 */
	public function logout()
	{
	}
	
	/**
	 * Generates a salted, hashed password for comparison or insertion
	 *
	 * @return string
	 */
	public function hash_password($password)
	{
		$password = $this->config['salt'] . $password . $this->config['salt'];
		$password = sha1($password);
		return $password;
	}
	
	/**
	 * Checks whether a user has permission to access a resource's method
	 *
	 * @return bool
	 */
	public function has_permission($resource, $method)
	{
	}

}
