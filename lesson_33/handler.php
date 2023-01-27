<?
include_once('crest.php');

if ($_REQUEST['type'] == 'SMS')
{

	// $dir = $_SERVER['DOCUMENT_ROOT'] . __DIR__ . '/tmp/'; try this depending on your server configuration
	$dir = 'tmp/';

	if(!file_exists($dir))
	{
		mkdir($dir, 0777, true);
	}

	$recipient = trim($_REQUEST['message_to']);
	$message_id = $_REQUEST['message_id'];
	$message = trim($_REQUEST['message_body']);

	/*

	if ( ($recipient != '') && ($message != '') )
		$delivered = sendMessage ($recipient, $message_id, $message); // SMS-provider routine

	 */


	/* How to update message status:

	if ($delivered)
		$result = CRest::call(
			'messageservice.message.status.update',
			[
				'CODE' => 'COURSE.SMS',
				'message_id' => $message_id,
				'status' => 'delivered'
			]
		);
	 */

	// save event to log
	file_put_contents(
		$dir . time() . '_' . rand(1, 9999) . '.txt',
		var_export(
			[
				'request' => $_REQUEST
			],
			true
		)
	);

}


?>