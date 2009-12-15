<?PHP
/*
Copyright (C) 2009  Mickael Bailly

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

function testme () {
	echo "ping\n";
}


/**
* CouchDB client class
*
* This class implements all required methods to use with a 
* CouchDB server
*
*
*/
class couchClient extends couch {

	/**
	* @var string database name
	*/
	protected $dbname = '';
	/**
	* @var array CouchDB view query options
	*/
	protected $view_query = array();
	/**
	* @var array CouchDB changes query options
	*/
	protected $changes_query = array();
	/**
	* @var bool option to return couchdb view results as couchDocuments objects
	*/
	protected $results_as_cd = false;

	/**
	* @var boolean tell if documents shall be returned as arrays instead of objects
	*/
	protected $results_as_array = false;


	/**
	* @var array list of properties beginning with '_' and allowed in CouchDB objects
	*/
	public static $allowed_underscored_properties = array('_id','_rev','_attachments');

	/**
	* class constructor
	*
	* @param string $dsn CouchDB server data source name (eg. http://localhost:5984)
	*	@param integer $port CouchDB server port
	* @param string $dbname CouchDB database name
	*/
	public function __construct($dsn, $dbname) {
		if ( !strlen($dbname) )	throw new InvalidArgumentException("Database name can't be empty");
		parent::__construct($dsn);
		$this->dbname = $dbname;
	}

	/**
	* helper method to execute the following algorithm :
	*
	* query the couchdb server
	* test the status_code
	* return the response body on success, throw an exception on failure
	*
	* @param string $method HTTP method (GET, POST, ...)
	* @param string $url URL to fetch
	* @param $array $allowed_status_code the list of HTTP response status codes that prove a successful request
	* @param array $parameters additionnal parameters to send with the request
	* @param string|object|array $data the request body. If it's an array or an object, $data is json_encode()d
	*/
	protected function _queryAndTest ( $method, $url,$allowed_status_codes, $parameters = array(),$data = NULL ) {
		$raw = $this->query($method,$url,$parameters,$data);
		$response = $this->parseRawResponse($raw, $this->results_as_array);
		$this->results_as_array = false;
		if ( in_array($response['status_code'], $allowed_status_codes) ) {
			return $response['body'];
		}
		throw new couchException($raw);
		return FALSE;
	}

	/**
	*list all databases on the CouchDB server
	*
	* @return object databases list
	*/
	public function listDatabases ( ) {
		return $this->_queryAndTest ('GET', '/_all_dbs', array(200));
	}

	/**
	*create the database
	*
	* @return object creation infos
	*/
	public function createDatabase ( ) {
		return $this->_queryAndTest ('PUT', '/'.urlencode($this->dbname), array(201));
	}

	/**
	*delete the database
	*
	* @return object creation infos
	*/
	public function deleteDatabase ( ) {
		return $this->_queryAndTest ('DELETE', '/'.urlencode($this->dbname), array(200));
	}

	/**
	*get database infos
	*
	* @return object database infos
	*/
	public function getDatabaseInfos ( ) {
		return $this->_queryAndTest ('GET', '/'.urlencode($this->dbname), array(200));
	}

	/**
	*return database uri
	*
	* example : http://couch.server.com:5984/mydb
	*
	* @return string database URI
	*/
	public function getDatabaseUri() {
		return $this->dsn.'/'.$this->dbname;
	}

	/**
	* test if the database already exists
	*
	* @return boolean wether or not the database exist
	*/
	public function databaseExists () {
		try {
			$back = $this->getDatabaseInfos();
			return TRUE;
		} catch ( Exception $e ) {
			// if status code = 404 database does not exist
			if ( $e->getCode() == 404 )   return FALSE;
			// we met another exception so we throw it
			throw $e;
		}
	}

	/**
	*CouchDb changes option
	*
	*
	* @link http://books.couchdb.org/relax/reference/change-notifications
	* @param integer $value sequence number
	* @return couchClient $this
	*/
	public function since($value) {
		$this->changes_query['since']=(int)$value;
		return $this;
	}

