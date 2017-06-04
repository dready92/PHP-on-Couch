<?php

require_once 'config.php';
$config = config::getInstance();
$admin = $config->getFirstAdmin();
$user = $admin['username'];
$pass = $admin['password'];
$host = "http://$user:$pass@" . $config->getDSN() . "/";
//We generate three dbs
$dbNames = [];

for ($i = 0; $i < 3; $i++)
	$dbNames[] = uniqid('simulatingdb');

//Wait for the async listener to be setup.
if ($argc < 3)
	die("You must provide 2 arguments. Example :  php simulateChanges.php my_db /var/tmp/mytriggerfile.lock");

$db = $argv[1];
$file = $argv[2];

$timeout = 30;
var_dump($argv);
//To avoiding infinite loop
echo $file;
//if (!set_time_limit($timeout))
//	die("Unable to setup file execution limit. Closing...");

while (!file_exists($file) && !file_exists(__DIR__ . DIRECTORY_SEPARATOR . $file))
	sleep(0.02);

echo "\nFile found : $file";
//Client is listening
sleep(1); //Safety wait
foreach ($dbNames as $val) {
	//Add documents with a 0.02 seconds interval
	$id = uniqid();
	$doc = json_encode(['_id' => $id, 'name' => $val]);
	_request($host . $db . "/$id", $doc, 'PUT');
	sleep(0.02);
}

exit(0);

function _request($url, $data, $method)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	if (!empty($data)) {
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
	}curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	echo curl_exec($ch) . PHP_EOL;
	curl_close($ch);
}
