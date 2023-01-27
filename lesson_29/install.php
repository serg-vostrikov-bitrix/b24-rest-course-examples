<?php
include_once(__DIR__.'/cextrest.php');

$install_result = CRestExt::installApp();

// embedded for placement "placement.php"
$handlerBackUrl = ($_SERVER['HTTPS'] === 'on' || $_SERVER['SERVER_PORT'] === '443' ? 'https' : 'http') . '://'
	. $_SERVER['SERVER_NAME']
	. (in_array($_SERVER['SERVER_PORT'],	['80', '443'], true) ? '' : ':' . $_SERVER['SERVER_PORT'])
	. str_replace($_SERVER['DOCUMENT_ROOT'], '',__DIR__)
	. '/handler.php';

$result = CRestExt::call(
	'event.bind',
	[
		'EVENT' => 'ONCRMCONTACTUPDATE',
		'HANDLER' => $handlerBackUrl,
        'EVENT_TYPE' => 'online'
	]
);

CRest::setLog(['update' => $result], 'installation');

$result = CRestExt::call(
	'event.bind',
	[
		'EVENT' => 'ONCRMCONTACTADD',
		'HANDLER' => $handlerBackUrl,
		'EVENT_TYPE' => 'online'
	]
);

CRestExt::setLog(['add' => $result], 'installation');

if($install_result['rest_only'] === false):?>
<head>
	<script src="//api.bitrix24.com/api/v1/"></script>
	<?if($install_result['install'] == true):?>
    /*
	<script>
		BX24.init(function(){
			BX24.installFinish();
		});
	</script>
    */
	<?endif;?>
</head>
<body>
	<?if($install_result['install'] == true):?>
		installation has been finished
	<?else:?>
        <pre><?print_r($install_result);?></pre>
		installation error
	<?endif;?>
</body>
<?endif;