<?
	/**
	* index.php: The large mess that is the game supervisor page.  This was developed in a very ad hoc way
	* and needs a complete overhaul before any kind of public release.  It works, but it isn't pretty.
	* In any case, the functionality is that the game admin can control most aspects of gameplay from here;
	* communicate with players; promote/demote users; award FCGs for in-game behavior; upload new artworks;
	* etc.
	*
	*/

        ob_start( );                
		require '../functions.php';        
		require '../db.php';
	ob_end_clean( );

	global $gameGenie;

	$workCountQuery = $dbh->prepare( "SELECT COUNT(id) AS count FROM works" );
	$workCountQuery->execute( );
	$workCountFetch = $workCountQuery->fetch( );
	$workCount = $workCountFetch['count'];	

?>
<html>
<head>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">

        google.load( "jquery", "1" );
        google.load( "jqueryui", "1" );

</script>

<style type="text/css">@import url(resources/plupload/js/jquery.ui.plupload/css/jquery.ui.plupload.css);</style>
<script type="text/javascript" src="resources//plupload/js/plupload.full.js"></script>
<script type="text/javascript" src="resources/plupload/js/jquery.ui.plupload/jquery.ui.plupload.js"></script>

<script type="text/javascript">

        $(document).ready( function() {
		$( "#datepicker" ).datepicker( { dateFormat: "yy-mm-dd", minDate: 0 } );
	
		$( ".grayout" ).fadeTo( 500, 0.2 );
		
                $( "#supervisor" ).tabs();

		$( "#nextLot" ).button( );
		$( "#nextLot" ).click( function( ) {
			$( 'form#endAuc' ).submit( );
		} );
		$( ".aucSubmit" ).button( );
		$( ".aucSubmit" ).click( function( ) {
				$('form#auctions').submit( );			
		} );

		$(".challengeApprover").button( );
                $( ".challengeApprover" ).click( function( ) {
			var bn = $(this).attr('format');
			var action = $(this).attr( 'formact' );
                        var submissionData = $('form#' + bn ).serialize( );
			submissionData += '&action=' + action;
			console.log( submissionData );
                        $.ajax( {
                                type: "GET",
                                url: "http://humanities-dev-01.lib.duke.edu/projects/fcn/new/supervisor/ca.php",
                                data: submissionData,
                                success: function( result ) {
					console.log( submissionData );
                                }
                        } );
			// Fade this row once we've approved or rejected its contents
			$(this).closest( 'tr' ).fadeOut( 500 );
			return false;
                } );

		$( ".promotionButton" ).button();
		$( ".promotionButton" ).click( function( ) {
			var submissionData = $('form#promotionForm' ).serialize( );
			$.ajax( {
				type: "GET",
				url: "http://humanities-dev-01.lib.duke.edu/projects/fcn/new/supervisor/promote.php",
				data: submissionData,
				success: function( result ) {
					 $("#dialog-confirm").html( result );
                                        $("#dialog-confirm").dialog({
                                                resizable: false, height: 200, width: 400, modal: true, 
                                                buttons: { 
                                                        Okay: function( ) {
								$("#promotionForm").find("textarea").val( '' );
                                                                $( this ).dialog( "close" );
                                                        }
                                                }
                                        });

				}
			} );
			return false;
		} );

                $( ".awardButton" ).button();
                $( ".awardButton" ).click( function( ) {
                        var submissionData = $('form#awardForm' ).serialize( );
                        $.ajax( {
                                type: "GET",
                                url: "http://humanities-dev-01.lib.duke.edu/projects/fcn/new/supervisor/award.php",
                                data: submissionData,
                                success: function( result ) {
                                         $("#dialog-confirm").html( result );
                                        $("#dialog-confirm").dialog({
                                                resizable: false, height: 200, width: 400, modal: true, 
                                                buttons: { 
                                                        Okay: function( ) {
                                                                $("#awardForm").find("textarea").val( '' );
                                                                $("#awardForm").find("input").val('');
                                                                $( this ).dialog( "close" );
                                                        }
                                                }
                                        });

                                }
                        } );
                        return false;
                } );

	$("#uploader").plupload({
		// General settings
		runtimes : 'gears,html5',
		url : 'uploader-new.php',
		max_file_size : '10mb',
		chunk_size : '1mb',
		unique_names : true,

		// Specify what files to browse for
		filters : [
			{title : "Image files", extensions : "jpg,gif,png"}
		]
	});

	// Client side form validation
	$('#uploadform').submit(function(e) {
        var uploader = $('#uploader').plupload('getUploader');

        // Files in queue upload them first
        if (uploader.files.length > 0) {
            // When all files are uploaded submit form
            uploader.bind('StateChanged', function() {
                if (uploader.files.length === (uploader.total.uploaded + uploader.total.failed)) {
                    $('form')[0].submit();
                }
            });
                
            uploader.start();
        } else
            alert('You must at least upload one file.');

        return false;
    });

        });
