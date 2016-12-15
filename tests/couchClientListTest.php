<?php

// error_reporting(E_STRICT);
error_reporting(E_ALL);

use PHPOnCouch\CouchClient,
	PHPOnCouch\CouchDocument,
	PHPOnCouch\CouchAdmin;

require_once join(DIRECTORY_SEPARATOR,[__DIR__,'_config','config.php']);

class couchClientListTest extends PHPUnit_Framework_TestCase
{

	private $host = 'localhost';
	private $port = '5984';

	public function setUp()
	{
		$config = config::getInstance();
		$url = $config->getUrl($this->host, $this->port,null);
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

	public function testList()
	{
		$doc = new CouchDocument($this->aclient);
		$doc->_id = "_design/test";
		$views = array(
			"simple" => array(
				"map" => "function (doc) {
					if ( doc.type ) {
						emit( [ doc.type, doc._id ] , doc);
					}
				}"
			)
		);
		$lists = array(
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

		$doc = new CouchDocument($this->aclient);
		$doc->_id = '_design/test2';
		$lists = array(
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

		$docs = array(
			array('_id' => 'first', 'type' => 'test', 'param' => 'hello'),
			array('_id' => 'second', 'type' => 'test2', 'param' => 'hello2'),
			array('_id' => 'third', 'type' => 'test', 'param' => 'hello3')
		);
		$this->client->storeDocs($docs);
		$test = $this->client->getList('test', 'list1', 'simple');
		$this->assertInternalType("array", $test);
		$this->assertEquals(count($test), 3);
		foreach ($test as $row) {
			$this->assertInternalType("object", $row);
			$this->assertObjectHasAttribute('id', $row);
			$this->assertObjectHasAttribute('key', $row);
			$this->assertObjectHasAttribute('value', $row);
		}

		$test = $this->client->startkey(array('test'))->endkey(array('test', array()))->getList('test', 'list1', 'simple');
		$this->assertInternalType("array", $test);
		$this->assertEquals(count($test), 2);
		foreach ($test as $row) {
			$this->assertInternalType("object", $row);
			$this->assertObjectHasAttribute('id', $row);
			$this->assertObjectHasAttribute('key', $row);
			$this->assertObjectHasAttribute('value', $row);
		}

		$test = $this->client->startkey(array('test2'))->endkey(array('test2', array()))->getForeignList('test2', 'list2', 'test', 'simple');
		$this->assertInternalType("array", $test);
		$this->assertEquals(count($test), 1);
		foreach ($test as $row) {
			$this->assertInternalType("object", $row);
			$this->assertObjectHasAttribute('id', $row);
			$this->assertObjectHasAttribute('key', $row);
			$this->assertObjectHasAttribute('value', $row);
			$this->assertEquals($row->value, 'test2');
		}

		$test = $this->client
				->startkey(array('test2'))
				->endkey(array('test2', array()))
				->include_docs(TRUE)
				->getForeignList('test2', 'list2', 'test', 'simple');
		$this->assertInternalType("array", $test);
		$this->assertEquals(count($test), 1);
		foreach ($test as $row) {
			$this->assertInternalType("object", $row);
			$this->assertObjectHasAttribute('id', $row);
			$this->assertObjectHasAttribute('key', $row);
			$this->assertObjectHasAttribute('value', $row);
			$this->assertObjectHasAttribute('doc', $row);
			$this->assertInternalType("object", $row->doc);
			$this->assertObjectHasAttribute('_id', $row->doc);
			$this->assertObjectHasAttribute('_rev', $row->doc);
			$this->assertEquals($row->value, 'test2');
		}

// 		print_r($test);
// 		$this->assertInternalType("object", $test);
// 		$this->assertObjectHasAttribute("doc",$test);
// 		$this->assertObjectHasAttribute("query_length",$test);
	}

}
