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
	* @var array query parameters
	*/
	protected $query_parameters = array();

	/**
	* @var array CouchDB query options definitions
	*
	* key is the couchClient method (mapped with __call)
	* value is a hash containing :
	*	- name : the query option name (couchdb side)
	*	- filter : the type of filter to apply to the value (ex to force a cast to an integer ...)
	*/
	protected $query_defs = array (
		"since" 			=> array ("name" => "since", "filter" => "int"),
		"heartbeat" 		=> array ("name" => "heartbeat", "filter" => "int"),
		"style"				=> array ("name" => "style", "filter"=>null),
		"conflicts" 		=> array ("name" => "conflicts", "filter"=>"staticValue", "staticValue"=> "true"),
		"revs" 				=> array ("name" => "revs", "filter"=>"staticValue", "staticValue"=> "true"),
		"revs_info" 		=> array ("name" => "revs_info", "filter"=>"staticValue", "staticValue"=> "true"),
		"rev" 				=> array ("name" => "rev", "filter"=>null),
		"key"				=> array ("name" => "key", "filter"=> "jsonEncode"),
		"keys"				=> array ("name" => "keys", "filter"=> "ensureArray"),
		"startkey"			=> array ("name" => "startkey", "filter"=> "jsonEncode"),
		"endkey"			=> array ("name" => "endkey", "filter"=> "jsonEncode"),
		"startkey_docid"	=> array ("name" => "startkey_docid", "filter"=> "string"),
		"endkey_docid"		=> array ("name" => "endkey_docid", "filter"=> "string"),
		"limit"				=> array ("name" => "limit", "filter"=> "int"),
		"stale" 			=> array ("name" => "stale", "filter"=>"staticValue", "staticValue"=> "ok"),
		"descending" 		=> array ("name" => "descending", "filter"=>"jsonEncodeBoolean"),
		"skip"				=> array ("name" => "skip", "filter"=> "int"),
		"group" 			=> array ("name" => "group", "filter"=>"jsonEncodeBoolean"),
		"group_level"		=> array ("name" => "group_level", "filter"=>"int"),
		"reduce" 			=> array ("name" => "reduce", "filter"=>"jsonEncodeBoolean"),
		"include_docs" 		=> array ("name" => "include_docs", "filter"=>"jsonEncodeBoolean"),
		"inclusive_end"		=> array ("name" => "inclusive_end", "filter"=>"jsonEncodeBoolean")
	);


	/**
	* @var bool option to return couchdb view results as couchDocuments objects
	*/
	protected $results_as_cd = false;

	/**
	* @var boolean tell if documents shall be returned as arrays instead of objects
	*/
	protected $results_as_array = false;


	/**
	* @var array list of properties beginning with '_' and allowed in CouchDB objects in a "store" type operation
	*/
	public static $allowed_underscored_properties = array('_id','_rev','_attachments','_deleted');

	/**
	* @var array list of properties beginning with '_' and that should be removed from CouchDB objects in a "store" type operation
	*/
	public static $underscored_properties_to_remove_on_storage = array('_conflicts','_revisions','_revs_info');

	/**
	* class constructor
	*
	* @param string $dsn CouchDB server data source name (eg. http://localhost:5984)
	* @param string $dbname CouchDB database name
	* @param array $options Additionnal configuration options
	*/
	public function __construct($dsn, $dbname, $options = array() ) {
		// in the case of a cookie based authentification we have to remove user and password infos from the DSN
		if ( array_key_exists("cookie_auth",$options) && $options["cookie_auth"] == "true" ) {
			$parts = parse_url($dsn);
			if ( !array_key_exists("user",$parts) || !array_key_exists("pass",$parts) ) {
				throw new Exception("You should provide a user and a password to use cookie based authentification");
			}
			$user = $parts["user"];
			$pass = $parts["pass"];
			$dsn = $parts["scheme"]."://".$parts["host"];
			$dsn.= array_key_exists("port",$parts) ? ":".$parts["port"] : "" ;
			$dsn.= array_key_exists("path",$parts) ? $parts["path"] : "" ;
		}
		$this->useDatabase($dbname);
		parent::__construct($dsn,$options);
		if ( array_key_exists("cookie_auth", $options) && $options["cookie_auth"] == "true" ) {
			$raw_data = $this->query("POST","/_session",null,http_build_query(array("name"=>$user,"password"=>$pass)), "application/x-www-form-urlencoded");
			list($headers, $body) = explode("\r\n\r\n", $raw_data,2);
			$headers_array=explode("\n",$headers);
			foreach ( $headers_array as $line ) {
				if ( strpos($line,"Set-Cookie: ") === 0 ) {
					$line = substr($line,12);
					$line = explode("; ",$line,2);
					$this->setSessionCookie(reset($line));
					break;
				}
			}
			if ( ! $this->sessioncookie ) {
				throw new Exception("Cookie authentification failed");
			}
		}
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
	* @param string $content_type set the content-type of the request
	*/
	protected function _queryAndTest ( $method, $url,$allowed_status_codes, $parameters = array(),$data = NULL, $content_type = NULL ) {
		$raw = $this->query($method,$url,$parameters,$data,$content_type);
		$response = $this->parseRawResponse($raw, $this->results_as_array);
		$this->results_as_array = false;
		if ( in_array($response['status_code'], $allowed_status_codes) ) {
			return $response['body'];
		}
		throw couchException::factory($response, $method, $url, $parameters);
		return FALSE;
	}

	function __call($name, $args) {
		if ( !array_key_exists($name,$this->query_defs) ) {
			throw new Exception("Method $name does not exist");
		}
		if ( $this->query_defs[$name]['filter'] == 'int' ) {
			$this->query_parameters[ $this->query_defs[$name]['name'] ] = (int)reset($args);
		} elseif ( $this->query_defs[$name]['filter'] == 'staticValue' ) {
			$this->query_parameters[ $this->query_defs[$name]['name'] ] = $this->query_defs[$name]['staticValue'];
		} elseif ( $this->query_defs[$name]['filter'] == 'jsonEncode' ) {
			$this->query_parameters[ $this->query_defs[$name]['name'] ] = json_encode(reset($args));
		} elseif ( $this->query_defs[$name]['filter'] == 'ensureArray' ) {
			if ( is_array(reset($args)) ) {
				$this->query_parameters[ $this->query_defs[$name]['name'] ] = reset($args);
			}
		} elseif ( $this->query_defs[$name]['filter'] == 'string' ) {
			$this->query_parameters[ $this->query_defs[$name]['name'] ] = (string)reset($args);
		} elseif ( $this->query_defs[$name]['filter'] == 'jsonEncodeBoolean' ) {
			$this->query_parameters[ $this->query_defs[$name]['name'] ] = json_encode((boolean)reset($args));
		} else {
			$this->query_parameters[ $this->query_defs[$name]['name'] ] = reset($args);
		}
// 		print_r($this->query_parameters);
		return $this;
	}

	/**
        * Set all CouchDB query options at once.
        * Any invalid options are ignored.
        *
        * @link http://wiki.apache.org/couchdb/HTTP_view_API
        * @param mixed $value any json encodable thing
        * @return couchClient $this
        */
	public function setQueryParameters(array $options) {
          foreach($options as $option=>$v) if (array_key_exists($option,$this->query_defs))
              $this->$option($v);
              return $this;
	}


	/**
	* set the name of the couchDB database to work on
	*
	* @param string $dbname name of the database
	* @return couchClient $this
	*/
	public function useDatabase( $dbname ) {
		if ( !strlen($dbname) )	throw new InvalidArgumentException("Database name can't be empty");
		if ( !$this->isValidDatabaseName($dbname) )	throw new InvalidArgumentException('Database name contains invalid characters. Only lowercase characters (a-z), digits (0-9), and any of the characters _, $, (, ), +, -, and / are allowed.');
		$this->dbname = $dbname;
		return $this;
	}

	/**
	* Tests a CouchDB database name and tell if it's a valid one
	*
	*
	* @param string $dbname name of the database to test
	* @return boolean true if the database name is correct
	*/
	public static function isValidDatabaseName ( $dbname ) {
		if ( $dbname == "_users" )	return true;
		if (  preg_match ( "@^[a-z][a-z0-9_\$\(\)\+\-/]*$@",$dbname) ) return true;
		return false;
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
	* return database name
	*
	* @return string database name
	*/
	public function getDatabaseName () {
		return $this->dbname;
	}

	/**
	* returns CouchDB server URI
	*
	* example : http://couch.server.com:5984
	*
	* @return string CouchDB Server URL
	*/
	public function getServerUri () {
		return $this->dsn;
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
	* launch a compact operation on the database
	*
	*
	* @return object CouchDB's compact response ( usually {"ok":true} )
	*/
	public function compactDatabase () {
		return $this->_queryAndTest ( "POST", '/'.urlencode($this->dbname).'/_compact', array(202) );
	}

	/**
	* launch a cleanup operation on database views
	*
	*
	* @return object CouchDB's cleanup views response ( usually {"ok":true} )
	*/
	public function cleanupDatabaseViews () {
		return $this->_queryAndTest ( "POST", '/'.urlencode($this->dbname).'/_view_cleanup', array(202) );
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
			$this->query_parameters['feed'] = $value;
		}elseif ( $value == 'continuous' ) {
			$this->query_parameters['feed'] = $value;
			$this->query_parameters['continuous_feed'] = $continuous_callback;
		} elseif (!empty($this->query_parameters['feed']) ) {
			unset($this->query_parameters['feed']);
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
			$this->query_parameters['filter']=trim($value);
			$this->query_parameters = array_merge($additional_query_options,$this->query_parameters);
		}
		return $this;
	}

	/**
	* fetch database changes
	*
	* @return object CouchDB changes response
	*/
	public function getChanges() {
		if ( !empty($this->query_parameters['feed']) && $this->query_parameters['feed'] == 'continuous' ) {
// 			return $this->_continuousChanges();
			$url = '/'.urlencode($this->dbname).'/_changes';
			$opts = $this->query_parameters;
			$this->query_parameters = array();
			$callable = $opts['continuous_feed'];
			unset($opts['continuous_feed']);
			return $this->continuousQuery($callable,'GET',$url,$opts);
		}
		$url = '/'.urlencode($this->dbname).'/_changes';
		$opts = $this->query_parameters;
		$this->query_parameters = array();
		return $this->_queryAndTest ('GET', $url, array(200,201),$opts);
	}


	/**
	* fetch multiple revisions at once
	*
	* @link http://wiki.apache.org/couchdb/HTTP_Document_API
	* @param array|all $value array of revisions to fetch, or special keyword all
	* @return couchClient $this
	*/
	public function open_revs ($value) {
		if ( is_string($value) && $value == 'all' ) {
			$this->query_parameters['open_revs'] = "all";
		} elseif ( is_array($value) ) {
			$this->query_parameters['open_revs'] = json_encode($value);
		}
		return $this;
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

		$doc_query = $this->query_parameters;
		$this->query_parameters = array();

		$back = $this->_queryAndTest ('GET', $url, array(200),$doc_query);
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
			if ( in_array($key,couchClient::$underscored_properties_to_remove_on_storage) ) {
				unset($doc->$key);
			}
			elseif ( substr($key,0,1) == '_' AND !in_array($key,couchClient::$allowed_underscored_properties) )
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
	* @param array $docs array of documents to store
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

		$url  = '/'.urlencode($this->dbname).'/_bulk_docs';
		return $this->_queryAndTest ('POST', $url, array(200,201),array(),$request);
	}


	/**
	* delete many CouchDB documents in a single HTTP request
	*
	* @link http://wiki.apache.org/couchdb/HTTP_Bulk_Document_API
	* @param array $docs array of documents to delete
	* @param boolean $all_or_nothing set the bulk update type to "all or nothing"
	* @return object CouchDB bulk document storage response
	*/
	public function deleteDocs ( $docs, $all_or_nothing = false ) {
		if ( !is_array($docs) )	throw new InvalidArgumentException ("docs parameter should be an array");
		/*
			create the query content
		*/
		$request = array('docs'=>array());
		foreach ( $docs as $doc ) {
			$destDoc = null;
			if ( $doc instanceof couchDocument )	$destDoc = $doc->getFields();
			else 									$destDoc = $doc;

			if ( is_array($destDoc) )	$destDoc['_deleted'] = true;
			else 						$destDoc->_deleted   = true;
			$request['docs'][] = $destDoc;
		}
		if ( $all_or_nothing ) {
			$request['all_or_nothing'] = true;
		}

		$url  = '/'.urlencode($this->dbname).'/_bulk_docs';
		return $this->_queryAndTest ('POST', $url, array(200,201),array(),$request);
	}


	/**
	* update a couchDB document through an Update Handler 
	* wrapper to $this->updateDocFullAPI
	*
	* @link http://wiki.apache.org/couchdb/Document_Update_Handlers
	* @param string $ddoc_id name of the design doc containing the update handler definition (without _design)
	* @param string $handler_name name of the update handler
	* @param array|object $params parameters to send to the update handler
	* @param string $doc_id id of the document to update (can be null)
	*/
	public function updateDoc ( $ddoc_id, $handler_name, $params, $doc_id = null ) {
		if ( !is_array($params) && !is_object($params) ) throw new InvalidArgumentException ("params parameter should be an array or an object");
		if ( is_object($params) )	$params = (array)$params;

		$options = array();
		if ( $doc_id ) $options["doc_id"] = $doc_id;
		if ( $params ) $options["params"] = $params;

		return $this->updateDocFullAPI($ddoc_id, $handler_name, $options);
	}


        /**
        * update a couchDB document through an Update Handler 
        *
        * @link http://wiki.apache.org/couchdb/Document_Update_Handlers
        * @param string $ddoc_id name of the design doc containing the update handler definition (without _design)
        * @param string $handler_name name of the update handler
	* @param array $options list of optionnal data to send to the couch update handler.
	*		- "params" : array|object of variables being sent in the URL ( /?foo=bar )
	*		- "data"   : string|array|object data being sent in the body of the request. 
	*				If data is an array or an object it's parsed through PHP http_build_query function
	*				and the content-type of the request is set to "application/x-www-form-urlencoded"
	*		- "Content-Type" : the http header "Content-Type" to send to the couch server
        * @param array|object $params parameters to send to the update handler
        * @param string $doc_id id of the document to update (can be null)
        */
	public function updateDocFullAPI ( $ddoc_id, $handler_name, $options = array() ) {
		$params = array();
		$data = null;
		$contentType = null;
                $method = 'PUT';
                $url  = '/'.urlencode($this->dbname).'/_design/'.urlencode($ddoc_id).'/_update/'.$handler_name.'/';
		if ( array_key_exists("doc_id",$options) && is_string($options["doc_id"]) ) {
                	$url .= urlencode($options["doc_id"]);
		}
		if ( array_key_exists("params",$options) && (is_array($options["params"]) || is_object($options["params"])) ) {
			$params = $options["params"];
		}
		if ( array_key_exists("Content-Type",$options) && is_string($options["Content-Type"]) ) {
			$contentType = $options["Content-Type"];
		}

		if ( array_key_exists("data",$options) ) {
			if ( is_string($options["data"]) ) {
				$data = $options["data"];
				if ( !$contentType ) $contentType = "application/x-www-form-urlencoded";
			} elseif ( is_array($options["data"]) || is_object($options["data"]) ) {
				$data = http_build_query($options["data"]);
				$contentType = "application/x-www-form-urlencoded";
			}
		}

                return $this->_queryAndTest ($method, $url, array(200,201),$params,$data,$contentType);
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
		$view_query = $this->query_parameters;
		$this->query_parameters = array();
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
		$results_as_cd = $this->results_as_cd;
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
		$this->results_as_cd = false;
		list($method, $view_query, $data) = $this->_prepare_view_query();

		if ( is_array($additionnal_parameters) && count($additionnal_parameters) ) {
			$view_query = array_merge($additionnal_parameters,$view_query);
		}
		return $this->_queryAndTest ($method, $url, array(200),$view_query,$data);
	}

	/**
	* request a list from the CouchDB server
	*
	* Beginning in CouchDB 0.11, the design doc where the list function is defined can be different from
	* the design doc where the view function is defined
	*
	* So if you got a design doc "_design/example1" with a defined view "view1", and
	* a design doc "_design/example2" with a defined list "list1", you can query the view view1 
	* and then pass it through the list list1 by using :
	*
	* getForeignList("example2","list1","example1","view1");
	*
	* @link http://wiki.apache.org/couchdb/Formatting_with_Show_and_List
	* @param string $id the name of the design document containing the list (without _design)
	* @param string $name list name
	* @param string $view_id the name of the design document containing the view (without _design)
	* @param string $view_name view name
	* @param array $additionnal_parameters some other parameters to send in the query
	* @return object CouchDB list query response
	*/
	public function getForeignList ( $id, $name, $view_id, $view_name, $additionnal_parameters = array() ) {
		if ( !$id OR !$name )    throw new InvalidArgumentException("You should specify list id and name");
		if ( !$view_id OR !$view_name )    throw new InvalidArgumentException("You should specify view id and view name");
		$url = '/'.urlencode($this->dbname).
				'/_design/'.urlencode($id).'/_list/'.urlencode($name).
				'/'.urlencode($view_id).'/'.urlencode($view_name);
		$this->results_as_cd = false;
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
	* request design doc views informations from the CouchDB server
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param string $id design document name (without _design)
	* @return object CouchDB view infos response
	*/
	public function getViewInfos ( $id ) {
		if ( !$id )    throw new InvalidArgumentException("You should specify view id");
		$url = '/'.urlencode($this->dbname).'/_design/'.urlencode($id).'/_info';
		return $this->_queryAndTest ("GET", $url, array(200));
	}


	/**
	* launch a compact operation on a database design document
	*
	* to compact views defined in _design/thedoc , use compactViews ("thedoc")
	*
	* @param string $id design document name (without _design)
	* @return object CouchDB's compact response ( usually {"ok":true} )
	*/
	public function compactViews ( $id ) {
		$id = preg_replace("@^_design/@","",$id);
// 		print_r("calling $id");
		return $this->_queryAndTest ( "POST", '/'.urlencode($this->dbname).'/_compact/'.urlencode($id), array(202) );
	}


	/**
	* launch a compact operation on all database design documents
	*
	* to compact views defined in _design/thedoc , use compactViews ("thedoc")
	*
	* @return void
	*/
	public function compactAllViews () {
		$response = $this->startkey('_design/')->endkey('_designa')->getAllDocs();
		if ( property_exists($response,'rows') && is_array($response->rows) ) {
			foreach ( $response->rows as $view_row ) {
				$this->compactViews($view_row->key);
			}
		}
	}


	/**
	* returns all documents contained in the database
	*
	*
	* @return object CouchDB _all_docs response
	*/
	public function getAllDocs ( ) {
		$url = '/'.urlencode($this->dbname).'/_all_docs';
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

		$url = '/_uuids';

		$back = $this->_queryAndTest ('GET', $url, array(200), array("count"=>$count) );
		if ( $back && property_exists($back,'uuids') ) {
			return $back->uuids;
		}
		return false;
	}
	
	/**
	* Synchronize database to disc
	*
	* @return object CouchDB document storage response
	*/
	public function ensureFullCommit() {
		$method = 'POST';
		$url = '/' . urlencode($this->dbname) . '/_ensure_full_commit';
		return $this->_queryAndTest($method, $url, array(200, 201));
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
	// CouchDB response codes we handle specialized exceptions
	protected static $code_subtypes = array(404=>'couchNotFoundException', 403=>'couchForbiddenException', 401=>'couchUnauthorizedException', 417=>'couchExpectationException');
	// more precise response problem
    protected static $status_subtypes = array('Conflict'=>'couchConflictException');
    // couchDB response once parsed
	protected $couch_response = array();

	/**
	*class constructor
	*
	* @param string|array $response HTTP response from the CouchDB server
	* @param string $method  the HTTP method
	* @param string $url the target URL
	* @param mixed $parameters the query parameters
	*/
	function __construct($response, $method = null, $url = null, $parameters = null) {
		$this->couch_response = is_string($response) ? couch::parseRawResponse($response) : $response;
		if (is_object($this->couch_response['body']) and isset($this->couch_response['body']->reason))
			$message = $this->couch_response['status_message'] . ' - ' . $this->couch_response['body']->reason;
		else
			$message = $this->couch_response['status_message'];
		if ( $method )	$message.= " ($method $url ".json_encode($parameters).')';
		parent::__construct($message, isset($this->couch_response['status_code']) ? $this->couch_response['status_code'] : null);
    }


	public static function factory($response, $method, $url, $parameters) {
		if (is_string($response)) $response = couch::parseRawResponse($response);
		if (!$response) return new couchNoResponseException();
		if (isset($response['status_code']) and isset(self::$code_subtypes[$response['status_code']]))
			return new self::$code_subtypes[$response['status_code']]($response, $method, $url, $parameters);
		elseif (isset($response['status_message']) and isset(self::$status_subtypes[$response['status_message']]))
			return new self::$status_subtypes[$response['status_message']]($response, $method, $url, $parameters);
		else
			return new self($response, $method, $url, $parameters);
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
		if ( isset($this->couch_response['body']) )
			return $this->couch_response['body'];
	}
}

class couchNoResponseException extends couchException {
	function __construct() {
		parent::__construct(array('status_message'=>'No response from server - '));
	}
}
class couchNotFoundException extends couchException {}
class couchForbiddenException extends couchException {}
class couchUnauthorizedException extends couchException {}
class couchExpectationException extends couchException {}
class couchConflictException extends couchException {}
