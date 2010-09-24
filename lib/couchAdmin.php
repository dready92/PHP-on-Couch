<?PHP


/**
* Special class to handle administration tasks
* - create administrators
* - create users
* - create roles
* - assign roles to users
*
*
*
*/
class couchAdmin {
	/**
	* @var reference to our CouchDB client
	*/
	private $client = null;


	/**
	* @var the name of the CouchDB server "users" database
	*/
	private $usersdb = "_users";

	/**
	*constructor
	*
	* @param couchClient $client the couchClient instance
	* @param array $options array. For now the only option is "users_database" to override the defaults "_users"
	*/
	public function __construct ( couchClient $client, $options = array() ) {
		$this->client = $client;
		if ( is_array($options) && isset($options["users_database"]) ) {
			$this->usersdb = $options["users_database"];
		}
	}

	/**
	*Set the name of the users database (_users by default)
	*
	*@param string $name CouchDB users database name (_users is the default)
	*/
	public function setUsersDatabase ($name) {
		$this->usersdb = $name;
	}

	/**
	*get the name of the users database this class will use
	*
	*
	*@return string users database name
	*/
	public function getUsersDatabase () {
		return $this->usersdb;
	}

	private function build_url ($parts) {
		$back = $parts["scheme"]."://";
		if ( !empty($parts["user"]) ) {
			$back.=$parts["user"];
			if ( !empty($parts["pass"]) ) {
				$back.=":".$parts["pass"];
			}
			$back.="@";
		}
		$back.=$parts["host"];
		if ( !empty($parts["port"]) ) {
			$back.=":".$parts["port"];
		}
		$back.="/";
		if ( !empty($parts["path"]) ) {
			$back.=$parts["path"];
		}
		return $back;
	}

	/**
	* Creates a new CouchDB server administrator
	*
	* @param string $login administrator login
	* @param string $password administrator password
	* @param array $roles add additionnal roles to the new admin
	* @return stdClass CouchDB server response
	*/
	public function createAdmin ( $login, $password, $roles = array() ) {
		$login = urlencode($login);
		$data = (string)$password;
		if ( strlen($login) < 1 ) {
			throw new InvalidArgumentException("Login can't be empty");
		}
		if ( strlen($data) < 1 ) {
			throw new InvalidArgumentException("Password can't be empty");
		}
		$url = '/_config/admins/'.urlencode($login);
		try {
			$raw = $this->client->query(
				"PUT",
				$url,
				array(),
				json_encode($data)
			);
		} catch ( Exception $e ) {
			throw $e;
		}
		$resp = couch::parseRawResponse($raw);
		if ( $resp['status_code'] != 200 ) {
			throw new couchException($raw);
		}

		$dsn = $this->client->dsn_part();
		$dsn["user"] = $login;
		$dsn["pass"] = $password;
		$client = new couchClient( $this->build_url($dsn), $this->usersdb, $this->client->options() );
		$user = new stdClass();
		$user->name=$login;
		$user->type = "user";
		$user->roles = $roles;
		$user->_id = "org.couchdb.user:".$login;
		return $client->storeDoc($user);
	}

	/**
	* Permanently removes a CouchDB Server administrator
	*
	*
	* @param string $login administrator login
	* @return stdClass CouchDB server response
	*/
	public function deleteAdmin ( $login ) {
		$login = urlencode($login);
		if ( strlen($login) < 1 ) {
			throw new InvalidArgumentException("Login can't be empty");
		}

		try {
			$client = new couchClient( $this->client->dsn() , $this->usersdb);
			$doc = $client->getDoc("org.couchdb.user:".$login);
			$client->deleteDoc($doc);
		} catch (Exception $e) {
		}

		$url = '/_config/admins/'.urlencode($login);
		$raw = $this->client->query(
			"DELETE",
			$url
		);
		$resp = couch::parseRawResponse($raw);
		if ( $resp['status_code'] != 200 ) {
			throw new couchException($raw);
		}
		return $resp["body"];
	}
	
