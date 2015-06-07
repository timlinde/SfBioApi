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

	/**
	* Get movie by its id.
	*
	* @param integer $movieId                   Sf movie ID
	* @param string $cityId                Sf city ID (BS = Borås)
	*
	* @return mixed
	*/
	public function getMovie($movieId, $cityId = 'BS') {
		return $this->makeCall('movies/moviedetail/' . $movieId . '?cityid=' . $cityId);
	}

	/**
	* Get available movies by city
	*
	* @param string $cityId                   Sf city ID (BS = Borås)
	*
	* @return mixed
	*/
	public function getAvailableMovies($cityId) {
		return $this->makeCall('tickets/currentmovies/' . $cityId, 'GET');
	}

	/**
	* Get upcoming movies
	*
	*
	*
	* @return mixed
	*/
	public function getUpcomingMovies() {
		return $this->makeCall('movies/upcomingmovies', 'GET');
	}

	/**
	* Get available tickets for movies
	*
	* @param integer $movieId                   Sf movie ID
	* @param string $cityId                   Sf city ID (BS = Borås)
	* @param string $date                   Date you wish to book tickets on
	*
	* @return mixed
	*/
	public function getTicketsOfMovie($movieId, $date, $cityId) {
		return $this->makeCall('tickets/shows?cityid=' . $cityId . '&movieid=' . $movieId . '&date=' . $date . '&includeAllVersions=true', 'GET');
	}

	/**
	* Get available seats in auditorium (Not working, not finished yet)
	*
	*
	* @return mixed
	*/
	public function getAvailableSeats() {
		//$theatreName, $sys99Code, $movieId, $date, $cityId
		//return $this->makeCall('auditorium/layout/2001/5/60/50');
		//return $this->makeCall('auditorium/layout/2001/4/60/50');
		return $this->makeCall('auditorium/layout/2001/1/60/50');
	}


	/**
	* Book tickets for a movie
	*
	* @param integer $sys99Code               Auditorium id
	* @param string $cityId                   Sf city ID (BS = Borås)
	* @param string $datetime                  Unixtime you wish to book tickets on
	* @param array $seats
	* @return mixed
	*/
	public function bookTickets($cityId = 'BS', $sys99Code, $datetime, $seats = null) {

		$postData = [
			'cityid' => $cityId,
			'auditoriumsys99code' => $sys99Code,
			'datetime' => $datetime,
			'seatkey' => ':36'
		];

		return $this->makeCall(
			'tickets/lockseats', 
			'POST',
			$postData
		);
	}

	/**
	* Release tickets for a movie (Stop booking process)
	*
	* @param integer $bookingId               Booking id from bookTickets() call.
	* @return mixed
	*/
	public function releaseTickets($bookingId) {
		return $this->makeCall(
			'tickets/unlockseats', 
			'POST',
			[
				'bookingid' => $bookingId
			]
		);
	}

	/**
	* Get available offers by SF by city
	*
	* @param string $cityId                   Sf city ID (BS = Borås)
	* @return mixed
	*/
	public function getOffers($cityId = 'BS') {
		return $this->makeCall('offers/list?uuid=7954B0AE-AC97-435D-A0EB-350932BAF933&ostype=Iphone&cityid=' . $cityId, 'GET');
	}

	/**
	* Parse Digest WWW-authentication header
	*
	* @return array
	*/
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

	/**
	* Parse array and return Digest auth header
	*
	* @return string Digest header
	*/
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

	protected function makeCall($function, $method = 'GET', $postData = null, $digestData = null) {

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
	    curl_setopt($ch, CURLOPT_VERBOSE, false);
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
		    return $this->makeCall($function, $method, $postData, $this->parseHttpDigest($headers['WWW-Authenticate']));
		} else {
			return json_decode($body);
		}
	}
}