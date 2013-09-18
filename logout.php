<?
	/**
	* logout.php: End the user's session.
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @date 8/2012
	*/
session_start();
session_destroy();
?><html>
<head>
<script type="text/javascript">
        google.load( "jquery", "1" );
        google.load( "jqueryui", "1" );
</script>
<script type="text/javascript">
</script>       
<link rel="stylesheet" href="fcn.css" type="text/css"/>
</head>                 
<body>    
<h1>Fantasy Collecting Network: Logged Out</h1>
<form action="log.php" method="post">
Username: <input type="text" name="username" value=""/> 
<p/>
Password: <input type="password" name="password" value=""/> 
<input type="submit" value="Log In">
</form>
</body>
</html>
