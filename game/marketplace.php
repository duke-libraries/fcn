<?php
	/**
	* marketplace.php: The central hub for trading and auctioning works.  Players can manage
	* all trade/auction activity from here; trades are initiated from userCollection.php.
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 1/2013
	*/

	if(session_id() == '') {
        	session_start();
	}

        $gameinstance = $_SESSION['gameinstance'];        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];

        ob_start( );                
		require 'functions.php';        
		require 'db.php';
	ob_end_clean( );

        logVisit( $uuid, basename( __FILE__ ) );

        $activeAuctionCount = $dbh->prepare( "SELECT id FROM auctions WHERE end > NOW()" );
        $activeAuctionCount->bindValue( 1, time() );
        $activeAuctionCount->execute( );
	$auctions = $activeAuctionCount->rowCount( );
?>
<html>
<head>                                  
<title>Fantasy Collecting: Marketplace (<?php echo getUserName( $uuid ); ?>)</title>
<script type="text/javascript" src="https://www.google.com/jsapi"></script><script type="text/javascript">
        google.load( "jquery", "1" );           
        google.load( "jqueryui", "1" );                 
</script>                                       <link rel="stylesheet" type="text/css" href="resources/fcn.css"/>
<link rel="stylesheet" type="text/css" href="resources/shadowbox.css">
<link rel="stylesheet" type="text/css" href="resources/jquery-ui.css"/>
<script type="text/javascript" src="resources/shadowbox.js"></script>
<script type="text/javascript">
        Shadowbox.init( {
                overlayOpacity: '0.9',
                modal: true
        });

	$(document).ready( function( ) {
		// Set up the trade approval button.  Users click on this widget to approve (or reject) proposed
		// trades; clicks are handled via ajax calls to tradeApproval.php.
                $( ".tradeApproval" ).button( );
                $( ".tradeApproval" ).click( function( ) {
                        var approvalAction = $(this).attr( 'name' );
                        var submissionData = $(this).closest( 'form' ).serialize( ) + '&a=' + approvalAction;
                        $.ajax( {               
                                type: "POST",
                                url: "./tradeApproval.php",
                                data: submissionData , 
                                success: function( result ) {
                                }
                        } );    
                        $(this).closest( 'div' ).fadeOut( 1000 );
                                
                        return false;
                } );
                $( "#bug" ).click( function( ) {
                        Shadowbox.open( {
                                content: "bug.php", player: "iframe", height:480, width:640
                        } );
                } );
		$( "#tabs" ).tabs();

		// The "buy it now" function (available for certain auctions).  Serialize the auction forma nd pass it
		// via ajax to buyBIN.php, which handles the transfer of works and money.   
                $( ".buyBIN" ).click( function( ) {
                        var submissionData = $(this).closest( 'form' ).serialize( );
                        $( "#dialog-confirm" ).dialog({
                                resizable: false,
                                height:140,
                                modal: true,
                                buttons: {
						// Slightly nuts here, but we're trying to display a dialog that indicates
						// what just happened
                                                "Buy outright": function() {
                                                        $.ajax( {
                                                                type: "POST", url: "./buyBIN.php", data: submissionData, success: function( result ) {
                                                                        // Fixme - switch to jqui modal dialog 
                                                                        Shadowbox.open({
                                                                                content: '<div style="padding:5px;vertical-align:top;width:100%;height:100%;background-color:#fff">' + result + '</div>',
                                                                                player: "html",
                                                                                height: 200,
                                                                                width: 300 
                                                                        });
                                                                }
                                                        } );    
                                                        $( this ).dialog( "close" );
                                                },
                                                Cancel: function() {
                                                        $( this ).dialog( "close" );
                                                }
                                }
                        });
                        return false;
                } );


		// The name is an artifact of classified ads (defunct 1/2013), but the button itself
		// places a bid for a given auction.  Works much the same way as the BIN button.  Very
		// much the same way.  In fact, why not fixme by folding all this repeated code into
		// another funtction?  -- before public release
                $( ".buyClassified" ).click( function( ) {
                        var submissionData = $(this).closest( 'form' ).serialize( );
			$( "#dialog-confirm" ).dialog({
				resizable: false,
				height:140,
				modal: true,
				buttons: {
						"Place Bid": function() {
							$.ajax( {
								type: "POST", url: "./bidPlace.php", data: submissionData, success: function( result ) {
									Shadowbox.open({
										content: '<div style="padding:5px;vertical-align:top;width:100%;height:100%;background-color:#fff">' + result + '</div>',
										player: "html",
										height: 200,
										width: 300 
									});
								}
							} );	
							$( this ).dialog( "close" );
						},
						Cancel: function() {
							$( this ).dialog( "close" );
						}
				}
			});
                        return false;
                } );


		$( "#newAuction" ).button( );
	
	} );
