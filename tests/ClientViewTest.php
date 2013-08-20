<?php

use PHPOnCouch\Couch;
use PHPOnCouch\Admin;
use PHPOnCouch\Client;
use PHPOnCouch\Document;
use PHPOnCouch\Replicator;
use \stdClass;

class ClientViewTest extends PHPUnit_Framework_TestCase
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

        $this->_makeView();

    }

    public function tearDown()
    {
        $this->client->deleteDatabase();
    }


    protected function _makeView()
    {

        $doc = new Document($this->client);
        $doc->_id = "_design/test";
        $views = array();

        $map = "function (doc) {
          if ( doc.type && doc.type == 'test' ) {
            emit(doc.type,1);
          }
        }";

        $reduce = "function (keys, values) {
          return sum(values);
        }";

        $map2 = "function (doc) {
          if ( doc.type ) {
            emit(doc.type,1);
          }
        }";

        $doc->views = array (
          "simple" => array (
            "map"=>$map,
            "reduce"=>$reduce
          ),
          "complex" => array (
            "map"=>$map2,
            "reduce"=>$reduce
          )
        );

    }

    protected function _common_test($infos, $test, $args) {

        $this->assertEquals($infos->doc_count, $args[0]);
        $this->assertInternalType('object', $test);
        $this->assertObjectHasAttribute("total_rows", $test);
        $this->assertEquals($test->total_rows, $args[1]);
        $this->assertObjectHasAttribute("offset", $test);
        $this->assertEquals($test->offset, $args[2]);
        $this->assertObjectHasAttribute("rows", $test);
        $this->assertInternalType('array', $test->rows);
        $this->assertEquals(count($test->rows), $args[3]);

        foreach ($test->rows as $row) {
            $this->assertObjectHasAttribute("id",    $row);
            $this->assertObjectHasAttribute("key",   $row);
            $this->assertObjectHasAttribute("value", $row);
        }

    }

    public function testSimpleViews()
    {

        $docs = array (
            array("_id" => "one",   "type" => "test",  "param" => null),
            array("_id" => "two",   "type" => "test",  "param" => null),
            array("_id" => "three", "type" => "test",  "param" => null),
            array("_id" => "four",  "type" => "test2", "param" => null),
            array("_id" => "five",  "type" => "test",  "param" => null)
        );

        $this->client->storeDocs($docs);

        $infos = $this->client->getDatabaseInfos();
        $test  = $this->client->reduce(false)->getView("test", "simple");

        $this->_common_test($infos, $test, array(6, 4, 0, 4));

        foreach ($test->rows as $row) {
            $this->assertObjectNotHasAttribute("doc", $row);
        }

    }

    public function testSimpleReduceViews()
    {

        $docs = array (
            array("_id" => "one",   "type" => "test",  "param" => null),
            array("_id" => "two",   "type" => "test",  "param" => null),
            array("_id" => "three", "type" => "test",  "param" => null),
            array("_id" => "four",  "type" => "test2", "param" => null),
            array("_id" => "five",  "type" => "test",  "param" => null)
        );
        $this->client->storeDocs($docs);

        $infos = $this->client->getDatabaseInfos();
        $test  = $this->client->getView("test", "simple");

        $this->assertEquals($infos->doc_count, 6);
        $this->assertInternalType('object', $test);
        $this->assertObjectHasAttribute("rows", $test);
        $this->assertInternalType('array', $test->rows);
        $this->assertEquals(count($test->rows), 1);
        $row = reset($test->rows);
        $this->assertInternalType('object', $row);
        $this->assertObjectHasAttribute("value", $row);
        $this->assertEquals($row->value, 4);

    }

    public function testIncludeDocs()
    {

        $docs = array (
            array("_id" => "one",   "type" => "test",  "param" => null),
            array("_id" => "two",   "type" => "test",  "param" => null),
            array("_id" => "three", "type" => "test",  "param" => null),
            array("_id" => "four",  "type" => "test2", "param" => null),
            array("_id" => "five",  "type" => "test",  "param" => null)
        );
        $this->client->storeDocs($docs);

        $infos = $this->client->getDatabaseInfos();
        $test  = $this->client->reduce(false)->include_docs(true)->getView("test", "simple");

        $this->_common_test($infos, $test, array(6, 4, 0, 4));

        foreach ($test->rows as $row) {
            $this->assertObjectHasAttribute("doc", $row);
        }

    }

    public function testViewKey()
    {

        $docs = array (
            array("_id" => "one",   "type" => "test",  "param" => null),
            array("_id" => "two",   "type" => "test2", "param" => null),
            array("_id" => "three", "type" => "test",  "param" => null),
            array("_id" => "four",  "type" => "test2", "param" => null),
            array("_id" => "five",  "type" => "test2", "param" => null)
        );
        $this->client->storeDocs($docs);

        $infos = $this->client->getDatabaseInfos();
        $test  = $this->client->reduce(false)->key("test")->getView("test", "complex");

        $this->_common_test($infos, $test, array(6, 5, 0, 2));

        foreach ( $test->rows as $row ) {
            $this->assertEquals("test", $row->key);
        }

    }

    public function testViewKeys()
    {

        $docs = array (
            array("_id" => "one",   "type" => "test",  "param" => null),
            array("_id" => "two",   "type" => "test2", "param" => null),
            array("_id" => "three", "type" => "test",  "param" => null),
            array("_id" => "four",  "type" => "test3", "param" => null),
            array("_id" => "five",  "type" => "test2", "param" => null)
        );
        $this->client->storeDocs($docs);

        $infos = $this->client->getDatabaseInfos();
        $test  = $this->client->reduce(false)->keys(array("test", "test3"))->getView("test", "complex");

        $this->_common_test($infos, $test, array(6, 5, 0, 3));

    }

    public function testViewStartkey () {

        $docs = array (
            array("_id" => "one",   "type" => "test",  "param" => null),
            array("_id" => "two",   "type" => "test2", "param" => null),
            array("_id" => "three", "type" => "test",  "param" => null),
            array("_id" => "four",  "type" => "test3", "param" => null),
            array("_id" => "five",  "type" => "test2", "param" => null)
        );
        $this->client->storeDocs($docs);

        $infos = $this->client->getDatabaseInfos();
        $test  = $this->client->reduce(false)->startkey("test3")->getView("test", "complex");

        $this->_common_test($infos, $test, array(6, 5, 4, 1));

    }

    public function testViewEndkey()
    {

        $docs = array (
            array("_id" => "one",   "type" => "test",  "param" => null),
            array("_id" => "two",   "type" => "test2", "param" => null),
            array("_id" => "three", "type" => "test",  "param" => null),
            array("_id" => "four",  "type" => "test3", "param" => null),
            array("_id" => "five",  "type" => "test2", "param" => null)
        );
        $this->client->storeDocs($docs);

        $infos = $this->client->getDatabaseInfos();
        $test  = $this->client->reduce(false)->endkey("test")->getView("test","complex");

        $this->_common_test($infos, $test, array(6, 5, 0, 2));

        $test = $this->client->reduce(false)->endkey("test")->inclusive_end(false)->getView("test","complex");

        $this->assertInternalType('object', $test);
        $this->assertObjectHasAttribute("total_rows", $test);
        $this->assertEquals($test->total_rows, 5);
        $this->assertObjectHasAttribute("offset", $test);
        $this->assertEquals($test->offset, 0);
        $this->assertObjectHasAttribute("rows", $test);
        $this->assertInternalType('array', $test->rows);
        $this->assertEquals(count($test->rows), 0);

        foreach ( $test->rows as $row ) {
            $this->assertObjectHasAttribute("id",   $row);
            $this->assertObjectHasAttribute("key",   $row);
            $this->assertObjectHasAttribute("value", $row);
        }

    }

    public function testViewStartkeyDocid()
    {

        $docs = array (
            array("_id" => "one",   "type" => "test",  "param" => null),
            array("_id" => "two",   "type" => "test2", "param" => null),
            array("_id" => "three", "type" => "test",  "param" => null),
            array("_id" => "four",  "type" => "test3", "param" => null),
            array("_id" => "five",  "type" => "test2", "param" => null)
        );
        $this->client->storeDocs($docs);

        $infos = $this->client->getDatabaseInfos();
        $test  = $this->client->reduce(false)->startkey("test")->startkey_docid("three")->getView("test","complex");

        $this->_common_test($infos, $test, array(6, 5, 1, 4));

    }

    public function testViewEndkeyDocid()
    {

        $docs = array (
            array("_id" => "one",   "type" => "test",  "param" => null),
            array("_id" => "two",   "type" => "test2", "param" => null),
            array("_id" => "three", "type" => "test",  "param" => null),
            array("_id" => "four",  "type" => "test3", "param" => null),
            array("_id" => "five",  "type" => "test2", "param" => null)
        );
        $this->client->storeDocs($docs);

        $infos = $this->client->getDatabaseInfos();
        $test  = $this->client->reduce(false)->endkey("test2")->endkey_docid("five")->getView("test","complex");

        $this->_common_test($infos, $test, array(6, 5, 0, 3));

    }

    public function testViewLimit()
    {

        $docs = array (
            array("_id" => "one",   "type" => "test",  "param" => null),
            array("_id" => "two",   "type" => "test2", "param" => null),
            array("_id" => "three", "type" => "test",  "param" => null),
            array("_id" => "four",  "type" => "test3", "param" => null),
            array("_id" => "five",  "type" => "test2", "param" => null)
        );
        $this->client->storeDocs($docs);

        $infos = $this->client->getDatabaseInfos();
        $test  = $this->client->reduce(false)->limit(2)->getView("test","complex");

        $this->_common_test($infos, $test, array(6, 5, 0, 2));

    }

    public function testViewSkip()
    {

        $docs = array (
            array("_id" => "one",   "type" => "test",  "param" => null),
            array("_id" => "two",   "type" => "test2", "param" => null),
            array("_id" => "three", "type" => "test",  "param" => null),
            array("_id" => "four",  "type" => "test3", "param" => null),
            array("_id" => "five",  "type" => "test2", "param" => null)
        );
        $this->client->storeDocs($docs);

        $infos = $this->client->getDatabaseInfos();
        $test = $this->client->reduce(false)->skip(2)->getView("test","complex");

        $this->_common_test($infos, $test, array(6, 5, 2, 3));

    }

    public function testViewDescending()
    {

        $docs = array (
            array("_id" => "one",   "type" => "test",  "param" => null),
            array("_id" => "two",   "type" => "test2", "param" => null),
            array("_id" => "three", "type" => "test",  "param" => null),
            array("_id" => "four",  "type" => "test3", "param" => null),
            array("_id" => "five",  "type" => "test2", "param" => null)
        );
        $this->client->storeDocs($docs);

        $infos = $this->client->getDatabaseInfos();
        $test  = $this->client->reduce(false)->getView("test","complex");

        $this->assertEquals ( $infos->doc_count, 6 );
        $this->assertInternalType('object', $test);
        $this->assertObjectHasAttribute("total_rows",$test);
        $this->assertEquals($test->total_rows, 5);
        $this->assertObjectHasAttribute("offset",$test);
        $this->assertEquals($test->offset, 0);
        $this->assertObjectHasAttribute("rows",$test);
        $this->assertInternalType('array', $test->rows);
        $this->assertEquals(count($test->rows), 5);
        $row = reset($test->rows);
        $this->assertObjectHasAttribute("key",$row);
        $this->assertEquals($row->key, "test");

        $test = $this->client->reduce(false)->descending(true)->getView("test","complex");
        $this->assertInternalType('object', $test);
        $this->assertObjectHasAttribute("total_rows",$test);
        $this->assertEquals($test->total_rows, 5);
        $this->assertObjectHasAttribute("offset",$test);
        $this->assertEquals($test->offset, 0);
        $this->assertObjectHasAttribute("rows",$test);
        $this->assertInternalType('array', $test->rows);
        $this->assertEquals(count($test->rows), 5);
        $row = reset($test->rows);
        $this->assertObjectHasAttribute("key",$row);
        $this->assertEquals($row->key, "test3");

    }

    public function testViewGroup()
    {

        $docs = array (
            array("_id" => "one",   "type" => "test",  "param" => 1),
            array("_id" => "two",   "type" => "test2", "param" => 2),
            array("_id" => "three", "type" => "test",  "param" => 2),
            array("_id" => "four",  "type" => "test3", "param" => 1),
            array("_id" => "five",  "type" => "test2", "param" => 1)
        );
        $this->client->storeDocs($docs);

        $infos = $this->client->getDatabaseInfos();
        $this->assertEquals( $infos->doc_count, 6);

        $doc = Document::getInstance($this->client, "_design/test");
        $views = $doc->views;
        $views->multigroup = new stdClass();
        $views->multigroup->map = "function (doc) {
          if ( doc.type && doc.param ) {
            emit( [doc.type, doc.param], 1);
          }
        }";

        $views->multigroup->reduce = "function(keys,values) {
          return sum(values);
        }";

        $doc->views = $views;

        $test = $this->client->group(true)->getView("test","multigroup");
        $this->assertInternalType('object', $test);
        $this->assertObjectHasAttribute("rows",$test);
        $this->assertInternalType('array', $test->rows);
        $this->assertEquals(count($test->rows), 5);

        $test = $this->client->group(true)->group_level(1)->getView("test","multigroup");
        $this->assertInternalType('object', $test);
        $this->assertObjectHasAttribute("rows",$test);
        $this->assertInternalType('array', $test->rows);;
        $this->assertEquals(count($test->rows), 3);

    }

    public function testViewAsArray()
    {

        $docs = array (
            array("_id" => "one",   "type" => "test",  "param" => null),
            array("_id" => "two",   "type" => "test2", "param" => null),
            array("_id" => "three", "type" => "test",  "param" => null),
            array("_id" => "four",  "type" => "test3", "param" => null),
            array("_id" => "five",  "type" => "test2", "param" => null)
        );
        $this->client->storeDocs($docs);

        $infos = $this->client->getDatabaseInfos();
        $test = $this->client->reduce(false)->asArray()->getView("test","complex");

        $this->assertEquals($infos->doc_count, 6);
        $this->assertInternalType('array', $test);

    }

}
