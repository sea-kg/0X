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
	<body onload="onload_body();">
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

	if (!isset($_SESSION['nick'])) {
		?>
			<form>Your nick:
				<input type="text" name="setnick" value=""/>
				<input type="submit" value="logon"/>
			</form>
		<?
	} else {
		echo 'Your Nick: <div id="nick">'.$_SESSION['nick'].'</div> <a href="?rmnick">logoff</a><br><br>';
		if (!isset($_SESSION['gameid']))
		{
			echo '<a href="?create_game">create game</a><br> or 
				<form>GameID:
					<input type="text" name="setgameid" value=""/>
					<input type="submit" value="select"/>
				</form>
			';
		} else {
			echo 'GameID: <div id="gameid">'.$_SESSION['gameid'].'</div> <a href="?exit_game">exit</a><br>';
			echo '
			Author: <div id="author">?</div>
			Player2: <div id="player2">?</div>
			<table bgcolor=black cellpadding=10 cellspacing=1>
				<tr>
					<td bgcolor=white id="c00" onclick="set_here(this);">?</td>
					<td bgcolor=white id="c01" onclick="set_here(this);">?</td>
					<td bgcolor=white id="c02" onclick="set_here(this);">?</td>
				</tr>
				<tr>
					<td bgcolor=white id="c10" onclick="set_here(this);">?</td>
					<td bgcolor=white id="c11" onclick="set_here(this);">?</td>
					<td bgcolor=white id="c12" onclick="set_here(this);">?</td>
				</tr>
				<tr>
					<td bgcolor=white id="c20" onclick="set_here(this);">?</td>
					<td bgcolor=white id="c21" onclick="set_here(this);">?</td>
					<td bgcolor=white id="c22" onclick="set_here(this);">?</td>
				</tr>
			</table>
			Next player:
			<div id="next_player">?</div>
			Winner:
			<div id="winner">?</div>
			<pre id="debug"></pre>
			
			';
		} 
	}
?>
	</body>
</html>