</script>
</head>         
<body>          
<?php include('topBar.php'); ?>
<div class="body">
<div id="tabs" style="width:80%;">
	<ul>
	<li><a href="#auctions">Auctions (<?php echo $auctions;?>)</a></li>
	<li><a href="#trades">Trades</a></li>
	<li><a href="#manage">Manage My Auctions</a></li>
	</ul>

	<div id="trades">
        <h2>Trades Pending My Approval:</h2>             
<?php           
	// What's going on here is that we get a list of trades involving this player (as recipient rather
	// than initiator) and give him/her the opportunity to decline or accept them.  
	$stmt = $dbh->prepare( "SELECT origin,destination,fcn_from_origin,fcn_from_destination,gameinstance,date,trades.id as tid,work_from_origin,work_from_destination,c1.name AS origin_name,c2.name AS destination_name,accepted FROM trades LEFT OUTER JOIN collectors AS c1 ON c1.id = trades.origin LEFT OUTER JOIN collectors AS c2 ON c2.id = trades.destination WHERE trades.destination = ? AND accepted = -1 ORDER BY trades.date ASC LIMIT 10" );
        $stmt->bindValue( 1, $uuid );
        $stmt->execute( );
?>
<ul>    
<?php
	$rc = 0;
	// Iterate through the results of the query above, printing out a div with the trade summary,
	// accept button, and decline button.   
        while( $row = $stmt->fetch( ) )
        {      
		++$rc; 
                echo("<div style=\"border-bottom:1px solid black;width:90%;background-color:" . ((( $rc % 2 ) === 0) ? "lightgray" : "white" ) . ";display:inline-block;padding:5px;clear:both;\">" );

		echo("<div style=\"float:left;display:inline;padding:5px;padding-left:15px;\">" . $row['origin_name'] . " offers you<br/>");

		// Because multiple works can be involved in a trade, we need to explode the fields and
		// iterate over the resulting arrays...
		$offers = explode( ' ', $row['work_from_origin'] );
		foreach( $offers as $work ) {
			$subquery = $dbh->prepare( "SELECT id FROM works WHERE id = ?" );
                	$subquery->bindValue( 1, $work );
                	$subquery->execute( );
                	while( $subrow = $subquery->fetch( ) )
                	{
				// ...printing an image and a link to workview.php for each item.
                       		echo( "<a href=\"workview.php?wid=" . $subrow['id'] . "\" rel=\"shadowbox\"><img src=\"img.php?img=" . $subrow['id'] . "\" style=\"width:75px;\"></a>" );     
                	}
		}

		// It's also possible not to offer any works--just cash. 
		if ( $row['work_from_origin'] === '' || $row['work_from_origin'] === ' ' ) {
			echo( "(No works offered)" );
		}
	
		echo("</div><div style=\"display:inline;float:left;border-left:1px solid gray;padding-left:15px;clear:right;padding:5px;\">in exchange for:<br/> " );

		//FIXME: This code is repeated basically 4x with minimal variations.  Need to abstract it
		// into functions.php before release
                $offers = explode( ' ', $row['work_from_destination'] );
                foreach( $offers as $work ) {
                        $subquery = $dbh->prepare( "SELECT id FROM works WHERE id = ?" );
                        $subquery->bindValue( 1, $work );
                        $subquery->execute( );
                        while( $subrow = $subquery->fetch( ) )
                        {
                                echo( "<a href=\"workview.php?wid=" . $subrow['id'] . "\" rel=\"shadowbox\"><img src=\"img.php?img=" . $subrow['id'] . "\" style=\"width:75px;\"></a>" );  
                        }
                }

                if ( $row['work_from_destination'] === '' || $row['work_from_destination'] === ' ' ) {
                        echo( "(No works requested)" );
                }
		echo( "</div>" );

		// See if any cash is involved in this proposal	
		if ( $row['fcn_from_origin'] > 0 ) {
			echo( "<p style=\"display:block;clear:both;margin-left:10px;\">In addition to any works shown above, " . $row['origin_name'] . " offers you " . $CURRENCY_SYMBOL . $row['fcn_from_origin'] . " as part of this trade." );
		} else if ( $row['fcn_from_destination'] > 0 ) {
			echo( "<p/>In addition to any works shown above, " . $row['origin_name'] . " requests " . $CURRENCY_SYMBOL . $row['fcn_from_destination'] . " from you as part of this trade." );
		}

		// And finally print the form with accept/reject buttons.  The trade data are hidden
		// inputs; the form is processed by tradeApproval.php.
		echo( "<p/><form style=\"display:block;clear:both;padding:5px;margin-left:10px;\"><input type=\"hidden\" name=\"originId\" value=\"" . $row['origin'] . "\"/><input type=\"hidden\" name=\"destinationId\" value=\"" . $row['destination'] . "\"/><input type=\"hidden\" name=\"offered\" value=\"" . $row['work_from_origin'] . "\"/><input type=\"hidden\" name=\"traded_for\" value=\"" . $row['work_from_destination'] . "\"/><input type=\"hidden\" name=\"tid\" value=\"" . $row['tid'] . "\"/>" );

		// But first make sure we have enough money to accept the trade :o
		if( $row['fcn_from_destination'] > getPoints( $uuid ) ) {
			echo( "You can't approve this trade until you have at least " . $CURRENCY_SYMBOL . $row['fcn_from_destination'] . ".<p/>" );
		} else {
			echo( "<button class=\"tradeApproval\" name=\"a\">Approve</button>" );
		}
		echo( "<button class=\"tradeApproval\" name=\"r\">Reject</button> with message (optional):<input type=\"text\" class=\"tradeComment\" size=\"50\" name=\"tradeComment\"/></form></div>\n" );
        }
