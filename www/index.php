<?
	session_start();
	function load_game($gameid) {
		if (!preg_match("/^[a-z0-9]{13,13}$/", $gameid))
		{
			header('Location: ?please_dont_hack_me');
			exit;
		}

		$filename = 'games/'.$gameid.'.js';
		if (!file_exists($filename))
		{
			header('Location: ?game_not_exists');
			exit;
		}
		return json_decode(file_get_contents($filename), true);
	};
	
	function save_game($game) {
		$json = json_encode($game);
		file_put_contents('games/'.$game['gameid'].'.js', $json);
	};
	
	function create_game() {
		$game = array();
		$game['gameid'] = uniqid();
		$game['author'] = $_SESSION['nick'];
		$game['next_player'] = $_SESSION['nick'];
		$game['winner'] = '?';
		$game['fields'] = array();
		for ($x = 0; $x < 3; $x++) {
			for ($y = 0; $y < 3; $y++) {
				$game['fields']['c'.$x.$y] = '?';
			}
		}
		return $game;
	}
	
	function check_winner($game) {
		
		// vertical
		for ($x = 0; $x < 3; $x++) {
			$check = array('X' => 0, '0' => 0);
			for ($y = 0; $y < 3; $y++) {
				if ($game['fields']['c'.$x.$y] == 'X')
					$check['X']++;
				if ($game['fields']['c'.$x.$y] == '0')
					$check['0']++;
			}
			if ($check['X'] == 3)
				return 'X';
			if ($check['0'] == 3)
				return '0';
		}
		
		// horizontal
		for ($y = 0; $y < 3; $y++) {
			$check = array('X' => 0, '0' => 0);
			for ($x = 0; $x < 3; $x++) {
				if ($game['fields']['c'.$x.$y] == 'X')
					$check['X']++;
				if ($game['fields']['c'.$x.$y] == '0')
					$check['0']++;
			}
			if ($check['X'] == 3)
				return 'X';
			if ($check['0'] == 3)
				return '0';
		}
		
		// diagonal 1
		$check = array('X' => 0, '0' => 0);
		for ($i = 0; $i < 3; $i++) {
			if ($game['fields']['c'.$i.$i] == 'X')
				$check['X']++;
			if ($game['fields']['c'.$i.$i] == '0')
				$check['0']++;
		}
		if ($check['X'] == 3)
			return 'X';
		if ($check['0'] == 3)
			return '0';
			
		// diagonal 2
		$check = array('X' => 0, '0' => 0);
		for ($i = 0; $i < 3; $i++) {
			if ($game['fields']['c'.$i.(2 - $i)] == 'X')
				$check['X']++;
			if ($game['fields']['c'.$i.(2 - $i)] == '0')
				$check['0']++;
		}
		if ($check['X'] == 3)
			return 'X';
		if ($check['0'] == 3)
			return '0';
						
		return '';
	}
	
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
		$game = create_game();
		$gameid = $game['gameid'];
		$_SESSION['gameid'] = $gameid;
		save_game($game);
		header('Location: ?gameid='.$gameid);
		exit;
	}

	if (isset($_GET['exit_game'])) {
		unset($_SESSION['gameid']);
		header('Location: ?');
		exit;
	}
	
	if (isset($_GET['setgameid'])) {
		$gameid = $_GET['setgameid'];
		$game = load_game($gameid);
		if ($game['author'] != $_SESSION['nick'])
		{
			if (!isset($game['player2'])) {
				$game['player2'] = $_SESSION['nick'];
				$_SESSION['gameid'] = $game['gameid'];
				save_game($game);
				header('Location: ?');
				exit;
			} else {
				if ($game['player2'] == $_SESSION['nick']) {
					$_SESSION['gameid'] = $game['gameid'];
					header('Location: ?');
					exit;
				}
			}
			header('Location: ?game_busy');
			exit;
		} else {
			$_SESSION['gameid'] = $game['gameid'];
		}

		header('Location: ?gameid='.$_SESSION['gameid']);
		exit;
	}
	
	if (isset($_GET['gameid']) && isset($_GET['cell'])) {
		$gameid = $_GET['gameid'];
		$cell = $_GET['cell'];
		$game = load_game($gameid);

		if (!preg_match("/^c[0-2]{1}[0-2]{1}$/", $cell))
		{
			header('Location: ?please_dont_hack_me');
			exit;
		}
		
		if ($game['next_player'] != $_SESSION['nick']) {
			echo 'Wait player2 for step';
			exit;
		}
		if (!isset($game['player2'])) {
			echo 'Wait connect player2';
			exit;
		}
		if ($game['winner'] != '?') {
			echo 'Game ended. winner: '.$game['winner'];
			exit;
		}
		
		if ($game['fields'][$cell] != '?') {
			echo 'Cell is filled. Please choose "?".';
			exit;
		}

		if ($game['author'] == $_SESSION['nick']) {
			$game['fields'][$cell] = 'X';
			$game['next_player'] = $game['player2'];
		} else if ($game['player2'] == $_SESSION['nick']) {
			$game['fields'][$cell] = '0';
			$game['next_player'] = $game['author'];
		}
		$winner = check_winner($game);
		if ($winner != '')
			$game['winner'] = $winner == 'X' ?  $game['author'] : $game['player2'];
		save_game($game);
		exit;
	}
