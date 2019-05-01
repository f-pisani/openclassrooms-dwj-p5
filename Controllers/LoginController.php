<?php
namespace Controllers;
use App\{Config, Request, Controller, View, DB};
use Models\{UserLogin};

class LoginController extends Controller
{
	public function index()
	{
		return View::render('login', 'layouts/login');
	}

	public function register()
	{
		$nickname = Request::get('nickname');
		$email = Request::get('email_register');
		$pwd = Request::get('pwd_register');
		$pwdConf = Request::get('pwd_conf');
		echo $nickname." ".$email." ".$pwd." ".$pwdConf;

		return View::render('login', 'layouts/login');
	}
}
?>
