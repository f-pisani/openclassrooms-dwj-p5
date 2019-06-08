<?php use App\{Config, Request}; ?>

<h1>Project<span>flow</span></h1>


<div id="form-wrapper">
	<div id="form-register">
		<form <?=(Request::get('submit') == 'register') ? 'style="opacity: 1; display: flex;"' : 'style="opacity: 0; display: none;"'?> class="form" action="<?=SITE_URL.'/register'?>" method="post">
			<h2>Rejoinez-nous ! Commencez de nouveaux projets.</h2>
			<div class="form-column">
				<label class="form-label" for="nickname">Nom d'utilisateur</label>
				<input class="form-input" type="input" id="nickname" name="nickname" placeholder="ex: Jean Dupont" value="<?=Request::get('nickname')?>" required="true" autocomplete="off">
			</div>
			<div class="form-column">
				<label class="form-label" for="email_register">E-mail</label>
				<input class="form-input" type="email" id="email_register" name="email_register" placeholder="ex: jean.dupont@gmail.com" value="<?=Request::get('email_register')?>" required="true" autocomplete="off">
			</div>
			<div class="form-column">
				<label class="form-label" for="pwd_register">Mot de passe</label>
				<input class="form-input" type="password" id="pwd_register" name="pwd_register" required="true" autocomplete="off">
			</div>
			<div class="form-column">
				<label class="form-label" for="pwd_conf">Confirmation mot de passe</label>
				<input class="form-input" type="password" id="pwd_conf" name="pwd_conf" required="true" autocomplete="off">
			</div>
			<div class="form-actions">
				<button class="form-btn btn-left" type="submit" name="submit" value="register">Je m'inscris</button>
			</div>
		</form>

		<div <?=(Request::get('submit') == 'register') ? 'style="opacity: 0; display: none;"' : 'style="opacity: 1; display: flex;"'?> class="form-logo">
			<h2>Inscrivez-vous !</h2>
			<i class="fas fa-users"></i>
		</div>
	</div>

	<div id="form-login">
		<form <?=(Request::get('submit') == 'login') ? 'style="opacity: 1; display: flex;"' : 'style="opacity: 0; display: none;"'?> class="form" action="<?=SITE_URL.'/login'?>" method="post">
			<h2>Suivez l'avancement de vos projets. Connectez-vous !</h2>
			<div class="form-column">
				<label class="form-label" for="email">E-mail</label>
				<input class="form-input" type="email" id="email" name="email" placeholder="ex: jean.dupont@gmail.com" value="<?=Request::get('email')?>">
			</div>
			<div class="form-column">
				<label class="form-label" for="pwd">Mot de passe</label>
				<input class="form-input" type="password" id="pwd" name="pwd">
			</div>
			<div class="form-actions">
				<button class="form-btn" type="submit" name="submit" value="login">Connexion</button>
			</div>
		</form>

		<div <?=(Request::get('submit') == 'login') ? 'style="opacity: 0; display: none;"' : 'style="opacity: 1; display: flex;"'?> class="form-logo">
			<h2>Connectez-vous !</h2>
			<i class="fas fa-sign-in-alt"></i>
		</div>
	</div>
</div>

<script>
$(document).ready(function(){
	let jFormRegister = new jForm({selector: '#form-register > .form',
								   errors: <?=$DataValidator->errorToJson()?>});

	function formFocusIn(e)
	{
		if ($(this).width() / $(this).parent().width() * 100 > 60) return; // Already animated

		var altForm = $(this).siblings().get(0);
		var targetWidth = ($(document).width() > 1200) ? "70%" : "80%";
		var altFormTargetWidth = ($(document).width() > 1200) ? "30%" : "80%";

		$(this).animate({width: targetWidth}, "fast", "swing", ()=>{});
		$(altForm).animate(
			{width: altFormTargetWidth},
			{duration: "fast",
			 ease: "swing",
		     start: (item) => {

				var parentElem = item.elem;
		      	$(parentElem).children('form').animate(
					{opacity: "0"},
					{duration: "fast",
		  			 ease: "swing",
					 complete: () => {

						$(parentElem).children('form').css('display', 'none');
						$(parentElem).children('.form-logo').animate(
							{opacity: "1"},
							{duration: "fast",
				   			 ease: "swing",
				   			 start: (item) => { $(item.elem).css('display', 'flex'); }
						});

					}});

		  	}
		});
	}

	function openFormFromLogo(e)
	{
		var altForm = $(this).parent().siblings().get(0);
		var targetWidth = ($(document).width() > 1200) ? "50%" : "80%";
		var altFormTargetWidth = ($(document).width() > 1200) ? "50%" : "80%";

		$(this).parent().animate(
			{width: targetWidth, opacity: "0"},
			{duration: "fast",
			 ease: "swing",
		     complete: () => {
				var parentElem = $(this).parent();
				$(parentElem).children('.form-logo').css('display', 'none');

		      	$(parentElem).animate(
					{opacity: "1"},
					{duration: "fast",
		  			 ease: "swing",
					 start: (item) => {
 						$(parentElem).children('form').css('display', 'flex');
						$(parentElem).children('form').animate(
							{opacity: "1"},
							{duration: "fast",
				  			 ease: "swing"});
					 }}
				 );

		  	}
		});

		$(altForm).animate(
			{width: altFormTargetWidth},
			{duration: "fast",
			 ease: "swing",
		     start: (item) => {

				var parentElem = item.elem;
		      	$(parentElem).children('form').animate(
					{opacity: "0"},
					{duration: "fast",
		  			 ease: "swing",
					 complete: () => {

						$(parentElem).children('form').css('display', 'none');
						$(parentElem).children('.form-logo').animate(
							{opacity: "1"},
							{duration: "fast",
				   			 ease: "swing",
				   			 start: (item) => { $(item.elem).css('display', 'flex'); }});

					}});

		  	}
		});
	}

	$('#form-register').focusin(formFocusIn);
	$('#form-register .form-logo').click(openFormFromLogo);
	$('#form-login').focusin(formFocusIn);
	$('#form-login .form-logo').click(openFormFromLogo);
<?php
if(Request::get('submit') == 'register')
{
?>
	$('#form-register').focusin();
<?php
}
else if(Request::get('submit') == 'login')
{
?>
	$('#form-login').focusin();
<?php
}
?>
});
</script>
