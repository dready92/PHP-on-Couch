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

use Exception;
use PHPOnCouch\Exceptions\CouchException;
use InvalidArgumentException;
use BadMethodCallException;
use PHPOnCouch\Exceptions\CouchNotFoundException;

/**
 * CouchDB client class
 *
 * This class implements all required methods to use with a
 * CouchDB server
 *
 * @method CouchClient since(string $val) Start the results from the change immediately after the given update sequence.
 * @method CouchClient heartbeat(int $val) Period in milliseconds after which an
 * empty line is sent in the results. Only applicable for longpoll, continuous, and eventsource feeds.
 * @method CouchClient style(string $val) Specifies how many revisions are returned in the changes array.
 * @method CouchClient conflicts(boolean $include_conflicts) Includes conflicts information in response.
 * @method CouchClient descending(boolean $descending) Return the change results in descending sequence order (most recent change first). Default is false.
 * @method CouchClient revs(boolean $includeRevs)  Includes list of all known document revisions.
 * @method CouchClient rev(string $rev) Retrieves document of specified revision.
 * @method CouchClient revs_info(boolean $includeRevInfo) Includes detailed information for all known document revisions.
 * @method CouchClient key(string $key) Specify a document id to fetch in the query.
 * @method CouchClient keys(array $keys) A list of documents ids to fetch in the query.
 * @method CouchClient startkey(string $val)  Return records starting with the specified key.
 * @method CouchClient endkey(string $val) Stop returning records when the specified key is reached.
 * @method CouchClient startkey_docid(string $val) Return records starting with the specified document ID. Requires startkey to be specified for this to have any effect.
 * @method CouchClient endkey_docid(string $val) Stop returning records when the specified document ID is reached. Requires endkey to be specified for this to have any effect.
 * @method CouchClient limit(int $val) Limit the number of the returned documents to the specified number.
 * @method CouchClient stale(string $val)  Allow the results from a stale view to be used. Supported values: ok and update_after. Deprecated from CouchDB 2.1.1 Will be removed with CouchDB 3.0.0
 * @method CouchClient stable(boolean $val) Whether or not the view results should be returned from a stable set of shards. Default is false. Optional
 * @method CouchClient update (string $val) Whether or not the view in question should be updated prior to responding to the user. Supported values: true, false, lazy. Default is true. Optional
 * @method CouchClient skip(int $val)  Allow the results from a stale view to be used. Supported values: ok and update_after.
 * @method CouchClient group(boolean $val) Group the results using the reduce function to a group or single row.
 * @method CouchClient group_level(int $val) Specify the group level to be used.
 * @method CouchClient reduce(boolean $val) Use the reduction function
 * @method CouchClient include_docs(boolean $val) Include the associated document with each result. If there are conflicts, only the winning revision is returned.
 * @method CouchClient inclusive_end(boolean $val) Specifies wheter the specified end key should be included in the result.
 * @method CouchClient attachments(boolean $val)  Include the Base64-encoded content of attachments in the documents that are included if include_docs is true.
 * @method CouchClient sort(array | object $sortObj)  JSON array following sort syntax.
 * @method CouchClient fields(array | string $fields) SON array specifying which fields of each object should be returned. If it is omitted, the entire object is returned. More information provided in the section on filtering fields.
 *
 */
class CouchClient extends Couch
{

    /**
     * @var string database name
     */
    protected $dbname = '';

    /**
     * @var array query parameters
     */
    protected $queryParameters = [];

    /**
     * @var array CouchDB query options definitions
     *
     * key is the couchClient method (mapped with __call)
     * value is a hash containing :
     *    - name : the query option name (couchdb side)
     *    - filter : the type of filter to apply to the value (ex to force a cast to an integer ...)
     */
    protected $queryDefs = [
        'since' => ['name' => 'since', 'filter' => 'string'],
        'heartbeat' => ['name' => 'heartbeat', 'filter' => 'int'],
        'style' => ['name' => 'style', 'filter' => null],
        'conflicts' => ['name' => 'conflicts', 'filter' => 'staticValue', 'staticValue' => 'true'],
        'revs' => ['name' => 'revs', 'filter' => 'staticValue', 'staticValue' => 'true'],
        'revs_info' => ['name' => 'revs_info', 'filter' => 'staticValue', 'staticValue' => 'true'],
        'rev' => ['name' => 'rev', 'filter' => null],
        'key' => ['name' => 'key', 'filter' => 'jsonEncode'],
        'keys' => ['name' => 'keys', 'filter' => 'ensureArray'],
        'startkey' => ['name' => 'startkey', 'filter' => 'jsonEncode'],
        'endkey' => ['name' => 'endkey', 'filter' => 'jsonEncode'],
        'startkey_docid' => ['name' => 'startkey_docid', 'filter' => 'string'],
        'endkey_docid' => ['name' => 'endkey_docid', 'filter' => 'string'],
        'limit' => ['name' => 'limit', 'filter' => 'int'],
        //Deprecated in CouchDB 2.1.1 -> Removed in CouchDB 3.0.0
        'stale' => ['name' => 'stale', 'filter' => 'enum', 'enum' => ['ok', 'update_after']],
        'descending' => ['name' => 'descending', 'filter' => 'jsonEncodeBoolean'],
        'skip' => ['name' => 'skip', 'filter' => 'int'],
        'group' => ['name' => 'group', 'filter' => 'jsonEncodeBoolean'],
        'group_level' => ['name' => 'group_level', 'filter' => 'int'],
        'reduce' => ['name' => 'reduce', 'filter' => 'jsonEncodeBoolean'],
        'include_docs' => ['name' => 'include_docs', 'filter' => 'jsonEncodeBoolean'],
        'inclusive_end' => ['name' => 'inclusive_end', 'filter' => 'jsonEncodeBoolean'],
        'attachments' => ['name' => 'attachments', 'filter' => 'jsonEncodeBoolean'],

        //CouchDB 2.0.0 parameters

        //Those parameter are only for MangoQuery (Could cause problems in the futur)
        'sort' => ['name' => 'sort', 'filter' => null],
        'fields' => ['name' => 'fields', 'filter' => 'ensureArray'],

        //CouchDB 2.1.1 parameters
        'stable' => ['name' => 'stable', 'filter' => 'jsonEncodeBoolean'],
        'update' => ['name' => 'update', 'filter' => 'enum', 'enum' => ['true', 'false', 'lazy']],
    ];

