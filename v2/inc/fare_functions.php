<?php
function curl_get($url)
{
	$ch = curl_init();

	curl_setopt_array($ch, 
		array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true
		)
	);

	$response = curl_exec($ch);
	curl_close($ch);

	$array = json_decode($response, true);
	if(isset($array)) return $array;
	else return false;
}

function curl_post($url, $fields)
{
	// Generate Fields string.
	$fields_string = "";
	foreach($fields as $key=>$value) {
		$fields_string .= $key.'='.$value.'&';
	}
	rtrim($fields_string, '&');

	// Initialise cURL.
	$ch = curl_init();

	// Set cURL options.
	curl_setopt_array($ch, array(
		CURLOPT_URL => $url,
		CURLOPT_POST => count($fields),
		CURLOPT_POSTFIELDS => $fields_string,
		CURLOPT_RETURNTRANSFER => true
		));

	// Record connection result.
	$connectionlog_result = curl_exec($ch);

	// Close cURL connection
	curl_close($ch);

	// return result.
	return $connectionlog_result;
}

function isLiveSite()
{
	$params = array();
	$parts = explode('/', $_SERVER['REQUEST_URI']);

	if($_SERVER['SERVER_NAME'] == "whatsmyfare.ie" or $_SERVER['SERVER_NAME'] == "www.whatsmyfare.ie")
	// if($_SERVER['SERVER_NAME'] == "whatsmyfare.ie" or $_SERVER['SERVER_NAME'] == "www.whatsmyfare.ie" or $_SERVER['SERVER_NAME'] == "whats-my-fare-ie.elasticbeanstalk.com")
	{
		return true;
	}else return false;
}

?>