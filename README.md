Introduction
============

[PHP On Couch](http://dready.byethost31.com/index.php/display/edit/192) try to provide an easy way to work with your [CouchDB](http://couchdb.apache.org) [documents](http://wiki.apache.org/couchdb/HTTP_Document_API) with [PHP](http://php.net). Some code first :

    <?PHP
    require_once 'couch.php';
    require_once 'couch_client.php';
    require_once 'couch_document.php';
    
    $client = new couch_client ('my.couch.server.com',5984,'my_database'); // set a new connector to the CouchDB server
    
    // list databases
    foreach ( $client->dbs_list() as $one_db )
        echo "Found database $one_db on the server<BR>\n";
    
    $doc = $client->doc_get('some_doc_id'); // get a document as a PHP stdClass object
    $doc->newproperty = array("hello !","world"); //then add a property
    //and store the doc
    try {
       $client->doc_store($doc);
    } catch (Exception $e) {
       echo "Document storage failed : ".$e->getMessage()."<BR>\n";
    }
    
    //using couch_document class :
    $doc = new couch_document($client);
    $doc->set( array('_id'=>'JohnSmith','name'=>'Smith','firstname'=>'John') ); //create a document and store it in the database
    echo $doc->name ; // should echo "Smith"
    $doc->name = "Brown"; // set document property "name" to "Brown" and store the updated document in the database

Components
==========

This library got three main classes, and a custom [Exception](http://php.net/manual/en/language.exceptions.php) class.

couch class
-----------

This is the most basic of the three classes, and is responsible for the low level dialog between PHP and the CouchDB server. There should be no need of using it directly.

couch_client class
------------------

This class maps all the actions the application can do on the CouchDB server. We can find three main topics :

### database stuff

listing databases, create and delete a database, retrieve database informations, test whether a databse exists

### document stuff

fetching and storing documents, copy a document, store and delete document attachments, getting all docs

### view stuff

calling a view with query options : key, startkey, endkey, limit, stale, ...

couch_document class
--------------------

Easing the manipulation of documents, the couch_document class uses PHP magic getters and setters.

Quick-start guide
=================

1. copy couch.php, couch_client.php and couch_document.php somewhere on your disk
   
2. Include those files whenever you need to access CouchDB server :
        
        <?PHP
        require_once "couch.php";
        require_once "couch_client.php";
        require_once "couch_document.php";

3. Create a client object. You have to tell it the _hostname_ and _port_ of your CouchDB server, as well as the name of the database you want to work on
        
        $client = new couch_client($couchdb_server_hostname, $couchdb_server_port, $couchdb_database_name);

4. Use it !
        
        try {
            $client->db_create();
        } catch (Exception $e) {
            echo "Unable to create database : ".$e->getMessage();
        }
        
        $doc = new couch_document($client);
        $doc->set( array('_id'=>'some_doc_id', 'type'=>'story','title'=>"First story") );
        
        $view = $client->limit(10)->descending(TRUE)->get_view('some_design_doc','viewname');
        
Feedback
========

Don't hesitate to submit feedback, bugs and feature requests !

Resources
=========

[PHP on Couch API](http://dready.byethost31.com/index.php/display/view/193)

[Database API](http://dready.byethost31.com/index.php/display/view/194)

[Document API](http://dready.byethost31.com/index.php/display/view/195)

[View API](http://dready.byethost31.com/index.php/display/view/196)

