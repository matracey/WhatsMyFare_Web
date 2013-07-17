<?php

class resultSet
{
	// Temporary variables
	private $services = array(
		'red' => array('name'=>'Luas Red Line', 'img'=>'images/red.png', 'colour'=>'#aa3535'),
		'green' => array('name'=>'Luas Green Line', 'img'=>'images/green.png', 'colour'=>'#5b922f'),
		'both' => array('name'=>'Luas', 'img'=>'images/both.png', 'colour'=>'#61438e'),
		'dart' => array('name'=>'DART', 'img'=>'images/dart.png', 'colour'=>'#78c13c'),
		'rail' => array('name'=>'Commuter Rail', 'img'=>'images/rail.png', 'colour'=>'#33a9c6')
		);
	private $brackets = array(
		'adult' => array('name'=>'Adult', 'img'=>'images/adult.png'),
		'student' => array('name'=>'Student', 'img'=>'images/student.png'),
		'child' => array('name'=>'Child', 'img'=>'images/child.png')
		);

	// User specified values
	private $stopFrom;
	private $stopTo;
	private $service;
	private $bracket;

	private $selectedServicesEnum = array('luas' => 2, 'rail' => 1, 'dart' => 1 , 'bus' => 3);
	private $stopServicesEnum = array(
		1 => 'red',
		2 => 'green',
		3 => 'rail',
		4 => 'dart'
		);

	// Computed values
	public $displayedServiceName;
	public $displayedServiceImg;
	public $displayedServiceColour;

	public $displayedBracketName;
	public $displayedBracketImg;

	public $displayedOrigin;
	public $displayedDestination;

	public $origin;
	public $destination;
	
	// Calculated Fare data
	private $priceCode;
	public $cashSingleFare;
	public $leapSingleFare;
	public $cashReturnFare;
	public $leapReturnFare;
	
	private $urlBody = "http://whats-my-fare-ie.elasticbeanstalk.com/private/api/MzM5ODM2MzI=/";

	// Initialiser
	public function __construct($stopFrom, $stopTo, $service, $bracket)
	{
		// Set up the inital properties of the object.
		$this->stopFrom = $stopFrom;
		$this->stopTo = $stopTo;
		$this->service = $service;
		$this->bracket = $bracket;

		$this->getDisplayedBracket();
		$this->getDisplayedPoints();

		$this->priceCode = $this->getPriceCodes();
		$this->priceCode = $this->priceCode['fare_price_code'];
		$this->setFares();
		$this->getDisplayedService();		
	}

	// Get Computed Values
	// These methods will get the correct data that should be displayed to the user.
	private function getDisplayedService()
	{
		$service = $this->service;
		$originServiceID = (int)$this->origin['service_id'];
		$destinationServiceID = (int)$this->destination['service_id'];
		if ($service == 'luas')
		{
			if ($originServiceID !== $destinationServiceID) //($originServiceID === 1 and $destinationServiceID === 2) or ($originServiceID === 2 and $destinationServiceID === 1)
			{
				$service = 'both';
			}else $service = $this->stopServicesEnum[$originServiceID];
		}

		$this->displayedServiceName = $this->services[$service]['name'];
		$this->displayedServiceImg = $this->services[$service]['img'];
		$this->displayedServiceColour = $this->services[$service]['colour'];
		return false;
	}

	private function getValuesForQuery($query)
	{
		$url = $this->urlBody . "getStopForService/name/". urlencode($query). "/type_id/". $this->selectedServicesEnum[$this->service];
		$handle = curl_init();
		curl_setopt_array($handle, 
			array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true
			)
		);

		$response = curl_exec($handle);
		curl_close($handle);

		$array = json_decode($response, true);
		if(isset($array[0]) and !empty($array[0]))return $array[0];
		else return false;
	}

	private function getDisplayedBracket()
	{
		// This method should obtain the correct BRACKET to display on results screen.
		$bracket = $this->bracket;

		$this->displayedBracketName = $this->brackets[$bracket]['name'];
		$this->displayedBracketImg = $this->brackets[$bracket]['img'];
		return false;
	}

	private function getDisplayedPoints()
	{
		// STEP 1: Get the Stops via API.
		// This method should obtain the correctly formatted ORIGIN and DESTINATION that the user has entered.
		$this->origin = $this->getValuesForQuery($this->stopFrom);
		$this->destination = $this->getValuesForQuery($this->stopTo);
		if ($this->origin === null or $this->destination === null) return false;

		$this->displayedOrigin = $this->origin['name'];
		$this->displayedDestination = $this->destination['name'];
		return true;
	}

	private function getPriceCodes()
	{
		// STEP 2: Get the Fare Price Code for the displayed Stops
		// This method should obtain the FARE VALUES ( in cash and leap card ) for both single and return journies.
		$originID = urlencode($this->origin['id']);
		$destinID = urlencode($this->destination['id']);

		$url = $this->urlBody . "getFareForStops/from/" . $originID . "/to/" . $destinID;
		$handle = curl_init();
		curl_setopt_array($handle, 
			array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true
			)
		);

		$response = curl_exec($handle);
		curl_close($handle);

		$array = json_decode($response, true);
		if(isset($array) and !empty($array) and count($array) === 1)return $array[0];

		return false;
	}

	// private function init

	private function setFares()
	{
		// STEP 3: Get the Prices for the set Price Code.
		if (!isset($this->priceCode) or empty($this->priceCode))
		{
			return false;
		}
		$url = $this->urlBody . "getActivePriceForCode/code/". urlencode($this->priceCode);
		$handle = curl_init();
		curl_setopt_array($handle, 
			array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true
			)
		);

		$response = curl_exec($handle);
		curl_close($handle);

		$array = json_decode($response, true);
		if( isset($array) and !empty($array) and (count($array) === 1) ) $array = $array[0];
		else return false;

		// echo 'Code: '.$this->priceCode;
		// echo "<pre>";
		// var_dump($array);
		// echo "</pre>";
		// exit();

		$this->cashSingleFare = $array[$this->bracket . '_single_cash'];
		$this->leapSingleFare = $array[$this->bracket . '_single_leap'];
		$this->cashReturnFare = $array[$this->bracket . '_return_cash'];
		$this->leapReturnFare = $array[$this->bracket . '_return_leap'];
	}
}
?>
