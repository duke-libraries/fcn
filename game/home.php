<?php
	/**
	* home.php: The first page a user lands on after logging in.  Contains the newsfeed, notification widget, 
	* and dashboard.
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1., 2/2013
	*/

	if(session_id() == '') {
        	session_start();
	}

        $gameinstance = $_SESSION['gameinstance']; 
	$uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];

        ob_start( );      
		require 'db.php';
		require 'functions.php';
	ob_end_clean( );

	logVisit( $uuid, basename( __FILE__ ) );
?>
<html>
<head>                                  
<title>Fantasy Collecting: Home (<?php echo getUserName( $uuid ); ?>)</title>
<script type="text/javascript" src="https://www.google.com/jsapi"></script><script type="text/javascript">
        google.load( "jquery", "1" );           
        google.load( "jqueryui", "1" );                 
</script>                                       
<link rel="stylesheet" type="text/css" href="resources/fcn.css"/>
<link rel="stylesheet" type="text/css" href="resources/resources/shadowbox.css">
<link rel="stylesheet" type="text/css" href="resources/jquery-ui.css"/>
<script type="text/javascript" src="resources/shadowbox.js"></script>
<script src="http://d3js.org/d3.v2.js"></script>
<script type="text/javascript" src="resources/jquery.ui.potato.menu.js"></script>
<link rel="stylesheet" type="text/css" href="resources/jquery.ui.potato.menu.css"/>
<script type="text/javascript">
        Shadowbox.init( {
                overlayOpacity: '0.9',
                modal: true
        });

	$(document).ready( function( ) {
		// Set up document widgets, etc.
		$( "#tabs" ).tabs();
		$( "button" ).button( );

		$( "#bug" ).click( function( ) {
			Shadowbox.open( {
				content: "bug.php", player: "iframe", height:480, width:640
			} );
		} );

		// glanceSelector is a <select> widget on the Dashboard tab.  It uses load() to call
		// various data factories and display their output in the viscontent div.  This is an
		// extensible part of FCN, meant to allow for easy plugging-in of new visualizations, 
		// data displays, etc.  
		$( "#glanceSelector" ).change( function( ) {
			$("#viscontent").empty();
			var visType = $("#glanceSelector option:selected").attr("id");

			if ( visType == 'mostlove' ) {
                                $("#viscontent").load( 'factories/mostLoveFactory.php' );
			} else if ( visType == 'volume' ) {
				doTradeVol( );
			} else if ( visType == 'leaderboard' ) {
				$("#viscontent").load( 'factories/fcgRankingFactory.php' );
			} else { /* Unknown vis type? */ }
		} );

		// Challenge approval button is available to Connoisseur players; they can earn FCGs by 
		// approving others' tombstone submissions.  
                $(".challengeApprover").button( );
                $( ".challengeApprover" ).click( function( ) { 
                        var bn = $(this).attr('format');
                        var action = $(this).attr( 'formact' );	//formact = approve/reject
                        var submissionData = $('form#' + bn ).serialize( ); 
                        submissionData += '&action=' + action + '&uuid=' + <?php echo $uuid;?>;
                        console.log( submissionData );
                        $.ajax( {
                                type: "GET",
                                url: "ca.php",
                                data: submissionData,
                                success: function( result ) {
                                        console.log( submissionData );
                                }
                        } );
                        // Fade this row once we've approved or rejected its contents.
                        $(this).closest( 'tr' ).fadeOut( 500 );
                        return false;
                } );


		// FIXME this really needs to be in an external file.  D3 chordal graph adapted from Mike Bostock's 
		// sample.  
		function doTradeVol( ) {
			var outerRadius = 700 / 2,
    			innerRadius = outerRadius - 120;

			var fill = d3.scale.category20c();

			var chord = d3.layout.chord()
    				.padding(.04)
    				.sortSubgroups(d3.descending)
    				.sortChords(d3.descending);

			var arc = d3.svg.arc()
    				.innerRadius(innerRadius)
    				.outerRadius(innerRadius + 20);

			var svg = d3.select("#viscontent").append("svg")
    				.attr("width", outerRadius * 2)
    				.attr("height", outerRadius * 2)
  				.append("g")
    				.attr("transform", "translate(" + outerRadius + "," + outerRadius + ")");

			d3.json("factories/dataFactory.php", function(trades) {
  				var indexByName = {},
      				nameByIndex = {},
      				matrix = [],
      				n = 0;

  				trades.forEach(function(d) {
    					d = d.name;
    					if (!(d in indexByName)) {
      					nameByIndex[n] = d;
      					indexByName[d] = n++;
    					}
  				});

  
				trades.forEach(function(d) {
    					var source = indexByName[d.name],
        				row = matrix[source];
    					if (!row) {
     						row = matrix[source] = [];
     						for (var i = -1; ++i < n;) row[i] = 0;
    					}
    				d.trades.forEach(function(d) { row[indexByName[d]]++; });
  			});

  			chord.matrix(matrix);

  			var g = svg.selectAll("g.group")
      				.data(chord.groups)
    				.enter().append("g")
      				.attr("class", "group");

  			g.append("path")
      				.style("fill", function(d) { return fill(d.index); })
      				.style("stroke", function(d) { return fill(d.index); })
      				.attr("d", arc);

  			g.append("text")
      				.each(function(d) { d.angle = (d.startAngle + d.endAngle) / 2; })
      				.attr("dy", ".35em")
      				.attr("text-anchor", function(d) { return d.angle > Math.PI ? "end" : null; })
      				.attr("transform", function(d) {
        				return "rotate(" + (d.angle * 180 / Math.PI - 90) + ")"
            				+ "translate(" + (innerRadius + 26) + ")"
            				+ (d.angle > Math.PI ? "rotate(180)" : "");
      				})
      			.text(function(d) { return nameByIndex[d.index]; });

  			svg.selectAll("path.chord")
      				.data(chord.chords)
    				.enter().append("path")
      				.attr("class", "chord")
      				.style("stroke", function(d) { return d3.rgb(fill(d.source.index)).darker(); })
      				.style("fill", function(d) { return fill(d.source.index); })
      				.attr("d", d3.svg.chord().radius(innerRadius));
			});
		}
               
		$("#viscontent").load( 'factories/fcgRankingFactory.php' );

	} );

