<?php                   

	/**
	* collections.php: List all collectors in the game.  Show a marquee image for each player, and when
	* the user mouses over it, switch to a thumbnail grid of other works in the player's collection. 
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 8/2012
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
        logVisit( $uuid, basename( __FILE__ ) );

?>                              
<html>                                  
<head>                                  
<title>Fantasy Collecting: All Collections (<?php echo getUserName( $uuid ); ?>)</title>
<script type="text/javascript" src="https://www.google.com/jsapi"></script><script type="text/javascript">
        google.load( "jquery", "1" );           
        google.load( "jqueryui", "1" );                 
</script>                                       <link rel="stylesheet" type="text/css" href="resources/fcn.css"/>
<script>
	// FIXME: The bug function should really be in the (document).ready() of topBar.php, since jQuery
	// allows multiple ready() functions.  
	$(document).ready( function()  {
                $( "#bug" ).click( function( ) {
                        Shadowbox.open( {
                                content: "bug.php", player: "iframe", height:480, width:640
                        } );
                } );

		// Mouseover/out function for collection cells (marquee images).  Hide/show the 
		// Marquee or Thumbnail divs accordingly; Thumb div is hidden by default.
		$( "div.collCell" ).mouseover( function( ) {
			$(this).find( 'div.collCellMarquee' ).hide( );
			$(this).find( 'div.collCellThumbs' ).show( );
		} );
		
		$( "div.collCell" ).mouseout( function( ) {
			$(this).find( 'div.collCellThumbs' ).hide( );
			$(this).find( 'div.collCellMarquee' ).show( );
		} );
	} );
</script>
</head>
<body>     
<?php include('topBar.php'); 
	// topBar is the main navigation bar.  
?>
<div class="body">
<?php
		// Some of these SQL statements really need to be updated to reflect the fact that "gameinstance"
		// isn't a thing anymore.  In any case, select a list of collections active in this game.  (It's
		// technically possible to be a player and not have a collection; hence our not just assuming that
		// list of players = list of collections.)   
                $sstmt = $dbh->prepare( "SELECT collections.id,owner,gameinstance,points,collectors.name AS cn from collections inner join collectors on collectors.id = owner where gameinstance = ? ORDER BY points DESC" );
                $sstmt->bindParam( 1, $gameinstance );
                $sstmt->execute( );

		// Print a 3 x n grid of collections.
		$ind = 0;	// Index value; modulus 3 to find where to start a new row
		echo( "<div style=\"display:table-row;\">" );
                while( $row = $sstmt->fetch( ) )
                {
			// Ignore the superuser, if she/he has a collection
			if ( $row['owner'] == $FCN_SUPERUSER ) {
				continue;
			}

			// Need to spell the math out (== 0) because Boolean false and 0 aren't equivalent in PHP
			if ( $ind % 3 == 0 )
			{
					echo( "</div><div style=\"display:table-row;\">" );
			}

			// Figure out the name of the user table for this collector.  So much code smell
                        $userTable = $row['owner'] . "_" . $gameinstance . "_coll";

			// Placeholder for the actual cover/marquee piece -- just pick the first one in their user table.
                        $collectionQuery = $dbh->prepare( "SELECT work,w1.id AS iid FROM " . $userTable . " LEFT OUTER JOIN works AS w1 ON w1.id = " . $userTable . ".work LIMIT 1" );
                        $collectionQuery->execute( );
			$userName = $row['cn'];

			// Loop that prints the collection cell divs.  
                        while( $item = $collectionQuery->fetch( ) )
                        {
				// Name of collector (or "You") and a star icon if this collector has Connoisseur status.
                                echo( "<div class=\"collCell\" id=\"" . $row['owner'] . "\"><b>" . ( $row['owner'] == $uuid ? "You" : $row['cn'] ) );
				echo( " " . ( isConnoisseur( $row['owner'] ) ? "<img style=\"padding-top:3px;\" src=\"resources/icons/star_16x16.png\" alt=\"[Connoisseur]\"/>" : "" ) );

				// Current FCGs (money) and a link to userCollection, a larger display of all this user's art.
				echo( " (" . $CURRENCY_SYMBOL . $row['points'] . ")</b><br/><a href=\"userCollection.php?uid=" . $row['owner'] . "&amp;gid=" . $_SESSION['gameinstance'] . "\">" );
			
				// Marquee image for this collection.  Right now, it's just the first row returned from the query
				// above, but it would be nice to allow users to specify a marquee work.  
                                echo( "<div class=\"collCellMarquee\"><img src=\"img.php?img=" . $item['iid'] . "\" width=\"180\" alt=\"[Marquee Image]\" valign=\"top\" style=\"padding:5px;border-style:none;\"/></div>" );
                                echo( "</a>\n" );

				// Now print the (hidden) matrix of thumbnails.  Select a list of works in this user's collection,
				// use img.php to display them as thumbnails, and limit them to 40px wide
				echo( "<div class=\"collCellThumbs\" style=\"display:none;\">" );
				echo( "<a href=\"userCollection.php?uid=" . $row['owner'] . "&amp;gid=" . $_SESSION['gameinstance'] . "\">" );
			                        $subQuery = $dbh->prepare( "SELECT work,w1.id AS iid FROM " . $userTable . " LEFT OUTER JOIN works AS w1 ON w1.id = " . $userTable . ".work" ); 
                        			$subQuery->execute( );	
						while( $subItem = $subQuery->fetch( ) )
						{
							echo( "<img src=\"img.php?mode=thumb&img=" . $subItem['iid'] . "\" width=\"40\" style=\"padding:5px;border-style:none;\"/>" );
						}
				echo( "</a></div>\n" );	
				echo( "</div>\n" );
				$ind++;
                        }
                }
		echo "</div>\n";
?>
</div>
<?php
        include('jewel.php');

?>
</body>
</html>
