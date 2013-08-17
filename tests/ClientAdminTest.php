<?php

use PHPOnCouch\Couch;
use PHPOnCouch\Admin;
use PHPOnCouch\Client;
use PHPOnCouch\Document;
use PHPOnCouch\Replicator;
use \stdClass;

class ClientAdminTest extends \PHPUnit_Framework_TestCase
{

    const DATABASE_NAME = "test_database976277263";

    const ADMIN_LOGIN = "adm";
    const ADMIN_PASS  = "sometest";

    private $couch_server = "http://localhost:5984/";
    private $aclient = null;
    private $client  = null;

    /**
     * The database and user's are destroyed are recreated for each test.
     * PHPunit does not guarantee the order in which the tests are executed.
     */
    public function setUp()
    {

        $this->client = new Client($this->couch_server, self::DATABASE_NAME);

        try {
            # In case something went bad during previous test
            $this->client->deleteDatabase();
        } catch (Exception $e) {}

        $this->client->createDatabase();

        $adm = new Admin($this->client);
        $adm->createAdmin(self::ADMIN_LOGIN, self::ADMIN_PASS);
        $this->aclient = new Client("http://".self::ADMIN_LOGIN.":".self::ADMIN_PASS."@localhost:5984/", self::DATABASE_NAME);

    }

    public function tearDown()
    {
        $this->aclient = new Client("http://".self::ADMIN_LOGIN.":".self::ADMIN_PASS."@localhost:5984/", self::DATABASE_NAME);
        $this->aclient->deleteDatabase();
        $adm = new Admin($this->aclient);
        $adm->deleteAdmin(self::ADMIN_LOGIN);

    }

    public function isAdminCreated()
    {
        $this->assertInstanceOf('Admin', $this->aclient);
    }

    public function testAdminCanAdmin()
    {

        $this->aclient->useDatabase('idontexist9903044');

        try {
            $this->aclient->deleteDatabase();
        } catch (Exception $e) {}

        $ok = $this->aclient->createDatabase();

        $this->assertInternalType('object', $ok);
        $this->assertObjectHasAttribute("ok", $ok);
        $this->assertEquals($ok->ok,true);

        $ok = $this->aclient->deleteDatabase();

        $this->assertInternalType('object', $ok);
        $this->assertObjectHasAttribute("ok", $ok);
        $this->assertEquals($ok->ok, true);

    }

    public function testUserCreateAccount()
    {

        $adm = new Admin($this->aclient);
        $ok  = $adm->createUser("joe", "dalton");

        $this->assertInternalType('object', $ok);
        $this->assertObjectHasAttribute("ok", $ok);
        $this->assertTrue($ok->ok);

    }

    public function testAllUsers()
    {

        $adm = new Admin($this->aclient);
        $ok  = $adm->getAllUsers(true);

        $this->assertInternalType('array', $ok);
        $this->assertEquals(count($ok), 2);

    }

    public function testGetUser ()
    {

        $adm = new Admin($this->aclient);
        $ok  = $adm->getUser("joe");

        $this->assertInternalType('object', $ok);
        $this->assertObjectHasAttribute("_id",$ok);

    }

    public function testUserAccountWithRole()
    {

        $roles = array("badboys", "jailbreakers");
        $adm   = new Admin($this->aclient);
        $ok    = $adm->createUser("jack", "dalton", $roles);

        $this->assertInternalType('object', $ok);
        $this->assertObjectHasAttribute("ok",$ok);
        $this->assertEquals($ok->ok,true);

        $user = $adm->getUser("jack");

        $this->assertInternalType('object', $user);
        $this->assertObjectHasAttribute("_id", $user);
        $this->assertObjectHasAttribute("roles", $user);
        $this->assertInternalType('array', $user->roles);
        $this->assertEquals(count($user->roles), 2);

        foreach ( $user->roles as $role ) {
            $this->assertEquals(in_array($role, $roles),true);
        }

        $user = $adm->deleteUser("jack");

        $this->assertInternalType('object', $ok);
        $this->assertObjectHasAttribute("ok", $ok);
        $this->assertTrue($ok->ok);

    }

