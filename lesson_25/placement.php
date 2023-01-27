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

	<title>Placement</title>
</head>
<body class="container-fluid">
<div class="alert alert-success" role="alert">
	<?
	echo 'placement: '.$_REQUEST['PLACEMENT'].'<br/>';
	echo 'placement options: '.$_REQUEST['PLACEMENT_OPTIONS'].'<br/>';
	?>
</div>
<h2>Просмотр документа</h2>
<?
$placement_options = json_decode($_REQUEST['PLACEMENT_OPTIONS'], true);
?>
<div style="height: 500px; overflow-y: scroll; overflow-wrap: break-word;">
<pre><?echo file_get_contents('docs/'.$placement_options['doc'].'.txt');?></pre>
</div>
</body>