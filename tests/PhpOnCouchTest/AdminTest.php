<?php

// error_reporting(E_STRICT);
error_reporting(E_ALL);

class PhpOnCouchTest_AdminTest extends PHPUnit_Framework_TestCase
{

	private $couch_server = null;
	private $client = null;
	private $aclient = null;

	public function __construct()
	{
	    $this->couch_server = "http://";
	    if ( COUCH_TEST_SERVER_USERNAME != null ) {
	        $this->couch_server .= COUCH_TEST_SERVER_USERNAME;
	        if ( COUCH_TEST_SERVER_PASSWORD != null ) $this->$couch_server .= ':' . COUCH_TEST_SERVER_HOSTNAME;
	    }
	    $this->couch_server .= COUCH_TEST_SERVER_HOST . '/';
	}

    public function setUp()
    {
        $this->client = new PhpOnCouch_Client($this->couch_server,COUCH_TEST_SERVER_DATABASE_CLIENT_TEST);
		$this->aclient = new PhpOnCouch_Client(sprint("http://%s:%s@%s",
		                                        COUCH_ADMIN_TEST_SERVER_USERNAME,
		                                        COUCH_ADMIN_TEST_SERVER_PASSWORD,
		                                        COUCH_ADMIN_TEST_SERVER_HOSTNAME),
                                         COUCH_TEST_SERVER_DATABASE_CLIENT_TEST);
    }

	public function tearDown()
    {
        $this->client = null;
		$this->aclient = null;
    }


	public function testFirstAdmin () {
		$adm = new PhpOnCouch_Admin(COUCH_ADMIN_TEST_SERVER_HOSTNAME);
		$adm->createAdmin(COUCH_ADMIN_TEST_SERVER_USERNAME,COUCH_ADMIN_TEST_SERVER_PASSWORD);
	}

	public function testAdminIsSet () {
		//$this->setExpectedException('couchException');
		$code = 0;
		try { $this->client->createDatabase("test"); } 
		catch ( Exception $e ) { $code = $e->getCode(); }
		$this->assertEquals(302,$code);
// 		print_r($code);
	}

	public function testAdminCanAdmin () {
		$ok = $this->aclient->createDatabase();
		$this->assertType("object", $ok);
		$this->assertObjectHasAttribute("ok",$ok);
		$this->assertEquals($ok->ok,true);
		$ok = $this->aclient->deleteDatabase();
		$this->assertType("object", $ok);
		$this->assertObjectHasAttribute("ok",$ok);
		$this->assertEquals($ok->ok,true);
// 		print_r($ok);
	}

	public function testUserAccount () {
		$adm = new PhpOnCouch_Admin($this->aclient);
		$ok = $adm->createUser("joe","dalton");
		$this->assertType("object", $ok);
		$this->assertObjectHasAttribute("ok",$ok);
		$this->assertEquals($ok->ok,true);
// 		$ok = $adm->deleteUser("joe");
// 		print_r($ok);
	}

	public function testAllUsers () {
		$adm = new PhpOnCouch_Admin($this->aclient);
		$ok = $adm->getAllUsers(true);
		$this->assertType("array", $ok);
		$this->assertEquals(count($ok),2);
	}

	public function testGetUser () {
		$adm = new PhpOnCouch_Admin($this->aclient);
		$ok = $adm->getUser("joe");
		$this->assertType("object", $ok);
		$this->assertObjectHasAttribute("_id",$ok);
	}

	public function testUserAccountWithRole () {
		$roles = array("badboys","jailbreakers");
		$adm = new PhpOnCouch_Admin($this->aclient);
		$ok = $adm->createUser("jack","dalton",$roles);
		$this->assertType("object", $ok);
		$this->assertObjectHasAttribute("ok",$ok);
		$this->assertEquals($ok->ok,true);
		$user = $adm->getUser("jack");
		$this->assertType("object", $user);
		$this->assertObjectHasAttribute("_id",$user);
		$this->assertObjectHasAttribute("roles",$user);
		$this->assertType("array", $user->roles);
		$this->assertEquals(count($user->roles),2);
		foreach ( $user->roles as $role ) {
			$this->assertEquals(in_array($role,$roles),true);
		}
	}

	public function testGetSecurity () {
		$this->aclient->createDatabase();
		$adm = new PhpOnCouch_Admin($this->aclient);
		$security = $adm->getSecurity();
		$this->assertObjectHasAttribute("admins",$security);
		$this->assertObjectHasAttribute("readers",$security);
		$this->assertObjectHasAttribute("names",$security->admins);
		$this->assertObjectHasAttribute("roles",$security->admins);
		$this->assertObjectHasAttribute("names",$security->readers);
		$this->assertObjectHasAttribute("roles",$security->readers);
// 		print_r($security);
	}

