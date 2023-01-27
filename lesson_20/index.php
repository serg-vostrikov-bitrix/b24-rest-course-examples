<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>No UI app for Bitrix24</title>
</head>
<body>
	<div id="name">
		<?php
		require_once (__DIR__.'/crest.php');

		$result = CRest::call('user.current');

		echo $result['result']['NAME'].' '.$result['result']['LAST_NAME'];
		?>
	</div>
</body>
</html>