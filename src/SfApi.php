<?php

class Sf {


	const API_URL = 'https://mobilebackend.sfbio.se/services/5/';
	
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
		return $this->makeCall('movies/ ' . $cityId . '/movieid/' . $movieId);
	}

	/**
	* Get available movies by city
	*
	* @param string $cityId                   Sf city ID (BS = Borås)
	*
	* @return mixed
	*/
	public function getAvailableMovies($cityId) {
		return $this->makeCall('movies/' . $cityId . '/extended', 'GET');
	}

	/**
	* Get upcoming movies
	*
	*
	*
	* @return mixed
	*/
	public function getUpcomingMovies($cityId = 'BS') {
		return $this->makeCall('movies/' . $cityId . '/upcoming', 'GET');
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
	public function getTicketsOfMovie($movieId, $date, $cityId = 'BS') {
		return $this->makeCall('shows/' . $cityId . '/movieid/' . $movieId . '/day/' . $date, 'GET');
	}

	public function getTicketInformation($movieDate, $movieTime, $sys99Code, $cityId, $cinemaName) {
		return $this->makeCall('shows/showid/' . $movieDate . '_' . $movieTime . '_' . $sys99Code . '_' . $cityId . '/theatremainaccount/' . $cinemaName);
	}

	/**
	* Get Cinema seating layout
	*
	*
	* @return mixed
	*/
	public function getCinemaLayout($cinemaName, $sys99Code) {
		return $this->makeCall('auditoriums/layout/theatremainaccount/' . $cinemaName . '/sys99code/' . $sys99Code . '/seatwidth/60/seatheight/50');
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
			'cityId' => $cityId,
			'auditoriumSys99Code' => $sys99Code,
			'dateTime' => $datetime,
			'seatKeys' => [
				[
					'seatId' => '25',
					'seatSection' => ''
				],
				[
					'seatId' => '26',
					'seatSection' => ''
				]
			]
		];

		return $this->makeCall(
			'reservations/lockseats', 
			'POST',
			$postData
		);
	}

	public function validatePayment($bookingId, $showType, $ticketType) {
		$postData = [
			'bookingId' => $bookingId,
			'loyaltyCards' => [],
			'showKey' => [
				$showType
			],
			'ticketTypes' => [
				$ticketType
			]
		];

		return $this->makeCall(
			'payments/validate', 
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
			'reservations/unlockseats/' . $bookingId
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

	protected function makeCall($function, $method = 'GET', $postData = null) {

		$headerData = [
	    	'User-Agent: SFBio/5.0.0 (iPhone; iOS 8.3; Scale/2.00)',
	    	'X-SF-Iphone-Version: 5.0.0',
	    	'Authorization: Basic U0ZiaW9BUEk6YlNGNVBGSGNSNFoz'
	    ];

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

		return json_decode($body);
	}
}