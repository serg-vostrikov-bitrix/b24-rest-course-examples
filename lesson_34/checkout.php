<?php
/**
 * Скрипт обработки платежа по сценарию checkout
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
 
require_once ('cextrest.php');
require_once(__DIR__.'/paysys.php');
 
CRestExt::setLog(
    [
        'request' => $_REQUEST,
    ],
    'checkout'
);


if (!array_key_exists('MEMBER_ID', $_REQUEST) || empty($_REQUEST['MEMBER_ID'])) {
    http_response_code(404);

    exit;
}

$memberId = $_REQUEST['MEMBER_ID'];
$PSys = new PaySys($memberId);
$result = $PSys->checkout();


CRestExt::setLog(
    [
        'request' => $_REQUEST,
        'result' => $result
    ],
    'checkout'
);

if (array_key_exists('PAYMENT_URL', $result) && !empty($result['PAYMENT_URL'])) {
    http_response_code(200);
    header('Content-Type:application/json; charset=UTF-8');
    
    echo json_encode($result);
}
else {
    http_response_code(503); // service unavailable
}
