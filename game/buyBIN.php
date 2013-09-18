<?
	/**
	* buyBIN.php: Place a 'buy it now' bid for an auction.  This should probably be folded into
	* bidPlace.php.  Output from this script is displayed in a shadowbox on the marketplace page.
	*
	* @param aid (via POST): Auction ID.  Corresponds to the primary key of the auctions table.
	*   passed from marketplace.php.
	* @param amount (via POST): the amount.  Passed from marketplace.php, but why do we need it?
	*   this value is already in the auctions table, right? 
	* 
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1., 1/2013
	*/

	if(session_id() == '') {
	        session_start();
	}

        $gameinstance = $_SESSION['gameinstance'];
	$uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];

        ob_start( );
		require 'db.php';
		require 'functions.php';        
	ob_end_clean( );

	$auctionId = $_POST['aid'];
	$bidAmt = $_POST['amount'];

        logVisit( $uuid, basename( __FILE__ ) );
?>
<link rel="stylesheet" type="text/css" href="resources/jquery-ui.css"/>
<link rel="stylesheet" type="text/css" href="resources/fcn.css"/>
<link rel="stylesheet" type="text/css" href="resources/shadowbox.css">
<?php

	// If the auction is still active and the player has enough money, call the endAuctionBIN() function 
	// (defined in functions.php), which handles the end-of-auction events.  
	if ( ( isAuctionStillActiveFixedEnd( $auctionId ) ) && ( $bidAmt <= getPoints( $uuid ) ) ) {
		endAuctionBIN( $auctionId, $uuid, $bidAmt, $gameinstance );
		echo( "You have purchased this work for " . $CURRENCY_SYMBOL . $bidAmt . "." );
                        echo( "<p/><button id=\"dismiss\" class=\"ui-widget ui-button ui-state-default ui-corner-all ui-button-text-only\" style=\"width:100px;height:40px;\" onClick=\"window.parent.Shadowbox.close( );\">Okay</button>" );
	// Again, this should be the first condition -- FIXME -- if the auction has ended, 
	} elseif ( !isAuctionStillActiveFixedEnd( $auctionId ) ) {
		echo( "The auction has already ended!" );
                        echo( "<p/><button id=\"dismiss\" class=\"ui-widget ui-button ui-state-default ui-corner-all ui-button-text-only\" style=\"width:100px;height:40px;\" onClick=\"window.parent.Shadowbox.close( );\">Okay</button>" );
	} else {
		// Remaining explanation -- lack of points.
		echo( "You don't have " . $CURRENCY_SYMBOL . $bidAmt . "!" );
                        echo( "<p/><button id=\"dismiss\" class=\"ui-widget ui-button ui-state-default ui-corner-all ui-button-text-only\" style=\"width:100px;height:40px;\" onClick=\"window.parent.Shadowbox.close( );\">Okay</button>" );
	}
?>
