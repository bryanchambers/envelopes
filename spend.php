<?php 
if(isset($_GET['envelope'])) {
	$envelope = $_GET['envelope'];
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
		.elements {
			width: 94%;
			height: 150px;
			font-size: 5em;
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
		#amount {
			color: #158431;
			border-bottom: 2px solid #158431;
			border-radius: 0;
		}
		#submit {
			background-color: #7ce997;
			border: 2px solid #158431;
		}
		#cancel {
			background-color: #e6e6e6;
			border: 2px solid #4d4d4d;
		}
	</style>
</head>
<body>
	<p id='header' class='elements'><?php echo $envelope; ?></p>
	<form>
		<input id='amount' class='elements' type='number' name='amount' placeholder='enter amount'>
		<input id='submit' class='elements' type='submit' name='submit' value='Spend'>
	</form>
	<a href='/envelopes'><button id='cancel' class='elements'>Cancel</button></a>
</body>
</html>

