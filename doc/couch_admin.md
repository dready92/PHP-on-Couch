This section give details about the couchAdmin object.

Please Read this first !!
=========================

The couchAdmin class is only needed to **manage** users of a CouchDB server : add users, add admins, ...

You don't need the couchAdmin class to connect to CouchDB with a login / password. You only need to add your login and password to the DSN argument when creating your CouchDB client :

    $client = new couchClient ("http://theuser:secretpass@couch.server.com:5984","mydatabase");


Managing CouchDB users
======================

CouchDB rights management is really complex. [This page](http://wiki.apache.org/couchdb/Security_Features_Overview) can really help to understand how security is implemented in couchDB.

The **couchAdmin** class contains helpful methods to create admins, users, and associate users to databases.

Synopsys
--------

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    // Here my couchDB is in "admin party" mode (no user, no admin defined)
    //
    // I create an "anonymous" connector to the database
    $client = new couchClient ("http://localhost:5984/","mydb" );
    // I then create an instance of the couchAdmin class, passing the couchClient as a parameter
    $anonymous_adm = new couchAdmin($client);
    
    // I create the first admin user
    try {
        $anonymous_adm->createAdmin("superAdmin","secretpass");
    } catch ( Exception $e ) {
        die("unable to create admin user: ".$e->getMessage());
    }
    
    //
    // now my database is not in "admin party" anymore : to continue Administration I need to setup an authenticated connector
    //
    $admclient = new couchClient ("http://superAdmin:secretpass@localhost:5984/", "mydb" );
    $adm = new couchAdmin($admclient);
    
    // create a regular (no superadmin) user)
    try {
        $adm->createUser("joe","secret");
    } catch ( Exception $e ) {
        die("unable to create regular user: ".$e->getMessage());
    }
    
    // set "joe" as admin of the database "mydb"
    try {
        $adm->addDatabaseAdminUser("joe");
    } catch ( Exception $e ) {
        die("unable to add joe to the admins list of mydb: ".$e->getMessage());
    }
    
    // Oh no I missed up remove "joe" from database "mydb" admins
    try {
        $adm->removeDatabaseAdminUser("joe");
    } catch ( Exception $e ) {
        die("unable to remove joe from the admins list of mydb: ".$e->getMessage());
    }
    
    // and add it to the readers group of database "mydb"
    try {
        $adm->addDatabaseReaderUser("joe");
    } catch ( Exception $e ) {
        die("unable to add joe to the readers list of mydb: ".$e->getMessage());
    }
    
    // well... get the list of users belonging to the "readers" group of "mydb"
    $users = $adm->getDatabaseReaderUsers();  // array ( "joe" )
    

