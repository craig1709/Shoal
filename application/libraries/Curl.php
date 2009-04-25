<?php defined('SYSPATH') or die('No direct script access.');
/**
 * The CURL library provides an object oriented interface
 * to the procedural CURL PHP functions, the class only
 * allows for a single CURL session per instance - as curl_init
 * is called from the constructor...
 *
 * $Id: Curl.php 1 2009-02-20 18:44:16Z ixmatus $
 *
 * @package		Standard
 * @subpackage	Libraries
 * @category	cURL Abstraction
 * @author		Parnell Springmeyer <parnell@rastermedia.com>
 * @todo 		Nada
 */

class Curl_Core
{
	protected $curl		= Null;
	public $options	= array();
	
	/**
	 * Instantiate the CURL lib and set any
	 * options that may have been supplied.
	 *
	 * Initialize CURL.
	 *
	 * @param	array $options
	 */
	public function __construct($options=Null)
	{
		// Set our options
		if(is_array($options))
			$this->options = $options;
		
		// Is cURL installed?
		if(!function_exists('curl_init'))
			throw new Kohana_User_Exception('A cURL error occurred', 'It appears you do not have cURL installed!');
		
		// Initialize curl session
		$this->curl	= curl_init();
	}
	
	/**
	 * Execute the current CURL session with
	 * the provided options.
	 *
	 * @return	mixed
	 */
	public function execute()
	{
		// Set the options array
		curl_setopt_array($this->curl, $this->options);
		
		// Execute the session!
		$status	= curl_exec($this->curl);
		
		// Check for any errors, if any occurred throw an exception...
		if($errno=curl_errno($this->curl)>0)
			throw new Kohana_User_Exception('A cURL error occurred - '.$errno, curl_error($this->curl));
		
		// Return the status if no errors occurred
		return $status;
	}
	
	/**
	 * Add an option to the CURL options array.
	 *
	 * Any calls to this method must happen *before*
	 * execute() is called.
	 *
	 * @return	object (chainable)
	 */
	public function addOption($option, $value)
	{
		// Add the option and its value to the array
		$this->options[$option]	= $value;
		
		// Return object self
		return $this;
	}
	
	/**
	 * Be sure to destroy our CURL session
	 */
	public function __destroy()
	{
		curl_close($this->curl);
	}
} // End Curl Core
