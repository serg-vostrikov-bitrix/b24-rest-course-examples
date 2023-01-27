<?php

include_once('crest.php');

if(
	!empty($_REQUEST) &&
	in_array($_REQUEST['event'], ['ONIMBOTJOINCHAT', 'ONIMBOTMESSAGEADD'])
)
{

	// $dir = $_SERVER['DOCUMENT_ROOT'] . __DIR__ . '/tmp/'; try this depending on your server configuration
	$dir = 'tmp/';

	if(!file_exists($dir))
	{
		echo 'create: '.mkdir($dir, 0777, true);
	}

	// save event to log
	file_put_contents(
		$dir . time() . '_' . rand(1, 9999) . '.txt',
		var_export(
			[
				'request' =>
					[
						'event' => $_REQUEST['event'],
						'data' => $_REQUEST['data'],
						'ts' => $_REQUEST['ts'],
						'auth' => $_REQUEST['auth'],
					]
			],
			true
		)
	);

	// answer to user
	$result = CRest::call(
		'imbot.message.add',
		[
			'BOT_ID' => '10', // insert yours!
			'CLIENT_ID' => 'xbizjrui9ouhc3o7tw4hmw8teki33p1m', // insert yours!
			'DIALOG_ID' => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
			'MESSAGE' => "reply: '".$_REQUEST['data']['PARAMS']['MESSAGE']."'"
		]
	);



}