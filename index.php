<?php 




function displayEnvelope($name, $goal, $balance) {
	$width = round(($balance / $goal) * 100) . '%';

	echo "<a href='spend.php?envelope=$name'>";
		echo "<div class='basics envelope'>";
			echo "<div class='spent-bar' style='width: $width'>";
				echo "<span class='spent-val'>$balance</span>";
				echo "<span class='env-name'>$name</span>";
	echo "</div></div></a>";
}

?>

<!DOCTYPE html>
<html>
<head>
	<title></title>
	<style>
		body {
			background-color: #f2f2f2;
		}
		.basics {
			width: 94%;
			height: 150px;
			font-size: 4em;
			padding: 0;
			margin: 3%;
			border-radius: 5px;
			font-family: Helvetica;
			color: #666666;
			-webkit-appearance: none;
		}
		#header, #amount {
			text-align: center;
			border: none;
			outline: none;
			background-color: #f2f2f2;
			width: 94%;
		}
		#header {
			font-weight: bold;
			font-size: 7em;
		}
		.envelope {
			background-color: #f2f2f2;
			border: 2px solid #158431;
		}
		.spent-bar {
			background-color: #7ce997;
			border-radius: 5px 0 0 5px;
			padding: 0;
			margin: 0;
			height: 100%;
			width: 10%;
			white-space: nowrap;
		}
		a {
			text-decoration: none;
		}
		.spent-val, .env-name {
			line-height: 150px;
			margin-left: 20px;
		}
	</style>
</head>
<body>
	<p id='header' class='basics'>Envelopes</p>
	
	<?php 
		displayEnvelope('Groceries', 60, 20);
		displayEnvelope('Fun', 200, 100);
		displayEnvelope('Random', 100, 35);
	?>

</body>
</html>