	/**
	* create a user
	*
	* @param string $login user login
	* @param string $password user password
	* @param array $roles add additionnal roles to the new user
	* @return stdClass CouchDB user creation response (the same as a document storage response)
	*/
	public function createUser ($login, $password, $roles = array() ) {
		$password = (string)$password;
		if ( strlen($login) < 1 ) {
			throw new InvalidArgumentException("Login can't be empty");
		}
		if ( strlen($password) < 1 ) {
			throw new InvalidArgumentException("Password can't be empty");
		}
		$user = new stdClass();
		$user->salt = sha1( microtime().mt_rand(1000000,9999999),false);
		$user->password_sha = sha1( $password . $user->salt, false);
		$user->name=$login;
		$user->type = "user";
		$user->roles = $roles;
		$user->_id = "org.couchdb.user:".$login;
		$client = new couchClient( $this->client->dsn() , $this->usersdb, $this->client->options());
		return $client->storeDoc($user);
	}


	/**
	* Permanently removes a CouchDB User
	*
	*
	* @param string $login user login
	* @return stdClass CouchDB server response
	*/
	public function deleteUser ( $login ) {
		if ( strlen($login) < 1 ) {
			throw new InvalidArgumentException("Login can't be empty");
		}
		$client = new couchClient( $this->client->dsn() , $this->usersdb);
		$doc = $client->getDoc("org.couchdb.user:".$login);
		return $client->deleteDoc($doc);
	}

	/**
	* returns the document of a user
	*
	* @param string $login login of the user to fetch
	* @return stdClass CouchDB document
	*/
	public function getUser ($login) {
		if ( strlen($login) < 1 ) {
			throw new InvalidArgumentException("Login can't be empty");
		}
		$client = new couchClient( $this->client->dsn() , $this->usersdb, $this->client->options());
		return $client->getDoc("org.couchdb.user:".$login);
	}

	/**
	* returns all users
	*
	* @param boolean $include_docs if set to true, users documents will also be included
	* @return array users array : each row is a stdObject with "id", "rev" and optionally "doc" properties
	*/
	public function getAllUsers($include_docs = false) {
		$client = new couchClient( $this->client->dsn() , $this->usersdb, $this->client->options());
		if ( $include_docs ) {
			$client->include_docs(true);
		}
		return $client->startkey("org.couchdb.user:")->endkey("org.couchdb.user;")->getAllDocs()->rows;
	}

	/**
	* Add a role to a user document
	*
	* @param string|stdClass $user the user login (as a string) or the user document ( fetched by getUser() method )
	* @param string $role the role to add in the list of roles the user belongs to
	* @return boolean true if the user $user now belongs to the role $role
	*/
	public function addRoleToUser ($user,$role) {
		if ( is_string($user) ) {
			$user = $this->getUser($user);
		} elseif ( !property_exists($user,"_id") || !property_exists($user,"roles") ) {
			throw new InvalidArgumentException("user parameter should be the login or a user document");
		}
		if ( !in_array($role,$user->roles) ) {
			$user->roles[] = $role;
			$client = clone($this->client);
			$client->useDatabase($this->usersdb);
			$client->storeDoc($user);
		}
		return true;
	}

	/**
	* Remove a role from a user document
	*
	* @param string|stdClass $user the user login (as a string) or the user document ( fetched by getUser() method )
	* @param string $role the role to remove from the list of roles the user belongs to
	* @return boolean true if the user $user don't belong to the role $role anymore
	*/
	public function removeRoleFromUser ($user,$role) {
		if ( is_string($user) ) {
			$user = $this->getUser($user);
		} elseif ( !property_exists($user,"_id") || !property_exists($user,"roles") ) {
			throw new InvalidArgumentException("user parameter should be the login or a user document");
		}
		if ( in_array($role,$user->roles) ) {
			$user->roles = $this->rmFromArray($role, $user->roles);
			$client = clone($this->client);
			$client->useDatabase($this->usersdb);
			$client->storeDoc($user);
		}
		return true;
	}


