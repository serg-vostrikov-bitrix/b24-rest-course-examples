<?
require_once (__DIR__.'/crest.php');

$result = CRest::call(
	'crm.deal.update',
	['ID' => '6', 'FIELDS' => ['STAGE_ID' => 'WON', 'CLOSED' => '1', ], ]
);

echo '<pre>';
print_r($result);
echo '</pre>';