</script>
<link rel="stylesheet" type="text/css" href="../fcn.css"/>
<link rel="stylesheet" type="text/css" href="../jquery-ui.css"/>
<title>
FCN Supervisor Page
</title>
</head>
<style>
td { border:1px dotted lightgray; }
td.header { font: 1.1em; font-weight:bold; background:lightgray;  }
</style>
<body>
<h1>FCN Supervisor Page</h1>
<p/>
<div id="supervisor" name="supervisor" style="width:85%">
<ul>
<li><a href="#viewCollectors">Game Overview</a></li>
<li><a href="#manageArt">Manage Art</a></li>
<li><a href="#gameAdmin">Hazards</a></li>
<li><a href="#challenges">Challenges</a></li>
<li><a href="#awards">Awards</a></li>
<li><a href="#auctions">Auctions</a></li>
<li><a href="#reports">Reports</a></li>
<li><a href="#bugs">Bugs &amp; Suggestions</a></li>
<li><a href="#communicate">Communications</a></li>
</ul>
<div id="viewCollectors">
<h2>Game Overview</h2>
<table>
<tr>
<td valign="top">
<b>Leaderboard (FCGs)</b>
<?
	$stmt = $dbh->prepare( "SELECT * FROM collectors WHERE id > 0 and id != ? ORDER BY points DESC" );
	$stmt->bindParam( 0, $gameGenie );
	$stmt->execute( );

	echo "<ol>\n";
	while ( $row = $stmt->fetch( ) )
	{
		echo "<li>";
		echo $row['name'] . ": ";
		echo $row['points'];
		echo "<br/>";
		echo "</li>";
	} 
	echo "</ol>\n";
?>
</td>
<td valign="top">
<b>Login Count</b>
<?
	$stmt = $dbh->prepare( "select uid, collectors.name, collectors.email as email, count(*) as c from logins left join collectors on collectors.id=logins.uid where uid != ? group by uid order by c desc" );
	$stmt->bindParam( 0, $gameGenie );
	$stmt->execute( );

	echo "<ol>\n";
	
	while( $row = $stmt->fetch( ) ) {
		echo( "<li>" );
		echo( $row['name'] . " (" . $row['email'] . " - " . $row['c'] . ")</li>" );
	}	
	echo "</ol>\n";
?>
</td><td valign="top">
<b>Most Tombstones Accepted</b>
<?php
	$stmt = $dbh->prepare( "select uid_creator,collectors.name,count(*) as c from tombstones left join collectors on collectors.id = tombstones.uid_creator where uid_creator != ? group by uid_creator order by c desc" );
	$stmt->bindParam( 0, $gameGenie );
	$stmt->execute( );

	echo( "<ol>\n" );
	while( $row = $stmt->fetch( ) ) {
		echo( "<li>" );
		echo( $row['name'] . " (" . $row['c'] . ")</li>" );
	}
	echo( "</ol>\n" );
?>
</td>
<td valign="top">
<b>Most Descriptions Accepted</b>
<?php
        $stmt = $dbh->prepare( "select uid,collectors.name,count(*) as c from work_descriptions left join collectors on collectors.id = work_descriptions.uid where uid != ? group by uid order by c desc" );
	$stmt->bindParam( 0, $gameGenie );
        $stmt->execute( );

        echo( "<ol>\n" );
        while( $row = $stmt->fetch( ) ) {
                echo( "<li>" );
                echo( $row['name'] . " (" . $row['c'] . ")</li>" );
        }
        echo( "</ol>\n" );
?>
</td>

</tr></table>
<h2>Recent Gameplay Events</h2>
<iframe src="../feed.php" style="width:100%;height:800px;border:1px solid gray;"></iframe>
</div>
<div id="manageArt">
<div><h3>Upload New Artwork
</h3>

<form method="post" id="uploadform" action="uploader-new.php">
	<div id="uploader">
		<p>You need to be using an HTML5-compliant browser (Chrome, Safari) for this feature.</p>
	</div>