	/**
	* returns the security object of a database
	*
	* @link http://wiki.apache.org/couchdb/Security_Features_Overview
	* @return stdClass security object of the database
	*/
	public function getSecurity ( ) {
		$dbname = $this->client->getDatabaseName();
		$raw = $this->client->query(
			"GET",
			"/".$dbname."/_security"
		);
		$resp = couch::parseRawResponse($raw);
		if ( $resp['status_code'] != 200 ) {
			throw new couchException($raw);
		}
		if ( ! property_exists($resp['body'], "admins") ) {
			$resp["body"]->admins = new stdClass();
			$resp["body"]->admins->names = array();
			$resp["body"]->admins->roles = array();
			$resp["body"]->readers = new stdClass();
			$resp["body"]->readers->names = array();
			$resp["body"]->readers->roles = array();
		}
		return $resp['body'];
	}

	/**
	* set the security object of a database
	*
	* @link http://wiki.apache.org/couchdb/Security_Features_Overview
	* @param stdClass $security the security object to apply to the database
	* @return stdClass CouchDB server response ( { "ok": true } )
	*/
	public function setSecurity ( $security ) {
		if ( !is_object($security) ) {
			throw new InvalidArgumentException("Security should be an object");
		}
		$dbname = $this->client->getDatabaseName();
		$raw = $this->client->query(
			"PUT",
			"/".$dbname."/_security",
			array(),
			json_encode($security)
		);
		$resp = couch::parseRawResponse($raw);
		if ( $resp['status_code'] == 200 ) {
			return $resp['body'];
		}
		throw new couchException($raw);
	}

	/**
	* add a user to the list of readers for the current database
	*
	* @param string $login user login
	* @return boolean true if the user has successfuly been added
	*/
	public function addDatabaseReaderUser($login) {
		if ( strlen($login) < 1 ) {
			throw new InvalidArgumentException("Login can't be empty");
		}
		$sec = $this->getSecurity();
		if ( in_array($login, $sec->readers->names) ) {
			return true;
		}
		array_push($sec->readers->names,$login);
		$back = $this->setSecurity ( $sec );
		if ( is_object($back) && property_exists($back,"ok") && $back->ok == true ) {
			return true;
		}
		return false;
	}

	/**
	* add a user to the list of admins for the current database
	*
	* @param string $login user login
	* @return boolean true if the user has successfuly been added
	*/
	public function addDatabaseAdminUser($login) {
		if ( strlen($login) < 1 ) {
			throw new InvalidArgumentException("Login can't be empty");
		}
		$sec = $this->getSecurity();
		if ( in_array($login, $sec->admins->names) ) {
			return true;
		}
		array_push($sec->admins->names,$login);
		$back = $this->setSecurity ( $sec );
		if ( is_object($back) && property_exists($back,"ok") && $back->ok == true ) {
			return true;
		}
		return false;
	}

	/**
	* get the list of admins for the current database
	*
	* @return array database admins logins
	*/
	public function getDatabaseAdminUsers() {
		$sec = $this->getSecurity();
		return $sec->admins->names;
	}

	/**
	* get the list of readers for the current database
	*
	* @return array database readers logins
	*/
	public function getDatabaseReaderUsers() {
		$sec = $this->getSecurity();
		return $sec->readers->names;
	}

	/**
	* remove a user from the list of readers for the current database
	*
	* @param string $login user login
	* @return boolean true if the user has successfuly been removed
	*/
	public function removeDatabaseReaderUser($login) {
		if ( strlen($login) < 1 ) {
			throw new InvalidArgumentException("Login can't be empty");
		}
		$sec = $this->getSecurity();
		if ( !in_array($login, $sec->readers->names) ) {
			return true;
		}
		$sec->readers->names = $this->rmFromArray($login, $sec->readers->names);
		$back = $this->setSecurity ( $sec );
		if ( is_object($back) && property_exists($back,"ok") && $back->ok == true ) {
			return true;
		}
		return false;
	}

