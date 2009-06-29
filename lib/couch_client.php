<?PHP

class couch_client extends couch {

  protected $dbname = '';
  protected $view_query = array();


	/**
	* class constructor
	*
	* @param string $hostname CouchDB server host
	*	@param integer $port CouchDB server port
	* @param string $dbname CouchDB database name
	*/
  public function __construct($hostname, $port, $dbname) {
    parent::__construct($hostname,$port);
    $this->dbname = $dbname;
  }

	/**
	* generic method to execute the following algorithm :
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
  protected function _query_and_test ( $method, $url,$allowed_status_codes, $parameters = array(),$data = NULL ) {
    $raw = $this->query($method,$url,$parameters,$data);
    $response = $this->parse_raw_response($raw);
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
  public function dbs_list ( ) {
    return $this->_query_and_test ('GET', '/_all_dbs', array(200));
  }

	/**
	*create the database
	*
	* @return object creation infos
	*/
  public function db_create ( ) {
    return $this->_query_and_test ('PUT', '/'.urlencode($this->dbname), array(201));
  }

	/**
	*delete the database
	*
	* @return object creation infos
	*/
  public function db_delete ( ) {
      return $this->_query_and_test ('DELETE', '/'.urlencode($this->dbname), array(200));
  }

	/**
	*get database infos
	*
	* @return object database infos
	*/
  public function db_infos ( ) {
    return $this->_query_and_test ('GET', '/'.urlencode($this->dbname), array(200));
  }

	/**
	* test if the database already exists
	*
	* @return boolean wether or not the database exist
	*/
  public function db_exists () {
    try {
      $back = $this->db_infos();
      return TRUE;
    } catch ( Exception $e ) {
      // si le status code est 404 la base n'éxiste pas
      if ( $e->getCode() == 404 )   return FALSE;
      // sinon il y a eu une autre erreur : nous remontons l'exception
      throw $e;
    }
  }

	/**
	* fetch a CouchDB document
	*
	* @param string $id document id
	* @return object CouchDB document
	*/
  public function doc_get ($id) {
		if ( !strlen($id) ) 
			throw new Exception ("Document ID is empty");

    if ( preg_match('/^_design/',$id) )
      $url = '/'.urlencode($this->dbname).'/_design/'.urlencode(str_replace('_design/','',$id));
    else
      $url = '/'.urlencode($this->dbname).'/'.urlencode($id);

    return $this->_query_and_test ('GET', $url, array(200));
  }

	/**
	* store a CouchDB document
	*
	* @param object $doc document to store
	* @return object CouchDB document storage response
	*/
  public function doc_store ( $doc ) {
		if ( !is_object($doc) )
			throw new Exception ("Document should be an object");
    $method = 'POST';
    $url  = '/'.urlencode($this->dbname);
    if ( !empty($doc->_id) )    {
      $method = 'PUT';
      $url.='/'.urlencode($doc->_id);
    }
    return $this->_query_and_test ($method, $url, array(200,201),array(),$doc);
  }