?>
<html>
	<head>
		<title> Tic Tac Toe </title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" href="css/main.css" />
		<script type="text/javascript">
//<![CDATA[

function load_game() {
	
	var gameid = document.getElementById('gameid').innerHTML;
	var debug = document.getElementById('debug');
	// debug.innerHTML += "reload game\n";
	
	if (window.XMLHttpRequest)
	{
		// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	};  
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
			var game = JSON.parse(xmlhttp.responseText);
			document.getElementById('author').innerHTML = game.author;
			document.getElementById('player2').innerHTML = game.player2;
			document.getElementById('next_player').innerHTML = game.next_player;
			document.getElementById('winner').innerHTML = game.winner;
			document.getElementById('c00').innerHTML = game.fields.c00;
			document.getElementById('c01').innerHTML = game.fields.c01;
			document.getElementById('c02').innerHTML = game.fields.c02;
			document.getElementById('c10').innerHTML = game.fields.c10;
			document.getElementById('c11').innerHTML = game.fields.c11;
			document.getElementById('c12').innerHTML = game.fields.c12;
			document.getElementById('c20').innerHTML = game.fields.c20;
			document.getElementById('c21').innerHTML = game.fields.c21;
			document.getElementById('c22').innerHTML = game.fields.c22;
		}
	}
	xmlhttp.open("GET","games/"+gameid+".js",true);
	xmlhttp.send();
}

var interval = null;

function onload_body() {
	load_game();
	interval = setInterval(load_game, 1000);
}

function set_here(e) {
	clearInterval(interval);
	var gameid = document.getElementById('gameid').innerHTML;
	var debug = document.getElementById('debug');
	
	debug.innerHTML += "your set " + e.id + "\n";
	
	if (window.XMLHttpRequest)
	{
		// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	};  
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
			debug.innerHTML += xmlhttp.responseText + "\n";
			onload_body();
		}
	}
	var url = "?gameid="+gameid+"&cell="+e.id;

	xmlhttp.open("GET",url,true);
	xmlhttp.send();	
};
//]]>
		</script>
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
		echo '
			<div class="x_line">
				<div class="x_label_name">Your Nick:</div> 
				<div class="x_label_value" id="nick">'.$_SESSION['nick'].'</div>
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
			</div>
			<div class="x_line">
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
				<div class="x_label"><a href="?exit_game">exit</a>
			</div>
			</div>
				<div class="x_line">
				<div class="x_label_name"></div> 
				<div class="x_label_value"><hr></div>
			</div>
				';
			echo '
				<div class="x_line">
					<div class="x_label_name">Author:</div>
					<div class="x_label_value" id="author">?</div>
				</div>
				<div class="x_line">
					<div class="x_label_name">Player2:</div>
					<div class="x_label_value" id="player2">?</div>
				</div>
				
				<div class="x_line">
					<div class="x_label_name">Next player:</div>
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
				<pre id="debug"></pre>
			
			';
		} 
	}
	echo '</div>';
?>
	</body>
</html>
