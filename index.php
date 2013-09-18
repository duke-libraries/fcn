<?
	/**
	* index.php: Display a login form.
	*
	* @author William Shaw <william.shaw@duke.edu>
	* @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @date 8/2012
	*/

?>
<html>
<head>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
        google.load( "jquery", "1" );
        google.load( "jqueryui", "1" );
</script>
<script type="text/javascript" src="shadowbox.js"></script>
<script type="text/javascript">
        Shadowbox.init( {
                overlayOpacity: '0.9',
                modal: true
        });

        $(document).ready( function() {
		$('input#sub').button();
        });
</script>       
<link rel="stylesheet" href="new/fcn.css" type="text/css"/>
<link rel="stylesheet" type="text/css" href="new/jquery-ui.css"/>
</script>
</head>                 
<body>    
<?php

// Check the user's browser.  Internet Explorer has a number of problems rendering the UI, even
// with jQuery, so we didn't support it during development.  TODO -- add support at some point.

$u_agent = $_SERVER['HTTP_USER_AGENT'];


if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
				echo ("<h1>Unsupported Browser</h1>Sorry, Fantasy Collecting Network does not support Internet Explorer yet." );
				echo ( "Please use Firefox, Chrome, Safari, Opera, or another standards-compliant browser.");
				exit;
}
?>
<div style="width:600px;margin-left:auto;margin-right:auto;font-size:1.5em;">

<!-- A simple login form.  The form is handled by login.php. Allow users to sign up if they haven't 
     already; we open the signup form using shadowbox.js.  -->

<h1>Fantasy Collecting Network</h1>
<form action="login.php" method="post">
Username: <input type="text" name="username" value="" style="font-size:1em;"/> 
<p/>
Password: <input type="password" name="password" value="" style="font-size:1em;"/> 
<p/>
<input id="sub" type="submit" value="Log In" style="float:right;clear:both;">
<p/>
Don't have a username?  <a style="font-color:black;" href="signup.php" rel="shadowbox;width:640px;height:480px">Sign up.</a>
</form>
</div>
</body>
</html>
