<?php
/**
 * Скрипт оплаты по кредитной карте
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
ini_set('display_errors', true);
error_reporting(E_ALL);

require_once ('cextrest.php');
require_once(__DIR__.'/paysys.php');

CRestExt::setLog(
    [
        'request' => $_REQUEST
    ],
    'card_checkout'
);

$orderKey = (array_key_exists('id', $_REQUEST)) ? $_REQUEST['id'] : '';
if (empty($orderKey)) {
    http_response_code(404);

    exit;
}
$order = PaySys::getOrderByHash($orderKey);
if (empty($order)) {
    http_response_code(404);

    exit;
}
$orderType = ($order['TYPE'] == 'ORDER') ? 'Order' : 'Invoice';
$orderDate = new DateTime($order['DATE']);


$memberId = $order['MEMBER_ID'];
$domain = $order['DOMAIN'];
if (empty($memberId)) {
    die('Portal not found');
}
$PSys = new PaySys($memberId, $domain);
$errors = [];
if (array_key_exists('pay', $_REQUEST) && $_REQUEST['pay'] == '1') {
    if (!$PSys->payOrderByHash($orderKey)) {
        $errors = $PSys->getErrors();
    }
    else {
        $order = PaySys::getOrderByHash($orderKey);
    }
}
if (array_key_exists('reject', $_REQUEST) && $_REQUEST['reject'] == '1') {
    if (!$PSys->rejectOrderByHash($orderKey)) {
        $errors = $PSys->getErrors();
    }
    else {
        $order = PaySys::getOrderByHash($orderKey);
    }
}
$status = 'Unpaid';
if (intval($order['PAID']) > 0) {
    $status = 'Paid';
}
if (intval($order['PAID']) < 0) {
    $status = 'Rejected';
}
?>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="css/app.css">
    <!--link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"-->
    <script
            src="https://code.jquery.com/jquery-3.6.0.js"
            integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk="
            crossorigin="anonymous"></script>

    <style>
        .box {
            background-size: cover;
            background-image: url('back.jpg');
        }
        .background-tint {
            background-color: rgba(255,255,255,.8);
            background-blend-mode: screen;
        }
    </style>

    <title>Credit Card Payment</title>
</head>
<body class="container-fluid box background-tint">
<div class="row">

    <div class="col-lg-6 col-md-6 col-sm-8 col-xs-10">
        <h1>Credit Card Payment</h1>
        <p>Date: <span><?php echo $orderDate->format('Y-m-d H:i')?></span></p>
        <p><?php echo $orderType?>: <span><?php echo $order['ID']?></span></p>
        <p>Sum: <span><?php echo $order['SUM']?></span></p>
        <p>Currency: <span><?php echo $order['CURRENCY']?></span></p>
        <p>Status: <span><?php echo $status?></span></p>
        <?php if (intval($order['PAID']) == 0):?>
        
            <?php if (count($errors)) {
                echo 'Errors:<pre>'.print_r($errors, true).'</pre>';
            }
            ?>
            
        <form name="pay_form" method="post" action="">
            <input type="hidden" name="pay" value="1">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($orderKey)?>">
            <button type="submit" class="btn btn-default">Pay</button>
        </form>
        <form name="reject_form" method="post" action="">
            <input type="hidden" name="reject" value="1">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($orderKey)?>">
            <button type="submit" class="btn btn-default">Reject</button>
        </form>
        <?php endif?>
        <p><a href="<?php echo $order['RETURN_URL']?>" target="_blank">Return URL</a>
    </div>
</div>
</body>
</html>
 
