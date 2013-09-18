<?
	/**
	* distributor.php: Script that distributes initial collections to all registered users and gives
	* 200 FCGs to each player.  
	*
	* @param gameid (via GET): the id # of this game.  
	* @param colsize (via GET): the initial size of this collection.
	* @param maxsize (via GET): Maximum allowable collection size.  Not actually used in the game right now.
	*/
	$gameinstance = $_GET['gameid'];
	$initialSize = $_GET['colsize'];
	$maxSize = $_GET['maxsize'];

        ob_start( );         
		require '../functions.php';        
		require '../db.php';
	ob_end_clean( );
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="../resources/fcn.css"/>
<title>
Distributor
</title>
</head>
<body>
<?php
	// See if it's possible to do what the game admin wants in terms of collection size...
	if ( ( $initialSize > $maxSize ) && ( $initialSize < 3 ) ) { 
		echo( "Error: There aren't enough works in the database to support a game of this size!  Need at least 3 works per collector.<p/>\n" );
		echo( "</body></html>\n" );	
		exit( );
	}

	if ( $initialSize > $maxSize ) {
		echo( "Warning: initial collection size is greater than maximum alloawble collection size.  Collection size will be reduced to " . $maxSize . ".<p/>\n" );
		$initialSize = $maxSize;
	} else if ( $initialSize < 3 ) { 
		echo( "Warning: the minimum collection size is 3.  Increasing initial collections to 3 works.<p/>\n" );
		$initialSize = 3;
	}


	$tempTableName = "temp_" . time();

	// Copy all works into a temporary table
	$stmt = $dbh->prepare( "CREATE TABLE " . $tempTableName . " AS SELECT * FROM works" );
	$stmt->execute( ); 


	echo( "<h2>Distributing initial collections</h2>" );
	$stmt = $dbh->prepare("SELECT collections.id,owner,gameinstance,collectors.name,collectors.id as coid from collections inner join collectors on collectors.id = owner where gameinstance = ?" );
	$stmt->bindParam( 1, $_GET['gameid'] );
	$stmt->execute( );
	
	while( $row = $stmt->fetch( ) )
	{
		if ( $row['coid'] == $FCN_SUPERUSER ) {
			continue;
		}

		// Specify the name of the user collection table and create it.  In retrospect, I'm skeptical that this
		// is the best way to keep track of who owns what.  It made sense (maybe) when we thought we'd be having
		// multiple game instances running simultaneously, but I'd prefer just having an owner field in the
		// main works table now.   
		$userTable = $row['coid'] . "_" . $_GET['gameid'] . "_coll";
		$substat = $dbh->prepare( "CREATE TABLE " . $userTable . " ( work INT )" );
		$substat->execute( );

		// Give each player 200 points
		adjustPoints( $row['coid'], 200 );	

		// Select n random works into the user table 
		echo( "<h3>" . $row['name'] . "</h3>" );
		$subs = $dbh->prepare( "SELECT * FROM " . $tempTableName . " WHERE id > -1 ORDER BY RAND() LIMIT " . $initialSize );
		$subs->execute( );
	
		while( $subrow = $subs->fetch( ) )
		{
			// Show game admin who got what, and then delete the selected works from this temporary table. 
			echo( "<img src=\"../img.php?img=" . $subrow[ 'id' ] . "\" style=\"width:100px;\"/>\n" );
			$deletion = $dbh->prepare( "DELETE FROM " . $tempTableName . " WHERE id = ?" );
			$deletion->bindParam( 1, $subrow['id'] );
			$deletion->execute( );
			
			// For purposes of provenance, record this as a "trade" from origin -1
			$insertion = $dbh->prepare( "INSERT INTO trades(origin, destination, gameinstance, work_from_origin, work_from_destination, accepted) VALUES(?,?,?,?,?,?)" );
			$insertion->bindValue( 1, -1 );
			$insertion->bindValue( 2, $row['owner'] );
			$insertion->bindValue( 3, $_GET['gameid'] );
			$insertion->bindValue( 4, $subrow['id'] );
			$insertion->bindValue( 5, -1 );
			$insertion->bindValue( 6, 1 );
			$insertion->execute( );

			$insertion = $dbh->prepare( "INSERT INTO " . $userTable . " VALUES( ? )" );
			$insertion->bindValue( 1, $subrow['id'] );
			$insertion->execute( );
		}
	} 

	$stmt = $dbh->prepare( "DROP TABLE " . $tempTableName );
	$stmt->execute( );
?>
</body>
</html>
