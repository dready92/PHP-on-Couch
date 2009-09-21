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
	* @var resource HTTP server socket
	* @see _connect()
	*/
	protected $socket = NULL;

	/**
	* @var boolean tell if curl PHP extension has been detected
	*/
	protected $curl = FALSE;

	/**
	* class constructor
	*
	* @param string $hostname CouchDB server host
	*	@param integer $port CouchDB server port
	*/
	public function __construct ($hostname, $port) {
		$this->hostname = $hostname;
		$this->port = $port;
		if ( function_exists('curl_init') )	$this->curl = TRUE;
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
	public static function parseRawResponse($raw_data, $json_as_array = FALSE) {
		if ( !strlen($raw_data) ) throw new InvalidArgumentException("no data to parse");
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
		if ( $this->curl )	return $this->_curl_query($method,$url,$parameters, $data);
		else								return $this->_socket_query($method,$url,$parameters, $data);
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
  public function storeFile ( $url, $file, $content_type ) {
		if ( $this->curl )	return $this->_curl_storeFile($url,$file,$content_type);
		else								return $this->_socket_storeFile($url,$file,$content_type);
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
  public function storeAsFile($url,$data,$content_type) {
		if ( $this->curl )	return $this->_curl_storeAsFile($url,$data,$content_type);
		else								return $this->_socket_storeAsFile($url,$data,$content_type);

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
	public function _socket_query ( $method, $url, $parameters = array() , $data = NULL ) {
		if ( !in_array($method, $this->HTTP_METHODS )    )
			throw new Exception("Bad HTTP method: $method");

		if ( is_array($parameters) AND count($parameters) )
			$url = $url.'?'.http_build_query($parameters);

		$request = $this->_socket_buildRequest($method,$url,$data);
		if ( !$this->_connect() )	return FALSE;
		$raw_response = $this->_execute($request);
		$this->_disconnect();

    //log_message('debug',"COUCH : Executed query $method $url");
    //log_message('debug',"COUCH : ".$raw_response);
		return $raw_response;
	}


	/**
	* build HTTP request to send to the server
	*
	* @param string $method HTTP method to use
	* @param string $url the request URL
	* @param string|object|array $data the request body. If it's an array or an object, $data is json_encode()d
	* @return string HTTP request
	*/
	protected function _socket_buildRequest($method,$url,$data) {
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
	* record a file located on the disk as a CouchDB attachment
	* uses PHP socket API
	*
	* @param string $url CouchDB URL to store the file to
	* @param string $file path to the on-disk file
	* @param string $content_type attachment content_type
	*
	* @return string server response
	*/
	protected function _socket_storeFile($url,$file,$content_type) {
		if ( !strlen($url) )	throw new InvalidArgumentException("Attachment URL can't be empty");
		if ( !strlen($file) OR !is_file($file) OR !is_readable($file) )	throw new InvalidArgumentException("Attachment file does not exist or is not readable");
		if ( !strlen($content_type) ) throw new InvalidArgumentException("Attachment Content Type can't be empty");
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
	* uses PHP socket API
	*
	* @param string $url CouchDB URL to store the file to
	* @param string $data data to send as the attachment content
	* @param string $content_type attachment content_type
	*	
	* @return string server response
	*/
  public function _socket_storeAsFile($url,$data,$content_type) {
	if ( !strlen($url) )	throw new InvalidArgumentException("Attachment URL can't be empty");
	if ( !strlen($content_type) ) throw new InvalidArgumentException("Attachment Content Type can't be empty");
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
	* build HTTP request to send to the server
	* uses PHP cURL API
	*
	* @param string $method HTTP method to use
	* @param string $url the request URL
	* @param string|object|array $data the request body. If it's an array or an object, $data is json_encode()d
	* @return resource CURL request resource
	*/
	protected function _curl_buildRequest($method,$url,$data) {
		$http = curl_init($url);
		$http_headers = array('Accept: application/json,text/html,text/plain,*/*') ;
		if ( is_object($data) OR is_array($data) )
			$data = json_encode($data);

		curl_setopt($http, CURLOPT_CUSTOMREQUEST, $method);

    if ( $method == 'COPY') {
      $http_headers[] = "Destination: $data";
    } elseif ($data) {
			curl_setopt($http, CURLOPT_POSTFIELDS, $data);
		}

		curl_setopt($http, CURLOPT_HTTPHEADER,$http_headers);

		return $http;
	}


	/**
	*send a query to the CouchDB server
	* uses PHP cURL API
	*
	* @param string $method HTTP method to use (GET, POST, ...)
	* @param string $url URL to fetch
	* @param array $parameters additionnal parameters to send with the request
	* @param string|array|object $data request body
	*
	* @return string|false server response on success, false on error
	*/
	public function _curl_query ( $method, $url, $parameters = array() , $data = NULL ) {
		if ( !in_array($method, $this->HTTP_METHODS )    )
			throw new Exception("Bad HTTP method: $method");

		$url = 'http://'.$this->hostname.':'.$this->port.$url;
		if ( is_array($parameters) AND count($parameters) )
			$url = $url.'?'.http_build_query($parameters);

		$http = $this->_curl_buildRequest($method,$url,$data);
		curl_setopt($http,CURLOPT_HEADER, true);
		curl_setopt($http,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($http,CURLOPT_FOLLOWLOCATION, true);

		$response = curl_exec($http);
		curl_close($http);
		//echo $response;

		return $response;
    //log_message('debug',"COUCH : Executed query $method $url");
    //log_message('debug',"COUCH : ".$raw_response);

	}

	/**
	* record a file located on the disk as a CouchDB attachment
	* uses PHP cURL API
	*
	* @param string $url CouchDB URL to store the file to
	* @param string $file path to the on-disk file
	* @param string $content_type attachment content_type
	*
	* @return string server response
	*/
  public function _curl_storeFile ( $url, $file, $content_type ) {
	if ( !strlen($url) )	throw new InvalidArgumentException("Attachment URL can't be empty");
	if ( !strlen($file) OR !is_file($file) OR !is_readable($file) )	throw new InvalidArgumentException("Attachment file does not exist or is not readable");
	if ( !strlen($content_type) ) throw new InvalidArgumentException("Attachment Content Type can't be empty");
	$url = 'http://'.$this->hostname.':'.$this->port.$url;
	$http = curl_init($url);
	$http_headers = array('Accept: application/json,text/html,text/plain,*/*','Content-Type: '.$content_type) ;
	curl_setopt($http, CURLOPT_PUT, 1);
	curl_setopt($http, CURLOPT_HTTPHEADER,$http_headers);
	curl_setopt($http, CURLOPT_UPLOAD, true);
	curl_setopt($http, CURLOPT_HEADER, true);
	curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($http, CURLOPT_FOLLOWLOCATION, true);
	$fstream=fopen($file,'r');
	curl_setopt($http, CURLOPT_INFILE, $fstream);
	curl_setopt($http, CURLOPT_INFILESIZE, filesize($file));
	$response = curl_exec($http);
	fclose($fstream);
	curl_close($http);
	return $response;
  }

	/**
	* store some data as a CouchDB attachment
	* uses PHP cURL API
	*
	* @param string $url CouchDB URL to store the file to
	* @param string $data data to send as the attachment content
	* @param string $content_type attachment content_type
	*
	* @return string server response
	*/
  public function _curl_storeAsFile($url,$data,$content_type) {
	if ( !strlen($url) )	throw new InvalidArgumentException("Attachment URL can't be empty");
	if ( !strlen($content_type) ) throw new InvalidArgumentException("Attachment Content Type can't be empty");
	$url = 'http://'.$this->hostname.':'.$this->port.$url;
	$http = curl_init($url);
	$http_headers = array('Accept: application/json,text/html,text/plain,*/*','Content-Type: '.$content_type,'Content-Length: '.strlen($data)) ;
	curl_setopt($http, CURLOPT_CUSTOMREQUEST, 'PUT');
	curl_setopt($http, CURLOPT_HTTPHEADER,$http_headers);
	curl_setopt($http, CURLOPT_HEADER, true);
	curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($http, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($http, CURLOPT_POSTFIELDS, $data);
	$response = curl_exec($http);
	curl_close($http);
	return $response;
  }

}

