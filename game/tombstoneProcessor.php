<?      
	/**
	* tombstoneProcessor.php: Simple form handler for tombstones (Artist name/lifespan, work title).
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 8/2012
	* 
	* @param work (via POST): work id (primary key of works table)
	* @param artist (via POST): string containing the artist's name.
	* @param born (via POST): artist's year of birth (free form string)
	* @param died (via POST): artist's year of death (free form string)
	* @param wt (Via POST): work title 
	* @param wd (via POST): work date
	*/

	if(session_id() == '') {
        	session_start();
	}

        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];
	$work = $_POST['work'];
	$artist = $_POST['artist'];
	$born = $_POST['born'];
	$died = $_POST['died'];
	$wt = $_POST['wt'];
	$wd = $_POST['wd'];
        
	ob_start( );                
		require 'db.php';
		require 'functions.php';        
	ob_end_clean( );
	
        logVisit( $uuid, basename( __FILE__ ) );
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
		// Hook into parent window Shadowbox, resize it, and activate "dismiss" button
		var s = window.parent.Shadowbox;
		s.setDimensions( 150, 200, 150, 200, 0, 0, 0, true );
		$( "#dismiss" ).button( );
	} );
</script>
<link rel="stylesheet" type="text/css" href="resources/jquery-ui.css"/>
<link rel="stylesheet" type="text/css" href="resources/fcn.css"/>
<link rel="stylesheet" type="text/css" href="resources/shadowbox.css">
<script type="text/javascript" src="resources/shadowbox.js"></script>
<script type="text/javascript">        Shadowbox.init( {
                overlayOpacity: '0.9',
                modal: true
        });

</script>
</head>
<body style="background-color:#fff">
<?php
	// Fairly simple logic here -- just insert the form values into the tombstones table.
	$stmt = $dbh->prepare( "INSERT INTO tombstones( wid, uid_creator, artist, born, died, worktitle, workdate, approved ) VALUES( ?,?,?,?,?,?,?,2 )" );
	$stmt->bindParam( 1, $work );
	$stmt->bindParam( 2, $uuid );
	$stmt->bindParam( 3,  $artist );
	$stmt->bindParam( 4,  $born  );
	$stmt->bindParam( 5,  $died );
	$stmt->bindParam( 6,  $wt );
	$stmt->bindParam( 7,  $wd );
	$stmt->execute( );
?>
<h2>Tombstone Recorded</h2>
You've added a tombstone to "<?php echo $_POST['wt'];?>".
<p/>
<button id="dismiss" onClick="window.parent.Shadowbox.close( );window.parent.location.href=window.parent.location.href;">Okay</button>
</body>
</html>
