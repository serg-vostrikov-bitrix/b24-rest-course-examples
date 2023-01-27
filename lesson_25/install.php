<?php
require_once (__DIR__.'/crest.php');

$install_result = CRest::installApp();

// embedded for placement "placement.php"
$handlerBackUrl = ($_SERVER['HTTPS'] === 'on' || $_SERVER['SERVER_PORT'] === '443' ? 'https' : 'http') . '://'
	. $_SERVER['SERVER_NAME']
	. (in_array($_SERVER['SERVER_PORT'],	['80', '443'], true) ? '' : ':' . $_SERVER['SERVER_PORT'])
	. str_replace($_SERVER['DOCUMENT_ROOT'], '',__DIR__)
	. '/placement.php';

$result = CRest::call(
	'placement.bind',
	[
		'PLACEMENT' => 'REST_APP_URI',
		'HANDLER' => $handlerBackUrl
	]
);

CRest::setLog(['deal_tab' => $result], 'installation');

if($install_result['rest_only'] === false):?>
<head>
	<script src="//api.bitrix24.com/api/v1/"></script>
	<?if($install_result['install'] == true):?>
	<script>
		BX24.init(function(){
			BX24.installFinish();
		});
	</script>
	<?endif;?>
</head>
<body>
	<?if($install_result['install'] == true):?>
		installation has been finished
	<?else:?>
		installation error
	<?endif;?>
</body>
<?endif;