</script>
</head>         
<body>         
<?php include('topBar.php'); ?>
<div class="body">
<div id="tabs" style="min-width:50%;max-width:80%;margin-left:40px;">
	<ul>
	<li><a href="#allactivity">All Activity</a></li>
	<li><a href="#dashboard">Dashboard</a></li>
	<?php
		if ( isConnoisseur( $uuid ) ) {
			// Some visual indicator that the user is a connoisseur, and a tab containing the tombstone approval code
			echo( "<li><a href=\"#con\"><img src=\"resources/icons/star_24x24.png\" style=\"height:22px;\"/></a></li>");
		}
	?>
	</ul>

	<div id="allactivity"> 
		<?php
			// Display the event feed.  TODO: make the event feed a little more usable, e.g., by loading
			// older events when the user scrolls to the bottom of the page.  Right now, there's no obvious
			// way for players to see what happened before the past 40 events.   	
			$query = $dbh->prepare( "SELECT * FROM events ORDER BY date DESC LIMIT 40" );
			$query->execute( );
			while( $row = $query->fetch( ) ) {
				// displayEvent: convenience function (functions.php) that generates the HTML.  
				echo displayEvent( $row, $CONTEXT_EVENT_FEED );	
			}
		?>
	</div>

	<?php
		// If the user has Connoisseur status, print the div (tab) that allows them to approve 
		// submitted tombstones.  The logic here is basically the same as the supervisor's page
		// (supervisor/index.php).  
		if ( isConnoisseur( $uuid ) ) {
			echo( "<div id=\"con\">" );
			echo( "<h2>You're a Connoisseur!</h2>" );
			echo( "You've earned the connoisseur badge!  As a reward for your excellent gameplay, " );
			echo( "you can earn extra " . $CURRENCY_SYMBOL . " by approving other players' tombstones.  Check this " );
			echo( "tab of your home screen to see what tombstones are available for validation. " );

			echo( "<table style=\"border:1px solid black;width:90%;\">" );
			echo( "<tr><td class=\"header\">Collector</td><td class=\"header\">Work</td><td class=\"header\">Tombstone</td><td class=\"header\">Approve?</td></tr>" );
              
			// TODO the magic numbers for approval/rejection in various tables are really confusing.  
			// 2 = pending approval, but how would anyone know?  Need to make these named constants before release. 
			$stmt = $dbh->prepare( "SELECT * FROM tombstones WHERE approved = 2 AND uid_creator != ?" );
			$stmt->bindParam( 1, $uuid );
                	$stmt->execute( );
                        
                while ( $row = $stmt->fetch( ) ) {
			// Create an ad hoc form for approving or rejecting tombstone
                        echo( "<form id=\"" . $row['id'] . "-ts\"><input type=\"hidden\" name=\"mode\" value=\"ts\"/><input type=\"hidden\" name=\"tombstoneId\" value=\"" . $row['id'] . "\"/><input type=\"hidden\" name=\"player\" value=\"" . $row['uid_creator'] . "\"/><input type=\"hidden\" name=\"work\" value=\"" . $row['wid'] . "\"/><tr>\n" );
                        echo( "<td style=\"vertical-align:top;\">" . getUsername( $row['uid_creator'] ) . "</td><td style=\"vertical-align:top;\"><a rel=\"shadowbox\" href=\"workinfo.php?wid=\"" . $row['wid'] . "&gameinstance=" . $gameinstance . "\"><img src=\"img.php?img=" . $row['wid'] . "\" style=\"width:75px;\"/></a></td>" );
                        echo( "<td style=\"vertical-align:top;\">" . getTombstone( $row['wid'], false ) . "</td><td>" );
                        echo( "<button format=\"" . $row['id'] ."-ts\"  class=\"challengeApprover\" formact=\"approve\">Yes</button>" );
                        echo( "<button format=\"" . $row['id'] . "-ts\" class=\"challengeApprover\" formact=\"reject\"> No </button></td></tr></form>\n" );
                }


        		echo( "</table>" );
		

			echo( "</div>\n" );
		}
	?>

	<!-- Print the dashboard div.  There's some useful (?) functionality here, but it's really meant to
	     be an extensible foundation for additional visualizations, market-at-a-glance charts, etc. -->	
	<div id="dashboard" style="min-height:950px;">
		<div id="love">
		Works you <img src="resources/icons/raster/gray_dark/heart_fill_16x14.png" style="margin-top:6px;" alt="love"/>
		<hr style="color:lightgray;background-color:lightgray;height:1px;width:80%;"/>
		<p/>
		<?php
			// Get a list of all works this player has liked by clicking the heart icon (from userCollection.php);
			// display them as images
			$love = $dbh->prepare( "SELECT wid FROM likes WHERE uid = ?" );
			$love->bindParam( 1, $uuid );

			$love->execute( );

			while ( $loveRow = $love->fetch( ) ) {
				echo( "<img src=\"img.php?img=" . $loveRow['wid'] . "\" style=\"width:100%;\"/><p/>\n" );
			}

			if ( $love->rowCount( ) == 0 ) {
				echo( "You haven't <img src=\"resources/icons/raster/gray_dark/heart_fill_16x14.png\" style=\"margin-top:6px;\" alt=\"love\"/>ed any works yet.<p/>");
			}
		?>	
		</div>	

		<div id="vis">
		<!-- vis = visualization, although there's other data here too.  -->
		<div class="visHeader">
		&nbsp;&nbsp;&nbsp;
		Market at a Glance: 

		<!-- handled in (document).ready() above. -->	
		<select id="glanceSelector">
			<option id="leaderboard" selected><?php echo $CURRENCY_SYMBOL;?> Leaderboard</option>
			<option id="volume">Trade volume</option>
			<option id="mostlove">Most &#x2665;'d works</option>
		</select>
		
		</div>
		<div id="viscontent">
			<!-- Populated on change of state in glanceSelector -->
		</div>
		</div>
	</div>

</div>
</div>
<?php
        include('jewel.php');
?>
</body>
</html>