?>              
</ul>
        <hr/>

        <h2>Trades Pending Another Player's Approval:</h2>

<?php           $stmt = $dbh->prepare( "SELECT origin,destination,fcn_from_origin,fcn_from_destination,gameinstance,date,trades.id as tid,work_from_origin,work_from_destination,c1.name AS origin_name,c2.name AS destination_name,accepted FROM trades LEFT OUTER JOIN collectors AS c1 ON c1.id = trades.origin LEFT OUTER JOIN collectors AS c2 ON c2.id = trades.destination WHERE trades.origin = ? AND accepted = -1 ORDER BY trades.date ASC LIMIT 10" );
        $stmt->bindValue( 1, $uuid );
        $stmt->execute( );
?>
<ul>    
<?php
	$rc = 0;
        while( $row = $stmt->fetch( ) )
        {      
		++$rc; 
		// Print a div containing a summary of the trade and the accept/reject buttons.
		// Keep track of row count in $rc and mod 2 to determine whether we style this
		// div differently (gray background alternates with white for better list
		// legibility).  Basically the same functionality as above; this is an area where
		// we really need to fixme by abstracting a lot of repeated functionality into
		// functions.  
                echo("<div style=\"border-bottom:1px solid black;width:90%;background-color:" . ((( $rc % 2 ) === 0) ? "lightgray" : "white" ) . ";display:inline-block;padding:5px;clear:both;\">" );

		echo("<div style=\"float:left;display:inline;padding:5px;padding-left:15px;\">" . $row['origin_name'] . " offers you<br/>");

		$offers = explode( ' ', $row['work_from_origin'] );
		foreach( $offers as $work ) {
			$subquery = $dbh->prepare( "SELECT id FROM works WHERE id = ?" );
                	$subquery->bindValue( 1, $work );
                	$subquery->execute( );
                	while( $subrow = $subquery->fetch( ) )
                	{
                       		echo( "<a href=\"workview.php?wid=" . $subrow['id'] . "\" rel=\"shadowbox\"><img src=\"img.php?img=" . $subrow['id'] . "\" style=\"width:75px;\"></a>" );     
                	}
		}

		if ( $row['work_from_origin'] === '' || $row['work_from_origin'] === ' ' ) {
			echo( "(No works offered)" );
		}
	
		echo("</div><div style=\"display:inline;float:left;border-left:1px solid gray;padding-left:15px;clear:right;padding:5px;\">in exchange for:<br/> " );

                $offers = explode( ' ', $row['work_from_destination'] );
                foreach( $offers as $work ) {
                        $subquery = $dbh->prepare( "SELECT id FROM works WHERE id = ?" );
                        $subquery->bindValue( 1, $work );
                        $subquery->execute( );
                        while( $subrow = $subquery->fetch( ) )
                        {
                                echo( "<a href=\"workview.php?wid=" . $subrow['id'] . "\" rel=\"shadowbox\"><img src=\"img.php?img=" . $subrow['id'] . "\" style=\"width:75px;\"></a>" );  
                        }
                }

                if ( $row['work_from_destination'] === '' || $row['work_from_destination'] === ' ' ) {
                        echo( "(No works requested)" );
                }
		echo( "</div>" );

		if ( $row['fcn_from_origin'] > 0 ) {
			echo( "<p style=\"display:block;clear:both;margin-left:10px;\">In addition to any works shown above, you offered " . $CURRENCY_SYMBOL . $row['fcn_from_origin'] . " as part of this trade." );
		} else if ( $row['fcn_from_destination'] > 0 ) {
			echo( "<p/>In addition to any works shown above, you requested " . $CURRENCY_SYMBOL . $row['fcn_from_destination'] . " as part of this trade." );
		}

		// Now print the cancellation button:
		echo( "<p/><form style=\"display:block;clear:both;padding:5px;margin-left:10px;\"><input type=\"hidden\" name=\"originId\" value=\"" . $row['origin'] . "\"/><input type=\"hidden\" name=\"destinationId\" value=\"" . $row['destination'] . "\"/><input type=\"hidden\" name=\"offered\" value=\"" . $row['work_from_origin'] . "\"/><input type=\"hidden\" name=\"traded_for\" value=\"" . $row['work_from_destination'] . "\"/><input type=\"hidden\" name=\"tid\" value=\"" . $row['tid'] . "\"/><button class=\"tradeApproval\" name=\"tC\">Cancel Trade</button> with message (optional):<input type=\"text\" class=\"tradeComment\" size=\"50\" name=\"tradeComment\"/></form></div>\n" );
        }
