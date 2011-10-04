This section give details on actions on the CouchDB server through PHP on Couch.

Creating a CouchDB connection
=============================

To use PHP on Couch client, you have to create a couchClient instance, setting the URL to your couchDB server, and the database name.

Example : connect to the couchDB server at http://my.server.com on port 5984 and on database mydb :

    $client = new couchClient("http://my.server.com:5984/","mydb");

If you want to authenticate to the server using a username & password, just set it in the URL.

Example : connect to the couchDB server at http://my.server.com on port 5984 using the username "couchAdmin", the password "secret" and on database mydb :

    $client = new couchClient("http://couchAdmin:secret@my.server.com:5984/","mydb");

You can also tell couchClient to use cookie based authentification, by passing an additional flag "cookie_auth" set to TRUE in the options array, as the third parameter of the couchClient constructor.

Example : as the previous one, but using cookie based authentification

    $client = new couchClient("http://couchAdmin:secret@my.server.com:5984/","mydb", array("cookie_auth"=>TRUE) );

You can also manually set the session cookie.

Example : manually setting the session cookie :

    $client = new couchClient("http://my.server.com:5984/","mydb");
    $client->setSessionCookie("AuthSession=Y291Y2g6NENGNDgzNzY6Gk0NjM-UKxhpX_IyiH-C-9yXY44");



Get server DSN
==============

The method **dsn()** returns the DSN of the server. Database is not included in the DSN.

Example :

    $client = new couchClient("http://couch.server.com:5984/","hello");
    echo $client->dsn(); // will echo : http://couch.server.com:5984

Testing a database name
=======================

Database names on CouchDB have restrictions : only lowercase characters (a-z), digits (0-9), and any of the characters _, $, (, ), +, -, and / are allowed. To test if a given database name is valid, use the static **isValidDatabaseName()** couchClient method.

Example :

    $my_database = "user311(public)";
    if ( couchClient::isValidDatabaseName($my_database) ) {
        $client = new couchClient("http://couch.server.com:5984/",$my_database);
    } else {
        die("Invalid database name");
    }

List databases
==============

The method **listDatabases()** lists the available databases on the CouchDB server.

Example :

    $dbs = $client->listDatabases();
    print_r($dbs); // array ('first_database','another_database')

Create a database
=================

The method **createDatabase()** will try to create the database according to the name you set when creating couch_client object $client. Note that, is the database already exist, this method will throw an exception.

Example :

    $client->createDatabase();

Delete a database
=================

The method **deleteDatabase()** permanently remove from the server the database according to the name you set when creating couch_client object $client. Note that, if the database does not exist, the method will throw an exception.

Example :

    $client->deleteDatabase();

Test whether a database exist
=============================

The method **databaseExists()** test if the database already exist on the server.

Example :

    if ( !$client->databaseExists() ) {
        $client->createDatabase();
    }

Get database informations
=========================

The method **getDatabaseInfos()** sends back informations about the database. Informations contains the number of documents in the database, the space of the database on disk, the update sequence number, ...

Example :

    print_r($client->getDatabaseInfos());
    /*
    array("db_name" => "testdb",
          "doc_count" => 2, 
          "doc_del_count" => 0, 
          "update_seq" => 6,
          "purge_seq" => 0, 
          "compact_running" => false, 
          "disk_size" => 277707, 
          "instance_start_time" => "1246277543362647"
     )
     */

Get database URI
================

The method **getDatabaseUri()** sends back a string giving the HTTP connection URL to the database server.

Example :

    echo $client->getDatabaseUri();
    /*
    http://db.example.com:5984/testdb
    */


Get Universally Unique IDentifiers
==================================

The method **getUuids($count)** sends back an array of universally unique identifiers (that is, big strings that can be used as document ids)

Example :

    print_r($client->getUuids(5));
    /*
        array ( 0 => "65a8f6d272b3e5e62ee9de8eacc083a5",
                1 => "e43b04e44233d72b353c1cd8915b886d",
                2 => "7498fb296f19ebc2554a4812f3d9ae12",
                3 => "f3f855a15eb90e9fcdbda5e017b9f2cd",
                4 => "9d9a8214762d06cdf0158d7f6697cac9" )
    */

Change the current database on a server
=======================================

The method **useDatabase($dbname)** changes the working database on the CouchDB server.

Example :

    $client = new couchClient("http://localhost:5984", "db1");
    $all_docs_db1 = $client->getAllDocs(); //retrieve all docs of database db1
    $client->useDatabase("db2");           //switch to "db2" database
    $all_docs_db2 = $client->getAllDocs(); //retrieve all docs of database db2


