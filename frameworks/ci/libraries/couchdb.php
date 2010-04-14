<?PHP

require_once APPPATH."/libraries/couch.php";
require_once APPPATH."/libraries/couchClient.php";
require_once APPPATH."/libraries/couchDocument.php";
require_once APPPATH."/libraries/couchReplicator.php";

class couchdb extends couchClient {

	function __construct() {
		$ci =& get_instance();
		$ci->config->load("couchdb");
		parent::__construct($ci->config->item("couch_dsn"), $ci->config->item("couch_database"));
	}

}
