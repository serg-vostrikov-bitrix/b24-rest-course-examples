<?php
require_once (__DIR__.'/crest.php');

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

$result = CRest::installApp();

if($result['rest_only'] === false):?>
	<head>
		<script src="//api.bitrix24.com/api/v1/"></script>
		<?php if($result['install'] == true):?>
			<script>
				BX24.init(function(){
					BX24.installFinish();
				});
			</script>
		<?php endif;?>
	</head>
	<body>
		<?php if($result['install'] == true):?>
			installation has been finished
		<?php else:?>
			installation error
		<?php endif;?>
	</body>
<?php endif;