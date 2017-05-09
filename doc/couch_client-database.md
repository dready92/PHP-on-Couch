This section give details on actions on the CouchDB server through PHP on Couch.

## Table of content

- [Getting started](#getting-started)
- [General](#general)
    + [dsn()](#dsn)
    + [getSessionCookie()](#getsessioncookie)
    + [setSessionCookie($cookie)](#setsessioncookiecookie)
    + [isValidDatabaseName($name)](#isvaliddatabasename)
    + [listDatabases()](#listdatabases)
    + [createDatabase()](#createdatabase)
    + [deleteDatabase()](#deletedatabase)
    + [databaseExists()](#databaseexists)
    + [getDatabaseInfos()](#getdatabaseinfos)
    + [getDatabaseUri()](#getdatabaseuri)
    + [getUuids($count = 1)](#getuuidscount--1)
    + [useDatabase($dbName)](#usedatabasedbname)
    + [getMembership()](#getmembership)
    + [getConfig($nodeName[, $section [, $key ]])](#getconfignodename--section--key-)
    + [setConfig($nodeName, $section, $key)](#setconfignodename-section-key-value)
    + [deleteConfig($nodeName , $section , $key)](#deleteconfignodename-section-key)
- [Changes](#changes)
    + [getChanges()](#getchanges)
    + [Chainable methods to use before getChanges()](#chainable-methods-to-use-before-getchanges)
        * [since(string $value)](#sincestring-value)
        * [heartbeat(integer $value)](#heartbeatinteger-value)
        * [feed(string $value, $callback)](#feedstring-value-callback)
        * [filter(string $value, array $additional_query_options)](#filterstring-value-array-additional_query_options)  
        * [style(string $value)](#stylestring-value)   
-  [Maintenance]() 
    + [ensureFullCommit()](#ensurefullcommit)
    + [compactDatabase()](#compactdatabase)
    + [compactAllViews()](#compactallviews)
    + [compactViews($id)](#compactviewsid)
    + [cleanupDatabaseViews()](#cleanupdatabaseviews)

-----


## Getting started

To use PHP on Couch client, you have to create a couchClient instance, setting the URL to your couchDB server, and the database name.

Example : connect to the couchDB server at http://my.server.com on port 5984 and on database mydb :

```php
$client = new CouchClient("http://my.server.com:5984/","mydb");
```

If you want to authenticate to the server using a username & password, just set it in the URL.

Example : connect to the couchDB server at http://my.server.com on port 5984 using the username "couchAdmin", the password "secret" and on database mydb :

```php
$client = new CouchClient("http://couchAdmin:secret@my.server.com:5984/","mydb");
```

You can also tell couchClient to use cookie based authentification, by passing an additional flag "cookie_auth" set to true in the options array, as the third parameter of the couchClient constructor.

Example : as the previous one, but using cookie based authentification

```php
$client = new CouchClient("http://couchAdmin:secret@my.server.com:5984/","mydb", array("cookie_auth"=>true) );
```

You can also manually set the session cookie.

Example : manually setting the session cookie :

```php
$client = new CouchClient("http://my.server.com:5984/","mydb");
$client->setSessionCookie("AuthSession=Y291Y2g6NENGNDgzNzY6Gk0NjM-UKxhpX_IyiH-C-9yXY44");
```

## General

### dsn()

The method **dsn()** returns the DSN of the server. Database is not included in the DSN.

Example :

```php
$client = new CouchClient("http://couch.server.com:5984/","hello");
echo $client->dsn(); // will echo : http://couch.server.com:5984
```

### getSessionCookie()

This method returns the current session cookie if set.

Example :

```php
$cookie = $client->getSessionCookie();
```

### setSessionCookie($cookie)

This method set the cookie and is chainable.

Example :

```php
$cookie = $client->setSessionCookie("AuthSession=Y291Y2g6NENGNDgzNz")->getSessionCookie();
```

### isValidDatabaseName()

Database names on CouchDB have restrictions : only lowercase characters (a-z), digits (0-9), and any of the characters _, $, (, ), +, -, and / are allowed. The name has to start with a lowercase letter (a-z). To test if a given database name is valid, use the static **isValidDatabaseName()** couchClient method.

Note: to allow access to system databases (_users, _replicator), those names added to special list and will return `true`.

Example :

```php
$my_database = "user311(public)";
if ( CouchClient::isValidDatabaseName($my_database) ) {
    $client = new CouchClient("http://couch.server.com:5984/",$my_database);
} else {
    die("Invalid database name");
}
```

### listDatabases()

The method **listDatabases()** lists the available databases on the CouchDB server.

Example :

```php
$dbs = $client->listDatabases();
print_r($dbs); // array ('first_database','another_database')
```

### createDatabase()

The method **createDatabase()** will try to create the database according to the name you set when creating couch_client object $client. Note that, is the database already exist, this method will throw an exception.

Example :

```php
$client->createDatabase();
```

### deleteDatabase()

The method **deleteDatabase()** permanently remove from the server the database according to the name you set when creating couch_client object $client. Note that, if the database does not exist, the method will throw an exception.

Example :

```php
$client->deleteDatabase();
```

### databaseExists()

The method **databaseExists()** test if the database already exist on the server.

Example :

```php
if ( !$client->databaseExists() ) {
    $client->createDatabase();
}
```

### getDatabaseInfos()

The method **getDatabaseInfos()** sends back informations about the database. Informations contains the number of documents in the database, the space of the database on disk, the update sequence number, ...

Example :

```php
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
```

### getDatabaseUri()

The method **getDatabaseUri()** sends back a string giving the HTTP connection URL to the database server.

Example :

```php
echo $client->getDatabaseUri();
/*
http://db.example.com:5984/testdb
*/
```

### getUuids($count = 1)

The method **getUuids($count = 1)** sends back an array of universally unique identifiers (that is, big strings that can be used as document ids)

Example :

```php
print_r($client->getUuids(5));
/*
    array ( 0 => "65a8f6d272b3e5e62ee9de8eacc083a5",
            1 => "e43b04e44233d72b353c1cd8915b886d",
            2 => "7498fb296f19ebc2554a4812f3d9ae12",
            3 => "f3f855a15eb90e9fcdbda5e017b9f2cd",
            4 => "9d9a8214762d06cdf0158d7f6697cac9" )
*/
```

### useDatabase($dbName)

The method **useDatabase($dbname)** changes the working database on the CouchDB server.

Example :

```php
$client = new CouchClient("http://localhost:5984", "db1");
$all_docs_db1 = $client->getAllDocs(); //retrieve all docs of database db1
$client->useDatabase("db2");           //switch to "db2" database
$all_docs_db2 = $client->getAllDocs(); //retrieve all docs of database db2
```

### getMembership()


With the new Cluster infrastructure in CouchDB 2.0, you now have to configure each nodes. To do so, you need to get the information about them. The `_membership`endpoint allow you to get all the nodes that the current nodes knows and all the nodes that are in the same cluster. The method **getMembership()** returns an object like this :


```json
{
  "all_nodes": [],
  "cluster_nodes": []
}
```


### getConfig($nodeName [, $section [, $key ]])

*The configurations methods are implemented for PHP-on-Couch 2.0 only. Note that the configuration is per-node only* 

To configure, you need to use **getConfig($nodeName [, $section [, $key ]])**. If you don't know the nodeName, you can use the **getMembership()** method.

Examples :

**getConfig("couchdb@localhost")**

Returns a JSON object with the whole configuration
```json
{
    "attachments":{

    },
    "couchdb":{
        
    }
}
```

**getConfig("couchdb@localhost","httpd")**
*Note : It will return a CouchNotFoundException is the section is not present*.
Returns  a JSON object that represent the desired section

```json
{
    "allow_jsonp": "false",
    "authentication_handlers": "{couch_httpd_oauth, oauth_authentication_handler}, {couch_httpd_auth, cookie_authentication_handler}, {couch_httpd_auth, default_authentication_handler}",
    "bind_address": "127.0.0.1",
    "default_handler": "{couch_httpd_db, handle_request}",
    "enable_cors": "false",
    "log_max_chunk_size": "1000000",
    "port": "5984",
    "secure_rewrites": "true",
    "vhost_global_handlers": "_utils, _uuids, _session, _oauth, _users"
}

```

**getConfig("couchdb@localhost","log","level")**

Returns either text-plain of JSON value of the section/key.
*Note : It will return a CouchNotFoundException is the section or key are not present*.

```
"debug"
```


### setConfig($nodeName, $section, $key, $value)

*The configurations methods are implemented for PHP-on-Couch 2.0 only. Note that the configuration is per-node only* 

The method **setConfig($nodeName, $section, $key, $value)** let you configure your installation. It can returns CouchNotAuthorizedException or CouchNotFoundException depending on the parameters supplied.

Example :

**setConfig("couchdb@localhost","log","level","info")**

Returns the old value in text-plain or JSON format.
```
"debug"
```

### deleteConfig($nodeName, $section, $key)

*The configurations methods are implemented for PHP-on-Couch 2.0 only. Note that the configuration is per-node only* 

The method **deleteConfig($nodeName, $section, $key)** let you delete a configuration key from your node. 

Example: 

**deleteConfig("couchdb@localhost","log","level")**
It will returns the JSON value of  the parameter before its deletion. Not that the method can throw a CouchNotFoundException or a CouchUnauthorizedException regarding of the section/key and permissions.

```
"info"
```


## Changes

CouchDB implements database changes feedback and polling. [You'll find more infos here](http://books.couchdb.org/relax/reference/change-notifications).
For any event in the database, CouchDB increments a sequence counter.

### getChanges()

The method **getChanges()** sends back a CouchDB changes object.

Example :

```php
print_r($client->getChanges());
/*
    stdClass Object
    (              
        [results] => Array
            (             
                [0] => stdClass Object
                    (                 
                        [seq] => 'example-last-update-sequence'
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
                        [seq] => 'example-last-update-sequence'
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
```

## Chainable methods to use before getChanges()


The following methods allow a fine grained control on the _changes_ request to issue.

### since(string $value)
Retrieve changes that happened after sequence number $value

### heartbeat(integer $value)
Number of milliseconds between each heartbeat line (an ampty line) one logpoll and continuous feeds

### feed(string $value, $callback)
Feed type to use. In case of "continuous" feed type, $callback should be set and should be a PHP callable object (so *is_callable($callback)* should be true)

The callable function or method will receive two arguments : the JSON object decoded as a PHP object, and a couchClient instance, allowing developers to issue CouchDB queries from inside the callback.

### filter(string $value, array $additional_query_options)
Apply the changes filter $value. Add additional headers if any

### style(string $value)
Changes display style, use "all_docs" to switch to verbose

Example :

```php
// fetching changes since sequence 'example-last-update-sequence' using filter "messages/incoming"
$changes = $client->since('example-last-update-sequence')->filter("messages/incoming")->getChanges();
```

Example - Continuous changes with a callback function

```php
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
```

### ensureFullCommit()

The method **ensureFullCommit()** tells couchDB to commit any recent changes to the database file on disk.

Example :

```php
$response = $client->ensureFullCommit();
print_r($response); 
/* should print something like : 
 stdClass Object
	(
		[ok] => 1,
		[instance_start_time] => "1288186189373361"
	)
*/
```

### Maintenance

Three main maintenance tasks can be performed on a CouchDB database : compaction, view compaction, and view cleanup.

### compactDatabase()

CouchDB database file is an append only : during any modification on database documents (add, remove, or update), the modification is recorded at the end of the database file. The compact operation removes old versions of database documents, thus reducing database file size and improving performances. To initiate a compact operation, use the **compactDatabase()** method.

Example :

```php
// asking the server to start a database compact operation
$response = $client->compactDatabase(); // should return stdClass ( "ok" => true )
```
### compactAllViews()

Just as documents files, view files are also append-only files. To compact all view files of all design documents, use the **compactAllViews()** method.

Example :

```php
// asking the server to start a view compact operation on all design documents
$response = $client->compactAllViews(); // return nothing
```
### compactViews($id)

To compact only views from a specific design document, use the **compactViews( $id )** method.

Example :

```php
// asking the server to start a database compact operation on the design document _design/example
$response = $client->compactViews( "example" ); // should return stdClass ( "ok" => true )
```

### cleanupDatabaseViews()

This operation will delete all unused view files. Use the **cleanupDatabaseViews()** method to initiate a cleanup operation on old view files
Example :

```php
// asking the server to start a database view files cleanup operation
$response = $client->cleanupDatabaseViews(); // should return stdClass ( "ok" => true )
```
