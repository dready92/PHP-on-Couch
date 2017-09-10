#!/usr/bin/php -q
<?PHP
/**

 This PHP script executes basic tasks on a CouchDB server

 For this to work, you have to tell the database DSN (example : http://couch.server.com:5984/) and the name of a database that does not exist

*/

 //Setup an autoloader (using src/autoload.php)
 $srcDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src';
 require $srcDir . DIRECTORY_SEPARATOR . 'autoload.php';

### ANON DSN
//$couchDsn = "http://localhost:5984/";
### AUTHENTICATED DSN
$couchDsn = "http://admin:adminPwd@localhost:5984/";
$couchDB = "example";


//Import required libraries
use PHPOnCouch\CouchClient;
use PHPOnCouch\Exceptions\CouchException;

/**
* create the client
*/
$client = new CouchClient($couchDsn,$couchDB);



/**
* first of all, let's list databases on the server.
* This ensure server connectivity and that $couchDB does not exist
*
* note the use of a "try {} catch () {}" block to allow gracefull error handling
*
*/

echo 'Getting databases infos : $databases = $client->listDatabases();'."\n";

try {
	$databases = $client->listDatabases();
} catch ( Exception $e) {
	echo "Some error happened during the request. This is certainly because your couch_dsn ($couchDsn) does not point to a CouchDB server...\n";
	exit(1);
}
echo "Database list fetched : \n".print_r($databases,true)."\n";

if ( in_array($couchDB,$databases) ) {
	echo "Database $couchDB already exist. Please drop it or edit this script and set $couchDB to a non-existant database\n";
	exit(1);
}






/**
* Let's create the database
*
* We don't pass a database name parameter, $client uses the database name we passed in argument when creating it
*
* In case of failure, if exception ($e) is a CouchException, the failure is "application-side" : couchDB returned 
* an HTTP failure code (for example if the database already exist).
*
*/
echo "Creating database ".$client->getDatabaseUri().': $result = $client->createDatabase();'."\n";
try {
	$result = $client->createDatabase();
} catch (Exception $e) {
	if ( $e instanceof CouchException ) {
		echo "We issued the request, but couch server returned an error.\n";
		echo "We can have HTTP Status code returned by couchDB using \$e->getCode() : ". $e->getCode()."\n";
		echo "We can have error message returned by couchDB using \$e->getMessage() : ". $e->getMessage()."\n";
		echo "Finally, we can have CouchDB's complete response body using \$e->getBody() : ". print_r($e->getBody(),true)."\n";
		exit (1);
	} else {
		echo "It seems that something wrong happened. You can have more details using :\n";
		echo "the exception class with get_class(\$e) : ".get_class($e)."\n";
		echo "the exception error code with \$e->getCode() : ".$e->getCode()."\n";
		echo "the exception error message with \$e->getMessage() : ".$e->getMessage()."\n";
		exit (1);
	}
}
echo "Database successfully created. CouchDB sent the response :".print_r($result,true)."\n";

echo "Just to see exceptions, we try to create the database (that already exist...)\n";
try {
        $result = $client->createDatabase();
} catch (Exception $e) {
        if ( $e instanceof CouchException ) {
                echo "We issued the request, but couch server returned an error.\n";
                echo "We can have HTTP Status code returned by couchDB using \$e->getCode() : ". $e->getCode()."\n";
                echo "We can have error message returned by couchDB using \$e->getMessage() : ". $e->getMessage()."\n";
                echo "Finally, we can have CouchDB's complete response body using \$e->getBody() : ". print_r($e->getBody(),true)."\n";
        } else {
                echo "It seems that something wrong happened. You can have more details using :\n";
                echo "the exception class with get_class(\$e) : ".get_class($e)."\n";
                echo "the exception error code with \$e->getCode() : ".$e->getCode()."\n";
                echo "the exception error message with \$e->getMessage() : ".$e->getMessage()."\n";
        }
}


/**
*CouchDB gives lots of details for a single database
*
*Let's see that
*
*/

echo "Getting database informations\n";
try {
	$db_infos = $client->getDatabaseInfos();
} catch (Exception $e) {
	echo "Something weird happened  :".$e->getMessage()." (errcode=".$e->getCode().")\n";
	exit(1);
}
echo "Database informations : \n".print_r($db_infos,true)."\n";
echo "Displaying database disk usage using \$db_infos->disk_size: ".$db_infos->disk_size." bytes\n";


/**
* Finally delete database
*
*
*
*/
echo "Deleting database\n";
try {
        $result = $client->deleteDatabase();
} catch ( Exception $e) {
        echo "Something weird happened: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
        exit(1);
}
echo "Database deleted. CouchDB response: ".print_r($result,true)."\n";

echo "\nTo learn more database features : https://github.com/PHP-on-Couch/PHP-on-Couch/blob/master/doc/database.rst \n";