</form>

</div>
<p/>

<b>Currently Active Games</b><p/><ul>
<?
        $stmt = $dbh->prepare("SELECT * FROM games WHERE UNIX_TIMESTAMP( ended ) = 0" );
        $stmt->execute();
        while( $row = $stmt->fetch( ) )
        {
                $participants = "";
                $gameParticipants = 0;
                echo( "<li><a href=\"../gameview.php?id=\"" . $row['id'] . "\"><b>" . $row['name'] . "</b></a> supervised by " . $row['supervisor'] . "; started on " . $row['started'] . " <br/>" );
                echo( "Participants: " );
                $sstmt = $dbh->prepare( "SELECT collections.id,owner,gameinstance,collectors.name from collections inner join collectors on collectors.id = owner where gameinstance = ?" );
                $sstmt->bindParam( 1, $row['id'] );
                $sstmt->execute( );
                while( $subrow = $sstmt->fetch( ) )
                {
                        $participants .= $subrow[ 'name' ] . ", ";
                        ++$gameParticipants;
                }
                echo ( substr($participants,0,-2)  );

                if ( $row['dist'] == 0 )
                {
                        $maxCollectionSize = floor( $workCount / $gameParticipants );
                        echo "<form method=\"get\" action=\"distributor.php\">\n";
                        echo "<input type=\"hidden\" name=\"gameid\" value=\"" . $row['id'] . "\"/>\n";
                        echo "<p/>Initial collection size: <input type=\"text\" size=\"2\" name=\"colsize\"/>\n (cannot exceed " . $maxCollectionSize . ")\n";
                        echo "<input type=\"hidden\" name=\"maxsize\" value=\"" . $maxCollectionSize . "\"/>\n";
                        echo "<input type=\"submit\" value=\"Distribute initial collections\" />\n";
                        echo "</form>\n";
                }
        }

        echo "</li>\n";

?>



</div>


<div id="gameAdmin">
<h3>
<a href="#">Hazards</a>
</h3>
You may do two things here.  One, you may enter a random hazard that might affect anyone and any piece of art, and you
may specify a time period within which it should happen, along with the number of times it can happen.  
<p/>
Two, you may instantly strike down some poor collector or his artwork.  
<p/>
In both cases, you may specify
penalties including loss of work value, freezes on trading or selling, loss of points, a user's being barred from auction, 
and so on. 
<p/>
But this functionality isn't active for the trial game.
</div>

<div id="challenges">
<h3>Challenges Pending Your Approval</h3>
<h4>Tombstones</h4>
<!-- Hey, let's have some tables -->
<table style="border:1px solid black;width:90%;">
<tr><td class="header">Collector</td><td class="header">Work</td><td class="header">Tombstone</td><td class="header">Approve?</td></tr>
	<?php
		$stmt = $dbh->prepare( "SELECT * FROM tombstones WHERE approved = 2" );
		$stmt->execute( );

		while ( $row = $stmt->fetch( ) ) {
			echo( "<form id=\"" . $row['id'] . "-ts\"><input type=\"hidden\" name=\"mode\" value=\"ts\"/><input type=\"hidden\" name=\"tombstoneId\" value=\"" . $row['id'] . "\"/><input type=\"hidden\" name=\"player\" value=\"" . $row['uid_creator'] . "\"/><input type=\"hidden\" name=\"work\" value=\"" . $row['wid'] . "\"/><tr>\n" );
			echo( "<td style=\"vertical-align:top;\">" . getUsername( $row['uid_creator'] ) . "</td><td style=\"vertical-align:top;\"><a href=\"javascript:alert('" . $row['wid'] . "');\"><img src=\"../img.php?img=" . $row['wid'] . "\&mode=thumb\" style=\"width:75px;\"/></a></td>" );
			echo( "<td style=\"vertical-align:top;\">" . getTombstone( $row['wid'], false ) . "</td><td>" );
			echo( "<button format=\"" . $row['id'] ."-ts\"  class=\"challengeApprover\" formact=\"approve\">Yes</button>" );
			echo( "<button format=\"" . $row['id'] . "-ts\" class=\"challengeApprover\" formact=\"reject\"> No </button></td></tr></form>\n" );
		}


	?>
	</table>

