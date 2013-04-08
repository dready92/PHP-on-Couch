<?PHP
/*
Copyright (C) 2009  Mickael Bailly

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class couchDocument {

	/**
	* @var stdClass object internal data
	*/
	protected $__couch_data = NULL;

	/**
	*class constructor
	*
	* @param couchClient $client couchClient connection object
	*
	*/
	function __construct(couchClient $client) {
		$this->__couch_data = new stdClass();
		$this->__couch_data->client = $client;
		$this->__couch_data->fields = new stdClass();
		$this->__couch_data->autocommit = true;
	}

	/**
	* load a CouchDB document from the CouchDB server
	*
	* @param string $id CouchDB document ID
	* @return couchDocument $this
	*/
	public function load ( $id ) {
		if ( !strlen($id) ) throw new InvalidArgumentException("No id given");
		$this->__couch_data->fields = $this->__couch_data->client->getDoc($id);
		return $this;
	}

	/**
	* Set the auto-commit mode (default true)
	*
	* If set to false, you should explicitely call the record() method :
	* <code>
	* $couchdoc->setAutocommit(false);
	* $couchdoc->somefield = "foo";
	* $couchdoc->someotherfield = "bar";
	* $couchdoc->record();
	* </code>
	*
	* @param boolean $commit turn on or off the autocommit feature
	* @return couchDocument $this
	*/
	public function setAutocommit($commit) {
		$this->__couch_data->autocommit = (boolean)$commit;
		return $this;
	}

	/**
	* get current auto-commit state (on or off)
	*
	* @return boolean true if auto-commit is enabled
	*/
	public function getAutocommit() {
		return $this->__couch_data->autocommit;
	}

	/**
	* load a CouchDB document from a PHP object
	*
	* note that this method clones the object given in argument
	*
	* @param object $doc CouchDB document (should have $doc->_id  , $doc->_rev, ...)
	* @return couchDocument $this
	*/
  public function loadFromObject($doc) {
		$this->__couch_data->fields = clone $doc;
		return $this;
  }

	/**
	* load a document in a couchDocument object and return it
	*
	* @static
	* @param couchClient $client couchClient instance
	* @param string $id id of the document to load
	* @return couchDocument couch document loaded with data of document $id
	*/
	public static function getInstance(couchClient $client,$id) {
		$back = new couchDocument($client);
		return $back->load($id);
	}

	/**
	* returns all defined keys in this document
	*
	* @return array list of keys available in this document
	*/
	public function getKeys ( ) {
		return array_keys(get_object_vars($this->__couch_data->fields));
	}

	/**
	* returns all fields (key => values) of this document
	*
	* @return object all fields of the document
	*/
  public function getFields () {
		return clone $this->__couch_data->fields;
	}

	/**
	* returns document URI
	*
	* example : couch.server.com:5984/mydb/thisdoc
	*
	* @return string document URI
	*/
	public function getUri() {
		return $this->__couch_data->client->getDatabaseUri().'/'.$this->id();
	}

	/**
	* returns document id (or null)
	*
	* @return string document id
	*/
	public function id() {
		return $this->get('_id');
	}

	/**
	* returns value of field $key
	*
	* @param string $key field name
	* @return mixed field value (or null)
	*/
	public function get ( $key ) {
    //echo "get for $key\n";
		$key = (string)$key;
		if (!strlen($key) )	throw new InvalidArgumentException("No key given");
		return property_exists( $this->__couch_data->fields,$key ) ? $this->__couch_data->fields->$key : NULL;
	}

	/**
	* PHP magic method : getter
	*
	* @see get()
	*/
	public function __get ( $key ) {
		return $this->get($key);
	}

	/**
	* set one field to a value
	*
	* does not update the database
	*
	* @param string $key field name
	* @param mixed $value field value
	* @return boolean TRUE
	*/
	protected function setOne ($key, $value ) {
		$key = (string)$key;
		if ( !strlen($key) )  throw new InvalidArgumentException("property name can't be empty");
		if ( $key == '_rev' )	throw new InvalidArgumentException("Can't set _rev field");
		if ( $key == '_id' AND $this->get('_id') )	throw new InvalidArgumentException("Can't set _id field because it's already set");
		if ( substr($key,0,1) == '_' AND !in_array($key,couchClient::$allowed_underscored_properties) )
			throw new InvalidArgumentException("Property $key can't begin with an underscore");
    //echo "setting $key to ".print_r($value,TRUE)."<BR>\n";
		$this->__couch_data->fields->$key = $value;
		return TRUE;
	}

	/**
	* record the object to the database
	*
	*
	*/
	public function record() {
		foreach ( couchClient::$underscored_properties_to_remove_on_storage as $key ) {
			if ( property_exists($this->__couch_data->fields,$key) ) {
				unset( $this->__couch_data->fields->$key );
			}
		}
		$response = $this->__couch_data->client->storeDoc($this->__couch_data->fields);
		$this->__couch_data->fields->_id = $response->id;
		$this->__couch_data->fields->_rev = $response->rev;
	}

	/**
	* set document fields
	*
	* this method store the object in the database !
	*
	* there is 2 ways to use it. Set one field :
	* <code>
	* $this->set('some_field','some value');
	* </code>
	*
	* or set multiple fields in one go :
	* <code>
	* $this->set( array('some_field'=>'some value','some_other_field'=>'another value') );
	* </code>
	*
	* @param string|array $key
	* @param mixed $value
	*
	*/
	public function set ( $key , $value = NULL ) {
    
		if ( func_num_args() == 1 ) {
			if ( !is_array($key) AND !is_object($key) )	throw new InvalidArgumentException("When second argument is null, first argument should ba an array or an object");
			foreach ( $key as $one_key => $one_value ) {
				$this->setOne($one_key,$one_value);
			} 
		} else {
			$this->setOne($key,$value);
		}
		if ( $this->__couch_data->autocommit ) {
			$this->record();
		}
		return TRUE;
	}

	/**
	* PHP automagic setter
	*
	* modify a document property and store to the Server
	*
	* @link http://php.net/__set
	*
	* @param string $key name of the property to set
	* @param mixed $value property value
	*/
	public function __set( $key , $value ) {
		return $this->set($key,$value);
	}

	/**
	* PHP automagic isset'er
	*
	*
	* @link http://php.net/__isset
	*
	* @param string $key name of the property to test
	*/
	public function __isset($key) {
		return property_exists($this->__couch_data->fields,$key);
	}

	/**
	* deletes a document property
	*
	* @param string $key the key to remove
	* @return boolean whether the removal process ran successfully
	*/
	public function remove($key) {
		$key = (string)$key;
		if ( !strlen($key) )	throw new InvalidArgumentException("Can't remove a key without name");
		if ( $key == '_id' OR $key == '_rev' )		return FALSE;
		if ( isset($this->$key) ) {
			unset($this->__couch_data->fields->$key);
			$this->record();
		}
		return TRUE;
	}

	/**
	* PHP automagic unset'er
	*
	* @see remove()
	* @param string $key the property to delete
	* @return boolean whether the removal process ran successfully
	*/
	public function __unset($key) {
		return $this->remove($key);
	}

	/**
	* Replicates document on another couchDB database
	*
	* @param string $url the database to replicate to ( eg. "http://localhost:5984/foo" or "foo" )
	* @param boolean $create_target if set to true, target database will be created if needed
	* @return boolean tell if document replication succeded
	*/
	public function replicateTo($url, $create_target = false) {
		echo "replicateTo : ".$this->_id.", $url\n";
		if ( !isset($this->_id) ) {
			throw new InvalidArgumentException("Can't replicate a document without id");
		}
		if ( !class_exists("couchReplicator") ) {
			return false;
		}
		$r = new couchReplicator($this->__couch_data->client);
		if ( $create_target ) {
			$r->create_target();
		}
		try {
			$res = $r	->doc_ids( array( $this->_id ) )
				->to($url);
		} catch ( Exception $e ) {
			return false;
		}
		print_r($res);
		return true;
	}


	/**
	* Replicates document on another couchDB database
	*
	* @param string $id id of the document to replicate
	* @param string $url the database to replicate from ( eg. "http://localhost:5984/foo" or "foo" )
	* @param boolean $create_target if set to true, target database will be created if needed
	* @return boolean tell if document replication succeded
	*/
	public function replicateFrom($id, $url, $create_target = false) {
		echo "replicateFrom : $id, $url\n";
		if ( !class_exists("couchReplicator") ) {
			return false;
		}
		$r = new couchReplicator($this->__couch_data->client);
		if ( $create_target ) {
			$r->create_target();
		}
		$r->doc_ids( array( $id ) )->from($url);
		$this->load($id);
		return true;
	}

	/**
	* Attach a file to a document
	* 
	*
	* @param string $file the attachment file (local storage)
	* @param string $content_type the attachment content-type (defaults to 'application/octet-stream')
	* @param string $filename the attachment filename. If not specified, the basename of "$file" will be used
	* @return object CouchDB attachment storage response
	*/
	public function storeAttachment($file, $content_type = 'application/octet-stream',$filename = null) {
		$back = $this->__couch_data->client->storeAttachment($this,$file,$content_type,$filename);
		$this->load($this->_id);
		return $back;
	}

	/**
	* Attach data as a document attachment
	* 
	*
	* @param string $data the attachment contents
	* @param string $filename the attachment filename.
	* @param string $content_type the attachment content-type (defaults to 'application/octet-stream')
	* @return object CouchDB attachment storage response
	*/
	public function storeAsAttachment ($data,$filename,$content_type = 'application/octet-stream') {
		$back = $this->__couch_data->client->storeAsAttachment($this,$data,$filename,$content_type);
		$this->load($this->_id);
		return $back;
	}

	/**
	* Deletes an attachment
	*
	* @param string $attachment_name name of the document attachment
	* @return object CouchDB attachment removal response
	*/
	public function deleteAttachment ($attachment_name) {
		$back = $this->__couch_data->client->deleteAttachment( $this , $attachment_name );
		$this->load($this->_id);
		return $back;
	}

	/**
	* returns the URI of a document attachment
	*
	* @param string $attachment_name the name of the attachment (relative to the document)
	* @return string the attachment URI
	*/
	public function getAttachmentUri ($attachment_name ) {
		return $this->getUri().'/'.$attachment_name;
	}

	/**
	* just a proxy method to couchClient->getShow()
	*
	* @param string $id name of the design document containing the show function
	* @param string $name name of the show function
	* @param array $additionnal_params other parameters to send to couchServer
	* @return mixed CouchDB show response
	*/
	public function show ( $id,$name,$additionnal_params = array() ) {
		return $this->__couch_data->client->getShow($id,$name,$this->_id,$additionnal_params);
	}
	
	/**
	* just a proxy method to couchClient->updateDoc
	*
	* @param string $id name of the design document containing the update function
	* @param string $name name of the update function
	* @param array $additionnal_params other parameters to send to couch
	* @return mixed couchDB update response
	*/
	public function update( $id,$name,$additionnal_params = array() ) {
		if ( !$this->_id )	return false;
		$back = $this->__couch_data->client->updateDoc($id,$name, $additionnal_params, $this->_id);
		// we should reload document to be sure that we have an up-to-date version
		$this->load($this->_id);
		return $back;
	}
}
