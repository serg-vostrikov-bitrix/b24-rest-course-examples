<?
require_once (__DIR__.'/crest.php');

echo 'request: <pre>';
print_r($_REQUEST);
echo '</pre>';

$result = CRest::call(
	'profile',
	[
	]
);

echo 'profile:<pre>';
print_r($result);
echo '</pre>';