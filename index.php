<?php
require_once 'inc/fare_functions.php';

// API URL - used for all API communication.
// $urlBody = "http://whats-my-fare-ie.elasticbeanstalk.com/private/api/MzM5ODM2MzI=/";
$urlBody = "http://whatsmyfare.ie/private/api/private/api/MzM5ODM2MzI=/";


/*
 * LOG IP OF CONNECTING USER
 *
 * 1. Generate API access url for adding connection log.
 * 2. Add all required fields to the array.
 * 3. POST the fields to the API URL.
 */

$url = $urlBody . "addConnectionLog";
$fields = array();
$fields['ip'] = $_SERVER['REMOTE_ADDR'];
if(!isset($fields['ip'])) $fields['ip'] = "0.0.0.0";

curl_post($url, $fields);

/*
 * CHECK IS SITE ACTIVE
 * If site isn't active, we'll load the contents of the site_inactive.html file.
 * Once that's loaded, we'll exit.
 * 
 * Otherwise, we'll continue as normal.
 */

$url = $urlBody . "siteStatus";
$result = curl_get($url);
$siteStatus = $result['value'];
if($siteStatus == 0 and isLiveSite())
{
	include 'inc/closed.php';
	exit();
}

include 'inc/header.php';
$errorMessages = array(
	'There was a problem with the origin that you entered. Please try again.',
	'There was a problem with the destination that you entered. Please try again.',
	'There was a problem with the origin and destination that you entered. Please try again.'
	);

?>
	<div class="dynamic_body">
	<?php if( isset($_GET['or']) or isset($_GET['de']) ) {?><div class="error_message"><p class="error"><?php
	if (isset($_GET['or']) and isset($_GET['de']))
	{
		echo $errorMessages[2];
	}else if (isset($_GET['or']))
	{
		echo $errorMessages[0];
	}else if (isset($_GET['de']))
	{
		echo $errorMessages[1];
	}
	?></p></div><?php }?>
		<form action="get_result.php" method="post">
		<div class="form_wrapper">
			<div class="selection_wrapper">
				<div class="service_selector" id="luasDiv">
					<input type="radio" name="service" id="luas" value="luas" checked="checked" />
					<label for="luas" class="selector_label" id="luas"><img src="images/both.png" alt="luas" /><div id="luas_label">Luas</div></label>
				</div>
				<div class="service_selector" id="dartDiv">
					<input type="radio" name="service" id="dart" value="dart" />
					<label for="dart" class="selector_label" id="dart"><img src="images/dart.png" alt="dart" /><div id="dart_label">DART</div></label>
				</div>
				<div class="service_selector" id="railDiv">
					<input type="radio" name="service" id="rail" value="rail" />
					<label for="rail" class="selector_label" id="rail"><img src="images/rail.png" alt="rail" /><div id="rail_label">Commuter Rail</div></label>
				</div>
			</div>
			<div class="capture_wrapper">
				<input type="text" class="userInput" name="origin" id="origin" value="Start typing an origin..." autocomplete="off" />
				<ul id="originResults" class="resultsList">
				<!-- Search Results go here... -->
				</ul>
			</div>
			<div class="capture_wrapper">
				<input type="text" class="userInput" name="destin" id="destin" value="Start typing a destination..." autocomplete="off" />
				<ul id="destinResults" class="resultsList">
				<!-- Search Results go here... -->
				</ul>
			</div>
			<div class="selection_wrapper">
				<div class="bracket_selector" id="adultDiv">
					<input type="radio" name="bracket" id="adult" value="adult" checked="checked" />
					<label for="adult" class="selector_label" id="adult"><img src="images/adult.png" alt="adult" /><div>Adult</div></label>
				</div>
				<div class="bracket_selector" id="studentDiv">
					<input type="radio" name="bracket" id="student" value="student" />
					<label for="student" class="selector_label" id="student"><img src="images/student.png" alt="student" /><div>Student</div></label>
				</div>
				<div class="bracket_selector" id="childDiv">
					<input type="radio" name="bracket" id="child" value="child" />
					<label for="child" class="selector_label" id="child"><img src="images/child.png" alt="child" /><div>Child</div></label>
				</div>
			</div>
			<div class="capture_wrapper" style="display: block;">
				<input type="submit" class="submit" value="Tell Me My Fare!" name="submit" />
			</div>
		</div>
		</form>
	</div>
<?php include 'inc/footer.php'; ?>