<h4>Descriptions</h4>
<table style="border:1px solid black;width:90%;">
<tr><td class="header">Collector</td><td class="header">Work</td><td class="header">Description</td><td style="min-width:130px;" class="header">Approve?</td></tr>
        <?php
                $stmt = $dbh->prepare( "SELECT * FROM work_descriptions WHERE approved = 2" );
                $stmt->execute( );

                while ( $row = $stmt->fetch( ) ) {
                        echo( "<form id=\"" . $row['id'] . "-d\"><input type=\"hidden\" name=\"mode\" value=\"d\"/><input type=\"hidden\" name=\"tombstoneId\" value=\"" . $row['id'] . "\"/><input type=\"hidden\" name=\"player\" value=\"" . $row['uid'] . "\"/><input type=\"hidden\" name=\"work\" value=\"" . $row['work'] . "\"/>\n" );
                        echo( "<tr><td style=\"vertical-align:top;\">" . getUsername( $row['uid'] ) . "</td><td><img src=\"../img.php?img=" . $row['work'] . "\" style=\"width:75px;\"/></td>" );
                        echo( "<td style=\"vertical-align:top;\">" . getDescription( $row['work'] ) . "</td><td>" );
			echo( "<button format=\"" . $row['id'] . "-d\" class=\"challengeApprover\" formact=\"approve\">Yes</button>" );
			echo( "<button format=\"" . $row['id'] . "-d\" class=\"challengeApprover\" formact=\"reject\">No</button></td></tr></form>\n" );

                }
        ?>
        </table>

</div>

<div id="awards">
<h1>Awards</h1>
<h3>Promote or Demote a Player</h3>
Here, you may promote a player to Connoisseur status (or strip them of that status, as the case may be).  Enter a short message to appear
in the player's notification feed.
<?
	$stmt = $dbh->prepare( "SELECT * FROM collectors WHERE id > -1 ORDER BY name ASC" );
	$stmt->execute( );

	echo( "<form id=\"promotionForm\">" );
        echo "<select name=\"collector\">\n";
        while( $row = $stmt->fetch( ) )
        {
                echo( "<option value=\"" . $row['id'] . "\">" . $row['name'] . ( isConnoisseur( $row['id'] ) ? " (Connoisseur)" : "" ) . "</option>\n" );
        }
        echo "</select>\n";
        echo "<br/><textarea style=\"width:400px;height:150px;\" name=\"desc\"></textarea>\n";
        echo "</form>";
        echo "<button class=\"promotionButton\">Change Player's Status</button>\n";
?>
	<p/>
<h3>Arbitrary &#8750;</h3>
Select a player, specify an amount of &#8750; to grant or subtract, and enter a message.  Your message text will appear in the collector's notification feed. (Example: "You have been rewarded for your excellent work descriptions!" "You lose &#8750;50 for slacking off.")<p/>
<?
        $stmt = $dbh->prepare( "SELECT * FROM collectors WHERE id > -1 ORDER BY name ASC" );
        $stmt->execute();

	echo "<form id=\"awardForm\">";
	echo "<select name=\"collector\">\n";
        while( $row = $stmt->fetch( ) )
        {
                echo( "<option value=\"" . $row['id'] . "\">" . $row['name'] . "</option>\n" );
        }
	echo( "<option value=\"-1\">All players</option>\n" );
	echo "</select>\n";
	echo $CURRENCY_SYMBOL . "<input type=\"text\" name=\"points\" size=\"6\"/>\n";
		
	echo "<br/><textarea style=\"width:400px;height:150px;\" name=\"desc\"></textarea>\n";
	echo "</form>";
	echo "<button class=\"awardButton\">Exercise Benevolence/Malice</button>\n";

?>

</div>

<div id="auctions">
<h2>Current Auction:</h2>
<p/>
<form method="post" action="endAuction.php" id="endAuc">
<?php
	$stmt = $dbh->prepare( "SELECT * FROM auctions WHERE pending=1" );
	$stmt->execute( );

	while( $row = $stmt->fetch( ) ) {
		echo "The current auction lot is:<p/><img src=\"../img.php?img=" . $row['wid'] . "\" style=\"width:200px;\"><p/>Offered by " . getUsername( $row['uid'] ) . ".";
		echo "High bid: " . $CURRENCY_SYMBOL . getHighBidAmountForAuction( $row['id' ] ) . " (" . getHighBidderForAuction( $row['id' ] ) . ")"; 	}

		echo "<input type=\"hidden\" name=\"aid\" value=\"0\"/><button id=\"nextLot\">Next Lot</button>\n";

