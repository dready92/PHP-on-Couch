<?php

// error_reporting(E_STRICT);
error_reporting(E_ALL);

require_once 'PHPUnit/Framework.php';

require_once "lib/couch.php";
require_once "lib/couchClient.php";
require_once "lib/couchDocument.php";
require_once "lib/couchReplicator.php";


class couchClientTest extends PHPUnit_Framework_TestCase
{

        private $couch_server = "http://localhost:5984/";

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
EOT
;


    public function setUp()
    {
        $this->client = new couchClient($this->couch_server,"couchclienttest");
        try {
            $this->client->deleteDatabase();
        } catch ( Exception $e) {}
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
        $this->client = null;
    }

	public function testUpdate () {
		$update = $this->client->updateDoc("test","test",array());
		$this->assertType("object", $update);
		$this->assertObjectHasAttribute("query",$update);
		$this->assertType("object", $update->query);
		$this->assertEquals(0, count((array)$update->query));
		$this->assertObjectHasAttribute("form",$update);
                $this->assertType("object", $update->form);
                $this->assertEquals(0, count((array)$update->form));

	}

	public function testUpdateQuery () {
		$update = $this->client->updateDoc("test","test",array("var1"=>"val1/?\"","var2"=>"val2"));
                $this->assertType("object", $update);
                $this->assertObjectHasAttribute("query",$update);
                $this->assertType("object", $update->query);
                $this->assertEquals(2, count((array)$update->query));
		$this->assertObjectHasAttribute("var1",$update->query);
                $this->assertType("string", $update->query->var1);
                $this->assertEquals("val1/?\"", $update->query->var1);



                $this->assertObjectHasAttribute("form",$update);
                $this->assertType("object", $update->form);
                $this->assertEquals(0, count((array)$update->form));
	}

	public function testUpdateForm () {
		$update = $this->client->updateDocFullAPI("test","test",array(
			"data"=> array("var1"=>"val1/?\"","var2"=>"val2")
		));
		$this->assertType("object", $update);
                $this->assertObjectHasAttribute("query",$update);
                $this->assertType("object", $update->query);
                $this->assertEquals(0, count((array)$update->query));
                $this->assertObjectHasAttribute("form",$update);
                $this->assertType("object", $update->form);
                $this->assertEquals(2, count((array)$update->form));
		$this->assertObjectHasAttribute("var1",$update->form);
                $this->assertType("string", $update->form->var1);
                $this->assertEquals("val1/?\"", $update->form->var1);

	}

}
?>
