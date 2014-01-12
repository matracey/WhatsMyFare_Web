<!DOCTYPE html>
<html>
<head>
	<title>What's My Fare</title>
	<link rel="stylesheet" type="text/css" href="style/style.css" />
	<link rel="icon" type="image/png" href="./images/icon.png">
	<!-- <link rel="icon" type="image/x-icon" href="./images/icon.ico"> -->
	<?php if(isset($pageID) and $pageID === 'result'){ ?>
	<script type="text/javascript">
	var cashSingle = <?php echo $result->cashSingleFare; ?>;
	var leapSingle = <?php echo $result->leapSingleFare; ?>;
	var cashReturn = <?php echo $result->cashReturnFare; ?>;
	var leapReturn = <?php echo $result->leapReturnFare; ?>;
	</script>
	<?php } ?>
	<script type="text/javascript" src="js/jquery-1.10.2.min.js"></script>
	<script type="text/javascript" src="js/jquery.activity-indicator-1.0.0.js"></script>
	<script type="text/javascript" src="js/scriptv2.js"></script>

</head>
<body>
<div class="header_wrapper">
	<a href="./" class="homeLink">
	<div class="header">
		<h1>What's My Fare</h1>
		<p class="subtitle">A public transport fare calculator for Dublin.</p>
	</div>
	</a>
</div>