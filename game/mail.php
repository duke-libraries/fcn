<?php
	/**
	* mail.php: The in-game message center.  Right now, it's limited to 1:1 communication between
	* players, but it would be nice to expand the functionality to support conversations among
	* an arbitrary nubmer of participants.  
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

        logVisit( $uuid, basename( __FILE__ ) );

?>
<html>
<head>
<title>Fantasy Collecting: Message Center (<?php echo getUserName( $uuid ); ?>)</title>
<script type="text/javascript" src="https://www.google.com/jsapi"></script><script type="text/javascript">
        google.load( "jquery", "1" );
        google.load( "jqueryui", "1" );
</script>
<script type="text/javascript" src="resources/shadowbox.js"></script>
<script type="text/javascript">
        Shadowbox.init( {
                overlayOpacity: '0.9',
                modal: true
        });
</script>
<script type="text/javascript">
	$(document).ready( function( ) { 
                $( "#bug" ).click( function( ) {
                        Shadowbox.open( {
                                content: "bug.php", player: "iframe", height:480, width:640
                        } );
                } );

		$( "div.msghead" ).click( function( ) {
			// The msghead div is a quick preview of the conversation -- it appears in the left column
			// of the message center.  Clicking on it will (1) create a response form for this thread; 
			// (2) call msgThread.php to populate the conversation div with past messages; and (3) set 
			// up button#responseButton to submit the response textarea and refresh the conversation 
			// when clicked.
			var responseForm = "<form id=\"responseForm\"><textarea id=\"" + $(this).attr('name') + "\" class=\"response\" name=\"string\" style=\"width:80%;height:100%;\"></textarea><input name=\"address\" type=\"hidden\" value=\"" + $(this).attr('id') + "\"/><button id=\"responseButton\">Respond</button></form>\n";
			$.ajax( {
				type: "GET",
  				url: "msgThread.php",
  				cache: false,
				data: { uidf: $(this).attr('name') }
			} ).done(function( html ) {
				// Populate #conversation and do some eye candy
  				$("#conversation").html(html);
				$("#conversation").animate({ scrollTop: $('#conversation').height()}, 1000);
				$("#respond").html( responseForm );
				$( "button#responseButton" ).button( );
				$( "button#responseButton" ).css( { "width" : "19%", "float" : "right", "height" : "50%", "vertical-align" : "center" } );
				$( "button#responseButton" ).click( function( e ) {
					var submissionData = $(this).closest('form').serialize( );		
					// The form = response form created above.  
					$.ajax( {
						type: "POST", data: submissionData, url: "/projects/fcn/new/mailProcessor.php", success: function( result ) {
							// Refresh #conversation with the updated thread. 
							$('textarea.response').val('');
							$( "#conversation" ).load( "msgThread.php?uidf=" + $('textarea.response').attr('id') );
							$("#conversation").animate({ scrollTop: $('#conversation').height()}, 1000);
						},
						error: function( err ) { 
							// Something went wrong...	
							alert( "Sorry, but the system encountered an error while sending your message.  Please try again.");	
						}
					} );
					e.preventDefault( );
				} );	
			} );
		} );
	} );
</script>
<link rel="stylesheet" type="text/css" href="resources/fcn.css"/>
<link rel="stylesheet" type="text/css" href="resources/shadowbox.css"/>
<link rel="stylesheet" type="text/css" href="resources/jquery-ui.css"/>
</head>
<body>
<?php include('topBar.php'); ?>
<div class="body">
<div class="inbox" style="float:left;width:85%;">
<?php

	// Get all messages sent to this player...
	$mailQuery = $dbh->prepare( "SELECT * FROM msgs WHERE uidt = ? GROUP BY uidf ORDER BY ts DESC" );
        $mailQuery->bindParam( 1, $uuid );
        $mailQuery->execute( );

	// And print them out in this anonymous div, which has a bunch of style information that should
	// be in resources/fcn.css.  The basic logic here is to populate it with msgHead divs (click event handled
	// above), which contain the sender's name, the time of the last message received, and a preview
	// of the message content (first 60 bytes).    
	$mcount = 0;
	echo "<div style=\"position:fixed;width:200px;left:30;height:100%;border-right:1px solid lightgray;clear:both;overflow:auto;\">\n";
		
	// First, print the compose button
?>	<div style="width:80%;margin-left:auto;margin-right:auto;text-align:center;clear:both;"><b><a href="mailCompose.php" rel="shadowbox;height=480;width=640;"><img src="resources/compose.png"/></a></b></div> <?php
        while( $row = $mailQuery->fetch( ) ) { 
		echo( "<div class=\"msghead\" name=\"" . $row['uidf'] . "\" id=\"" . getUserName( $row['uidf'] ) . "\"><img src=\"mail-icon.png\" style=\"width:20px;vertical-align:middle;padding-bottom:4px;\"/>&nbsp;<b>" . getUsername( "" . $row['uidf'] . "" ) . "</b><br/><span style=\"font-size:small;\">" . $row['ts'] . "</span><br/>" . substr( getMessageContent( $row['mid'] ), 0, 60 ) . "..." . 
	"</div>" );
		$mcount++;
	}

	if ( $mcount == 0 )
		echo( "<p style=\"margin-left:20px;\">You have no messages.</p>" );

	echo "</div>\n";
	echo "<div id=\"conversation\" style=\"position:fixed;left:300;width:40%;height:80%;overflow:hidden;border-bottom:1px solid lightgray;padding:8px;overflow-y:scroll;\">No conversation selected.\n";
	echo "</div>\n";
	echo "<div id=\"respond\" style=\"position:fixed;left:300;bottom:10;width:40%;height:8%;\"></div>\n";
?>
</div>
</div>
<?php
        include('jewel.php');
?>
</body>
</html>
