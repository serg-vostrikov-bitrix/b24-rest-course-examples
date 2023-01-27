<?php
/**
 * Обработчик события OnAppInstall
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

if(in_array($_REQUEST['event'], ['ONAPPINSTALL',]))
{

	$auth = $_REQUEST['auth'];
	if (!is_array($auth) || empty($auth['application_token'])  || empty($auth['member_id'])) {
        exit;
	}
    
	CRestExt::installApp();
}


?>
