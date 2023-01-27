<?php
/**
 * Скрипт оплаты электронным кошельком
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
    'iframe'
);

$showGetDataJS = true;

$orderKey = (array_key_exists('id', $_REQUEST)) ? $_REQUEST['id'] : '';
if (!empty($orderKey)) {
        
    $order = PaySys::getOrderByHash($orderKey);
    if (empty($order)) {
        http_response_code(404);

        exit;
    }
    $showGetDataJS = false;
    $orderType = ($order['TYPE'] == 'ORDER') ? 'Order' : 'Invoice';
    $orderDate = new DateTime($order['DATE']);


    $memberId = $order['MEMBER_ID'];
    $domain = $order['DOMAIN'];
    if (empty($memberId)) {
        die('Portal not found');
    }
    $PSys = new PaySys($memberId, $domain);
    $errors = [];
    $wallet = (array_key_exists('wallet', $_REQUEST)) ? $_REQUEST['wallet'] : '';
    if (array_key_exists('action', $_REQUEST) && $_REQUEST['action'] == 'pay') {
        if (!$PSys->payOrderByHash($orderKey)) {
            $errors = $PSys->getErrors();
        }
        else {
            $order = PaySys::getOrderByHash($orderKey);
        }
    }
    if (array_key_exists('action', $_REQUEST) && $_REQUEST['action'] == 'reject') {
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
}
if (array_key_exists('get_detail_data', $_REQUEST) && $_REQUEST['get_detail_data'] == '1') {
    $memberId = $_REQUEST['MEMBER_ID'];
    if (empty($memberId)) {
        die('Portal not found');
    }
    $PSys = new PaySys($memberId);
    $errors = [];
    $orderKey = $PSys->makeWalletOrder();
    if (empty($orderKey)) {
        die('Error add new order: '.implode("\n", $errors));
    }
    $newUlr = ($_SERVER['HTTPS'] === 'on' || $_SERVER['SERVER_PORT'] === '443' ? 'https' : 'http') . '://'
	. $_SERVER['SERVER_NAME']
	. (in_array($_SERVER['SERVER_PORT'],	['80', '443'], true) ? '' : ':' . $_SERVER['SERVER_PORT'])
	. $_SERVER['PHP_SELF']. '?id='.$orderKey;
	header('Location: '.$newUlr);
	
	exit;
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

    <title>E-Wallet Payment</title>
</head>
<body class="container-fluid box background-tint">
<?php if ($showGetDataJS):?>
    <form name="get_detail_form" method="post" action="">
        <input type="hidden" name="ORDER_ID" value="" />
        <input type="hidden" name="MEMBER_ID" value="" />
        <input type="hidden" name="PAYMENT_ID" value="" />
        <input type="hidden" name="SERVICE_ID" value="" />
        <input type="hidden" name="BX_SYSTEM_PARAMS" value="" />
        <input type="hidden" name="get_detail_data" value="1" />
    </form>
<script>
    // Для одноразовости работы скрипта получения данных о заказе
    let firstStart = false;
    // Регистрируем обработчик события message для получения данных для формы в iframe
    document.addEventListener("DOMContentLoaded", function () {
        window.addEventListener("message", function (event) {

            // Чтобы не дёргать скрипт по десять раз и не перезаписывать переменные, фильтруем первый запуск
            if (!firstStart) {
                firstStart = true;

                //console.log('event.data: ', event.data)

                // получение данных от сайта (от платёжной системы)
                const member_id = event.data['MEMBER_ID'];
                const order_id = event.data['ORDER_ID'];
                const service_id = event.data['SERVICE_ID'];
                const payment_id = event.data['PAYMENT_ID'];
                /*
                event.data['BX_SYSTEM_PARAMS']['RETURN_URL'] = encodeURIComponent(
                    event.data['BX_SYSTEM_PARAMS']['RETURN_URL']
                );
                */
                const BX_SYSTEM_PARAMS =  JSON.stringify(event.data['BX_SYSTEM_PARAMS']);

                //console.log('BX_SYSTEM_PARAMS: ',BX_SYSTEM_PARAMS)
                // Проверяем наличие данных
                if (
                    member_id
                    && order_id
                    && payment_id
                    && BX_SYSTEM_PARAMS
                ) {
                    const form = document.forms.get_detail_form;
                    if (form) {
                        form.elements.ORDER_ID.value = order_id;
                        form.elements.MEMBER_ID.value = member_id;
                        form.elements.PAYMENT_ID.value = payment_id;
                        form.elements.SERVICE_ID.value = service_id;
                        form.elements.BX_SYSTEM_PARAMS.value = BX_SYSTEM_PARAMS;
                        form.submit();
                    }
                } else {
                    alert("Incorrect request");
                }
            }
        }, false);
    });
</script>
<?php else:?>
<div class="row">

    <div class="col-lg-6 col-md-6 col-sm-8 col-xs-10">
        <h3>E-Wallet Payment</h3>
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
            <div class="form-group">
                <label for="exampleWalletNum">E-Wallet number</label>
                <input type="text" class="form-control" id="exampleWalletNum" name="wallet" value="<?php echo htmlspecialchars($wallet)?>">
            </div>
            <input type="hidden" name="action" value="pay">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($orderKey)?>">
            <input type="hidden" name="MEMBER_ID" value="<?php echo htmlspecialchars($memberId)?>">
            <input type="hidden" name="DOMAIN" value="<?php echo htmlspecialchars($domain)?>">
            <button type="button" name="pay_btn" onClick="iframeCheckForm(this);" class="btn btn-default">Pay</button>
            <button type="button" name="reject_btn" onClick="iframeCheckForm(this);" class="btn btn-default">Reject</button>
        </form>
        <?php endif?>
    </div>
</div>
<script>
    function iframeCheckForm(btn) {
        const form = btn.form;
        if (!form.elements.wallet.value.length) {
            alert('Insert wallet number!');
            
            return;
        }
        if (btn.name == 'pay_btn') {
            // here is some js-code of the external pay system
            // that does the real payment action
            form.elements.action.value = 'pay';
        }
        else {
            form.elements.action.value = 'reject';
        }
        form.submit();
    }
</script>
<?php endif?>
</body>
</html>
 
