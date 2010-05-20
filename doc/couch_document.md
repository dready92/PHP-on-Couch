This section give details on using couchDocument data mapper

couchDocuments to simplify the code
===================================

CouchDB embed a simple JSON/REST HTTP API. You can simplify even more your PHP code using couch documents.
Couch Documents take care of revision numbers, and automatically propagate updates on database.

The basics
==========

Creating a new document
=======================

To create an empty couchDocument, simply instanciate the **couchDocument** class, passing the couchClient object as the constructor argument.

Example :

    $client = new couchClient('http://localhost:5984/','myDB');
    $doc = new couchDocument($client);

If I set a property on $doc, it'll be registered in the database. If the property is not _id, the unique identifier will be automatically created by CouchDB, and available in the couchDocument object.

Example :

    $doc->type="contact";
    echo $doc->id();
	// 1961f10823408cc9e1cccc145d35d10d

However if you specify _id, that one will of course be used.

Example :

    $doc = new couchDocument($client);
    $doc->_id = "some_doc";
    echo $doc->id();
    // some_doc

Setting properties
==================

Setting one property at a time
------------------------------

As we just saw, just set the property on the $doc object and it'll be recorded in the database

Example :

    $doc = new couchDocument($client);
    $doc->_id = "some_doc";
    $doc->type = "page";
    $doc->title = "Introduction";

Setting a bunch of properties
-----------------------------

It's always possible to set several properties in one query using the **set()** method

Example using an array :

    $doc = new couchDocument($client);
    $doc->set (
        array(
            '_id'   => 'some_doc',
            'type'  => "page",
            'title' => "Introduction"
        )
    );

Example using an object

    $prop = new stdClass();
    $prop->_id = "some_doc";
    $prop->type = "page";
    $prop->title = "Introduction";
    
    $doc = new couchDocument($client);
    $doc->set ( $prop );

Disabling auto-commit feature
-----------------------------

If, for some reason, you need to disable the auto-commit feature, use the **setAutocommit()** method. In this case, you'll have to explicitely call the **record()** method to store your changes on the database.

Example :

    $doc = new couchDocument($client);
    $doc->setAutocommit(false);
    $doc->_id = "some_doc";
    $doc->type = "page";
    $doc->title = "Introduction";
    $doc->record();

To know if the auto-commit feature is activated, use the **getAutocommit()** method : it returns a boolean.


Unsetting a property
--------------------

To unset a property, just use the **unset** PHP function, as you'll do for a PHP object.

Example :

    $prop = new stdClass();
    $prop->_id = "some_doc";
    $prop->type = "page";
    $prop->title = "Introduction";

    $doc = new couchDocument($client);
    $doc->set ( $prop );
    unset($doc->title);
    echo $doc->title ; // won't echo anything

Fetching a couchDocument from the database
==========================================

The static method **getInstance()** returns a couchDocument when the specified id exists :

Example :

    $doc = couchDocument::getInstance($client,'some_doc');
    echo $doc->_rev."\n";
    echo $doc->type;


Getting a document URI
======================

The method **getUri()** sends back a string giving the current document URI.

Example :

    echo $doc->getUri();
    /*
    db.example.com:5984/testdb/dome_doc_id
    */

Getting back classic PHP object
===============================

To get the couch document fields from a couchDocument object, use the **getFields()** method


Example :

    $doc = couchDocument::getInstance($client,'some_doc');
    print_r($doc->getFields());
    /*
        stdClass object {
            "_id"  => "some_doc",
            "_rev" => "3-234234255677684536",
            "type" => "page",
            "title"=> "Introduction"
        }
    */

Add/Update an attachment
========================

When the attachment is a file on-disk
-------------------------------------

The method **storeAttachment()** adds a new attachment, or update the attachment if it already exists. The attachment contents is located on a file.

Example - Store the file /path/to/some/file.txt as an attachment of document id "some_doc" :

    $doc = couchDocument::getInstance($client,'some_doc');
    try {
        $doc->storeAttachment("/path/to/some/file.txt","text/plain");
    } catch (Exception $e) {
        echo "Error: attachment storage failed : ".$e->getMessage().' ('.$e->getCode().')';
    }

When the attachment is the content of a PHP variable
----------------------------------------------------

