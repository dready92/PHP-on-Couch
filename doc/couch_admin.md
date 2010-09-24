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
    

Creating a couchAdmin instance
==============================

The couchAdmin class constructor takes an only parameter : a couchClient object. You have to be careful, the couchClient object should have enough credentials to perform the administrative tasks.

Example :

    // create a couchClient instance
    $client = new couchClient("http://localhost:5984/","mydb");
    // now create the couchAdmin instance
    $adm = new couchAdmin($client);
    // here $adm will connect to CouchDB without any credentials : that will only work if there is no administrator created yet on the server.


First time configuration of CouchDB
-----------------------------------

On a fresh install, CouchDB is in **admin party** mode : that means any operation (create / delete databases, store documents and design documents) can be performed without any authentication.

Below is an example to configure the first server administrator, that we will name **couchAdmin** with the password **secretpass** :

    // create an anonymous couchClient connection (no user/pass)
    $client = new couchClient("http://localhost:5984/","mydb");
    // now create the couchAdmin instance
    $adm = new couchAdmin($client);
    //create the server administrator
    try {
        $adm->createAdmin("couchAdmin","secretpass");
    } catch ( Exception $e ) {
        die ("Can't create server administrator : ".$e->getMessage());
    }

Now that the couch server got a server administrator, it's not in "admin party" mode anymore : we can't create a second server administrator using the same, anonymous couchClient instance.
We need to create a couchClient instance with the credentials of **couchAdmin**.

    // create a server administrator couchClient connection
    $client = new couchClient("http://couchAdmin:secretpass@localhost:5984/","mydb");
    // now create the couchAdmin instance
    $adm = new couchAdmin($client);



Creating / getting users
========================

Creating a server administrator
-------------------------------

The method **createAdmin ($login, $password, $roles = array() )** creates a CouchDB *server* administrator. A server administrator can do everything on a CouchDB server.

Example :

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    // Create an admin user
    try {
        $adm->createAdmin("superAdmin","ommfgwtf");
    } catch ( Exception $e ) {
        die("unable to create admin user: ".$e->getMessage());
    }


Creating a normal user
----------------------

The method **createUser ($login, $password, $roles = array())** creates a CouchDB user.

Example :

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    // Create a user
    try {
        $adm->createUser("joe","dalton");
    } catch ( Exception $e ) {
        die("unable to create user: ".$e->getMessage());
    }


Example - creating a user and adding it to some roles

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    $roles = array ("thief","jailbreaker");
    
    try {
        $adm->createUser("jack","dalton",$roles);
    } catch ( Exception $e ) {
        die("unable to create user: ".$e->getMessage());
    }


Getting a user document
-----------------------

The method **getUser ( $login )** returns the user document stored in the users database of the CouchDB server.

Example :

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    // get a user
    try {
        $joe = $adm->getUser("joe");
    } catch ( Exception $e ) {
        if ( $e->getCode() == 404 ) {
            echo "User joe does not exist.";
        } else {
            die("unable to get user: ".$e->getMessage());
        }
    }


Getting all users documents
---------------------------

The method **getAllUsers ()** returns the list of all users registered in the users database of the CouchDB server. This method calls a view, so you can use the view query options !

Example :

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    // get all users
    try {
        $all = $adm->getAllUsers();
    } catch ( Exception $e ) {
        die("unable to get users: ".$e->getMessage());
    }
    print_r($all);
    
    /** will print something like 
    Array (
        stdClass (
            "id" => "_design/_auth",
            "key" => "_design/_auth",
            "value" => stdClass (
                            "rev" => "1-54a591939c91922a35efee07eb2c3a72"
                      )
        ),
        stdClass (
            "id" => "org.couchdb.user:jack",
            "key" => "org.couchdb.user:jack",
            "value" => stdClass (
                             "rev" => "1-3e4dd4a7c5a9d422f8379f059fcfce98"
                       )
        ),
        stdClass (
            "id" => "org.couchdb.user:joe",
            "key" => "org.couchdb.user:joe",
            "value" => stdClass (
                             "rev" => "1-9456a56f060799567ec4560fccf34534"
                       )
        )
    )
    **/

Example - including user documents and not showing the design documents

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $all = $adm->include_docs(true)->startkey("org.couchdb.user:")->getAllUsers();
    } catch ( Exception $e ) {
        die("unable to get users: ".$e->getMessage());
    }
    print_r($all);
    
    /** will print something like 
    Array (
        stdClass (
            "id" => "org.couchdb.user:jack",
            "key" => "org.couchdb.user:jack",
            "value" => stdClass (
                             "rev" => "1-3e4dd4a7c5a9d422f8379f059fcfce98"
                       ),
            "doc" => stdClass ( "_id" => "org.couchdb.user:jack", ... )
        ),
        stdClass (
            "id" => "org.couchdb.user:joe",
            "key" => "org.couchdb.user:joe",
            "value" => stdClass (
                             "rev" => "1-9456a56f060799567ec4560fccf34534"
                       ),
            "doc" => stdClass ( "_id" => "org.couchdb.user:joe", ... )
        )
    )
    **/


