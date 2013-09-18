<?
  	/** 
	* workview.php: Usually opened in a shadowbox as a modal overlay, workview.php is 
	* available from many places in the game.  It displays an enlargement of the image 
	* in question, its description (if a user has created one), and its provenance in 
	* the current game.
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 8/2012
	*
	* @param wid (via GET): the id (work table primary key) of the work in question.
	*/

	if(session_id() == '') {
        	session_start();
	}

	$workid = $_GET['wid'];
	$gameinstance = $_SESSION['gameinstance'];

	$workTitle = "";
	$workArtist = "";
	$workDate = "";
	$imgFile = "";
        
	ob_start( );                
		require 'functions.php';        
		require 'db.php';
	ob_end_clean( );

?>

<html>
<head>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">

        google.load( "jquery", "1" );
        google.load( "jqueryui", "1" );

</script>
<script type="text/javascript">

        $(document).ready( function() {
		// Set up widgets and dismissal button
                $( "#tabs" ).tabs( );
		$( "#dismiss" ).button( );
		$( "#dismiss" ).click( function() {
			window.parent.Shadowbox.close( );
		} );

        } );
</script>
<link rel="stylesheet" type="text/css" href="resources/fcn.css"/>
<link rel="stylesheet" type="text/css" href="resources/jquery-ui.css"/>

<title>Work View</title>
</head>
<body style="background-color:#ffffff;">
<?
	// Some cobwebs here that need to be cleared out, but we're basically trying to see if 
	// the work exists.  
	$stmt = $dbh->prepare( "SELECT COUNT(*) AS c,id as wid,img FROM works WHERE works.id = ?" );
	$stmt->bindValue( 1, $workid );		
	$stmt->execute( );
	while( $row = $stmt->fetch( ) )
	{
		if ( $row['c'] == "0" )
		{
			echo( "<h3>Error: Invalid work or game instance passed to work view page.</h3>\n" );
			echo( "</body></html>\n" );
			exit( );
		}
		$imgFile = $row['img'];	
	}
?>
<button id="dismiss" style="float:left;clear:both;display:block;">Close Window</button>
<br/>
<div id="tabs" style="margin-top:30px;">
<ul>
<li><a href="#image">Image</a></li>
<li><a href="#provenance">Provenance</a></li>
<li><a href="#description">Description</a></li>
</ul>
<div id="provenance">
<h3>Provenance</h3>
<div>
<?
			// Figure out where the work has been.  Create a temporary table to store provenance...
			$provTable = "p_" . rand();

			$provCreate = $dbh->prepare( "CREATE TABLE " . $provTable . " ( date TIMESTAMP, description VARCHAR(256) )" );
			$provCreate->execute( );

			// 10/31/2012: a lot of the joining and stuff could be deprecated because of new convenience
			// functions in functions.php.  But it may be cheaper to do the joins than repeated selects -- not sure.
			// Anyway, we're finding when/where the work has been traded.
                        $collectionQuery = $dbh->prepare( "SELECT origin,destination,gameinstance,trades.date,work_from_origin,work_from_destination,c1.name AS origin_name,c2.name AS destination_name,w1.title AS work_offered,w1.img AS url,w1.id as theId,w2.id AS work_traded_for,accepted FROM trades LEFT OUTER JOIN works AS w1 ON w1.id = trades.work_from_origin LEFT OUTER JOIN works AS w2 ON w2.id = trades.work_from_destination LEFT OUTER JOIN collectors AS c1 ON c1.id = trades.origin LEFT OUTER JOIN collectors AS c2 ON c2.id = trades.destination WHERE (work_from_origin = ?) AND (accepted = 1) AND (gameinstance = ?) ORDER BY trades.date DESC" );
                        $collectionQuery->bindValue( 1, $workid );
			$collectionQuery->bindValue( 2, $gameinstance );
                        $collectionQuery->execute( );

			// Get all auction sales....
			$auctionQuery = $dbh->prepare( "SELECT uid,wid,winner,highbid,end FROM auctions WHERE wid = ? AND winner > 0" );
			$auctionQuery->bindValue( 1, $workid );
			$auctionQuery->execute( );	

			while( $row = $auctionQuery->fetch( ) ) {
				// Put a description of the auction transaction into the temporary table.
				$stmt = $dbh->prepare( "INSERT INTO " . $provTable . " VALUES(?,?)" );
				$stmt->bindParam( 1, $row['end'] );
				$entry = "Purchased at auction by " . getUsername( $row['winner'] ) . " for " . $CURRENCY_SYMBOL . $row['highbid'] . ".";
				$stmt->bindParam( 2, $entry );
				$stmt->execute( );
			}

                        while( $item = $collectionQuery->fetch( ) )
			{
				// Description of the trade transaction into the temporary table.
				$stmt = $dbh->prepare( "INSERT INTO " . $provTable . " VALUES(?,?)" );
				$stmt->bindParam( 1, $item['date'] );
				$entry = "Traded from " . $item['origin_name'] . " to " . $item['destination_name'] . " on " . $item['date'] . " in exchange for " . ( ( $item['work_traded_for'] != "" ) ? "<a rel=\"shadowbox\" href=\"workview.php?wid=" . $item['work_traded_for'] . "\"><img src=\"img.php?img=" . $item['work_traded_for'] . "\" style=\"width:50px;\"/></a>" : "(nothing traded)" ); 
				$stmt->bindParam( 2, $entry );
				$stmt->execute( );
			}

			// Finally, print out the contents of the temporary table, wrought from a million joins
			echo( "<ol>\n" );

			$stmt = $dbh->prepare( "SELECT * FROM " . $provTable . " ORDER BY date ASC" );
			$stmt->execute( );
			while( $row = $stmt->fetch( ) ) {
				echo "<li>" . $row['description'] . "</li>\n";	
			}
			echo( "</ol>\n" );

			// ...and get rid of it.  
			$drop = $dbh->prepare( "DROP TABLE " . $provTable );
			$drop->execute( );
?>
</div>
</div>

<div id="image">
<h3>
<?php
	// Image div.  Print out the tombstone if the user has supplied one.  
	if ( workHasTombstone( $workid ) ) { echo getTombstone( $workid ); }
?>
</h3>
<div>
<center>
<?
	// And include the full-size image.
	echo( "<img src=\"img.php?img=" . $workid . "\" alt=\"[image]\" />\n" );
?>
</center>
</div>
</div>

<div id="description">
<h3>Description</h3>
<p/>
<?php
	// Print out the work description if a user has provided one.
	if ( workHasDescription( $workid ) ) {
		echo getDescription( $workid );
	} else {
		echo "This work doesn't have a description yet.";
	}
?>
</div>

</div>

</div>
</div>
</body>
</html>
