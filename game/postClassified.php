<?php   
	/**
	* postClassified.php: Post not a classified but an auction.  Todo: refactor.  Handles input
	* from createClassified.php, allowing users to create auctions.  
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1., 1/2013
	* 
	* @param worksToList (via POST): work id to list in the auction.
	* @param price (via POST): starting bid.
	* @param reserve (via POST): reserve price for this auction (optional)
	* @param duration (via POST): length of the auction in days.
	* @param bin (via POST): buy-it-now price (optional)
	*/

	if(session_id() == '') {
        	session_start();
	}

        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];
	$works = $_POST['worksToList'];
	$price = $_POST['price'];
	$reserve = $_POST['reserve'];
	$endtime = $_POST['duration']; 
	$bin = $_POST['bin'];
        
	ob_start( );                
		require 'db.php';	
		require 'functions.php';
	ob_end_clean( );

        logVisit( $uuid, basename( __FILE__ ) );

?>
<html>
<head>
<script type="text/javascript" src="https://www.google.com/jsapi"></script><script type="text/javascript">
        google.load( "jquery", "1" );
        google.load( "jqueryui", "1" );
</script>
<link rel="stylesheet" type="text/css" href="resources/jquery-ui.css"/>
<link rel="stylesheet" type="text/css" href="resources/fcn.css"/>
<link rel="stylesheet" type="text/css" href="resources/shadowbox.css">
<script type="text/javascript" src="resources/shadowbox.js"></script>
<script type="text/javascript">        Shadowbox.init( {
                overlayOpacity: '0.9',
                modal: true
        });

	$(document).ready( function( ) {
		$("#dismiss").button();
		$("#dismiss").click( function( ) {
			// Other methods of forcing reload don't work, for whatever reason.  This simply reloads
			// the parent window (marketplace.php) so that the user's auction shows up.  
			window.parent.location.href = window.parent.location.href;
                        window.parent.Shadowbox.close();
                } );
	} );
</script> 
</head>
<body style="background-color:#fff">
<?php if ( $works === "" ) {
	echo( "<h2>No work selected!</h2>Please select a work to list." );
	exit( );
}?>

<h2>Listing Posted</h2>
You have successfully posted the auction.  If you want to cancel the auction, use the "Manage My Auctions" tab in the Marketplace.  
<?php
			// Set up the auction end time by relying on MySQL's current timestamp + n days
			$stmt = $dbh->prepare( "SELECT CURRENT_TIMESTAMP() + INTERVAL " . $endtime . " DAY as t" );
			$stmt->execute( );
			while( $row = $stmt->fetch( ) ) {
				$endstamp = $row['t'];
			}
			// Insert auction data into auctions table...
			$stmt = $dbh->prepare( "INSERT INTO auctions(uid,wid,end,initial_bid,reserve,bin) values( ?, ?, ?, ?, ?, ? )" );
			$stmt->bindParam( 1, $uuid );
			$stmt->bindParam( 2, $works );
			$stmt->bindValue( 3, $endstamp );
			$stmt->bindParam( 4, $price );
			$stmt->bindValue( 5, $reserve );
			$stmt->bindValue( 6, $bin );
			$stmt->execute( );
	
			// ...and notify the entire game that a new auction is happening.
			createNotification( -1, $E_CLASSIFIED_LISTING, "<a href=\"marketplace.php?#auctions\">" . getUsername( $uuid ) . " is auctioning " . getTombstoneOrNot( $works, true ) . " (starting bid: " . $CURRENCY_SYMBOL . $price . ").</a>" );


			// FIgure out the auction ID.  I think it's possible to get the primary key of the row
			// you just inserted in MySQL via some built-in function, but I'm not sure that it's 
			// 100% reliable or failsafe in this scenario.  
			$sta = $dbh->prepare( "SELECT id FROM auctions WHERE uid = ? AND end = ?" );
			$sta->bindParam( 1, $uuid );
			$sta->bindValue( 2, $endstamp );
			$sta->execute( );
		
			$aucId = -1;
	
			while( $row = $sta->fetch( ) ) {
				$aucId = $row['id'];
			}

		// Using the auction ID we just selected, create the auction end event for this auction. 
                $z = $dbh->prepare( "CREATE EVENT auctionEnd" . $aucId . " ON SCHEDULE AT '" . $endstamp . "' DO BEGIN UPDATE auctions SET pending=0,winner=-1,highbid=0,end=NOW() WHERE id=? LIMIT 1; END" );
                $z->bindParam( 1, $aucId );
                $z->execute( );


?>
<p/>
<button id="dismiss">Okay</button>
</body>
</html>
