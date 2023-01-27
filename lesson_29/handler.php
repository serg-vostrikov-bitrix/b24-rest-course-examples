<?
include_once(__DIR__.'/cextrest.php');

if(in_array($_REQUEST['event'], ['0' => 'ONCRMCONTACTUPDATE', '1' => 'ONCRMCONTACTADD']))
{

	// $dir = $_SERVER['DOCUMENT_ROOT'] . __DIR__ . '/tmp/'; try this depending on your server configuration
	$dir = 'tmp/';

	if(!file_exists($dir))
	{
		mkdir($dir, 0777, true);
	}

	CRestExt::setCurrentBitrix24($_REQUEST['auth']['member_id']);

	$get_result = CRestExt::call(
		'crm.contact.get',
		['ID' => $_REQUEST['data']['FIELDS']['ID'], ]
	);

	$name = mb_convert_case($get_result['result']['NAME'], MB_CASE_TITLE, "UTF-8");
	$last_name = mb_convert_case($get_result['result']['LAST_NAME'], MB_CASE_TITLE, "UTF-8");
	$middle_name = mb_convert_case($get_result['result']['SECOND_NAME'], MB_CASE_TITLE, "UTF-8");

	if (
		($get_result['result']['NAME'] != $name) ||
		($get_result['result']['LAST_NAME'] != $last_name) ||
		($get_result['result']['SECOND_NAME'] != $middle_name)
	) {
		$update_result = CRestExt::call(
			'crm.contact.update',
			[
				'ID' => $_REQUEST['data']['FIELDS']['ID'],
				'FIELDS' => [
					'NAME' => $name,
					'LAST_NAME' => $last_name,
					'SECOND_NAME' => $middle_name
				]
			]
		);
	}

	// save event to log
	file_put_contents(
		$dir . time() . '_' . rand(1, 9999) . '.txt',
		var_export(
			[
				'get' => $get_result,
				'update' => $update_result,
				'names' => [$name, $last_name, $middle_name],
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


?>