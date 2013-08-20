<?php

use PHPOnCouch\Couch;
use PHPOnCouch\Admin;
use PHPOnCouch\Client;
use PHPOnCouch\Document;
use PHPOnCouch\Replicator;
use \stdClass;

class ClientTest extends PHPUnit_Framework_TestCase
{

    const DATABASE_NAME = "test_database976277263";

    private $couch_server = "http://localhost:5984/";
    private $client       = null;

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

    }

    public function tearDown()
    {
        $this->client->deleteDatabase();
    }

    public function testDatabaseNameValidator()
    {

        $matches = array (
            "Azerty"   => false,
            "a-zer_ty" => true,
            "a(zert)y" => true,
            "4azerty"  => false
        );

        foreach ($matches as $key => $val) {
          $this->assertEquals($val, Client::isValidDatabaseName($key));
        }

    }

    public function testDatabaseExists () {

        $exist = $this->client->databaseExists();
        $this->assertTrue($exist, "testing against an existing database");

        $client = new Client($this->couch_server, "foofoofooidontexist");
        $this->assertFalse($client->databaseExists(), "testing against a non-existing database");

    }

    public function testDatabaseInfos()
    {

        $infos = $this->client->getDatabaseInfos();
        $this->assertInternalType('object', $infos);

        $tsts = array(
            'db_name'             => self::DATABASE_NAME,
            "doc_count"           => 0,
            "doc_del_count"       => 0,
            "update_seq"          => 0,
            "purge_seq"           => 0,
            "compact_running"     => false,
            "disk_size"           => false,
            "instance_start_time" => false,
            "disk_format_version" => false
        );

        foreach($tsts as $attr => $value) {
            $this->assertObjectHasAttribute($attr, $infos);
            if ($value !== false) {
                $this->assertEquals($value, $infos->$attr);
            }
        }

    }

    public function testGetDatabaseUri()
    {
        $this->assertEquals($this->couch_server . self::DATABASE_NAME, $this->client->getDatabaseUri());
    }

    public function testGetDatabaseName()
    {
        $this->assertEquals(self::DATABASE_NAME, $this->client->getDatabaseName());
    }

    public function testGetServerUri()
    {
        $this->assertEquals($this->couch_server . self::DATABASE_NAME, $this->client->getDatabaseUri());
    }


    /**
     * @expectedException InvalidArgumentException
     */
    public function testStoreDocException()
    {

        $test = array ("_id" => "great", "type" => "array");
        $this->client->storeDoc($test);

    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testStoreDocException2()
    {

        $test = new stdclass();
        $test->_id   = "great";
        $test->_type = "object";
        $this->client->storeDoc($test);

    }

    public function testStoreDoc()
    {

        $infos      = $this->client->getDatabaseInfos();
        $test       = new stdclass();
        $test->_id  = "great";
        $test->type = "object";

        $this->client->storeDoc($test);

        $infos2 = $this->client->getDatabaseInfos();
        $doc    = $this->client->getDoc("great");

        $this->assertEquals($infos->doc_count + 1, $infos2->doc_count);
        $this->assertObjectHasAttribute("type", $doc);
        $this->assertTrue($doc->type === "object");
        $this->assertInternalType('object', $doc);

    }

    public function testRemoveDoc()
    {

        $test       = new stdclass();
        $test->_id  = "great";
        $test->type = "object";

        $this->client->storeDoc($test);
        $doc = $this->client->getDoc($test->_id);
        $this->client->deleteDoc($doc);
        $infos = $this->client->getDatabaseInfos();

        $this->assertEquals($infos->doc_count, 0);

    }

    public function testBulkDocsStorage()
    {

        $data = array (
            new stdclass(),
            new stdclass(),
            new stdclass()
        );

        $infos = $this->client->getDatabaseInfos();
        $this->assertEquals($infos->doc_count, 0);

        $stored = $this->client->storeDocs($data, false);
        $infos  = $this->client->getDatabaseInfos();
        $this->assertEquals($infos->doc_count, 3);

        $data[0]->_id  = "test";
        $data[0]->type = "male";
        $data[1]->_id  = "test";
        $data[1]->type = "female";
        $data[2]->_id  = "test";
        $data[2]->type = "both";

        $stored = $this->client->storeDocs($data,true);
        $infos  = $this->client->getDatabaseInfos();

        $this->assertEquals ( $infos->doc_count, 4);

        $doc = $this->client->conflicts()->getDoc("test");

        $this->assertInternalType('object', $doc);
        $this->assertObjectHasAttribute("_conflicts", $doc);
        $this->assertInternalType('array', $doc->_conflicts);;
        $this->assertEquals(count($doc->_conflicts), 2);

        $data[0]->_id = "test2";
        $data[1]->_id = "test2";
        $data[2]->_id = "test2";

        $stored = $this->client->storeDocs($data, false);

        $this->assertInternalType('array', $stored);
        $this->assertEquals(count($stored), 3);

        unset($stored[0]);
        foreach ($stored as $s) {
            $this->assertInternalType('object', $s);
            $this->assertObjectHasAttribute("error", $s);
            $this->assertEquals($s->error, "conflict");
        }

        $doc = $this->client->conflicts()->getDoc("test2");
        $this->assertObjectNotHasAttribute("_conflicts", $doc);

    }

    public function testcompactAllViews()
    {

        $cd = new Document($this->client);
        $cd->set(array(
            '_id' => '_design/test',
            'language'=>'javascript'
        ));

        $this->client->compactAllViews();
        $this->client->deleteDoc($cd);

    }

    public function testDocumentAttachment()
    {

        $cd = new Document($this->client);
        $cd->set(array('_id' => 'somedoc'));
        $back   = $cd->storeAsAttachment("This is the content", "file.txt", "text/plain");
        $fields = $cd->getFields();

        $this->assertInternalType('object', $back);
        $this->assertObjectHasAttribute("ok", $back);
        $this->assertEquals($back->ok , true);
        $this->assertObjectHasAttribute("_attachments", $fields);
        $this->assertObjectHasAttribute("file.txt", $fields->_attachments);

        $this->client->deleteDoc($cd);

        $cd = new Document($this->client);
        $cd->set(array('_id' => 'somedoc2'));
        $back   = $cd->storeAttachment("lib/PHPOnCouch/Couch.php", "text/plain", "file.txt");
        $fields = $cd->getFields();

        $this->assertInternalType('object', $back);
        $this->assertObjectHasAttribute("ok", $back);
        $this->assertEquals($back->ok , true );
        $this->assertObjectHasAttribute("_attachments", $fields);
        $this->assertObjectHasAttribute("file.txt", $fields->_attachments);

        $back   = $cd->deleteAttachment("file.txt");
        $fields = $cd->getFields();
        $this->assertInternalType('object', $back);
        $this->assertObjectHasAttribute("ok", $back);
        $this->assertEquals($back->ok , true );
        $test = property_exists($fields, '_attachments');
        $this->assertEquals($test, false);

        $this->client->deleteDoc($cd);

    }

    public function testRevs()
    {

        $doc = new Document($this->client);

        $doc->type = sha1('random');
        $doc->type = sha1('random');
        $doc->type = sha1('random');

        $doc = $this->client->revs()->revs_info()->getDoc($doc->_id);

        $this->assertObjectHasAttribute("_revisions", $doc);
        $this->assertObjectHasAttribute("_revs_info", $doc);
        $this->assertObjectHasAttribute("ids", $doc->_revisions);

        $this->assertEquals(count($doc->_revs_info) , 3);
        $this->assertEquals(count($doc->_revisions->ids), 3);

        $this->client->deleteDoc($doc);

    }

    public function testBulkDocsStorageAllOrNothing()
    {

        $data = array (
            new stdclass(),
            new stdclass(),
            new stdclass()
        );

        $infos = $this->client->getDatabaseInfos();
        $this->assertEquals($infos->doc_count, 0);

        $data[0]->_id  = "test";
        $data[0]->type = "male";
        $data[1]->_id  = "test";
        $data[1]->type = "female";
        $data[2]->_id  = "test";
        $data[2]->type = "both";

        $stored = $this->client->storeDocs($data,true);
        $infos  = $this->client->getDatabaseInfos();

        $this->assertEquals($infos->doc_count, 1);

        $doc = $this->client->conflicts()->getDoc("test");

        $this->assertObjectHasAttribute("_conflicts",$doc);
        $this->assertEquals(count($doc->_conflicts), 2);

        $data[0]->_id  = "test2";
        $data[0]->type = "male";
        $data[1]->_id  = "test2";
        $data[1]->type = "female";
        $data[2]->_id  = "test2";
        $data[2]->type = "both";

        $stored = $this->client->storeDocs($data,false);
        $infos  = $this->client->getDatabaseInfos();

        $this->assertEquals ($infos->doc_count, 2);

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
        $doc    = $this->client->asArray()->getDoc("great");

        $this->assertEquals($infos->doc_count+1, $infos2->doc_count);
        $this->assertInternalType('array', $doc);;
        $this->assertArrayHasKey("type", $doc);
        $this->assertTrue($doc["type"] === "object");

    }

}
