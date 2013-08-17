<?php

use PHPOnCouch\Couch;
use PHPOnCouch\Admin;
use PHPOnCouch\Client;
use PHPOnCouch\Document;
use PHPOnCouch\Replicator;
use \stdClass;

class ClientListTest extends PHPUnit_Framework_TestCase
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

    public function testList()
    {

        $doc = new Document($this->client);
        $doc->_id = "_design/test";

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

        $doc = new Document($this->client);
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

        $this->assertInternalType('array', $test);
        $this->assertEquals(count($test), 3);

        $common_tests = function($row) {
            $this->assertInternalType('object', $row);
            $this->assertObjectHasAttribute('id',    $row);
            $this->assertObjectHasAttribute('key',   $row);
            $this->assertObjectHasAttribute('value', $row);
        };

        foreach ($test as $row) {
            $common_tests($row);
        }

        $test = $this->client->startkey(array('test'))
                             ->endkey(array('test', array()))
                             ->getList('test', 'list1', 'simple');

        $this->assertInternalType('array', $test);
        $this->assertEquals(count($test), 2);

        foreach( $test as $row) {
            $common_tests($row);
        }

        $test = $this->client->startkey(array('test2'))
                             ->endkey(array('test2', array()))
                             ->getForeignList('test2', 'list2', 'test', 'simple');

        $this->assertInternalType('array', $test);
        $this->assertEquals(count($test), 1);

        foreach ($test as $row) {
            $common_tests($row);
            $this->assertEquals($row->value, 'test2');
        }

        $test = $this->client
                ->startkey( array('test2') )
                ->endkey( array('test2', array()) )
                ->include_docs(TRUE)
                ->getForeignList('test2','list2','test','simple');

        $this->assertInternalType('array', $test);
        $this->assertEquals(count($test), 1);

        foreach( $test as $row ) {
            $common_tests($row);
            $this->assertObjectHasAttribute('doc',$row);
            $this->assertInternalType('object', $row->doc);
            $this->assertObjectHasAttribute('_id',$row->doc);
            $this->assertObjectHasAttribute('_rev',$row->doc);
            $this->assertEquals($row->value,'test2');
        }

    }

}