	public function testSetSecurity () {
// 		$this->aclient->createDatabase();
		$adm = new PhpOnCouch_Admin($this->aclient);
		$security = $adm->getSecurity();
		$security->admins->names[] = "joe";
		$security->readers->names[] = "jack";
		$ok = $adm->setSecurity($security);
		$this->assertType("object", $ok);
		$this->assertObjectHasAttribute("ok",$ok);
		$this->assertEquals($ok->ok,true);

		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->names),1);
		$this->assertEquals(reset($security->readers->names),"jack");
		$this->assertEquals(count($security->admins->names),1);
		$this->assertEquals(reset($security->admins->names),"joe");
	}

	public function testDatabaseAdminUser () {
		$adm = new PhpOnCouch_Admin($this->aclient);
		$ok = $adm->removeDatabaseAdminUser("joe");
		$this->assertType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->names),0);
		$ok = $adm->addDatabaseAdminUser("joe");
		$this->assertType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->names),1);
		$this->assertEquals(reset($security->admins->names),"joe");
	}

	public function testDatabaseReaderUser () {
		$adm = new PhpOnCouch_Admin($this->aclient);
		$ok = $adm->removeDatabaseReaderUser("jack");
		$this->assertType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->names),0);
		$ok = $adm->addDatabaseReaderUser("jack");
		$this->assertType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->names),1);
		$this->assertEquals(reset($security->readers->names),"jack");
	}

	public function testGetDatabaseAdminUsers () {
		$adm = new PhpOnCouch_Admin($this->aclient);
		$users = $adm->getDatabaseAdminUsers();
		$this->assertType("array", $users);
		$this->assertEquals(1,count($users));
		$this->assertEquals("joe",reset($users));
	}

	public function testGetDatabaseReaderUsers () {
		$adm = new PhpOnCouch_Admin($this->aclient);
		$users = $adm->getDatabaseReaderUsers();
		$this->assertType("array", $users);
		$this->assertEquals(1,count($users));
		$this->assertEquals("jack",reset($users));
	}

// roles

	public function testDatabaseAdminRole () {
		$adm = new PhpOnCouch_Admin($this->aclient);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->roles),0);
		$ok = $adm->addDatabaseAdminRole("cowboy");
		$this->assertType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->roles),1);
		$this->assertEquals(reset($security->admins->roles),"cowboy");
		$ok = $adm->removeDatabaseAdminRole("cowboy");
		$this->assertType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->roles),0);
	}

	public function testDatabaseReaderRole () {
		$adm = new PhpOnCouch_Admin($this->aclient);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->roles),0);
		$ok = $adm->addDatabaseReaderRole("cowboy");
		$this->assertType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->roles),1);
		$this->assertEquals(reset($security->readers->roles),"cowboy");
		$ok = $adm->removeDatabaseReaderRole("cowboy");
		$this->assertType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->roles),0);
	}

	public function testGetDatabaseAdminRoles () {
		$adm = new PhpOnCouch_Admin($this->aclient);
		$users = $adm->getDatabaseAdminRoles();
		$this->assertType("array", $users);
		$this->assertEquals(0,count($users));
// 		$this->assertEquals("joe",reset($users));
	}

	public function testGetDatabaseReaderRoles () {
		$adm = new PhpOnCouch_Admin($this->aclient);
		$users = $adm->getDatabaseReaderRoles();
		$this->assertType("array", $users);
		$this->assertEquals(0,count($users));
// 		$this->assertEquals("jack",reset($users));
	}

// /roles



	public function testUserRoles () {
		$adm = new PhpOnCouch_Admin($this->aclient);
		$user = $adm->getUser("joe");
		$this->assertType("object", $user);
		$this->assertObjectHasAttribute("_id",$user);
		$this->assertObjectHasAttribute("roles",$user);
		$this->assertType("array", $user->roles);
		$this->assertEquals(0,count($user->roles));
		$adm->addRoleToUser($user,"cowboy");
		$user = $adm->getUser("joe");
		$this->assertType("object", $user);
		$this->assertObjectHasAttribute("_id",$user);
		$this->assertObjectHasAttribute("roles",$user);
		$this->assertType("array", $user->roles);
		$this->assertEquals(1,count($user->roles));
		$this->assertEquals("cowboy",reset($user->roles));
		$adm->addRoleToUser("joe","trainstopper");
		$user = $adm->getUser("joe");
		$this->assertType("object", $user);
		$this->assertObjectHasAttribute("_id",$user);
		$this->assertObjectHasAttribute("roles",$user);
		$this->assertType("array", $user->roles);
		$this->assertEquals(2,count($user->roles));
		$this->assertEquals("cowboy",reset($user->roles));
		$this->assertEquals("trainstopper",end($user->roles));
		$adm->removeRoleFromUser($user,"cowboy");
		$user = $adm->getUser("joe");
		$this->assertType("object", $user);
		$this->assertObjectHasAttribute("_id",$user);
		$this->assertObjectHasAttribute("roles",$user);
		$this->assertType("array", $user->roles);
		$this->assertEquals(1,count($user->roles));
		$this->assertEquals("trainstopper",reset($user->roles));
		$adm->removeRoleFromUser("joe","trainstopper");
		$user = $adm->getUser("joe");
		$this->assertType("object", $user);
		$this->assertObjectHasAttribute("_id",$user);
		$this->assertObjectHasAttribute("roles",$user);
		$this->assertType("array", $user->roles);
		$this->assertEquals(0,count($user->roles));
	}



	public function testDeleteUser() {
		$adm = new PhpOnCouch_Admin($this->aclient);
		$ok = $adm->deleteUser("joe");
		$this->assertType("object", $ok);
		$this->assertObjectHasAttribute("ok",$ok);
		$this->assertEquals($ok->ok,true);
		$ok = $adm->getAllUsers(true);
		$this->assertType("array", $ok);
		$this->assertEquals(count($ok),2);
	}

	public function testDeleteAdmin() {
		$adm = new PhpOnCouch_Admin($this->aclient);
		$adm->createAdmin("secondAdmin","password");
		$adm->deleteAdmin("secondAdmin");
		$adm->createAdmin("secondAdmin","password");
	}

	public function testUsersDatabaseName () {
		$adm = new PhpOnCouch_Admin($this->aclient,array("users_database"=>"test"));
		$this->assertEquals("test",$adm->getUsersDatabase());
		$adm = new PhpOnCouch_Admin($this->aclient);
		$this->assertEquals("_users",$adm->getUsersDatabase());
		$adm->setUsersDatabase("test");
		$this->assertEquals("test",$adm->getUsersDatabase());
	}


}
