CouchAdmin class
****************

Please read this first !!
=========================

The CouchAdmin class is only needed to **manage** users of a CouchDB server : add users, add admins, ...

You don't need the CouchAdmin class to connect to CouchDB with a login / password. You only need to add your login and password to the DSN argument when creating your CouchDB client :

.. code-block:: php

    $client = new CouchClient ("http://theuser:secretpass@couch.server.com:5984","mydatabase");


Managing CouchDB users
======================

CouchDB rights management is really complex. `This page <http://wiki.apache.org/couchdb/Security_Features_Overview/>`_ can really help to understand how security is implemented in couchDB.

The **CouchAdmin** class contains helpful methods to create admins, users, and associate users to databases.

Synopsys
========

.. code-block:: php

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
    // now my database is not in "admin party" anymore.
    // To continue Administration I need to setup an authenticated connector
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


Getting started
===============

.. php:namespace:: PHPOnCouch

.. php:class:: CouchAdmin

    The class that helps managing permissions, users and admins.

:hidden:`__construct`
"""""""""""""""""""""

    .. php:method:: __construct(CouchClient $client,$options = array())

        The CouchAdmin class constructor takes 2 parameters : a couchClient object and an array of configuration options.

        :params CouchClient $client: The CouchClient instance created with enough permissions to perform the administrative tasks.
        :params array $options: The options that can be passed to the CouchInstance and CouchAdmin. Here are the specific options for CouchAdmin :

            - users_database : The user database to use (overwrite the default _users)
            - node : The node to use for the configuration. **If it's not defined**, the first node of the *cluster_nodes* will be taken.

        Example :

        .. code-block:: php

            // create a CouchClient instance
            $client = new CouchClient("http://localhost:5984/","mydb");
            // now create the CouchAdmin instance
            $adm = new CouchAdmin($client);
            // here $adm will connect to CouchDB without any credentials : that will only work if there is no administrator created yet on the server.

Admin party
===========

On a fresh install, CouchDB is in **admin party** mode : that means any operation (create / delete databases, store documents and design documents) can be performed without any authentication.

Below is an example to configure the first server administrator, that we will name **couchAdmin** with the password **secretpass** :

.. code-block:: php

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

Now that the couch server got a server administrator, it's not in "admin party" mode anymore : we can't create a second server administrator using the same, anonymous couchClient instance.
We need to create a couchClient instance with the credentials of **couchAdmin**.

..  code-block:: php

    // create a server administrator couchClient connection
    $client = new CouchClient("http://couchAdmin:secretpass@localhost:5984/","mydb");
    // now create the CouchAdmin instance
    $adm = new CouchAdmin($client);

Create users and admins
=======================


:hidden:`createAdmin`
"""""""""""""""""""""

    .. php:method:: createAdmin($login, $password, $roles = array())

        Creates a CouchDB *server* administrator. A server administrator can do everything on a CouchDB server.

        :params string $login: The login of the new admin
        :params string $password: The raw password for the new admin.
        :params array $roles: The roles that will have this admin.

        Example :

        .. code-block:: php

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

:hidden:`createUser`
""""""""""""""""""""

    .. php:method:: createUser($login, $password, $roles = array())

        Creates a CouchDB user and returns it.

        :params string $login: The login of the new user
        :params string $password: The raw password for the new user.
        :params array $roles: The roles that will have this user.

        Example :

        .. code-block:: php

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

        Example with roles

        .. code-block:: php

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

:hidden:`getUser`
"""""""""""""""""

    .. php:method:: getUser($login)

        The method returns the user document stored in the users database of the CouchDB server.

        :params string $login: The username of the user to find.
        :returns: The user if found. Otherwise, a CouchNotFoundException will be thrown.

        Example :

        .. code-block:: php

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

:hidden:`getAllUsers`
"""""""""""""""""""""

    .. php:method:: getAllUsers()

        The method returns the list of all users registered in the users database of the CouchDB server.

        .. note:: This method calls a view, so you can use the view query options !

        :returns: An array of users found in the database.

        Example :

        .. code-block:: php

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

        Example - including user documents and not showing the design documents

        .. code-block:: php

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

Removing users
==============

.. warning:: This only works with CouchDB starting at version 1.0.1

