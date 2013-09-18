<?
	/**
	* logout.php: End the user's session.
	*
	* TODO: This should really just redirect back to the main login page instead of replicating it.
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 8/2012 
	*/

	session_destroy();

?><html>
<head>
<script type="text/javascript">
        google.load( "jquery", "1" );
        google.load( "jqueryui", "1" );
</script>
<script type="text/javascript">
</script>       
<link rel="stylesheet" href="resources/fcn.css" type="text/css"/>
<link rel="stylesheet" type="text/css" href="resources/jquery-ui.css"/>
</head>                 
<body>    
<h1>Fantasy Collecting: Logged Out</h1>
<form action="../log.php" method="post">
Username: <input type="text" name="username" value=""/> 
<p/>
Password: <input type="password" name="password" value=""/> 
<input type="submit" value="Log In">
</form>
</body>
</html>
