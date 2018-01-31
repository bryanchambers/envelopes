<?php 

/*






COMMANDS
----------
db create [table]
db drop   [table]

create [envelope] [refill] [goal]
delete [envelope]

transfer [value] from [envelope] to [envelope]

set [envelope] refill  to [value]
set [envelope] goal    to [value]
set [envelope] balance to [value]

empty [envelope]

move [envelope] [value]
move [envelope] top
move [envelope] bottom

reorder [envelope1,envelope2,envelope3]
reorder reset

swap [envelope] and [envelope]

rename [old name] to [new name]








*/

require 'db.php';

//mail('bchambers@athenium.com', 'Envelopes', 'Test');

function getCommand() {
	if(isset($_POST['cmd']) && $_POST['cmd'] != '') {
		return $_POST['cmd'];
	} else {
		return false;
	}
}


function displayCommand() {
	$cmd = getCommand();
	if($cmd) { 
		echo $cmd;
	}
}





function parseCommand() {
	$cmd = getCommand();
	
	if($cmd) { echo cmdHandler($cmd); } 
	else     { echo defaultMessage(); }
}



function defaultMessage() {
	$messages = [
		"Reporting for duty",
		"All systems operational",
		"Online and standing by",
		"Adulting at maximum"
	];
	return $messages[intval(round(rand(0, count($messages) - 1), 0))];
}





function cmdHandler($cmd) {
	$words = explode(' ', trim($cmd));
	$type  = strtolower($words[0]);

	switch($type) {
		case 'db':       if(count($words) == 3) { return cmdDB(       $words[1], $words[2]); }            break;
		case 'create':   if(count($words) >= 3) { return cmdCreate(   $words[1], $words[2], $words[3]); } break;
		case 'delete':   if(count($words) == 2) { return cmdDelete(   $words[1]); }                       break;
		case 'transfer': if(count($words) == 6) { return cmdTransfer( $words[1], $words[3], $words[5]); } break;
		case 'set':      if(count($words) == 5) { return cmdSet(      $words[1], $words[2], $words[4]); } break;
		case 'empty':    if(count($words) == 2) { return cmdEmpty(    $words[1]); }                       break;
		case 'move':     if(count($words) == 3) { return cmdMoveToTop($words[1]); }                       break;
		case 'reorder':  if(count($words) == 2) { return cmdMoveToTop($words[1]); }                       break;
		case 'swap':     if(count($words) == 4) { return cmdMoveToTop($words[1]); }                       break;
		case 'rename':   if(count($words) == 4) { return cmdRename(   $words[1], $words[3]); }            break;
		case 'refill':   if(count($words) == 2) { return cmdRefill(   $words[1]); }                       break;
	}
	return false;
}












function cmdDB($subtype, $table) {
	if($subtype == 'create') { 
		return dbCreateTable(dbConnect(), $table, tableDefs($table)); 
	} else if($subtype == 'drop') { 
		return dbDropTable(dbConnect(), $table); 
	} else {
		return false;
	}
}


function cmdCreate($name, $refill, $goal) {
	if(ctype_alpha($name)) {
		$refill = intval($refill);
		$goal   = intval($goal);

		if($refill !== false && $goal !== false) {
			return createEnvelope(dbConnect(), $name, $refill, $goal);
		} else {
			return 'Refill and goal must be integers';
		}
	} else {
		return 'Invalid envelope name';
	}
}




function cmdDelete($envelope) {
	if(ctype_alpha($envelope)) {
		return deleteEnvelope(dbConnect(), $envelope);
	} else {
		return 'Invalid envelope name';
	}
}




function cmdMoveToTop($envelope) {
	if(ctype_alpha($envelope)) {
		$position = getSortPosition(dbConnect(), $envelope);
		if(intval($position)) {
			$shift = shiftEnvelopesDown(dbConnect(), $position);
			$move  = moveEnvelopeToTop(dbConnect(), $envelope);
			return $shift . ' ' . $move;
		}
	} else {
		return 'Invalid envelope name';
	}
}




function cmdRename($old, $new) {
	if(ctype_alpha($old) && ctype_alpha($new)) {
		if($new != 'to' && $old != 'to') {
			return renameEnvelope(dbConnect(), $old, $new);
		} else {
			return 'Invalid syntax';
		}
	} else {
		return 'Invalid envelope name';
	}
}



function cmdSet($envelope, $attribute, $value) {
	if(ctype_alpha($envelope)) {
		$value = intval($value);
		if($value !== false) {
			switch($attribute) {
				case 'refill':
					return setRefill(dbConnect(), $envelope, $value);
				break;

				case 'goal':
					return setGoal(dbConnect(), $envelope, $value);
				break;

				case 'balance':
					return setBalance(dbConnect(), $envelope, $value);
				break;

				default:
					return false;
			}
		} else {
			return 'Value must be an integer';
		}
	} else {
		return 'Invalid envelope name';
	}
}


function cmdTransfer($amount, $from, $to) {
	if(ctype_alpha($from) && ctype_alpha($to)) {
		$amount = intval($amount);
		if($amount !== false && $amount >= 0) {
			$dbc = dbConnect();
			
			$resFrom = changeBalance($dbc, $from, $amount * -1);
			$resTo   = changeBalance($dbc, $to, $amount);

			return $resFrom . ' ' . $resTo;
		} else {
			return 'Amount must be a positive integer';
		}
	} else {
		return 'Invalid envelope name';
	}
}


function cmdRefill($envelope) {
	if(ctype_alpha($envelope)) {
		return refill(dbConnect(), $envelope);
	} else {
		return 'Invalid envelope name';
	}
}



function cmdEmpty($envelope) {
	if(ctype_alpha($envelope)) {
		return emptyEnvelope(dbConnect(), $envelope);
	} else {
		return 'Invalid envelope name';
	}
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
			font-size: 70px;
			padding: 0;
			margin: 3%;
			border-radius: 5px;
			font-family: Helvetica;
			color: #666666;
			-webkit-appearance: none;
		}
		#header, #cmd {
			outline: none;
			background-color: #f2f2f2;
			width: 94%;
		}
		#header {
			border: none;
			text-align: center;
			font-weight: bold;
			font-size: 100px;
		}
		#cmd {
			border: none;
			font-family: monospace;
			font-size: 50px;
			height: 100px;
			color: #158431;
		}
		#submit-btn {
			background-color: #7ce997;
			border: 2px solid #158431;
		}
		#home {
			background-color: #e6e6e6;
			border: 2px solid #4d4d4d;
		}
		#response {
			font-family: monospace;
			font-size: 50px;
		}
	</style>

	<script>
		function main() {
			document.getElementById("cmd").focus();

			document.getElementById('cmd').addEventListener('keypress', function(event) {
				if(event.keyCode == 13) {
					document.forms['form'].submit();
				}
			});
		}
	</script>
</head>
<body onload='main()'>
	<p id='header' class='elements'>Admin</p>
	<form id='form' method='post' action=''>
		<textarea id='cmd' class='elements' name='cmd' placeholder='Enter command'><?php displayCommand(); ?></textarea>
		<p id='response' class='elements'> <?php parseCommand(); ?></p>
		<input id='submit-btn' class='elements' type='submit' name='submit-btn' value='Run'>
	</form>
	<a href='/envelopes'><button id='home' class='elements'>Home</button></a>
</body>
</html>

