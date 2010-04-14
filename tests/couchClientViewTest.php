<?php

// error_reporting(E_STRICT);
error_reporting(E_ALL);

require_once 'PHPUnit/Framework.php';

require_once "lib/couch.php";
require_once "lib/couchClient.php";
require_once "lib/couchDocument.php";
require_once "lib/couchReplicator.php";


class couchClientViewTest extends PHPUnit_Framework_TestCase
{

	private $couch_server = "http://localhost:5984/";

    public function setUp()
    {
        $this->client = new couchClient($this->couch_server,"couchclienttest");
		try {
			$this->client->deleteDatabase();
		} catch ( Exception $e) {}
		$this->client->createDatabase();
    }

	public function tearDown()
    {
        $this->client = null;
    }

	protected function _makeView () {
		$doc = new couchDocument($this->client);
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

	public function testSimpleViews () {
		$this->_makeView();
		$docs = array (
			array( "_id"=>"one","type"=>"test","param" => null),
			array( "_id"=>"two","type"=>"test","param" => null),
			array( "_id"=>"three","type"=>"test","param" => null),
			array( "_id"=>"four","type"=>"test2","param" => null),
			array( "_id"=>"five","type"=>"test","param" => null)
		);
		$this->client->storeDocs($docs);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 6 );
		$test = $this->client->reduce(false)->getView("test","simple");
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("total_rows",$test);
		$this->assertEquals($test->total_rows, 4);
		$this->assertObjectHasAttribute("offset",$test);
		$this->assertEquals($test->offset, 0);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 4);
		foreach ( $test->rows as $row ) {
			$this->assertObjectHasAttribute("id",$row);
			$this->assertObjectHasAttribute("key",$row);
			$this->assertObjectHasAttribute("value",$row);
			$this->assertObjectNotHasAttribute("doc",$row);
		}
	}

	public function testSimpleReduceViews () {
		$this->_makeView();
		$docs = array (
			array( "_id"=>"one","type"=>"test","param" => null),
			array( "_id"=>"two","type"=>"test","param" => null),
			array( "_id"=>"three","type"=>"test","param" => null),
			array( "_id"=>"four","type"=>"test2","param" => null),
			array( "_id"=>"five","type"=>"test","param" => null)
		);
		$this->client->storeDocs($docs);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 6 );
		$test = $this->client->getView("test","simple");
// 		print_r($test);
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 1);
		$row = reset($test->rows);
		$this->assertType("object", $row);
		$this->assertObjectHasAttribute("value",$row);
		$this->assertEquals($row->value, 4);		
	}


	public function testIncludeDocs () {
		$this->_makeView();
		$docs = array (
			array( "_id"=>"one","type"=>"test","param" => null),
			array( "_id"=>"two","type"=>"test","param" => null),
			array( "_id"=>"three","type"=>"test","param" => null),
			array( "_id"=>"four","type"=>"test2","param" => null),
			array( "_id"=>"five","type"=>"test","param" => null)
		);
		$this->client->storeDocs($docs);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 6 );
		$test = $this->client->reduce(false)->include_docs(true)->getView("test","simple");
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("total_rows",$test);
		$this->assertEquals($test->total_rows, 4);
		$this->assertObjectHasAttribute("offset",$test);
		$this->assertEquals($test->offset, 0);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 4);
		foreach ( $test->rows as $row ) {
			$this->assertObjectHasAttribute("id",$row);
			$this->assertObjectHasAttribute("key",$row);
			$this->assertObjectHasAttribute("value",$row);
			$this->assertObjectHasAttribute("doc",$row);
		}
	}

	public function testViewKey () {
		$this->_makeView();
		$docs = array (
			array( "_id"=>"one","type"=>"test","param" => null),
			array( "_id"=>"two","type"=>"test2","param" => null),
			array( "_id"=>"three","type"=>"test","param" => null),
			array( "_id"=>"four","type"=>"test2","param" => null),
			array( "_id"=>"five","type"=>"test2","param" => null)
		);
		$this->client->storeDocs($docs);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 6 );
		$test = $this->client->reduce(false)->key("test")->getView("test","complex");
// 		print_r($test);
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("total_rows",$test);
		$this->assertEquals($test->total_rows, 5);
		$this->assertObjectHasAttribute("offset",$test);
		$this->assertEquals($test->offset, 0);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 2);
		foreach ( $test->rows as $row ) {
			$this->assertObjectHasAttribute("id",$row);
			$this->assertObjectHasAttribute("key",$row);
			$this->assertEquals($row->key, "test");
			$this->assertObjectHasAttribute("value",$row);
		}
	}

	public function testViewKeys () {
		$this->_makeView();
		$docs = array (
			array( "_id"=>"one","type"=>"test","param" => null),
			array( "_id"=>"two","type"=>"test2","param" => null),
			array( "_id"=>"three","type"=>"test","param" => null),
			array( "_id"=>"four","type"=>"test3","param" => null),
			array( "_id"=>"five","type"=>"test2","param" => null)
		);
		$this->client->storeDocs($docs);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 6 );
		$test = $this->client->reduce(false)->keys(array("test","test3"))->getView("test","complex");
