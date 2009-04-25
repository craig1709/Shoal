<?php defined('SYSPATH') or die('No direct script access.');
 
class menu_Core {
 
 	protected $html;
 
	public static function render($menu)
	{
		$html = '';
	
		foreach ($menu as $title => $href)
		{
			$attr = (url::current() == $href) ? array('class' => 'current') : array();

			$html .= '<li>' . html::anchor($href, $title, $attr) . '</li>';
			
		}
		
		return $html;
	}
}
 
?>