:hidden:`deleteAdmin`
"""""""""""""""""""""

    .. php:method:: deleteAdmin($login)

        This permanently removes the admin $login.

        :params string $login: The username of the admin to delete.
        :returns string:

            Returns the hash of the password before it got removed.

            Example : -hashed-0c796d26c439bec7445663c2c2a18933858a8fbb,f3ada55b560c7ca77e5a5cdf61d40e1a

        Example : creating and immediately removing a server administrator

        .. code-block:: php

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

:hidden:`deleteUser`
""""""""""""""""""""

    .. php:method:: deleteUser($login)

        This method permanently removes the user $login.

        :params string $login: The login of the user to delete.

        Example : removing a server user

        .. code-block:: php

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

Roles assignation
=================

:hidden:`addRoleToUser`
"""""""""""""""""""""""

    .. php:method:: addRoleToUser($user, $role)

        This method adds the role *$role* to the list of roles user *$user* belongs to. **$user** can be a PHP stdClass representing a CouchDB user object (as returned by getUser() method), or a user login.

        :params string|stdClass $user: The username of the user to edit or the User object returned by :meth:`CouchAdmin::getUser()` for example.
        :params string $role: The role to add to the specified user.

        Example : adding the role *cowboy* to user *joe*

        .. code-block:: php

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

:hidden:`removeRoleFromUser`
""""""""""""""""""""""""""""

    .. php:method:: removeRoleFromUser($user, $role)

        This method removes the role *$role* from the list of roles user *$user* belongs to. **$user** can be a PHP stdClass representing a CouchDB user object (as returned by getUser() method), or a user login.

        :params string|stdClass $user: The username of the user to edit or the User object returned by :meth:`CouchAdmin::getUser()` for example.
        :params string $role: The role to remove to the specified user.

        Example : removing the role *cowboy* of user *joe*

        .. code-block:: php

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

:hidden:`setRolesToUser`
""""""""""""""""""""""""

    .. php:method:: setRolesToUser($user, array $roles = [])

        This method let you set the roles for the selected user. A $user can either be the username of the user or a user object containing an **_id** and a **roles** property.

        Example of usage :

        .. code-block:: php

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

Database user security
======================

CouchDB databases got two types of privileged users : the *members*, that can read all documents, and only write normal (non-design) documents.
The *admins* got all privileges of the *members*, and they also can write design documents, use temporary views, add and remove *members* and *admins* of the database.
`The CouchDB wiki gives all details regarding rights management. <http://wiki.apache.org/couchdb/Security_Features_Overview/>`_


:hidden:`addDatabaseMemberUser`
"""""""""""""""""""""""""""""""

    .. php:method:: addDatabaseMemberUser($login)

        This method adds a user in the members list of the database.

        :params string $login: The user to add to the member list of the current database

        Example - adding joe to the members of the database mydb

        .. code-block:: php

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

:hidden:`addDatabaseAdminUser`
""""""""""""""""""""""""""""""

    .. php:method:: addDatabaseAdminUser($login)

        Adds a user in the admins list of the database.

         :params string $login: The user to add to the admin list of the current database

        Example - adding joe to the admins of the database mydb

        .. code-block:: php

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

:hidden:`getDatabaseMemberUsers`
""""""""""""""""""""""""""""""""

    .. php:method:: getDatabaseMemberUsers()

        Returns the list of users belonging to the *members* of the database.

        :returns: An array of usernames that belong to the member list of this database.

        Example - getting all users beeing *members* of the database mydb

        .. code-block:: php

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

:hidden:`getDatabaseAdminUsers`
"""""""""""""""""""""""""""""""

    .. php:method:: getDatabaseAdminUsers()

        Returns the list of users belonging to the *admins* of the database.

        :returns: An array of usernames that belong to the admin list of this database.

        Example - getting all users beeing *admins* of the database mydb

        .. code-block:: php

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

:hidden:`removeDatabaseMemberUser`
""""""""""""""""""""""""""""""""""

    .. php:method:: removeDatabaseMemberUser($login)

        Removes a user from the members list of the database.

        :params string $login: Remove the database username from the database member list.

        Example - removing joe from the members of the database mydb

        .. code-block:: php

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

