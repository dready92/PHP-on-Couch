This section give details on actions on the CouchDB server through PHP on Couch.

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
    db.example.com:5984/testdb
    */


Get Universally Unique IDentifiers
================

The method **getUuids($count)** sends back an array of universally unique identifiers (that is, big strings that can be used as document ids)

Example :

    print_r($client->getUuids(5));
    /*
		array (	0 => "65a8f6d272b3e5e62ee9de8eacc083a5",
						1 => "e43b04e44233d72b353c1cd8915b886d",
						2 => "7498fb296f19ebc2554a4812f3d9ae12",
						3 => "f3f855a15eb90e9fcdbda5e017b9f2cd",
						4 => "9d9a8214762d06cdf0158d7f6697cac9" )
    */

