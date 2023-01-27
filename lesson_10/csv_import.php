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

$clients = csv_to_array('clients.csv', ';');

echo '<pre>'; print_r($clients); echo '</pre>';

$results = [];

foreach ($clients as $client) {
	$results[] = CRest::call(
		'crm.contact.add',
		[
			'FIELDS' => [
				'NAME' => $client['NAME'],
				'LAST_NAME' => $client['LAST_NAME'],
				'EMAIL' => ['0' => ['VALUE' => $client['EMAIL'], 'VALUE_TYPE' => 'WORK', ], ],
				'PHONE' => ['0' => ['VALUE' => $client['PHONE'], 'VALUE_TYPE' => 'WORK', ], ],
			],
		]
	);
}

echo '<pre>'; print_r($results); echo '</pre>';