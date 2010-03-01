<?PHP


/**
* Special class to handle replication stuff, as the API is still evolving
*
*
*
*
*/
class couchReplicator {
	/**
	* @var reference to our CouchDB client
	*/
	private $client = null;

	/**
	* @var replication options
	*/
	private $opts = array();

	/**
	*constructor
	*
	* @param couchClient $client the couchClient instance
	*/
	function __construct ( couchClient $client ) {
		$this->client = $client;
	}

	/**
	* chainable method : tell couchdb to create target database if it doesn't exist
	*
	* @return couchReplicator $this
	*/
	public function create_target () {
		$this->opts['create_target'] = true;
		return $this;
	}

	/**
	* chainable method : setup a continuous replication stream
	*
	* @return couchReplicator $this
	*/
	public function continuous () {
		$this->opts['continuous'] = true;
		return $this;
	}

	/**
	* chainable method : cancel a continuous replication stream
	*
	* TODO: check if that works (apparently that doesn't)
	*
	* @return couchReplicator $this
	*/
	public function cancel () {
		$this->opts['cancel'] = true;
		return $this;
	}

	/**
	* chainable method : restrict replication to given document ids
	*
	* @param array $ids list of document ids to replicate
	* @return couchReplicator $this
	*/
	public function doc_ids ( array $ids ) {
		$this->opts['doc_ids'] = $ids;
		return $this;
	}

	/**
	* chainable method : set replication filter
	*
	* filter design doc should belong to the source database
	*
	* @param string $name replication filter name ( ex mydesign/myfilter )
	* @return couchReplicator $this
	*/
	public function filter ( $name ) {
		$this->opts['filter'] = $name;
		return $this;
	}

	/**
	* chainable method : set query params (for example for a filtered replication)
	*
	* @param array|object $ids list of document ids to replicate
	* @return couchReplicator $this
	*/
	public function query_params ( $params ) {
		$this->opts['query_params'] = $params;
		return $this;
	}


	/**
	* replicate from local TO specified url (push replication)
	*
	* @param string $url url of the remote couchDB server
	* @return object couchDB server response to replication request
	*/
	public function to ( $url ) {
		$this->opts['source'] = $this->client->getDatabaseName();
		$this->opts['target'] = $url;
		return $this->_launch();
	}

	/**
	* replicate to local FROM specified url (push replication)
	*
	* @param string $url url of the remote couchDB server
	* @return object couchDB server response to replication request
	*/
	public function from ( $url ) {
		$this->opts['target'] = $this->client->getDatabaseName();
		$this->opts['source'] = $url;
		return $this->_launch();
	}


	private function _launch() {
		$opts = $this->opts;
		$this->opts = array();
// 		echo "posting to /_replicate";
		$raw = $this->client->query(
			"POST",
			'/_replicate',
			array(),
			$opts
		);
// 		print_r($raw);
		$resp = couch::parseRawResponse($raw);
// 		print_r($resp);
		if ( $resp['status_code'] == 200 ) {
			return $resp['body'];
		}
		// continuous setup returns 202 Accepted
		if ( array_key_exists('continuous',$opts) && $opts['continuous'] == true && $resp['status_code'] == 202 ) {
			return $resp['body'];
		}
		throw new couchException($raw);
	}
}
