<?php
require_once 'inc/resultSet.php';
require_once 'inc/const.php';
if (isset($_POST['submit']))
{
	$result = new resultSet($_POST['origin'],$_POST['destin'],$_POST['service'],$_POST['bracket']);
	
	if ($result->origin === false or $result->destination === false)
	{
		$err = "Location: index.php?";
		if($result->origin === false) $err .= "or=0&";
		if($result->destination === false) $err .= "de=0&";
		rtrim($err, "&");
		header($err);
		exit();
	}

}else {
	header("Location: ./");
	exit();
}

$pageID = 'result';
include 'inc/header.php';

?>
<div class="dynamic_body">
	<div id="result_div">
		<!-- Journey Type Switch -->
		<div class="result_journey_selector_wrapper">
			<div class="journey_selector" id="singleDiv">
				<input type="radio" name="journey_type" id="single" value="single" class="radioGroup" />
				<label for="single" class="journey_selector_label" id="single">Single</label>
			</div>
			<div class="journey_selector" id="returnDiv">
				<input type="radio" name="journey_type" id="return" value="return" class="radioGroup" />
				<label for="return" class="journey_selector_label" id="return">Return</label>
			</div>
		</div>
		<!-- Image, Text and Text colour -->
		<div class="result_top_wrapper">
			<div style="display: inline-block;">
				<div class="service_selected" style="border: 0;">
					<img src="<?php echo $result->displayedServiceImg; ?>" alt="<?php echo $result->displayedServiceName; ?>" />
					<p class="service_label" style="color:<?php echo $result->displayedServiceColour; ?>;"><?php echo $result->displayedServiceName; ?></p>
				</div>
				<div class="bracket_selected" style="border: 0;">
					<img src="<?php echo $result->displayedBracketImg; ?>" alt="<?php echo $result->displayedBracketName; ?>" />
					<p class="service_label" style="color:#000000 ?>"><?php echo $result->displayedBracketName; ?></p>
				</div>
			</div>
		</div>
		<div class="result_points_wrapper">
			<!-- This will contain the origin and destination. -->
			<div class="point_wrapper">
				<div class="point_pre">From: </div><div class="point_post"><?php echo $result->displayedOrigin; ?></div>
			</div>
			<div class="point_wrapper">
				<div class="point_pre">To: </div><div class="point_post"><?php echo $result->displayedDestination; ?></div>
			</div>
		</div>
		<div class="result_fare_val_wrapper">
			<!-- This will contain the prices. -->
			<div class="fare_block"><p class="fareTitle">Cash Fare:</p><p class="fareAmount">&euro;<span id="cash"><?php
				if (!isset($result->cashSingleFare))
				{
					echo '0.00';
				}
				else
				{
					echo number_format($result->cashSingleFare, 2);
				}
			?></span></p></div>
			<div class="fare_block"><p class="fareTitle">Leap Card Fare:</p><p class="fareAmount">&euro;<span id="leap"><?php
				if (!isset($result->leapSingleFare)) echo '0.00';
				else echo number_format($result->leapSingleFare, 2);
			?></span></p></div>
		</div>
	</div>
</div>
<?php include 'inc/footer.php'; ?>