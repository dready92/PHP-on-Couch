<?php

// error_reporting(E_STRICT);
error_reporting(E_ALL);

use PHPOnCouch\CouchClient,
	PHPOnCouch\CouchDocument,
	PHPOnCouch\CouchAdmin,
	PHPOnCouch\Exceptions,
	PHPOnCouch\Exceptions\CouchException;

require_once join(DIRECTORY_SEPARATOR, [__DIR__, '_config', 'config.php']);

class couchClientAdminTest extends PHPUnit_Framework_TestCase
{

	private $host = 'localhost';
	private $port = '5984';
	private $admin = array("login" => "adm", "password" => "sometest");
	private $aclient = null;

	public function setUp()
	{
		$config = config::getInstance();
		$url = $config->getUrl($this->host, $this->port, null);
		$aUrl = $config->getUrl($this->host, $this->port, $config->getFirstAdmin());
		$this->client = new CouchClient($url, 'couchclienttest');
		$this->aclient = new CouchClient($aUrl, 'couchclienttest');
		try {
			$this->aclient->deleteDatabase();
		} catch (Exception $e) {
			
		}
		$this->aclient->createDatabase();
	}

	public function tearDown()
	{
		$this->client = null;
		$this->aclient = null;
	}

	public function testFirstAdmin()
	{
		$adm = new CouchAdmin($this->aclient);
		$adm->createAdmin($this->admin["login"], $this->admin["password"]);
	}

	public function testAdminIsSet()
	{
//		//$this->setExpectedException('couchException');
//		$code = 0;
//		try {
//			$this->client->createDatabase("test");
//		} catch (Exception $e) {
//			$code = $e->getCode();
//		}
//		$this->assertEquals(302, $code);
//// 		print_r($code);
		$this->expectException(CouchException::class);
		$this->expectExceptionCode('412');
//		$this->setExpectedException('PHPOnCouch\Exceptions\couchException', '', 412);
		$this->aclient->createDatabase();
	}

	public function testAdminCanAdmin()
	{
		$this->aclient->deleteDatabase();

		$ok = $this->aclient->createDatabase();
		$this->assertInternalType("object", $ok);
		$this->assertObjectHasAttribute("ok", $ok);
		$this->assertEquals($ok->ok, true);
		$ok = $this->aclient->deleteDatabase();
		$this->assertInternalType("object", $ok);
		$this->assertObjectHasAttribute("ok", $ok);
		$this->assertEquals($ok->ok, true);
// 		print_r($ok);
	}

	public function testUserAccount()
	{
		$adm = new CouchAdmin($this->aclient);
		$ok = $adm->createUser("joe", "dalton");
		$this->assertInternalType("object", $ok);
		$this->assertObjectHasAttribute("ok", $ok);
		$this->assertEquals($ok->ok, true);
// 		$ok = $adm->deleteUser("joe");
// 		print_r($ok);
	}

	public function testAllUsers()
	{
		$adm = new CouchAdmin($this->aclient);
		$ok = $adm->getAllUsers(true);
		$this->assertInternalType("array", $ok);
		$this->assertEquals(count($ok), 2);
	}

	public function testGetUser()
	{
		$adm = new CouchAdmin($this->aclient);
		$ok = $adm->getUser("joe");
		$this->assertInternalType("object", $ok);
		$this->assertObjectHasAttribute("_id", $ok);
	}

	public function testUserAccountWithRole()
	{
		$roles = array("badboys", "jailbreakers");
		$adm = new CouchAdmin($this->aclient);
		$ok = $adm->createUser("jack", "dalton", $roles);
		$this->assertInternalType("object", $ok);
		$this->assertObjectHasAttribute("ok", $ok);
		$this->assertEquals($ok->ok, true);
		$user = $adm->getUser("jack");
		$this->assertInternalType("object", $user);
		$this->assertObjectHasAttribute("_id", $user);
		$this->assertObjectHasAttribute("roles", $user);
		$this->assertInternalType("array", $user->roles);
		$this->assertEquals(count($user->roles), 2);
		foreach ($user->roles as $role) {
			$this->assertEquals(in_array($role, $roles), true);
		}
	}

