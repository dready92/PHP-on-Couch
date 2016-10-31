[![Build Status](https://travis-ci.org/popojargo/PHP-on-Couch.svg?branch=master)](https://travis-ci.org/popojargo/PHP-on-Couch)
[![Coverage Status](https://coveralls.io/repos/github/popojargo/PHP-on-Couch/badge.svg?branch=master)](https://coveralls.io/github/popojargo/PHP-on-Couch?branch=master)
#2.0.0
To access PHP-on-Couch for CouchDB 1.6.1, please visit [this link](http://github.com/popojargo/PHP-on-Couch/blob/1.6.1).

##What's new

Due to the lack of support on the last repository, I forked it and I will make sure it's kept active.


With the new release of 2.0, the master branch will support only this version.

##Changes

Since I forked the origin branch, I updated the library a bit. We are now using Namespaces for the whole library. It's cleaner to use it with an autoloader. Please take a look to the updated examples for more details.



##Introduction

[PHP On Couch](http://github.com/dready92/PHP-on-Couch/) tries to provide an easy way to work with your [CouchDB](http://couchdb.apache.org) [documents](http://wiki.apache.org/couchdb/HTTP_Document_API) with [PHP](http://php.net). Some code first :

At first, you need to import the main components through their namespace. If you use composer, I suggest you to use their autoload wich is easy to setup. Otherwise, you can use your own autoload function or a basic require with some namespace escaping.

```
use PHPOnCouch\Couch, //The core of PHP-on-Couch
    PHPOnCouch\CouchAdmin, //The object to handle admins
    PHPOnCouch\CouchClient; //The CouchDB client object

```

Here's an example for basic operations

```
// Set a new connector to the CouchDB server
$client = new CouchClient('http://my.couch.server.com:5984', 'my_database');

// document fetching by ID
$doc = $client->getDoc('some_doc_id');
// updating document
$doc->newproperty = array("hello !", "world");
try {
    $client->storeDoc($doc);
} catch (Exception $e) {
    echo "Document storage failed : " . $e->getMessage() . "<BR>\n";
}
```

Here's a quick example of how to fetch a view

```
// view fetching, using the view option limit
try {
    $view = $client->limit(100)->getView('orders', 'by-date');
} catch (Exception $e) {
    echo "something weird happened: " . $e->getMessage() . "<BR>\n";
}
```

Finally, how to use the CouchDocument class.

```
//using couch_document class :
$doc = new CouchDocument($client);
$doc->set(array('_id' => 'JohnSmith', 'name' => 'Smith', 'firstname' => 'John')); //create a document and store it in the database
echo $doc->name; // should echo "Smith"
$doc->name = "Brown"; // set document property "name" to "Brown" and store the updated document in the database
```

##Components

This library has four main classes and a custom [Exception](http://php.net/manual/en/language.exceptions.php) class.

Couch class
-----------

This is the most basic of the three classes, and is responsible for the low level dialog between PHP and the CouchDB server. There should be no need of using it directly.

CouchClient class
------------------

This class maps all the actions the application can do on the CouchDB server. Documentation is split in three main topics :

### [database stuff](http://github.com/popojargo/PHP-on-Couch/blob/master/doc/couch_client-database.md)

list databases, create and delete a database, retrieve database informations, test whether a database exists, get uuids, get databases changes

### [document stuff](http://github.com/popojargo/PHP-on-Couch/blob/master/doc/couch_client-document.md)

fetching and storing documents, copy a document, store and delete document attachments, getting all documents

### [view stuff](http://github.com/popojargo/PHP-on-Couch/blob/master/doc/couch_client-view.md)

calling a view with query options : key, startkey, endkey, limit, stale, ...

CouchDocument class
--------------------

Easing the manipulation of documents, the CouchDocument class uses PHP magic getters and setters.

[CouchReplicator class](http://github.com/popojargo/PHP-on-Couch/blob/master/doc/couch_replicator.md)
---------------------

A dedicated class to manage replications over different instances of CouchDB databases.

[CouchAdmin class](http://github.com/popojargo/PHP-on-Couch/blob/master/doc/couch_admin.md)
----------------

A class to manage users and database/users associations

Quick-start guide
=================

1. Copy the /src folder somewhere in a PHP-on-Couch folder. *Easier way of installer will be provided soon*.
   
2. Import those classes whenever you need to access CouchDB server :

```
use PHPOnCouch\Couch, 
    PHPOnCouch\CouchAdmin, 
    PHPOnCouch\CouchClient; 
```

If you need to use replication features, also use the couchReplicator definition :

        use PHPOnCouch\CouchReplicator;

3. Create a client object. You have to tell it the _Data source name_ (dsn) of your CouchDB server, as well as the name of the database you want to work on. The DSN is the URL of your CouchDB server, for example _http://localhost:5984_.
        
        $client = new CouchClient($couchdb_server_dsn, $couchdb_database_name);

4. Use it !
        
        try {
            $client->createDatabase();
        } catch (Exception $e) {
            echo "Unable to create database : ".$e->getMessage();
        }
        
        $doc = new CouchDocument($client);
        $doc->set( array('_id'=>'some_doc_id', 'type'=>'story','title'=>"First story") );
        
        $view = $client->limit(10)->descending(TRUE)->getView('some_design_doc','viewname');
        
Feedback
========

Don't hesitate to submit feedback, bugs and feature requests ! My contact address is alexiscote19 at hotmail dot com

Resources
=========

[Database API](http://github.com/popojargo/PHP-on-Couch/blob/master/doc/couch_client-database.md)

[Document API](http://github.com/popojargo/PHP-on-Couch/blob/master/doc/couch_client-document.md)

[View API](http://github.com/popojargo/PHP-on-Couch/blob/master/doc/couch_client-view.md)

[couchDocument API](http://github.com/popojargo/PHP-on-Couch/blob/master/doc/couch_document.md)

[couchReplicator API](http://github.com/popojargo/PHP-on-Couch/blob/master/doc/couch_replicator.md)

[couchAdmin API](http://github.com/popojargo/PHP-on-Couch/blob/master/doc/couch_admin.md)
