<?
	/**
	* aucval.php: In the live auction system, this script is called by client-side polling
	* every couple seconds to update the high bidder, time of last bid, and bid amount in
	* JSON format.  
	*
	* @param i (via GET): the auction id.  Corresponds with primary key of auctions table.
	* 
	* Called by: marketplace.php (within JavaScript polling function).
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 10/2012
	* 
	*/

	if(session_id() == '') {
        	session_start();
	}

	header('Content-type: application/json');

        ob_start( ); 
		require 'db.php';
		require 'functions.php';        
	ob_end_clean( );

	$requested = $_GET['i'];

	// Set upt he JSON object containing our data.  Sample format: {"amt":120,"u":16,"t":128974918274, "metReserve":0 }
	// amt = bid amount; u = user ID of high bidder; t: epoch timestamp of last bid; metReserve: was reserve met? 1/0 
	$JSON = "{\"amt\":" . getHighBidAmountForAuction( $requested ) . ", \"u\":\"" . getHighBidderForAuction( $requested ) . "\", \"t\":" . getLastBidTimeForAuction( $requested ) . ", \"metReserve\":" . didAuctionMeetReserve( $requested ) . "}";
	echo $JSON;
?>
