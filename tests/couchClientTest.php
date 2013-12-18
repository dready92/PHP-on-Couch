<?php

// error_reporting(E_STRICT);
error_reporting(E_ALL);


class couchClientTest extends PHPUnit_Framework_TestCase
{

	private $couch_server = "http://localhost:5984/";
	private $config;

    public function setUp()
    {
        $this->config = require './tests/_files/config.php';
        $client_test1 = $this->config ['databases']['client_test1'];
        $admin_config = $this->config ['databases']['client_admin'];

        $this->client = new couchClient($client_test1['uri'],$client_test1['dbname']);
        $this->aclient = new couchClient($admin_config['uri'],$admin_config['dbname']);
        try {
            $this->aclient->deleteDatabase();
        } catch (Exception $e) {
        }
        $this->aclient->createDatabase();
    }

	public function tearDown()
    {
        $this->client = null;
    }

	public function testDatabaseNameValidator () {
		$matches = array (
			"Azerty"=>false,
			"a-zer_ty" => true,
			"a(zert)y" => true,
			"4azerty" => false
		);
		foreach ( $matches as $key => $val ) {
			$this->assertEquals ( $val, couchClient::isValidDatabaseName($key) );
		}

	}

	public function testDatabaseExists () {
		$exist = $this->client->databaseExists();
		$this->assertTrue($exist,"testing against an existing database");

		$client = new couchClient($this->couch_server,"foofoofooidontexist");
		$this->assertFalse($client->databaseExists(),"testing against a non-existing database");

	}

	public function testDatabaseInfos () {
        $client_test1 = $this->config ['databases']['client_test1'];
		$infos = $this->client->getDatabaseInfos();
// 		print_r($infos);
		$this->assertInternalType("object", $infos);
		$tsts = array(
			'db_name' => $client_test1['dbname'],
			"doc_count" => 0,
			"doc_del_count" => 0,
			"update_seq" => 0,
			"purge_seq" => 0,
			"compact_running" => false,
			"disk_size" => false,
			"instance_start_time" => false,
			"disk_format_version" => false
		);
		foreach ( $tsts as $attr => $value ) {
			$this->assertObjectHasAttribute($attr,$infos);
			if ( $value !== false ) {
				$this->assertEquals($value,$infos->$attr);
			}
		}
	}

	public function testDatabaseDelete () {
		$back = $this->aclient->deleteDatabase();
		$this->assertInternalType("object", $back);
		$this->assertObjectHasAttribute("ok",$back);
		$this->assertEquals(true,$back->ok);
// 		print_r($back);
	}

	public function testGetDatabaseUri () {
        $client_test1 = $this->config ['databases']['client_test1'];
		$this->assertEquals ( $client_test1['uri']. '/' .$client_test1['dbname'], $this->client->getDatabaseUri() );
	}

	public function testGetDatabaseName () {
        $client_test1 = $this->config ['databases']['client_test1'];
		$this->assertEquals ( $client_test1['dbname'], $this->client->getDatabaseName() );
	}

	public function testGetServerUri () {
        $client_test1 = $this->config ['databases']['client_test1'];
		$this->assertEquals ( $client_test1['uri'], $this->client->getServerUri() );
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testStoreDocException () {
		$test = array ("_id"=>"great","type"=> "array");
		$this->client->storeDoc($test);
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testStoreDocException2 () {
		$test = new stdclass();
		$test->_id = "great";
		$test->_type = "object";
		$this->client->storeDoc($test);
	}

	public function testStoreDoc () {
		$infos = $this->client->getDatabaseInfos();
		$test = new stdclass();
		$test->_id = "great";
		$test->type = "object";
		$this->client->storeDoc($test);
		$infos2 = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count+1, $infos2->doc_count );
		$doc = $this->client->getDoc("great");
		$this->assertInternalType("object", $doc);
		$this->assertObjectHasAttribute("type",$doc);
		$this->assertEquals("object",$doc->type);
	}

	public function testBulkDocsStorage () {
        $this->markTestIncomplete();
		$data = array (
			new stdclass(),
			new stdclass(),
			new stdclass()
		);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 0 );

		$stored = $this->client->storeDocs($data,false);
// 		print_r($stored);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 3 );

		$data[0]->_id = "test";
		$data[0]->type = "male";
		$data[1]->_id = "test";
		$data[1]->type = "female";
		$data[2]->_id = "test";
		$data[2]->type = "both";
		$stored = $this->client->storeDocs($data,true);
// 		print_r($stored);
		$infos = $this->client->getDatabaseInfos();
// 		print_r($infos);
		$this->assertEquals ( $infos->doc_count, 4 );

		$doc = $this->client->conflicts()->getDoc("test");
		$this->assertInternalType("object", $doc);
		$this->assertObjectHasAttribute("_conflicts",$doc);
		$this->assertInternalType("array",$doc->_conflicts);
		$this->assertEquals( count( $doc->_conflicts ) , 2 );
		$data[0]->_id = "test2";
		$data[1]->_id = "test2";
		$data[2]->_id = "test2";
		$stored = $this->client->storeDocs($data,false);
		$this->assertInternalType("array",$stored);
		$this->assertEquals( count($stored) , 3 );
		foreach ( $stored as $s ) {
			$this->assertInternalType("object",$s);
			$this->assertObjectHasAttribute("error",$s);
			$this->assertEquals($s->error, "conflict");
		}
		$doc = $this->client->conflicts()->getDoc("test2");
		$this->assertObjectNotHasAttribute("_conflicts",$doc);
// 		print_r($stored);
	}

