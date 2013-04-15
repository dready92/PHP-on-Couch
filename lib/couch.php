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

/**
* couch class
*
* basics to implement JSON / REST / HTTP CouchDB protocol
*
*/
class couch {
	/**
	* @var string database source name
	*/
	protected $dsn = '';

	/**
	* @var array database source name parsed
	*/
	protected $dsn_parsed = null;

	/**
	* @var array couch options
	*/
	protected $options = null;
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
	* @var string the session cookie
	*/
	protected $sessioncookie = null;

	/**
	* class constructor
	*
	* @param string $dsn CouchDB Data Source Name
	*	@param array $options Couch options
	*/
	public function __construct ($dsn, $options = array() ) {
		$this->dsn = preg_replace('@/+$@','',$dsn);
		$this->options = $options;
		$this->dsn_parsed = parse_url($this->dsn);
		if ( !isset($this->dsn_parsed['port']) ) {
			$this->dsn_parsed['port'] = 80;
		}
		if ( function_exists('curl_init') )	$this->curl = TRUE;
	}


	/**
	* returns the DSN, untouched
	*
	* @return string DSN
	*/
	public function dsn() {
		return $this->dsn;
	}

	/**
	* returns the options array
	*
	* @return string DSN
	*/
	public function options() {
		return $this->options;
	}

	/**
	* set the session cookie to send in the headers
	* @param string $cookie the session cookie ( example : AuthSession=Y291Y2g6NENGNDgzNz )
	*
	* @return \couch
	*/
	public function setSessionCookie ( $cookie ) {
		$this->sessioncookie = $cookie;
		return $this;
	}


