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





function dbCreateTable($dbc, $table, $query) {
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




function addEnvelope($dbc, $name, $refill, $cycle) {
	try {
		$query = $dbc->prepare("INSERT INTO envelopes(name, refill_amount, refill_cycle) VALUES(:name, :refill, :cycle)");
		$query->bindParam(':name',   $name);
		$query->bindParam(':refill', $refill);
		$query->bindParam(':cycle',  $cycle);
		
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



function changeRefillAmount($dbc, $id, $amount) {
	try {
		$query = $dbc->prepare("UPDATE envelopes SET refill_amount=:amount WHERE id=:id");
		$query->bindParam(':amount', $amount);
		$query->bindParam(':id',     $id);
		
		$query->execute();
		return false;
	} catch(PDOException $err) {
		return $err->getMessage();
	}
}



function changeRefillCycle($dbc, $id, $cycle) {
	try {
		$query = $dbc->prepare("UPDATE envelopes SET refill_cycle=:cycle WHERE id=:id");
		$query->bindParam(':cycle', $cycle);
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
