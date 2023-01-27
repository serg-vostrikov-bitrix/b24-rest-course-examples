<?
require_once (__DIR__.'/crest.php');

function displayValue($value) {
	if (is_array($value)) {
		$result = '';
		foreach ($value as $item) $result .= $item.', ';
		return $result;

	} else return $value;
}

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
    <script src="//api.bitrix24.com/api/v1/"></script>

	<title>Placement</title>
</head>
<body class="container">
<div class="alert alert-success" role="alert"><pre>
	<?
	print_r($_REQUEST);
	?>
	</pre>
</div>
<?

    $placement_options = json_decode($_REQUEST['PLACEMENT_OPTIONS'], true);

	$contact = CRest::call(
		'crm.contact.get',
		[
			'ID' => $placement_options['ID']
		]
	);

	if ($contact['error'] == ''):
?>
<table class="table table-striped">
	<?foreach ($contact['result'] as $field => $value):?>
		<tr>
			<td><?=$field;?></td>
			<td><?=displayValue($value);?></td>
		</tr>
	<?endforeach;?>
</table>
<?endif;?>
</body>