	/**
	*CouchDb changes option
	*
	*
	* @link http://books.couchdb.org/relax/reference/change-notifications
	* @param integer $value heartbeat in milliseconds
	* @return couchClient $this
	*/
	public function heartbeat($value) {
		$this->changes_query['heartbeat']=(int)$value;
		return $this;
	}

	/**
	*CouchDb changes option
	*
	*
	* @link http://books.couchdb.org/relax/reference/change-notifications
	* @param string $value feed type
	* @param callable $continuous_callback in case of a continuous feed, the callback to be executed on new event reception
	* @return couchClient $this
	*/
	public function feed($value,$continuous_callback = null) {
		if ( $value == 'longpoll' ) {
			$this->changes_query['feed'] = $value;
		}elseif ( $value == 'continuous' ) {
			$this->changes_query['feed'] = $value;
			$this->changes_query['continuous_feed'] = $continuous_callback;
		} elseif (!empty($this->changes_query['feed']) ) {
			unset($this->changes_query['feed']);
		}
		return $this;
	}
	

	/**
	*CouchDb changes option
	*
	*
	* @link http://books.couchdb.org/relax/reference/change-notifications
	* @param string $value designdocname/filtername
	* @return couchClient $this
	*/
	public function filter($value,$additional_query_options = array() ) {
		if ( strlen(trim($value)) ) {
			$this->changes_query['filter']=trim($value);
			$this->changes_query = array_merge($additional_query_options,$this->changes_query);
		}
		return $this;
	}

	/**
	*CouchDb changes option
	*
	*
	* @link http://books.couchdb.org/relax/reference/change-notifications
	* @param string $value 'all_docs' to switch style
	* @return couchClient $this
	*/
	public function style($value) {
		if ( $value != 'all_docs' ) {
			if ( !empty($this->changes_query['style']) )
				unset($this->changes_query['style']);
		} else {
			$this->changes_query['style'] = 'all_docs';
		}
		return $this;
	}


	/**
	* fetch database changes
	*
	* @return object CouchDB changes response
	*/
	public function getChanges() {
		if ( !empty($this->changes_query['feed']) && $this->changes_query['feed'] == 'continuous' ) {
			return $this->_continuousChanges();
		}
		$url = '/'.urlencode($this->dbname).'/_changes';
		$opts = $this->changes_query;
		$this->changes_query = array();
		return $this->_queryAndTest ('GET', $url, array(200,201),$opts);
	}


	/**
	* Internal wrapper of a changes request in continuous mode
	*
	*
	*/
	protected function _continuousChanges() {
		$url = '/'.urlencode($this->dbname).'/_changes';
		$opts = $this->changes_query;
		$this->changes_query = array();
		$callable = $opts['continuous_feed'];
		unset($opts['continuous_feed']);
		return $this->continuousQuery($callable,'GET',$url,$opts);
	}


	/**
	* fetch a CouchDB document
	*
	* @param string $id document id
	* @return object CouchDB document
	*/
	public function getDoc ($id) {
		if ( !strlen($id) )
			throw new InvalidArgumentException ("Document ID is empty");

		if ( preg_match('/^_design/',$id) )
			$url = '/'.urlencode($this->dbname).'/_design/'.urlencode(str_replace('_design/','',$id));
		else
			$url = '/'.urlencode($this->dbname).'/'.urlencode($id);

		$back = $this->_queryAndTest ('GET', $url, array(200));
		if ( !$this->results_as_cd ) {
			return $back;
		}
		$this->results_as_cd = false;
		$c = new  couchDocument($this);
		return $c->loadFromObject($back);
	}

	/**
	* store a CouchDB document
	*
	* @param object $doc document to store
	* @return object CouchDB document storage response
	*/
	public function storeDoc ( $doc ) {
		if ( !is_object($doc) )	throw new InvalidArgumentException ("Document should be an object");
		foreach ( array_keys(get_object_vars($doc)) as $key ) {
			if ( substr($key,0,1) == '_' AND !in_array($key,couchClient::$allowed_underscored_properties) )
				throw new InvalidArgumentException("Property $key can't begin with an underscore");
		}
		$method = 'POST';
		$url  = '/'.urlencode($this->dbname);
		if ( !empty($doc->_id) )    {
			$method = 'PUT';
			$url.='/'.urlencode($doc->_id);
		}
		return $this->_queryAndTest ($method, $url, array(200,201),array(),$doc);
	}

