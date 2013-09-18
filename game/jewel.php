<?

        /**
        * jewel.php: So named because the notification feed was once accessible from a Facebook-style
	*  jewel icon.  This script is included in the major game state pages and prints out the 
	*  persistent notification stream (new messages, trades, etc.) 
	*
        * @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
        * @version 0.1, 10/2012
        */

	if(session_id() == '') {
		session_start();
	}

	ob_start( );
		require 'db.php';
	ob_end_clean( );

        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];

	// Session variable keeping track of the last time the jewel was loaded.  We pass this information
	// to stream.php, the helper that delivers new notifications on the fly. 
	$_SESSION['lastJewelLoadTime'] = time();

?>
<script language="javascript" type="text/javascript">

	$(document).ready( function( ) {
		$("#jewel").height( $(window).height( ) - 61  );

		/**
		* As of 2012/08/01, the notification stream is persistent and can't be hidden.  To enable
		  hiding, uncomment this code and the div below that contains button#toggleJewel.  
		
		$( "button#toggleJewel" ).button( );
		$( "button#toggleJewel" ).click( function( ) {
			$( "#jewel" ).slideToggle( "fast", function ( ) {
				if ( $( "#jewel" ).is( ":visible" ) ) {
					$( "#toggleButton" ).attr( "src", "resources/icons/arrow_down_32x32.png" );
				} else {
					$( "#toggleButton" ).attr( "src", "resources/icons/arrow_up_32x32.png" );
				}
			} );
		} );
		*/

		// Poll away!  HTML5 WebSockets, anyone?   
		(function poll(){
   			setTimeout(function(){
      				$.ajax({ url: "stream.php?jlt=<?echo $_SESSION['lastJewelLoadTime'];?>", success: function(data){
						$('#notificationContent').prepend( data );
        				poll();
      				}, dataType: "html"});
			// Check for new notifications every 10 seconds.  
  			}, 10000);
		})();
	} );

</script>
<!--div style="position:fixed; right:0; bottom:0; z-index:5000;">
<button id="toggleJewel"><img id="toggleButton" src="resources/icons/arrow_down_32x32.png"/></button>
</div-->
<div style="position:fixed; right:0; bottom:0; height:500; overflow:auto; width:250; border:2px solid lightgrey; border-top: none;" id="jewel">
<div id="notificationContent" style="font-size:90%;margin-top:0px;overflow:auto">
<?
	// When loading jewel.php, populate this div with previous notifications.  New ones come in via the
	// polling function wrapped around stream.php.  
        $query = $dbh->prepare( "SELECT * FROM notifications WHERE (target = ? OR target = -1) ORDER BY date DESC" );
        $query->bindParam( 1, $uuid ); 
        $query->execute( );
        while ( $row = $query->fetch( ) ) {
		// This "style" should really be in resources/fcn.css fixme
                echo( "<div style=\"clear:both;padding:4px;vertical-align:top;\"><img src=\"resources/icons/" . getIconForEventType( $row['type'] ) . "\" style=\"margin-top:4px;width:12px;float:left;padding-right:4px;padding-bottom:4px;\"/>" . $row['text'] . "</div>" );
        }
?>
</div>
</div>
