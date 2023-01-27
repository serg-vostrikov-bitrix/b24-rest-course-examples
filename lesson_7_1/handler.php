<?php

include_once('crest.php');


if(!empty($_REQUEST['auth']['application_token']) && $_REQUEST['auth']['application_token'] == 'xdgkmdkmo6vvudmczjk2av0wauv0kb1x')
{
	if(in_array($_REQUEST['event'], ['0' => 'ONCRMCONTACTUPDATE', ]))
	{
		$result = CRest::call(
			'crm.contact.get',
			['ID' => $_REQUEST['data']['FIELDS']['ID'], ]
		);


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
					'result' => $result,
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

	}
}