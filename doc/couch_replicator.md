This section give details on using the couchReplicator object.

Replication of CouchDB databases
================================

CouchDB supports replicating a database on other CouchDB databases. Think of replication as a copy-paste operation on databases.

The couchReplicator object is a simple abstraction of the CouchDB replication model. Those replication features are available in CouchDB 0.11 . At the time of this coding, canceling a continuous replication doesn't seem to always work.

To create a new couchReplicator object, you first have to include necessary files, and then instanciate the object, passing in argument a couchClient instance.

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchDocument.php";
    require_once "lib/couchReplicator.php";
    
    $client = new couchClient ("http://localhost:5984/","mydb" );
    // I create a replicator instance
    $replicator = new couchReplicator($client);


Replication Basics
==================

To replicate a database to another existing database, use the **to()** method.

Example :

    $client = new couchClient ("http://localhost:5984/","mydb" );
    // I create a replicator instance
    $replicator = new couchReplicator($client);
    $response = $replicator->to("http://another.server.com:5984/mydb");
    // database http://localhost:5984/mydb will be replicated to http://another.server.com:5984/mydb

Note that you can replicate on a local databse to, eg :

    $response = $replicator->to("mydb_backup");
    // database http://localhost:5984/mydb will be replicated to http://localhost:5984/mydb_backup

To replicate from a database to an existing database, use the **from()** method.

    $response = $replicator->from("http://another.server.com:5984/mydb");
    // database http://another.server.com:5984/mydb will be replicated to http://localhost:5984/mydb

Please note that CouchDB developpers hardly suggest to use the Pull replication mode : that means to prefer the "from()" method.


Auto-creating the target database
---------------------------------

The **create_target()** chainable method enables CouchDB to automatically create the target database, in case it doesn't exist.

Example :

    $response = $replicator->create_target()->from("http://another.server.com:5984/mydb");

Which is equivalent to :

    $replicator->create_target();
    $response = $replicator->from("http://another.server.com:5984/mydb");

If the target database already exist, the create_target() method has no use.

Replicating only selected documents
-----------------------------------

To replicate only some documents, pass their ids to the **doc_ids()** chainable method.

Example :

    $replicator->doc_ids( array ("some_doc", "some_other_doc") )->from("http://another.server.com:5984/mydb");

This code will replicate documents "some_doc" and "some_other_doc" of database "http://another.server.com:5984/mydb" to database "http://localhost:5984/mydb"

Continuous replication
======================

A continuous replication is a replication that is permanent : once set, any change to the source database will be automatically propagated to the destination database. 

Setting up continuous replication
---------------------------------

To setup a continuous replication, use the **continuous()** chainable method.

Example :

    // setup a continuous replication
    $replicator->continuous()->from("http://another.server.com:5984/mydb");
    // create a couchClient instance on the source database
    $client2 = new couchClient("http://another.server.com:5984/","mydb");
    // create and record a document on the source database
    $doc = new stdClass();
    $doc->_id = "some_doc_on_another_server";
    $doc->type = "foo";
    $client2->storeDoc( $doc );
    // let some time for CouchDB to replicate
    sleep(10);
    // read the document from the destination database
    $doc = $client->getDoc("some_doc_on_another_server");
    echo $doc->type;
    
Cancelling a continuous replication
-----------------------------------

To cancel a previously setup continuous replication, use the **cancel()** chainable method.

Example :

    // setup a continuous replication
    $replicator->continuous()->from("http://another.server.com:5984/mydb");
    (...) //code code code
    // remove the continuous replication
    $replicator->cancel()->from("http://another.server.com:5984/mydb");

Filtered replication
====================

To have a full control over which document should be replicated, setup a filter definition on the source database. Then use the **filter()** chainable method to filter replicated documents.

    // create a couchClient instance pointing to the source database
    $source_client = new couchClient("http://localhost:5984","mydb");
    // create a couchClient instance pointing to the target database
    $target_client = new couchClient("http://another.server.com:5984","mydb")
    
    // create a design doc
    $doc = new stdClass();
    $doc->_id = "_design/replication_rules";
    $doc->language = "javascript";
    // create a "no_design_doc" filter : only documents without the string "_design" will be replicated
    $doc->filters = array (
        "no_design_doc" => "function (doc, req) {
            if ( doc._id.match('_design') ) {
                return false;
            } else {
                return true;
            }
        }"
    );
    // store the design doc in the SOURCE database
    $target_client->storeDoc($doc);
    
    //create a couchReplicator instance on the destination database
    $replicator = new couchReplicator($target_client);
    
    // replicate source database to target database, using the "no_design_doc" filter
    $replicator->filter('replication_rules/no_design_doc')->from($source_client->getDatabaseUri());

Using request parameters in replication filters
-----------------------------------------------

Filters can have a query parameters. This allows more generic filter codes.
Let's modify the filter code above to pass the string to compare the document id to via query parameters :

    // create a couchClient instance pointing to the source database
    $source_client = new couchClient("http://localhost:5984","mydb");
    // create a couchClient instance pointing to the target database
    $target_client = new couchClient("http://another.server.com:5984","mydb")
    
    // create a design doc
    $doc = new stdClass();
    $doc->_id = "_design/replication_rules";
    $doc->language = "javascript";
    // create a "no_design_doc" filter : only documents without the string "_design" will be replicated
    $doc->filters = array (
        "no_str_in_doc" => "function (doc, req) {
            if ( doc._id.match( req.query.needle ) ) {
                return false;
            } else {
                return true;
            }
        }"
    );
    // store the design doc in the SOURCE database
    $target_client->storeDoc($doc);
    
    //create a couchReplicator instance on the destination database
    $replicator = new couchReplicator($target_client);
    
    // replicate source database to target database, using the "no_str_in_doc" filter, and setting needle to "_design"
    $params = array ("needle"=>"_design");
    $replicator->query_params($params)->filter('replication_rules/no_str_in_doc')->from($source_client->getDatabaseUri());

Replication of individual couchDocuments
========================================

Please read the couchDocument documentation to learn how to simply replicate a document to or from a database to another


