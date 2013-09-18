<?php
	/**
        * mostLoveFactory.php: generate the list of most loved works for display on the dashboard (home.php). 
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
		require '../db.php';
		require '../functions.php';        
	ob_end_clean( );
?>
<?php
	$query = $dbh->prepare( "select wid,count(*) as c from likes group by wid ORDER BY c DESC LIMIT 10" );
	$query->execute( );
	$firstIteration = true;
	$maxValue = -1;
	if ( $query->rowCount() == 0 ) {
		echo( "Nobody loves any works yet...why not go find something you like and &#x2665; it?" );
	} else { 

		while( $row = $query->fetch( ) ) {
			// On first iteration, we need to know the maximum # of likes in the game so we can scale
			// our ad hoc "bar chart" accordingly
			if ( $firstIteration ) { $maxValue = $row['c']; $firstIteration = false; }
	
			// Calculate width of this div (use %)
			$barWidth = floor( ( $row['c'] / $maxValue ) * 100 );
			echo( "<div style=\"padding:10px;z-index:0;float:left;clear:both;border:0px solid black;width:90%;text-align:left;\">" );
			echo( "<div style=\"padding:10px;z-index:1;height:80px;width:" . $barWidth . "%;background-color:lightgray;\">" );	
			echo( "<a href=\"workview.php?wid=" . $row['wid'] . "\"  target=\"shadowbox\"><img src=\"img.php?img=" . $row['wid'] . "\" style=\"float:left;height:80px;\"/></a>" );
			echo( "<span style=\"margin-left:20px;margin-left:20px;font-size:2.75em;color:gray;\">" . $row['c'] . "</span>\n" );
			echo( "<span style=\"font-size:1.25em;color:gray;\"><sup>&#x2665;</sup></span>" );
			echo( "</div>" );
			echo( "</div>" );
		}
	}
?>