    public function testGetSecurity()
    {

        $adm = new Admin($this->aclient);
        $security = $adm->getSecurity();

        $this->assertObjectHasAttribute("admins", $security);
        $this->assertObjectHasAttribute("readers",$security);
        $this->assertObjectHasAttribute("names",  $security->admins);
        $this->assertObjectHasAttribute("roles",  $security->admins);
        $this->assertObjectHasAttribute("names",  $security->readers);
        $this->assertObjectHasAttribute("roles",  $security->readers);

    }

    public function testSetSecurity()
    {

        $adm = new Admin($this->aclient);
        $security = $adm->getSecurity();
        $security->admins->names[]  = "joe";
        $security->readers->names[] = "jack";
        $ok = $adm->setSecurity($security);

        $this->assertInternalType('object', $ok);
        $this->assertObjectHasAttribute("ok", $ok);
        $this->assertEquals($ok->ok, true);

        $security = $adm->getSecurity();

        $this->assertEquals(count($security->readers->names), 1);
        $this->assertEquals(reset($security->readers->names), "jack");
        $this->assertEquals(count($security->admins->names), 1);
        $this->assertEquals(reset($security->admins->names), "joe");

    }

    public function testDatabaseAdminUser()
    {

        $adm      = new Admin($this->aclient);
        $ok       = $adm->removeDatabaseAdminUser("joe");
        $security = $adm->getSecurity();

        $this->assertInternalType('bool', $ok);
        $this->assertEquals(count($security->admins->names), 0);

        $ok       = $adm->addDatabaseAdminUser("joe");
        $security = $adm->getSecurity();

        $this->assertInternalType('bool', $ok);
        $this->assertEquals($ok, true);
        $this->assertEquals(count($security->admins->names), 1);
        $this->assertEquals(reset($security->admins->names), "joe");

    }

    public function testDatabaseReaderUser()
    {

        $adm = new Admin($this->aclient);
        $ok = $adm->removeDatabaseReaderUser("jack");
        $this->assertInternalType('bool', $ok);
        $this->assertEquals($ok, true);
        $security = $adm->getSecurity();
        $this->assertEquals(count($security->readers->names),0);

        $ok = $adm->addDatabaseReaderUser("jack");
        $this->assertInternalType('bool', $ok);
        $this->assertEquals($ok, true);
        $security = $adm->getSecurity();
        $this->assertEquals(count($security->readers->names),1);
        $this->assertEquals(reset($security->readers->names),"jack");

    }

    public function testGetDatabaseAdminUsers()
    {

        $adm = new Admin($this->aclient);
        $ok  = $adm->addDatabaseAdminUser("joe");

        $users = $adm->getDatabaseAdminUsers();
        $this->assertInternalType('array', $users);
        $this->assertEquals(1, count($users));
        $this->assertEquals("joe", reset($users));

    }

    public function testGetDatabaseReaderUsers()
    {

        $adm = new Admin($this->aclient);
        $ok  = $adm->addDatabaseReaderUser("jack");

        $users = $adm->getDatabaseReaderUsers();
        $this->assertInternalType('array', $users);
        $this->assertEquals(1, count($users));
        $this->assertEquals("jack", reset($users));

    }

    public function testDatabaseAdminRole()
    {

        $adm      = new Admin($this->aclient);
        $security = $adm->getSecurity();
        $ok       = $adm->addDatabaseAdminRole("cowboy");

        $this->assertEquals(count($security->admins->roles),0);
        $this->assertInternalType('bool', $ok);
        $this->assertEquals($ok,true);

        $security = $adm->getSecurity();

        $this->assertEquals(count($security->admins->roles),1);
        $this->assertEquals(reset($security->admins->roles),"cowboy");

        $ok = $adm->removeDatabaseAdminRole("cowboy");

        $this->assertInternalType('bool', $ok);
        $this->assertEquals($ok, true);

        $security = $adm->getSecurity();

        $this->assertEquals(count($security->admins->roles),0);

    }

