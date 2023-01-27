<?php
/**
 * Скрипт установки приложения
 * PHP version 8.0.24
 *
 * @category Application
 * @package  Package
 *
 * @author    Vadim Soluyanov <sallee@info-expert.ru>
 * @copyright 2022 Bitrix
 * @license   GNU AGPLv3 https://choosealicense.com/licenses/agpl-3.0/
 *
 * @link https://bitbucket.org/b24dev/exampleps.git
 */

require_once(__DIR__.'/cextrest.php');

$install_result = CRestExt::installApp();

// обработчик событий
$handlerBackUrl = ($_SERVER['HTTPS'] === 'on' || $_SERVER['SERVER_PORT'] === '443' ? 'https' : 'http') . '://'
	. $_SERVER['SERVER_NAME']
	. (in_array($_SERVER['SERVER_PORT'],	['80', '443'], true) ? '' : ':' . $_SERVER['SERVER_PORT'])
	. str_replace($_SERVER['DOCUMENT_ROOT'], '',__DIR__)
	. '/handler.php';

// регистрируем обработчик, чтобы при событии получить и сохранить application_token
$result = CRestExt::call(
	'event.bind',
	[
		'EVENT' => 'OnAppInstall',
		'HANDLER' => $handlerBackUrl,
        'EVENT_TYPE' => 'online'
	]
);

CRest::setLog(['update' => $result], 'installation');

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