	/**
	* store many CouchDB documents
	*
	* @link http://wiki.apache.org/couchdb/HTTP_Bulk_Document_API
	* @param object $docs array of documents to store
	* @param boolean $all_or_nothing set the bulk update type to "all or nothing"
	* @return object CouchDB bulk document storage response
	*/
	public function storeDocs ( $docs, $all_or_nothing = false ) {
		if ( !is_array($docs) )	throw new InvalidArgumentException ("docs parameter should be an array");
		/*
			create the query content
		*/
		$request = array('docs'=>array());
		foreach ( $docs as $doc ) {
			if ( $doc instanceof couchDocument ) {
				$request['docs'][] = $doc->getFields();
			} else {
				$request['docs'][] = $doc;
			}
		}
		if ( $all_or_nothing ) {
			$request['all_or_nothing'] = true;
		}

		$method = 'POST';
		$url  = '/'.urlencode($this->dbname).'/_bulk_docs';

		return $this->_queryAndTest ($method, $url, array(200,201),array(),$request);
	}

	/**
	* copy a CouchDB document
	*
	* @param string $id id of the document to copy
	* @param string $new_id id of the new document
	* @return object CouchDB document storage response
	*/
	public function copyDoc($id,$new_id) {
		if ( !strlen($id) )
			throw new InvalidArgumentException ("Document ID is empty");
		if ( !strlen($new_id) )
			throw new InvalidArgumentException ("New document ID is empty");

		$method = 'COPY';
		$url  = '/'.urlencode($this->dbname);
		$url.='/'.urlencode($id);
		return $this->_queryAndTest ($method, $url, array(200,201),array(),$new_id);
	}

	/**
	* store a CouchDB attachment
	*
	* in this case the attachment content is in a PHP variable
	*
	* @param object $doc doc to store the attachment in
	* @param string $data attachment content
	* @param string $filename attachment name
	* @param string $content_type attachment content type
	* @return object CouchDB attachment storage response
	*/
	public function storeAsAttachment ($doc,$data,$filename,$content_type = 'application/octet-stream') {
		if ( !is_object($doc) )	throw new InvalidArgumentException ("Document should be an object");
		if ( !$doc->_id )       throw new InvalidArgumentException ("Document should have an ID");
		$url  = '/'.urlencode($this->dbname).'/'.urlencode($doc->_id).'/'.urlencode($filename);
		if ( $doc->_rev ) $url.='?rev='.$doc->_rev;
		$raw = $this->storeAsFile($url,$data,$content_type);
		$response = $this->parseRawResponse($raw, $this->results_as_array);
		$this->results_as_array = false;
		return $response['body'];
	}

	/**
	* store a CouchDB attachment
	*
	* in this case the attachment is a file on the harddrive
	*
	* @param object $doc doc to store the attachment in
	* @param string $file file to attach (complete path on the harddrive)
	* @param string $filename attachment name
	* @param string $content_type attachment content type
	* @return object CouchDB attachment storage response
	*/
	public function storeAttachment ($doc,$file,$content_type = 'application/octet-stream',$filename = null) {
		if ( !is_object($doc) )	throw new InvalidArgumentException ("Document should be an object");
		if ( !$doc->_id )       throw new InvalidArgumentException ("Document should have an ID");
		if ( !is_file($file) )  throw new InvalidArgumentException ("File $file does not exist");
		$url  = '/'.urlencode($this->dbname).'/'.urlencode($doc->_id).'/';
		$url .= empty($filename) ? basename($file) : $filename ;
		if ( $doc->_rev ) $url.='?rev='.$doc->_rev;
		$raw = $this->storeFile($url,$file,$content_type);
		$response = $this->parseRawResponse($raw, $this->results_as_array);
		return $response['body'];
	}