Removing users
==============

Warning : this only works with CouchDB starting at version 1.0.1

Removing a server administrator
-------------------------------

The method **deleteAdmin($login)** permanently removes the admin $login.

Example : creating and immediately removing a server administrator

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    $adminLogin = "butterfly";
    $adminPass = "wing";
    try {
        $ok = $adm->createAdmin($adminLogin, $adminPass);
    } catch (Exception $e) {
        die("unable to create admin user: ".$e->getMessage());
    }
    // here "butterfly" admin exists and can login to couchDB to manage the server

    // now we remove it
    try {
        $ok = $adm->deleteAdmin($adminLogin);
    } catch (Exception $e) {
        die("unable to delete admin user: ".$e->getMessage());
    }
    // here "butterfly" admin does not exist anymore

Note : the response of deleteAdmin() method is a string : it's the hash of the password this admin had before been removed. Example : -hashed-0c796d26c439bec7445663c2c2a18933858a8fbb,f3ada55b560c7ca77e5a5cdf61d40e1a

Removing a user
---------------

The method **deleteUser($login)** permanently removes the user $login.

Example : removing a server user

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $ok = $adm->deleteUser("joe");
    } catch (Exception $e) {
        die("unable to delete user: ".$e->getMessage());
    }
    print_r($ok);

    /** will print something like :
    stdClass Object
    (
        [ok] => 1
        [id] => org.couchdb.user:joe
        [rev] => 6-415784680cff486e2d0144ed39da2431
    )
    */



Assigning roles to users
========================

Assigning a role to a user
-----------------------

The method **addRoleToUser($user, $role)** adds the role *$role* to the list of roles user *$user* belongs to. **$user** can be a PHP stdClass representing a CouchDB user object (as returned by getUser() method), or a user login.

Example : adding the role *cowboy* to user *joe*

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $adm->addRoleToUser("joe","cowboy");
    } catch ( Exception $e ) {
        die("unable to add a role to user: ".$e->getMessage());
    }
    echo "Joe now got role cowboy";


Removing a role from the list of roles a user belongs to
--------------------------------------------------------

The method **removeRoleFromUser($user, $role)** removes the role *$role* from the list of roles user *$user* belongs to. **$user** can be a PHP stdClass representing a CouchDB user object (as returned by getUser() method), or a user login.

Example : removing the role *cowboy* of user *joe*

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $adm->removeRoleFromUser("joe","cowboy");
    } catch ( Exception $e ) {
        die("unable to remove a role of a user: ".$e->getMessage());
    }
    echo "Joe don't belongs to the cowboy role anymore";



Assigning users to databases
============================

CouchDB databases got two types of privileged users : the *readers*, that can read all documents, and only write normal (non-design) documents.
The *admins* got all privileges of the *readers*, and they also can write design documents, use temporary views, add and remove *readers* and *admins* of the database.
[The CouchDB wiki gives all details regarding rights management.](http://wiki.apache.org/couchdb/Security_Features_Overview)


Adding a user to the "readers"
------------------------------

The method **addDatabaseReaderUser ($login)** adds a user in the readers list of the database.

Example - adding joe to the readers of the database mydb

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $adm->addDatabaseReaderUser("joe");
    } catch ( Exception $e ) {
        die("unable to add user: ".$e->getMessage());
    }


Adding a user to the "admins"
------------------------------

The method **addDatabaseAdminUser ($login)** adds a user in the admins list of the database.

Example - adding joe to the admins of the database mydb

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $adm->addDatabaseAdminUser("joe");
    } catch ( Exception $e ) {
        die("unable to add user: ".$e->getMessage());
    }


Getting the list of "readers" of the database
---------------------------------------------

The method **getDatabaseReaderUsers ()** returns the list of users belonging to the *readers* of the database.

Example - getting all users beeing *readers* of the database mydb

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $users = $adm->getDatabaseReaderUsers();
    } catch ( Exception $e ) {
        die("unable to list users: ".$e->getMessage());
    }
    print_r($users);
    // will echo something like: Array ( "joe" , "jack" )


Getting the list of "admins" of the database
---------------------------------------------

The method **getDatabaseAdminUsers ()** returns the list of users belonging to the *admins* of the database.

Example - getting all users beeing *admins* of the database mydb

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $users = $adm->getDatabaseAdminUsers();
    } catch ( Exception $e ) {
        die("unable to list users: ".$e->getMessage());
    }
    print_r($users);
    // will echo something like: Array ( "william" )


Removing a user from the "readers"
------------------------------

The method **removeDatabaseReaderUser ($login)** removes a user from the readers list of the database.

