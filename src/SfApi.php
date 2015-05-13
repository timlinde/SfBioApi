<?php

class Sf {


	const API_URL = 'https://mobilebackend.sfbio.se/services/4/';
	
	/**
	* The SF Digest API User.
	*
	* @var string
	*/
	private $_apiuser = 'SFbioAPI';
	/**
	* The SF Digest API password.
	*
	* @var string
	*/
	private $_apipassword = 'bSF5PFHcR4Z3';


	/**
	* Default constructor.
	*
	* @param array|string $config          Sf configuration data
	*
	* @return void
	*
	* @throws \sf\sf\Sf
	*/
	public function __construct($config) {
		if (is_array($config)) {
		  // if you want to access user data
		  $this->_apiuser = $config['apiUser'];
		  $this->_apipassword = $config['apiPassword'];
		} else {
		  throw new Exception("Error: __construct() - Configuration data is missing.");
		}
	}

	public function getMovie($movieId, $cityId = 'BS') {
		return $this->_makeCall('movies/moviedetail/' . $movieId . '?cityid=' . $cityId);
	}

	public function getAvailableMovies() {
		return $this->_makeCall('tickets/currentmovies/BS', 'GET');
	}

	public function getUpcomingMovies() {
		return $this->_makeCall('movies/upcomingmovies', 'GET');
	}

	public function getTicketsOfMovie($movieId, $date, $cityId) {
		return $this->_makeCall('tickets/shows?cityid=' . $cityId . '&movieid=' . $movieId . '&date=' . $date . '&includeAllVersions=true', 'GET');
	}

	public function getAvailableSeats() {
		//$theatreName, $sys99Code, $movieId, $date, $cityId
		//return $this->_makeCall('auditorium/layout/2001/5/60/50');
		//return $this->_makeCall('auditorium/layout/2001/4/60/50');
		return $this->_makeCall('auditorium/layout/2001/1/60/50');
	}

	public function bookTickets($cityId = 'BS', $sys99Code, $datetime, $seats = null) {

		$postData = [
			'cityid' => $cityId,
			'auditoriumsys99code' => $sys99Code,
			'datetime' => $datetime,
			'seatkey' => ':36'
		];

		return $this->_makeCall(
			'tickets/lockseats', 
			'POST',
			$postData
		);
	}

	public function releaseTickets($bookingId) {
		return $this->_makeCall(
			'tickets/unlockseats', 
			'POST',
			[
				'bookingid' => $bookingId
			]
		);
	}

	public function getOffers($cityId = 'BS') {
		return $this->_makeCall('offers/list?uuid=7954B0AE-AC97-435D-A0EB-350932BAF933&ostype=Iphone&cityid=' . $cityId, 'GET');
	}

	public function testCase() {
		return $this->_makeCall('offers/list?uuid=7954B0AE-AC97-435D-A0EB-350932BAF933&ostype=Iphone&cityid=BS', 'GET');
	}

	private function parseHttpDigest($digest) {
	    $data = array();
	    $parts = explode(", ", $digest);

	    foreach ($parts as $element) {
	    	$bits = explode("=", $element);
	    	$data[$bits[0]] = str_replace('"','', $bits[1]);
	    }
	    $data['nonce'] = $data['nonce'] . '==';
	    return $data;
	}

	private function generateDigestHeader($data) {
		$A1 = md5($this->_apiuser . ':' . $data['Digest realm'] . ':' . $this->_apipassword);
		$A2 = md5("GET" . ':' . $data['uri']);
		$responseHeader = md5("{$A1}:{$data['nonce']}:{$data['ncvalue']}:{$data['cnonce']}:{$data['qop']}:{$A2}");
		$authString  = 'Digest username="' . $this->_apiuser . '", realm="';
        $authString .= $data['Digest realm'].'", nonce="'.$data['nonce'].'",';
        $authString .= ' uri="' . $data['uri'] . '", cnonce="' . $data['cnonce'];
        $authString .= '", nc="' . $data['ncvalue'] . '", response="'.$responseHeader.'", qop="auth"';
        return $authString;
	}

	private function get_headers_from_curl_response($response)
	{
	    $headers = array();

	    $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

	    foreach (explode("\r\n", $header_text) as $i => $line)
	        if ($i === 0)
	            $headers['http_code'] = $line;
	        else
	        {
	            list ($key, $value) = explode(': ', $line);

	            $headers[$key] = $value;
	        }

	    return $headers;
	} 

	protected function _makeCall($function, $method = 'GET', $postData = null, $digestData = null) {

		if (is_null($digestData)) {
			$headerData = [
		    	'User-Agent: SF Bio 4.0.2 rv:256 (iPhone; iPhone OS 8.3; sv_SE)',
		    	'X-SF-Iphone-Version: 4.0.2',
		    	'Authorization: Basic U0ZiaW9BUEk6YlNGNVBGSGNSNFoz'
		    ];
		} else {

			$digestData['uri'] = '/services/4/' . $function;
			$digestData['cnonce'] = md5(time());
			$digestData['ncvalue'] = '00000001';
			$authString = $this->generateDigestHeader($digestData);
			
			$headerData = [
		    	'User-Agent: SF Bio 4.0.2 rv:256 (iPhone; iPhone OS 8.3; sv_SE)',
		    	'X-SF-Iphone-Version: 4.0.2',
		    	'Authorization: ' . $authString
		    ];
		}

		$paramString = null;
		if (isset($postData) && is_array($postData)) {
			$paramString = '&' . http_build_query($postData);
		}

		$apiCall = self::API_URL . $function;

		$ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $apiCall);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerData);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_HEADER, true);

		if ('POST' === $method) {
			curl_setopt($ch, CURLOPT_POST, count($postData));
			curl_setopt($ch, CURLOPT_POSTFIELDS, ltrim($paramString, '&'));
		}

	    $jsonData = curl_exec($ch);

	    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	    $header = substr($jsonData, 0, $header_size);
		$body = substr($jsonData, $header_size);

	    if (is_null($digestData)) {
		    $headers = $this->get_headers_from_curl_response($jsonData);
		    return $this->_makeCall($function, $method, $postData, $this->parseHttpDigest($headers['WWW-Authenticate']));
		} else {
			return json_decode($body);
		}
	}
}