?>              
</ul>

	</div>

	<div id="manage"> 
		<!-- Auction management div: create new or cancel existing.  -->
		<span style="font-size:1.5em;">My Auctions</span><p/>
		<!-- Yes, the auction creation form is still called createClassified - one of many places
		     where we need some refactoring... -->
		<a href="createClassified.php" rel="shadowbox;height=600;width=950;"><button id="newListing">Create a New Auction</button></a>
		<button id="deleteListing">Cancel Selected Auction</button>
		<p/>
		<form id="dummy">
		<?php
			// Get a list of auctions owned by this player; print them out alongside a radio button
			// that allows users to select an auction for deletion.  the deleteListing button is managed
			// in (document).ready().
	                $stmt = $dbh->prepare( "SELECT * FROM auctions WHERE UNIX_TIMESTAMP(end) > UNIX_TIMESTAMP(NOW()) AND uid = ?" );
       	         	$stmt->bindParam( 1, $uuid );
	
			$stmt->execute( );
		
			if ( $stmt->rowCount( ) == 0 )
			{
				echo "<div style=\"display:inline-block;clear:both;margin-left:20px;font-size:18pt;\">You have no auctions.</div>\n";
			}
	
                	while( $row = $stmt->fetch( ) )
                	{
				echo( "<div style=\"background-color:lightgray;width:100%;height:150px;padding-bottom:2px;\">\n" );
				echo( "<input type=\"radio\" name=\"radio\" value=\"" . $row['id' ] . "\" style=\"vertical-align:middle;width:40px;position:relative;left:10px;\"><img src=\"img.php?img=" . $row['wid'] . "\" style=\"height:150px;position:relative;left:0px;display:inline-block;vertical-align:middle;\"/> <div style=\"display:inline-block;width:70%;\">Current bid: " . $CURRENCY_SYMBOL . getHighBidAmountForAuction( $row['id'] ) . " (" . getHighBidderForAuction( $row['id'] ) . ")<br/>Reserve: " . $CURRENCY_SYMBOL .  getReserve( $row['id'] ) . "<br/>Ends: " . getAuctionEnd( $row['id'] ) . " </div>" );
				echo( "</div>\n" );

			}

		?>
		</form>
	</div>

	<div id="auctions"> 

	<?php
			// Here's where we list all the active auctions and allow players to bid on them.  
			// Select list of active auctions...
			$stmt = $dbh->prepare( "SELECT * FROM auctions WHERE UNIX_TIMESTAMP(end) > UNIX_TIMESTAMP(NOW()) ORDER BY end ASC" );
			$stmt->execute( );

	                while( $row = $stmt->fetch( ) )
        	        {
				// ..and for each one, display a form with the work image, a link to workview,php, the work tombstone,
				// the amount of time left in the auction, and basic auction data (curent bid, minimum bid if nobody
				// has bid yet, buy-it-now price); includes text input for bid amount and button(s) for placing
				// a bid / buying outright, if applicable.  Buttons are handled in (document).ready() above.
                	        echo( "<form>\n");
                        	echo( "<input type=\"hidden\" name=\"aid\" value=\"" . $row['id'] . "\"/>" );
                        	echo( "<input type=\"hidden\" name=\"seller\" value=\"" . $row['uid'] . "\"/>" );
                        	echo( "<input type=\"hidden\" name=\"work\" value=\"" . $row['wid'] . "\"/> " );
                        	echo( "<div style=\"background-color:lightgray;width:100%;height:200px;padding:8px;\">\n" );
                        	echo( "<div style=\"float:left;display:inline;\"><a rel=\"shadowbox\" href=\"workview.php?wid=" . $row['wid'] . "\"><img src=\"img.php?img=" . $row['wid'] . "\" style=\"height:150px;\"/></a>\n" );
                        	echo( "</div><div style=\"float:left;display:inline;width:80%;padding:10px;\">" );
				echo( "<span style=\"font-size:1.5em;\">" . getTombstoneOrBlank( $row['wid'], true ) . "</span><br/>" );
                        	echo( "Offered by " . getUsername( $row['uid'] ) . " (ends in <b>" . secondsToString((strtotime( $row['end'] ) - strtotime('-0 seconds'))) . "</b>, on " . $row['end'] . ")<p/>Current high bid: " . getHighBidderForAuction( $row['id'] ) );
				echo( " (" . $CURRENCY_SYMBOL . getHighBidAmountForAuction( $row['id'] ) );

				if( getReserve( $row['id'] > 0 ) ) {	
					echo( ", reserve" . ( didAuctionMeetReserve( $row['id'] ) > 0 ? " met" : " not met" ) );
				}
				echo( ")<p/>\n" );
				echo( "<input type=\"text\" name=\"amount\" size=\"5\"/>\n" );
                       		echo( "<button type=\"submit\" style=\"\" class=\"buyClassified\" name=\"" . $row['id' ] . "\" id=\"#buy" . $row['wid'] . "\">Place Bid" );
				if ( getMinimumBidForAuction( $row['id'] ) > getHighBidAmountForAuction( $row['id'] ) ) {
					echo( " (Minimum bid: " . $CURRENCY_SYMBOL . getMinimumBidForAuction( $row['id'] ) . ")" );
				}

				echo( "</button>\n" );
				if ( ( getAuctionBIN( $row['id'] ) > 0 ) && ( getHighBidAmountForAuction( $row['id'] ) <= getMinimumBidForAuction( $row['id'] ) ) ) {
					echo( "<input type=\"hidden\" name=\"amountOutright\" value=\"" . getAuctionBIN( $row['id'] ) . "\"/>\n" );
					echo( "<button class=\"buyBIN\" name=\"" . $row['id'] . "\" id=\"#bin" . $row['wid'] . "\">Buy outright for " . $CURRENCY_SYMBOL . getAuctionBIN($row['id']) . "</button>\n" );
				}

                        	echo( "</div></div><p/>\n" );
                        	echo( "</form>\n" );
                	}


	?><p/>
	</div>
</div>
</div>
<!-- Hidden divs used for jQuery UI modal dialogs -->
<div id="dialog-confirm" title="Place bid?">
	<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span></p>
</div>
<div id="bidmessage" title="Bid Result">
        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span></p>
</div>
</body>
<?php
        include('jewel.php');
?>
</html>
