<?php   
	/**
	* createClassified.php: Despite the name (an artifact of when classified ads were part of FCN), this
	* script actually allows users to create an auction listing.  It's a form called from marketplace.php
	* and processed by postClassified.php.  
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 2/2013
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
	global $gameGenie;
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
<script type="text/javascript">
        Shadowbox.init( {
                overlayOpacity: '0.9',
                modal: true
        });
</script> 
<script>
	$(document).ready( function( ) {
		// Set up some buttons.  This listing utility opens in a shadowbox, so the close button
		// needs to hook into the parent's Shadowbox object
		$('#cancel').button( );
		$('#cancel').click( function( ) {
			window.parent.Shadowbox.close( );
		} );
		$('#submit').button( );
		$('#submit').click( function( ) {
			// See if the numerical data (i.e., price/reserve) make logical sense.  We don't
			// care if start price > reserve, since the former effectively overrides the latter
			// in such a case.  (But maybe users care?  Should possibly throw an alert in case
			// user didn't mean to do that.)
			if ( $('#price').val() < 0 || $('#reserve').val() < 0 ) {
				alert( "Start price and reserve price must both be 0 or greater." );
				return false;
			} 
			$('#worksToList').val( $('#worksToList').text( ) );
			$('#cForm').submit( );
		} );

		// Use jQuery UI selectable widget to allow users a visual selection of work to auction.
		$('#selectable').selectable( {
			stop: function( ) { 
			var result = $( "#worksToList").empty();
			$( ".ui-selected", this ).each( function( ) {
				var index = $(this).attr( "id" );
				result.append( index + " " );
			} );
			}
		} );
		$('#selectable').css( { clear:'both', display:'inline-block'} );	
	} );
</script>
</head>
<body style="background-color:#fff">
<form id="cForm" action="postClassified.php" method="post">
<!-- invisible field / container for list of work ids.  In this case, list = 1.  -->
<input type="text" id="worksToList" name="worksToList" style="display:none;"></input>
<h2>Select work to offer:</h2>
<div id="workselector">
<?php
	// Select the user's collection and display its images as a series of list items; that's how
	// jQuery UI selectable likes its elements.  
	$userTable = $uuid . "_" . $gameinstance . "_coll";
	$query = $dbh->prepare( "SELECT work,w1.id AS url FROM " . $userTable . " LEFT OUTER JOIN works AS w1 ON w1.id = " . $userTable . ".work" );
	$query->execute( );

	echo( "<ol id=\"selectable\">\n" );
	
	while( $item = $query->fetch( ) )
	{
		echo( "<li class=\"ui-widget-content\" id=\"" . $item['work'] . "\"><img src=\"img.php?img=" . $item['url'] .  ( $uuid == $gameGenie ? "&mode=thumb" : "" ) . "\" width=\"80\" alt=\"[img]\" valign=\"bottom\" style=\"border:0px;display:inline;float:left;\"/></li>\n" );
	}
		
	echo( "</ol>\n" );
?>
</div>
<h2>Enter a start price:</h2>
<input type="text" id="price"  name="price" style="font:2em;"/>
<h2>Enter a reserve price (optional):</h2>
<input type="text" id="reserve" value="0" name="reserve" style="font:2em;"/>
<h2>Enter an outright sale price (optional):</h2>
<input type="text" id="bin" name="bin" value="0" name="reserve" style="font:2em;"/>
<br/>(Players may purchase the work for this price without going through the bidding process.  As soon as someone places a bid, purchasing at the Sale price is no longer available.)
<h2>
<h2>Select auction duration:</h2>
<select name="duration">
	<option value="1">1 day</option>
	<option value="3" selected>3 days</option>
	<option value="5">5 days</option>
	<option value="7">1 week</option>
</select>
</form>
<button style="float:right;" id="submit">Post Auction</button>
<button style="float:right;" id="cancel">Cancel</button>
</body>
</html>