The method **storeAsAttachment()** adds a new attachment, or update the attachment if it already exists. The attachment contents is contained in a PHP variable.

Example - Store "Hello world !\nAnother Line" as an attachment named "file.txt" on document "some_doc" :

    $doc = couchDocument::getInstance($client,'some_doc');
    try {
        $doc->storeAsAttachment("Hello world !\nAnother Line", "file.txt" , "text/plain");
    } catch (Exception $e) {
        echo "Error: attachment storage failed : ".$e->getMessage().' ('.$e->getCode().')';
    }

Delete an attachment
====================

The method **deleteAttachment()** permanently removes an attachment from a document.
    
Example - Deletes the attachment "file.txt" of document "some_doc" :

    $doc = couchDocument::getInstance($client,'some_doc');
    try {
        $doc->deleteAttachment("file.txt");
    } catch (Exception $e) {
        echo "Error: attachment removal failed : ".$e->getMessage().' ('.$e->getCode().')';
    }

Getting the URI of an attachment
================================

The method **getAttachmentUri()** returns the URI of an attachment.

Example :

    $doc = couchDocument::getInstance($client,'some_doc');
    if ( $doc->_attachments ) {
        foreach ( $doc->_attachments as $name => $infos ) {
            echo $name.' '.$doc->getAttachmentURI($name); 
            // should say something like "file.txt http://localhost:5984/dbname/some_doc/file.txt"
        }
    }
    try {
        $doc->deleteAttachment("file.txt");
    } catch (Exception $e) {
        echo "Error: attachment removal failed : ".$e->getMessage().' ('.$e->getCode().')';
    }


couchDocuments replication
==========================

The couchDocuments instance provides an easy way to replicate a document to, or from, another database. Think about replication like a copy-paste operation of the document to CouchDB databases.

For those methods to work, you should have included the couchReplicator class file lib/couchReplicator.php .

Replicating a document to another CouchDB database
--------------------------------------------------

Use the **replicateTo()** method to replicate a couchDocument to another couchDB database.

Example :

    $client = new couchClient("http://couch.server.com:5984/","mydb");
    // load an existing document
    $doc = couchDocument::getInstance($client,"some_doc_id");
    // replicate document to another database
    $doc->replicateTo("http://another.server.com:5984/mydb/");

The replicateTo can have another argument, a boolean one. If true, the database will be created on the destination server if it doesn't exist.


Replicating a document from another CouchDB database
--------------------------------------------------

Use the **replicateFrom()** method to replicate a couchDocument from another couchDB database, and then load it into the couchDocument instance.

Example :

    $client = new couchClient("http://couch.server.com:5984/","mydb");
    // load an existing document
    $doc = new couchDocument($client);
    
    // replicate document from another database, and then load it into $doc
    $doc->replicateFrom("some_doc_id","http://another.server.com:5984/mydb/");
    echo $doc->_id ; (should return "some_doc_id")
    $doc->type="foo"; // doc is recorded on "http://couch.server.com:5984/mydb"

    // then replicate $doc back to http://another.server.com:5984/mydb/
    $doc->replicateTo("http://another.server.com:5984/mydb/");

The replicateFrom can have another argument, a boolean one. If true, the database will be created on the destination server if it doesn't exist.

Formating Documents with show functions
=======================================

The **show($id,$name,$additionnal_parameters)** method parses the current document through a CouchDB show function.

Example : the database contains the following design document :

    {
        "_id": "_design/clean",
        "shows": {
            "html": "function (doc, req) {
                        send('<p>ID: '+doc._id+', rev: '+doc._rev+'</p>');
                    }"
        }
    }

and another document that got the id "some_doc". We load the "some_doc" document as a couchDocument object:

    $doc = couchDocument::getInstance($client,"some_doc");

We can then request couchDB to parse this document through a show function :

    $html = $doc->show("clean","html");
    // html should contain "<p>ID: some_doc, rev: 3-2342342346</p>"

The show method is a proxy method to the **getShow()** method of **couchClient**.

Updating a document using update handlers
=========================================

The **update($id,$name,$additionnal_params)** method allows to use the CouchDB [update handlers](http://wiki.apache.org/couchdb/Document_Update_Handlers) feature to update an existing document.
The couchDocument object shouldd have an id for this to work ! Please see **couchClient** *updateDoc* method for more infos.


