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
create [name]
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




function isSuperAdmin() {
	if(isset($_GET['super']) && $_GET['super'] == 'true') {
		return true;
	} else {
		return false;
	}
}








function cmdHandler($cmd) {
	$words = explode(' ', trim($cmd));
	$type  = $words[0];

	switch($type) {
		case 'db':
			if(count($words) == 3) { return cmdDB($words[1], $words[2]); }             // Subtype, Table Name
			else { return false; }
		break;

		case 'create':
			if(count($words) == 2) { return cmdCreate($words[1]); }                    // New Envelope Name
			else { return false; }
		break;

		case 'set':
			if(count($words) == 5) { return cmdSet($words[1], $words[2], $words[4]); } // Envelope Name, Attribute, Value
			else { return false; }
		break;

		case 'rename':
			if(count($words) == 4) { return cmdRename($words[1], $words[2]); }         // Old Envelope Name, New Envelope Name
			else { return false; }
		break;

		case 'empty':
			if(count($words) == 2) { return cmdEmpty($words[1]); }                     // Envelope Name
			else { return false; }
		break;

		default:
			return false;
	}
}












function cmdDB($subtype, $table) {
	if($subtype == 'create') { 
		return dbCreateTable($dbc, $table, tableDefs($table)); 
	} else if($subtype == 'drop') { 
		return dbDropTable($dbc, $table); 
	} else {
		return false;
	}
}


function cmdCreate($envelope) {

}



function cmdSet($envelope, $attribute, $value) {

}


function cmdRename($old, $new) {

}



function cmdEmpty($envelope) {

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

