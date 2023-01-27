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

	<title>Contact form</title>
</head>
<body class="container-fluid">
<div class="row">
    <div class="col-lg-6 col-md-6">

<? if (empty($_REQUEST)): ?>

	<form method="post">
		<div class="form-group">
			<label for="exampleInputName">First name:</label>
			<input type="text" class="form-control" id="exampleInputName" name="exampleInputName">
		</div>
		<div class="form-group">
			<label for="exampleInputLastName">Last name:</label>
			<input type="text" class="form-control" id="exampleInputLastName" name="exampleInputLastName">
		</div>
		<div class="form-group">
			<label for="exampleInputPhone">Phone</label>
			<input type="text" class="form-control" id="exampleInputPhone" name="exampleInputPhone">
		</div>
		<button type="submit" class="btn btn-default">Submit</button>
	</form>
<? else: ?>
	<div class="alert alert-success" role="alert">Thank you very much for filling in!</div>
	<?

	$result = CRest::call(
		'crm.contact.add',
		[
			'FIELDS' => [
				'NAME' => $_REQUEST['exampleInputName'],
				'LAST_NAME' => $_REQUEST['exampleInputLastName'],
				'PHONE' => [
					'0' => ['VALUE' => $_REQUEST['exampleInputPhone'], 'VALUE_TYPE' => 'WORK'],
				],
			],
		]
	);

	?>

<? endif; ?>
    </div>
</div>
</body>
</html>
