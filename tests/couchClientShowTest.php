<?php

// error_reporting(E_STRICT);
error_reporting(E_ALL);

use PHPOnCouch\CouchClient,
	PHPOnCouch\CouchDocument,
	PHPOnCouch\CouchAdmin;

require_once join(DIRECTORY_SEPARATOR, [__DIR__, '_config', 'config.php']);

class couchClientShowTest extends PHPUnit_Framework_TestCase
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

	public function testShow()
	{
		$doc = new CouchDocument($this->aclient);
		$doc->_id = "_design/test";
		$show = array(
			"simple" => "function (doc, ctx) {
				ro = {body: ''};
				if ( ! doc ) {
					ro.body = 'no document';
				} else {
					ro.body = 'document: '+doc._id;
				}
				ro.body += ' ';
				var len = 0;
				for ( var k in ctx.query ) {
					len++;
				}
				ro.body += len;
				return ro;
			}",
			"json" => "function (doc, ctx) {
				ro = {body: ''};
				back = {doc: null};
				if ( doc ) {
					back.doc = doc._id;
				}
				var len = 0;
				for ( var k in ctx.query ) {
					len++;
				}
				back.query_length = len;
				ro.body = JSON.stringify(back);
				ro.headers = { \"content-type\": 'application/json' };
				return ro;
			}"
		);
		$doc->shows = $show;
		$test = $this->client->getShow("test", "simple", "_design/test");
		$this->assertEquals($test, "document: _design/test 0");
		$test = $this->client->getShow("test", "simple", "_design/test", array("param1" => "value1"));
		$this->assertEquals($test, "document: _design/test 1");
		$test = $this->client->getShow("test", "simple", null);
		$this->assertEquals($test, "no document 0");
		$test = $this->client->getShow("test", "simple", null, array("param1" => "value1"));
		$this->assertEquals($test, "no document 1");
		$test = $this->client->getShow("test", "json", null);
		$this->assertInternalType("object", $test);
		$this->assertObjectHasAttribute("doc", $test);
		$this->assertObjectHasAttribute("query_length", $test);
	}

}
