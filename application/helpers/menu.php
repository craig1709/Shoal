<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Menu helper. Renders a <ul> element from an array.
 *
 * @package		Shoal
 * @author		Shoal Team
 * @copyright	(c) 2009 Shoal Team
 * @license		http://www.gnu.org/licenses/gpl-3.0-standalone.html
 */
class menu_Core {
 
 	protected $html;
 
	public static function render($menu, $id = "menu", $prefix = FALSE, $current = 1)
	{
		$html = "<ul id=\"$id\">";
	
		foreach ($menu as $href => $title)
		{
			$attr = (uri::segment($current) == $href) ? array('class' => 'current') : array();
			
			if ($prefix) {
				$prepend = uri::segment(1) . '/' . uri::segment(2, 'index');
				$href = $prepend . '/' . $href;
			}
			
			$html .= '<li>' . html::anchor($href, $title, $attr) . '</li>';
			
		}
		
		$html .= '</ul>';
		
		return $html;
	}
}
