<?php
	/**
	* functions.php: container for utility functions that appear throughout the FCN code.  Also contains
	* game-wide constants and configuration variables (paths, etc). 
	*
        * @author William Shaw <william.shaw@duke.edu>  
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	*/

	// Database connection stub
	require 'db.php';

        $mhost = "localhost";
        $gameinstance = $_SESSION['gameinstance'];
        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];

	// Root path of FCN game files.
	$FCN_ROOT			= "/complete/path/on/your/server/";

	$FCN_IMAGES_PATH		= $FCN_ROOT . "resources/img/";

	$CURRENCY_SYMBOL		= '&#8750;';	// Unicode 222E (integral symbol)

	$AUCTION_IDLE_TIME		= 20;		// Time (in seconds) of zero-bid activity required to end an auction

	// Event types/
	$E_TRADE_PROPOSED		= 1;
	$E_TRADE_REJECTED		= 2;	
	$E_TRADE_ACCEPTED		= 3;
	$E_HAZARD			= 4;
	$E_ACHIEVEMENT			= 5;
	$E_CHALLENGE_ISSUED		= 6;
	$E_AUCTION_STARTED		= 7;
	$E_BID_PLACED			= 8;
	$E_AUCTION_WON			= 9;
	$E_AUCTION_FAILED		= 10;
	$E_CLASSIFIED_PURCHASE		= 11;
	$E_CLASSIFIED_LISTING		= 12;
	$E_WALL_LABEL_UPDATED		= 13;
	$E_MESSAGE_RECEIVED		= 14;	

	// Event display contexts.  
	$CONTEXT_EVENT_FEED		= 0;

	// User preferences.  The preferences system is not really sketched out yet, but the idea is
	// to use bitmasking to keep track of an arbitrary number of preferences.
	$PREF_SHOW_NOTIFICATION_TICKER	= (1 << 0);
	$PREF_SEND_ALERTS_VIA_EMAIL	= (1 << 1);

	// Points awarded for various game actions.
	$PTS_CHALLENGE_TOMBSTONE	= 10;
	$PTS_CHALLENGE_DESCRIPTION	= 10;

	// States of unavailability, so to speak.
	$AVAILABLE			= 0;
	$UNAVAILABLE_CURVEBALL		= 1;
	$UNAVAILABLE_TRADE		= 2;
	$UNAVAILABLE_CLASSIFIED		= 3;

	// Reasons for adjusting player's money (for the transactions table)
	$REASON_AWARD			= 0;
	$REASON_CURVEBALL_POSITIVE	= 1;
	$REASON_CURVEBALL_NEGATIVE	= 2;
	$REASON_INCOME_FROM_AUCTION	= 3;
	$REASON_INCOME_FROM_CLASSIFIED	= 4;
	$REASON_INCOME_FROM_TRADE	= 5;
	$REASON_CONSIGNMENT_FEE		= 6;
	$REASON_EXPENSE_CLASSIFIED_PURCHASE	= 7;
	$REASON_EXPENSE_AUCTION_PURCHASE	= 8;
	$REASON_PENALTY			= 9;
	$REASON_THEFT			= 10;
	$REASON_INTEREST_ACCRUED	= 11;
	$REASON_EXPENSE_BANK_PAYMENT	= 12;
	$REASON_INCOME_FROM_BANK_LOAN	= 13;
	$REASON_EXPENSE_P2P_LOAN_PAYMENT	= 14;
	$REASON_INCOME_FROM_P2P_LOAN		= 15;

	// Levels
	$LEVEL_DEFAULT_PLAYER		= 1;
	$LEVEL_CONNOISSEUR		= 10;
	$LEVEL_ADMIN			= 100;

	/**
	* isConnoisseur: does the player have connoisseur status?
	* @param uid The user id
 	* @return Boolean 
	*/
	function isConnoisseur( $uid ) {
		global $dbh;
		$query = $dbh->prepare( "SELECT level FROM collectors WHERE id = ? AND level = ? LIMIT 1" );
		$query->bindParam( 1, $uid );
		$query->bindValue( 2, 10 );
		$query->execute( );
		return( $query->rowCount( ) > 0 ? true : false );
	}

	/**
	* logVisit: Record a visit to a PHP script in the database.  Allows a picture of user movement 
	* across the game over time (visits table automatically generates timestamp on insert).
	*
	* @param uid The user id
	* @param page The filename of the script calling this function.
	*/
	function logVisit( $uid, $page ) {
		global $dbh;
		$query = $dbh->prepare( "INSERT INTO visits(uid,page) VALUES(?,?)" );
		$query->bindParam( 1, $uid );
		$query->bindParam( 2, $page );
		$query->execute( );
	}

        /**
        * setLevel: set a player's level.  Right now, this doesn't mean much -- it's either 1 or 10. 
        *
        * @param uid The user id to change level
	* @param level The level to set
        */
	function setLevel( $uid, $level ) { 
		global $dbh;
		$query = $dbh->prepare( "UPDATE collectors SET level = ? WHERE id = ?" );
		$query->bindParam( 1, $level );
		$query->bindParam( 2, $uid );
		$query->execute( );
	}

        /**
        * isPreferenceTrue: return true if (bitvector & flag).  Unfinished. 
        */
	function isPreferenceTrue( $uid, $flag ) {
		// ( bitvector & flag ) == return true 		
	}

        /**
        * getUsername: Given a uid #, return the associated username. 
        *
        * @param uid The user id
        * @return Username, if the user exists, or "[Unknown]" if there's no such user.  
        */	
	function getUsername( $uid ) {
		global $dbh;
		$userQuery = $dbh->prepare( "SELECT id,name FROM collectors WHERE id = ? LIMIT 1" );
		$userQuery->bindParam( 1, $uid );
		$userQuery->execute( );

		while( $row = $userQuery->fetch( ) ) { return $row['name']; } 

		return "[Unknown]";
	}

        /**
        * getAdministratorId: return the id of the game administrator.  By default, this is the minimum
	* value of the user id column in collectors, but future versions of the game shouldn't have a 
	* semi-magic uid.  Instead, we just say that level 100 = admin. 
        *
        * @return The administrator's uid. 
        */
	function getAdministratorId( ) {
		global $dbh;
                $userQuery = $dbh->prepare( "SELECT id FROM collectors WHERE level = ? LIMIT 1" );	
		$userQuery->bindParam( 1, $LEVEL_ADMIN );
		$userQuery->execute( );
		while( $row = $userQuery->fetch( ) ) {
			return( $row['id'] );
		}
		return -1;
	}

        /**
        * isUsernameAvailable: See if a propsoed username is already taken. 
        *
        * @param test The proposed username.
        * @return Boolean (true = available, false = taken).
        */
	function isUsernameAvailable( $test )
	{
		global $dbh;
		$query = $dbh->prepare( "SELECT name FROM collectors WHERE name = ?" );
		$query->bindParam( 1, $test );
		$query->execute( );

		return( $query->rowCount( ) > 0 ? false : true );
	}

        /**
        * getUserId: Given a username, return the id (primary key of collectors table) associated with it. 
        *
        * @param name The username.
        * @return The user id, or -1 if no result.
        */
	function getUserId( $name )
	{
		global $dbh;
                $query = $dbh->prepare( "SELECT id,name FROM collectors WHERE name = ?" );
                $query->bindParam( 1, $name);
                $query->execute( );
		while ( $row = $query->fetch( ) ) {
			return $row['id'];
		}
		return -1;
	}


	/**
        * getMessageContent: Print the contents of a given mail message, given its id (primary key in msgs table). 
        *
        * @param mid The message id.
        * @return The message content, or a string indicating that the message id wasn't found.
        */
	function getMessageContent( $mid ) {
		global $dbh;
                $userQuery = $dbh->prepare( "SELECT mid,string FROM msgs WHERE mid = ? LIMIT 1" );
                $userQuery->bindParam( 1, $mid );
                $userQuery->execute( );
                while( $row = $userQuery->fetch( ) ) { 
		$str = str_replace('\r\n', '<p/> ', $row['string']);
		
		return ( stripslashes( $str ) ); }

                return "[Unknown message content]";
	}

        /**
        * getImageURLFromID: Given a work ID (primary key of works table), return the name of the image file associated
	* with that work. 
        *
        * @param iid The work id.
        * @return The relative path of the image file, or a string indicating failure.
        */
	function getImageURLFromID( $iid ) {
		global $dbh;
                $userQuery = $dbh->prepare( "SELECT id,img FROM works WHERE id = ? LIMIT 1" );
                $userQuery->bindParam( 1, $iid );
                $userQuery->execute( );

                while( $row = $userQuery->fetch( ) ) { return $row['img']; }

                return "[Unknown URL]";
        }

        /**
        * getThumbnail: Given a work ID, generate a link to its thumbnail.  Convenience function.   
        *
        * @param iid The work id.
        * @return HTML <img> tag of the thumbnail.  
        */
	function getThumbnail( $iid ) {
		return "<img src=\"img.php?img=" . $iid . "\" style=\"width:75px;\" alt=\"[Thumbnail]\"/>";
	}

	/**
        * getReserve: Tell us the reserve price of a given auction. 
        *
        * @param iid The auction id (primary key of auctions table).
        * @return Reserve price (automatically 0 if there is no reserve).
        */
	function getReserve( $iid ) {
		global $dbh;
		$userQuery = $dbh->prepare( "select reserve from auctions where id = ?" );
		$userQuery->bindParam( 1, $iid );
		$userQuery->execute( );
                while( $row = $userQuery->fetch( ) ) { return $row['reserve']; }
	}

        /**
        * didAuctionMeetReserve: Determine whether bidding for a specified auction has met the reserve price.
        *
        * @param iid The auction id.
        * @return 1/0 (yes/no).
        */
	function didAuctionMeetReserve( $iid ) {
		global $dbh;
		$userQuery = $dbh->prepare( "select bids.amt,auctions.reserve from bids join auctions on bids.aid = auctions.id where bids.aid = ? order by amt desc limit 1" );
                $userQuery->bindParam( 1, $iid );
                $userQuery->execute( );

                while( $row = $userQuery->fetch( ) ) { return ( ( $row['amt'] >= $row['reserve'] ) ? 1 : 0 ); }
	}

	// Code smell: all these auction funcs should just return one object w/addressable data for
	// bidder, last bid, high bid amt.  I.e. they should be collapsed into one.  

        /**
        * getHighBidderForAuction: Tell us who's the high bidder for the specified auction.   
        *
        * @param iid the auction id.
        * @return username (NB *not* the user ID) of the high bidder, or "Nobody" if no one has bid yet.
        */
        function getHighBidderForAuction( $iid ) {
		global $dbh;
                $userQuery = $dbh->prepare( "SELECT uid,aid,amt FROM bids WHERE aid = ? ORDER BY amt DESC LIMIT 1" );
                $userQuery->bindParam( 1, $iid );
                $userQuery->execute( );

                while( $row = $userQuery->fetch( ) ) { return getUsername( $row['uid'] ); }

                return "Nobody";
        }

        /**
        * getMinimumBidForAuction: does what it says. 
        *
        * @param iid The auction id
        * @return The minimum bid, which is 5 by default.
        */
	function getMinimumBidForAuction( $iid ) {
                global $dbh;
                $userQuery = $dbh->prepare( "SELECT initial_bid FROM auctions WHERE id = ? LIMIT 1" );
                $userQuery->bindParam( 1, $iid );
                $userQuery->execute( );

                while( $row = $userQuery->fetch( ) ) { return $row['initial_bid']; }
	}

        /**
        * getSellerIdForAuction: does what it says. 
        *
        * @param iid The auction id
        * @return The UID of the seller, or -1 if something is bonkers and we don't have a seller...
        */

	function getSellerIdForAuction( $iid ) {
	      	global $dbh;
                $userQuery = $dbh->prepare( "SELECT uid FROM auctions WHERE id = ? LIMIT 1" );
                $userQuery->bindParam( 1, $iid );
                $userQuery->execute( );

                while( $row = $userQuery->fetch( ) ) { return $row['uid']; }
                return -1;	
	}

        /**
        * getLastBidTimeForAuction: does what it says. 
        *
        * @param iid The auction id
        * @return Epoch time of the last bid for this auction. 
        */
	function getLastBidTimeForAuction( $iid ) {
		global $dbh;
		$userQuery = $dbh->prepare( "SELECT aid,amt,unix_timestamp(ts) as ts FROM bids where aid=? ORDER BY amt desc limit 1" );
                $userQuery->bindParam( 1, $iid );
                $userQuery->execute( );
                while( $row = $userQuery->fetch( ) ) { return ( $row['ts'] ); }

                return "[Unknown]";	
	}

        /**
        * getHighBidAmountForAuction: does what it says. 
        *
        * @param iid The auction id
        * @return The high bid, or 0 if there aren't any bids yet.
        */
        function getHighBidAmountForAuction( $iid ) {
		global $dbh;
                $userQuery = $dbh->prepare( "SELECT uid,aid,amt FROM bids WHERE aid = ? ORDER BY amt DESC LIMIT 1" );
                $userQuery->bindParam( 1, $iid );
                $userQuery->execute( );
        
                while( $row = $userQuery->fetch( ) ) { return ( $row['amt'] ); }
        
                return "0";
        }    

        /**
        * getIconForEventType: given an event type (see codes above), return the filename of the
	* associated icon (for use in newsfeed, jewel, etc). 
        *
        * @param eType the event code. 
        * @return The associated filename (from the event_types table), or [Error].
        */
	function getIconForEventType( $eType ) {
		global $dbh;
		$query = $dbh->prepare( "SELECT icon FROM event_types WHERE type = ? LIMIT 1" );
		$query->bindParam( 1, $eType );
		$query->execute( );
		while( $row = $query->fetch( ) ) { return $row['icon']; }
		return "[Error]";
	}

        /**
        * displayEvent: Generate a simple HTML div for the activity feed, given a row from the events
	* table as a PHP array. 
        *
        * @param row Array representing a row of the events table. 
	* @param context Deprecated
	* @return HTML representation of this even.
        */
	function displayEvent( $row, $context ) {
	
		$output = "<div class=\"activityFeed\">";

		$output .= "<div class=\"feedicon\"><img src=\"resources/icons/" . getIconForEventType( $row['type'] ) . "\"/></div>";
		$output .= "<div class=\"feedtitle\">";
		$output .= "<b>" . $row['headline'] . "</b><br/>";
		$output .= "<span style=\"font-size:small;color:gray;\">" . $row['date'] . "</span>";
		$output .= "</div>";	
		$output .= "<div class=\"feedbody\">";
		$output .= stripslashes( $row['description'] );
		$output .= "</div>";

		$output .= "</div>";

		return $output;

	}

        /**
        * adjustPoints: increase or decrease a user's points (FCGs). 
        *
        * @param user The user's id (passing -1 as $user will affect all players). 
	* @param amount The amount, positive or negative.
        */
	function adjustPoints( $user, $amount ) {
		global $dbh;

		// -1 = everyone
		if ( $user == -1 )
		{
                        $query = $dbh->prepare( "UPDATE collectors SET points = (points + ?)" );
                        $query->bindParam( 1, $amount );
                        $query->execute( );
		} else {
                	$query = $dbh->prepare( "UPDATE collectors SET points = (points + ?) WHERE id = ?" );
                	$query->bindParam( 1, $amount );
			$query->bindParam( 2, $user );
                	$query->execute( );
		}
	}

        /**
        * createUser: does what it says.  Does not, however, actually create their collection table; that happens
	* when the game supervisor distributes initial collections. 
	*
	* A (bad) assumption here: input arriving at this function has already been validated elsewhere.  Hm. 
        *
        * @param name The user's name.
	* @param pw Password, in plaintext glory, but soon to be encrypted by MD5. 
	* @param email The user's email address.
	* @param ok_to_use_record Boolean value indicating whether the player consented to have his/her gameplay
	*   recorded and used in any research, visualizations, models of this gameplay, etc.  
        */
	function createUser( $name, $pw, $email, $consent ) {
		global $dbh;
		$query = $dbh->prepare( "INSERT INTO collectors(name,email,password,ok_to_use_record) VALUES(?, ?, MD5(?), ?)" );
		$query->bindParam( 1, $name );
		$query->bindParam( 2, $email );
		$query->bindParam( 3, $pw );
		$query->bindParam( 4, $consent );

		$query->execute( );

		// Also need to register their collection.  The gameinstance stuff is deprecated but still here...
		$query = $dbh->prepare( "INSERT INTO collections(owner,name,gameinstance) VALUES(?,?,?)" );
		$ui = getUserId( $name );
		$cg = getCurrentGameInstance( );
		$query->bindParam( 1, $ui );
		$query->bindParam( 2, $name );
		$query->bindParam( 3, $cg );

		$query->execute( );

		// Notify all users that this player has joined
		createNotification( -1, 6, $name . " has joined the game." );
	}

        /**
        * getCurrentGameInstance: returns the instance number of the current game. 
        *
        * @return The instance id of the active game
	*
	* @deprecated 10/2012
        */
	function getCurrentGameInstance( ) {
		global $dbh;
		
		$stmt = $dbh->prepare("SELECT * FROM games WHERE UNIX_TIMESTAMP( ended ) = 0 LIMIT 1" );
		$stmt->execute( );

		while( $row = $stmt->fetch( ) ) { return $row['id']; }
	}

	/**
        * checkPassword: Given a username and password, see if the guess is correct.   
	* 
	* The security / pw handling in this game is kind of flimsy.  MD5 is weak, but a worse problem
	* is that pws are always passed in plaintext to the server...
        *
        * @param user The username.
	* @param guess The password guess.
        * @return Boolean (true  = guess was correct for this user) 
        */
	function checkPassword( $user, $guess ) {
		global $dbh;
		$query = $dbh->prepare( "SELECT name,password FROM collectors WHERE name=? AND password=MD5(?)" );
		$query->bindParam( 1, $user );
		$query->bindParam( 2, $guess );

		$query->execute( );

		return ( $query->rowCount( ) > 0 ? true : false );
	}

        /**
        * getPoints: returns a user's current score (FCGs). 
        *
        * @param user the user id. 
        * @return The current score for this user, or [Error], which isn't a number at all, is it?
        */
	function getPoints( $user ) {
		global $dbh;
                $query = $dbh->prepare( "SELECT points FROM collectors WHERE id = ? LIMIT 1" );
                $query->bindParam( 1, $user );
                $query->execute( );
                while( $row = $query->fetch( ) ) { return $row['points']; }
                return "[Error]";
        }

        /**
        * getAuctionEnd: given an auction ID, return the end time for it.   
        *
        * @param aid The auction id
        * @return The end time, which is a MySQL timestamp (not Unix epoch).
        */
	function getAuctionEnd( $aid ) {
                global $dbh;
                $query = $dbh->prepare( "SELECT end FROM auctions WHERE id = ? LIMIT 1" );
                $query->bindParam( 1, $aid );
                $query->execute( );
                while( $row = $query->fetch( ) ) { return ( $row['end'] ); }
	}

        /**
        * endAuctionBIN: end an auction that resulted in a buy it now purchase.   
        *
	* @param auctionId The auction id (primary key of auctions table)
	* @param user User ID of the player placing this bid
	* @param bidAmt The amount of the BIN purchase
	* @param gameinstance deprecated, but here so that other moving parts move correctly.
        */
	function endAuctionBIN( $auctionId, $user, $bidAmt, $gameinstance ) {
		global $dbh;

		// Place a bid using the normal bidding function, but then...
	        placeBidFixedEnd( $auctionId, $user, $bidAmt, $gameinstance );

		// ... drop the scheduled events created by that function, since the auction is
		// ending now.  
		$query = $dbh->prepare( "DROP EVENT IF EXISTS auctionEnd" . $auctionId );
                $query->execute( );
                $query = $dbh->prepare( "DROP EVENT IF EXISTS notifyWinner" . $auctionId );
                $query->execute( );
                $query = $dbh->prepare( "DROP EVENT IF EXISTS notifySeller" . $auctionId );	
		$query->execute( );

		// Set the auction end time to NOW().	
		$query = $dbh->prepare( "UPDATE auctions SET end = NOW() WHERE id = ?" );
		$query->bindParam( 1, $auctionId );
		$query->execute( );

		// Find the work ID associated with this auction...	
		$query = $dbh->prepare( "SELECT wid FROM auctions WHERE id = ? LIMIT 1" );
	        $query->bindParam( 1, $auctionId );
                $query->execute( );
		$workid = -1;
                while( $row = $query->fetch( ) ) {
			$workid = $row['wid'];
		}
			// Give the high bidder the work...
			$subq = $dbh->prepare( "INSERT INTO " . $user . "_" . $gameinstance . "_coll VALUES(?)" );
			$subq->bindValue( 1, $workid );
			$subq->execute( );

			// ...and take it from the seller.	
			$subs= $dbh->prepare( "DELETE FROM " . getSellerIdForAuction( $auctionId ) . "_" . $gameinstance . "_coll WHERE work=?" );
			$subs->bindValue( 1, $workid );
			$subs->execute( );

			// Remove the work from any other pending transactions.	
 			clearWorkFromOtherTransactions( $workid );

		// Assess a final value fee of 10% (roughly) the sale price and transfer bidder's FCGs to
		// seller.  
		$finalVal = floor( $bidAmt * 0.10 );
		adjustPoints( getSellerIdForAuction( $auctionId ), $bidAmt );
		adjustPoints( getSellerIdForAuction( $auctionId ), -$finalVal );
		adjustPoints( $user, -$bidAmt ); 
	}


        /**
        * getAuctionBIN: Get the buy-it-now price for a given auction. 
        *
        * @param aid The auction id
        * @return Buy-now price, or 0 (default).
        */
	function getAuctionBIN( $aid ) {
                global $dbh;
                $query = $dbh->prepare( "SELECT bin FROM auctions WHERE id = ? LIMIT 1" );
                $query->bindParam( 1, $aid );
                $query->execute( );
                while( $row = $query->fetch( ) ) { return ( $row['bin'] ); }
	}

        /**
        * isAuctionStillActive: See if a given auction is still happening.  This function applies only to old-style
	* live auctions, which aren't currently supported (they'll return in the next version of FCN).
        *
        * @param aid The auction id
        * @return Boolean (true = auction is in progress)
        */
	function isAuctionStillActive( $aid ) {
		global $dbh;
                $query = $dbh->prepare( "SELECT COUNT(*) as ct FROM auctions WHERE id = ? AND pending = 1 LIMIT 1" );
                $query->bindParam( 1, $aid );
                $query->execute( );
                while( $row = $query->fetch( ) ) { return ( $row['ct'] == 1 ? true : false ); }
	}

        /**
        * isAuctionStillActiveFixedEnd: See if an eBay-style auction (fixed end time, continuous bidding) has ended.
        *
        * @param aid The auction id
        * @return Boolean (true = still happening)
        */
        function isAuctionStillActiveFixedEnd( $aid ) {
                global $dbh;
                $query = $dbh->prepare( "SELECT COUNT(*) as ct FROM auctions WHERE id = ? AND end > NOW() LIMIT 1" );
                $query->bindParam( 1, $aid );
                $query->execute( );
                while( $row = $query->fetch( ) ) { return ( $row['ct'] == 1 ? true : false ); }
        }

	/**
	* placeBid: Enter a bid at auction.  Parameters are checked before being passed (in marketplace.php).
	*
	* A key operation here is the declaration of an auctionEnd EVENT that executes 15 seconds from the bid time to:
	* 
	*	- Drop the auctionEnd event if exists (to allow for continual updating)
	*	- Mark the auction as complete; record its high bidder, high bid amount, and end time
	* 	- Move the work in question into the high bidder's inventory
	*	- Transfer money as appropriate from the high bidder to the seller (less commission?)
	*	- Create another event that activates the next auction in +5 seconds, i.e., selects 1 work from the
	*	  auction pool w/pending = 2  and updates it to pending = 1
	*
	* If there are no works with pending=1, then the auction session is over.  
	*
	* This function applies only to the (temporarily-deprecated) live auction functionality.
	*
	* @param auc The auction id.
	* @param user The user id of the bidder.
	* @param amt The amount of this bid.
	* @param gi The game instance
	*/	
	function placeBid( $auc, $user, $amt, $gi ) {
		global $dbh;
		$utable = $user . "_" . $gi . "_coll";		// :(
		$query = $dbh->prepare( "INSERT INTO bids(uid, aid, amt) VALUES(?,?,?)");
		$query->bindParam( 1, $user );
		$query->bindParam( 2, $auc );
		$query->bindParam( 3, $amt );
		$query->execute( );
	
		// A successful bid means that we need to drop and recreate the end-of-auction events.
		$query = $dbh->prepare( "DROP EVENT IF EXISTS auctionEnd" );	
		$query->execute( );
		$query = $dbh->prepare( "DROP EVENT IF EXISTS nextAucTrigger" );
		$query->execute( );
		$query = $dbh->prepare( "DROP EVENT IF EXISTS notifyWinner" );
		$query->execute( );
		$query = $dbh->prepare( "DROP EVENT IF EXISTS notifySeller" );
		$query->execute( );

		// Make the buyer/seller notification events here.  Would be nice to notify admin about what's going on
		$wid = "";  $reserve = 0;  $selleruid = 0;

		$w = $dbh->prepare( "SELECT uid,wid,reserve FROM auctions WHERE id=?" );
		$w->bindParam( 1, $auc );
		$w->execute( );

		while( $row = $w->fetch( ) ) {
			$wid = $row['wid'];
			$selleruid = $row['uid'];	
			$reserve = $row['reserve'];
		}

		$sellertable = $selleruid . "_" . $gi . "_coll";

	if ( $amt < $reserve ) {

		// Create an event that returns the work to seller in event of failed auction.  
		// Why 15 seconds from now?  b/c 15 seconds of idleness = end of auction.  Any other bids
		// before then would drop this event, so it won't execute in case somebody else places another
		// bid.  Same goes for the following events. 
                $z = $dbh->prepare( "CREATE EVENT auctionEnd ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL 15 SECOND DO BEGIN UPDATE auctions SET pending=0,winner=-1,highbid=?,end=NOW() WHERE id=?;SELECT uid,wid,highbid INTO @uid,@wid,@high FROM auctions WHERE id=?;INSERT INTO " . $sellertable . " VALUES(@wid); END" );
                $z->bindParam( 1, $amt );
                $z->bindParam( 2, $auc );
                $z->bindParam( 3, $auc );
                $z->execute( );

		// ...and one that notifies the seller of this failure...
                $ye = $dbh->prepare( "CREATE EVENT notifySeller ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL 16 SECOND DO BEGIN SELECT uid INTO @uid FROM auctions WHERE id = ?; INSERT INTO notifications(type,text,target) VALUES(10,'Your work failed to sell at auction!  It has been returned to your collection.',@uid); END" );
                $ye->bindParam( 1, $auc );
                $ye->execute( );

		// ..and one that notifies the "Winner" that his/her bid wasn't high enough.
		$ze = $dbh->prepare( "CREATE EVENT notifyWinner ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL 16 SECOND DO BEGIN INSERT INTO notifications(type,text,target) VALUES(10, 'Your high bid failed to meet the reserve price!', " . $user . "); END" );
		$ze->execute( );
	
	} else { 
		// Same as above, but successful: reserve was met.
		$z = $dbh->prepare( "CREATE EVENT auctionEnd ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL 15 SECOND DO BEGIN UPDATE auctions SET pending=0,winner=?,highbid=?,end=NOW() WHERE id=?;SELECT winner,uid,wid,highbid INTO @winner,@uid,@wid,@high FROM auctions WHERE id=?;INSERT INTO " . $utable . " VALUES(@wid);UPDATE collectors SET points = (points - @high) WHERE id=@winner;UPDATE collectors SET points = ( points + @high ) WHERE id=@uid; END" );
		$z->bindParam( 1, $user );
		$z->bindParam( 2, $amt );
		$z->bindParam( 3, $auc );
		$z->bindParam( 4, $auc );
		$z->execute( );

		// Nofity seller of sucessful auction
                $ye = $dbh->prepare( "CREATE EVENT notifySeller ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL 16 SECOND DO BEGIN SELECT uid INTO @uid FROM auctions WHERE id = ?; INSERT INTO notifications(type,text,target) VALUES(9,'Your work sold at auction!',@uid); END" );
                $ye->bindParam( 1, $auc );
                $ye->execute( );

		// Notify the winner
                $ze = $dbh->prepare( "CREATE EVENT notifyWinner ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL 16 SECOND DO BEGIN INSERT INTO notifications(type,text,target) VALUES(9, 'Your high bid met the reserve price.  You won an auction!', " . $user . "); END" );
                $ze->execute( );
		
		// And remove work from any other pending transactions.  
 		clearWorkFromOtherTransactions( $wid );
	  } 
	}


        /**
        * placeBidFixedEnd: Enter a bid at auction.  Parameters are checked before being passed (in marketplace.php).
        * This function is used in the current auction system; placeBid() is the corresponding function for the live/
	* rapid auction system, which may be re-implemented in future releases.
	*
        * @param auc The auction id.
        * @param user The user id of the bidder.
        * @param amt The amount of this bid.
        * @param gi The game instance
        */
        function placeBidFixedEnd( $auc, $user, $amt, $gi ) {
                global $dbh;
                $utable = $user . "_" . $gi . "_coll";          // :(

		// insert the bid data into the bids table 
                $query = $dbh->prepare( "INSERT INTO bids(uid, aid, amt) VALUES(?,?,?)");
                $query->bindParam( 1, $user );
                $query->bindParam( 2, $auc );
                $query->bindParam( 3, $amt );
                $query->execute( );

		// Drop these events because they're now obsolete (there's a new high bid)
                $query = $dbh->prepare( "DROP EVENT IF EXISTS auctionEnd" . $auc );
                $query->execute( );
                $query = $dbh->prepare( "DROP EVENT IF EXISTS notifyWinner" . $auc ); 
                $query->execute( );
                $query = $dbh->prepare( "DROP EVENT IF EXISTS notifySeller" . $auc );
                $query->execute( );
        
                $wid = "";  $reserve = 0;  $selleruid = 0;
      
		// Find out the user, work id, and reserve amount of this auction          
                $w = $dbh->prepare( "SELECT uid,wid,reserve FROM auctions WHERE id=?" );
                $w->bindParam( 1, $auc );
                $w->execute( );

                while( $row = $w->fetch( ) ) {
                        $wid = $row['wid'];
                        $selleruid = $row['uid'];
                        $reserve = $row['reserve'];
                }

                $sellertable = $selleruid . "_" . $gi . "_coll";

        if ( $amt < $reserve ) {
		// Scheduled event that specifies no winner @ auction end.
                $z = $dbh->prepare( "CREATE EVENT auctionEnd" . $auc . " ON SCHEDULE AT '" . getAuctionEnd( $auc ) . "' DO BEGIN UPDATE auctions SET pending=0,winner=-1,highbid=?,end=NOW() WHERE id=?;SELECT uid,wid,highbid INTO @uid,@wid,@high FROM auctions WHERE id=?; END" );
                $z->bindParam( 1, $amt );
                $z->bindParam( 2, $auc );
                $z->bindParam( 3, $auc );
                $z->execute( );

		// Event that notifies seller of the failure to meet reserve...
                $ye = $dbh->prepare( "CREATE EVENT notifySeller" . $auc . " ON SCHEDULE AT '" . getAuctionEnd( $auc ) . "' DO BEGIN SELECT uid INTO @uid FROM auctions WHERE id = ?; INSERT INTO notifications(type,text,target) VALUES(10,'Your work failed to sell at auction!  It has been returned to your collection.',@uid); END" );
                $ye->bindParam( 1, $auc );
                $ye->execute( );

		// And the event that notifies the would-be winner of the same.
                $ze = $dbh->prepare( "CREATE EVENT notifyWinner" . $auc . " ON SCHEDULE AT '" . getAuctionEnd( $auc ) . "' DO BEGIN INSERT INTO notifications(type,text,target) VALUES(10, 'Your high bid failed to meet the reserve price!', " . $user . "); END" );
                $ze->execute( );

        } else {
		// Successful sale.  
                $z = $dbh->prepare( "CREATE EVENT auctionEnd" . $auc . " ON SCHEDULE AT '" . getAuctionEnd( $auc ) . "' DO BEGIN UPDATE auctions SET pending=0,winner=?,highbid=? WHERE id=?;SELECT winner,uid,wid,highbid INTO @winner,@uid,@wid,@high FROM auctions WHERE id=?;INSERT INTO " . $utable . " VALUES(@wid);DELETE FROM " . $sellertable . " WHERE work = @wid;UPDATE collectors SET points = (points - @high) WHERE id=@winner;UPDATE collectors SET points = ( points + FLOOR(@high * .9) ) WHERE id=@uid; END" );
                $z->bindParam( 1, $user );
                $z->bindParam( 2, $amt );
                $z->bindParam( 3, $auc );
                $z->bindParam( 4, $auc );
                $z->execute( );

                $ye = $dbh->prepare( "CREATE EVENT notifySeller" . $auc . " ON SCHEDULE AT '" . getAuctionEnd( $auc ) . "' DO BEGIN SELECT uid INTO @uid FROM auctions WHERE id = ?; INSERT INTO notifications(type,text,target) VALUES(9,'Your work sold at auction!',@uid); END" );
                $ye->bindParam( 1, $auc );
                $ye->execute( );

                $ze = $dbh->prepare( "CREATE EVENT notifyWinner" . $auc . " ON SCHEDULE AT '" . getAuctionEnd( $auc ) . "' DO BEGIN INSERT INTO notifications(type,text,target) VALUES(9, 'Your high bid met the reserve price.  You won an auction!', " . $user . "); END" );
                $ze->execute( );	
	
		// Remove work from any other pending transactions.  	
		clearWorkFromOtherTransactions( $wid );

          }
        }


        /**
        * clearWorkFromOtherTransactions: This function is called when a work sells at auction, for example,
	* or is successfully traded to another player.  We want to remove the work from any pending trades/sales
	* so that we don't wind up with duplicates in the game.  
        *
        * @param wid The work to clear from all pending transactions.
        */	
	function clearWorkFromOtherTransactions( $wid ) {
		global $dbh;

		// Cancel outstanding trades that include this work, either as an offered or requested piece.
                $query = $dbh->prepare( "update trades set accepted=-99 where accepted=-1 AND (work_from_origin LIKE '%?%' OR work_from_destination LIKE '%?%'");
                $query->bindParam( 1, $wid );
                $query->bindParam( 2, $wid );
                $query->execute( );

		// Find any auctions that include this number.  Ordering by id (descending) ensures that we get
		// only the most recent auction that involves this work.  A more sane way to do this would probably
		// be to see if the auction is actually happening or if it's over...
		$query = $dbh->prepare( "select id from auctions where wid=? order by id desc limit 1" );
		$query->bindParam( 1, $wid );
		$query->execute( );

		$theAuctionId = -1;

		while( $row = $query->fetch( ) ) {
			$theAuctionId = $row['id'];
		}

		// End the auction and drop any scheduled events associated with it
		$query = $dbh->prepare( "update auctions set end=NOW() where id=?" );
		$query->bindParam( 1, $theAuctionId );
		$query->execute( );

                $query = $dbh->prepare( "DROP EVENT IF EXISTS auctionEnd" . $theAuctionId );
                $query->execute( );
                $query = $dbh->prepare( "DROP EVENT IF EXISTS notifyWinner" . $theAuctionId );
                $query->execute( );
                $query = $dbh->prepare( "DROP EVENT IF EXISTS notifySeller" . $theAuctionId );
                $query->execute( );
	}

        /**
        * createNotification: Convenience function for inserting a notification into the jewel newsfeed.   
        *
        * @param uid The user to notify.  -1 = all users.
	* @param type The type of notification (see above for possible values).
	* @param text The text of the notification.  Theoretically, this can be up to 256 characters, but
	*   the notification div is fairly narrow, so we want to stick with short notification text.
        */
	function createNotification( $uid, $type, $text ) {
		global $dbh;
		$query = $dbh->prepare( "INSERT INTO notifications(target,type,text) VALUES(?,?,?)" );
		$query->bindParam( 1, $uid );
		$query->bindParam( 2, $type );
		$query->bindParam( 3, $text );
		$query->execute( );
	}

        /**
        * workHasTombstone: Find out whether a given work has a tombstone (usually so we can display it). 
        *
	* @param wid The work id; primary key of the works table.
	* @return Boolean
        */
	function workHasTombstone( $wid ) {
		global $dbh;
		$query = $dbh->prepare( "select count(id) as c from tombstones where wid = ?" );
		$query->bindParam( 1, $wid );
		$query->execute( );
                while( $row = $query->fetch( ) ) { return ( $row['c'] > 0 ? true : false ); }
	}


	/**
	* getTombstoneOrNot: Return the tombstone for a work, or just the string "a work" if it doesn't have
	* tombstone data.
	*
	* @param wid The work id
	* @param brief Whether to use brief mode (i.e., just the title)
	* @return Tombstone, or the text "a work" 
	*/
	function getTombstoneOrNot( $wid, $brief ) {
		global $dbh;
		return ( workHasTombstone( $wid ) ? getTombstone( $wid, $brief ) : "a work" );
	}

        /**
        * getTombstoneOrBlank: Same as above, but return blank text instead of "a work."  The number of functions
	* related to tombstones is a bit silly; it would be better to build this functionality into getTombstone() via
	* another parameter and then just refactor.  So...FIXME.
	* 
	* @wid The work id
	* @return Tombstone, or blank text 
        */
	function getTombstoneOrBlank( $wid ) {
		global $dbh;
		return ( workHasTombstone( $wid ) ? getTombstone( $wid, true ) : "" );
	}

        /**
        * getTombstone: Return the tombstone data for a given work id.  This data can include the artist name and his/her
	* dates of birth and death along with the work title.  
        *
        * @param wid The work id
	* @param brief Boolean value indicating whether we want just the work title (true) or complete information (false).
        * @return Tombstone data according to the value of $brief.
        */
	function getTombstone( $wid, $brief ) {
		global $dbh;
                $query = $dbh->prepare( "select uid_creator,artist,born,died,worktitle,workdate from tombstones where wid = ?" );
                $query->bindParam( 1, $wid );
                $query->execute( );
                while( $row = $query->fetch( ) ) { 
			if ( $brief == true ) {
				return "\"" . $row['worktitle'] . "\"";
			} else {
				return stripslashes( $row['artist'] ) . " (" . $row['born'] . " - " . $row['died'] . ")" . ", \"" . stripslashes( $row['worktitle'] ) . "\" (" . $row['workdate'] . ")";
			}
		}
	}

	/**
        * workHasDescription: convenience function to check whether a work has a user-generated description.  
        *
        * @param The work id
        * @return Boolean
        */
        function workHasDescription( $wid ) {
		global $dbh;
                $query = $dbh->prepare( "select count(id) as c from work_descriptions where work = ?" );
                $query->bindParam( 1, $wid );
                $query->execute( );
                while( $row = $query->fetch( ) ) { return ( $row['c'] > 0 ? true : false ); }
        }

        /**
        * getDescription: Get the description for a given work.
        *
        * @param wid The work id
        * @return The description text.
        */
        function getDescription( $wid ) {
		global $dbh;
                $query = $dbh->prepare( "select text from work_descriptions where work = ?" );
                $query->bindParam( 1, $wid );
                $query->execute( );
                while( $row = $query->fetch( ) ) {
			// nl2br = \n -> <br/>
                        return stripslashes( nl2br( trim( $row['text'] ) ) );
                }
        }

        /**
        * isAuctionPending: See if a timed/live auction is pending.  A convenience function for the previous
	* auction system.
        *
        * @return Boolean 
        */
	function isAuctionPending( ) {
		global $dbh;
	
		// Live auctions begin at a specified time and are recorded in the auction_groups table; if there's anything
		// there with a timestamp in the future, return true
		$query = $dbh->prepare( "select id,unix_timestamp(start) AS t from auction_groups WHERE unix_timestamp(start) > unix_timestamp(now())" );
		$query->execute( );
	
		// FIXME before release, here and elsewhere: rowCount() is specific to MySQL and limits portability
		// to other DBMSs.  Of course, the same is probably true of all the EVENT junk as well...
		return ( $query->rowCount( ) > 0 ? true : false );
	}

        /**
        * pendingAuctionId: Get the id (auction_groups primary key) of a pending auction.  Deprecated (part of live
	* auction system).  
        *
        * @return The id (primary key of auction_groups) of the next auction.  
        */
	function pendingAuctionId( ) {
		global $dbh;
		// Select the id and start time.  It's not possible to have more than one pending auction in this version
		// of the game.
                $query = $dbh->prepare( "select id,unix_timestamp(start) AS t from auction_groups WHERE unix_timestamp(start) > unix_timestamp(now())" );              
                $query->execute( );
		while ( $row = $query->fetch( ) ) {
			return $row['id'];
		}
	}

	/**
        * pendingAuctionDate: Get the start time/date of a pending auction in the live auction system.  Called from
	* the supervisor page.   
        *
        * @return Time of the auction (human-readable timestamp, not epoch).  
        */
        function pendingAuctionDate( ) {
		global $dbh;
                $query = $dbh->prepare( "select id,start from auction_groups WHERE unix_timestamp(start) > unix_timestamp(now())" );
                $query->execute( );
                while ( $row = $query->fetch( ) ) {
                        return $row['start'];
                }
        }

	/**
	* verificationCode: Generates a random 16-character verification code from some basic ASCII characters.  
	* For use in the hitherto unimplemented email verification system.
	*
	* @return 16-character verification code.
	*/
	function verificationCode( ) {
    		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    		$randomString = '';
    		for ($i = 0; $i < 16; $i++) {
        		$randomString .= $characters[rand(0, strlen($characters) - 1)];
    		}
    		return $randomString;
	}

	/**
	* isEmailValid: Uses regular expressions to determine whether an email address is valid or not.  Calls to
	* explode() are not wanting.  
	*
	* @param email The email address.  
	*/
	function isEmailValid( $email ) {
		// Take broadest possible view first: does this string even conform to the user@host format?	
		// If not, let's get out without wasting any more time.
  		if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
    			return false;
  		}
  	
		// Split the address by kerploding it around @	
		$email_array = explode("@", $email);

		// Assign everything left of @ to $local_array
  		$local_array = explode(".", $email_array[0]);
  
		// Iterate over each character in $local_array and see if it's a valid character for email addresses.	
		for ($i = 0; $i < sizeof($local_array); $i++) {
    			if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) {
      				return false;
    			}
  		}

		// Examine the string to the right of the @ 
		if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
    			$domain_array = explode(".", $email_array[1]);
			// If no TLD, get out
    			if (sizeof($domain_array) < 2) {
        			return false; 
    			}
			// Ensure that characters are valid.  Possible improvement: don't be so lax about the TLD
    			for ($i = 0; $i < sizeof($domain_array); $i++) {
      				if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) {
        				return false;
      				}
    			}
  		}
  	
		// At this point, we're fairly sure we have a well-formed email address. 98% sure.  
		return true;
	}

	/**
	* secondsToString: Convenience function that takes an arbitrary number of seconds and converts it
	* to a more human-readable metric of time (days/hours/minutes).  
	*
	* @param seconds The number of seconds.
	* @return Human-readable time.
	*/
	function secondsToString( $seconds )  {
		if ( $seconds > 60 ) {
                	$days = floor( $seconds / 86400 );
                	$hours = floor( ( ( $seconds % 86400 ) / 60 ) / 60 );
                	$minutes = ( ( ( $seconds % 86400 ) / 60 ) % 60 );
			return $days . ( $days === 1 ? " day," : " days, " ) . $hours . ( $hours === 1 ? "hour, and " :" hours, and " ) . $minutes . ( $minutes === 1 ? " minute" : " minutes" );
		} else
			return $seconds . ( $seconds === 1 ? " second" : " seconds" );
	}
?>
