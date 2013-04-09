<?php

// error_reporting(E_STRICT);
error_reporting(E_ALL);

class PhpOnCouchTest_ClientShowTest extends PHPUnit_Framework_TestCase
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


	public function testShow () {
		$doc = new PhpOnCouch_Document($this->client);
		$doc->_id="_design/test";
		$show = array (
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
		$test = $this->client->getShow("test","simple","_design/test");
		$this->assertEquals ( $test, "document: _design/test 0" );
		$test = $this->client->getShow("test","simple","_design/test",array("param1"=>"value1"));
		$this->assertEquals ( $test, "document: _design/test 1" );
		$test = $this->client->getShow("test","simple",null);
		$this->assertEquals ( $test, "no document 0" );
		$test = $this->client->getShow("test","simple",null,array("param1"=>"value1"));
		$this->assertEquals ( $test, "no document 1" );
		$test = $this->client->getShow("test","json",null);
		$this->assertType("object", $test);
		$this->assertObjectHasAttribute("doc",$test);
		$this->assertObjectHasAttribute("query_length",$test);
	}

}
