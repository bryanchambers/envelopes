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
			refill  SMALLINT NOT NULL,
			goal    SMALLINT NOT NULL,
			balance SMALLINT NOT NULL,
			sort    SMALLINT NOT NULL)";

	return $defs[$table];
}




function dbCreateTable($dbc, $table, $query) {
	try {
		$dbc->query($query);
		
		$warnings = dbGetWarnings($dbc);
		if($warnings) {
			return "Database warning *<span class='error'>$warnings</span>*";
		} else {
			return "Created table $table.";
		}
	} catch(PDOException $err) {
		$error = $err->getMessage();
		if($error && $error != '') {

		} else {
			return "";
		}
	}
}









//Query Types
//   Non-Prepared, No Data
//   Non-Prepared, Returns Data
//   Prepared, No Data
//   Prepared, Returns Data



function dbQueryBasic($dbc, $query, $msg) {
	try{
		$result = $dbc->query($query);
		
		if($msg) {
			return dbWarning(dbGetWarnings($dbc), $msg);
		} else {
			$data = [];

			while($row = $result->fetchObject()) {
				$data[] = $row;
			}
			return $data;
		}
	} catch(PDOException $err) {
		return dbError($err->getMessage());
	}
}



function dbQueryPrep($dbc, $query, $msg) {
	
}




function dbWarning($warning, $msg) {
	if($warning && $warning != '') {
		return "<br/>>> Database warning <br/>>> <span class='sql-msg'>$warning</span>";
	} else {
		return "<br/>>> $msg";
	}
}



function dbError($error) {
	if($error && $error != '') {
		return "<br/>>> Database error <br/>>> <span class='sql-msg'>$error</span>";
	} else {
		return "<br/>>> Something something something error";
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
	$output = ['success' => true, 'data' => [], 'errors' => ''];

	try {
		$query = $dbc->query($query_string);
		
		while($row = $query->fetchObject()) {
			$output['data'][] = $row;
		}
	} catch(PDOException $err) {
		$output['success']  = false;
		$output['errors']   = $err->getMessage();
	}

	return $output;
}



function getEnvelopes($dbc) {
	$query_string = "SELECT * FROM envelopes ORDER BY sort ASC LIMIT 100";
	return getData($query_string, $dbc);
}



function getMaxPosition($dbc) {
	$query_string = "SELECT MAX(sort) AS 'max' FROM envelopes";
	$data = getData($query_string, $dbc);

	if($data['success']) {
		return $data['data'][0]->max;
	} else {
		echo $data['errors'];
		return false;
	}
}






function createEnvelope($dbc, $name, $refill, $goal) {
	try {
		$sort = getMaxPosition($dbc);
		if($sort) {
			$sort++;
		} else {
			$sort = 1;
		}
		
		$query = $dbc->prepare("INSERT INTO envelopes(name, refill, goal, balance, sort) VALUES(:name, :refill, :goal, 0, :sort)");
		$query->bindParam(':name',   $name);
		$query->bindParam(':refill', $refill);
		$query->bindParam(':goal',   $goal);
		$query->bindParam(':sort',   $sort);
		
		$query->execute();
		return "Created new envelope $name with a refill of $refill and a goal of $goal.";
	} catch(PDOException $err) {
		return "Database error *<span class='error'" . $err->getMessage() . "</span>*";
	}
}



function deleteEnvelope($dbc, $name) {
	try {
		$query = $dbc->prepare("DELETE FROM envelopes WHERE name=:name");
		$query->bindParam(':name',   $name);
		
		$query->execute();
		return "Deleted $name";
	} catch(PDOException $err) {
		return "Database error *<span class='error'" . $err->getMessage() . "</span>*";
	}
}




function getSortPosition($dbc, $envelope) {
	try {
		$query = $dbc->prepare("SELECT sort FROM envelopes WHERE name=:envelope LIMIT 1");
		$query->bindParam(':envelope', $envelope);
		$query->execute();
		
		$row = $query->fetchObject();
		return $row->sort;
	} catch(PDOException $err) {
		return "Database error *<span class='error'" . $err->getMessage() . "</span>*";
	}
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
