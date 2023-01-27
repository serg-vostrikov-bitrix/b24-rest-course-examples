<?php
/**
 * Скрипт показа заказов по порталам
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
    'orders'
);

$memberId = (array_key_exists('member_id', $_REQUEST)) ? $_REQUEST['member_id'] : '';
$orderKey = (array_key_exists('id', $_REQUEST)) ? $_REQUEST['id'] : '';
$curOrder = [];
$errors = [];
if (!empty($orderKey)) {
    $curOrder = PaySys::getOrderByHash($orderKey);
    if (is_array($curOrder) && !empty($curOrder)) {
        $memberId = $curOrder['MEMBER_ID'];
    }
}
$orders = [];
if (!empty($memberId)) {
    $PSys = new PaySys($memberId);
    if (!empty($curOrder)) {
        if (array_key_exists('action', $_REQUEST) && $_REQUEST['action'] == 'pay') {
            if (!$PSys->payOrderByHash($orderKey)) {
                $errors = $PSys->getErrors();
            }
        }
        if (array_key_exists('action', $_REQUEST) && $_REQUEST['action'] == 'reject') {
            if (!$PSys->rejectOrderByHash($orderKey)) {
                $errors = $PSys->getErrors();
            }
        }
    }
    $orders = $PSys->getOrders();
}
$portals = PaySys::getPortals();
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

    <title>Payment system integration</title>
</head>
<body class="container-fluid box background-tint">
<div class="row">

    <div class="col-lg-6 col-md-6 col-sm-8 col-xs-10">

        <h1>Orders</h1>
        <?php if (count($errors)) {
                echo 'Errors:<pre>'.print_r($errors, true).'</pre>';
            }
        ?>
        <form name="orders_form" method="get" action="">
            <div class="form-group">
                <label for="examplePortals">Portals with orders</label>
                <select class="form-control" onChange="this.form.submit();" id="examplePortals" name="member_id">
                    <option value=""></option>
                    <?php foreach($portals as $memId => $domain):
                        $selected = ($memId == $memberId) ? ' selected' : '';
                    ?>
                        <option value="<?php echo $memId?>"<?php echo $selected?>><?php echo $domain?></option>
                    <?php endforeach?>
                </select>
            </div>
        </form>
    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-md-8 col-sm-10 col-xs-12">
        <form name="action_form" method="get" action="">
            <input type="hidden" name="id" value="">
            <input type="hidden" name="action" value="">
            <table class="table">
                <thead>
                    <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Date</th>
                    <th scope="col">Sum</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($memberId)):?>
                    <tr>
                        <td colspan="5">Select portal, please</td>
                    </tr>
                    <?php else:?>
                        <?php foreach($orders as $key => $order):
                            $type = ($order['TYPE'] == 'ORDER') ? 'Order' : 'Invoice';
                            $date = new DateTime($order['DATE']);
                            $status = 'Unpaid';
                            $actions = '<button type="button" onClick="examplePayOrder(this, \''.$key.'\');" name="pay" value="1">Pay</button>
                            <button type="button" onClick="exampleRejectOrder(this, \''.$key.'\');" name="reject" value="1">Reject</button>
                            ';
                            if (intval($order['PAID']) > 0) {
                                $status = 'Paid';
                                $actions = '&nbsp;';
                            }
                            if (intval($order['PAID']) < 0) {
                                $status = 'Rejected';
                                $actions = '&nbsp;';
                            }
                        ?>
                        <tr>
                            <td><?php echo ($type.'&nbsp;'.$order['ID'])?></td>
                            <td><?php echo $date->format('Y-m-d H:i')?></td>
                            <td><?php echo ($order['SUM'] . '&nbsp;'. $order['CURRENCY'])?></td>
                            <td><?php echo $status?></td>
                            <td><?php echo $actions?></td>
                        </tr>
                        <?php endforeach?>
                    <?php endif?>
                </tbody>
            </table>
        </form>
    </div>
</div>
<script>
    function examplePayOrder(btn, id) {
        const form = btn.form;
        form.elements.id.value = id;
        form.elements.action.value = 'pay';
        form.submit();
    }
    function exampleRejectOrder(btn, id) {
        const form = btn.form;
        form.elements.id.value = id;
        form.elements.action.value = 'reject';
        form.submit();
    }
</script>
</body>
</html>
