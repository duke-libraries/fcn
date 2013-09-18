<?
	/**
	* login.php: handle login attempts for Fantasy Collecting.
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 8/2012
	*
	* @param username The username from index.php.
	* @param password The password guess from index.php.
	*/

	// Begin a session and include the database initializer
	session_start();
        require_once 'game/db.php';
	$gameinstance = -1;

?>
<html>
<head>
<title>User Frontend</title>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">

        google.load( "jquery", "1" );
        google.load( "jqueryui", "1" );

	function redirect( ) {
		window.location = 'game/home.php';
	}

</script>
<link rel="stylesheet" href="game/fcn.css" type="text/css"/>
<link rel="stylesheet" type="text/css" href="game/jquery-ui.css"/>
</head>
<body>
<?
	$uuid = 0;
	$uname = "";
	$guess = $_POST['password'];

	// Passwords are encrypted with MD5, which is probably fine for a simple Web game...
	$stmt = $dbh->prepare( "SELECT id,name,COUNT(*) AS c FROM collectors WHERE name = ? AND password = MD5(?)" );
	$stmt->bindValue( 1, $_POST['username'] );
	$stmt->bindValue( 2, $guess );
	$stmt->execute( );
	while( $row = $stmt->fetch( ) )
	{
		if ( $row['c'] != "1" ) 
		{
			echo( "<h1>Invalid user or password.</h1>" );
			echo ("</body></html>\n" );
			exit( );
		}
		$uuid = $row['id'];
		$uname = $row['name'];
	}

	// See if the user is playing more than one FC game.  This functionality is deprecated, but the code
	// remains here in case someone wants to re-implement it in the future.  
	$stmt = $dbh->prepare( "SELECT COUNT(*) as c,collections.id,owner,gameinstance,games.name as gn,games.ended,games.id FROM collections INNER JOIN games ON games.id = gameinstance WHERE owner = ? AND UNIX_TIMESTAMP( games.ended ) = 0" ); 
	$stmt->bindValue( 1, $uuid );
	$stmt->execute( );

	while( $row = $stmt->fetch( ) ) 
	{
		if( $row['c'] == "1" ) 
		{
			// Just one session -- easy enough	
			$gameinstance = $row[ 'gameinstance' ];
			break;
		}
		else if ( $row['c'] == "0" )
		{
			// No games.  TODO: write game selection logic.
			echo( "<h4>You aren't participating in any games!</h4>" );
			echo( "Select a game to join" );
		}
		else
		{
			// Multiple active games.  TODO: make this form do something, if multiple game support is required.
			echo( "<h4>Multple Active Games</h4>You're participating in more than one game right now.  Which game would you like to join?<p/>\n" );
			echo( "<form method=\"post\" action=\"log.php\">\n" );
			echo( "<select name=\"gameChoice\">\n" );
			echo( "<option value=\"" . $row['gameinstance'] . "\">Game #" . $row['gameinstance'] . " (" . $row['gn'] . ")</option>\n" );
			echo( "</select>\n<input type=\"hidden\" name=\"validated\" value=\"1\"/>\n" );
			echo( "<input type=\"hidden\" name=\"uuid\" value=\"" . $uuid . "\"/>\n" );
			echo( "<input type=\"hidden\" name=\"mode\" value=\"gameChoice\"/>\n" );
			echo( "<input type=\"Submit\" value=\"Join game\"></form>\n" );
		}	
	}

	// Username/password were correct.  Set up some session variables and redirect to the homepage.
	echo( "<h1>Login successful</h1>\nSending you to your user page.\n" );
	$_SESSION['uuid'] = $uuid;
	$_SESSION['uname'] = $uname;
	$_SESSION['gameinstance'] = $gameinstance;

	// Keep track of logins for assessment, data analysis, etc., in case the data turn out to be useful.
	$stmt = $dbh->prepare( "INSERT INTO logins(uid) VALUES(?)" );
	$stmt->bindParam( 1, $uuid );
	$stmt->execute( );
	
	// Redirect to the user frontpage after a short pause (or instantaneously in WebKit browsers...) 
	echo( "<script language=\"javascript\">var t = setTimeout( redirect( ), 4000 ); </script>\n" );
?>
</body>
</html>
