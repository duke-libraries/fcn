<?php
        /**     
        * fcgRankingFactory.php: Generate an ordered list of collectors (by points descending).  For
	* inclusion on the "market at a glance" dashboard in home.php.
        *
        * @author William Shaw <william.shaw@duke.edu> 
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
        * @version 0.1, 1/2013
        */      

        if(session_id() == '') {
                session_start();
        }

        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];
        ob_start( );
		require '../functions.php';        
		require '../db.php';
	ob_end_clean( );
?>
<?php
	$query = $dbh->prepare( "select name,points,id from collectors WHERE (id != " . $FCN_SUPERUSER . " AND id > -1) ORDER BY points DESC LIMIT 10" );
	$query->execute( );
		$x = 1;
		while( $row = $query->fetch( ) ) {
			echo( "<div style=\"font-size:1.5em;margin-left:15px;padding-left:15px;padding:10px;z-index:0;float:left;clear:both;border:0px solid black;width:90%;text-align:left;\">" );
			echo( $x . ".  <a href=\"userCollection.php?uid=" . $row['id'] . "\"  target=\"shadowbox\">" . $row['name'] . "</a>" );
			echo( "<span style=\"margin-left:20px;margin-left:20px;color:gray;\">" . $CURRENCY_SYMBOL . $row['points'] . "</span>\n" );
			echo( "</div>" );
			++$x;
		}
?>