	/**
	* remove a user from the list of admins for the current database
	*
	* @param string $login user login
	* @return boolean true if the user has successfuly been removed
	*/
	public function removeDatabaseAdminUser($login) {
		if ( strlen($login) < 1 ) {
			throw new InvalidArgumentException("Login can't be empty");
		}
		$sec = $this->getSecurity();
		if ( !in_array($login, $sec->admins->names) ) {
			return true;
		}
		$sec->admins->names = $this->rmFromArray($login, $sec->admins->names);
		$back = $this->setSecurity ( $sec );
		if ( is_object($back) && property_exists($back,"ok") && $back->ok == true ) {
			return true;
		}
		return false;
	}

/// roles

	/**
	* add a role to the list of readers for the current database
	*
	* @param string $role role name
	* @return boolean true if the role has successfuly been added
	*/
	public function addDatabaseReaderRole($role) {
		if ( strlen($role) < 1 ) {
			throw new InvalidArgumentException("Role can't be empty");
		}
		$sec = $this->getSecurity();
		if ( in_array($role, $sec->readers->roles) ) {
			return true;
		}
		array_push($sec->readers->roles,$role);
		$back = $this->setSecurity ( $sec );
		if ( is_object($back) && property_exists($back,"ok") && $back->ok == true ) {
			return true;
		}
		return false;
	}

	/**
	* add a role to the list of admins for the current database
	*
	* @param string $role role name
	* @return boolean true if the role has successfuly been added
	*/
	public function addDatabaseAdminRole($role) {
		if ( strlen($role) < 1 ) {
			throw new InvalidArgumentException("Role can't be empty");
		}
		$sec = $this->getSecurity();
		if ( in_array($role, $sec->admins->roles) ) {
			return true;
		}
		array_push($sec->admins->roles,$role);
		$back = $this->setSecurity ( $sec );
		if ( is_object($back) && property_exists($back,"ok") && $back->ok == true ) {
			return true;
		}
		return false;
	}

	/**
	* get the list of admin roles for the current database
	*
	* @return array database admins roles
	*/
	public function getDatabaseAdminRoles() {
		$sec = $this->getSecurity();
		return $sec->admins->roles;
	}

	/**
	* get the list of reader roles for the current database
	*
	* @return array database readers roles
	*/
	public function getDatabaseReaderRoles() {
		$sec = $this->getSecurity();
		return $sec->readers->roles;
	}

	/**
	* remove a role from the list of readers for the current database
	*
	* @param string $role role name
	* @return boolean true if the role has successfuly been removed
	*/
	public function removeDatabaseReaderRole($role) {
		if ( strlen($role) < 1 ) {
			throw new InvalidArgumentException("Role can't be empty");
		}
		$sec = $this->getSecurity();
		if ( !in_array($role, $sec->readers->roles) ) {
			return true;
		}
		$sec->readers->roles = $this->rmFromArray($role, $sec->readers->roles);
		$back = $this->setSecurity ( $sec );
		if ( is_object($back) && property_exists($back,"ok") && $back->ok == true ) {
			return true;
		}
		return false;
	}

	/**
	* remove a role from the list of admins for the current database
	*
	* @param string $role role name
	* @return boolean true if the role has successfuly been removed
	*/
	public function removeDatabaseAdminRole($role) {
		if ( strlen($role) < 1 ) {
			throw new InvalidArgumentException("Role can't be empty");
		}
		$sec = $this->getSecurity();
		if ( !in_array($role, $sec->admins->roles) ) {
			return true;
		}
		$sec->admins->roles = $this->rmFromArray($role, $sec->admins->roles);
		$back = $this->setSecurity ( $sec );
		if ( is_object($back) && property_exists($back,"ok") && $back->ok == true ) {
			return true;
		}
		return false;
	}

/// /roles

	private function rmFromArray($needle, $haystack) {
		$back = array();
		foreach ( $haystack as $one ) {
			if ( $one != $needle ) { $back[] = $one; }
		}
		return $back;
	}

}
