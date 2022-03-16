<?php
error_reporting(E_ALL & ~E_NOTICE);

define('BASE_URL', 'http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '').'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).'/');

$action = $_GET['action'];

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($action)){
	$data = [];
	$vapids = file_get_contents('vapids.json');
	if($vapids && $vapids = json_decode($vapids, true)){
		$data = $vapids;
	}

	if($action == 'subscribe'){
		$body = file_get_contents('php://input');
		if($body = json_decode($body, true)){
			$data[] = $body;
		}
		
		$fs = fopen('vapids.json', 'w');
		fwrite($fs, json_encode($data));
		fclose($fs);
		die();
	}

	if($action == 'unsubscribe'){
		$body = file_get_contents('php://input');
		if($body = json_decode($body, true)){
			foreach($data as $key => $value){
				if($value['keys']['p256dh'] == $body['keys']['p256dh'] && $value['keys']['auth'] == $body['keys']['auth']){
					unset($data[$key]);
				}
			}

			$fs = fopen('vapids.json', 'w');
			fwrite($fs, json_encode($data));
			fclose($fs);
			die();
		}
		
	}
}

require_once 'vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// array of notifications
$notifications = [];

if(($vapids = file_get_contents('vapids.json')) && $vapids = json_decode($vapids, true)){
	foreach($vapids as $key => $value){
		$notifications[] = [
			'subscription' => Subscription::create($value),
			'payload' => json_encode([
				'actions' => [
					[
						'action' => 'url',
						'title' => 'Lihat',
						'icon' => BASE_URL.'logo.png'
					]
				],
				'title' => 'Title',
				'body' => 'Text',
				'icon' => BASE_URL.'logo.png'
			])
		];
	}
}

$auth = [
    'VAPID' => [
        'subject' => 'mailto:me@website.com', // can be a mailto: or your website address
        'publicKey' => 'BNKKKP0gA1iRjpWEr4LWC04d005sGnrEUYcgcV6g2IXwVBuzNiWDgXyvL9vUyTb33C4SOEfElh0lcBFGkKVZZ1I', // (recommended) uncompressed public key P-256 encoded in Base64-URL
        'privateKey' => '7TbGAUrxmpDp9HARnFjluFU0nQsDZG1baMAxTdF7K7Y', // (recommended) in fact the secret multiplier of the private key encoded in Base64-URL
        // 'pemFile' => 'path/to/pem', // if you have a PEM file and can link to it on your filesystem
        // 'pem' => 'pemFileContent', // if you have a PEM file and want to hardcode its content
    ],
];

$webPush = new WebPush($auth);

// send multiple notifications with payload
foreach ($notifications as $notification) {
    $webPush->queueNotification(
        $notification['subscription'],
        $notification['payload'] // optional (defaults null)
    );
}

/**
 * Check sent results
 * @var MessageSentReport $report
 */
foreach ($webPush->flush() as $report) {
    $endpoint = $report->getRequest()->getUri()->__toString();

    if ($report->isSuccess()) {
        echo "[v] Message sent successfully for subscription {$endpoint}.";
    } else {
        echo "[x] Message failed to sent for subscription {$endpoint}: {$report->getReason()}";
    }
}

/**
 * send one notification and flush directly
 * @var MessageSentReport $report
 */
// $report = $webPush->sendOneNotification(
//     $notifications[0]['subscription'],
//     $notifications[0]['payload'] // optional (defaults null)
// );