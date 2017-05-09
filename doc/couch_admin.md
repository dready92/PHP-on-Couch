This section give details about the CouchAdmin object.

## Table of content

- [Please read this first](#please-read-this-first-)
- [Managing CouchDB users](#managing-couchdb-users)
- [Synopsys](#synopsys)
- [Getting started](#getting-started)
- [Admin party](#admin-party)
- [Create users and admins](#create-users-and-admins)
    + [createAdmin($login, $password, $roles = array())](#createadminlogin-password-roles--array)
    + [createUser($login, $password, $roles = array())](#createuserlogin-password-roles--array)
    + [getUser($login)](#getuserlogin)
    + [getAllUsers()](#getallusers)
- [Removing users](#removing-users)
    + [deleteAdmin ($login)](#deleteadminlogin)
    + [deleteUser($login)](#deleteuserlogin)
- [Roles assignation](#roles-assignation)
    + [addRoleToUser($user, $role)](#addroletouseruser-role)
    + [removeRoleFromUser($user, $role)](#removerolefromuseruser-role)
    + [setRolesToUser($user,array $roles = [])](#setrolestouseruser-array-roles--)
- [Database user security](#database-user-security)
    + [addDatabaseMemberUser($login)](#adddatabasememberuserlogin)
    + [addDatabaseAdminUser($login)](#adddatabaseadminuserlogin)
    + [getDatabaseMemberUsers()](#getdatabasememberusers)
    + [getDatabaseAdminUsers()](#getdatabaseadminusers)
    + [removeDatabaseMemberUser($login)](#removedatabasememberuserlogin)
    + [removeDatabaseAdminUser($login)](#removedatabaseadminuserlogin)
- [Database roles security](#database-roles-security)
    + [addDatabaseMemberRole($role)](#adddatabasememberrolerole)
    + [addDatabaseAdminRole($role)](#adddatabaseadminrolerole)
    + [getDatabaseMemberRoles()](#getdatabasememberroles)
    + [getDatabaseAdminRoles()](#getdatabaseadminroles)
    + [removeDatabaseMemberRole($role)](#removedatabasememberrolerole)
    + [removeDatabaseAdminRole($role)](#removedatabaseadminrole-role)
- [Accessing Database security object](#accessing-database-security-object)
    + [getSecurity()](#getsecurity)
    + [setSecurity($security)](#setsecuritysecurity)
- [Database options](#database-options)
    + [CouchAdmin users_database](#couchadmin-users_database)
    + [setUserDatabase($name)](#setuserdatabasename)
    + [getUserDatabase($name)](#getuserdatabasename)

## Please read this first !!


The CouchAdmin class is only needed to **manage** users of a CouchDB server : add users, add admins, ...

You don't need the couchAdmin class to connect to CouchDB with a login / password. You only need to add your login and password to the DSN argument when creating your CouchDB client :

```php
$client = new CouchClient ("http://theuser:secretpass@couch.server.com:5984","mydatabase");
```

##Managing CouchDB users

CouchDB rights management is really complex. [This page](http://wiki.apache.org/couchdb/Security_Features_Overview) can really help to understand how security is implemented in couchDB.

The **CouchAdmin** class contains helpful methods to create admins, users, and associate users to databases.

## Synopsys

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
// Here my couchDB is in "admin party" mode (no user, no admin defined)
//
// I create an "anonymous" connector to the database
$client = new CouchClient ("http://localhost:5984/","mydb" );
// I then create an instance of the couchAdmin class, passing the couchClient as a parameter
$anonymous_adm = new CouchAdmin($client);

// I create the first admin user
try {
    $anonymous_adm->createAdmin("superAdmin","secretpass");
} catch ( Exception $e ) {
    die("unable to create admin user: ".$e->getMessage());
}

//
// now my database is not in "admin party" anymore : to continue Administration I need to setup an authenticated connector
//
$admclient = new CouchClient ("http://superAdmin:secretpass@localhost:5984/", "mydb" );
$adm = new CouchAdmin($admclient);

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

// and add it to the members group of database "mydb"
try {
    $adm->addDatabaseMemberUser("joe");
} catch ( Exception $e ) {
    die("unable to add joe to the members list of mydb: ".$e->getMessage());
}

// well... get the list of users belonging to the "members" group of "mydb"
$users = $adm->getDatabaseMemberUsers();  // array ( "joe" )
```

## Getting started

**__construct(CouchClient $client,$options = array())**
The couchAdmin class constructor takes 2 parameters : a couchClient object and an array of configuration options.

*$client* :  You have to be careful, the couchClient object should have enough credentials to perform the administrative tasks.

*$options*: This array has 2 possibles keys for the moments. 
- users_database : The user database to use (overwrite the default _users)
- node : The node to use for the configuration. **If it's not defined**, the first node of the *cluster_nodes* will be taken. 



Example :

```php
// create a CouchClient instance
$client = new CouchClient("http://localhost:5984/","mydb");
// now create the CouchAdmin instance
$adm = new CouchAdmin($client);
// here $adm will connect to CouchDB without any credentials : that will only work if there is no administrator created yet on the server.
```

## Admin party

On a fresh install, CouchDB is in **admin party** mode : that means any operation (create / delete databases, store documents and design documents) can be performed without any authentication.

Below is an example to configure the first server administrator, that we will name **couchAdmin** with the password **secretpass** :

```php
// create an anonymous couchClient connection (no user/pass)
$client = new CouchClient("http://localhost:5984/","mydb");
// now create the couchAdmin instance
$adm = new CouchAdmin($client);
//create the server administrator
try {
    $adm->createAdmin("couchAdmin","secretpass");
} catch ( Exception $e ) {
    die ("Can't create server administrator : ".$e->getMessage());
}
```

Now that the couch server got a server administrator, it's not in "admin party" mode anymore : we can't create a second server administrator using the same, anonymous couchClient instance.
We need to create a couchClient instance with the credentials of **couchAdmin**.

```php
// create a server administrator couchClient connection
$client = new CouchClient("http://couchAdmin:secretpass@localhost:5984/","mydb");
// now create the CouchAdmin instance
$adm = new CouchAdmin($client);
```

## Create users and admins

### createAdmin($login, $password, $roles = array())

The method **createAdmin ($login, $password, $roles = array())** creates a CouchDB *server* administrator. A server administrator can do everything on a CouchDB server.

Example :

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

// Create an admin user
try {
    $adm->createAdmin("superAdmin","ommfgwtf");
} catch ( Exception $e ) {
    die("unable to create admin user: ".$e->getMessage());
}
```

### createUser($login, $password, $roles = array())


The method **createUser($login, $password, $roles = array())** creates a CouchDB user and returns it.

Example :

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

// Create a user
try {
    $adm->createUser("joe","dalton");
} catch ( Exception $e ) {
    die("unable to create user: ".$e->getMessage());
}
```

Example - creating a user and adding it to some roles

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

$roles = array ("thief","jailbreaker");

try {
    $adm->createUser("jack","dalton",$roles);
} catch ( Exception $e ) {
    die("unable to create user: ".$e->getMessage());
}
```

### getUser($login)

The method **getUser($login)** returns the user document stored in the users database of the CouchDB server.

Example :

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

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
```

### getAllUsers() 

The method **getAllUsers()** returns the list of all users registered in the users database of the CouchDB server. This method calls a view, so you can use the view query options !

Example :

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

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
```

Example - including user documents and not showing the design documents

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

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
```

## Removing users

Warning : this only works with CouchDB starting at version 1.0.1

### deleteAdmin($login)

The method **deleteAdmin($login)** permanently removes the admin $login.

Example : creating and immediately removing a server administrator

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

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
```

Note : the response of deleteAdmin() method is a string : it's the hash of the password this admin had before been removed. Example : -hashed-0c796d26c439bec7445663c2c2a18933858a8fbb,f3ada55b560c7ca77e5a5cdf61d40e1a

### deleteUser($login)

The method **deleteUser($login)** permanently removes the user $login.

Example : removing a server user

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
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
```


## Roles assignation

### addRoleToUser($user, $role)

The method **addRoleToUser($user, $role)** adds the role *$role* to the list of roles user *$user* belongs to. **$user** can be a PHP stdClass representing a CouchDB user object (as returned by getUser() method), or a user login.

Example : adding the role *cowboy* to user *joe*

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $adm->addRoleToUser("joe","cowboy");
} catch ( Exception $e ) {
    die("unable to add a role to user: ".$e->getMessage());
}
echo "Joe now got role cowboy";
```

### removeRoleFromUser($user, $role)

The method **removeRoleFromUser($user, $role)** removes the role *$role* from the list of roles user *$user* belongs to. **$user** can be a PHP stdClass representing a CouchDB user object (as returned by getUser() method), or a user login.

Example : removing the role *cowboy* of user *joe*

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $adm->removeRoleFromUser("joe","cowboy");
} catch ( Exception $e ) {
    die("unable to remove a role of a user: ".$e->getMessage());
}
echo "Joe don't belongs to the cowboy role anymore";
```

### setRolesToUser($user, array $roles = [])

This method let you set the roles for the selected user. A $user can either be the username of the user or a user object containing an **_id** and a **roles** property. 

Example of usage : 

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $adm->setRolesForUser("joe",['tester','developer']);
    echo "Joe has now the tester and developer roles.";
} catch ( Exception $e ) {
    die("unable to remove a role of a user: ".$e->getMessage());
}
```


## Database user security

CouchDB databases got two types of privileged users : the *members*, that can read all documents, and only write normal (non-design) documents.
The *admins* got all privileges of the *members*, and they also can write design documents, use temporary views, add and remove *members* and *admins* of the database.
[The CouchDB wiki gives all details regarding rights management.](http://wiki.apache.org/couchdb/Security_Features_Overview)


### addDatabaseMemberUser($login)

The method **addDatabaseMemberUser($login)** adds a user in the members list of the database.

Example - adding joe to the members of the database mydb

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $adm->addDatabaseMemberUser("joe");
} catch ( Exception $e ) {
    die("unable to add user: ".$e->getMessage());
}
```

### addDatabaseAdminUser($login)

The method **addDatabaseAdminUser($login)** adds a user in the admins list of the database.

Example - adding joe to the admins of the database mydb

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $adm->addDatabaseAdminUser("joe");
} catch ( Exception $e ) {
    die("unable to add user: ".$e->getMessage());
}
```

### getDatabaseMemberUsers()

The method **getDatabaseMemberUsers()** returns the list of users belonging to the *members* of the database.

Example - getting all users beeing *members* of the database mydb

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $users = $adm->getDatabaseMemberUsers();
} catch ( Exception $e ) {
    die("unable to list users: ".$e->getMessage());
}
print_r($users);
// will echo something like: Array ( "joe" , "jack" )
```

### getDatabaseAdminUsers()

The method **getDatabaseAdminUsers()** returns the list of users belonging to the *admins* of the database.

Example - getting all users beeing *admins* of the database mydb

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $users = $adm->getDatabaseAdminUsers();
} catch ( Exception $e ) {
    die("unable to list users: ".$e->getMessage());
}
print_r($users);
// will echo something like: Array ( "william" )
```

### removeDatabaseMemberUser($login)

The method **removeDatabaseMemberUser($login)** removes a user from the members list of the database.

Example - removing joe from the members of the database mydb

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $adm->removeDatabaseMemberUser("joe");
} catch ( Exception $e ) {
    die("unable to remove user: ".$e->getMessage());
}
```

### removeDatabaseAdminUser($login)

The method **removeDatabaseAdminUser($login)** removes a user from the admins list of the database.

Example - removing joe from the admins of the database mydb

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $adm->removeDatabaseAdminUser("joe");
} catch ( Exception $e ) {
    die("unable to remove user: ".$e->getMessage());
}
```


## Database roles security

Just like users, roles can be assigned as admins or members in a CouchDB database.
[The CouchDB wiki gives all details regarding rights management.](http://wiki.apache.org/couchdb/Security_Features_Overview)


### addDatabaseMemberRole($role)

The method **addDatabaseMemberrole($role)** adds a role in the members list of the database.

Example - adding cowboy to the members of the database mydb

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $adm->addDatabaseMemberRole("cowboy");
} catch ( Exception $e ) {
    die("unable to add role: ".$e->getMessage());
}
```

### addDatabaseAdminRole($role)

The method **addDatabaseAdminRole($role)** adds a role in the admins list of the database.

Example - adding *cowboy* role to the *admins* of the database mydb

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $adm->addDatabaseAdminrole("cowboy");
} catch ( Exception $e ) {
    die("unable to add role: ".$e->getMessage());
}
```

### getDatabaseMemberRoles()

The method **getDatabaseMemberRoles()** returns the list of roles belonging to the *members* of the database.

Example - getting all roles beeing *members* of the database mydb

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $roles = $adm->getDatabaseMemberRoles();
} catch ( Exception $e ) {
    die("unable to list roles: ".$e->getMessage());
}
print_r($roles);
// will echo something like: Array ( "cowboy" , "indians" )
```

### getDatabaseAdminRoles()

The method **getDatabaseAdminRoles()** returns the list of roles belonging to the *admins* of the database.

Example - getting all roles beeing *admins* of the database mydb

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $roles = $adm->getDatabaseAdminRoles();
} catch ( Exception $e ) {
    die("unable to list roles: ".$e->getMessage());
}
print_r($roles);
// will echo something like: Array ( "martians" )
```

### removeDatabaseMemberRole($role)

The method **removeDatabaseMemberRole($role)** removes a role from the members list of the database.

Example - removing *cowboy* from the *members* of the database mydb

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $adm->removeDatabaseMemberRole("cowboy");
} catch ( Exception $e ) {
    die("unable to remove role: ".$e->getMessage());
}
```

### removeDatabaseAdminRole($role)

The method **removeDatabaseAdminRole($role)** removes a role from the admins list of the database.

Example - removing *martians* from the admins of the database mydb

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new couchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $adm->removeDatabaseAdminRole("martians");
} catch ( Exception $e ) {
    die("unable to remove role: ".$e->getMessage());
}
```



## Accessing Database security object

Each Couch database got a security object. The security object is made like :

```json
{
    "admins" : {
        "names" : ["joe", "phil"],
        "roles" : ["boss"]
    },
    "members" : {
        "names" : ["dave"],
        "roles" : ["producer", "consumer"]
    }
}
```

PHP on Couch provides methods to directly get and set the security object.


### getSecurity()

The method **getSecurity()** returns the security object of a CouchDB database.

Example - getting the security object of the database mydb

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new couchAdmin($client);

try {
    $security = $adm->getSecurity();
} catch ( Exception $e ) {
    die("unable to get security object: ".$e->getMessage());
}
```

### setSecurity($security)

The method **setSecurity($security)** set the security object of a Couch database

Example - setting the security object of the database mydb

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client);

try {
    $adm->setSecurity($security);
} catch ( Exception $e ) {
    die("unable to set security object: ".$e->getMessage());
}
```

## Database options

CouchDB got a special database used to store users. By default this database is called **_users**, but this can be changed.


### CouchAdmin users_database

To create a couchAdmin instance and specify the name of the users database, use the constructor second parameter $options, setting the option **users_database**:

Example - setting the couchdb users database name on couchAdmin object creation

```php
<?php
use PHPOnCouch\Couch,
    PHPOnCouch\CouchClient,
    PHPOnCouch\CouchAdmin;
$client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
$adm = new CouchAdmin($client, array ("users_database"=> "theUsers") );
```

### setUserDatabase($name)

The **setUsersDatabase($name)** method allows to specify an alternate name for the users database on an already created couchAdmin instance.


### getUserDatabase($name)

The **getUsersDatabase($name)** method return the name that is used actually to connect to the users database.




