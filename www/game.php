<?
	class game {
		private $id = null;
		private $lastError = '';
		private $game_arr = null;
		private $conn = null;
		private $bUpdate = false;
		
		public function game($conn) {
			$this->conn = $conn;
		}
		
		public function isLoaded() {
			return $this->game_arr != null;
		}
		
		public function setAuthor($author) {
			if ($this->game_arr == null)
				$this->game_arr = array();
			$this->game_arr['author'] = $author;
		}
		
		public function setWinner($winner) {
			if ($this->game_arr == null)
				$this->game_arr = array();
			$this->game_arr['winner'] = $winner;
		}
		
		public function setPlayer1($player1) {
			if ($this->game_arr == null)
				$this->game_arr = array();
			$this->game_arr['author'] = $player1;
		}
		
		public function setPlayer2($player2) {
			if ($this->game_arr == null)
				$this->game_arr = array();

			$this->game_arr['player2'] = $player2;
		}
		
		public function setNextPlayer($next_player) {
			if ($this->game_arr == null)
				$this->game_arr = array();

			$this->game_arr['next_player'] = $next_player;
		}
		
		public function setCell($cell, $value) {
			
			if ($this->game_arr == null) {
				$this->game_arr = array();
				$this->game_arr['fields'] = array();
			}
			
			$this->game_arr['fields'][$cell] = $value;
		}

		public function author() {
			if ($this->game_arr != null)
				return $this->game_arr['author'];
			return null;
		}
		
		public function player1() {
			if ($this->game_arr != null)
				return $this->game_arr['author'];
			return null;
		}
		
		public function player2() {
			if ($this->game_arr != null)
				return $this->game_arr['player2'];
			return null;
		}
		
		public function winner() {
			if ($this->game_arr != null)
				return $this->game_arr['winner'];
			return null;
		}
		
		public function cell($cell) {
			if ($this->game_arr != null)
				return $this->game_arr['fields'][$cell];
			return null;
		}

		public function next_player() {
			if ($this->game_arr != null)
				return $this->game_arr['next_player'];
			return null;
		}
		
		public function uuid() {
			if ($this->game_arr != null)
				return $this->game_arr['gameid'];
			return null;
		}
		
		public function json() {
			if ($this->game_arr != null)
				return json_encode($this->game_arr);
			return '';
		}
		
		public function load($gameid) {

			if (!preg_match("/^[a-z0-9]{13,13}$/", $gameid))
			{
				$this->lastError = 'please_dont_hack_me';
				return false;
			}
			
			try {
				$this->game_arr = null;
				$stmt = $this->conn->prepare('select * from games where uuid = ?');
				$stmt->execute(array($gameid));
				if($row = $stmt->fetch()) {
					$this->game_arr = json_decode($row['json'], true);
					$this->bUpdate = true;
				} else {
					$this->lastError = 'game_not_exists';
					return false;
				}
			} catch(PDOException $e) {
				$this->lastError = $e->getMessage();
				return false;
			}
			return true;
		}
		
		public function create() {
			$this->game_arr = array();
			$this->game_arr['gameid'] = uniqid();
			$this->game_arr['author'] = $_SESSION['nick'];
			$this->game_arr['player2'] = '?';
			$this->game_arr['next_player'] = $_SESSION['nick'];
			$this->game_arr['winner'] = '?';
			$this->game_arr['fields'] = array();
			for ($x = 0; $x < 3; $x++) {
				for ($y = 0; $y < 3; $y++) {
					$this->game_arr['fields']['c'.$x.$y] = '?';
				}
			}
			$this->bUpdate = false;
			return true;
		}
		
		public function save() {
			
			$query = '';
			if ($this->bUpdate == true) {
				$query = '
					UPDATE games SET
						author = ?, player2 = ?, winner = ?, 
						json = ?, last_changed = NOW()
					WHERE 
						uuid = ?;
				';
			} else {
				$query = '
					INSERT INTO games (author, player2, winner, json, create_date, last_changed, uuid)
					VALUES(?,?,?,?,NOW(), NOW(), ?);
				';
			}

			$arr = array();
			$arr[] = $this->game_arr['author'];
			$arr[] = $this->game_arr['player2'];
			$arr[] = $this->game_arr['winner'];
			$arr[] = json_encode($this->game_arr);
			$arr[] = $this->game_arr['gameid'];

			try {
				$q = $this->conn->prepare($query);
				if($q->execute($arr) != 1)
				{
					echo $query;
					$this->lastError = "Could not update or insert";
					return false;
				}
			} catch(PDOException $e) {
				$this->lastError = $e->getMessage();
				return false;
			}
			$this->bUpdate = true;
			return true;
		}

		public function getLastError() {
			return $this->lastError;
		}

		public function check_winner() {
			// vertical
			for ($x = 0; $x < 3; $x++) {
				$check = array('X' => 0, '0' => 0);
				for ($y = 0; $y < 3; $y++) {
					if ($this->cell('c'.$x.$y) == 'X')
						$check['X']++;
					if ($this->cell('c'.$x.$y) == '0')
						$check['0']++;
				}
				if ($check['X'] == 3)
					return $this->author();
				if ($check['0'] == 3)
					return $this->player2();
			}
			
			// horizontal
			for ($y = 0; $y < 3; $y++) {
				$check = array('X' => 0, '0' => 0);
				for ($x = 0; $x < 3; $x++) {
					if ($this->cell('c'.$x.$y) == 'X')
						$check['X']++;
					if ($this->cell('c'.$x.$y) == '0')
						$check['0']++;
				}
				if ($check['X'] == 3)
					return $this->author();
				if ($check['0'] == 3)
					return $this->player2();
			}
			
			// diagonal 1
			$check = array('X' => 0, '0' => 0);
			for ($i = 0; $i < 3; $i++) {
				if ($this->cell('c'.$i.$i) == 'X')
					$check['X']++;
				if ($this->cell('c'.$i.$i) == '0')
					$check['0']++;
			}
			if ($check['X'] == 3)
				return $this->author();
			if ($check['0'] == 3)
				return $this->player2();
				
			// diagonal 2
			$check = array('X' => 0, '0' => 0);
			for ($i = 0; $i < 3; $i++) {
				if ($this->cell('c'.$i.(2 - $i)) == 'X')
					$check['X']++;
				if ($this->cell('c'.$i.(2 - $i)) == '0')
					$check['0']++;
			}
			if ($check['X'] == 3)
				return $this->author();
			if ($check['0'] == 3)
				return $this->player2();
			
			
			for ($y = 0; $y < 3; $y++) {
				$check = array('X' => 0, '0' => 0);
				for ($x = 0; $x < 3; $x++) {
					if ($this->cell('c'.$x.$y) == 'X')
						$check['X']++;
					if ($this->cell('c'.$x.$y) == '0')
						$check['0']++;
				}
				if ($check['X'] == 3)
					return $this->author();
				if ($check['0'] == 3)
					return $this->player2();
			}

			// todo draw
			$check2 = 0; 
			for ($x = 0; $x < 3; $x++) {
				for ($y = 0; $y < 3; $y++) {
					if ($this->cell('c'.$x.$y) != '?')
						$check2++;
				}
			}

			if ($check2 == 9)
				return 'draw';

			return '';
		}
	};