	/**
	* return a part of the data source name
	*
	* if $part parameter is empty, returns dns array
	*
	* @param string $part part to return
	* @return string DSN part
	*/
	public function dsn_part($part = null) {
		if ( !$part ) {
			return $this->dsn_parsed;
		}
		if ( isset($this->dsn_parsed[$part]) ) {
			return $this->dsn_parsed[$part];
		}
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
	* @throws InvalidArgumentException
	*/
	public static function parseRawResponse($raw_data, $json_as_array = FALSE) {
		if ( !strlen($raw_data) ) throw new InvalidArgumentException("no data to parse");
		while ( !substr_compare($raw_data, "HTTP/1.1 100 Continue\r\n\r\n", 0, 25) ) {
			$raw_data = substr($raw_data, 25);
		}
		$response = array('body'=>null);
		list($headers, $body) = explode("\r\n\r\n", $raw_data,2);
		$headers_array=explode("\n",$headers);
		$status_line = reset($headers_array);
		$status_array = explode(' ',$status_line,3);
		$response['status_code'] = trim($status_array[1]);
		$response['status_message'] = trim($status_array[2]);
		if ( strlen($body) ) {
			$response['body'] = preg_match('@Content-Type:\s+application/json@i',$headers) ? json_decode($body,$json_as_array) : $body ;
		}
		return $response;
	}

	/**
	*send a query to the CouchDB server
	*
	* @param string $method HTTP method to use (GET, POST, ...)
	* @param string $url URL to fetch
	* @param array $parameters additionnal parameters to send with the request
	* @param string $content_type the content type of the sent data (defaults to application/json)
	* @param string|array|object $data request body
	*
	* @return string|false server response on success, false on error
	*/
	public function query ( $method, $url, $parameters = array() , $data = NULL, $content_type = NULL ) {
		if ( $this->curl )	return $this->_curl_query($method,$url,$parameters, $data, $content_type);
		else				return $this->_socket_query($method,$url,$parameters, $data, $content_type);
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
		else				return $this->_socket_storeFile($url,$file,$content_type);
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
		else				return $this->_socket_storeAsFile($url,$data,$content_type);

	}

	/**
	*send a query to the CouchDB server
	*
	* In a continuous query, the server send headers, and then a JSON object per line.
	* On each line received, the $callable callback is fired, with two arguments :
	*
	* - the JSON object decoded as a PHP object
	*
	* - a couchClient instance to use to make queries inside the callback
	*
	* If the callable returns the boolean FALSE , continuous reading stops.
	*
	* @param callable $callable PHP function name / callable array ( see http://php.net/is_callable )
	* @param string $method HTTP method to use (GET, POST, ...)
	* @param string $url URL to fetch
	* @param array $parameters additionnal parameters to send with the request
	* @param string|array|object $data request body
	*
	* @return string|false server response on success, false on error
	*
	* @throws Exception|InvalidArgumentException|couchException|couchNoResponseException
	*/
	public function continuousQuery($callable,$method,$url,$parameters = array(),$data = null) {
		if ( !in_array($method, $this->HTTP_METHODS )    )
			throw new Exception("Bad HTTP method: $method");
		if ( !is_callable($callable) )
			throw new InvalidArgumentException("callable argument have to success to is_callable PHP function");
		if ( is_array($parameters) AND count($parameters) )
			$url = $url.'?'.http_build_query($parameters);
        //Send the request to the socket
		$request = $this->_socket_buildRequest($method,$url,$data,null);
		if ( !$this->_connect() )	return FALSE;
		fwrite($this->socket, $request);

		//Read the headers and check that the response is valid
		$response = '';
		$headers = false;
		while (!feof($this->socket)&& !$headers) {
			$response.=fgets($this->socket);
            if ($response == "HTTP/1.1 100 Continue\r\n\r\n") { $response = ''; continue; } //Ignore 'continue' headers, they will be followed by the real header.
			elseif (preg_match("/\r\n\r\n$/",$response) ) {
				$headers = true;
			}
		}
		$headers = explode("\n",trim($response));
		$split=explode(" ",trim(reset($headers)));
		$code = $split[1];
		unset($split);
        //If an invalid response is sent, read the rest of the response and throw an appropriate couchException
        if (!in_array($code,array(200,201))) {
            stream_set_blocking($this->socket,false);
            $response .= stream_get_contents($this->socket);
            fclose($this->socket);
            throw couchException::factory($response, $method, $url, $parameters);
        }

        //For as long as the socket is open, read lines and pass them to the callback
		$c = clone $this;
		while ($this->socket && !feof($this->socket)) {
			$e = NULL;
			$e2 = NULL;
			$read = array($this->socket);
			if (false === ($num_changed_streams = stream_select($read, $e, $e2, 1))) {
				$this->socket = null;
			} elseif ($num_changed_streams > 0) {
				$line = fgets($this->socket);
				if ( strlen(trim($line)) ) {
					$break = call_user_func($callable,json_decode($line),$c);
					if ( $break === FALSE ) {
						fclose($this->socket);
					}
				}
			}
		}
		return $code;
	}


	/**
	*send a query to the CouchDB server
	*
	* @param string $method HTTP method to use (GET, POST, ...)
	* @param string $url URL to fetch
	* @param array $parameters additionnal parameters to send with the request
	* @param string $content_type the content type of the sent data (defaults to application/json)
	* @param string|array|object $data request body
	*
	* @return string|false server response on success, false on error
	*
	* @throws Exception
	*/
	public function _socket_query ( $method, $url, $parameters = array() , $data = NULL, $content_type = NULL ) {
		if ( !in_array($method, $this->HTTP_METHODS )    )
			throw new Exception("Bad HTTP method: $method");

		if ( is_array($parameters) AND count($parameters) )
			$url = $url.'?'.http_build_query($parameters);

		$request = $this->_socket_buildRequest($method,$url,$data, $content_type);
		if ( !$this->_connect() )	return FALSE;
// 		echo "DEBUG: Request ------------------ \n$request\n";
		$raw_response = $this->_execute($request);
		$this->_disconnect();

// 		echo 'debug',"COUCH : Executed query $method $url";
// 		echo 'debug',"COUCH : ".$raw_response;
		return $raw_response;
	}


	/**
	* returns first lines of request headers
	*
	* lines :
	* <code>
	* VERB HTTP/1.0
	* Host: my.super.server.com
	* Authorization: Basic...
	* Accept: application/json,text/html,text/plain,* /*
    * </code>
	*
	* @param string $method HTTP method to use
	* @param string $url the request URL
	* @return string start of HTTP request
	*/
	protected function _socket_startRequestHeaders($method,$url) {
      if ( $this->dsn_part('path') ) $url = $this->dsn_part('path').$url;
		$req = "$method $url HTTP/1.0\r\nHost: ".$this->dsn_part('host')."\r\n";
		if ( $this->dsn_part('user') && $this->dsn_part('pass') ) {
		  $req .= 'Authorization: Basic '.base64_encode($this->dsn_part('user').':'.
		        	$this->dsn_part('pass'))."\r\n";
		} elseif ( $this->sessioncookie ) {
			$req .= "Cookie: ".$this->sessioncookie."\r\n";
		}
		$req.="Accept: application/json,text/html,text/plain,*/*\r\n";

		return $req;
	}

	/**
	* build HTTP request to send to the server
	*
	* @param string $method HTTP method to use
	* @param string $url the request URL
	* @param string|object|array $data the request body. If it's an array or an object, $data is json_encode()d
	* @param string $content_type the content type of the sent data (defaults to application/json)
	* @return string HTTP request
	*/
	protected function _socket_buildRequest($method,$url,$data, $content_type) {
		if ( is_object($data) OR is_array($data) )
			$data = json_encode($data);
		$req = $this->_socket_startRequestHeaders($method,$url);
		if ( $content_type ) {
			$req .= 'Content-Type: '.$content_type."\r\n";
		} else {
			$req .= 'Content-Type: application/json'."\r\n";
		}
		if ( $method == 'COPY') {
			$req .= 'Destination: '.$data."\r\n\r\n";
		} elseif ($data) {
			$req .= 'Content-Length: '.strlen($data)."\r\n\r\n";
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
	*
	* @throws InvalidArgumentException
	*/
	protected function _socket_storeFile($url,$file,$content_type) {

		if ( !strlen($url) )	throw new InvalidArgumentException("Attachment URL can't be empty");
		if ( !strlen($file) OR !is_file($file) OR !is_readable($file) )	throw new InvalidArgumentException("Attachment file does not exist or is not readable");
		if ( !strlen($content_type) ) throw new InvalidArgumentException("Attachment Content Type can't be empty");
		$req = $this->_socket_startRequestHeaders('PUT',$url);
		$req .= 'Content-Length: '.filesize($file)."\r\n"
				.'Content-Type: '.$content_type."\r\n\r\n";
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
	*
	* @throws InvalidArgumentException
	*/
  public function _socket_storeAsFile($url,$data,$content_type) {
		if ( !strlen($url) )	throw new InvalidArgumentException("Attachment URL can't be empty");
		if ( !strlen($content_type) ) throw new InvalidArgumentException("Attachment Content Type can't be empty");

		$req = $this->_socket_startRequestHeaders('PUT',$url);
		$req .= 'Content-Length: '.strlen($data)."\r\n"
				.'Content-Type: '.$content_type."\r\n\r\n";
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
	*
	* @throws Exception
	*/
	protected function _connect() {
		$ssl = $this->dsn_part('scheme') == 'https' ? 'ssl://' : '';
		$this->socket = @fsockopen($ssl.$this->dsn_part('host'), $this->dsn_part('port'), $err_num, $err_string);
		if(!$this->socket) {
			throw new Exception('Could not open connection to '.$this->dsn_part('host').':'.$this->dsn_part('port').': '.$err_string.' ('.$err_num.')');
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

	/*
	* add user-defined options to Curl resource
	*/
	protected function _curl_addCustomOptions ($res) {
		if ( array_key_exists("curl",$this->options) && is_array($this->options["curl"]) ) {
			curl_setopt_array($res,$this->options["curl"]);
		}
	}


	/**
	* build HTTP request to send to the server
	* uses PHP cURL API
	*
	* @param string $method HTTP method to use
	* @param string $url the request URL
	* @param string|object|array $data the request body. If it's an array or an object, $data is json_encode()d
	* @param string $content_type the content type of the sent data (defaults to application/json)
	* @return resource CURL request resource
	*/
	protected function _curl_buildRequest($method,$url,$data,$content_type) {
		$http = curl_init($url);
		$http_headers = array('Accept: application/json,text/html,text/plain,*/*') ;
		if ( is_object($data) OR is_array($data) ) {
			$data = json_encode($data);
		}
		if ( $content_type ) {
			$http_headers[] = 'Content-Type: '.$content_type;
		} else {
			$http_headers[] = 'Content-Type: application/json';
		}
		if ( $this->sessioncookie ) {
			$http_headers[] = "Cookie: ".$this->sessioncookie;
		}
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
	* @param string $content_type the content type of the sent data (defaults to application/json)
	*
	* @return string|false server response on success, false on error
	*
	* @throws Exception
	*/
	public function _curl_query ( $method, $url, $parameters = array() , $data = NULL, $content_type = NULL ) {
		if ( !in_array($method, $this->HTTP_METHODS )    )
			throw new Exception("Bad HTTP method: $method");

		$url = $this->dsn.$url;
		if ( is_array($parameters) AND count($parameters) )
			$url = $url.'?'.http_build_query($parameters);
		$http = $this->_curl_buildRequest($method,$url,$data, $content_type);
		$this->_curl_addCustomOptions ($http);
		curl_setopt($http,CURLOPT_HEADER, true);
		curl_setopt($http,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($http,CURLOPT_FOLLOWLOCATION, true);

		$response = curl_exec($http);
		curl_close($http);

		return $response;
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
	*
	* @throws InvalidArgumentException
	*/
	public function _curl_storeFile ( $url, $file, $content_type ) {
		if ( !strlen($url) )	throw new InvalidArgumentException("Attachment URL can't be empty");
		if ( !strlen($file) OR !is_file($file) OR !is_readable($file) )	throw new InvalidArgumentException("Attachment file does not exist or is not readable");
		if ( !strlen($content_type) ) throw new InvalidArgumentException("Attachment Content Type can't be empty");
		$url = $this->dsn.$url;
		$http = curl_init($url);
		$http_headers = array(
			'Accept: application/json,text/html,text/plain,*/*',
			'Content-Type: '.$content_type,
			'Expect: '
		);
		if ( $this->sessioncookie ) {
			$http_headers[] = "Cookie: ".$this->sessioncookie;
		}
		curl_setopt($http, CURLOPT_PUT, 1);
		curl_setopt($http, CURLOPT_HTTPHEADER,$http_headers);
		curl_setopt($http, CURLOPT_UPLOAD, true);
		curl_setopt($http, CURLOPT_HEADER, true);
		curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($http, CURLOPT_FOLLOWLOCATION, true);
		$fstream=fopen($file,'r');
		curl_setopt($http, CURLOPT_INFILE, $fstream);
		curl_setopt($http, CURLOPT_INFILESIZE, filesize($file));
		$this->_curl_addCustomOptions ($http);
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
	*
	* @throws InvalidArgumentException
	*/
	public function _curl_storeAsFile($url,$data,$content_type) {
		if ( !strlen($url) )	throw new InvalidArgumentException("Attachment URL can't be empty");
		if ( !strlen($content_type) ) throw new InvalidArgumentException("Attachment Content Type can't be empty");
		$url = $this->dsn.$url;
		$http = curl_init($url);
		$http_headers = array(
			'Accept: application/json,text/html,text/plain,*/*',
			'Content-Type: '.$content_type,
			'Expect: ',
			'Content-Length: '.strlen($data)
		) ;
		if ( $this->sessioncookie ) {
			$http_headers[] = "Cookie: ".$this->sessioncookie;
		}
		curl_setopt($http, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($http, CURLOPT_HTTPHEADER,$http_headers);
		curl_setopt($http, CURLOPT_HEADER, true);
		curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($http, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($http, CURLOPT_POSTFIELDS, $data);
		$this->_curl_addCustomOptions ($http);
		$response = curl_exec($http);
		curl_close($http);
		return $response;
	}

}

