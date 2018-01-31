<?php

function dbConnect() {
	$file = file_get_contents('db-prod.json', FILE_USE_INCLUDE_PATH);
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
			refill  SMALLINT NOT NULL,
			goal    SMALLINT NOT NULL,
			balance SMALLINT NOT NULL,
			sort    SMALLINT NOT NULL)";

	return $defs[$table];
}












//Query Types
//   Non-Prepared, No Data
//   Non-Prepared, Returns Data
//   Prepared, No Data
//   Prepared, Returns Data



function dbResultHandler($dbc, $query, $usePreparedStatement, $successMessage) {
	try {
		if($usePreparedStatement) {
			$query->execute();
			$result = $query;
		} else {
			$result = $dbc->query($query);
		}

		if($successMessage)  { return dbSuccessHandler($dbc, $successMessage); } 
		else                 { return dbDataHandler($result); }
	} 
	catch(PDOException $err) { return dbErrorHandler($err->getMessage()); }
}





function dbDataHandler($result) {
	$data = [];

	while($row = $result->fetchObject()) { 
		$data[] = $row;
	}

	switch(count($data)) {
		case 0: return false; break;
		
		case 1:
			$row = $data[0];
			
			if(count($row) > 0) {
				return $row;
			} else {
				return false;
			}

		default: return $data;
	}
}



function dbSuccessHandler($dbc, $successMessage) {
	$warning = dbGetWarnings($dbc);

	if($warning && $warning != '') {
		return adminMessageWrapper('warning', $warning);
	} else {
		return adminMessageWrapper('normal', $successMessage);
	}
}



function dbErrorHandler($error) {
	if($error && $error != '') {
		return adminMessageWrapper('error', $warning);
	} else {
		return adminMessageWrapper('normal', "Well, this is embarassing. Something went wrong but I have no idea what.");
	}
}



function adminMessageWrapper($messageType, $messageText) {
	$cursor = '>>';
	$class  = 'db-msg';

	if($messageType == 'warning' or $messageType == 'error') {
		$message = "<br/>$cursor Database $messageType";
		$details = "<br/>$cursor <span class='$class'>$messageText</span>";
		return $message . $details;
	} 
	else { 
		return "<br/>$cursor $messageText";
	}
}














function dbCreateTable($dbc, $table) {
	$query                = tableDefs($table);
	$successMessage       = "Created table $table";
	$usePreparedStatement = false;

	return dbResultHandler($dbc, $query, $usePreparedStatement, $successMessage);
}



function dbDropTable($dbc, $table) {
	$query                = "DROP TABLE IF EXISTS $table";
	$successMessage       = "Dropped table $table";
	$usePreparedStatement = false;

	return dbResultHandler($dbc, $query, $usePreparedStatement, $successMessage);
}




function getEnvelopes($dbc) {
	$query                = "SELECT * FROM envelopes ORDER BY sort ASC LIMIT 100";
	$successMessage       = false;
	$usePreparedStatement = false;

	return dbResultHandler($dbc, $query, $usePreparedStatement, $successMessage);
}





function getMaxPosition($dbc) {
	$query                = "SELECT MAX(sort) AS 'max' FROM envelopes";
	$successMessage       = false;
	$usePreparedStatement = false;

	return dbResultHandler($dbc, $query, $usePreparedStatement, $successMessage)->max;
}







function getInitPosition($dbc) {
	$max = getMaxPosition($dbc);
	
	if($max) { return $max++; } 
	else     { return 1; }
}







function createEnvelope($dbc, $name, $refill, $goal) {
	$sort = getInitPosition($dbc);

	$query = $dbc->prepare("INSERT INTO envelopes(name, refill, goal, balance, sort) VALUES(:name, :refill, :goal, 0, :sort)");
	$query->bindParam(':name',   $name);
	$query->bindParam(':refill', $refill);
	$query->bindParam(':goal',   $goal);
	$query->bindParam(':sort',   $sort);

	$successMessage       = "Created envelope $name $refill/$goal";
	$usePreparedStatement = true;
		
	return dbResultHandler($dbc, $query, $usePreparedStatement, $successMessage);
}




function deleteEnvelope($dbc, $envelope) {
	$query = $dbc->prepare("DELETE FROM envelopes WHERE name=:envelope LIMIT 1");
	$query->bindParam(':envelope', $envelope);

	$successMessage       = "Deleted envelope $envelope";
	$usePreparedStatement = true;
		
	return dbResultHandler($dbc, $query, $usePreparedStatement, $successMessage);
}




function getSortPosition($dbc, $envelope) {
	$query = $dbc->prepare("SELECT sort FROM envelopes WHERE name=:envelope LIMIT 1");
	$query->bindParam(':envelope', $envelope);

	$successMessage       = false;
	$usePreparedStatement = true;
		
	return dbResultHandler($dbc, $query, $usePreparedStatement, $successMessage);
}











function shiftEnvelopesDown($dbc, $position) {
	try {
		$query = $dbc->prepare("UPDATE envelopes SET sort = sort + 1 WHERE sort < :position");
		$query->bindParam(':position', $position);
		
		$query->execute();
		return "Shifted envelopes below position $position";
	} catch(PDOException $err) {
		return "Database error *<span class='error'" . $err->getMessage() . "</span>*";
	}
}


function moveEnvelopeToTop($dbc, $envelope) {
	try {
		$query = $dbc->prepare("UPDATE envelopes SET sort = 1 WHERE name = :envelope LIMIT 1");
		$query->bindParam(':envelope', $envelope);
		
		$query->execute();
		return "Moved $envelope to top";
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



function refill($dbc, $envelope) {
	try {
		if($envelope == 'all') {
			$conditions = '';
		} else {
			$conditions = "WHERE name = :envelope LIMIT 1";
		}
		$query = $dbc->prepare("UPDATE envelopes SET balance = balance + refill $conditions");
		$query->bindParam(':envelope', $envelope);
		
		$query->execute();
		return "Refilled $envelope";
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
