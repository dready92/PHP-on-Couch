<?php
/**
 * You can change the CouchDB port and host used by setting the environment variables
 */
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$config = config::getInstance();
$users = $config->getUsers();

//Loop through config
$admins = [];

$dsn = $config->getDSN();
$nodeName = (sizeof($argv) > 1 && isset($argv[1])) ? $argv[1] : null;
foreach ($users as $val) {
	if ($val['isAdmin']) {
		$admins[] = $val;
		continue;
	}
//	createUser($host, $val['username'], $val['password']);
}
$adminDsn = $dsn;
foreach ($admins as $val) {
	createAdmin($adminDsn, $val['username'], $val['password'], $nodeName);
	//We change the $adminDsn since we won't have any more the admin party.
	if ($adminDsn === $dsn)
		$adminDsn = urlencode($val['username']) . ':' . urlencode($val['password']) . '@' . $dsn;
}

/**
 * Creates an admin into the local database
 * @param String $host	Hostname of the database
 * @param String $user	Username to add
 * @param String $password	Password of the new user
 */
function createAdmin($host, $user, $password, $nodeName)
{
	$node = null;
	if (!empty($nodeName))
		$node = "/_node/$nodeName";
	$url = $host . ($node != null ? $node : '') . '/_config' . "/admins/$user";
	$data = json_encode($password);
	$method = 'PUT';
	_request($url, $data, $method);
}

/**
 * Creates a user into the local database
 * @param String $host	Hostname of the database
 * @param String $user	Username to add
 * @param String $password	Password of the new user
 */
function createUser($host, $user, $password)
{
	$url = $host . '/_users/' . $user;
	$data = _getUserDoc($user, $password);
	$method = 'PUT';
	_request($url, $data, $method);
}

/**
 * Creates the user document that will be added to the database.
 * @param type $user
 * @param type $password
 * @return type
 */
function _getUserDoc($user, $password)
{
	return json_encode((object) [
				'_id' => "org.couchdb.user:$user",
				'name' => $user,
				'type' => 'user',
				'roles' => [],
				'password' => $password
	]);
}

function _request($url, $data, $method)
{
//	$datasize = strlen($data);
//	echo $url . PHP_EOL;
//	var_dump($data);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
//    curl_setopt($ch, CURLOPT_VERBOSE, 1); // for debug
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_exec($ch) . PHP_EOL;
	curl_close($ch);
}