    /**
     * @var bool option to return couchdb view results as couchDocuments objects
     */
    protected $resultsAsCouchDocs = false;

    /**
     * @var boolean tell if documents shall be returned as arrays instead of objects
     */
    protected $resultAsArray = false;

    /**
     * @var array list of properties beginning with '_' and allowed in CouchDB objects in a 'store' type operation
     */
    public static $allowedUnderscoredProperties = ['_id', '_rev', '_attachments', '_deleted'];

    /**
     * @var array list of properties beginning with '_' and that should be removed from CouchDB objects in
     *  a 'store' type operation
     */
    public static $underscoredPropertiesToRemoveOnStorage = ['_conflicts', '_revisions', '_revs_info'];

    /**
     * class constructor
     *
     * @param string $dsn CouchDB server data source name (eg. http://localhost:5984)
     * @param string $dbname CouchDB database name
     * @param array $options Additionnal configuration options
     * @throws Exception
     */
    public function __construct($dsn, $dbname, $options = [])
    {
        if (array_key_exists('username', $options) && array_key_exists('password', $options)) {
            $dsn = $this->buildDSNFromUserPass($dsn, $options['username'], $options['password']);
        }

        // in the case of a cookie based authentification we have to remove user and password infos from the DSN
        if (array_key_exists('cookie_auth', $options) && $options['cookie_auth']) {
            $parts = parse_url($dsn);
            if (!array_key_exists('user', $parts) || !array_key_exists('pass', $parts)) {
                throw new Exception('You should provide a user and a password to use cookie based authentification');
            }
            $user = urlencode($parts['user']);
            $pass = urlencode($parts['pass']);
            $dsn = $parts['scheme'] . '://' . $parts['host'];
            $dsn .= array_key_exists('port', $parts) ? ':' . $parts['port'] : '';
            $dsn .= array_key_exists('path', $parts) ? $parts['path'] : '';

            $this->useDatabase($dbname);
            parent::__construct($dsn, $options);
            $queryParams = http_build_query(['name' => $user, 'password' => $pass]);
            $rawData = $this->query('POST', '/_session', null, $queryParams, 'application/x-www-form-urlencoded');
            list($headers) = explode("\r\n\r\n", $rawData, 2);
            $headersArray = explode("\r\n", $headers);
            foreach ($headersArray as $line) {
                if (strpos($line, 'Set-Cookie: ') === 0) {
                    $line = substr($line, 12);
                    $line = explode('; ', $line, 2);
                    $this->setSessionCookie(reset($line));
                    break;
                }
            }
            if (empty($this->getSessionCookie())) {
                throw new Exception('Cookie authentification failed');
            }
        } else
            $this->useDatabase($dbname);
        parent::__construct($dsn, $options);
    }

