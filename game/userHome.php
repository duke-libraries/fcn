<?php
  	/** 
	* userHome.php: Collection page for the currently logged-in user.  This really shouldn't be a separate 
	* script from userCollection.php; the only real differences are that the active user has "add tombstone/
	* add description" links below each image in his or her collection.  Future improvement: fold userHome 
	* into userCollection.
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
	global $gameGenie;
        logVisit( $uuid, basename( __FILE__ ) );

?>
<html>
<head>
<title>Fantasy Collecting: My Collection (<?php echo getUserName( $uuid ); ?>)</title>
<script type="text/javascript" src="https://www.google.com/jsapi"></script><script type="text/javascript">
        google.load( "jquery", "1" );
        google.load( "jqueryui", "1" );
</script>
<script type="text/javascript" language="javascript">

        $(document).ready( function() {
                $( "#bug" ).click( function( ) {
                        Shadowbox.open( {
                                content: "bug.php", player: "iframe", height:480, width:640
                        } );
                } );
	} );

</script>
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
<body>
<?php include('topBar.php'); ?>
<div class="body">
<?
                        $userTable = $uuid . "_" . $gameinstance . "_coll";

			$worksInColl = 0;

			// See if the user actually has any works...
			$cCount = $dbh->prepare( "SELECT COUNT(*) as count FROM " . $userTable );
			$cCount->execute( );

			while( $row = $cCount->fetch( ) )
			{
				$worksInColl = $row['count'];
                  		if ( $worksInColl == 0 ) {
                               		 echo( "You don't have any works in your collection.  Perhaps the game administrator hasn't distributed initial collections yet, or maybe you've sold everything you owned..." );
                        	}
			}

			// Should be wrapped in else from the worksInColl == 0 test.  But anyway,
			// we're getting a list of everything in this user's collection and displaying it
			// as a grid.  Same as userCollection.php, except here we have links to forms that
			// allow the user to enter tombstone / description data.  So let's fix this redundancy.  
                        $collectionQuery = $dbh->prepare( "SELECT work,w1.id AS url,w1.title AS title FROM " . $userTable . " LEFT OUTER JOIN works AS w1 ON w1.id = " . $userTable . ".work" );
			$ind = 0;
                        $collectionQuery->execute( );
				echo( "<div style=\"display:table-row;\">" );
                                        while( $item = $collectionQuery->fetch( ) )                        
					{
						if ( $ind % 3 == 0 )
						{
							echo( "</div><div style=\"display:table-row;\">" );
						}
                                	echo( "<div class=\"collCell\"><a rel=\"shadowbox;height:80%;width:80%;\" href=\"workview.php?wid=" . $item['work'] . "&amp;gid=" . $gameinstance . "\"/>" . "<img src=\"img.php?img=" . $item['url'] . ( $uuid == $gameGenie ? "&mode=thumb\"" : "\" width=\"260\"" ) . " alt=\"[Image]\" valign=\"top\"/></a>" );
					echo( "<div class=\"collCellCaption\">" );

					if ( workHasTombstone( $item['url'] ) ) {
						echo( getTombstone( $item['url'], false ) );
					} else { 
						echo( "<a class=\"navHref\" href=\"javascript:Shadowbox.open({ content: 'addTombstone.php?w=" . $item['url'] . "', player: 'iframe', height: 800, width: 640 });\">Add Tombstone</a>" );
					}
					echo " | ";

					if ( workHasDescription( $item['url'] ) ) {
						echo( "<a class=\"navHref\" href=\"javascript:Shadowbox.open({ content: 'addDescription.php?w=" . $item['url'] . "', player: 'iframe', height: 800, width: 640 });\">Edit Description</a>" );
					} else { 
						echo( "<a class=\"navHref\" href=\"javascript:Shadowbox.open({ content: 'addDescription.php?w=" . $item['url'] . "', player: 'iframe', height: 800, width: 640 });\">Add Description</a>" );
					}
						

					echo( "</div></div>\n" );
					$ind++;
                        		}
				echo( "</div>" );

?>
</div>
</body>
<?php
        include('jewel.php');
?>
</html>
