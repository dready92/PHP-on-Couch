<?php

$host = 'http://admin:adminPwd@localhost:5984/';

//We generate three dbs
$dbNames = [];

for ($i = 0; $i < 3; $i++)
	$dbNames[] = uniqid('simulatingdb');

//Wait for the async listener to be setup.
sleep(2);
foreach ($dbNames as $val) {
	_request($host . $val, '', 'PUT');
	sleep(0.02);
}

function _request($url, $data, $method)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	if (!empty($data)) {
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
	}curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_exec($ch) . PHP_EOL;
	curl_close($ch);
}
