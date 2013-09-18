<?php
	/**
	* award.php: do the work that allows the game admin to arbitrarily award (or penalize) players by
	* adjusting their points.
	*/
        ob_start( );                
		require '../functions.php';        
		require '../db.php';
	ob_end_clean( );

	$player = $_GET['collector'];
	$message = $_GET['desc'];
	$points = $_GET['points'];

	createNotification( $player, $E_HAZARD, $message );
	adjustPoints( $player, $points );	

	echo( "Divine intervention complete." );
?>

