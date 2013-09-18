<?
	/**
	* trader.php: Display the "trade table."  Called from userCollection.php, handled by tradeProcessor.php.
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
		require 'functions.php';        
		require 'db.php';
	ob_end_clean( );
        logVisit( $uuid, basename( __FILE__ ) );

?>
<html>
<head>

</title>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
        google.load( "jquery", "1" );
        google.load( "jqueryui", "1" );
</script>
<script type="text/javascript">

	var windowHeight = $(window).height();
	var halfHeight = ( windowHeight / 2 ) - 75;
	var windowWidth = $(window).width();

	$(document).ready( function( ) {
		// Two selectable elements (jQuery UI): one for the works we're requesting from the
		// other player, and one with which we can select our own works to offer.  These 
		// functions record the value of the selection once the user stops clicking/dragging.
		$('#selectable').selectable( {
			stop: function() {
				var result = $( "#requestedFrom" ).empty();
				$( ".ui-selected", this ).each(function() {
					var index = $(this).attr( "id" ); 
					result.append( index + " " );
				});
			}
		});

		$('#selectable').css({ clear: 'both', display: 'inline-block' });
		$('#selectable2').selectable( {                         
			stop: function() {
                               	var result= $("#offeredTo").empty(); 
				$( ".ui-selected", this ).each(function() {
                                        var index = $(this).attr( "id" ); 
					console.log( index );
                                        result.append( index + " " );
                                });
                        }
		});
		$('#selectable2').css({ clear: 'both', display: 'inline-block' });
		$('#bottomhalf').height( halfHeight );
		$('button#proposeTrade').button( );
		$('button#proposeTrade').click( function() {
			// Set up the propose trade button
			$("#requestedFrom").val( $("#requestedFrom").text() );	
			$("#offeredTo").val( $("#offeredTo").text() );
			console.log( $("#requestedFrom").text() + " requested / " + $("#offeredTo").text( ) );	

			if ( ( $("#requestedFrom" ).text( ).indexOf( "undefined" ) > 0 ) || ( $("#offeredTo").text( ).indexOf( "undefined" ) > 0 ) ) {
				// Workaround for what may be a bug in jQuery UI -- sometimes selectable
				// throws in an "undefined" or two, and I'm not sure why.  
				$(".ui-selected").removeClass("ui-selected"); 
				alert( "Sorry, but the system encountered an error while processing your selection.  Please double-check the requested works and try again." );
				return false;
			} 

			$("#tradeForm").submit();
		});
		$('button#cancelTrade').button( );
                $("#cancelTrade").click( function( ) {
                        window.parent.Shadowbox.close();
                } );
        } );

</script>
<link rel="stylesheet" href="resources/fcn.css" type="text/css"/>
<link rel="stylesheet" type="text/css" href="resources/jquery-ui.css"/>
</head>
<body style="background-color:#fff">
<form id="tradeForm" action="tradeProcessor.php" method="post">
<input type="text" id="offeredTo" name="offeredTo" style="display:none;"></input><input type="text" name="requestedFrom" id="requestedFrom" style="display:none;"></input>
<?
	// Quick sanity checks -- is somebody trying to access this directly?  
	if ( !isset( $_GET['origin'] ) || !isset( $_GET['gameinstance'] ) || !isset( $_GET['destination'] ) )
	{
		echo( "<h2>Fatal Error</h2> I'm sorry, but I don't have enough information to set up a trade for you.  " );
		echo( "This problem might have happened because you're accessing the trading table via a direct link.  " );
		echo( "Please access the trade table via your <a href=\"userHome.php\">user page</a>, or if you haven't yet logged in, <a href=\"../\">do so</a>.\n" );
		echo( "</body></html>\n" );
		exit( );
	}
	// Or is somebody trying to set up a trade for another player?  
	if ( ( $_SESSION['uuid'] != $_GET['origin'] ) || ($_SESSION['gameinstance'] != $_GET['gameinstance'] ) )
	{
		echo( "<h2>Fatal Error</h2> Illegal trade attempt.  Please return to your user page or log in.\n" ); 
		echo( "</body></html>\n" );
		exit( );
	}
	// At this point, we're fairly sure that there's no mischief afoot.
?>

<?
	$_SESSION['last_trade_with'] = $_GET['destination'];
?>
<div id="tophalf" style="height:300px;position:absolute;left:0px;top:0px;display:inline-block;width:100%;border-bottom:1px solid black;padding-left:20px;padding-top:20px;clear:both;">
<?
			// Print out a grid of the destination user's works (this is our first selectable)
                        $userTable = $_GET['destination'] . "_" . $gameinstance . "_coll";
                        $collectionQuery = $dbh->prepare( "SELECT work,w1.id AS url FROM " . $userTable . " LEFT OUTER JOIN works AS w1 ON w1.id = " . $userTable . ".work" );
                        $collectionQuery->execute( );

			echo( "<ol id=\"selectable\">\n" );

                        while( $item = $collectionQuery->fetch( ) )
                        {
				if ( isWorkAvailable( $item['url'] ) == $AVAILABLE ) {
                                	echo( "<li class=\"ui-widget-content\" id=\"" . $item['work'] . "\"><img src=\"img.php?img=" . $item['url'] . "\" width=\"80\" alt=\"[Image]\" valign=\"bottom\" style=\"border:0px;display:inline;\"/></li>\n" );
				}
                        }
			
			echo( "</ol>\n" );
?>

</div>
<div style="position:absolute;top:340px;left:0px;width:100%;height:40px;text-align:center;" id="center"> 
<!-- Center div allows users to include $ and submit the form or cancel. -->
Select multiple works with command-click on a Mac (ctrl-click on a PC). <br/>
<select name="moneyTransfer">
<option selected value="request">Request</option>
<option selected value="offer">Offer</option>
</select>
<?php echo $CURRENCY_SYMBOL;?> <input type="text" name="fcgs" size="2"/> in addition to works
<p/>
<button id="cancelTrade">Cancel</button>
<button id="proposeTrade">Propose Trade</button><br/>
</div>
</form>
<div id="bottomhalf" style="position:absolute;top:450px;left:0px;display:inline-block;width:100%;height:150px;border-top:1px solid black;width:100%;padding-bottom:20px;padding-top:20px;padding-left:20px;">
<?
			// BAsically the same code as above; one of several places that should likely be abstracted
			// into a more generic function.
                        $userTable = $_GET['origin'] . "_" . $gameinstance . "_coll";

                        $collectionQuery = $dbh->prepare( "SELECT work,w1.id AS url FROM " . $userTable . " LEFT OUTER JOIN works AS w1 ON w1.id = " . $userTable . 
".work" );
                        $collectionQuery->execute( );

                        echo( "<ol id=\"selectable2\">\n" );
                        while( $item = $collectionQuery->fetch( ) )
                        {
                                if ( isWorkAvailable( $item['url'] ) == $AVAILABLE ) {
                                	echo( "<li class=\"ui-widget-content\" id=\"" . $item['work'] . "\"><img src=\"img.php?img=" . $item['url'] . "\" width=\"80\" alt=\"[Image]\" valign=\"bottom\" style=\"border:0px;display:inline;\"/></li>\n" );
				}
                        }

                        echo( "</ol>\n" );
?>

</div>
</body>
</html>
