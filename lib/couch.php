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
* couch class
*
* basics to implement JSON / REST / HTTP CouchDB protocol
*
*/
class couch {
	/**
	* @var string database server hostname
	*/
	protected $hostname = '';
	/**
	* @var integer database server TCP port
	*/
	protected $port = 0;
	/**
	* @var array allowed HTTP methods for REST dialog
	*/
	protected $HTTP_METHODS = array('GET','POST','PUT','DELETE','COPY');
	/**
	* @var resource HTTP servfer socket
	* @see _connect()
	*/
	protected $socket = NULL;

	/**
	* class constructor
	*
	* @param string $hostname CouchDB server host
	*	@param integer $port CouchDB server port
	*/
	public function __construct ($hostname, $port) {
		$this->hostname = $hostname;
		$this->port = $port;
	}

	/**
	* parse a CouchDB server response and sends back an array 
	* the array contains keys :
	* status_code : the HTTP status code returned by the server
	* status_message : the HTTP message related to the status code
	* body : the response body (if any). If CouchDB server response Content-Type is application/json
	*        the body will by json_decode()d
	*
	* @static
	* @param string $raw_data data sent back by the server
	* @param boolean $json_as_array is true, the json response will be decoded as an array. Is false, it's decoded as an object
	* @return array CouchDB response
	*/
	public static function parse_raw_response($raw_data, $json_as_array = FALSE) {
		$response = array();
		list($headers, $body) = explode("\r\n\r\n", $raw_data);
		$status_line = reset(explode("\n",$headers));
		$status_array = explode(' ',$status_line,3);
		$response['status_code'] = trim($status_array[1]);
		$response['status_message'] = trim($status_array[2]);
		if ( strlen($body) ) {
			if ( preg_match('@Content-Type:\s+application/json@',$headers) )
				$response['body'] = json_decode($body,$json_as_array);
			else
				$response['body'] = $body;
		} else {
			$response['body'] = null;
		}
		return $response;
	}

	/**
	* build HTTP request to send to the server
	*
	* @param string $method HTTP method to use
	* @param string $url the request URL
	* @param string|object|array $data the request body. If it's an array or an object, $data is json_encode()d
	* @return string HTTP request
	*/
	protected function _build_request($method,$url,$data) {
		if ( is_object($data) OR is_array($data) )
			$data = json_encode($data);
		$req = "$method $url HTTP/1.0\r\nHost: {$this->hostname}\r\n";
	           $req.= "Accept: application/json,text/html,text/plain,*/*\r\n";
    if ( $method == 'COPY') {
      $req .= 'Destination: '.$data."\r\n\r\n";
    } elseif ($data) {
			$req .= 'Content-Length: '.strlen($data)."\r\n";
			$req .= 'Content-Type: application/json'."\r\n\r\n";
			$req .= $data."\r\n";
		} else {
			$req .= "\r\n";
		}
		return $req;
	}

	/**
	*open the connection to the CouchDB server
	*
	*This function can throw an Exception if it fails
	*
	* @return boolean wheter the connection is successful
	*/
	protected function _connect() {
		$this->socket = @fsockopen($this->hostname, $this->port, $err_num, $err_string);
		if(!$this->socket) {
			throw new Exception('Could not open connection to '.$this->hostname.':'.$this->port.': '.$err_string.' ('.$err_num.')');
			return FALSE;
		}
		return TRUE;
	}

	/**
	*send the HTTP request to the server and read the response
	*
	* @param string $request HTTP request to send
	* @return string $response HTTP response from the CouchDB server
	*/
	protected function _execute($request) {
		fwrite($this->socket, $request);
		$response = '';
		while(!feof($this->socket)) 
			$response .= fgets($this->socket);
		return $response;
	}

	/**
	*closes the connection to the server
	*
	*
	*/
	protected function _disconnect() {
		@fclose($this->socket);
		$this->socket = NULL;
	}

	/**
	*send a query to the CouchDB server
	*
	* @param string $method HTTP method to use (GET, POST, ...)
	* @param string $url URL to fetch
	* @param array $parameters additionnal parameters to send with the request
	* @param string|array|object $data request body
	*
	* @return string|false server response on success, false on error
	*/
	public function query ( $method, $url, $parameters = array() , $data = NULL ) {
		if ( !in_array($method, $this->HTTP_METHODS )    ) {
			throw new Exception("Bad HTTP method: $method");
			return FALSE;
		}

		if ( is_array($parameters) AND count($parameters) )
			$url = $url.'?'.http_build_query($parameters);

		$request = $this->_build_request($method,$url,$data);
		if ( !$this->_connect() )	return FALSE;
		$raw_response = $this->_execute($request);
		$this->_disconnect();

    //log_message('debug',"COUCH : Executed query $method $url");

		return $raw_response;
	}

	/**
	* record a file located on the disk as a CouchDB attachment
	*
	* @param string $url CouchDB URL to store the file to
	* @param string $file path to the on-disk file
	* @param string $content_type attachment content_type
	*	
	* @return string server response
	*/
  public function store_file ( $url, $file, $content_type ) {

    $req  = "PUT $url HTTP/1.0\r\nHost: {$this->hostname}\r\n";
	  $req .= "Accept: application/json,text/html,text/plain,*/*\r\n";
  	$req .= 'Content-Length: '.filesize($file)."\r\n";
		$req .= 'Content-Type: '.$content_type."\r\n\r\n";
    $fstream=fopen($file,'r');
    $this->_connect();
    fwrite($this->socket, $req);
    stream_copy_to_stream($fstream,$this->socket);
    $response = '';
    while(!feof($this->socket))
			$response .= fgets($this->socket);
    $this->_disconnect();
    fclose($fstream);
    return $response;
  }

	/**
	* store some data as a CouchDB attachment
	*
	* @param string $url CouchDB URL to store the file to
	* @param string $data data to send as the attachment content
	* @param string $content_type attachment content_type
	*	
	* @return string server response
	*/
  public function store_as_file($url,$data,$content_type) {
    $req  = "PUT $url HTTP/1.0\r\nHost: {$this->hostname}\r\n";
	  $req .= "Accept: application/json,text/html,text/plain,*/*\r\n";
  	$req .= 'Content-Length: '.strlen($data)."\r\n";
		$req .= 'Content-Type: '.$content_type."\r\n\r\n";
    $this->_connect();
    fwrite($this->socket, $req);
    fwrite($this->socket, $data);
    $response = '';
    while(!feof($this->socket))
			$response .= fgets($this->socket);
    $this->_disconnect();
    return $response;
  }

}
