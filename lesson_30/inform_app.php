<?
require_once (__DIR__.'/crest.php');

$result = CRest::call(
	'pull.application.event.add',
	[
		'COMMAND' => 'GRID_UPDATE',
		'PARAMS' => json_encode(['cell' => $_REQUEST['cell'], 'activated' => $_REQUEST['activated']]),
		'MODULE_ID' => 'application'
	]
);


echo json_encode($result);
?>