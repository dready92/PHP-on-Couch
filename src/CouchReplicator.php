<?php

/*
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

use PHPOnCouch\Exceptions\CouchException;

/**
 * Special class to handle replication stuff, as the API is still evolving
 *
 *
 *
 *
 */
class CouchReplicator
{

	/**
	 * @var CouchClient Reference to our CouchDB client
	 */
	private $client = null;

	/**
	 * @var array Replication options
	 */
	private $opts = [];

	/**
	 * constructor
	 *
	 * @param CouchClient $client the couchClient instance
	 */
	public function __construct(CouchClient $client)
	{
		$this->client = $client;
	}

	/**
	 * chainable method : tell couchdb to create target database if it doesn't exist
	 *
	 * @return CouchReplicator $this
	 */
	public function create_target()
	{
		$this->opts['create_target'] = true;
		return $this;
	}

	/**
	 * chainable method : setup a continuous replication stream
	 *
	 * @return CouchReplicator $this
	 */
	public function continuous()
	{
		$this->opts['continuous'] = true;
		return $this;
	}

	/**
	 * chainable method : cancel a continuous replication stream
	 *
	 * TODO: check if that works (apparently that doesn't)
	 *
	 * @return CouchReplicator $this
	 */
	public function cancel()
	{
		$this->opts['cancel'] = true;
		return $this;
	}

	/**
	 * chainable method : restrict replication to given document ids
	 * @SuppressWarnings functionNaming
	 * @param array $ids list of document ids to replicate
	 * @return CouchReplicator $this
	 */
	public function doc_ids(array $ids)
	{
		$this->opts['doc_ids'] = $ids;
		return $this;
	}

	/**
	 * chainable method : set replication filter
	 *
	 * filter design doc should belong to the source database
	 *
	 * @param string $name replication filter name ( ex mydesign/myfilter )
	 * @return CouchReplicator $this
	 */
	public function filter($name)
	{
		$this->opts['filter'] = $name;
		return $this;
	}

	/**
	 * Chainable method : set query params (for example for a filtered replication)
	 * @SuppressWarnings functionNaming
	 * @param array|object $params list of document ids to replicate
	 * @return CouchReplicator $this
	 */
	public function query_params($params)
	{
		$this->opts['query_params'] = $params;
		return $this;
	}

	/**
	 * replicate from local TO specified url (push replication)
	 *
	 * @param string $url url of the remote couchDB server
	 * @return object couchDB server response to replication request
	 */
	public function to($url)
	{
		$this->opts['source'] = $this->client->getDatabaseUri();
		$this->opts['target'] = $url;
		return $this->_launch();
	}

	/**
	 * Replicate to local FROM specified url (push replication)
	 *
	 * @param string $url url of the remote couchDB server
	 * @return object couchDB server response to replication request
	 */
	public function from($url)
	{
		$this->opts['target'] = $this->client->getDatabaseUri();
		$this->opts['source'] = $url;
		return $this->_launch();
	}

	private function _launch()
	{
		$opts = $this->opts;
		$this->opts = [];
		$raw = $this->client->query(
				"POST", '/_replicate', [], $opts
		);
		$resp = Couch::parseRawResponse($raw);
		if ($resp['status_code'] == 200) {
			return $resp['body'];
		}
		// continuous setup returns 202 Accepted
		if (array_key_exists('continuous', $opts) && $opts['continuous'] == true && $resp['status_code'] == 202) {
			return $resp['body'];
		}
		throw new CouchException($raw);
	}

}