Example - removing joe from the readers of the database mydb

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $adm->removeDatabaseReaderUser("joe");
    } catch ( Exception $e ) {
        die("unable to remove user: ".$e->getMessage());
    }


Removing a user from the "admins"
------------------------------

The method **removeDatabaseAdminUser ($login)** removes a user from the admins list of the database.

Example - removing joe from the admins of the database mydb

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $adm->removeDatabaseAdminUser("joe");
    } catch ( Exception $e ) {
        die("unable to remove user: ".$e->getMessage());
    }



Assigning roles to databases
============================

Just like users, roles can be assigned as admins or readers in a CouchDB database.
[The CouchDB wiki gives all details regarding rights management.](http://wiki.apache.org/couchdb/Security_Features_Overview)


Adding a role to the "readers"
------------------------------

The method **addDatabaseReaderrole ($role)** adds a role in the readers list of the database.

Example - adding cowboy to the readers of the database mydb

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $adm->addDatabaseReaderRole("cowboy");
    } catch ( Exception $e ) {
        die("unable to add role: ".$e->getMessage());
    }


Adding a role to the "admins"
------------------------------

The method **addDatabaseAdminRole ($role)** adds a role in the admins list of the database.

Example - adding *cowboy* role to the *admins* of the database mydb

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $adm->addDatabaseAdminrole("cowboy");
    } catch ( Exception $e ) {
        die("unable to add role: ".$e->getMessage());
    }


Getting the list of "readers" of the database
---------------------------------------------

The method **getDatabaseReaderRoles ()** returns the list of roles belonging to the *readers* of the database.

Example - getting all roles beeing *readers* of the database mydb

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $roles = $adm->getDatabaseReaderRoles();
    } catch ( Exception $e ) {
        die("unable to list roles: ".$e->getMessage());
    }
    print_r($roles);
    // will echo something like: Array ( "cowboy" , "indians" )


Getting the list of "admins" of the database
---------------------------------------------

The method **getDatabaseAdminRoles ()** returns the list of roles belonging to the *admins* of the database.

Example - getting all roles beeing *admins* of the database mydb

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $roles = $adm->getDatabaseAdminRoles();
    } catch ( Exception $e ) {
        die("unable to list roles: ".$e->getMessage());
    }
    print_r($roles);
    // will echo something like: Array ( "martians" )


Removing a role from the "readers"
------------------------------

The method **removeDatabaseReaderRole ($role)** removes a role from the readers list of the database.

Example - removing *cowboy* from the *readers* of the database mydb

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $adm->removeDatabaseReaderRole("cowboy");
    } catch ( Exception $e ) {
        die("unable to remove role: ".$e->getMessage());
    }


Removing a role from the "admins"
------------------------------

The method **removeDatabaseAdminRole ($role)** removes a role from the admins list of the database.

Example - removing *martians* from the admins of the database mydb

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $adm->removeDatabaseAdminRole("martians");
    } catch ( Exception $e ) {
        die("unable to remove role: ".$e->getMessage());
    }




Accessing the database security object
======================================

Each Couch database got a security object. The security object is made like :

    {
        "admins" : {
            "names" : ["joe", "phil"],
            "roles" : ["boss"]
        },
        "readers" : {
            "names" : ["dave"],
            "roles" : ["producer", "consumer"]
        }
    }

PHP on Couch provides methods to directly get and set the security object.


Getting the security object
---------------------------

The method **getSecurity ()** returns the security object of a CouchDB database.

Example - getting the security object of the database mydb

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $security = $adm->getSecurity();
    } catch ( Exception $e ) {
        die("unable to get security object: ".$e->getMessage());
    }


Setting the security object
---------------------------

The method **setSecurity($security)** set the security object of a Couch database

Example - setting the security object of the database mydb

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client);
    
    try {
        $adm->setSecurity($security);
    } catch ( Exception $e ) {
        die("unable to set security object: ".$e->getMessage());
    }

Setting the name of the CouchDB users database
==============================================

CouchDB got a special database used to store users. By default this database is called **_users**, but this can be changed.


Setting the users database name on couchAdmin creation
------------------------------------------------------

To create a couchAdmin instance and specify the name of the users database, use the constructor second parameter $options, setting the option **users_database**:

Example - setting the couchdb users database name on couchAdmin object creation

    <?PHP
    require_once "lib/couch.php";
    require_once "lib/couchClient.php";
    require_once "lib/couchAdmin.php";
    $client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new couchAdmin($client, array ("users_database"=> "theUsers") );
    
Changing the users database name of an existing couchAdmin instance
-------------------------------------------------------------------

The **setUsersDatabase($name)** method allows to specify an alternate name for the users database on an already created couchAdmin instance.

Getting the users database name currently set in an existing couchAdmin instance
--------------------------------------------------------------------------------

The **getUsersDatabase($name)** method return the name that is used actually to connect to the users database.