	public function testcompactAllViews () {
		$cd = new couchDocument($this->aclient);
		$cd->set ( array (
			'_id' => '_design/test',
			'language'=>'javascript'
		) );
		$this->aclient->compactAllViews();
	}

	public function testCouchDocumentAttachment () {
		$cd = new couchDocument($this->client);
		$cd->set ( array (
			'_id' => 'somedoc'
		) );
		$back = $cd->storeAsAttachment("This is the content","file.txt","text/plain");
		$fields = $cd->getFields();

		$this->assertInternalType("object", $back);
		$this->assertObjectHasAttribute("ok",$back);
		$this->assertEquals( $back->ok , true );
		$this->assertObjectHasAttribute("_attachments",$fields);
		$this->assertObjectHasAttribute("file.txt",$fields->_attachments);

		$cd = new couchDocument($this->client);
		$cd->set ( array (
			'_id' => 'somedoc2'
		) );
		$back = $cd->storeAttachment("lib/couch.php","text/plain","file.txt");
		$fields = $cd->getFields();

		$this->assertInternalType("object", $back);
		$this->assertObjectHasAttribute("ok",$back);
		$this->assertEquals( $back->ok , true );
		$this->assertObjectHasAttribute("_attachments",$fields);
		$this->assertObjectHasAttribute("file.txt",$fields->_attachments);

		$back = $cd->deleteAttachment("file.txt");
		$fields = $cd->getFields();
		$this->assertInternalType("object", $back);
		$this->assertObjectHasAttribute("ok",$back);
		$this->assertEquals( $back->ok , true );
		$test = property_exists($fields,'_attachments');
		$this->assertEquals($test,false);
// 		$this->assertObjectHasAttribute("file.txt",$fields->_attachments);
	}

	public function testRevs () {
		$cd = new couchDocument($this->client);
		$cd->set ( array (
			'_id' => 'somedoc'
		) );
		$cd->property1 = "one";
		$cd->property2 = "two";
		$doc = $this->client->revs()->revs_info()->getDoc("somedoc");
		$this->assertObjectHasAttribute("_revisions",$doc);
		$this->assertObjectHasAttribute("ids",$doc->_revisions);
		$this->assertEquals( count($doc->_revisions->ids) , 3 );
		$this->assertObjectHasAttribute("_revs_info",$doc);
		$this->assertEquals( count($doc->_revs_info) , 3 );
	}

	public function testBulkDocsStorageAllOrNothing () {
		$data = array (
			new stdclass(),
			new stdclass(),
			new stdclass()
		);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 0 );

		$data[0]->_id = "test";
		$data[0]->type = "male";
		$data[1]->_id = "test";
		$data[1]->type = "female";
		$data[2]->_id = "test";
		$data[2]->type = "both";
		$stored = $this->client->storeDocs($data,true);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 1 );
		$doc = $this->client->conflicts()->getDoc("test");
		$this->assertObjectHasAttribute("_conflicts",$doc);
		$this->assertEquals( count($doc->_conflicts) , 2 );

		$data[0]->_id = "test2";
		$data[0]->type = "male";
		$data[1]->_id = "test2";
		$data[1]->type = "female";
		$data[2]->_id = "test2";
		$data[2]->type = "both";

		$stored = $this->client->storeDocs($data,false);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 2 );
		$doc = $this->client->conflicts()->getDoc("test2");
		$this->assertObjectNotHasAttribute("_conflicts",$doc);
	}


	public function testDocAsArray () {
		$infos = $this->client->getDatabaseInfos();
		$test = new stdclass();
		$test->_id = "great";
		$test->type = "object";
		$this->client->storeDoc($test);
		$infos2 = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count+1, $infos2->doc_count );
		$doc = $this->client->asArray()->getDoc("great");
		$this->assertInternalType("array", $doc);
		$this->assertArrayHasKey("type",$doc);
		$this->assertEquals("object",$doc['type']);
	}
}
