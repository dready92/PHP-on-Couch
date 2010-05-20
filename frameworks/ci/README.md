PHP on Couch Ignited class
==========================

The following is a proposed implementation of PHP on Couch to be used with the Code Igniter PHP framework.

It relies on two files : couchdb.php, a CI compatible library, and couchdb.php (!), a configuration file.

Proposed layout
===============

application
application/libraries/couch.php
application/libraries/couchClient.php
application/libraries/couchDocument.php
application/libraries/couchReplicator.php
application/libraries/couchdb.php
application/config/couchdb.php

How does it work
================

The couchdb class extends the couchClient class. Basically, it :
- includes the four PHP on Couch files
- overload the couchClient constructor to read CouchDB server data source name and database name from a config file, instead of getting it through constructor parameters.

How to use it
=============

- copy the PHP on Couch classes *couch.php*, *couchClient.php*, *couchDocument.php* and *couchReplicator.php* inside the **libraries** folder of your CodeIgniter application.
- copy the *libraries/couchdb.php* file inside the **libraries** folder of your CodeIgniter application.
- copy the *config/couchdb.php* file inside the **config** folder of your application
- edit the *config/couchdb.php* file to set the two configuration options *couch_dsn* and *couch_database* :


    $config['couch_dsn'] = "http://localhost:5984/";
    $config['couch_database'] = "my_wonderful_db";


- if you want the couchdb object to be autoloaded, edit the *config/autoload.php* file of your CodeIgniter application and add "couchdb" to the list of libraries to autoload


    $autoload['libraries'] = array('couchdb');


- use it !



    // somewhere in the controller or model
    $doc = $this->couchdb->getDoc("my_first_doc");
    ...





