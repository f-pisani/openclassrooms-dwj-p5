<?php use App\{Config}; ?>

<h1>Project<span>flow</span></h1>
<div id="home-forms">
	<form id="form-register" class="form" action="<?=SITE_URL.'/register'?>" method="post">
		<h2>Rejoinez-nous ! Commencez de nouveaux projets.</h2>
		<div class="form-row">
			<label class="form-label" for="email_register">E-mail :</label>
			<input class="form-input" type="email" id="email_register" name="email_register" placeholder="ex: jean.dupont@gmail.com" value="">
		</div>
		<div class="form-row">
			<label class="form-label" for="pwd_register">Mot de passe :</label>
			<input class="form-input" type="password" id="pwd_register" name="pwd">
		</div>
		<div class="form-row">
			<label class="form-label" for="pwd_conf">Confirmation mot de passe :</label>
			<input class="form-input" type="password" id="pwd_conf" name="pwd_conf">
		</div>
		<button class="form-btn btn-right" type="submit" name="register" value="true">Je m'inscris</button>
	</form>

	<form id="form-login" class="form" action="<?=SITE_URL.'/login'?>" method="post">
		<h2>Suivez l'avancement de vos projets. Connectez-vous !</h2>
		<div class="form-row">
			<label class="form-label" for="email">E-mail :</label>
			<input class="form-input" type="email" id="email" name="email" placeholder="ex: jean.dupont@gmail.com" value="">
		</div>
		<div class="form-row">
			<label class="form-label" for="pwd">Mot de passe :</label>
			<input class="form-input" type="password" id="pwd" name="pwd">
		</div>
		<button class="form-btn btn-right" type="submit" name="login" value="true">Connexion</button>
	</form>
</div>
