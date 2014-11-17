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
	xmlhttp.open("GET","api.php?gameid="+gameid+"&json",true);
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
	
	// debug.innerHTML += "your set " + e.id + "\n";
	
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
	var url = "api.php?gameid="+gameid+"&cell="+e.id;

	xmlhttp.open("GET",url,true);
	xmlhttp.send();	
};