    protected function buildDSNFromUserPass($dsn, $username, $password)
    {
        $parts = parse_url($dsn);
        $user = urlencode($username);
        $pass = urlencode($password);
        $dsn = $parts['scheme'] . '://' . $user . ':' . $pass . '@' . $parts['host'];
        $dsn .= array_key_exists('port', $parts) ? ':' . $parts['port'] : '';
        $dsn .= array_key_exists('path', $parts) ? $parts['path'] : '';
        return $dsn;
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
     * @param array $allowedStatusCodes the list of HTTP response status codes that prove a successful request
     * @param array $parameters additionnal parameters to send with the request
     * @param string|object|array $data the request body. If it's an array or an object, $data is json_encode()d
     * @param string $contentType set the content-type of the request
     * @throws CouchException
     * @return object
     */
    public function queryAndValid($method, $url, $allowedStatusCodes, $parameters = [], $data = null, $contentType = null)
    {
        $raw = $this->query($method, $url, $parameters, $data, $contentType);
        $response = $this->parseRawResponse($raw, $this->resultAsArray);
        $this->resultAsArray = false;
        if (in_array($response['status_code'], $allowedStatusCodes)) {
            return $response['body'];
        }
        throw CouchException::factory($response, $method, $url, $parameters);
    }

    /**
     * Dynamicall call the setter and function
     * @param $name string The name of the function to call
     * @param $args
     * @return $this
     * @throws Exception
     */
    public function __call($name, $args)
    {
        if (!array_key_exists($name, $this->queryDefs)) {
            throw new Exception("Method $name does not exist");
        }
        if ($this->queryDefs[$name]['filter'] == 'int') {
            $this->queryParameters[$this->queryDefs[$name]['name']] = (int)reset($args);
        } elseif ($this->queryDefs[$name]['filter'] == 'staticValue') {
            $this->queryParameters[$this->queryDefs[$name]['name']] = $this->queryDefs[$name]['staticValue'];
        } elseif ($this->queryDefs[$name]['filter'] == 'jsonEncode') {
            $this->queryParameters[$this->queryDefs[$name]['name']] = json_encode(reset($args));
        } elseif ($this->queryDefs[$name]['filter'] == 'ensureArray') {
            if (is_array(reset($args))) {
                $this->queryParameters[$this->queryDefs[$name]['name']] = reset($args);
            }
        } elseif ($this->queryDefs[$name]['filter'] == 'string') {
            $this->queryParameters[$this->queryDefs[$name]['name']] = (string)reset($args);
        } elseif ($this->queryDefs[$name]['filter'] == 'jsonEncodeBoolean') {
            $this->queryParameters[$this->queryDefs[$name]['name']] = json_encode((boolean)reset($args));
        } elseif ($this->queryDefs[$name]['filter'] == 'enum') {
            $value = (string)reset($args);
            //handle backward compatibility for stale option
            if ($name == 'stale' && !$value) {
                $value = 'ok';
            }
            if (in_array($value, $this->queryDefs[$name]['enum'])) {
                $this->queryParameters[$this->queryDefs[$name]['name']] = $value;
            }
        } else {
            $this->queryParameters[$this->queryDefs[$name]['name']] = reset($args);
        }
        return $this;
    }

    /**
     * Set all CouchDB query options at once.
     * Any invalid options are ignored.
     *
     * @link http://wiki.apache.org/couchdb/HTTP_view_API
     * @param array $options any json encodable thing
     * @return CouchClient $this
     * @throws Exception
     */
    public function setQueryParameters(array $options)
    {
        foreach ($options as $option => $val)
            if (array_key_exists($option, $this->queryDefs))
                $this->$option($val);
        return $this;
    }

    /**
     * set the name of the couchDB database to work on
     *
     * @param string $dbname name of the database
     * @return CouchClient $this
     * @throws InvalidArgumentException
     */
    public function useDatabase($dbname)
    {
        if (!strlen($dbname))
            throw new InvalidArgumentException('Database name can\'t be empty');
        if (!$this->isValidDatabaseName($dbname)) {
            $errStr = 'Database name contains invalid characters.';
            $errStr .= 'Only lowercase characters (a-z), digits (0-9), and any of the characters';
            $errStr .= ' _, $, (, ), +, -, and / are allowed.';
            throw new InvalidArgumentException($errStr);
        }
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
    public static function isValidDatabaseName($dbname)
    {
        if (preg_match('/^[_a-z][a-z0-9_$()+\/-]*$/', $dbname))
            return true;
        return false;
    }

    public function setSessionCookie($cookie)
    {
        parent::setSessionCookie($cookie);
        return $this;
    }

    public function getSessionCookie()
    {
        return parent::getSessionCookie();
    }

    /**
     * list all databases on the CouchDB server
     *
     * @return object databases list
     * @throws CouchException
     */
    public function listDatabases()
    {
        return $this->queryAndValid('GET', '/_all_dbs', [200]);
    }

    /**
     * create the database
     *
     * @return object creation infos
     * @throws CouchException
     */
    public function createDatabase()
    {
        return $this->queryAndValid('PUT', '/' . urlencode($this->dbname), [201]);
    }

    /**
     * delete the database
     *
     * @return object creation infos
     * @throws CouchException
     */
    public function deleteDatabase()
    {
        return $this->queryAndValid('DELETE', '/' . urlencode($this->dbname), [200]);
    }

    /**
     * get database infos
     *
     * @return object database infos
     * @throws CouchException
     */
    public function getDatabaseInfos()
    {
        return $this->queryAndValid('GET', '/' . urlencode($this->dbname), [200]);
    }

    /**
     * return database uri
     *
     * example : http://couch.server.com:5984/mydb
     *
     * @return string database URI
     */
    public function getDatabaseUri()
    {
        return $this->dsn . '/' . $this->dbname;
    }

    /**
     * return database name
     *
     * @return string database name
     */
    public function getDatabaseName()
    {
        return $this->dbname;
    }

    /**
     * returns CouchDB server URI
     *
     * example : http://couch.server.com:5984
     *
     * @return string CouchDB Server URL
     */
    public function getServerUri()
    {
        return $this->dsn;
    }

    /**
     * test if the database already exists
     *
     * @return boolean wether or not the database exist
     * @throws Exception
     */
    public function databaseExists()
    {
        try {
            $this->getDatabaseInfos();
            return true;
        } catch (Exception $e) {
            // if status code = 404 database does not exist
            if ($e->getCode() == 404)
                return false;
            // we met another exception so we throw it
            throw $e;
        }
    }

    /**
     * launch a compact operation on the database
     *
     *
     * @return object CouchDB's compact response ( usually {'ok':true} )
     * @throws CouchException
     */
    public function compactDatabase()
    {
        return $this->queryAndValid('POST', '/' . urlencode($this->dbname) . '/_compact', [202]);
    }

    /**
     *    Get the nodes that are part of the cluster and all the nodes that this node know.
     * @return object {'all_nodes':[],'cluster_nodes':[]}
     * @throws CouchException
     */
    public function getMemberShip()
    {
        return $this->queryAndValid('GET', '/_membership', [200]);
    }

    /**
     * Get the configuration from the selected node.
     * @link http://docs.couchdb.org/en/1.6.1/api/server/configuration.html#get--_config API
     * @param string $nodeName The node name following the FQDN format : couchdb@localhost
     * @param string|null $section If specified, it will get the configuration
     * for the specified section as a JSON data structure. Otherwise, it returns
     *  the entire CouchDB server configuration as a JSON structure.
     * @param string|null $key If specified, it returns the value for this key in
     *  the section set earlier. Otherwise, it only returns the whole section.
     * @return object    Returns a response object
     * @throws InvalidArgumentException    Invalid parameters
     * @throws CouchNotFoundException    Whenever the section/key/node are invalids and the path to the value doesn't exist.
     * @throws CouchException
     */
    public function getConfig($nodeName, $section = null, $key = null)
    {
        //Parameter validation
        if (!is_string($nodeName))
            throw new InvalidArgumentException('The node name must be of type String');
        if ($section === null && $key !== $section)
            throw new InvalidArgumentException('The section parameter can\'t be empty or null');

        $url = '/_node/' . urlencode($nodeName) . '/_config';
        if (!empty($section) && is_string($section)) {
            $url .= '/' . urlencode($section);
            if (!empty($key) && is_string($key))
                $url .= '/' . urlencode($key);
        }
        return $this->queryAndValid('GET', $url . '/', [200]);
    }

    /**
     * Set a configuration value to a specific node and return the old value
     * @param string $nodeName The node name following the FQDN. For example : couchdb@localhost
     * @param string $section The section name to update.
     * @param string $key The key of the section to update.
     * @param mixed $value The value to set to the key.
     * @throws InvalidArgumentException
     * @return object    Returns the old value  for example when you change the debug level : 'info'
     * @throws CouchException
     */
    public function setConfig($nodeName, $section, $key, $value)
    {
        //Parameter validation
        if (!is_string($nodeName))
            throw new InvalidArgumentException('The node name must be of type String');
        if (empty($section) || empty($key))
            throw new InvalidArgumentException('You must supply a section and key parameter.');
        $nodeUrl = '/_node/' . urlencode($nodeName) . '/';
        $configUrl = urlencode($section) . '/' . urlencode($key);
        $encodedValue = json_encode($value);
        return $this->queryAndValid('PUT', $nodeUrl . '_config/' . $configUrl, [200], [], $encodedValue);
    }

    /**
     * Delete a configuration key to a specific node and return the old value
     * @param string $nodeName The node name following the FQDN. For example : couchdb@localhost
     * @param string $section The section name to update.
     * @param string $key The key of the section to update.
     * @throws InvalidArgumentException
     * @return object    Returns the old value  for example when you change the debug level : 'info'
     * @throws CouchException
     */
    public function deleteConfig($nodeName, $section, $key)
    {
        //Parameter validation
        if (!is_string($nodeName))
            throw new InvalidArgumentException('The node name must be of type String');
        if (empty($section) || empty($key))
            throw new InvalidArgumentException('You must supply a section and key parameter.');
        $nodeUrl = '/_node/' . urlencode($nodeName) . '/';
        $configUrl = urlencode($section) . '/' . urlencode($key);
        return $this->queryAndValid('DELETE', $nodeUrl . '_config/' . $configUrl, [200]);
    }

    /**
     * launch a cleanup operation on database views
     *
     *
     * @return object CouchDB's cleanup views response ( usually {'ok':true} )
     * @throws CouchException
     */
    public function cleanupDatabaseViews()
    {
        return $this->queryAndValid('POST', '/' . urlencode($this->dbname) . '/_view_cleanup', [202]);
    }

    /**
     * CouchDb changes option
     *
     *
     * @link http://books.couchdb.org/relax/reference/change-notifications
     * @param string $value feed type
     * @param callable $continuousCallback in case of a continuous feed, the callback to be executed
     * on new event reception
     * @return CouchClient $this
     */
    public function feed($value, $continuousCallback = null)
    {
        if ($value == 'longpoll') {
            $this->queryParameters['feed'] = $value;
        } elseif ($value == 'continuous') {
            $this->queryParameters['feed'] = $value;
            $this->queryParameters['continuous_feed'] = $continuousCallback;
        } else {
            if (!empty($this->queryParameters['feed']))
                unset($this->queryParameters['feed']);
            if (!empty($this->queryParameters['continuous_feed']))
                unset($this->queryParameters['continuous_feed']);
        }
        return $this;
    }

    /**
     * CouchDb changes option
     *
     *
     * @link http://books.couchdb.org/relax/reference/change-notifications
     * @param string $value designdocname/filtername
     * @param  array $additionalQueryOpts additional query options
     * @return CouchClient $this
     */
    public function filter($value, array $additionalQueryOpts = [])
    {
        if (strlen(trim($value))) {
            $this->queryParameters['filter'] = trim($value);
            $this->queryParameters = array_merge($additionalQueryOpts, $this->queryParameters);
        }
        return $this;
    }

    /**
     * fetch database changes
     *
     * @return object|string CouchDB changes response
     * @throws CouchException
     * @throws Exceptions\CouchNoResponseException
     */
    public function getChanges()
    {
        if (!empty($this->queryParameters['feed']) && $this->queryParameters['feed'] == 'continuous') {
            $url = '/' . urlencode($this->dbname) . '/_changes';
            $opts = $this->queryParameters;
            $this->queryParameters = [];
            $callable = $opts['continuous_feed'];
            unset($opts['continuous_feed']);
            return $this->continuousQuery($callable, 'GET', $url, $opts, null, $this);
        }
        $url = '/' . urlencode($this->dbname) . '/_changes';
        $opts = $this->queryParameters;
        $this->queryParameters = [];
        return $this->queryAndValid('GET', $url, [200, 201], $opts);
    }

    /**
     * fetch multiple revisions at once
     *
     * @link http://wiki.apache.org/couchdb/HTTP_Document_API
     * @SuppressWarnings functionNaming
     * @param array|string $value array of revisions to fetch, or special keyword all
     * @return CouchClient $this
     */
    public function open_revs($value)
    {
        if (is_string($value) && $value == 'all') {
            $this->queryParameters['open_revs'] = 'all';
        } elseif (is_array($value)) {
            $this->queryParameters['open_revs'] = json_encode($value);
        }
        return $this;
    }

    /**
     * fetch a CouchDB document
     *
     * @param string $id document id
     * @return object|array CouchDB document
     * @throws InvalidArgumentException
     * @throws CouchException
     */
    public function getDoc($id)
    {
        if (!strlen($id))
            throw new InvalidArgumentException('Document ID is empty');

        if (preg_match('/^_design/', $id))
            $url = '/' . urlencode($this->dbname) . '/_design/' . urlencode(str_replace('_design/', '', $id));
        else
            $url = '/' . urlencode($this->dbname) . '/' . urlencode($id);

        $docQuery = $this->queryParameters;
        $this->queryParameters = [];

        $back = $this->queryAndValid('GET', $url, [200], $docQuery);
        if (!$this->resultsAsCouchDocs) {
            return $back;
        }
        $this->resultsAsCouchDocs = false;
        $doc = new CouchDocument($this);
        return $doc->loadFromObject($back);
    }

    /**
     * store a CouchDB document
     *
     * @param object $doc document to store
     * @param bool $newEdits Default to true. Id prevents to update a document with a conflicting revision
     * @return object CouchDB document storage response
     * @throws CouchException
     */
    public function storeDoc($doc, $newEdits = true)
    {
        if (!is_object($doc))
            throw new InvalidArgumentException('Document should be an object');
        foreach (array_keys(get_object_vars($doc)) as $key) {
            if (in_array($key, CouchClient::$underscoredPropertiesToRemoveOnStorage)) {
                unset($doc->$key);
            } elseif (substr($key, 0, 1) == '_' && !in_array($key, CouchClient::$allowedUnderscoredProperties))
                throw new InvalidArgumentException("Property $key can't begin with an underscore");
        }
        $method = 'POST';
        $url = '/' . urlencode($this->dbname);
        if (!empty($doc->_id)) {
            $method = 'PUT';
            $url .= '/' . urlencode($doc->_id);
        }

        $params = ["new_edits" => $newEdits ? "true" : "false"];
        return $this->queryAndValid($method, $url, [200, 201, 202], $params, $doc);
    }

    /**
     * Store many CouchDB documents
     *
     * @link http://docs.couchdb.org/en/2.0.0/api/database/bulk-api.html#api-db-bulk-docs
     * @param array $docs array of documents to store
     * @param boolean $newEdits Default to true. If false, prevents the database from assigning them new revision IDs.
     * @return object CouchDB bulk document storage response
     * @throws CouchException
     */
    public function storeDocs(array $docs, $newEdits = true)
    {
        /*
          create the query content
         */
        $request = ['docs' => []];
        foreach ($docs as $doc) {
            if ($doc instanceof CouchDocument) {
                $request['docs'][] = $doc->getFields();
            } else {
                $request['docs'][] = $doc;
            }
        }
        if ($newEdits === false) {
            $request['new_edits'] = false;
        }

        $url = '/' . urlencode($this->dbname) . '/_bulk_docs';
        return $this->queryAndValid('POST', $url, [200, 201, 202], [], $request);
    }

    /**
     * delete many CouchDB documents in a single HTTP request
     *
     * @link http://docs.couchdb.org/en/2.0.0/api/database/bulk-api.html#api-db-bulk-docs
     * @param array $docs array of documents to delete.
     * @param boolean $newEdits Default to true. If false, prevents the database from assigning them new revision IDs.
     * @return object CouchDB bulk document storage response
     * @throws CouchException
     */
    public function deleteDocs(array $docs, $newEdits = true)
    {
        /*
          create the query content
         */
        $request = ['docs' => []];
        foreach ($docs as $doc) {
            $destDoc = null;
            if ($doc instanceof CouchDocument)
                $destDoc = $doc->getFields();
            else
                $destDoc = $doc;

            if (is_array($destDoc))
                $destDoc['_deleted'] = true;
            else
                $destDoc->_deleted = true;
            $request['docs'][] = $destDoc;
        }
        if ($newEdits === false) {
            $request['new_edits'] = false;
        }

        $url = '/' . urlencode($this->dbname) . '/_bulk_docs';
        return $this->queryAndValid('POST', $url, [200, 201, 202], [], $request);
    }

    /**
     * update a couchDB document through an Update Handler
     * wrapper to $this->updateDocFullAPI
     *
     * @link http://wiki.apache.org/couchdb/Document_Update_Handlers
     * @param string $ddocId name of the design doc containing the update handler definition (without _design)
     * @param string $handlerName name of the update handler
     * @param array|object $params parameters to send to the update handler
     * @param string $docIds id of the document to update (can be null)
     * @return array|bool @see updateDocFullAPI($ddoc_id, $handler_name, $options = array())
     * @throws InvalidArgumentException
     * @throws CouchException
     */
    public function updateDoc($ddocId, $handlerName, $params, $docIds = null)
    {
        if (!is_array($params) && !is_object($params))
            throw new InvalidArgumentException('params parameter should be an array or an object');
        if (is_object($params))
            $params = (array)$params;

        $options = [];
        if ($docIds)
            $options['doc_id'] = $docIds;
        if ($params)
            $options['params'] = $params;

        return $this->updateDocFullAPI($ddocId, $handlerName, $options);
    }

    /**
     * update a couchDB document through an Update Handler
     *
     * @link http://wiki.apache.org/couchdb/Document_Update_Handlers
     * @param string $ddocId name of the design doc containing the update handler definition (without _design)
     * @param string $handlerName name of the update handler
     * @param array $options list of optionnal data to send to the couch update handler.
     *        - 'doc_id' : array|object $params parameters to send to the update handler
     *        - 'params' : array|object of variables being sent in the URL ( /?foo=bar )
     *        - 'data'   : string|array|object data being sent in the body of the request.
     *                If data is an array or an object it's parsed through PHP http_build_query function
     *                and the content-type of the request is set to 'application/x-www-form-urlencoded'
     *        - 'Content-Type' : the http header 'Content-Type' to send to the couch server
     * @return bool|object
     * @see _queryAndTest
     * @throws CouchException
     */
    public function updateDocFullAPI($ddocId, $handlerName, $options = [])
    {
        $params = [];
        $data = null;
        $contentType = null;
        $method = 'PUT';
        $url = '/' . urlencode($this->dbname) . '/_design/' . urlencode($ddocId) . '/_update/' . $handlerName . '/';
        if (array_key_exists('doc_id', $options) && is_string($options['doc_id'])) {
            $url .= urlencode($options['doc_id']);
        }
        if (array_key_exists('params', $options) && (is_array($options['params']) || is_object($options['params']))) {
            $params = $options['params'];
        }
        if (array_key_exists('Content-Type', $options) && is_string($options['Content-Type'])) {
            $contentType = $options['Content-Type'];
        }

        if (array_key_exists('data', $options)) {
            if (is_string($options['data'])) {
                $data = $options['data'];
                if (!$contentType)
                    $contentType = 'application/x-www-form-urlencoded';
            } elseif (is_array($options['data']) || is_object($options['data'])) {
                $data = http_build_query($options['data']);
                $contentType = 'application/x-www-form-urlencoded';
            }
        }

        return $this->queryAndValid($method, $url, [200, 201, 202], $params, $data, $contentType);
    }

    /**
     * copy a CouchDB document
     *
     * @param string $id id of the document to copy
     * @param string $newId id of the new document
     * @return object CouchDB document storage response
     * @throws InvalidArgumentException
     * @throws CouchException
     */
    public function copyDoc($id, $newId)
    {
        if (!strlen($id))
            throw new InvalidArgumentException('Document ID is empty');
        if (!strlen($newId))
            throw new InvalidArgumentException('New document ID is empty');

        $method = 'COPY';
        $url = '/' . urlencode($this->dbname);
        $url .= '/' . urlencode($id);
        return $this->queryAndValid($method, $url, [200, 201, 202], [], $newId);
    }

    /**
     * store a CouchDB attachment
     *
     * in this case the attachment content is in a PHP variable
     *
     * @param object $doc doc to store the attachment in
     * @param string $data attachment content
     * @param string $filename attachment name
     * @param string $contentType attachment content type
     * @return object CouchDB attachment storage response
     * @throws InvalidArgumentException
     */
    public function storeAsAttachment($doc, $data, $filename, $contentType = 'application/octet-stream')
    {
        if (!is_object($doc))
            throw new InvalidArgumentException('Document should be an object');
        if (!isset($doc->_id))
            throw new InvalidArgumentException('Document should have an ID');
        $url = '/' . urlencode($this->dbname) . '/' . urlencode($doc->_id) . '/' . urlencode($filename);
        if ($doc->_rev)
            $url .= '?rev=' . $doc->_rev;
        $raw = $this->storeAsFile($url, $data, $contentType);
        $response = $this->parseRawResponse($raw, $this->resultAsArray);
        $this->resultAsArray = false;
        return $response['body'];
    }

    /**
     * Get an attachment file from a document.
     * @param object $doc The document do get the attachment from. The document must has an _id and/or _rev.
     * @param string $attName The attachment name
     * @return string    Returns the raw content from the attachment.
     * @throws InvalidArgumentException if arguments are not valid.
     * @throws CouchException
     */
    public function getAttachment($doc, $attName)
    {
        if (!is_object($doc))
            throw new InvalidArgumentException('Document should be an object');
        if (!isset($doc->_id))
            throw new InvalidArgumentException('Document should have an ID');
        $url = '/' . urlencode($this->dbname) . '/' . urlencode($doc->_id) . '/' . $attName;
        if ($doc->_rev)
            $url .= '?rev=' . urlencode($doc->_rev);
        return $this->queryAndValid('GET', $url, [200]);
    }

    /**
     * store a CouchDB attachment
     *
     * in this case the attachment is a file on the harddrive
     *
     * @param object $doc doc to store the attachment in
     * @param string $file file to attach (complete path on the harddrive)
     * @param string $filename attachment name
     * @param string $contentType attachment content type
     * @return object CouchDB attachment storage response
     * @throws InvalidArgumentException
     */
    public function storeAttachment($doc, $file, $contentType = 'application/octet-stream', $filename = null)
    {
        if (!is_object($doc))
            throw new InvalidArgumentException('Document should be an object');
        if (!isset($doc->_id))
            throw new InvalidArgumentException('Document should have an ID');
        if (!is_file($file))
            throw new InvalidArgumentException("File $file does not exist");
        $url = '/' . urlencode($this->dbname) . '/' . urlencode($doc->_id) . '/';
        $url .= empty($filename) ? urlencode(basename($file)) : urlencode($filename);
        if ($doc->_rev)
            $url .= '?rev=' . $doc->_rev;
        $raw = $this->storeFile($url, $file, $contentType);
        $response = $this->parseRawResponse($raw, $this->resultAsArray);
        return $response['body'];
    }

    /**
     * delete a CouchDB attachment from a document
     *
     * @param object $doc CouchDB document
     * @param string $attachmentName name of the attachment to delete
     * @return object CouchDB attachment removal response
     * @throws InvalidArgumentException
     * @throws CouchException
     */
    public function deleteAttachment($doc, $attachmentName)
    {
        if (!is_object($doc))
            throw new InvalidArgumentException('Document should be an object');
        if (!isset($doc->_id))
            throw new InvalidArgumentException('Document should have an ID');
        if (!strlen($attachmentName))
            throw new InvalidArgumentException('Attachment name not set');
        $url = '/' . urlencode($this->dbname) .
            '/' . urlencode($doc->_id) .
            '/' . urlencode($attachmentName);
        return $this->queryAndValid('DELETE', $url, [200, 202], ['rev' => $doc->_rev]);
    }

    /**
     * remove a document from the database
     *
     * @param object $doc document to remove
     * @return object CouchDB document removal response
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function deleteDoc($doc)
    {
        if (!is_object($doc))
            throw new InvalidArgumentException('Document should be an object');
        if (empty($doc->_id) || empty($doc->_rev)) {
            throw new Exception('Document should contain _id and _rev');
        }
        $url = '/' . urlencode($this->dbname) . '/' . urlencode($doc->_id) . '?rev=' . urlencode($doc->_rev);
        return $this->queryAndValid('DELETE', $url, [200, 202]);
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
     * @return CouchClient $this
     *
     */
    public function asCouchDocuments()
    {
        $this->resultsAsCouchDocs = true;
        $this->resultAsArray = false;
        return $this;
    }

    /**
     * returns couchDB results as array
     *
     * cannot be used in conjunction with asCouchDocuments()
     *
     * @return CouchClient $this
     */
    public function asArray()
    {
        $this->resultAsArray = true;
        $this->resultsAsCouchDocs = false;
        return $this;
    }

    /**
     * lookup $this->viewQuery and prepare view request
     *
     *
     * @return array [ HTTP method , array of view options, data ]
     */
    protected function prepareViewQuery()
    {
        $viewQuery = $this->queryParameters;
        $this->queryParameters = [];
        $method = 'GET';
        $data = null;
        if (isset($viewQuery['keys'])) {
            $method = 'POST';
            $data = json_encode(['keys' => $viewQuery['keys']]);
            unset($viewQuery['keys']);
        }
        return [$method, $viewQuery, $data];
    }

    /**
     * request a view from the CouchDB server
     *
     * @link http://wiki.apache.org/couchdb/HTTP_view_API
     * @param string $id design document name (without _design)
     * @param string $name view name
     * @return object|array|bool
     * @throws InvalidArgumentException
     * @throws CouchException
     */
    public function getView($id, $name)
    {
        if (!$id || !$name)
            throw new InvalidArgumentException('You should specify view id and name');
        $url = '/' . urlencode($this->dbname) . '/_design/' . urlencode($id) . '/_view/' . urlencode($name);
        if ($this->resultsAsCouchDocs)
            $this->include_docs(true);
        $resultsAsCouchDocs = $this->resultsAsCouchDocs;
        $this->resultsAsCouchDocs = false;

        list($method, $viewQuery, $data) = $this->prepareViewQuery();

        if (!$resultsAsCouchDocs)
            return $this->queryAndValid($method, $url, [200], $viewQuery, $data);

        return $this->resultsToCouchDocuments(
            $this->queryAndValid($method, $url, [200], $viewQuery, $data)
        );
    }

    /**
     * returns couchDB view results as couchDocuments objects
     *
     * - for string view keys, the object is found on 'view key' index
     *            ex : view returns
     *            <code>[ 'client' : null , 'client2' : null ]</code>
     *        is translated to :
     *            array ( 'client' => array(couchDocument) , 'client2' => array(couchDocument) )
     *
     * - for array view keys, the object key in the result array is the last key of the view
     *            ex : view returns
     *            <code>[ [ '#44556643', 'client' ] : null , [ '#65745767566','client2' : null ]</code>
     *        is translated to :
     *            array ( 'client' => array(couchDocument) , 'client2' => array(couchDocument) )
     *
     * @param $results object The view resultset
     * @return boolean|array Array of couchDocument objects
     */
    public function resultsToCouchDocuments($results)
    {
        if (!$results->rows || !is_array($results->rows))
            return false;
        $back = [];
        foreach ($results->rows as $row) { // should have $row->key & $row->doc
            if (!$row->key || !$row->doc)
                return false;
            // create CouchDocument
            $cd = new CouchDocument($this);
            $cd->loadFromObject($row->doc);

            // set key name
            if (is_string($row->key))
                $key = $row->key;
            elseif (is_array($row->key)) {
                if (!is_array(end($row->key)) && !is_object(end($row->key)))
                    $key = end($row->key);
                else
                    continue;
            } else continue;

            // set value in result array
            if (isset($back[$key])) {
                if (is_array($back[$key]))
                    $back[$key][] = $cd;
                else
                    $back[$key] = [$back[$key], $cd];
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
     * @param string $viewName view name
     * @param array $additionalParameters some other parameters to send in the query
     * @return object CouchDB list query response
     * @throws InvalidArgumentException
     * @throws CouchException
     */
    public function getList($id, $name, $viewName, $additionalParameters = [])
    {
        if (!$id || !$name)
            throw new InvalidArgumentException('You should specify list id and name');
        if (!$viewName)
            throw new InvalidArgumentException('You should specify view name');
        $urlEnd = '/_list/' . urlencode($name) . '/' . urlencode($viewName);
        $url = '/' . urlencode($this->dbname) . '/_design/' . urlencode($id) . $urlEnd;
        $this->resultsAsCouchDocs = false;
        list($method, $viewQuery, $data) = $this->prepareViewQuery();

        if (is_array($additionalParameters) && count($additionalParameters)) {
            $viewQuery = array_merge($additionalParameters, $viewQuery);
        }
        return $this->queryAndValid($method, $url, [200], $viewQuery, $data);
    }

    /**
     * request a list from the CouchDB server
     *
     * Beginning in CouchDB 0.11, the design doc where the list function is defined can be different from
     * the design doc where the view function is defined
     *
     * So if you got a design doc '_design/example1' with a defined view 'view1', and
     * a design doc '_design/example2' with a defined list 'list1', you can query the view view1
     * and then pass it through the list list1 by using :
     *
     * getForeignList('example2','list1','example1','view1');
     *
     * @link http://wiki.apache.org/couchdb/Formatting_with_Show_and_List
     * @param string $id the name of the design document containing the list (without _design)
     * @param string $name list name
     * @param string $viewId the name of the design document containing the view (without _design)
     * @param string $viewName view name
     * @param array $additionalParams some other parameters to send in the query
     * @return object CouchDB list query response
     * @throws InvalidArgumentException
     * @throws CouchException
     */
    public function getForeignList($id, $name, $viewId, $viewName, $additionalParams = [])
    {
        if (!$id || !$name)
            throw new InvalidArgumentException('You should specify list id and name');
        if (!$viewId || !$viewName)
            throw new InvalidArgumentException('You should specify view id and view name');
        $url = '/' . urlencode($this->dbname) .
            '/_design/' . urlencode($id) . '/_list/' . urlencode($name) .
            '/' . urlencode($viewId) . '/' . urlencode($viewName);
        $this->resultsAsCouchDocs = false;
        list($method, $viewQuery, $data) = $this->prepareViewQuery();

        if (is_array($additionalParams) && count($additionalParams)) {
            $viewQuery = array_merge($additionalParams, $viewQuery);
        }
        return $this->queryAndValid($method, $url, [200], $viewQuery, $data);
    }

    /**
     * request a show from the CouchDB server
     *
     * @link http://wiki.apache.org/couchdb/Formatting_with_Show_and_List
     * @param string $id design document name (without _design)
     * @param string $name show name
     * @param string $docId id of the couchDB document (can be null !)
     * @param array $additionalParams some other parameters to send in the query
     * @return object CouchDB list query response
     * @throws InvalidArgumentException
     * @throws CouchException
     */
    public function getShow($id, $name, $docId = null, $additionalParams = [])
    {
        if (!$id || !$name)
            throw new InvalidArgumentException('You should specify list id and name');
        $url = '/' . urlencode($this->dbname) . '/_design/' . urlencode($id) . '/_show/' . urlencode($name);
        if ($docId)
            $url .= '/' . urlencode($docId);
        return $this->queryAndValid('GET', $url, [200], $additionalParams);
    }

    /**
     * request design doc views informations from the CouchDB server
     *
     * @link http://wiki.apache.org/couchdb/HTTP_view_API
     * @param string $id design document name (without _design)
     * @return object CouchDB view infos response
     * @throws InvalidArgumentException
     * @throws CouchException
     */
    public function getViewInfos($id)
    {
        if (!$id)
            throw new InvalidArgumentException('You should specify view id');
        $url = '/' . urlencode($this->dbname) . '/_design/' . urlencode($id) . '/_info';
        return $this->queryAndValid('GET', $url, [200]);
    }

    /**
     * launch a compact operation on a database design document
     *
     * to compact views defined in _design/thedoc , use compactViews ('thedoc')
     *
     * @param string $id design document name (without _design)
     * @return object CouchDB's compact response ( usually {'ok':true} )
     * @throws CouchException
     */
    public function compactViews($id)
    {
        $formattedId = preg_replace('@^_design/@', '', $id);
        $url = '/' . urlencode($this->dbname) . '/_compact/' . urlencode($formattedId);
        return $this->queryAndValid('POST', $url, [202]);
    }

    /**
     * launch a compact operation on all database design documents
     *
     * to compact views defined in _design/thedoc , use compactViews ('thedoc')
     *
     * @return void
     * @throws CouchException
     */
    public function compactAllViews()
    {
        $response = $this->startkey('_design/')->endkey('_designa')->getAllDocs();
        if (property_exists($response, 'rows') && is_array($response->rows)) {
            foreach ($response->rows as $row) {
                $this->compactViews($row->key);
            }
        }
    }

    /**
     * returns all documents contained in the database
     *
     *
     * @return object CouchDB _all_docs response
     * @throws CouchException
     */
    public function getAllDocs()
    {
        $url = '/' . urlencode($this->dbname) . '/_all_docs';
        list($method, $viewQuery, $data) = $this->prepareViewQuery();
        return $this->queryAndValid($method, $url, [200], $viewQuery, $data);
    }

    /**
     * returns a/some universally unique identifier(s)
     *
     *
     * @param integer $count the number of uuids to return
     * @return array|false an array of uuids on success, false on failure.
     * @throws InvalidArgumentException
     * @throws CouchException
     */
    public function getUuids($count = 1)
    {
        $validCount = (int)$count;
        if ($validCount < 1)
            throw new InvalidArgumentException('Uuid count should be greater than 0');

        $url = '/_uuids';

        $back = $this->queryAndValid('GET', $url, [200], ['count' => $validCount]);
        if ($back && property_exists($back, 'uuids')) {
            return $back->uuids;
        }
        return false;
    }

    /**
     * Synchronize database to disc
     *
     * @return object CouchDB document storage response
     * @throws CouchException
     */
    public function ensureFullCommit()
    {
        $method = 'POST';
        $url = '/' . urlencode($this->dbname) . '/_ensure_full_commit';
        return $this->queryAndValid($method, $url, [200, 201]);
    }

    /**
     * Create an index that will be queryable by the _find endpoint.
     * @see http://docs.couchdb.org/en/2.0.0/api/database/find.html#db-index
     * @throws BadMethodCallException if the type parameter is specified and changed.
     * @param array $fields
     * @param string $name Default to null. If you don't provide a name, it will be automatically generated.
     * @param string $ddoc Default to null. If you don't provide a design document name, one will be generated.
     * @param string $type The type of index. In the future, json and text will be available. For the moment,
     *  the indexes are unavailable.
     * @return object Returns a response object. Usually, it contains the 'result', 'id' and 'name'.
     * @throws CouchException
     */
    public function createIndex(array $fields, $name = null, $ddoc = null, $type = 'json')
    {
        $method = 'POST';
        $request = [
            'index' => [
                'fields' => $fields
            ]
        ];

        //Parameter validation
        if (isset($name))
            $request['name'] = $name;
        if (isset($ddoc))
            $request['ddoc'] = $ddoc;
        if ($type != 'json')
            throw new BadMethodCallException('The type parameter has not been implemented yet.');


        $url = '/' . urlencode($this->dbname) . '/_index';
        return $this->queryAndValid($method, $url, [200], [], $request);
    }

    /**
     * Get the list of indexes in the database.
     * @throws CouchException if an error occured during the request.
     * @return array Returns an array of indexes.
     */
    public function getIndexes()
    {
        $method = 'GET';
        $url = '/' . urlencode($this->dbname) . '/_index';
        $result = $this->queryAndValid($method, $url, [200]);
        return $result->indexes;
    }

    /**
     * Delete the index from the current database.
     * @see http://docs.couchdb.org/en/2.0.0/api/database/find.html#delete--db-_index-designdoc-json-name
     * @throws BadMethodCallException if the type parameter is specified and changed.
     * @param string $ddoc The name of the design document that contain the index.
     * @param string $name The name of the index.
     * @param string $type The type of the index (Not implemented yet).
     * @return object  CouchDB's delete response ( usually {'ok':true} )
     * @throws CouchException
     */
    public function deleteIndex($ddoc, $name, $type = 'json')
    {
        if ($type != 'json')
            throw new BadMethodCallException('The type parameter has not been implemented yet.');
        $method = 'DELETE';
        $urlEnd = urlencode($ddoc) . '/json/' . urlencode($name);
        $url = '/' . urlencode($this->dbname) . '/_index/' . $urlEnd;
        return $this->queryAndValid($method, $url, [200]);
    }

    /**
     * Use the new Mango Query functionnalities to query your database.
     * @see http://docs.couchdb.org/en/2.0.0/api/database/find.html#db-find
     * @return
     * @throws CouchException if an error occurs during the transaction.
     * @param array|object $selector An associative array or an object that follow Mango Query selector syntax.
     * everything.
     * @param array|string $index Optional. Let your determine a specific index to use for your query.
     * @returns array Returns an array of documents.
     */
    public function find($selector, $index = null)
    {
        /*  TODO: Next major version: Return the response object instead of the docs.
            find() call can returns warnings or bookmarks.
        */
        $asArray = $this->resultAsArray;
        $result = $this->_find('_find', $selector, $index);

        if ($asArray) {
            return $result['docs'];
        } else {
            return $result->docs;
        }
    }

    /**
     * Protected function to call the _find and _explain endpoint
     * @return object
     * @throws CouchException if an error occurs during the transaction.
     * @param array|object $selector An associative array or an object that follow Mango Query selector syntax.
     * everything.
     * @param string $endpoint The endpoint to use. Either _explain or _find.
     * @param array|string $index Optional. Let your determine a specific index to use for your query.
     * @returns object Returns an response object with the documents.
     */
    private function _find($endpoint, $selector, $index = null)
    {
        $method = 'POST';
        $url = '/' . urlencode($this->dbname) . '/' . $endpoint;
        $request = [
            'selector' => $selector
        ];

        //Parameter validation
        $fieldsToParse = ['fields', 'limit', 'skip'];
        foreach ($fieldsToParse as $field)
            if (isset($this->queryParameters[$field])) {
                $request[$field] = $this->queryParameters[$field];
                unset($this->queryParameters[$field]);
            }

        if (isset($this->queryParameters['sort'])) {
            $sort = $this->queryParameters['sort'];
            $firstElem = reset($sort);
            if (!is_array($firstElem))
                $sort = [$sort];
            $request['sort'] = $sort;
            unset($this->queryParameters['sort']);
        }

        if (isset($index) && (is_array($index) || is_string($index)))
            $request['use_index'] = $index;

        if (isset($this->queryParameters['conflicts'])) {
            $request['conflicts'] = boolval($this->queryParameters['conflicts']);
            unset($this->queryParameters['conflicts']);
        }

        return $this->queryAndValid($method, $url, [200], [], $request);
    }

    /**
     * Execute a Mango Query on CouchDB and give details about the request.
     * @see http://docs.couchdb.org/en/2.0.0/api/database/find.html#db-explain
     * @return object
     * @throws CouchException if an error occurs during the transaction.
     * @param array|object $selector An associative array or an object that follow Mango Query selector syntax.
     * everything.
     * @param array|string $index Optional. Let your determine a specific index to use for your query.
     * @returns object Returns an object that contains all the information about the query.
     */
    public function explain($selector, $index = null)
    {
        return $this->_find('_explain', $selector, $index);
    }

}
