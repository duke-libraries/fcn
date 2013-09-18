<?php
        /**     
        * loveFactory.php: generate the overlay image that shows whether a player has loved a work.
	* Called via sentiment.js on userCollection.php.
	* 
        * @author William Shaw <william.shaw@duke.edu> 
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
        * @version 0.1, 1/2013
	*
	* @param wid The work id in question.
        */      

        if(session_id() == '') {
                session_start();
        }

        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];
	$work = $_GET['wid']
        ob_start( );                
		require '../functions.php';        
		require '../db.php';
	ob_end_clean( );
?>
<?php
	$query = $dbh->prepare( "SELECT uid,wid FROM likes WHERE (uid = ?) AND (wid = ?)" );
	$query->bindParam( 1, $uuid );
	$query->bindParam( 2, $work );
	$query->execute( );

	if ( $query->rowCount() == 0 ) {
		// Player doesn't love this work (yet)
		echo( "<div class=\"heart\" style=\"background:url('resources/icons/raster/gray_light/heart_stroke_16x14.png');background-size:contain;padding-left:2px;width:20px;height:20px;background-repeat:no-repeat;padding-top:2px;\"/>" );
	} else { 
		// <3
		echo( "<div class\"heart\" style=\"background:url('resources/icons/raster/white/heart_fill_16x14.png');background-size:contain;padding-left:2px;width:20px;height:20px;background-repeat:no-repeat;;padding-top:2px;\"/>" );
	}
?>
