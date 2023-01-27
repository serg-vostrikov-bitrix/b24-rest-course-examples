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
        .activated_cell {
            background-color: red;
        }
    </style>
	<title>External UI</title>
</head>
<body class="container-fluid">
<div class="row">
	<div class="col-lg-3 col-md-3 col-sm-8 col-xs-12">
        <h1>Click cells</h1>
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
    jQuery(function($){
        $( 'td' ).on( "click", function( event ) {
            $(this).toggleClass('activated_cell');
            console.log(this.id, $(this).attr('class'));

            var activated = $(this).attr('class') == 'activated_cell';

            $.ajax({
                url: "inform_app.php",
                data: {
                    'cell': this.id,
                    'activated': activated
                },
                success: function( result ) {
                    console.log('sent', JSON.parse(result));
                }
            });

        });
    });
</script>
</body>