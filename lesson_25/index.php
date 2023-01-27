<?
require_once (__DIR__.'/crest.php');
?>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="css/app.css">
	<!--link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"-->
	<script
		src="https://code.jquery.com/jquery-3.6.0.js"
		integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk="
		crossorigin="anonymous"></script>

	<title>Url Placement</title>
</head>
<body class="container-fluid">
	<div class="alert alert-success" role="alert"><pre>
		<?
		print_r($_REQUEST);
		?>
		</pre>
	</div>
	<form>
		<input type="hidden" name="saved" value="1">
		<button type="submit" class="btn btn-default">Загрузить документы</button>
	</form>
	<?if ($_REQUEST['saved'] == 1):

		$my_app_code = 'vstrtest.app10';

		$result = CRest::call(
			'log.blogpost.add',
			[
				'POST_MESSAGE' => '[p]Коллеги, были обновлены версии лицензионного договора. Необходимо 
				обсудить и выбрать одну версию для дальнейшей работы[/p]
				[LIST]
				[*][url=/marketplace/view/'.$my_app_code.'/?params[doc]=123]Версия 1[/url]
				[*][url=/marketplace/view/'.$my_app_code.'/?params[doc]=234]Версия 2[/url]
				[*][url=/marketplace/view/'.$my_app_code.'/?params[doc]=345]Версия 3[/url]
				[/LIST]
				',
				'POST_TITLE' => 'Договор для обсуждения'
			]
		);

		echo 'Документы загружены!';
	?>

	<?endif;?>
</body>