	public function testGetSecurity()
	{
//		$this->aclient->createDatabase();
		$adm = new CouchAdmin($this->aclient);
		$security = $adm->getSecurity();
		$this->assertObjectHasAttribute("admins", $security);
		$this->assertObjectHasAttribute("readers", $security);
		$this->assertObjectHasAttribute("names", $security->admins);
		$this->assertObjectHasAttribute("roles", $security->admins);
		$this->assertObjectHasAttribute("names", $security->readers);
		$this->assertObjectHasAttribute("roles", $security->readers);
// 		print_r($security);
	}

	public function testSetSecurity()
	{
// 		$this->aclient->createDatabase();
		$adm = new CouchAdmin($this->aclient);
		$security = $adm->getSecurity();
		$security->admins->names[] = "joe";
		$security->readers->names[] = "jack";
		$ok = $adm->setSecurity($security);
		$this->assertInternalType("object", $ok);
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
		$adm = new CouchAdmin($this->aclient);
		$ok = $adm->removeDatabaseAdminUser("joe");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok, true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->names), 0);
		$ok = $adm->addDatabaseAdminUser("joe");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok, true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->names), 1);
		$this->assertEquals(reset($security->admins->names), "joe");
	}

	/**
	 * @depends testDatabaseAdminUser
	 */
	public function testDatabaseReaderUser()
	{
		$adm = new CouchAdmin($this->aclient);
		$ok = $adm->removeDatabaseReaderUser("jack");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok, true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->names), 0);
		$ok = $adm->addDatabaseReaderUser("jack");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok, true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->names), 1);
		$this->assertEquals(reset($security->readers->names), "jack");
	}

	/**
	 * @depends testDatabaseReaderUser()
	 * @covers PHPOnCouch\couchAdmin::getDatabaseAdminUsers
	 */
	public function testGetDatabaseAdminUsers()
	{
		$adm = new CouchAdmin($this->aclient);
		$users = $adm->getDatabaseAdminUsers();
		$this->assertInternalType("array", $users);
		$this->assertEquals(1, count($users));
		$this->assertEquals("joe", reset($users));
	}
	
	/**
	 *  @depends testGetDatabaseAdminUsers()
	 */
	public function testGetDatabaseReaderUsers()
	{
		$adm = new CouchAdmin($this->aclient);
		$users = $adm->getDatabaseReaderUsers();
		$this->assertInternalType("array", $users);
		$this->assertEquals(1, count($users));
		$this->assertEquals("jack", reset($users));
	}

// roles

	public function testDatabaseAdminRole()
	{
		$adm = new CouchAdmin($this->aclient);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->roles), 0);
		$ok = $adm->addDatabaseAdminRole("cowboy");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok, true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->roles), 1);
		$this->assertEquals(reset($security->admins->roles), "cowboy");
		$ok = $adm->removeDatabaseAdminRole("cowboy");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok, true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->roles), 0);
	}

	public function testDatabaseReaderRole()
	{
		$adm = new CouchAdmin($this->aclient);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->roles), 0);
		$ok = $adm->addDatabaseReaderRole("cowboy");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok, true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->roles), 1);
		$this->assertEquals(reset($security->readers->roles), "cowboy");
		$ok = $adm->removeDatabaseReaderRole("cowboy");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok, true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->roles), 0);
	}

	/**
	 * @depends testDatabaseAdminRole
	 * @covers PHPOnCouch\couchAdmin::getDatabaseAdminRoles()
	 */
	public function testGetDatabaseAdminRoles()
	{
		$adm = new CouchAdmin($this->aclient);
		$users = $adm->getDatabaseAdminRoles();
		$this->assertInternalType("array", $users);
		$this->assertEquals(0, count($users));
// 		$this->assertEquals("joe",reset($users));
	}

	/**
	 * @depends testDatabaseReaderRole
	 * @covers PHPOnCouch\couchAdmin::getDatabaseReaderRoles()
	 */
	public function testGetDatabaseReaderRoles()
	{
		$adm = new CouchAdmin($this->aclient);
		$users = $adm->getDatabaseReaderRoles();
		$this->assertInternalType("array", $users);
		$this->assertEquals(0, count($users));
// 		$this->assertEquals("jack",reset($users));
	}

