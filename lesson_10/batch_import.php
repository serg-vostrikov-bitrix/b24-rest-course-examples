<?php
require_once (__DIR__.'/crest.php');

function csv_to_array($filename='', $delimiter=',')
{
	if(!file_exists($filename) || !is_readable($filename))
		return FALSE;

	$header = NULL;

	$data = array();
	if (($handle = fopen($filename, 'r')) !== FALSE)
	{
		while (($row = fgetcsv($handle, 5000, $delimiter)) !== FALSE)
		{
			if(!$header)
				$header = $row;
			else
				$data[] = array_combine($header, $row);
		}
		fclose($handle);
	}

	return $data;
}

$clients = csv_to_array('many_clients.csv', ';');

$batch = [];
$step = 0;

foreach ($clients as $client) {

	$batch['step_'.$step] = [
		'method' => 'crm.contact.add',
		'params' => [
			'FIELDS' => [
				'NAME' => $client['NAME'],
				'LAST_NAME' => $client['LAST_NAME'],
				'EMAIL' => ['0' => ['VALUE' => $client['EMAIL'], 'VALUE_TYPE' => 'WORK', ], ],
				'PHONE' => ['0' => ['VALUE' => $client['PHONE'], 'VALUE_TYPE' => 'WORK', ], ],
			]
		],
	];

	if (($step == 49) || ($step == count($clients) - 1)) {

		$result = CRest::callBatch($batch, 1);
		echo '<pre>'; print_r($result); echo '</pre>';

		$batch = [];
		$step = 0;

	}
	else $step++;

}

