<?php
namespace Controllers;
use App\{Config, Request, DataValidator, Controller, View, DB};
use Models\{UserLogin};

class LoginController extends Controller
{
	public function index()
	{
		return View::render('login', 'layouts/login');
	}

	public function register()
	{
		$data = Request::assoc('nickname,email_register,pwd_register,pwd_conf');
		$DataValidator = new DataValidator($data);
		$DataValidator->addFieldRules('email_register', [['ruleset'=>'email']]);
		$DataValidator->addFieldRules('nickname', [['empty'=>false, 'msg' => "Le champ est obligatoire."], ['length'=>'3|32', 'msg'=>"Le nom d'utilisateur doit être compris entre 3 et 12 caractères."]]);

		if (Request::get('submit') && $DataValidator->validate())
		{
			//var_dump($data);
		}
		else
		{
			//var_dump($DataValidator->errorToArray());
		}

		$title = "Inscription / Connexion";
		return View::render('login', 'layouts/login', compact('title', 'DataValidator'));
	}
}
?>
