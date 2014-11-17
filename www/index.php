<?
	include_once("config/config.php");
	include_once("game.php");
	$game = new game($conn);
	
	if (isset($_GET['setnick'])) {
		$_SESSION['nick'] = htmlspecialchars($_GET['setnick']);
		header("Location: ?");
		exit;
	}

	if (isset($_GET['rmnick'])) {
		unset($_SESSION['nick']);
		header("Location: ?");
		exit;
	}

	if (isset($_GET['create_game'])) {
		$game->create();
		$_SESSION['gameid'] = $game->uuid();
		if (!$game->save()) {
			echo "Error: ".$game->getLastError();
			exit;
		}
		header('Location: ?gameid='.$game->uuid());
		exit;
	}

	if (isset($_GET['exit_game'])) {
		unset($_SESSION['gameid']);
		header('Location: ?');
		exit;
	}
	
	if (isset($_GET['setgameid'])) {
		$gameid = $_GET['setgameid'];
		if (!$game->load($gameid)) {
			echo "Error: ".$game->getLastError();
			exit;
		}
		if ($game->author() != $_SESSION['nick'])
		{
			if ($game->player2() == '?') {
				$game->setPlayer2($_SESSION['nick']);
				$_SESSION['gameid'] = $game->uuid();
				if(!$game->save()) {
					echo "Error: ".$game->getLastError();
					exit;
				}
				header('Location: ?');
				exit;
			} else {
				if ($game->player2() == $_SESSION['nick']) {
					$_SESSION['gameid'] = $game->uuid();
					header('Location: ?');
					exit;
				}
			}
			header('Location: ?game_busy');
			exit;
		} else {
			$_SESSION['gameid'] = $game->uuid();
		}

		header('Location: ?gameid='.$_SESSION['gameid']);
		exit;
	}
?>
<html>
	<head>
		<title>0X</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" href="css/main.css" />
		<script src="js/main.js"></script>
	</head>
	<body class="x_main" onload="onload_body();">
<?
	if (isset( $_GET['game_not_exists']))
	{
		echo '<h1>Game are not exits</h1><br>';
	}
	
	if (isset( $_GET['please_dont_hack_me']))
	{
		echo '<h1>Please don\'t hack me!</h1><br>';
	}
	
	if (isset( $_GET['game_busy']))
	{
		echo '<h1>Game busy!</h1><br>';
	}
	echo '<h1>0X</h1>';
	echo '<div class="x_content">';

	if (!isset($_SESSION['nick'])) {
		echo '
			<div class="x_line">
				<div class="x_label_name">Your Nick:</div>
				<div class="x_label_value">
					<form>
						<input type="text" name="setnick" value=""/>
						<input type="submit" value="logon"/>
					</form>
				</div> 
			</div>
		';
	} else {
		$cnt = 0;
		try {
			$nick = $_SESSION['nick'];
			$stmt = $conn->prepare('SELECT count(id) as cnt FROM games WHERE winner = ?');
			$stmt->execute(array($nick));
			if($row = $stmt->fetch()) {
				$cnt = $row['cnt'];
			}
		} catch(PDOException $e) {
		}
			
		echo '
			<div class="x_line">
				<div class="x_label_name">Your Nick:</div> 
				<div class="x_label_value" id="nick">'.$_SESSION['nick'].' ('.$cnt.' wins)</div>
			</div>
			<div class="x_line">
				<div class="x_label_name"></div> 
				<div class="x_label_value"><a href="?rmnick">logoff</a></div>
			</div>
			<div class="x_line">
				<div class="x_label_name"></div> 
				<div class="x_label_value"><hr></div>
			</div>
		';
		if (!isset($_SESSION['gameid']))
		{
			echo '
			<div class="x_line">
				<div class="x_label_name">GameID:</div>
				<div class="x_label_value">
					<form>
						<input type="text" name="setgameid" value=""/>
						<input type="submit" value="select"/>
					</form>
				</div>
			</div>
			<div class="x_line">
				<div class="x_label_name"></div>
				<div class="x_label_value">
					or <a href="?create_game">create game</a>
				</div>
			</div>';
			
			
			
			try {
				$nick = $_SESSION['nick'];
				$stmt = $conn->prepare('SELECT create_date, author, player2, uuid FROM games WHERE (player2 = ?) or (winner = ? and (author = ? or player2 = ?)) ORDER BY create_date DESC LIMIT 0 , 10');
				$stmt->execute(array('?', '?', $nick, $nick));
				while($row = $stmt->fetch()) {
					
					$create_date = $row['create_date'];
					$author = $row['author'];
					$uuid = $row['uuid'];
					$player2 = $row['player2'];
					
					echo '
					<div class="x_line">
						<div class="x_label_name"></div>
						<div class="x_label_value">
							'.$create_date.', <a href="?setgameid='.$uuid.'">'.$uuid.'</a>, '.$author.' vs '.$player2.'
						</div>
					</div>
					';
				}
			} catch(PDOException $e) {
			}
					// or <a href="?create_game">create game</a>
			
			echo '<div class="x_line">
				<div class="x_label_name"></div> 
				<div class="x_label_value"><hr></div>
			</div>
			';
		} else {
			echo '
			<div class="x_line">
				<div class="x_label_name">GameID:</div>
				<div class="x_label_value" id="gameid">'.$_SESSION['gameid'].'</div>
			</div>
			<div class="x_line">
				<div class="x_label_name"></div>
				<div class="x_label_value"><a href="?exit_game">exit</a>
			</div>
			</div>
				<div class="x_line">
				<div class="x_label_name"></div> 
				<div class="x_label_value"><hr></div>
			</div>
				';
			echo '
				<div class="x_line">
					<div class="x_label_name">Author (Player1):</div>
					<div class="x_label_value" id="author">?</div>
				</div>
				<div class="x_line">
					<div class="x_label_name">Player2:</div>
					<div class="x_label_value" id="player2">?</div>
				</div>
				
				<div class="x_line">
					<div class="x_label_name">Player\'s turn:</div>
					<div class="x_label_value" id="next_player">?</div>
				</div>
				
				<div class="x_line">
					<div class="x_label_name">Winner:</div>
					<div class="x_label_value" id="winner">?</div>
				</div>
			
				<div class="x_line">
					<div class="x_label_name">Game:</div>
					<div class="x_label_value">
						<div class="x_fields">
							<div class="x_fields_row">
								<div id="c00" class="x_fields_cell" onclick="set_here(this);">?</div>
								<div id="c01" class="x_fields_cell" onclick="set_here(this);">?</div>
								<div id="c02" class="x_fields_cell" onclick="set_here(this);">?</div>
							</div>
							<div class="x_fields_row">
								<div id="c10" class="x_fields_cell" onclick="set_here(this);">?</div>
								<div id="c11" class="x_fields_cell" onclick="set_here(this);">?</div>
								<div id="c12" class="x_fields_cell" onclick="set_here(this);">?</div>
							</div>
							<div class="x_fields_row">
								<div id="c20" class="x_fields_cell" onclick="set_here(this);">?</div>
								<div id="c21" class="x_fields_cell" onclick="set_here(this);">?</div>
								<div id="c22" class="x_fields_cell" onclick="set_here(this);">?</div>
							</div>
						</div>
					</div>
				</div>
			';
		} 
	}
	echo '</div>
	<pre id="debug"></pre>
	<div id="opened_games">
	</div>
	<div id="stat_games">
	</div>
	';
?>
	</body>
</html>
