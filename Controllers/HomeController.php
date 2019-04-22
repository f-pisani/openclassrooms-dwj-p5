<?php
namespace Controllers;
use App\{Config, Controller, View};

class HomeController extends Controller
{
	public function index()
	{
		return View::render('home', 'layouts/login');
	}
}
?>
