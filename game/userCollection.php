<?php                   
        /**
        * userCollection.php: Display one user's collection as a grid of images linked to workview.php.
	* Includes a button linking to trader.php (allows users to propose trades with one another). 
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
	$userRequested = $_GET['uid'];

        ob_start( );
                require 'functions.php';
		require 'db.php';
        ob_end_clean( );

        logVisit( $uuid, basename( __FILE__ ) );

?>                              
<html>                                  
<head>                                  
<script type="text/javascript" src="https://www.google.com/jsapi"></script><script type="text/javascript">
        google.load( "jquery", "1" );           
        google.load( "jqueryui", "1" );                 
</script>                                       <link rel="stylesheet" type="text/css" href="resources/fcn.css"/>
<link rel="stylesheet" type="text/css" href="resources/shadowbox.css">
<link rel="stylesheet" type="text/css" href="resources/jquery-ui.css"/>
<script type="text/javascript" src="resources/sentiment.js"></script>
<script type="text/javascript" src="resources/shadowbox.js"></script>
<script type="text/javascript">
	Shadowbox.init( {
		overlayOpacity: '0.9',
		modal: true
	});


	$(document).ready( function( ) {
		$( "button.subs" ).button( );	// The oddly named trade-proposal button.
                $( "#bug" ).click( function( ) {
                        Shadowbox.open( {
                                content: "bug.php", player: "iframe", height:480, width:640
                        } );
                } );
	} );
</script>
</head>
<body>     
<?php include('topBar.php');?>
<div class="body">
<a href="collections.php" class="xbold navHref">All Collections</a> &gt; 
<?php 
echo( getUserName( $userRequested ) ); ?>'s Collection
<p/>
<?php if ( $uuid != $userRequested ) {
	// If this is someone other than uuid (me), display trade proposal button.
	if ( isConnoisseur( $userRequested ) ) {
		echo( "<img src=\"resources/icons/star_16x16.png\"/>" );
		echo( "&nbsp;&nbsp;<b>" . getUserName( $userRequested ) . "</b> has been given the <b>Connoisseur</b> badge by the game administrator!  " );
		echo( getUserName( $userRequested ) . " can earn " . $CURRENCY_SYMBOL . " by validating other players' tombstones and work descriptions.<p/>" );
	}

echo( "<a href=\"trader.php?origin=" . $uuid . "&amp;destination=" . $userRequested . "&amp;gameinstance=" . $gameinstance . "\" rel=\"shadowbox;height=800;width=1000;\">" );
echo( "<button type=\"submit\" class=\"subs ui-state-default ui-corner-all\"> Propose a Trade </button></a>");
}?>
</form>
<?php
		// Wordy but simple logic -- get everything this player owns and display it as a grid.
                $sstmt = $dbh->prepare( "SELECT collections.id,owner,gameinstance,points,collectors.name AS cn from collections inner join collectors on collectors.id = owner where gameinstance = ? and owner = ?" );
                $sstmt->bindParam( 1, $gameinstance );
		$sstmt->bindParam( 2, $userRequested );
                $sstmt->execute( );

		$ind = 0;
		echo( "<div style=\"display:table-row;\">" );

		// This whole thing really needs to be a modular function instead of being replicated nearly verbatim in >= 2 places.
		// There's no reason userHome should be a separate file, for example.
                while( $row = $sstmt->fetch( ) )
                {
                        $userTable = $row['owner'] . "_" . $gameinstance . "_coll";

                        $collectionQuery = $dbh->prepare( "SELECT work,w1.id AS url FROM " . $userTable . " LEFT OUTER JOIN works AS w1 ON w1.id = " . $userTable . ".work" );
                        $collectionQuery->execute( );
			$userName = $row['cn'];
                        while( $item = $collectionQuery->fetch( ) )
                        {
		                if ( $ind % 3 == 0 )
               			{
					// 3 x n grid
                                	echo( "</div><div style=\"display:table-row;\">" );
                        	}
				// Link the work image to workview.php (shadowbox).
                                echo( "<div class=\"collCell\"><a href=\"workview.php?wid=" . $item['url'] . "\" rel=\"shadowbox\">" );
	
				// Include the sentiment div, which contains the heart icon players can click to "love" a work.
				// (Handled in sentiment.js)
                                echo( "<div class=\"imgMouseOver\" name=\"" . $item['url'] . "\" style=\"background:url('img.php?img=" . $item['url'] . "');background-size:contain;background-repeat:no-repeat;\"/><div class=\"sentiment\" name=\"" . $item['url'] . "\" style=\"background-color: rgba(0,0,0,0.5);position:relative;float:left;width:20px;height:20px;padding:5px;overflow: none;z-index:-200;\"></div></div></a>" );
				echo( "<div class=\"collCellCaption\">" );
				// Show tombstone, if there is one.
				if ( workHasTombstone( $item['url'] ) ) {
					echo ( getTombstone( $item['url'], false ) );
				}
                                echo( "</div></div>\n" );
				$ind++;
                        }
                }
		echo "</div>\n";
?>
</div>
</body>
<?php
        include('jewel.php');
?>
</html>