// /roles



	public function testUserRoles()
	{
		$adm = new CouchAdmin($this->aclient);
		$user = $adm->getUser("joe");
		$this->assertInternalType("object", $user);
		$this->assertObjectHasAttribute("_id", $user);
		$this->assertObjectHasAttribute("roles", $user);
		$this->assertInternalType("array", $user->roles);
		$this->assertEquals(0, count($user->roles));
		$adm->addRoleToUser($user, "cowboy");
		$user = $adm->getUser("joe");
		$this->assertInternalType("object", $user);
		$this->assertObjectHasAttribute("_id", $user);
		$this->assertObjectHasAttribute("roles", $user);
		$this->assertInternalType("array", $user->roles);
		$this->assertEquals(1, count($user->roles));
		$this->assertEquals("cowboy", reset($user->roles));
		$adm->addRoleToUser("joe", "trainstopper");
		$user = $adm->getUser("joe");
		$this->assertInternalType("object", $user);
		$this->assertObjectHasAttribute("_id", $user);
		$this->assertObjectHasAttribute("roles", $user);
		$this->assertInternalType("array", $user->roles);
		$this->assertEquals(2, count($user->roles));
		$this->assertEquals("cowboy", reset($user->roles));
		$this->assertEquals("trainstopper", end($user->roles));
		$adm->removeRoleFromUser($user, "cowboy");
		$user = $adm->getUser("joe");
		$this->assertInternalType("object", $user);
		$this->assertObjectHasAttribute("_id", $user);
		$this->assertObjectHasAttribute("roles", $user);
		$this->assertInternalType("array", $user->roles);
		$this->assertEquals(1, count($user->roles));
		$this->assertEquals("trainstopper", reset($user->roles));
		$adm->removeRoleFromUser("joe", "trainstopper");
		$user = $adm->getUser("joe");
		$this->assertInternalType("object", $user);
		$this->assertObjectHasAttribute("_id", $user);
		$this->assertObjectHasAttribute("roles", $user);
		$this->assertInternalType("array", $user->roles);
		$this->assertEquals(0, count($user->roles));
	}

	public function testDeleteUser()
	{
		$adm = new CouchAdmin($this->aclient);
		$ok = $adm->deleteUser("joe");
		$this->assertInternalType("object", $ok);
		$this->assertObjectHasAttribute("ok", $ok);
		$this->assertEquals($ok->ok, true);
		$ok = $adm->getAllUsers(true);
		$this->assertInternalType("array", $ok);
		$this->assertEquals(count($ok), 2);
	}

	public function testDeleteAdmin()
	{
		$adm = new CouchAdmin($this->aclient);
		$adm->createAdmin("secondAdmin", "password");
		$adm->deleteAdmin("secondAdmin");
		$adm->createAdmin("secondAdmin", "password");
	}

	public function testUsersDatabaseName()
	{
		$adm = new CouchAdmin($this->aclient, array("users_database" => "test"));
		$this->assertEquals("test", $adm->getUsersDatabase());
		$adm = new CouchAdmin($this->aclient);
		$this->assertEquals("_users", $adm->getUsersDatabase());
		$adm->setUsersDatabase("test");
		$this->assertEquals("test", $adm->getUsersDatabase());
	}

}
