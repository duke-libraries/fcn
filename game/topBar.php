<?php
	/**
	* topBar.php: Navigation bar that's included at the top of major game states.  
	* Basic navigation paths to other parts of the game, along with an at-a-glance
	* view of the player's current score.
	* 	
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 8/2012
	*/

        if(session_id() == '') {
                session_start();
        }

        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];
	
	ob_start( );
		// These resources are almost certainly already included by the parent
		// script, so use require to avoid unnecessary reload
		require 'functions.php';
		require 'db.php';
	ob_end_clean( );
?>     
<div xmlns:xi="http://www.w3.org/2001/XInclude" id="topBar">
<div class="topNavLeft">
<span class="topNav">
<a href="home.php" class="navHref"><img src="resources/icons/home_32x32.png" style="height:24px;"class="navBarIcon"/></a> | 
<a href="userHome.php" class="navHref"><?php echo( $uname );?>'s Collection<?php
        echo " (" . $CURRENCY_SYMBOL . getPoints( $uuid ) . ")";
?>
</a></span>
<span class="topNav">| <a href="collections.php" class="navHref">All Collections</a> | <a href="marketplace.php" class="navHref">Marketplace</a> |
<a href="mail.php" class="navHref"><img src="mail-icon.png" class="navBarIcon"/></a></span>
</div>

<div class="topNavRight">
<span class="topNav"><a href="#"><img id="bug" src="resources/icons/bug.png" style="height:24px;margin-right:15px;" class="navBarIcon"/></a><a href="logout.php" class="navHref">Sign Out</a></span>
</div>
</div>