	/**
	* copy a CouchDB document
	*
	* @param string $id id of the document to copy
	* @param string $new_id id of the new document
	* @return object CouchDB document storage response
	*/
  public function doc_copy($id,$new_id) {
		if ( !strlen($id) )
			throw new Exception ("Document ID is empty");
		if ( !strlen($new_id) ) 
			throw new Exception ("New document ID is empty");

    $method = 'COPY';
    $url  = '/'.urlencode($this->dbname);
    $url.='/'.urlencode($id);
    return $this->_query_and_test ($method, $url, array(200,201),array(),$new_id);
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
  public function as_attachment_store($doc,$data,$filename,$content_type = 'application/octet-stream') {
		if ( !is_object($doc) )	throw new Exception ("Document should be an object");
    if ( !$doc->_id )       throw new Exception ("Document should have an ID");
    $url  = '/'.urlencode($this->dbname).'/'.urlencode($doc->_id).'/'.urlencode($filename);
    if ( $doc->_rev ) $url.='?rev='.$doc->_rev;
    $raw = $this->store_as_file($url,$data,$content_type);
    $response = $this->parse_raw_response($raw);
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
  public function attachment_store($doc,$file,$content_type = 'application/octet-stream',$filename = null) {
		if ( !is_object($doc) )	throw new Exception ("Document should be an object");
    if ( !$doc->_id )       throw new Exception ("Document should have an ID");
    if ( !is_file($file) )  throw new Exception ("File $file does not exist");
    $url  = '/'.urlencode($this->dbname).'/'.urlencode($doc->_id).'/';
    $url .= empty($filename) ? basename($file) : $filename ;
    if ( $doc->_rev ) $url.='?rev='.$doc->_rev;
    $raw = $this->store_file($url,$file,$content_type);
    $response = $this->parse_raw_response($raw);
    return $response['body'];
  }

	/**
	* delete a CouchDB attachment from a document
	*
	* @param object $doc CouchDB document
	* @param string $attachment_name name of the attachment to delete
	* @return object CouchDB attachment removal response
	*/
  public function attachment_delete($doc,$attachment_name ) {
		if ( !is_object($doc) )	throw new Exception ("Document should be an object");
    if ( !$doc->_id )       throw new Exception ("Document should have an ID");
    if ( !strlen($attachment_name) )  throw new Exception ("Attachment name not set");
    $url  = '/'.urlencode($this->dbname).
            '/'.urlencode($doc->_id).
            '/'.urlencode($attachment_name);
    $raw = $this->query('DELETE',$url,array("rev"=>$doc->_rev));
    $response = $this->parse_raw_response($raw);
    return $response['body'];
  }

	/**
	* remove a document from the database
	*
	* @param object $doc document to remove
	* @return object CouchDB document removal response
	*/
  public function doc_delete ( $doc ) {
		if ( !is_object($doc) )	throw new Exception ("Document should be an object");
    if ( empty($doc->_id)  OR empty($doc->_rev) )    {
      throw new Exception("Document should contain _id and _rev");
      return FALSE;
    }

    $url = '/'.urlencode($this->dbname).'/'.urlencode($doc->_id).'?rev='.urlencode($doc->_rev);
    return $this->_query_and_test ('DELETE', $url, array(200));
  }


/*

CouchDB views : Please read http://wiki.apache.org/couchdb/HTTP_view_API

This class provides method chaining for query options. As an example :

$view_response = $couch_client->limit(50)->include_docs(TRUE)->get_view('blog_posts','order_by_date');



*/



	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param mixed $value any json encodable thing
	* @return couch_client $this
	*/
  public function key($value) {
    $this->view_query['key'] = json_encode($value);
    return $this;
  }

	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param mixed $value any json encodable thing
	* @return couch_client $this
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
	* @return couch_client $this
	*/
  public function endkey($value) {
    $this->view_query['endkey'] = json_encode($value);
    return $this;
  }

	/**
	* CouchDB query option
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param ineteger $value maximum number of items to fetch
	* @return couch_client $this
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
	* @return couch_client $this
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
	* @return couch_client $this
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
	* @return couch_client $this
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
	* @return couch_client $this
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
	* @return couch_client $this
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
	* @return couch_client $this
	*/
  public function include_docs($value) {
    $this->view_query['include_docs'] = json_encode((boolean)$value);
    return $this;
  }

	/**
	* request a view from the CouchDB server
	*
	* @link http://wiki.apache.org/couchdb/HTTP_view_API
	* @param string $id design document name (without _design)
	* @param string $name view name
	* @return object CouchDB view query response
	*/
  public function get_view ( $id, $name ) {
		if ( !$id OR !$name )    throw new Exception("You should specify view id and name");
		$url = '/'.urlencode($this->dbname).'/_design/'.urlencode($id).'/_view/'.urlencode($name);
		$view_query = $this->view_query;
		$this->view_query = array();
    return $this->_query_and_test ('GET', $url, array(200),$view_query);
	}

	/**
	* request a list from the CouchDB server
	*
	* @link http://wiki.apache.org/couchdb/Formatting_with_Show_and_List
	* @param string $id design document name (without _design)
	* @param string $name list name
	* @param string $view_name view name
	* @return object CouchDB list query response
	*/
  public function get_list ( $id, $name, $view_name ) {
		if ( !$id OR !$name )    throw new Exception("You should specify list id and name");
		if ( !$view_name )    throw new Exception("You should specify view name");
		$url = '/'.urlencode($this->dbname).'/_design/'.urlencode($id).'/_list/'.urlencode($name).'/'.urlencode($view_name);
		$view_query = $this->view_query;
		$this->view_query = array();
    return $this->_query_and_test ('GET', $url, array(200),$view_query);
	}

	/**
	* returns all documents contained in the database
	*
	* @return object CouchDB _all_docs response
	*/
	public function get_all_docs () {
		$url = '/'.urlencode($this->dbname).'/_all_docs';
		$view_query = $this->view_query;
		$this->view_query = array();
    return $this->_query_and_test ('GET', $url, array(200),$view_query);
	}

	/**
	* returns all documents contained associated wityh a sequence number
	*
	* @return object CouchDB _all_docs_by_seq response
	*/
	public function get_all_docs_by_seq () {
		$url = '/'.urlencode($this->dbname).'/_all_docs_by_seq';
		$view_query = $this->view_query;
		$this->view_query = array();
    return $this->_query_and_test ('GET', $url, array(200),$view_query);
	}
}

class couchException extends Exception {
    // reponse couchDB une fois parsee
    protected $couch_response = array();

    // constructeur
    function __construct($raw_response) {
        $this->couch_response = couch::parse_raw_response($raw_response);
        parent::__construct($this->couch_response['status_message'], $this->couch_response['status_code']);
    }

    function getBody() {
        return $this->couch_response['body'];
    }
}

