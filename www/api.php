<?
	include_once("config/config.php");
	include_once("game.php");
	$game = new game($conn);

	
	if (isset($_GET['gameid']) && isset($_GET['json'])) {
		$gameid = $_GET['gameid'];
		if ($game->load($gameid) == true)
		{
			echo $game->json();
			exit;
		}
		
		echo $game->getLastError();
		exit;	
	}
	
	if (isset($_GET['gameid']) && isset($_GET['cell'])) {
		$gameid = $_GET['gameid'];
		$cell = $_GET['cell'];
		if(!$game->load($gameid)) {
			echo $game->getLastError();
			exit;
		}

		if (!preg_match("/^c[0-2]{1}[0-2]{1}$/", $cell))
		{
			header('Location: ?please_dont_hack_me');
			exit;
		}
		
		if ($game->next_player() != $_SESSION['nick']) {
			echo 'Wait player2 for step';
			exit;
		}

		if ($game->player2() == '?') {
			echo 'Wait connect player2';
			exit;
		}
		
		if ($game->winner() != '?') {
			echo 'Game ended. winner: '.$game->winner();
			exit;
		}

		if ($game->cell($cell) != '?') {
			echo 'Cell is filled. Please choose "?".';
			exit;
		}

		if ($game->author() == $_SESSION['nick']) {
			$game->setCell($cell, 'X');
			$game->setNextPlayer($game->player2());
		} else if ($game->player2() == $_SESSION['nick']) {
			$game->setCell($cell, '0');
			$game->setNextPlayer($game->player1());
		}

		$winner = $game->check_winner();
		if ($winner != '') {
			$game->setWinner($winner);
			echo 'Winner: '.$winner;
		} else {
			echo 'Cell set '.$cell;
		}

		$game->save();
		exit;
	}
