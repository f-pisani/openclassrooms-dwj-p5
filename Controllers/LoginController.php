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
		$DataValidator->addFieldRules('nickname', [['ruleset'=>'nickname']]);
		$DataValidator->addFieldRules('pwd_register', [['match-field'=>'pwd_conf', 'msg'=>"Les deux mots de passes sont différents."]]);
		$DataValidator->addFieldRules('pwd_conf', [['match-field'=>'pwd_register', 'msg'=>"Les deux mots de passes sont différents."]]);

		if (Request::get('submit') && $DataValidator->validate())
		{
			$UserLogin = new UserLogin();
			$UserLogin->fill(['nickname' => $data['nickname'],
							  'email' => $data['email_register'],
						  	  'password' => $data['pwd_register']]);

			try
			{
				$result = $UserLogin->create();
			}
			catch(\App\DatabaseException $e)
			{
				$DataValidator->addErrorMsg('email_register', "Cette adresse email est déjà utilisée.");
				var_dump($UserLogin::isEmailAvailable($data['email_register']));
			}
		}

		$title = "Inscription / Connexion";
		return View::render('login', 'layouts/login', compact('title', 'DataValidator'));
	}
}
?>
