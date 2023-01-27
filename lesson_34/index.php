<?php
/**
 * Скрипт настроек приложения
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
    'index'
);

$memberId = (array_key_exists('member_id', $_REQUEST)) ? $_REQUEST['member_id'] : '';
$domain = (array_key_exists('DOMAIN', $_REQUEST)) ? $_REQUEST['DOMAIN'] : '';
if (empty($memberId)) {
    die('Portal not found');
}
$PSys = new PaySys($memberId, $domain);
if (array_key_exists('disconnect', $_REQUEST) && $_REQUEST['disconnect'] == '1') {
    $PSys->disconnect();
}

$showSettings = true;
$errors = [];
if (array_key_exists('save', $_REQUEST) && $_REQUEST['save'] == '1') {
    if ($PSys->saveSettings()) {
        $showSettings = false;
    }
    else {
        $errors = $PSys->getErrors();
    }
}
$curPaySystems = $PSys->getPaySystems();
if (is_array($curPaySystems) && count($curPaySystems)) {
    $showSettings = false;
}

/*
$res = CRestExt::call(
    'crm.lead.get',
    [
        'id' => 203,
    ]
);
CRestExt::setLog(
    [
        'crm.lead.get' => $res
    ],
    'index_test'
);
*/
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

        <?php if ($showSettings):
            $currSettings = $PSys->getSettings();
            if (count($errors)) {
                echo '<pre>'.print_r($errors, true).'</pre>';
            }
        ?>

        <h1>Payment Auth form</h1>
        <form name="settings_form" method="post" action="">
            <div class="form-group">
                <label for="exampleInputAPI">API Key</label>
                <input type="text" class="form-control" id="exampleInputAPI" name="API_KEY" value="<?php echo htmlspecialchars($currSettings['API_KEY'])?>">
            </div>
            <div class="form-group">
                <label for="exampleInputPass">Password</label>
                <input type="password" class="form-control" id="exampleInputPass" name="PASS" value="<?php echo htmlspecialchars($currSettings['PASS'])?>">
            </div>
            <input type="hidden" name="save" value="1">
            <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($memberId)?>">
            <input type="hidden" name="DOMAIN" value="<?php echo htmlspecialchars($domain)?>">
            <button type="submit" class="btn btn-default">Connect</button>
        </form>

        <?php else:?>
            <h1>Payment systems created</h1>
            <?php foreach($curPaySystems as $ps):?>
                <p><a target="_blank" href="<?php echo $ps['EDIT_URL']?>"><?php echo $ps['NAME']?></a></p>
            <?php endforeach?>
            
            <form name="disconnect_form" method="post" action="">
                <input type="hidden" name="disconnect" value="1">
                <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($memberId)?>">
                <input type="hidden" name="DOMAIN" value="<?php echo htmlspecialchars($domain)?>">
                <button type="submit" class="btn btn-default">Disconnect</button>
            </form>
        
        <?php endif;?>
    </div>
</div>
</body>
</html>
