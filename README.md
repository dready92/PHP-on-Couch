Introduction
============

[PHP On Couch](http://github.com/dready92/PHP-on-Couch/) tries to provide an easy way to work with your [CouchDB](http://couchdb.apache.org) [documents](http://wiki.apache.org/couchdb/HTTP_Document_API) with [PHP](http://php.net). Some code first :

    <?PHP
    require_once 'couch.php';
    require_once 'couchClient.php';
    require_once 'couchDocument.php';
    
    // set a new connector to the CouchDB server
    $client = new couchClient ('http://my.couch.server.com:5984','my_database');
    
    // document fetching by ID
    $doc = $client->getDoc('some_doc_id');
    // updating document
    $doc->newproperty = array("hello !","world");
    try {
       $client->storeDoc($doc);
    } catch (Exception $e) {
       echo "Document storage failed : ".$e->getMessage()."<BR>\n";
    }

    // view fetching, using the view option limit
    try {
       $view = $client->limit(100)->getView('orders','by-date');
    } catch (Exception $e) {
       echo "something weird happened: ".$e->getMessage()."<BR>\n";
    }

    //using couch_document class :
    $doc = new couchDocument($client);
    $doc->set( array('_id'=>'JohnSmith','name'=>'Smith','firstname'=>'John') ); //create a document and store it in the database
    echo $doc->name ; // should echo "Smith"
    $doc->name = "Brown"; // set document property "name" to "Brown" and store the updated document in the database

Components
==========

This library has four main classes and a custom [Exception](http://php.net/manual/en/language.exceptions.php) class.

couch class
-----------

This is the most basic of the three classes, and is responsible for the low level dialog between PHP and the CouchDB server. There should be no need of using it directly.

couchClient class
------------------

This class maps all the actions the application can do on the CouchDB server. Documentation is split in three main topics :

### [database stuff](http://github.com/dready92/PHP-on-Couch/blob/master/doc/couch_client-database.md)

list databases, create and delete a database, retrieve database informations, test whether a database exists, get uuids, get databases changes

### [document stuff](http://github.com/dready92/PHP-on-Couch/blob/master/doc/couch_client-document.md)

fetching and storing documents, copy a document, store and delete document attachments, getting all documents

### [view stuff](http://github.com/dready92/PHP-on-Couch/blob/master/doc/couch_client-view.md)

calling a view with query options : key, startkey, endkey, limit, stale, ...

couchDocument class
--------------------

Easing the manipulation of documents, the couchDocument class uses PHP magic getters and setters.

[couchReplicator class](http://github.com/dready92/PHP-on-Couch/blob/master/doc/couch_replicator.md)
---------------------

A dedicated class to manage replications over different instances of CouchDB databases.

[couchAdmin class](http://github.com/dready92/PHP-on-Couch/blob/master/doc/couch_admin.md)
----------------

A class to manage users and database/users associations

Quick-start guide
=================

1. copy couch.php, couchClient.php and couchDocument.php somewhere on your disk
   
2. Include those files whenever you need to access CouchDB server :
        
        <?PHP
        require_once "couch.php";
        require_once "couchClient.php";
        require_once "couchDocument.php";

If you need to use replication features, also include the couchReplicator definition :

        require_once "couchReplicator.php";

3. Create a client object. You have to tell it the _Data source name_ (dsn) of your CouchDB server, as well as the name of the database you want to work on. The DSN is the URL of your CouchDB server, for example _http://localhost:5984_.
        
        $client = new couchClient($couchdb_server_dsn, $couchdb_database_name);

4. Use it !
        
        try {
            $client->createDatabase();
        } catch (Exception $e) {
            echo "Unable to create database : ".$e->getMessage();
        }
        
        $doc = new couchDocument($client);
        $doc->set( array('_id'=>'some_doc_id', 'type'=>'story','title'=>"First story") );
        
        $view = $client->limit(10)->descending(TRUE)->getView('some_design_doc','viewname');
        
Feedback
========

Don't hesitate to submit feedback, bugs and feature requests ! My contact address is mickael dot bailly at free dot fr

Resources
=========

[Database API](http://github.com/dready92/PHP-on-Couch/blob/master/doc/couch_client-database.md)

[Document API](http://github.com/dready92/PHP-on-Couch/blob/master/doc/couch_client-document.md)

[View API](http://github.com/dready92/PHP-on-Couch/blob/master/doc/couch_client-view.md)

[couchDocument API](http://github.com/dready92/PHP-on-Couch/blob/master/doc/couch_document.md)

[couchReplicator API](http://github.com/dready92/PHP-on-Couch/blob/master/doc/couch_replicator.md)

[couchAdmin API](http://github.com/dready92/PHP-on-Couch/blob/master/doc/couch_admin.md)