	/**
	* delete a CouchDB attachment from a document
	*
	* @param object $doc CouchDB document
	* @param string $attachment_name name of the attachment to delete
	* @return object CouchDB attachment removal response
	*/
	public function deleteAttachment ($doc,$attachment_name ) {
		if ( !is_object($doc) )	throw new InvalidArgumentException ("Document should be an object");
		if ( !$doc->_id )       throw new InvalidArgumentException ("Document should have an ID");
		if ( !strlen($attachment_name) )  throw new InvalidArgumentException ("Attachment name not set");
		$url  = '/'.urlencode($this->dbname).
				'/'.urlencode($doc->_id).
				'/'.urlencode($attachment_name);
		return $this->_queryAndTest ('DELETE', $url, array(200),array('rev'=>$doc->_rev));
	}

	/**
	* remove a document from the database
	*
	* @param object $doc document to remove
	* @return object CouchDB document removal response
	*/
	public function deleteDoc ( $doc ) {
		if ( !is_object($doc) )	throw new InvalidArgumentException ("Document should be an object");
		if ( empty($doc->_id)  OR empty($doc->_rev) )    {
			throw new Exception("Document should contain _id and _rev");
			return FALSE;
		}
		$url = '/'.urlencode($this->dbname).'/'.urlencode($doc->_id).'?rev='.urlencode($doc->_rev);
		return $this->_queryAndTest ('DELETE', $url, array(200));
	}


/*

CouchDB views : Please read http://wiki.apache.org/couchdb/HTTP_view_API

This class provides method chaining for query options. As an example :

$view_response = $couchClient->limit(50)->include_docs(TRUE)->getView('blog_posts','order_by_date');



*/





	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param mixed $value any json encodable thing
	* @return couchClient $this
	*/
	public function key($value) {
		$this->view_query['key'] = json_encode($value);
		return $this;
	}

	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param mixed $value an array of JSON encodable things
	* @return couchClient $this
	*/
	public function keys($value) {
		// we don't encode value here, we'll prep it before view query
		if ( is_array($value) ) {
			$this->view_query['keys'] = $value;
		}
		return $this;
	}

	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param mixed $value any json encodable thing
	* @return couchClient $this
	*/
	public function startkey($value) {
		$this->view_query['startkey'] = json_encode($value);
		return $this;
	}

	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param mixed $value any json encodable thing
	* @return couchClient $this
	*/
	public function endkey($value) {
		$this->view_query['endkey'] = json_encode($value);
		return $this;
	}

	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param string $value document id
	* @return couchClient $this
	*/
	public function startkey_docid($value) {
		$this->view_query['startkey_docid'] = (string)$value;
		return $this;
	}

	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param string $value document id
	* @return couchClient $this
	*/
	public function endkey_docid($value) {
		$this->view_query['endkey_docid'] = (string)$value;
		return $this;
	}


	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param ineteger $value maximum number of items to fetch
	* @return couchClient $this
	*/
	public function limit($value) {
		$this->view_query['limit'] = (int)$value;
		return $this;
	}

	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param string $value has to be 'ok'
	* @return couchClient $this
	*/
	public function stale($value) {
		if ( $value == 'ok' )
		$this->view_query['stale'] = $value;
		return $this;
	}

	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param boolean $value order in descending
	* @return couchClient $this
	*/
	public function descending($value) {
		$this->view_query['descending'] = json_encode((boolean)$value);
		return $this;
	}

	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param int $value number of items to skip
	* @return couchClient $this
	*/
	public function skip($value) {
		$this->view_query['skip'] = (int)$value;
		return $this;
	}

	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param boolean $value whether to group the results
	* @return couchClient $this
	*/
	public function group($value) {
		$this->view_query['group'] = json_encode((boolean)$value);
		return $this;
	}

	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param boolean $value whether to execute the reduce function (if any)
	* @return couchClient $this
	*/
	public function reduce($value) {
		$this->view_query['reduce'] = json_encode((boolean)$value);
		return $this;
	}

	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param boolean $value whether to include complete documents in the response
	* @return couchClient $this
	*/
	public function include_docs($value) {
		$this->view_query['include_docs'] = json_encode((boolean)$value);
		return $this;
	}

	/**
	* returns couchDB results as couchDocuments objects
	*
	* implies include_docs(true)
	*
    * cannot be used in conjunction with asArray()
    *
	* when view result is parsed, view documents are translated to objects and sent back
	*
	* @view  results_as_couchDocuments()
	* @return couchClient $this
	*
	*/
	public function asCouchDocuments() {
		$this->results_as_cd = true;
        $this->results_as_array = false;
		return $this;
	}

