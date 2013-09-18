<?php
	// Database stub -- set up the connection to MySQL.

        $username = "";
        $password = "";        
        $dbh = new PDO( 'mysql:host=localhost;dbname=fcn', $username, $password );
	$mhost = "localhost";
        mysql_connect( $mhost, $username, $password ) OR DIE("Error: cannot connect to database.");

	$gg = $dbh->prepare( "SELECT id FROM collectors ORDER BY id ASC LIMIT 1" );	// TODO: Better way to specify superuser.
	$gg->execute( );
	$gameGenie = -1;
	while( $row = $gg->fetch( ) ) { $gameGenie = $row['id']; }	

?>
