#!/usr/bin/php -q
<?PHP
/**

 This script demonstrates the basics of storing and retrieving documents using PHP On Couch

 For this to work, you have to tell the database DSN (example : http://couch.server.com:5984/) and the name of a database that does not exist

*/

$couch_dsn = "http://localhost:5984/";
$couch_db = "example";


/**
* include the library
*/

require_once "../lib/couch.php";
require_once "../lib/couchClient.php";
require_once "../lib/couchDocument.php";

/**
* create the client
*/
$client = new couchClient($couch_dsn,$couch_db);


/**
* As usual we create the database
*
*
*/
echo "#### Creating database ".$client->getDatabaseUri().': $result = $client->createDatabase();'."\n";
try {
        $result = $client->createDatabase();
} catch (Exception $e) {
        if ( $e instanceof couchException ) {
                echo "We issued the request, but couch server returned an error.\n";
                echo "We can have HTTP Status code returned by couchDB using \$e->getCode() : ". $e->getCode()."\n";
                echo "We can have error message returned by couchDB using \$e->getMessage() : ". $e->getMessage()."\n";
                echo "Finally, we can have CouchDB's complete response body using \$e->getBody() : ". print_r($e->getBody(),true)."\n";
		echo "Are you sure that your CouchDB server is at $couch_dsn, and that database $couch_db does not exist ?\n";
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


/**
* We now want to store a document.
* The first step is to have a PHP object with properties being the document property.
*
* To store the document, we call the method $client->storeDoc($doc)
*
* Important : if the object got an _id property, it'll be the unique document id in the couchdb database.
* If _id is not set, CouchDB will choose one for us.
*
*/
echo "#### Storing a document\n";

$doc = new stdClass();
$doc->_id = "some_doc";
$doc->title = "Important documentation";
$doc->tags = array("documentation","secret");

echo "Storing \$doc : \$client->storeDoc(\$doc)\n";
try {
	$response = $client->storeDoc($doc);
} catch (Exception $e) {
	echo "Something weird happened: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
	exit(1);
}
echo "The document is stored. CouchDB response body: ".print_r($response,true)."\n";

echo "#### Storing a document without _id property\n";

$doc = new stdClass();
$doc->title = "not really documentation";
$doc->tags = array("documentation","fake");
echo "Storing \$doc : \$client->storeDoc(\$doc)\n";
try {
        $response = $client->storeDoc($doc);
} catch (Exception $e) {
        echo "Something weird happened: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
        exit(1);
}
echo "The document is stored. CouchDB response body: ".print_r($response,true)."\n";
echo "CouchDB created the unique identifier ".$response->id." for this document\n";







/**
* Updating a document
*
* To update an existing document, you should tell CouchDB the latest revision of the document.
* That way, if someone updated the document between the time you read it (to get revision & infos)
* and the time you update it, your update will fail and you'll know the document changed.
*
*
*/

echo "==== using previous \$doc object to update it\n";
echo "Adding revison and id properties to the object :\n";
echo "\$doc->_id = \$response->id;\n";
$doc->_id = $response->id;
echo "\$doc->_rev = \$response->rev;\n";
$doc->_rev = $response->rev;
echo "Making a change : \$doc->tags[] = \"updated\";\n";
$doc->tags[] = "updated";

echo "==== storing document\n";
echo "Storing \$doc : \$client->storeDoc(\$doc)\n";
try {
        $response = $client->storeDoc($doc);
} catch (Exception $e) {
        echo "Something weird happened: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
        exit(1);
}
echo "The document is stored. CouchDB response body: ".print_r($response,true)."\n";
echo "The revision property changed : ".$response->rev."\n";




/**
* If we update once again $doc without updating _rev couchDB returns a 409 "Document update conflict"
*
*
*/
echo "#### Storing doc without updating _rev property (this should gice an error)\n";
try {
	$result = $client->storeDoc($doc);
} catch (Exception $e ) {
	echo "The document update failed: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
}










/**
* To retrieve a document, just use getDoc($doc_id)
*
*
*
*
*/
echo "#### Getting document: some_doc\n";
echo "getting doc : \$doc = \$client->getDoc('some_doc')\n";
try {
	$doc = $client->getDoc('some_doc');
} catch (Exception $e) {
	if ( $e->code() == 404 ) {
		echo "Document \"some_doc\" not found\n";
	} else {
		echo "Something weird happened: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
	}
	exit(1);
}
echo "Document retrieved: ".print_r($doc,true)."\n";



/**
* Finally, deleting a document is achieved using deleteDoc
*
*
*
*/
echo "#### Deleting document \"some_doc\"\n";
echo "delete using previous \$doc object : \$client->deleteDoc(\$doc)\n";
try {
	$result = $client->deleteDoc($doc);
} catch (Exception $e) {
	echo "Something weird happened: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
	exit(1);
}
echo "Doc deleted, CouchDB response body: ".print_r($result,true)."\n";








/**
* Finally delete database
*
*
*
*/
try {
	$result = $client->deleteDatabase();
} catch ( Exception $e) {
	echo "Something weird happened: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
        exit(1);
}