:hidden:`removeDatabaseAdminUser`
"""""""""""""""""""""""""""""""""

    .. php:method:: removeDatabaseAdminUser($login)

        Removes a user from the admins list of the database.

        :params string $login: Remove the database username from the database admin list.

        Example - removing joe from the admins of the database mydb

        .. code-block:: php

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

Database roles security
=======================

Just like users, roles can be assigned as admins or members in a CouchDB database.
`The CouchDB wiki gives all details regarding rights management. <http://wiki.apache.org/couchdb/Security_Features_Overview/>`_


:hidden:`addDatabaseMemberRole`
"""""""""""""""""""""""""""""""

    .. php:method:: addDatabaseMemberRole($role)

        Adds a role in the members list of the database.

        :params string $role: The role to add to the member role list of the current database.

        Example - adding cowboy to the members of the database mydb

        .. code-block:: php

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

:hidden:`addDatabaseAdminRole`
""""""""""""""""""""""""""""""

    .. php:method:: addDatabaseAdminRole($role)

        Adds a role in the admins list of the database.

        :params string $role: The role to add to the admin role list of the current database.

        Example - adding *cowboy* role to the *admins* of the database mydb

        .. code-block:: php

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

:hidden:`getDatabaseMemberRoles`
""""""""""""""""""""""""""""""""

    .. php:method:: getDatabaseMemberRoles()

        Returns the list of roles belonging to the *members* of the database.

        :returns: An array of roles belonging to the member section of the current database.

        Example - getting all roles beeing *members* of the database mydb

        .. code-block:: php

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

:hidden:`getDatabaseAdminRoles`
"""""""""""""""""""""""""""""""

    .. php:method:: getDatabaseAdminRoles()

        Returns the list of roles belonging to the *admins* of the database.

        :returns: An array of roles belonging to the admin section of the current database.

        Example - getting all roles beeing *admins* of the database mydb

        .. code-block:: php

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

:hidden:`removeDatabaseMemberRole`
""""""""""""""""""""""""""""""""""

    .. php:method:: removeDatabaseMemberRole($role)

        Removes a role from the members list of the database.

        :params string $role: The role to remove from the database member role list.

        Example - removing *cowboy* from the *members* of the database mydb

        .. code-block:: php

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

:hidden:`removeDatabaseAdminRole`
"""""""""""""""""""""""""""""""""

    .. php:method:: removeDatabaseAdminRole($role)

        Removes a role from the admins list of the database.

        :params string $role: The role to remove from the database admin role list.

        Example - removing *martians* from the admins of the database mydb

        .. code-block:: php

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

Accessing Database security object
==================================

Each Couch database got a security object. The security object is made like :

.. code-block:: json

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


PHP on Couch provides methods to directly get and set the security object.

:hidden:`getSecurity`
"""""""""""""""""""""

    .. php:method:: getSecurity()

        :returns: Returns the security object of a CouchDB database.

        Example - getting the security object of the database mydb

        .. code-block:: php

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

:hidden:`setSecurity`
"""""""""""""""""""""

    .. php:method:: setSecurity($security)

        Set the security object of a Couch database

        :params stdClass $security: The security object to set to the current database.

        Example - setting the security object of the database mydb

        .. code-block:: php

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

:hidden:`setUserDatabase`
"""""""""""""""""""""""""

    .. php:method:: setUserDatabase($name)

        Set an alternate name for the users database on an already created couchAdmin instance.

        :params string $name: The name of the custom database to us to store users.

:hidden:`getUserDatabase`
"""""""""""""""""""""""""

    .. php:method:: getUserDatabase($name)

        :returns: Return the name that is used actually to connect to the users database.

Database options
================

CouchDB got a special database used to store users. By default this database is called **_users**, but this can be changed.


CouchAdmin users_database
"""""""""""""""""""""""""

To create a CouchAdmin instance and specify the name of the users database, use the constructor second parameter $options, setting the option **users_database**:

Example - setting the couchdb users database name on couchAdmin object creation

.. code-block:: php

    <?php
    use PHPOnCouch\Couch,
        PHPOnCouch\CouchClient,
        PHPOnCouch\CouchAdmin;
    $client = new CouchClient ("http://couchAdmin:secretpass@localhost:5984/","mydb" );
    $adm = new CouchAdmin($client, array ("users_database"=> "theUsers") );


You can also manipulate directly the CouchAdmin with the following methods : :meth:`CouchAdmin::setUserDatabase` and :meth:`CouchAdmin::getUserDatabase`.






