<?php

use PHPOnCouch\Couch;
use PHPOnCouch\Admin;
use PHPOnCouch\Client;
use PHPOnCouch\Document;
use PHPOnCouch\Replicator;
use \stdClass;

class ClientUpdateDocTest extends PHPUnit_Framework_TestCase
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

        $ddoc = new stdClass();
        $ddoc->_id = "_design/test";
        $ddoc->updates = array("test" => $this->updateFn);
        $this->client->storeDoc($ddoc);

        $doc = new stdClass();
        $doc->_id = "foo";
        $this->client->storeDoc($doc);

    }

    public function tearDown()
    {
        $this->client->deleteDatabase();
    }

    private $updateFn = <<<EOT
      function(doc,req) {
        var resp = {query:null,form:null};
        if ( "query" in req ) {
          resp.query = req.query;
        }
        if ( "form" in req ) {
          resp.form = req.form;
        }
        return [doc,{
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify(resp)
          }];
      }
EOT;


    public function testUpdate()
    {

        $update = $this->client->updateDoc("test","test",array());

        $this->assertInternalType('object', $update);
        $this->assertObjectHasAttribute("query", $update);
        $this->assertObjectHasAttribute("form",  $update);
        $this->assertInternalType('object', $update->query);
        $this->assertInternalType('object', $update->form);
        $this->assertEquals(0, count((array) $update->query));
        $this->assertEquals(0, count((array) $update->form));

    }

    public function testUpdateQuery()
    {

        $update = $this->client->updateDoc("test", "test", array("var1"=> "val1/?\"", "var2" => "val2"));

        $this->assertInternalType('object', $update);

        $this->assertObjectHasAttribute("query", $update);
        $this->assertInternalType('object', $update->query);;
        $this->assertEquals(2, count((array) $update->query));

        $this->assertObjectHasAttribute("var1", $update->query);
        $this->assertTrue("val1/?\"" === $update->query->var1);

        $this->assertObjectHasAttribute("form", $update);
        $this->assertInternalType('object', $update->form);
        $this->assertEquals(0, count((array) $update->form));

    }

    public function testUpdateForm()
    {

        $update = $this->client->updateDocFullAPI("test", "test",array(
            "data" => array("var1" => "val1/?\"", "var2" => "val2")
        ));

        $this->assertInternalType('object', $update);

        $this->assertObjectHasAttribute("query", $update);
        $this->assertInternalType('object', $update->query);;
        $this->assertEquals(0, count((array) $update->query));

        $this->assertObjectHasAttribute("form", $update);
        $this->assertInternalType('object', $update->form);;
        $this->assertEquals(2, count((array) $update->form));
        $this->assertObjectHasAttribute("var1", $update->form);
        $this->assertTrue("val1/?\"" === $update->form->var1);

    }

}
