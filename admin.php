<?php 

/*

SUPER ADMIN
------------
db create [name]
db drop [name]
empty [name]
set [name] balance to [value]


ADMIN
--------
create [name] [refill] [goal]
rename [old name] to [new name]
set [name] goal to [value]
set [name] refill to [value]




COMMANDS
----------
db
	create*
	drop*
create
set
	goal
	refill
	balance*
rename
empty*



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
	
	if($cmd) {
		$response = cmdHandler($cmd);
		if($response) {
			echo $response; 
		} else { 
			echo 'Invalid command';
		}
	}
}









function cmdHandler($cmd) {
	$words = explode(' ', trim($cmd));
	$type  = $words[0];

	switch($type) {
		case 'db':
			if(count($words) == 3) { return cmdDB($words[1], $words[2]); }                 // Subtype, Table Name
			else { return false; }
		break;

		case 'create':
			if(count($words) == 4) { return cmdCreate($words[1], $words[2], $words[3]); } // Name, Refill, Goal
			else { return false; }
		break;

		case 'rename':
			if(count($words) == 4) { return cmdRename($words[1], $words[2]); }             // Old Name, New Name
			else { return false; }
		break;

		case 'set':
			if(count($words) == 5) { return cmdSet($words[1], $words[2], $words[4]); }     // Envelope, Attribute, Value
			else { return false; }
		break;

		case 'refill':
			if(count($words) == 2) { return cmdRefill($words[1]); }                        // Envelope
			else { return false; }
		break;

		case 'transfer':
			if(count($words) == 6) { return cmdTransfer($words[1], $words[3], $words[5]); }  // Amount, From, To
			else { return false; }
		break;

		case 'empty':
			if(count($words) == 2) { return cmdEmpty($words[1]); }                         // Envelope
			else { return false; }
		break;

		default:
			return false;
	}
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
		if($refill[0] == 'r' && $goal[0] == 'g') {
			$refill = intval(substr($refill, 1));
			$goal   = intval(substr($goal, 1));

			if($refill && $goal && $refill > 0 && $goal > 0) {
				return createEnvelope(dbConnect(), $name, $refill, $goal);
			} else {
				return 'Refill and goal must be positive integers';
			}
		} else {
			return 'Invalid refill or goal syntax';
		}
	} else {
		return 'Invalid envelope name';
	}
}

function cmdRename($old, $new) {
	if(ctype_alpha($old) && ctype_alpha($new)) {
		return renameEnvelope(dbConnect(), $old, $new);
	} else {
		return 'Invalid envelope name';
	}
}



function cmdSet($envelope, $attribute, $value) {
	if(ctype_alpha($envelope)) {
		$value = intval($value);
		if($value && $value > 0) {
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
			return 'Value must be a positive integer';
		}
	} else {
		return 'Invalid envelope name';
	}
}


function cmdTransfer($amount, $from, $to) {
	if(ctype_alpha($from) && ctype_alpha($to)) {
		$amount = intval($amount);
		if($amount && $amount > 0) {
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
			font-size: 5em;
			padding: 0;
			margin: 3%;
			border-radius: 5px;
			font-family: Helvetica;
			color: #666666;
			-webkit-appearance: none;
		}
		#header, #cmd {
			border: none;
			outline: none;
			background-color: #f2f2f2;
			width: 94%;
		}
		#header {
			text-align: center;
			font-weight: bold;
			font-size: 7em;
		}
		#cmd {
			font-size: 3em;
			height: 300px;
			color: #158431;
			border: none;
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
		#response {
			font-size: 3em;
		}
	</style>
</head>
<body>
	<p id='header' class='elements'>Admin</p>
	<form method='post' action=''>
		<textarea id='cmd' class='elements' name='cmd' placeholder='>'><?php displayCommand(); ?></textarea>
		<p id='response' class='elements'><?php parseCommand(); ?></p>
		<input id='submit' class='elements' type='submit' name='submit' value='Run'>
	</form>
	<a href='/envelopes'><button id='cancel' class='elements'>Cancel</button></a>
</body>
</html>

