<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Alphanumeric related functions.
 *
 * @package		Shoal
 * @author		Shoal Team
 * @copyright	(c) 2009 Shoal Team
 * @license		http://www.gnu.org/licenses/gpl-3.0-standalone.html
 */
class alphanum_Core {
 
	public static function alpha()
	{
		$alphanum = array();
		for ($i=97; $i<=122; $i++) {
			$char = chr($i);
			$alphanum[$char] = strtoupper($char);
		}
		return $alphanum;
	}
	
	public static function num()
	{
		$alphanum = array();
		for ($i=48; $i<=57; $i++) {
			$char = chr($i);
			$alphanum[$char] = $char;
		}
		return $alphanum;
	}

}