// 		print_r($test);	
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("total_rows",$test);
		$this->assertEquals($test->total_rows, 5);
		$this->assertObjectHasAttribute("offset",$test);
		$this->assertEquals($test->offset, 0);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 3);
		foreach ( $test->rows as $row ) {
			$this->assertObjectHasAttribute("id",$row);
			$this->assertObjectHasAttribute("key",$row);
			$this->assertObjectHasAttribute("value",$row);
		}
	}


	public function testViewStartkey () {
		$this->_makeView();
		$docs = array (
			array( "_id"=>"one","type"=>"test","param" => null),
			array( "_id"=>"two","type"=>"test2","param" => null),
			array( "_id"=>"three","type"=>"test","param" => null),
			array( "_id"=>"four","type"=>"test3","param" => null),
			array( "_id"=>"five","type"=>"test2","param" => null)
		);
		$this->client->storeDocs($docs);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 6 );
		$test = $this->client->reduce(false)->startkey("test3")->getView("test","complex");
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("total_rows",$test);
		$this->assertEquals($test->total_rows, 5);
		$this->assertObjectHasAttribute("offset",$test);
		$this->assertEquals($test->offset, 4);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 1);
		foreach ( $test->rows as $row ) {
			$this->assertObjectHasAttribute("id",$row);
			$this->assertObjectHasAttribute("key",$row);
			$this->assertObjectHasAttribute("value",$row);
		}
	}



	public function testViewEndkey () {
		$this->_makeView();
		$docs = array (
			array( "_id"=>"one","type"=>"test","param" => null),
			array( "_id"=>"two","type"=>"test2","param" => null),
			array( "_id"=>"three","type"=>"test","param" => null),
			array( "_id"=>"four","type"=>"test3","param" => null),
			array( "_id"=>"five","type"=>"test2","param" => null)
		);
		$this->client->storeDocs($docs);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 6 );
		$test = $this->client->reduce(false)->endkey("test")->getView("test","complex");
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("total_rows",$test);
		$this->assertEquals($test->total_rows, 5);
		$this->assertObjectHasAttribute("offset",$test);
		$this->assertEquals($test->offset, 0);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 2);
		foreach ( $test->rows as $row ) {
			$this->assertObjectHasAttribute("id",$row);
			$this->assertObjectHasAttribute("key",$row);
			$this->assertObjectHasAttribute("value",$row);
		}

		$test = $this->client->reduce(false)->endkey("test")->inclusive_end(false)->getView("test","complex");
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("total_rows",$test);
		$this->assertEquals($test->total_rows, 5);
		$this->assertObjectHasAttribute("offset",$test);
		$this->assertEquals($test->offset, 0);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 0);
		foreach ( $test->rows as $row ) {
			$this->assertObjectHasAttribute("id",$row);
			$this->assertObjectHasAttribute("key",$row);
			$this->assertObjectHasAttribute("value",$row);
		}

	}

	public function testViewStartkeyDocid () {
		$this->_makeView();
		$docs = array (
			array( "_id"=>"one","type"=>"test","param" => null),
			array( "_id"=>"two","type"=>"test2","param" => null),
			array( "_id"=>"three","type"=>"test","param" => null),
			array( "_id"=>"four","type"=>"test3","param" => null),
			array( "_id"=>"five","type"=>"test2","param" => null)
		);
		$this->client->storeDocs($docs);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 6 );
		$test = $this->client->reduce(false)->startkey("test")->startkey_docid("three")->getView("test","complex");
// 		print_r($test);	
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("total_rows",$test);
		$this->assertEquals($test->total_rows, 5);
		$this->assertObjectHasAttribute("offset",$test);
		$this->assertEquals($test->offset, 1);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 4);
		foreach ( $test->rows as $row ) {
			$this->assertObjectHasAttribute("id",$row);
			$this->assertObjectHasAttribute("key",$row);
			$this->assertObjectHasAttribute("value",$row);
		}
	}


	public function testViewEndkeyDocid () {
		$this->_makeView();
		$docs = array (
			array( "_id"=>"one","type"=>"test","param" => null),
			array( "_id"=>"two","type"=>"test2","param" => null),
			array( "_id"=>"three","type"=>"test","param" => null),
			array( "_id"=>"four","type"=>"test3","param" => null),
			array( "_id"=>"five","type"=>"test2","param" => null)
		);
		$this->client->storeDocs($docs);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 6 );
		$test = $this->client->reduce(false)->endkey("test2")->endkey_docid("five")->getView("test","complex");
