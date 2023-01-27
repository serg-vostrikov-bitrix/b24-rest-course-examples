<?php
require_once (__DIR__.'/crest.php');

$install_result = CRest::installApp();


$result = CRest::call(
	'messageservice.sender.add',
	[
		'CODE' => 'COURSE.SMS',
		'TYPE' => 'SMS',
		'HANDLER' => $handlerBackUrl,
		'NAME' => 'COURSE.SMS Service'
	]
);

CRest::setLog(['sms' => $result], 'installation');

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