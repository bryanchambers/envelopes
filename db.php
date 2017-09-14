<?php

function dbConnect() {
	$file = file_get_contents('db.json', FILE_USE_INCLUDE_PATH);
	$info = json_decode($file, true);

	$host     = $info['host'];
	$db       = $info['db'];
	$username = $info['username'];
	$password = $info['password'];
	
	$dbc = new PDO("mysql:host=$host;dbname=$db", $username, $password);
	$dbc->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbc;
}





function dbGetWarnings($dbc) {
	$warnings = $dbc->query("SHOW WARNINGS")->fetch();
	if(isset($warnings['Message'])) {
		return $warnings['Message'];
	} else {
		return false;
	}
}





function tableDefs($table) {
	$defs = [];

	$defs['envelopes'] = "
		CREATE TABLE IF NOT EXISTS envelopes(
			name    VARCHAR(30) PRIMARY KEY NOT NULL,
			refill  INT,
			goal    INT,
			balance INT)";

	return $defs[$table];
}




function dbCreateTable($dbc, $table, $query) {
	$dbc->query($query);
	
	$warnings = dbGetWarnings($dbc);
	if($warnings) {
		return "Database warning *<span class='error'>$warnings</span>*";
	} else {
		return "Created table $table.";
	}
}





function dbDropTable($dbc, $table) {
	$dbc->query("DROP TABLE IF EXISTS $table");
	
	$warnings = dbGetWarnings($dbc);
	if($warnings) {
		return "Database warning *<span class='error'>$warnings</span>*";
	} else {
		return "Dropped table $table.";
	}
}




function getData($query_string, $dbc) {
	$output = ['success' => true, 'data' => [], 'errors' => []];

	try {
		$query = $dbc->query($query_string);
		
		while($row = $query->fetchObject()) {
			$output['data'][] = $row;
		}
	} catch(PDOException $err) {
		$output['success']  = false;
		$output['errors'][] = $err->getMessage();
	}

	return $output;
}



function getEnvelopes($dbc) {
	$query_string = "SELECT * FROM envelopes LIMIT 100";
	return getData($query_string, $dbc);
}











function createEnvelope($dbc, $name, $refill, $goal) {
	try {
		$query = $dbc->prepare("INSERT INTO envelopes(name, refill, goal) VALUES(:name, :refill, :goal)");
		$query->bindParam(':name',   $name);
		$query->bindParam(':refill', $refill);
		$query->bindParam(':goal',  $goal);
		
		$query->execute();
		return "Created new envelope $name with a refill of $refill and a goal of $goal.";
	} catch(PDOException $err) {
		return "Database error *<span class='error'" . $err->getMessage() . "</span>*";
	}
}


function changeBalance($dbc, $envelope, $change) {
	try {
		if($change >= 0) {
			$sign     = '+';
			$signtxt  = 'Increased';
		} else {
			$change  = abs($change);
			$sign    = '-';
			$signtxt = 'Decreased';
		}
		
		$query  = $dbc->prepare("UPDATE envelopes SET balance=balance" . $sign . ":change WHERE name=:envelope LIMIT 1");
		$query->bindParam(':change',   $change);
		$query->bindParam(':envelope', $envelope);
		
		$query->execute();
		return "$signtxt $envelope balance by $change.";
	} catch(PDOException $err) {
		return "Database error *<span class='error'" . $err->getMessage() . "</span>*";
	}
}






function setBalance($dbc, $envelope, $balance) {
	try {
		$query = $dbc->prepare("UPDATE envelopes SET balance=:balance WHERE name=:envelope LIMIT 1");
		$query->bindParam(':balance',  $balance);
		$query->bindParam(':envelope', $envelope);
		
		$query->execute();
		return "Set $envelope balance to $balance.";
	} catch(PDOException $err) {
		return "Database error *<span class='error'" . $err->getMessage() . "</span>*";
	}
}






function setRefill($dbc, $envelope, $refill) {
	try {
		$query = $dbc->prepare("UPDATE envelopes SET refill=:refill WHERE name=:envelope LIMIT 1");
		$query->bindParam(':refill',   $refill);
		$query->bindParam(':envelope', $envelope);
		
		$query->execute();
		return "Set $envelope refill to $refill";
	} catch(PDOException $err) {
		return "Database error *<span class='error'" . $err->getMessage() . "</span>*";
	}
}



function setGoal($dbc, $envelope, $goal) {
	try {
		$query = $dbc->prepare("UPDATE envelopes SET goal=:goal WHERE name=:envelope LIMIT 1");
		$query->bindParam(':goal',     $goal);
		$query->bindParam(':envelope', $envelope);
		
		$query->execute();
		return "Set $envelope goal to $goal.";
	} catch(PDOException $err) {
		return "Database error *<span class='error'" . $err->getMessage() . "</span>*";
	}
}



function renameEnvelope($dbc, $old, $new) {
	try {
		$query = $dbc->prepare("UPDATE envelopes SET name=:new WHERE name=:old LIMIT 1");
		$query->bindParam(':old', $old);
		$query->bindParam(':new', $new);
		
		$query->execute();
		return "Renamed $old to $new.";
	} catch(PDOException $err) {
		return "Database error *<span class='error'" . $err->getMessage() . "</span>*";
	}
}



function emptyEnvelope($dbc, $envelope) {
	try {
		$query = $dbc->prepare("UPDATE envelopes SET balance=0 WHERE name=:envelope LIMIT 1");
		$query->bindParam(':envelope', $envelope);
		
		$query->execute();
		return "Set $envelope balance to 0.";
	} catch(PDOException $err) {
		return "Database error *<span class='error'" . $err->getMessage() . "</span>*";
	}
}
