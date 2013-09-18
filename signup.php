<?php
	/**
	* signup.php: Allow users to sign up for game accounts.  It's a simple form that collects the basic data--
	* username, password, email--and passes it to signupProcessor.php.  Validation occurs via some functions in 
	* game/functions.php. 
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @date 11/2012
	*/

	require_once 'game/db.php';

        ob_start( );
                require 'game/functions.php';
        ob_end_clean( );
?>
<html>
<head>
<script type="text/javascript" src="https://www.google.com/jsapi"></script><script type="text/javascript">
        google.load( "jquery", "1" );
        google.load( "jqueryui", "1" );
</script>
<link rel="stylesheet" type="text/css" href="new/fcn.css"/>
<link rel="stylesheet" type="text/css" href="new/jquery-ui.css"/>
<script>
	$(document).ready( function( ) {
		$( "button#submit" ).button( );
		$( "button#submit" ).click( function( ) {
			$("#signupForm").submit( );
			return false;
		} );	
	} ); 
</script>
</head>
<body style="background-color:#fff;font-size:1em;">
<div style="width:50%;margin-left:auto;margin-right:auto;">
<form id="signupForm" action="signupProcessor.php" method="post">
<h1>Create a Collector Account</h1>
Once you create a collector account, you'll be able to log in, view your collection, explore other users' collections,
and buy, sell, or trade works in the marketplace.  Collector accounts can belong to individuals or teams, but they can
have only one email address associated with them.  (This address is used only for verifying the account -- all other
communication happens in-game.) 
<p/>
Fantasy Collecting requires that you use a modern Web browser (Chrome, Opera, Firefox, Safari -- Internet Explorer is not
supported).  It works best with a display of at least 1280 x 800 pixels, which is typical for a 13" laptop.  
<p/>
<center>
<table width="500">
<tr>
<td align="right">Collector (or Team) Name:</td><td><input name="name" size="30" style="font-size:1em;"/></td></tr>
<tr><td align="Right">Password:</td><td><input name="password" type="password" size="30" style="font-size:1em;"/></td></tr>
<tr><td align="right">Confirm password:</td><td><input name="confirm_password" type="password" size="30" style="font-size:1em;"/></td></tr>
<tr><td align="right">Email:</td><td><input name="email" size="30" style="font-size:1em;"/></td></tr>
<tr><td colspan="2">
<input type="checkbox" name="ok_to_use_record" value="1" checked>
I give permission for the record of my game play to be used in future reseach, development, and promotion of Fantasy Collecting.
</table>
</div>
<p/>
<p/>
<p/><center>
<button id="submit">Sign Up</button></center>
</div>
</form>
</div>
</body>
</html>