// 		print_r($test);	
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("total_rows",$test);
		$this->assertEquals($test->total_rows, 5);
		$this->assertObjectHasAttribute("offset",$test);
		$this->assertEquals($test->offset, 0);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 3);
		foreach ( $test->rows as $row ) {
			$this->assertObjectHasAttribute("id",$row);
			$this->assertObjectHasAttribute("key",$row);
			$this->assertObjectHasAttribute("value",$row);
		}
	}


	public function testViewLimit () {
		$this->_makeView();
		$docs = array (
			array( "_id"=>"one","type"=>"test","param" => null),
			array( "_id"=>"two","type"=>"test2","param" => null),
			array( "_id"=>"three","type"=>"test","param" => null),
			array( "_id"=>"four","type"=>"test3","param" => null),
			array( "_id"=>"five","type"=>"test2","param" => null)
		);
		$this->client->storeDocs($docs);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 6 );
		$test = $this->client->reduce(false)->limit(2)->getView("test","complex");
// 		print_r($test);	
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("total_rows",$test);
		$this->assertEquals($test->total_rows, 5);
		$this->assertObjectHasAttribute("offset",$test);
		$this->assertEquals($test->offset, 0);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 2);
		foreach ( $test->rows as $row ) {
			$this->assertObjectHasAttribute("id",$row);
			$this->assertObjectHasAttribute("key",$row);
			$this->assertObjectHasAttribute("value",$row);
		}
	}


	public function testViewSkip () {
		$this->_makeView();
		$docs = array (
			array( "_id"=>"one","type"=>"test","param" => null),
			array( "_id"=>"two","type"=>"test2","param" => null),
			array( "_id"=>"three","type"=>"test","param" => null),
			array( "_id"=>"four","type"=>"test3","param" => null),
			array( "_id"=>"five","type"=>"test2","param" => null)
		);
		$this->client->storeDocs($docs);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 6 );
		$test = $this->client->reduce(false)->skip(2)->getView("test","complex");
// 		print_r($test);	
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("total_rows",$test);
		$this->assertEquals($test->total_rows, 5);
		$this->assertObjectHasAttribute("offset",$test);
		$this->assertEquals($test->offset, 2);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 3);
		foreach ( $test->rows as $row ) {
			$this->assertObjectHasAttribute("id",$row);
			$this->assertObjectHasAttribute("key",$row);
			$this->assertObjectHasAttribute("value",$row);
		}
	}



	public function testViewDescending () {
		$this->_makeView();
		$docs = array (
			array( "_id"=>"one","type"=>"test","param" => null),
			array( "_id"=>"two","type"=>"test2","param" => null),
			array( "_id"=>"three","type"=>"test","param" => null),
			array( "_id"=>"four","type"=>"test3","param" => null),
			array( "_id"=>"five","type"=>"test2","param" => null)
		);
		$this->client->storeDocs($docs);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 6 );
		$test = $this->client->reduce(false)->getView("test","complex");
// 		print_r($test);	
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("total_rows",$test);
		$this->assertEquals($test->total_rows, 5);
		$this->assertObjectHasAttribute("offset",$test);
		$this->assertEquals($test->offset, 0);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 5);
		$row = reset($test->rows);
		$this->assertObjectHasAttribute("key",$row);
		$this->assertEquals($row->key, "test");

		$test = $this->client->reduce(false)->descending(true)->getView("test","complex");
// 		print_r($test);	
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("total_rows",$test);
		$this->assertEquals($test->total_rows, 5);
		$this->assertObjectHasAttribute("offset",$test);
		$this->assertEquals($test->offset, 0);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 5);
		$row = reset($test->rows);
		$this->assertObjectHasAttribute("key",$row);
		$this->assertEquals($row->key, "test3");
	}


	public function testViewGroup () {
		$this->_makeView();
		$docs = array (
			array( "_id"=>"one","type"=>"test","param" => 1),
			array( "_id"=>"two","type"=>"test2","param" => 2),
			array( "_id"=>"three","type"=>"test","param" => 2),
			array( "_id"=>"four","type"=>"test3","param" => 1),
			array( "_id"=>"five","type"=>"test2","param" => 1)
		);
		$this->client->storeDocs($docs);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 6 );

		$doc = couchDocument::getInstance($this->client, "_design/test");
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
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 5);

		$test = $this->client->group(true)->group_level(1)->getView("test","multigroup");
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("rows",$test);
		$this->assertType("array", $test->rows);
		$this->assertEquals(count($test->rows), 3);
	}

	public function testViewAsArray () {
		$this->_makeView();
		$docs = array (
			array( "_id"=>"one","type"=>"test","param" => null),
			array( "_id"=>"two","type"=>"test2","param" => null),
			array( "_id"=>"three","type"=>"test","param" => null),
			array( "_id"=>"four","type"=>"test3","param" => null),
			array( "_id"=>"five","type"=>"test2","param" => null)
		);
		$this->client->storeDocs($docs);
		$infos = $this->client->getDatabaseInfos();
		$this->assertEquals ( $infos->doc_count, 6 );
		$test = $this->client->reduce(false)->asArray()->getView("test","complex");
// 		print_r($test);	
		$this->assertType("array", $test);
	}



}