	/**
    * returns couchDB results as array
    *
    * cannot be used in conjunction with asCouchDocuments()
    *
    * @return couchClient $this
    */
    public function asArray() {
        $this->results_as_array = true;
        $this->results_as_cd = false;
        return $this;
    }

	/**
	* lookup $this->view_query and prepare view request
	*
	*
	* @return array [ HTTP method , array of view options, data ]
	*/
	protected function _prepare_view_query() {
		$view_query = $this->view_query;
		$this->view_query = array();
		$method = 'GET';
		$data = null;
		if ( isset($view_query['keys']) ) {
			$method = 'POST';
			$data = json_encode(array('keys'=>$view_query['keys']));
			unset($view_query['keys']);
		}
		return array ( $method, $view_query, $data );
	}

    /**
	* request a view from the CouchDB server
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param string $id design document name (without _design)
	* @param string $name view name
	* @return object CouchDB view query response
	*/
	public function getView ( $id, $name ) {
		if ( !$id OR !$name )    throw new InvalidArgumentException("You should specify view id and name");
		$url = '/'.urlencode($this->dbname).'/_design/'.urlencode($id).'/_view/'.urlencode($name);
		if ( $this->results_as_cd )		$this->include_docs(true);
// 		$view_query = $this->view_query;
		$results_as_cd = $this->results_as_cd;
// 		$this->view_query = array();
		$this->results_as_cd = false;

		list($method, $view_query, $data) = $this->_prepare_view_query();

		if ( ! $results_as_cd )
			return $this->_queryAndTest ($method, $url, array(200),$view_query,$data);

		return $this->resultsToCouchDocuments (
			$this->_queryAndTest ($method, $url, array(200),$view_query,$data)
		);
	}
	/**
	* returns couchDB view results as couchDocuments objects
	*
	* - for string view keys, the object is found on "view key" index
	*			ex : view returns
	*			<code>[ "client" : null , "client2" : null ]</code>
	* 		is translated to :
	*			array ( 'client' => array(couchDocument) , 'client2' => array(couchDocument) )
	*
	* - for array view keys, the object key in the result array is the last key of the view
	*			ex : view returns
	*			<code>[ [ "#44556643", "client" ] : null , [ "#65745767566","client2" : null ]</code>
	* 		is translated to :
	*			array ( 'client' => array(couchDocument) , 'client2' => array(couchDocument) )
	*
	*	@param stdClass couchDb view resultset
	* @return array array of couchDocument objects
	*/
	public function resultsToCouchDocuments ( $results ) {
		if ( !$results->rows or !is_array($results->rows) )	return FALSE;
		$back = array();
		foreach ( $results->rows as $row ) {	// should have $row->key & $row->doc
			if ( !$row->key or !$row->doc ) 	return false;
			// create couchDocument
			$cd = new couchDocument($this);
			$cd->loadFromObject($row->doc);
			
			// set key name
			if ( is_string($row->key) ) $key = $row->key;
			elseif ( is_array($row->key) ) {
				if ( !is_array(end($row->key)) && !is_object(end($row->key)) ) 
					$key = end($row->key);
				else
					continue;
			}
			
			// set value in result array
			if ( isset($back[$key]) ) {
				if ( is_array($back[$key]) ) 	$back[$key][] = $cd;
				else													$back[$key]   = array($back[$key],$cd);
			} else {
				$back[$key] = $cd;
			}
		}
		return $back;
	}

