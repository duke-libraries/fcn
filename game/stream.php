<?
	/**
	* stream.php: Given an epoch timestamp, get notifications targeted to a specific player (or the entire
	* game) and print them out in a <div> suitable for div#notification in jewel.php.  Called via JavaScript
	* polling in jewel.php.
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1., 8/2012
	*
	* @param jlt (via GET): Last jewel load time.  Passed from jewel.php.
	*/

	if(session_id() == '') {
        	session_start();
	}

        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];
	$jlt = $_GET['jlt'];
        ob_start( );                
		require 'functions.php';        
		require 'db.php';
	ob_end_clean( );

?>
<?
	// Find all notifications that are either global or targeted to uuid
        $query = $dbh->prepare( "SELECT * FROM notifications WHERE (target = ? OR target = -1) AND UNIX_TIMESTAMP(date) > ? ORDER BY date DESC" );
       	$query->bindParam( 1, $uuid ); 
	$query->bindParam( 2, $_SESSION['lastpoll'] );
	$query->execute( );
        while ( $row = $query->fetch( ) ) {
		echo( "<div style=\"clear:both;padding:4px;vertical-align:top;\"><img src=\"resources/icons/" . getIconForEventType( $row['type'] ) . "\" style=\"margin-top:4px;width:12px;float:left;padding-right:4px;padding-bottom:4px;\"/>" . $row['text'] . "</div>" );
        }

	$_SESSION['lastpoll'] = time();
?>

