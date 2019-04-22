<?php use App\{Config, View}; ?>

<!DOCTYPE html>
<html lang="fr">
	<head>
		<title><?= $title ?? 'Default title' ?></title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

		<link href="https://fonts.googleapis.com/css?family=Nunito:400,700|Lato:400,700" rel="stylesheet">
		<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/all.css"
		integrity="sha384-hWVjflwFxL6sNzntih27bfxkr27PmbbK/iSvJ+a4+0owXq79v+lsFkW54bOGbiDQ" crossorigin="anonymous">
		<link href="<?=SITE_URL.'/css/style.css'; ?>" rel="stylesheet">
		<link href="<?=SITE_URL.'/css/style_responsive.css'; ?>" rel="stylesheet">

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<script src="<?=SITE_URL.'/js/dashboard.js';?>"></script>
		<script src="<?=SITE_URL.'/js/board.js';?>"></script>
		<script src="<?=SITE_URL.'/js/colorpicker.js';?>"></script>
	</head>
	<body>
		<div id="wrapper">
			<?php include 'navbar.php'; ?>
			<div id="content">
				<?=View::$Content?>
			</div>
		</div>
	</body>
</html>
