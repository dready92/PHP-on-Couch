<?php

use PHPOnCouch\Couch;
use PHPOnCouch\Admin;
use PHPOnCouch\Client;
use PHPOnCouch\Document;
use PHPOnCouch\Replicator;
use \stdClass;

class ClientShowTest extends PHPUnit_Framework_TestCase
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

    public function testShow()
    {

        $doc = new Document($this->client);
        $doc->_id= "_design/test";

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
        $test = $this->client->getShow("test", "simple", "_design/test");
        $this->assertEquals($test, "document: _design/test 0");

        $test = $this->client->getShow("test", "simple", "_design/test", array("param1" => "value1"));
        $this->assertEquals($test, "document: _design/test 1");

        $test = $this->client->getShow("test", "simple", null);
        $this->assertEquals($test, "no document 0");

        $test = $this->client->getShow("test", "simple", null, array("param1" => "value1"));
        $this->assertEquals($test, "no document 1");

        $test = $this->client->getShow("test", "json", null);

        $this->assertInternalType('object', $test);
        $this->assertObjectHasAttribute("doc", $test);
        $this->assertObjectHasAttribute("query_length", $test);

    }

}
