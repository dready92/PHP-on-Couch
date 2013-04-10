<?php

// error_reporting(E_STRICT);
error_reporting(E_ALL);

class PhpOnCouchTest_ClientListTest extends PHPUnit_Framework_TestCase
{

	private $couch_server = null;
	private $client = null;

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
		try {
			$this->client->deleteDatabase();
		} catch ( Exception $e) {}
		$this->client->createDatabase();
    }

	public function tearDown()
    {
        $this->client = null;
    }


	public function testList () {
		$doc = new PhpOnCouch_Document($this->client);
		$doc->_id="_design/test";
		$views = array (
			"simple" => array (
				"map" => "function (doc) {
					if ( doc.type ) {
						emit( [ doc.type, doc._id ] , doc);
					}
				}"
			)
		);
		$lists = array (
			"list1" => "function (head, req) {
				var back = [];
				var row;
				while ( row = getRow() ) {
					back.push(row);
				}
				send(JSON.stringify(back));
			}"
		);
		$doc->views = $views;
		$doc->lists = $lists;

		$doc = new PhpOnCouch_Document($this->client);
		$doc->_id = '_design/test2';
		$lists = array (
			"list2" => "function (head, req) {
				var back = [];
				var row;
				while ( row = getRow() ) {
					row.value='test2';
					back.push(row);
				}
				send(JSON.stringify(back));
			}"
		);
		$doc->lists = $lists;

		$docs = array (
			array('_id'=>'first','type'=>'test','param'=>'hello'),
			array('_id'=>'second','type'=>'test2','param'=>'hello2'),
			array('_id'=>'third','type'=>'test','param'=>'hello3')
		);
		$this->client->storeDocs($docs);
 		$test = $this->client->getList('test','list1','simple');
		$this->assertType("array", $test);
		$this->assertEquals(count($test), 3);
		foreach( $test as $row ) {
			$this->assertType("object", $row);
			$this->assertObjectHasAttribute('id',$row);
			$this->assertObjectHasAttribute('key',$row);
			$this->assertObjectHasAttribute('value',$row);
		}

		$test = $this->client->startkey( array('test') )->endkey( array('test', array()) )->getList('test','list1','simple');
		$this->assertType("array", $test);
		$this->assertEquals(count($test), 2);
		foreach( $test as $row ) {
			$this->assertType("object", $row);
			$this->assertObjectHasAttribute('id',$row);
			$this->assertObjectHasAttribute('key',$row);
			$this->assertObjectHasAttribute('value',$row);
		}

		$test = $this->client->startkey( array('test2') )->endkey( array('test2', array()) )->getForeignList('test2','list2','test','simple');
		$this->assertType("array", $test);
		$this->assertEquals(count($test), 1);
		foreach( $test as $row ) {
			$this->assertType("object", $row);
			$this->assertObjectHasAttribute('id',$row);
			$this->assertObjectHasAttribute('key',$row);
			$this->assertObjectHasAttribute('value',$row);
			$this->assertEquals($row->value,'test2');
		}

		$test = $this->client
						->startkey( array('test2') )
						->endkey( array('test2', array()) )
						->include_docs(TRUE)
						->getForeignList('test2','list2','test','simple');
		$this->assertType("array", $test);
		$this->assertEquals(count($test), 1);
		foreach( $test as $row ) {
			$this->assertType("object", $row);
			$this->assertObjectHasAttribute('id',$row);
			$this->assertObjectHasAttribute('key',$row);
			$this->assertObjectHasAttribute('value',$row);
			$this->assertObjectHasAttribute('doc',$row);
			$this->assertType("object", $row->doc);
			$this->assertObjectHasAttribute('_id',$row->doc);
			$this->assertObjectHasAttribute('_rev',$row->doc);
			$this->assertEquals($row->value,'test2');
		}

// 		print_r($test);
		
// 		$this->assertType("object", $test);
// 		$this->assertObjectHasAttribute("doc",$test);
// 		$this->assertObjectHasAttribute("query_length",$test);
	}

}
