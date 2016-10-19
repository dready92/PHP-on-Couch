<?php

// error_reporting(E_STRICT);
error_reporting(E_ALL);

use PHPOnCouch\couchClient,
	PHPOnCouch\couchDocument,
	PHPOnCouch\couchAdmin,
	PHPOnCouch\Replicator;

require_once join(DIRECTORY_SEPARATOR,[__DIR__,'_config','config.php']);

class couchClientUpdateDocTest extends PHPUnit_Framework_TestCase
{

	private $host = 'localhost';
	private $port = '5984';
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
		$config = config::getInstance();
		$url = $config->getUrl($this->host, $this->port,null);
		$aUrl = $config->getUrl($this->host, $this->port, $config->getFirstAdmin());
		$this->client = new couchClient($url, 'couchclienttest');
		$this->aclient = new couchClient($aUrl, 'couchclienttest');
		try {
			$this->aclient->deleteDatabase();
		} catch (Exception $e) {
			
		}
		$this->aclient->createDatabase();

		$ddoc = new stdClass();
		$ddoc->_id = "_design/test";
		$ddoc->updates = array("test" => $this->updateFn);
		$this->aclient->storeDoc($ddoc);
		$doc = new stdClass();
		$doc->_id = "foo";
		$this->client->storeDoc($doc);
	}

	public function tearDown()
	{
		$this->client = null;
		$this->aclient = null;
	}

	public function testUpdate()
	{
		$update = $this->aclient->updateDoc("test", "test", array());
		$this->assertInternalType("object", $update);
		$this->assertObjectHasAttribute("query", $update);
		$this->assertInternalType("object", $update->query);
		$this->assertEquals(0, count((array) $update->query));
		$this->assertObjectHasAttribute("form", $update);
		$this->assertInternalType("object", $update->form);
		$this->assertEquals(0, count((array) $update->form));
	}

	public function testUpdateQuery()
	{
		$update = $this->aclient->updateDoc("test", "test", array("var1" => "val1/?\"", "var2" => "val2"));
		$this->assertInternalType("object", $update);
		$this->assertObjectHasAttribute("query", $update);
		$this->assertInternalType("object", $update->query);
		$this->assertEquals(2, count((array) $update->query));
		$this->assertObjectHasAttribute("var1", $update->query);
		$this->assertInternalType("string", $update->query->var1);
		$this->assertEquals("val1/?\"", $update->query->var1);



		$this->assertObjectHasAttribute("form", $update);
		$this->assertInternalType("object", $update->form);
		$this->assertEquals(0, count((array) $update->form));
	}

	public function testUpdateForm()
	{
		$update = $this->aclient->updateDocFullAPI("test", "test", array(
			"data" => array("var1" => "val1/?\"", "var2" => "val2")
		));
		$this->assertInternalType("object", $update);
		$this->assertObjectHasAttribute("query", $update);
		$this->assertInternalType("object", $update->query);
		$this->assertEquals(0, count((array) $update->query));
		$this->assertObjectHasAttribute("form", $update);
		$this->assertInternalType("object", $update->form);
		$this->assertEquals(2, count((array) $update->form));
		$this->assertObjectHasAttribute("var1", $update->form);
		$this->assertInternalType("string", $update->form->var1);
		$this->assertEquals("val1/?\"", $update->form->var1);
	}

}

