<?php
namespace App;

class ViewException extends \Exception {};

class View
{
	public static $Content='';

	public static function render($view, $layout='', $data=[])
	{
		if ($layout) $layout = APP_PATH.'/Views/'.$layout.'.php';
		$view = APP_PATH.'/Views/'.$view.'.php';

		if(file_exists($layout) || !$layout)
		{
			if(file_exists($view))
			{
				extract($data);

				ob_start();
				require $view;
				self::$Content = ob_get_contents();
				ob_clean();

				if ($layout) require $layout;
				else echo self::$Content;
			}
			else
				throw new ViewException("View::render(): Couldn't load view '$view'.");
		}
		else
			throw new ViewException("View::render(): Coundn't load layout '$layout'.");
	}
}
