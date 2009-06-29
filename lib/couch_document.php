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

class couch_document {

	/**
	* @var stdClass object internal data
	*/
	protected $__couch_data = NULL;

	/**
	*class constructor
	*
	* @param couch_client $client couch_client connection object
	*
	*/
	function __construct(couch_client $client) {
		$this->__couch_data->client = $client;
		$this->__couch_data->fields = new stdClass();
	}

	/**
	* load a CouchDB document from the CouchDB server
	*
	* @param string $id CouchDB document ID
	* @return couch_document $this
	*/
	public function load ( $id ) {
		if ( !strlen($id) ) throw new InvalidArgumentException("No id given");
		$this->__couch_data->fields = $this->__couch_data->client->doc_get($id);
		return $this;
	}

	/**
	* load a CouchDB document from a PHP object
	*
	* note that this method clones the object given in argument
	*
	* @param object $doc CouchDB document (should have $doc->_id  , $doc->_rev, ...)
	* @return couch_document $this
	*/
  public function load_with_object($doc) {
    $this->__couch_data->fields = clone $doc;
  }

	/**
	* load a document in a couch_document object and return it
	*
	* @static
	* @param couch_client $client couch_client instance
	* @param string $id id of the document to load
	* @return couch_docuument couch document loaded with data of document $id
	*/
	public static function get_instance(couch_client $client,$id) {
		$back = new couch_document($client);
		return $back->load($id);
	}

	/**
	* returns all defined keys in this document
	*
	* @return array list of keys available in this document
	*/
	public function get_keys ( ) {
		return array_keys(get_object_vars($this->__couch_data->fields));
	}

	/**
	* returns all fields (key => values) of this document
	*
	* @return object all fields of the document
	*/
  public function get_fields () {
		return clone $this->__couch_data->fields;
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
	protected function set_one ($key, $value ) {
		$key = (string)$key;
		if ( $key == '_rev' )	throw new InvalidArgumentException("Can't set _rev field");
		if ( $key == '_id' AND $this->get('_id') )	throw new InvalidArgumentException("Can't set _id field because it's already set");
    //echo "setting $key to ".print_r($value,TRUE)."<BR>\n";
		$this->__couch_data->fields->$key = $value;
		return TRUE;
	}

	/**
	* record the object to the database
	*
	*
	*/
	protected function record() {
		$response = $this->__couch_data->client->doc_store($this->__couch_data->fields);
		$this->__couch_data->fields->_id = $response->id;
		$this->__couch_data->fields->_rev = $response->rev;
	}

	/**
	* set document fields
	*
	* this method store the object in the database !
	*
	* there is 2 ways to use it. Set one field :
	*	<code>
	* $this->set('some_field','some value');
  * </code>
	*
	* or set multiple fields in one go :
	*	<code>
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
				$this->set_one($one_key,$one_value);
			} 
		} else {
			$this->set_one($key,$value);
		}
		$this->record();
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
}