	/**
	* request a list from the CouchDB server
	*
	* @link http://wiki.apache.org/couchdb/Formatting_with_Show_and_List
	* @param string $id design document name (without _design)
	* @param string $name list name
	* @param string $view_name view name
	* @param array $additionnal_parameters some other parameters to send in the query
	* @return object CouchDB list query response
	*/
	public function getList ( $id, $name, $view_name, $additionnal_parameters = array() ) {
		if ( !$id OR !$name )    throw new InvalidArgumentException("You should specify list id and name");
		if ( !$view_name )    throw new InvalidArgumentException("You should specify view name");
		$url = '/'.urlencode($this->dbname).'/_design/'.urlencode($id).'/_list/'.urlencode($name).'/'.urlencode($view_name);
// 		$view_query = $this->view_query;
		$this->results_as_cd = false;
// 		$this->view_query = array();

		list($method, $view_query, $data) = $this->_prepare_view_query();

		if ( is_array($additionnal_parameters) && count($additionnal_parameters) ) {
			$view_query = array_merge($additionnal_parameters,$view_query);
		}
		return $this->_queryAndTest ($method, $url, array(200),$view_query,$data);
	}

	/**
	* request a show from the CouchDB server
	*
	* @link http://wiki.apache.org/couchdb/Formatting_with_Show_and_List
	* @param string $id design document name (without _design)
	* @param string $name show name
	* @param string $doc_id id of the couchDB document (can be null !)
	* @param array $additionnal_parameters some other parameters to send in the query
	* @return object CouchDB list query response
	*/
	public function getShow ( $id, $name, $doc_id = null, $additionnal_parameters = array() ) {
		if ( !$id OR !$name )    throw new InvalidArgumentException("You should specify list id and name");
		$url = '/'.urlencode($this->dbname).'/_design/'.urlencode($id).'/_show/'.urlencode($name);
		if ( $doc_id )	$url.='/'.urlencode($doc_id);
		return $this->_queryAndTest ('GET', $url, array(200), $additionnal_parameters);
	}

	/**
	* returns all documents contained in the database
	*
	*
	* @return object CouchDB _all_docs response
	*/
	public function getAllDocs ( ) {
		$url = '/'.urlencode($this->dbname).'/_all_docs';
// 		$view_query = $this->view_query;
// 		$this->view_query = array();
// 		$method = 'GET';
// 		$data = null;
// 		if ( count($keys) ) {
// 			$method = 'POST';
// 			$data = json_encode(array('keys'=>$keys));
// 		}
		list($method, $view_query, $data) = $this->_prepare_view_query();
		return $this->_queryAndTest ($method, $url, array(200),$view_query,$data);
	}

	/**
	* returns all documents contained associated wityh a sequence number
	*
	* @return object CouchDB _all_docs_by_seq response
	*/
	public function getAllDocsBySeq () {
		$url = '/'.urlencode($this->dbname).'/_all_docs_by_seq';
// 		$view_query = $this->view_query;
// 		$this->view_query = array();
		list($method, $view_query, $data) = $this->_prepare_view_query();
		return $this->_queryAndTest ($method, $url, array(200),$view_query,$data);
	}

	/**
	* returns a/some universally unique identifier(s)
	*
	*
	* @param integer $count the number of uuids to return
	* @return array|false an array of uuids on success, false on failure.
	*/
	public function getUuids($count = 1) {
		$count=(int)$count;
		if ( $count < 1 ) throw new InvalidArgumentException("Uuid count should be greater than 0");
		$url = '/'.urlencode($this->dbname).'/_uuids?count='+$count;
		$back = $this->_queryAndTest ('GET', $url, array(200),$view_query);
		if ( $back && property_exists($back,'uuids') ) {
			return $back->uuids;
		}
		return false;
	}

}

/**
* customized Exception class for CouchDB errors
*
* this class uses : the Exception message to store the HTTP message sent by the server
* the Exception code to store the HTTP status code sent by the server
* and adds a method getBody() to fetch the body sent by the server (if any)
*
*/
class couchException extends Exception {
    // couchDB response once parsed
	protected $couch_response = array();

	/**
	*class constructor
	*
	* @param string $raw_response HTTP response from the CouchDB server
	*/
	function __construct($raw_response) {
		$this->couch_response = couch::parseRawResponse($raw_response);
		parent::__construct($this->couch_response['status_message'], $this->couch_response['status_code']);
	}

	/**
	* returns CouchDB server response body (if any)
	*
	* if the response's "Content-Type" is set to "application/json", the
	* body is json_decode()d
	*
	* @return string|object|null CouchDB server response
	*/
	function getBody() {
		return $this->couch_response['body'];
	}
}

