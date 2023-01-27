<?
require_once (__DIR__.'/crest.php');
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

    <script src="//api.bitrix24.com/api/v1/"></script>
    <script src="//api.bitrix24.com/api/v1/pull/"></script>

    <style>
        .activated_cell {
            background-color: red;
        }
    </style>
    <title>Index</title>
</head>
<body class="container-fluid">
<div class="row">
    <div class="col-lg-3 col-md-3 col-sm-8 col-xs-12">
        <h1>Status</h1>
        <table class="table table-bordered">
            <tr>
                <td id="cell1">&nbsp;</td>
                <td id="cell2">&nbsp;</td>
                <td id="cell3">&nbsp;</td>
            </tr>
            <tr>
                <td id="cell4">&nbsp;</td>
                <td id="cell5">&nbsp;</td>
                <td id="cell6">&nbsp;</td>
            </tr>
            <tr>
                <td id="cell7">&nbsp;</td>
                <td id="cell8">&nbsp;</td>
                <td id="cell9">&nbsp;</td>
            </tr>
        </table>
    </div>
</div>
<script>

    <?
        $uniqB24 = 'anyPref'.$_REQUEST['member_id'];

        $user = CRest::call(
            'profile',
            [

            ]
        );

        $uniqB24 = 'anyPref'.$_REQUEST['member_id'];
        $user_id = $user['result']['ID'];
    ?>

    window.appPullClient = new BX.PullClient({
        restApplication: '<?=$uniqB24;?>',
        restClient: BX24,
        userId: '<?=$user_id;?>'
    });

    window.appPullClient.subscribe({
        moduleId: 'application',
        callback: function (data) {
            console.warn(data); // {command: '...', params: {...}, extra: {...}}
            if (data.command == 'GRID_UPDATE') {
                if (data.params.activated == 'true')
                    $('#' + data.params.cell).addClass('activated_cell');
                else
                    $('#' + data.params.cell).removeClass('activated_cell');
            }
        }.bind(this)
    });

    window.appPullClient.start();

</script>

</body>
</html>