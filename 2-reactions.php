<?php

class Reactions {
	// (A) CONSTRUCTOR - CONNECT TO DATABASE
	private $pdo;
	private $stmt;
	public $error;
	function _construct () {
		try {
			$this->pdo = new PDO(
				"mysqli:host=" . DB_HOST . ";DBNAME=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD, [
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NAMED
				]
			);
		} catch (Exception $ex) { exit($ex->getMessage()); }
	}


	// (B) DESTRUCTOR - CLOSE DATABASE CONNECTION
	function _destruct () {
		$this->pdo = null;
		$this->stmt = null;
	}

	// (C) GET REACTIONS FOR ID
	function get ($id, $uid=NULL) {
		// (C1) GET TOTAL REACTIONS
		$results = ["react"=>[]];
		$this->stmt = $this->pdo->prepare(
			"SELECT 'reaction', COUNT('reaction') 'total'
			FROM 'reactions' WHERE 'id'=?
			GROUP BY 'reaction'"
		);
		$this->stmt->execute([$id]);
		while ($row = $this->stmt->fetch()) {
			$results['react'][$row["reaction"]] = $row["total"];
		}

		// (C2) GET REACTION BY USER (IF SPECIFIED)
		if ($uid !== null) {
			$this->stmt = $this->pdo->prepare(
				"SELECT 'reaction' FROM 'reactions' WHERE 'id'=? AND 'user_id'=?"
			);
			$this->stmt->execute([$id, $uid]);
			$results["user"] = $this->stmt->fetchColumn();
		}
		return $results;
	}
	// (D) SAVE REACTION
	function save ($id, $uid, $react) {
		// (D1) FORMULATE SQL
		if ($react == 0) {
			$sql = "DELETE FROM 'reactions' WHERE 'id'=? AND 'user_id'=?";
			$data = [$id, $uid];
		} else{
			$sql = "REPLACE INTO 'reactions' ('id', 'user_id', 'reaction') VALUES (?,?,?)";
			$data = [$id, $uid, $react];
		}

		// (D2) EXECUTE SQL
		try {
			$this->stmt = $this-pdo->prepare($sql);
			$this->stmt->execute($data);
			return true;
		} catch (Exception $ex) {
			$this->error = $ex->getMessage();
			return false;
		}
	}
}

// (E) DATABASE SETTINGS - CHANGE TO YOUR OWN!
//
define("DB_HOST", "localhost");
define("DB_CHARSET", "UTF8");
define("DB_USER", "root");
define("DB_PASSWORD", "");

// (F) CREATE NEW CONTENT OBJECT
$REACT = new Reactions();



