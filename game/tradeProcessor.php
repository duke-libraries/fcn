<?

	/**
	* tradeProcessor.php: Form handler for trader.php.  Finalizes trade proposals.  
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 8/2012
	*
	* @param offeredTo (via POST): space-delimited list of work ids we're offering.
	* @param fcgs (via POST): amount of money piled on top of trade request.
	* @param moneyTransfer (via POST): direction that the money is being transferred (offer == you're offering this
	* 	much; request == you're requesting that the trade partner supply it)
	* @param requests (via POST): space-delimited list of work ids we're requesting in return.
	*/

	if(session_id() == '') {
        	session_start();
	}

        $gameinstance = $_SESSION['gameinstance'];
        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];
	$offers = $_POST['offeredTo'];
	$fcgs = $_POST['fcgs'];
	$direction = $_POST['moneyTransfer'];
	$requests = $_POST['requestedFrom'];

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
<link rel="stylesheet" href="resources/fcn.css" type="text/css"/>
<link rel="stylesheet" type="text/css" href="resources/jquery-ui.css"/>
<script type="text/javascript">
	$(document).ready( function( ) {
		// Set upt he dimsiss button
		$("#goHome").button( );
		$("#goHome").click( function( ) {
			Shadowbox.close();
		} );
	} );
	var Shadowbox = window.parent.Shadowbox;
</script>
<title>Confirm Trade Request</title>
</head>
<body style="background-color:#fff">
<?

	// First, make sure we can actually offer this much money as part of a trade...
	if ( ( $fcgs > 0 ) && ( $fcgs > getPoints( $uuid ) ) && ( $direction === 'offer' ) ) {
		echo "<h1>Error</h1>You can't offer more " . $CURRENCY_SYMBOL . " than you currently have (" . $CURRENCY_SYMBOL . getPoints( $uuid ) . ").";
		echo ("<p/><button id=\"goHome\">Okay</button>\n" );
		exit;
	}

	// If that checks out, then record the proposed trade in the database.  
	$query = $dbh->prepare( "INSERT INTO trades( origin, destination, gameinstance, work_from_origin, work_from_destination, accepted, fcn_from_origin, fcn_from_destination ) VALUES( ?, ?, ?, ?, ?, ?, ?, ? )" );
	$query->bindValue( 1, $uuid );
	$query->bindValue( 2, $_SESSION['last_trade_with'] );	// Why is this a session var again?
	$query->bindValue( 3, $gameinstance );
	$query->bindValue( 4, $offers );
	$query->bindValue( 5, $requests );
	$query->bindValue( 6, '-1' );
	if ( $direction === 'request' ) {
		$query->bindValue( 7, '0' );
		$query->bindValue( 8, $fcgs );
	} else {
		$query->bindValue( 7, $fcgs );
		$query->bindValue( 8, '0' );
	}
	$query->execute( );

	$tradeId = $dbh->lastInsertId( );
?>
<?

	// Show a summary of the proposed trade and notify the trading partner
	trim( $offers );
	trim( $requests );

	$query = $dbh->prepare( "SELECT name FROM collectors WHERE id = ?" );
	$query->bindValue( 1, $_SESSION['last_trade_with']  );
	$query->execute( );

	$tradePartner = "";

	while( $row = $query->fetch( ) )
	{
		$tradePartner = $row[ 'name' ];	
	}

	echo( "<h1>Trade proposed with " . $tradePartner . "</h1>" );

	$offeredWorkIds = explode( ' ', $offers );
	$requestedWorkIds = explode( ' ', $requests );

	echo( "<div><div style=\"width:48%;float:left;border-right:1px solid black;font-size:1.25em;\">You offered:<p/>\n" );

	$headline = getUsername( $uuid ) . " proposed a trade with " . $tradePartner;
	$tradeDesc = "<div style=\"display:inline;padding-left:50px;float:left;padding-right:5px;padding-top:5px;padding-bottom:5px;\">" . getUsername( $uuid ) . " offered ";

	if ( $fcgs > 0 && $direction === 'offer' ) {
		$tradeDesc .= $CURRENCY_SYMBOL . $fcgs . " and";
	}

	$tradeDesc .= ": <br/>";

	foreach ( $offeredWorkIds as $work )
	{
		$query = $dbh->prepare( "SELECT id FROM works WHERE id = ?" );
		$query->bindValue( 1, $work );
		$query->execute( );
		while( $row = $query->fetch( ) )
		{
			$tradeDesc .= "<a rel=\"shadowbox;height:80%;width:80%;\" href=\"workview.php?wid=" . $row['id'] . "&gid=" . $gameinstance . "\"><img src=\"img.php?img=" . $row['id'] . "\" style=\"width:200px;padding-right:5px;vertical-align:top;\"></a>";	
			echo( "<div style=\"float:left;padding:5px;\"><img src=\"img.php?img=" . $row['id'] . "\" style=\"width:200px;\"></div>" );	
		} 	
	}

	echo( "</div><div style=\"width:50%;float:right;font-size:1.25em;\">You requested:<p/>\n" );

	$tradeDesc .= "</div><div style=\"display:inline;float:left;border-left:1px solid lightgray;padding:5px;padding-left:15px;\">...in exchange for";
	if ( $fcgs > 0 && $direction === 'request' ) { 
                $tradeDesc .= $CURRENCY_SYMBOL . $fcgs . " and";
        } 

	$tradeDesc .= ":<br/>";
	
        foreach ( $requestedWorkIds as $work )
        {
                $query = $dbh->prepare( "SELECT id FROM works WHERE id = ?" );    
                $query->bindValue( 1, $work );
                $query->execute( );
                while( $row = $query->fetch( ) )
                {
			$tradeDesc .= "<a rel=\"shadowbox;height:80%;width:80%;\" href=\"workview.php?wid=" . $row['id'] . "&gid=" . $gameinstance . "\"><img src=\"img.php?img=" . $row['id'] . "\" style=\"width:200px;padding-right:5px;\"></a>";	
			echo( "<div style=\"float:left;padding:5px;\"><img src=\"img.php?img=" . $row['id'] . "\" style=\"width:200px;vertical-align:top;\"></div>" );	
                }       
        }
	$tradeDesc .= "</div>";
	echo( "</div></div>" );
	echo( "<p/><div style=\"float:right;\"><button id=\"goHome\">Ok</button></div>\n" );


	// Finally, notify the entire game of trade activity by recording it in the events feed.
        $query = $dbh->prepare( "INSERT INTO events( type, target, other_participant, description, works_target, works_other, headline, xref ) VALUES( ?, ?, ?, ?, ?, ?, ?, ? )" );
        $query->bindValue( 1, $E_TRADE_PROPOSED );
        $query->bindValue( 2, $uuid );
        $query->bindValue( 3, $_SESSION['last_trade_with'] );
        $query->bindValue( 4, mysql_real_escape_string( $tradeDesc ) );
	$query->bindValue( 5, $offers );
	$query->bindValue( 6, $requests );
	$query->bindValue( 7, $headline );
	$query->bindValue( 8, $tradeId );
	$query->execute( );

	$tradeNotificationString = "<a href=\"" . $FCN_ROOT . "marketplace.php?#trades\">" . getUsername( $uuid ) . " proposed a trade with you.</a>";

	createNotification( $_SESSION['last_trade_with'], $E_TRADE_PROPOSED, $tradeNotificationString );

?>


</div>
</body>
</html>
