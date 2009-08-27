Introduction
============

[PHP On Couch](http://dready.byethost31.com/index.php/display/edit/192) try to provide an easy way to work with your [CouchDB](http://couchdb.apache.org) [documents](http://wiki.apache.org/couchdb/HTTP_Document_API) with [PHP](http://php.net). Some code first :

    <?PHP
    require_once 'couch.php';
    require_once 'couchClient.php';
    require_once 'couchDocument.php';
    
    $client = new couchClient ('my.couch.server.com',5984,'my_database'); // set a new connector to the CouchDB server
    
    // list databases
    foreach ( $client->listDatabases() as $one_db )
        echo "Found database $one_db on the server<BR>\n";
    
    $doc = $client->getDoc('some_doc_id'); // get a document as a PHP stdClass object
    $doc->newproperty = array("hello !","world"); //then add a property
    //and store the doc
    try {
       $client->storeDoc($doc);
    } catch (Exception $e) {
       echo "Document storage failed : ".$e->getMessage()."<BR>\n";
    }
    
    //using couch_document class :
    $doc = new couchDocument($client);
    $doc->set( array('_id'=>'JohnSmith','name'=>'Smith','firstname'=>'John') ); //create a document and store it in the database
    echo $doc->name ; // should echo "Smith"
    $doc->name = "Brown"; // set document property "name" to "Brown" and store the updated document in the database

Components
==========

This library got three main classes, and a custom [Exception](http://php.net/manual/en/language.exceptions.php) class.

couch class
-----------

This is the most basic of the three classes, and is responsible for the low level dialog between PHP and the CouchDB server. There should be no need of using it directly.

couchClient class
------------------

This class maps all the actions the application can do on the CouchDB server. We can find three main topics :

### database stuff

list databases, create and delete a database, retrieve database informations, test whether a databse exists

### document stuff

fetching and storing documents, copy a document, store and delete document attachments, getting all documents

### view stuff

calling a view with query options : key, startkey, endkey, limit, stale, ...

couch_document class
--------------------

Easing the manipulation of documents, the couchDocument class uses PHP magic getters and setters.

Quick-start guide
=================

1. copy couch.php, couchClient.php and couchDocument.php somewhere on your disk
   
2. Include those files whenever you need to access CouchDB server :
        
        <?PHP
        require_once "couch.php";
        require_once "couchClient.php";
        require_once "couchDocument.php";

3. Create a client object. You have to tell it the _hostname_ and _port_ of your CouchDB server, as well as the name of the database you want to work on
        
        $client = new couchClient($couchdb_server_hostname, $couchdb_server_port, $couchdb_database_name);

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

[PHP on Couch API](http://dready.byethost31.com/index.php/display/view/193)

[Database API](http://dready.byethost31.com/index.php/display/view/194)

[Document API](http://dready.byethost31.com/index.php/display/view/195)

[View API](http://dready.byethost31.com/index.php/display/view/196)

