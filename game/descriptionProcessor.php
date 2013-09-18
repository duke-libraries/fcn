<?      
	/**
	* descriptionProcessor.php: Form processor for submitting descriptions.  Called only from addDescription (the form
	* that also edits existing descriptions of works).  
	* 
	* @param work (via POST) - work ID in question.
	* @param desc (via POST) - text description of the work, its provenance, significance, etc.
	* @param mode (via POST) - either "new" (entirely new description) or "edit" (change existing).
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
	$work = $_POST['work'];
	$desc = $_POST['desc'];
	$mode = $_POST['mode'];

        ob_start( );
		require 'functions.php';        
		require 'db.php';
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
		// Resize the parent Shadowbox after the user submits the work description.  I.e., 
		// make it a more appropriate size for the information we're about to display... 
		var s = window.parent.Shadowbox;
		s.setDimensions( 150, 200, 150, 200, 0, 0, 0, true );
		$( "#dismiss" ).button( );
	} );
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
</head>
<body style="background-color:#fff">
<?php
	if ( $mode === "new" ) 
	{
		// New description: just insert into work_descriptions table.  Again, bindParam() handles input cleaning.
		$stmt = $dbh->prepare( "INSERT INTO work_descriptions( uid, text, work, approved ) VALUES( ?,?,?,2 )");
		$stmt->bindParam( 1, $uuid);
		$stmt->bindParam( 2, $desc);
		$stmt->bindParam( 3, $work );
		$stmt->execute( );
	} else {
		// If not new, then must be update.  We should really keep revisions on record: possible FIXME?
		// Reset approved status to 2 (pending approval).  
		$stmt = $dbh->prepare( "UPDATE work_descriptions SET uid =?, text =?, approved=2 WHERE work = ?");
                $stmt->bindParam( 1, $uuid);
                $stmt->bindParam( 2, $desc );
                $stmt->bindParam( 3, $work );
                $stmt->execute( );	
	}
?>
<h2>Description Updated</h2>
You've updated the description for this work.  It has been submitted for approval.  
<p/>
<button id="dismiss" onClick="window.parent.Shadowbox.close( );window.parent.location.href=window.parent.location.href;">Okay</button>
</body>
</html>
