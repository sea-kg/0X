<?

$user = 'tictactoe';
$pass = 'tictactoe';
$dbname = 'tictactoe';
$dbhost = '127.0.0.1';
$conn = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8", $user, $pass);

session_start();

function refreshTo($new_page)
{
        header ("Location: $new_page");
        exit;
};
