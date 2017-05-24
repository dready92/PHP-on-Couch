<?php

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

namespace PHPOnCouch;

use stdClass;
use Exception;
use InvalidArgumentException;
//use PHPOnCouch\CouchReplicator;

/**
 * Class used to manipulate Couch document easily. Allow to auto update the changes.
 */
class CouchDocument
{

	/**
	 * @var stdClass object internal data
	 */
	private $_couchData = null;

	/**
	 * class constructor
	 *
	 * @param CouchClient $client couchClient connection object
	 *
	 */
	function __construct(CouchClient $client)
	{
		$this->_couchData = new stdClass();
		$this->_couchData->client = $client;
		$this->_couchData->fields = new stdClass();
		$this->_couchData->autocommit = true;
	}

	/**
	 * load a CouchDB document from the CouchDB server
	 *
	 * @param string $id CouchDB document ID
	 * @return CouchDocument $this
	 * @throws InvalidArgumentException
	 */
	public function load($id)
	{
		if (!strlen($id))
			throw new InvalidArgumentException("No id given");
		$this->_couchData->fields = $this->_couchData->client->getDoc($id);
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
	 * @return CouchDocument $this
	 */
	public function setAutocommit($commit)
	{
		$this->_couchData->autocommit = (boolean) $commit;
		return $this;
	}

	/**
	 * get current auto-commit state (on or off)
	 *
	 * @return boolean true if auto-commit is enabled
	 */
	public function getAutocommit()
	{
		return $this->_couchData->autocommit;
	}

	/**
	 * load a CouchDB document from a PHP object
	 *
	 * note that this method clones the object given in argument
	 *
	 * @param object $doc CouchDB document (should have $doc->_id  , $doc->_rev, ...)
	 * @return CouchDocument $this
	 */
	public function loadFromObject($doc)
	{
		$this->_couchData->fields = clone $doc;
		return $this;
	}

	/**
	 * load a document in a couchDocument object and return it
	 *
	 * @static
	 * @param CouchClient $client couchClient instance
	 * @param string $id id of the document to load
	 * @return CouchDocument couch document loaded with data of document $id
	 */
	public static function getInstance(CouchClient $client, $id)
	{
		$back = new CouchDocument($client);
		return $back->load($id);
	}

	/**
	 * returns all defined keys in this document
	 *
	 * @return array list of keys available in this document
	 */
	public function getKeys()
	{
		return array_keys(get_object_vars($this->_couchData->fields));
	}

	/**
	 * returns all fields (key => values) of this document
	 *
	 * @return object all fields of the document
	 */
	public function getFields()
	{
		return clone $this->_couchData->fields;
	}

	/**
	 * returns document URI
	 *
	 * example : couch.server.com:5984/mydb/thisdoc
	 *
	 * @return string document URI
	 */
	public function getUri()
	{
		return $this->_couchData->client->getDatabaseUri() . '/' . $this->id();
	}

	/**
	 * returns document id (or null)
	 *
	 * @return string document id
	 */
	public function id()
	{
		return $this->get('_id');
	}

	/**
	 * returns value of field $key
	 *
	 * @param string $key field name
	 * @return mixed field value (or null)
	 * @throws InvalidArgumentException
	 */
	public function get($key)
	{
		$keyStr = (string) $key;
		if (!strlen($keyStr))
			throw new InvalidArgumentException("No key given");
		return property_exists($this->_couchData->fields, $keyStr) ? $this->_couchData->fields->$keyStr : null;
	}

	/**
	 * PHP magic method : getter
	 * @param string $key The key to get from the object.
	 * @throws InvalidArgumentException if no key are passed.
	 * @return mixed Returns the fields of the object if available.
	 * @see get()
	 */
	public function __get($key)
	{
		return $this->get($key);
	}

	/**
	 * set one field to a value
	 *
	 * does not update the database
	 *
	 * @param string $key field name
	 * @param mixed $value field value
	 * @return boolean true
	 * @throws InvalidArgumentException
	 */
	protected function setOne($key, $value)
	{
		$keyStr = (string) $key;
		if (!strlen($keyStr))
			throw new InvalidArgumentException("property name can't be empty");
		if ($keyStr == '_rev')
			throw new InvalidArgumentException("Can't set _rev field");
		if ($keyStr == '_id' && $this->get('_id'))
			throw new InvalidArgumentException("Can't set _id field because it's already set");
		if (substr($keyStr, 0, 1) == '_' && !in_array($keyStr, CouchClient::$allowedUnderscoredProperties))
			throw new InvalidArgumentException("Property $keyStr can't begin with an underscore");
		$this->_couchData->fields->$keyStr = $value;
		return true;
	}

	/**
	 * record the object to the database
	 *
	 *
	 */
	public function record()
	{
		foreach (CouchClient::$underscoredPropertiesToRemoveOnStorage as $key) {
			if (property_exists($this->_couchData->fields, $key)) {
				unset($this->_couchData->fields->$key);
			}
		}
		$response = $this->_couchData->client->storeDoc($this->_couchData->fields);
		$this->_couchData->fields->_id = $response->id;
		$this->_couchData->fields->_rev = $response->rev;
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
	 * @return boolean true
	 * @throws InvalidArgumentException
	 *
	 */
	public function set($key, $value = null)
	{

		if (func_num_args() == 1) {
			if (!is_array($key) && !is_object($key)) {
				$errStr = "When second argument is null, first argument should ba an array or an object";
				throw new InvalidArgumentException($errStr);
			}
			foreach ($key as $subKey => $subValue) {
				$this->setOne($subKey, $subValue);
			}
		} else {
			$this->setOne($key, $value);
		}
		if ($this->_couchData->autocommit) {
			$this->record();
		}
		return true;
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
	 * @return boolean true
	 */
	public function __set($key, $value)
	{
		return $this->set($key, $value);
	}

	/**
	 * PHP automagic isset'er
	 *
	 *
	 * @link http://php.net/__isset
	 *
	 * @param string $key name of the property to test
	 * @return boolean
	 */
	public function __isset($key)
	{
		return property_exists($this->_couchData->fields, $key);
	}

	/**
	 * deletes a document property
	 *
	 * @param string $key the key to remove
	 * @return boolean whether the removal process ran successfully
	 * @throws InvalidArgumentException
	 */
	public function remove($key)
	{
		$keyStr = (string) $key;
		if (!strlen($keyStr))
			throw new InvalidArgumentException("Can't remove a key without name");
		if ($keyStr == '_id' || $keyStr == '_rev')
			return false;
		if (isset($this->$keyStr)) {
			unset($this->_couchData->fields->$keyStr);
			$this->record();
		}
		return true;
	}

	/**
	 * PHP automagic unset'er
	 *
	 * @see remove()
	 * @param string $key the property to delete
	 * @return boolean whether the removal process ran successfully
	 */
	public function __unset($key)
	{
		return $this->remove($key);
	}

	/**
	 * Replicates document on another couchDB database
	 *
	 * @param string $url the database to replicate to ( eg. "http://localhost:5984/foo" or "foo" )
	 * @param boolean $createTarget if set to true, target database will be created if needed
	 * @return boolean tell if document replication succeded
	 * @throws InvalidArgumentException
	 */
	public function replicateTo($url, $createTarget = false)
	{
		if (!isset($this->_id)) {
			throw new InvalidArgumentException("Can't replicate a document without id");
		}
		$repl = new CouchReplicator($this->_couchData->client);
		if ($createTarget) {
			$repl->create_target();
		}
		try {
			$repl->doc_ids([$this->_id])->to($url);
		} catch (Exception $ex) {
			return false;
		}
		return true;
	}

	/**
	 * Replicates document on another couchDB database
	 *
	 * @param string $id id of the document to replicate
	 * @param string $url the database to replicate from ( eg. "http://localhost:5984/foo" or "foo" )
	 * @param boolean $createTarget if set to true, target database will be created if needed
	 * @return boolean tell if document replication succeded
	 */
	public function replicateFrom($id, $url, $createTarget = false)
	{
		$repl = new CouchReplicator($this->_couchData->client);
		if ($createTarget) {
			$repl->create_target();
		}
		try {
			$repl->doc_ids([$id])->from($url);
			$this->load($id);
		} catch (Exception $ex) {
			return false;
		}
		return true;
	}

	/**
	 * Attach a file to a document
	 *
	 *
	 * @param string $file the attachment file (local storage)
	 * @param string $contentType the attachment content-type (defaults to 'application/octet-stream')
	 * @param string $filename the attachment filename. If not specified, the basename of "$file" will be used
	 * @return object CouchDB attachment storage response
	 */
	public function storeAttachment($file, $contentType = 'application/octet-stream', $filename = null)
	{
		$back = $this->_couchData->client->storeAttachment($this, $file, $contentType, $filename);
		$this->load($this->_id);
		return $back;
	}

	/**
	 * Attach data as a document attachment
	 *
	 *
	 * @param string $data the attachment contents
	 * @param string $filename the attachment filename.
	 * @param string $contentType the attachment content-type (defaults to 'application/octet-stream')
	 * @return object CouchDB attachment storage response
	 */
	public function storeAsAttachment($data, $filename, $contentType = 'application/octet-stream')
	{
		$back = $this->_couchData->client->storeAsAttachment($this, $data, $filename, $contentType);
		$this->load($this->_id);
		return $back;
	}

	/**
	 * Deletes an attachment
	 *
	 * @param string $attachmentName name of the document attachment
	 * @return object CouchDB attachment removal response
	 */
	public function deleteAttachment($attachmentName)
	{
		$back = $this->_couchData->client->deleteAttachment($this, $attachmentName);
		$this->load($this->_id);
		return $back;
	}

	/**
	 * returns the URI of a document attachment
	 *
	 * @param string $attachmentName the name of the attachment (relative to the document)
	 * @return string the attachment URI
	 */
	public function getAttachmentUri($attachmentName)
	{
		return $this->getUri() . '/' . $attachmentName;
	}

	/**
	 * just a proxy method to couchClient->getShow()
	 *
	 * @param string $id name of the design document containing the show function
	 * @param string $name name of the show function
	 * @param array $additionnalParams other parameters to send to couchServer
	 * @return mixed CouchDB show response
	 */
	public function show($id, $name, $additionnalParams = [])
	{
		return $this->_couchData->client->getShow($id, $name, $this->_id, $additionnalParams);
	}

	/**
	 * just a proxy method to couchClient->updateDoc
	 *
	 * @param string $id name of the design document containing the update function
	 * @param string $name name of the update function
	 * @param array $additionnalParams other parameters to send to couch
	 * @return mixed couchDB update response
	 */
	public function update($id, $name, $additionnalParams = [])
	{
		if (!$this->_id)
			return false;
		$back = $this->_couchData->client->updateDoc($id, $name, $additionnalParams, $this->_id);
		// we should reload document to be sure that we have an up-to-date version
		$this->load($this->_id);
		return $back;
	}

}
