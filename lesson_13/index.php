<?
require_once (__DIR__.'/crest.php');

$result = CRest::call(
		'imbot.message.add',
		['BOT_ID' => '', 'CLIENT_ID' => '', 'DIALOG_ID' => '1', 'MESSAGE' => 'Привет! Я чат-бот!', ]
	);

echo '<pre>';
	print_r($result);
echo '</pre>';