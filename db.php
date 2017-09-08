<?php

function dbConnect() {
	$file = file_get_contents('db_connect.json', FILE_USE_INCLUDE_PATH);
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
			id      INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
			name    VARCHAR(50) NOT NULL,
			refill  INT,
			goal    INT,
			balance INT)";

	return $defs[$table];
}




function dbCreateTable($dbc, $table, $query) {
	echo $query;
	$dbc->query($query);
	
	$warnings = dbGetWarnings($dbc);
	if($warnings) {
		return $warnings;
	} else {
		return "Created table $table";
	}
}





function dbDropTable($dbc, $table, $confirm) {
	if($confirm) {
		$dbc->query("DROP TABLE IF EXISTS $table");
		
		$warnings = dbGetWarnings($dbc);
		if($warnings) {
			return $warnings;
		} else {
			return "Dropped table $table";
		}
	} else {
		return "Confirmation flag is false";
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



function getEnvelopeID($dbc, $name) {
	$query_string = "SELECT id, name FROM envelopes WHERE LIMIT 1";
	return getData($query_string, $dbc);
}




function addEnvelope($dbc, $name, $refill, $goal) {
	try {
		$query = $dbc->prepare("INSERT INTO envelopes(name, refill, goal) VALUES(:name, :refill, :goal)");
		$query->bindParam(':name',   $name);
		$query->bindParam(':refill', $refill);
		$query->bindParam(':goal',  $goal);
		
		$query->execute();
		return false;
	} catch(PDOException $err) {
		return $err->getMessage();
	}
}


function reduceBalance($dbc, $id, $change) {
	try {
		$change = abs($change);
		$query  = $dbc->prepare("UPDATE envelopes SET balance=balance-:change WHERE id=:id");
		$query->bindParam(':change', $change);
		$query->bindParam(':id',      $id);
		
		$query->execute();
		return false;
	} catch(PDOException $err) {
		return $err->getMessage();
	}
}



function increaseBalance($dbc, $id, $change) {
	try {
		$change = abs($change);
		$query  = $dbc->prepare("UPDATE envelopes SET balance=balance+:change WHERE id=:id");
		$query->bindParam(':change', $change);
		$query->bindParam(':id',      $id);
		
		$query->execute();
		return false;
	} catch(PDOException $err) {
		return $err->getMessage();
	}
}



function changeRefill($dbc, $id, $refill) {
	try {
		$query = $dbc->prepare("UPDATE envelopes SET refill=:refill WHERE id=:id");
		$query->bindParam(':refill', $refill);
		$query->bindParam(':id',     $id);
		
		$query->execute();
		return false;
	} catch(PDOException $err) {
		return $err->getMessage();
	}
}



function changeGoal($dbc, $id, $goal) {
	try {
		$query = $dbc->prepare("UPDATE envelopes SET goal=:goal WHERE id=:id");
		$query->bindParam(':goal', $goal);
		$query->bindParam(':id',    $id);
		
		$query->execute();
		return false;
	} catch(PDOException $err) {
		return $err->getMessage();
	}
}



function renameEnvelope($dbc, $id, $name) {
	try {
		$query = $dbc->prepare("UPDATE envelopes SET name=:name WHERE id=:id");
		$query->bindParam(':name', $name);
		$query->bindParam(':id',   $id);
		
		$query->execute();
		return false;
	} catch(PDOException $err) {
		return $err->getMessage();
	}
}