?>
</form>
<p/>
<h2>Upcoming Auction</h2>
<?php
	if ( isAuctionPending( ) ) {
		echo( "An auction will be held at " . pendingAuctionDate( ) . "." );
	} else {
		echo( "There is no upcoming auction, but you can add one below." );
	}
?>
<h2>Schedule an Auction</h2>
After you schedule the auction, it will appear in the list of upcoming auctions above.  For now, it's possible to
schedule only one future auction at a time.<p/>
<form id="auctions" method="post" action="newAuction.php">
Auction start date: <input type="text" name="date" id="datepicker"/><br/>
Auction start time: <input type="text" name="time"/> <b>Note:</b> please use 24-hour time format (13:00, 14:00...).  The default time zone is US Eastern.<br/>
Maximum works allowed (optional): <input type="text" name="maxworks" size="1"/> 
<p/>
<button name="aucSubmit" class="aucSubmit">Schedule Auction</button>
</form>
<?php

?>
</div>
<div id="communicate">
<h3>Communicate with Players</h3>
<form method="post" action="comm.php">
You can use this form to send a message to all players.  It will appear in the events feed.
<p/>
<textarea name="msg" style="width:500px;height:250px;">
</textarea><p/>
<input type="submit" value="Send Message"/>
</form>
</div>
<div id="bugs">
<h3>Bug Reports and Suggestions</h3>
<?php

	$stmt = $dbh->prepare( "SELECT * FROM bug_reports" );
	$stmt->execute( );
	echo ("<ul>\n");
	while( $row = $stmt->fetch( ) ) {
		echo( "<li><b>" . getUsername( $row['uid'] ) . " says:</b> " . stripslashes( $row['text'] ) . " (at " . $row['ts'] . ")" );
	}
	echo ( "</ul>\n" );
?>
</div>

<div id="reports">
<h3>
<a href="#">Reports</a>
</h3>
<form method="post" action="dataWrangler.php">
Download <select name="reportType">
<option value="1" selected>all trades</option>
<option value="3">accepted trades</option>
<option value="3">rejected trades</option>
<option value="2">individual collections</option>
</select>
 for game instance 
<select name="GameInstance">
<option value="2">2</option>
</select>
in this format: 
<select name="dataFormat">
<option value="1">csv</option>
<option value="2">xml</option>
<option value="3">sql dump</option>
<option value="4">tab delim</option>
</select>
<input type="submit" value="Download"/>
</form>
<p/>
<b>Recent Trades</b><p/>
<table style="border:1px solid black;width:90%;">
<tr><td class="header">Collector</td><td class="header">traded...</td><td class="header">...to...</td><td class="header">for:</td><td class="header">Time of trade</td><td class="header">Status</td></tr>
<?
	$stmt = $dbh->prepare( "SELECT origin,destination,gameinstance,trades.date,work_from_origin,work_from_destination,c1.name AS origin_name,c2.name AS destination_name,w1.title AS work_offered,w2.title AS work_traded_for,accepted FROM trades LEFT OUTER JOIN works AS w1 ON w1.id = trades.work_from_origin LEFT OUTER JOIN works AS w2 ON w2.id = trades.work_from_destination LEFT OUTER JOIN collectors AS c1 ON c1.id = trades.origin LEFT OUTER JOIN collectors AS c2 ON c2.id = trades.destination ORDER BY trades.date DESC LIMIT 200" );
	$stmt->execute( );

	while( $row = $stmt->fetch( ) )
	{
		echo("<tr><td>" . $row['origin_name'] . "</td><td><img src=\"../img.php?img=" . $row['work_from_origin'] . "\" style=\"width:45px;\"/></td><td>" . $row['destination_name'] . "</td><td>" . ( $row['work_from_destination'] == -1 ? "(nothing)" : "<img src=\"../img.php?img=" . $row['work_from_destination' ] . "\" style=\"width:45px;\"/>" ) . "</td>");
		echo("<td>" . $row['date'] . "</td><td>" . ( $row['accepted'] == 1 ? "accepted" : ( $row['accepted'] == -1 ? "pending" : ( $row['accepted'] == 2 ? "cancelled" : "rejected" ) ) ) . "</td></tr>\n" );
	}

?>
</table>
</div>
</div>
</div>

<div id="dialog-confirm" title="Award">
	<p><div id="dialogtext" class="ui-icon ui-icon alert" style="float:left; margin:0 7px 20px 0;"> </div></p>
</div>
</body>
</html>