    public function testDatabaseReaderRole()
    {

        $adm      = new Admin($this->aclient);
        $security = $adm->getSecurity();

        $this->assertEquals(count($security->readers->roles), 0);

        $ok = $adm->addDatabaseReaderRole("cowboy");

        $this->assertInternalType('bool', $ok);
        $this->assertEquals($ok,true);

        $security = $adm->getSecurity();

        $this->assertEquals(count($security->readers->roles), 1);
        $this->assertEquals(reset($security->readers->roles), "cowboy");

        $ok = $adm->removeDatabaseReaderRole("cowboy");

        $this->assertInternalType('bool', $ok);
        $this->assertEquals($ok,true);

        $security = $adm->getSecurity();

        $this->assertEquals(count($security->readers->roles), 0);

    }

    public function testGetDatabaseAdminRoles()
    {

        $adm = new Admin($this->aclient);
        $users = $adm->getDatabaseAdminRoles();

        $this->assertInternalType('array', $users);
        $this->assertEquals(0, count($users));

    }

    public function testGetDatabaseReaderRoles()
    {

        $adm = new Admin($this->aclient);
        $users = $adm->getDatabaseReaderRoles();

        $this->assertInternalType('array', $users);
        $this->assertEquals(0, count($users));

    }

    public function testUserRoles()
    {

        $commom_tests = function($user) {

          $this->assertInternalType('object', $user);
          $this->assertObjectHasAttribute("_id", $user);
          $this->assertObjectHasAttribute("roles", $user);
          $this->assertInternalType('array', $user->roles);

        };

        $adm  = new Admin($this->aclient);
        $user = $adm->getUser("joe");

        $commom_tests($user);
        $this->assertEquals(0, count($user->roles));

        $adm->addRoleToUser($user,"cowboy");
        $user = $adm->getUser("joe");

        $commom_tests($user);
        $this->assertEquals(1,count($user->roles));
        $this->assertEquals("cowboy",reset($user->roles));

        $adm->addRoleToUser("joe","trainstopper");
        $user = $adm->getUser("joe");

        $commom_tests($user);
        $this->assertEquals(2,count($user->roles));
        $this->assertEquals("cowboy",reset($user->roles));
        $this->assertEquals("trainstopper",end($user->roles));

        $adm->removeRoleFromUser($user,"cowboy");
        $user = $adm->getUser("joe");

        $commom_tests($user);
        $this->assertEquals(1, count($user->roles));
        $this->assertEquals("trainstopper",reset($user->roles));

        $adm->removeRoleFromUser("joe","trainstopper");
        $user = $adm->getUser("joe");

        $commom_tests($user);
        $this->assertEquals(0,count($user->roles));

    }

    public function testDeleteUser()
    {

        $adm = new Admin($this->aclient);

        $ok = $adm->deleteUser("joe");
        $this->assertInternalType('object', $ok);
        $this->assertObjectHasAttribute("ok", $ok);
        $this->assertEquals($ok->ok, true);

        $ok = $adm->getAllUsers(true);

        $this->assertInternalType('array', $ok);
        $this->assertEquals(count($ok), 1);

    }

    public function testUsersDatabaseName()
    {

        $adm = new Admin($this->aclient, array("users_database"=> "test"));
        $this->assertEquals("test", $adm->getUsersDatabase());

        $adm = new Admin($this->aclient);
        $this->assertEquals("_users", $adm->getUsersDatabase());

        $adm->setUsersDatabase("test");
        $this->assertEquals("test", $adm->getUsersDatabase());

    }

}