Database changes interface
==========================

CouchDB implements database changes feedback and polling. [You'll find more infos here](http://books.couchdb.org/relax/reference/change-notifications).
For any event in the database, CouchDB increments a sequence counter.

Getting changes
--------------

The method **getChanges()** sends back a CouchDB changes object.

Example :

    print_r($client->getChanges());
    /*
        stdClass Object
        (              
            [results] => Array
                (             
                    [0] => stdClass Object
                        (                 
                            [seq] => 3
                            [id] => 482fa0bed0473fd651239597d1080f03
                            [changes] => Array
                                (
                                    [0] => stdClass Object
                                        (
                                            [rev] => 3-58cae2758cea3e82105e1090d81a9e02
                                        )
        
                                )
        
                            [deleted] => 1
                        )
        
                    [1] => stdClass Object
                        (
                            [seq] => 4
                            [id] => 2f3f913f34d60e473fad4334c13a24ed
                            [changes] => Array
                                (
                                    [0] => stdClass Object
                                        (
                                            [rev] => 1-4c6114c65e295552ab1019e2b046b10e
                                        )
        
                                )
        
                        )
        
                )
        
            [last_seq] => 4
        )
    */


Chainable methods to use before getChanges()
------------------------------------------

The following methods allow a fine grained control on the _changes_ request to issue.

**since(integer $value)**: retrieve changes that happened after sequence number $value

**heartbeat(integer $value)**: number of milliseconds between each heartbeat line (an ampty line) one logpoll and continuous feeds

**feed(string $value,$callback)**: feed type to use. In case of "continuous" feed type, $callback should be set and should be a PHP callable object (so *is_callable($callback)* should be true)

The callable function or method will receive two arguments : the JSON object decoded as a PHP object, and a couchClient instance, allowing developers to issue CouchDB queries from inside the callback.

**filter(string $value, array $additional_query_options)**: apply the changes filter $value. Add additional headers if any

**style(string $value)**: changes display style, use "all_docs" to switch to verbose

Example :

    // fetching changes since sequence number 546 using filter "messages/incoming"
    $changes = $client->since(546)->filter("messages/incoming")->getChanges();

Example - Continuous changes with a callback function

    function index_doc($change,$couch) {
    	if( $change->deleted == true ) {
    		// won't index a deleted file
    		return ;
    	}
    	echo "indexing ".$change->id."\n";
    	$doc = $couch->getDoc($change->id);
    	unset($doc->_rev);
    	$id = $doc->_id;
    	unset($doc->_id);
    	my_super_fulltext_search_appliance::index($id, $doc);
    }
    	
    $client->feed('continuous','index_doc')->getChanges();
    // will return when index_doc returns false or on socket error

Force hard drive commit
=======================

The method **ensureFullCommit()** tells couchDB to commit any recent changes to the database file on disk.

Example :

    $response = $client->ensureFullCommit();
    print_r($response); 
    /* should print something like : 
     stdClass Object
    	(
    		[ok] => 1,
    		[instance_start_time] => "1288186189373361"
    	)
    */


Database maintenance tasks
==========================

Three main maintenance tasks can be performed on a CouchDB database : compaction, view compaction, and view cleanup.

Database compaction
-------------------

CouchDB database file is an append only : during any modification on database documents (add, remove, or update), the modification is recorded at the end of the database file. The compact operation removes old versions of database documents, thus reducing database file size and improving performances. To initiate a compact operation, use the **compactDatabase()** method.

Example :

    // asking the server to start a database compact operation
    $response = $client->compactDatabase(); // should return stdClass ( "ok" => true )

View compaction
---------------

Just as documents files, view files are also append-only files. To compact all view files of all design documents, use the **compactAllViews()** method.

Example :

    // asking the server to start a view compact operation on all design documents
    $response = $client->compactAllViews(); // return nothing

To compact only views from a specific design document, use the **compactViews( $id )** method.

Example :

    // asking the server to start a database compact operation on the design document _design/example
    $response = $client->compactViews( "example" ); // should return stdClass ( "ok" => true )


View files cleanup
------------------

This operation will delete all unused view files. Use the **cleanupDatabaseViews()** method to initiate a cleanup operation on old view files
Example :

    // asking the server to start a database view files cleanup operation
    $response = $client->cleanupDatabaseViews(); // should return stdClass ( "ok" => true )





