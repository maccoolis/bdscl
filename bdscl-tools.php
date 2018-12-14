<?php
 /*

	  Plugin Name:  Tools for the calculation of results and stats for the Burton Snooker league
 
	  Plugin URI:  http://bdcsnooker.org
 
	  Description:  BDSCL tools plugin
	 
	  Version:      1.1

	  Author:       Carl Poulton
	 
	  License: GPLv3
	
*/
	
	global $rootlocal;
	// Set the default season to the season that is O OPEN
	$GLOBALS["seasonid"] = $wpdb->get_var("SELECT SeasonId FROM seasons WHERE StatusFlag = 'O'");
	$GLOBALS["seasondesc"] = $wpdb->get_var("SELECT SeasonDesc FROM seasons WHERE StatusFlag = 'O'");
	// Set the default player to the first in the listf
	$GLOBALS["playerid"] = "526";
	// Set the default team to be the first in the list for the global season
	$GLOBALS["teamid"] = $wpdb->get_var($wpdb->prepare("SELECT Min(TeamId) FROM teams WHERE SeasonId = %d" ,$GLOBALS["seasonid"]));
	// Set the default division to be the first in the list for the global season
	$GLOBALS["divisionid"] = $wpdb->get_var($wpdb->prepare("SELECT Min(CompetitionId) FROM competitions WHERE SeasonId = %d", $GLOBALS["seasonid"]));
	// Set the default club to be the first one
	$GLOBALS["clubid"] = "1";
	// Set the default team tournament to be the first one in the global season - format=2 signifies team tournament
	$GLOBALS["teamtournoid"] = $wpdb->get_var($wpdb->prepare("SELECT Min(TournamentId) FROM tournament WHERE SeasonId = %d AND Format = 2",$GLOBALS["seasonid"]));
	// Set the default player tournament to be the first one in the global season - format=2 signifies team tournament
	$GLOBALS["playertournoid"] = $wpdb->get_var($wpdb->prepare("SELECT Min(TournamentId) FROM tournament WHERE SeasonId = %d AND Format <> 2",$GLOBALS["seasonid"]));
	
	$rootlocal = "";	// "/bdscl" add the folder here if wordpress install is in folder under htdocs/www
	$bdsclurl = 'http://www.bdcsnooker.org/'; //used to set links across the site
	
	// If we need to show errors in the php
	$wpdb->show_errors();
	
	// LOAD PHP ajaxcrud. Ajaxcrud is used for all the administration tables
	include_once('php/preheader.php');
	include_once('php/ajaxCRUD.class.php');
		
	//LOAD JQUERY
	function jq_scripts() {
  		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
	}
	add_action('wp_enqueue_scripts', 'jq_scripts');
		
	//LOAD STYLES	
	function bdscl_tools_stylesheet() {
		wp_register_style( 'bdscl_tools_style', plugins_url('css/bdscl_tools.css', __FILE__),array() );
		wp_enqueue_style( 'bdscl_tools_style');
	}	
	add_action('wp_enqueue_scripts','bdscl_tools_stylesheet');
	
	//LOAD PLUGIN JAVASCRIPT FOR BDSCL
	function bdscl_tools_js() {
		wp_register_script( 'bdscl_tools_js', plugins_url('js/bdscl-tools.js', __FILE__),array() );
		wp_enqueue_script( 'bdscl_tools_js');
	}
	add_action('wp_enqueue_scripts','bdscl_tools_js');

	//LOAD SCROLL TEXT JAVASCRIPT
	function scrollingtext_js() {
		wp_register_script( 'scrollingtext_js', plugins_url('js/scrolltext_custom.js', __FILE__),array() );
		wp_enqueue_script( 'scrollingtext_js');
	}
	add_action('wp_enqueue_scripts','scrollingtext_js');
	
	//LOAD AJAX FILES
    // Enqueue and localise scripts
    /*add_action( 'wp_enqueue_scripts', 'wpajaxhandler_enqueue' );
	function wpajaxhandler_enqueue()
	{
	    // your enqueue will probably look different.
	    wp_enqueue_script( 'my-ajax-handle', plugin_dir_url(__FILE__ ) . 'js/bdscl-tools.js', array( 'jquery' ) );
	
	    // Localize the script
	    $data = array( 
	        'ajax_url' => admin_url( 'admin-ajax.php' ) //,
	        //'nonce'    => wp_create_nonce( 'wpajaxhandler_nonce' )
	    );
		wp_localize_script( 'my-ajax-handle', 'the_ajax_script', $data );
	}*/

	
     wp_enqueue_script( 'my-ajax-handle', plugin_dir_url(__FILE__ ) . 'js/bdscl-tools.js', array( 'jquery' ) );
	 wp_localize_script( 'my-ajax-handle', 'the_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )) );
    
    // THE AJAX ADD ACTIONS
    // For the league fixture update form
    add_action( 'wp_ajax_the_ajax_hook', 'ajax_return_function' );
    add_action( 'wp_ajax_nopriv_the_ajax_hook', 'ajax_return_function' ); // need this to serve non logged in users
    
    // AJAX For the team tournament form
    add_action( 'wp_ajax_the_ajax_teamtournodisplay', 'ajax_return_teamtournodisplay' );
    add_action( 'wp_ajax_nopriv_the_ajax_teamtournodisplay', 'ajax_return_teamtournodisplay' ); // need this to serve non logged in users
    
    // AJAX For the player tournament form
    add_action( 'wp_ajax_the_ajax_playertournodisplay', 'ajax_return_playertournodisplay' );
    add_action( 'wp_ajax_nopriv_the_ajax_playertournodisplay', 'ajax_return_playertournodisplay' ); // need this to serve non logged in users
	
	// AJAX For the division form
    add_action( 'wp_ajax_the_ajax_divisiondisplay', 'ajax_return_divisiondisplay' );
    add_action( 'wp_ajax_nopriv_the_ajax_divisiondisplay', 'ajax_return_divisiondisplay' ); // need this to serve non logged in users
    
    // AJAX For the team form
    add_action( 'wp_ajax_the_ajax_teamdisplay', 'ajax_return_teamdisplay' );
    add_action( 'wp_ajax_nopriv_the_ajax_teamdisplay', 'ajax_return_teamdisplay' ); // need this to serve non logged in users
    
    // AJAX For the player rankings form
    add_action( 'wp_ajax_the_ajax_rankingsdisplay', 'ajax_return_rankingsdisplay' );
    add_action( 'wp_ajax_nopriv_the_ajax_rankingsdisplay', 'ajax_return_rankingsdisplay' ); // need this to serve non logged in users
    
    // AJAX For the player form
    add_action( 'wp_ajax_the_ajax_playerdisplay', 'ajax_return_playerdisplay' );
    add_action( 'wp_ajax_nopriv_the_ajax_playerdisplay', 'ajax_return_playerdisplay' ); // need this to serve non logged in users
    
    // AJAX For the club form
    add_action( 'wp_ajax_the_ajax_clubsdisplay', 'ajax_return_clubsdisplay' );
    add_action( 'wp_ajax_nopriv_the_ajax_clubsdisplay', 'ajax_return_clubsdisplay' ); // need this to serve non logged in users
    
    // AJAX For the fixtures create form
    add_action( 'wp_ajax_the_ajax_createfixdisplay', 'ajax_return_createfixdisplay' );
    add_action( 'wp_ajax_nopriv_the_ajax_createfixdisplay', 'ajax_return_createfixdisplay' ); // need this to serve non logged in users
    
    // AJAX For the head to head form
    add_action( 'wp_ajax_the_ajax_headtoheaddisplay', 'ajax_return_headtoheaddisplay' );
    add_action( 'wp_ajax_nopriv_the_ajax_headtoheaddisplay', 'ajax_return_headtoheaddisplay' ); // need this to serve non logged in users
    
    // AJAX For the results by week form
    add_action( 'wp_ajax_the_ajax_resultsbyweekdisplay', 'ajax_return_resultsbyweekdisplay' );
    add_action( 'wp_ajax_nopriv_the_ajax_resultsbyweekdisplay', 'ajax_return_resultsbyweekdisplay' ); // need this to serve non logged in users
    
    // AJAX for the competition entry form
    add_action( 'wp_ajax_ajax_add_compentry', 'ajax_add_compentry' );
    add_action( 'wp_ajax_nopriv_ajax_add_compentry', 'ajax_add_compentry' ); // need this to serve non logged in users

	// TEST AREA
	// ob_start(); // why ob... http://wordpress.stackexchange.com/questions/6730/custom-shortcode-in-widget-forced-to-top-of-widget
	//
	
	
	
	
	// -------------------- ADMINISTRATION FUNCTIONS START ----------------------------
	
	// Fixtures ------------------------------------------------------------------------
	add_shortcode('admin_fixtures_sc', 'admin_fixtures');
	function admin_fixtures(){
		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Fixtures button (right) to add a fixture in a particular season.
						 To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.<br><br> Please note
						that the Unplayed column should be set to 1 if a team drops out mid season. This is used to ensure
						Shipton 70% uses only valid fixtures.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the fixtures table
		$tblfixtures = new ajaxCRUD("Fixtures", "fixtures", "FixtureId");
		
		// Add the relationships so proper dropdowns from other tables appear				 
		$tblfixtures->defineRelationship("VenueClubId", "clubs", "ClubId", "ClubName", "ClubName ASC",1);
		$tblfixtures->defineRelationship("SeasonId", "seasons", "SeasonId", "SeasonDesc","SeasonId DESC,1");
		$tblfixtures->defineRelationship("CompetitionId", "competitions", "CompetitionId", "CONCAT(SeasonId, ': ', CompetitionName)","SeasonId DESC",1);
		$tblfixtures->defineRelationship("HomeTeamId", "teams", "TeamId", "CONCAT(SeasonId, ' ', TeamName)","TeamId DESC",1);
		$tblfixtures->defineRelationship("AwayTeamId", "teams", "TeamId", "CONCAT(SeasonId, ' ', TeamName)","TeamId DESC",1);
		
		// Add a select box at the top
		$tblfixtures->addAjaxFilterBox('SeasonId', 25);
		$tblfixtures->addAjaxFilterBox('CompetitionId', 25);
		$tblfixtures->addAjaxFilterBox('Weekno', 10);
		$tblfixtures->addAjaxFilterBox('HomeTeamId', 25);
		$tblfixtures->addAjaxFilterBox('AwayTeamId', 25);
		
		// User friendly descriptions for table columns
		$tblfixtures->displayAs("CompetitionId", "Competition");
		$tblfixtures->displayAs("SeasonId", "Season");
		$tblfixtures->displayAs("Weekno", "Week");
		$tblfixtures->displayAs("HomeTeamId", "Home team");
		$tblfixtures->displayAs("AwayTeamId", "Away team");
		$tblfixtures->displayAs("VenueClubId", "Club");
		
		// Add an order
		$tblfixtures->addOrderBy("ORDER BY SeasonId DESC, CompetitionId ASC, WeekNo ASC");
		
		// Show the table
		$tblfixtures->displayAddFormTop();
		$tblfixtures->showTable(); 
	}
	// Fixtures ------------------------------------------------------------------------
	
	// Players ------------------------------------------------------------------------
	add_shortcode('admin_players_sc', 'admin_players');
	function admin_players(){
		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Players by Team and Season button (right) to add a unique player to a team in a particular season.
						 To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the player table
		$tblplayers = new ajaxCRUD("Players by team and season", "players", "PlayerId");
		
		// Add the relationships so proper dropdowns from other tables appear
		$tblplayers->defineRelationship("UPlayerId", "uniqueplayers	", "UPlayerId", "CONCAT(Forename, ' ', Surname)","Surname ASC",1);					 
		$tblplayers->defineRelationship("ClubId", "clubs", "ClubId", "ClubName", "ClubName ASC",1);
		$tblplayers->defineRelationship("SeasonId", "seasons", "SeasonId", "SeasonDesc","SeasonId DESC,1");
		$tblplayers->defineRelationship("TeamId", "teams", "TeamId", "CONCAT(SeasonId, ' ', TeamName)","TeamId DESC",1);
		
		// Add a select box at the top
		$tblplayers->addAjaxFilterBox('SeasonId', 25);
		$tblplayers->addAjaxFilterBox('UPlayerId', 25);
		$tblplayers->addAjaxFilterBox('TeamId', 25);
		
		// User friendly descriptions for table columns
		$tblplayers->displayAs("UPlayerId", "Unique Player Name");
		$tblplayers->displayAs("SeasonId", "Season");
		$tblplayers->displayAs("TeamId", "Team Name");
		$tblplayers->displayAs("ClubId", "Club");
		
		// Add an order
		$tblplayers->addOrderBy("ORDER BY SeasonId DESC");
		
		// Show the table
		$tblplayers->displayAddFormTop();
		$tblplayers->showTable(); 
		
	}
	// Players end ----------------------------------------------------------------------
	
	// Unique Players ------------------------------------------------------------------------
	add_shortcode('admin_uniqueplayers_sc', 'admin_uniqueplayers');
	function admin_uniqueplayers(){
		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Unique Players button (right) to create a new player. An new player to the league must be added here first. To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the player table
		$tbluniqueplayers = new ajaxCRUD("Unique players", "uniqueplayers", "UPlayerId");
		$tbluniqueplayers->addAjaxFilterBox("Surname");
		
		// Add an order
		$tbluniqueplayers->addOrderBy("ORDER BY UPLayerId DESC");
		
		// Show the table
		$tbluniqueplayers->displayAddFormTop();
		$tbluniqueplayers->showTable();
	}
	// Unique Players end ----------------------------------------------------------------------
	
	// Teams ----------------------------------------------------------------------------
	add_shortcode('admin_teams_sc', 'admin_teams');
	function admin_teams(){		
		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Teams button (right) to create a new team. You need to add all teams that are competing in a season to this table.
					 To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the teams table
		$tblteams = new ajaxCRUD("Teams", "teams", "TeamId");
		
		// Add the relationships so proper dropdowns from other tables appear					 
		$tblteams->defineRelationship("ClubId", "clubs", "ClubId", "ClubName","ClubName ASC",1);
		$tblteams->defineRelationship("SeasonId", "seasons", "SeasonId", "SeasonDesc", "SeasonId DESC",1);
		// Add dropdown at the top
		$tblteams->addAjaxFilterBox('SeasonId', 15);
		// Add an order
		$tblteams->addOrderBy("ORDER BY SeasonId DESC");
		// Show the table
		// Don't display the primary key of the table
		$tblteams->displayAddFormTop();
		$tblteams->omitPrimaryKey();
		$tblteams->showTable();	
	}
	// Teams end -------------------------------------------------------------------------
	
	// Seasons  ----------------------------------------------------------------------------
	add_shortcode('admin_seasons2_sc', 'admin_seasons2');
	function admin_seasons2(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Seasons button (right) to create a new season. Set the season to (O) Open to make it the current 
						season and make sure old seasons are (C) Closed. To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
						
		$the_form = '
         <form id="theAdminSeasonsForm">';
         
         // GET THE SEASONS TO FILL THE TABLE
         $qrySeason = $wpdb->get_results("SELECT * FROM seasons ORDER BY SeasonId DESC");
             
         $the_form = $the_form . '      
         <div id="wrapper">';
        
         foreach($qrySeason as $season) {
         				
         }
		//Now put the team drop down in
		
       	$the_form = $the_form . ' 
         <input name="action" type="hidden" value="the_ajax_adminseasons" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form -->
         </form><br>';
		 
		 echo $the_form;
	}
		
		
	add_shortcode('admin_seasons_sc', 'admin_seasons');
	function admin_seasons(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Seasons button (right) to create a new season. Set the season to (O) Open to make it the current 
						season and make sure old seasons are (C) Closed. To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";

		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the seasons table
		$tblseasons = new ajaxCRUD("Seasons", "seasons", "SeasonId");

		// User friendly descriptions for table columns
		$tblseasons->displayAs("StatusFlag", "Open (O) or Closed (C)");
		$tblseasons->displayAs("SeasonDesc", "Season name");
		
		// Don't display the primary key of the table
		$tblseasons->omitPrimaryKey();
		// Add an order
		$tblseasons->addOrderBy("ORDER BY SeasonId DESC");
		// Show the table
		$tblseasons->displayAddFormTop();
		$tblseasons->showTable();
	}
	// Seasons end -------------------------------------------------------------------------
	
	// Fixture creation  ----------------------------------------------------------------------------
	add_shortcode('admin_fixturecreate_sc', 'admin_fixturecreate');
	function admin_fixturecreate(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];

		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		// GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
	     $qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons ORDER BY SeasonId DESC");
		 // The competitions
	     $qryComps =  $wpdb->get_results($wpdb->prepare("SELECT CompetitionId, CompetitionName, NrOfTeams FROM competitions WHERE SeasonId = %d",$GLOBALS["seasonid"]));
		 // The divisions
		 $nrDivs =  $wpdb->get_var($wpdb->prepare("SELECT Count(CompetitionId) FROM competitions WHERE SeasonId = %d",$GLOBALS["seasonid"]));
		 // Find the maximum number of teams
		 $maxTeams =  $wpdb->get_var($wpdb->prepare("SELECT Max(NrOfTeams) FROM competitions WHERE SeasonId = %d",$GLOBALS["seasonid"]));
		 
		 
		if (isset($_POST['submit'])) {
			// The create fixtures button has been clicked so we can go and create them...
			
			// We need to validate again as we can't create fixtures with blanks and duff info
		    $validationmessage = "";
			$returntext = array();
			// Lets get the week values and add them to the returntext array
        	for ($i=1; $i < ($maxTeams*2)+1; $i++) {
        		// First make a note if any of the fields are blank for validation purposes
			 	if (empty($_POST['date' . $i])) {
			 		$validationmessage = $validationmessage . "Week " . $i . " date is blank.<br>";
			 	} else {
			 		$returntext['Week' . $i] = $_POST['date' . $i];
			 	}
		 	}
			
			// Now lets get the teams and validate for blanks
			for ($i=1; $i < $maxTeams+1; $i++) {
			 	for ($j=1; $j < $nrDivs+1; $j++) {
			 		// First make a note if any of the fields are blank for validation purposes
			 		if (empty($_POST['teamd' . $i . 't' . $j])) {
			 			$validationmessage = $validationmessage . "Team " . $i . " in the " . $j . " division is blank.<br>";
			 		} else {
			 			$returntext['TeamP' . $i . 'D' . $j] = $_POST['teamd' . $i . 't' . $j];
			 		}
			 	} 
		 	}
			// Now check for duplicates in the array
			if (count($returntext) === count(array_unique($returntext))) {
				// No duplicates
			} else {
				// Duplicates
				$validationmessage = $validationmessage . "There are duplicate week dates or teams in one or more of your divisions or week dates";
			}
			
	        // Now for the validation check
	        if (empty($validationmessage)) {
	        	$success = "Success";
			} else {
				$success = "Fail";
	        }
			
	        //Lets pop a message up now if theres a fail
			if ($success == "Fail"){
				echo '<script type="text/javascript">alert("Validation issues:' . $validationmessage . '");</script>';
			};
			
		} else {
			
			// Put the instructions up and starting text
			$instructions = "<br><br>Steps to follow before creating fixtures:<br>1. Create new season.<br>2. Add divisions to that season.<br>3. Add all teams in league to that season.<br>4. Add teams to relevant division
							<br>5. Below put in the week dates for the season<br>6. Put the teams for each division into the relevant slots</div>";
			echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
			
			// Check the global seasonid so we can prefill the team table
			if (isset($_GET['seasonid'])) {
	    		$GLOBALS["seasonid"] = $_GET['seasonid'];
			}
			
			$the_form = '
	             <form id="theCreateFixturesForm" method="post" action="">';
	            				 
	             $the_form = $the_form . '      
	             <div id="wrapper">
	             <label>Season:</label>
	             <select autofocus name="season-list" id="season-list" onchange="document.getElementById(\'fixcreatehidden\').value=\'Season changed\';createfix_select(\'season\')">
	             <option value="">Select Season</option>';
	            
	             foreach($qrySeason as $season) {
	             				// Check the global season so we can set it to selected
								$selected = '';
								if ($GLOBALS["seasonid"] === $season->SeasonId){
									$selected = 'selected = "selected" ';
								}
	                            $the_form = $the_form . '   
	                            <option ' . $selected . 'value="' . $season->SeasonId . '">' . $season->SeasonDesc .'</option>';
	             }
				  $the_form = $the_form . '</select><br><br>';
				  
				// Put the weeks in a table so the dates can be added
				$the_form = $the_form .'<div id="thefixtable"><table class="ftab-fixcreatetable">
										<thead><tr>
										<th class="ftab-weekno">Week No.</th>
										<th class="ftab-date">Week Date</th>
										</tr></thead><tbody>';
				
				 for ($i=1; $i < ($maxTeams*2)+1; $i++) {
					$the_form = $the_form .
						'<tr>
							<td class="ftab-weekno">' . $i . '</td>
							<td class="ftab-date"><input type="date" id="date' . $i . '" name="date' . $i . '"></input></td>';
	 				$the_form = $the_form . '</tr>';
			 	}
				$the_form = $the_form . '</tbody></table>';
				// Now out the rules in about matching teams so they avoid each other on home nights
				if ($maxTeams == 16){
					$the_form = $the_form . '<div>Match team pairs in the numbers below so they do not cross: 1 and 16, 2 and 9, 3 and 10, 4 and 11, 5 and 12, 6 and 13, 7 and 14, 8 and 15</div>';
				}
				if ($maxTeams == 14){
					$the_form = $the_form . '<div>Match team pairs in the numbers below so they do not cross: 1 and 8, 2 and 9, 3 and 10, 4 and 11, 5 and 12, 6 and 13, 7 and 14</div>';
				}
				if ($maxTeams == 12){
					$the_form = $the_form . '<div>Match team pairs in the numbers below so they do not cross: 1 and 12, 2 and 7, 3 and 8, 4 and 9, 5 and 10, 6 and 11</div>';
				}
				if ($maxTeams == 10){
					$the_form = $the_form . '<div>Match team pairs in the numbers below so they do not cross: 1 and 10, 2 and 6, 3 and 7, 4 and 8, 5 and 9</div>';
				}
				$the_form = $the_form . '';
				
				$the_form = $the_form .'<table class="ftab-fixcreatetable">
										<thead><tr>
										<th class="ftab-weekno">Team No.</th>';
				
				$divs = array();
				foreach($qryComps as $Comp) {
				 	 $the_form = $the_form .'<th class="ftab-team">' . $Comp->CompetitionName . '</th>';
					 array_push($divs,$Comp->CompetitionId);
				 }					
				$the_form = $the_form .	'</tr></thead><tbody>';
			
				 for ($i=1; $i < $maxTeams+1; $i++) {
				 	// Helpful formatting for the user so they can see which teams should be matched
				 	switch ($maxTeams){
						case 16:
							switch ($i){
								case 1:
								case 16:
									$pairclass = "pair1";
								break;
								case 2:
								case 9:
									$pairclass = "pair2";
								break;
								case 3:
								case 10:
									$pairclass = "pair3";
								break;
								case 4:
								case 11:
									$pairclass = "pair4";
								break;
								case 5:
								case 12:
									$pairclass = "pair5";
								break;
								case 6:
								case 13:
									$pairclass = "pair6";
								break;
								case 7:
								case 14:
									$pairclass = "pair7";
								break;
								case 8:
								case 15:
									$pairclass = "pair8";
								break;
							}
						break;
						case 14:
							switch ($i){
								case 1:
								case 8:
									$pairclass = "pair1";
								break;
								case 2:
								case 9:
									$pairclass = "pair2";
								break;
								case 3:
								case 10:
									$pairclass = "pair3";
								break;
								case 4:
								case 11:
									$pairclass = "pair4";
								break;
								case 5:
								case 12:
									$pairclass = "pair5";
								break;
								case 6:
								case 13:
									$pairclass = "pair6";
								break;
								case 7:
								case 14:
									$pairclass = "pair7";
								break;
							}
						break;
						case 12:
							switch ($i){
								case 1:
								case 12:
									$pairclass = "pair1";
								break;
								case 2:
								case 7:
									$pairclass = "pair2";
								break;
								case 3:
								case 8:
									$pairclass = "pair3";
								break;
								case 4:
								case 9:
									$pairclass = "pair4";
								break;
								case 5:
								case 10:
									$pairclass = "pair5";
								break;
								case 6:
								case 11:
									$pairclass = "pair6";
								break;
							}
						break;
						case 10:
							switch ($i){
								case 1:
								case 10:
									$pairclass = "pair1";
								break;
								case 2:
								case 6:
									$pairclass = "pair2";
								break;
								case 3:
								case 7:
									$pairclass = "pair3";
								break;
								case 4:
								case 8:
									$pairclass = "pair4";
								break;
								case 5:
								case 9:
									$pairclass = "pair5";
								break;
							}
						break;
				 	}
					
					$the_form = $the_form .
						'<tr>
							<td class="ftab-weekno">' . $i . '</td>';
							
				 	 for ($j=1; $j < $nrDivs+1; $j++) {
				 	 	 
						 //Now put the teams into dropdowns
						 $teamdropdown = getTeambyDivSelect($GLOBALS["seasonid"],$divs[$j-1]);
						 // Put in a rest week option for odd numbered teams in divisions - we put in a funny 999991/2/3 number so it can be identified as a rest week in the JS and the MySQL - a zero doesn't work!
						 $teamdropdown = $teamdropdown . '<option value="99999' . $j . '">Rest</option>';
						 
						 $the_form = $the_form . '<td class="ftab-team ' . $pairclass . '"><select name="teamd' . $i . 't' . $j . '" id="teamd' . $i . 't' . $j . '" class="team-list">';
						 $the_form = $the_form . $teamdropdown . '</select></td>';
				 	} 
	 				$the_form = $the_form . '</tr>';
			 	}
				 $the_form = $the_form . '</tbody></table></div>
				 						 <input name="save" id="save" type="button" class="button" onClick="document.getElementById(\'fixcreatehidden\').value=\'Validation\';document.getElementById(\'save\').style.backgroundColor=\'#00ff00\';createfix_select(\'validate\')" value="Validate your data">
	             						 <input name="action" type="hidden" value="the_ajax_createfixdisplay" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form --></form><br>
										 <input name="fixcreatehidden" type="hidden" value="" id="fixcreatehidden"><br><br>
										 <div>When you are happy that the validations have returned with no errors (error box will show below) then click Create Fixtures. If you click Create Fixtures when the validations are still in error then firstly 
										 the next page will tell you there are still errors and you will lose all your entries for safety sake. If you have input correct data but, for example, incorrect pairs so fixtures are not valid then you will
										 need to delete from the system before trying again.</div><br>
										 <input name="submit" id="submit" type="submit" value ="Create the fixtures"><br>
										 </form><br><div name="errortext" type="text" value="" id="errortext" style="width:100%"></div><br>';
				 echo $the_form;
			} // end of else of post submit		
	}

	function ajax_return_createfixdisplay(){
		global $rootlocal;
        global $wpdb;
        $location = $_SERVER['DOCUMENT_ROOT'];
                
        include ($location . $rootlocal . '/wp-config.php');
        include ($location . $rootlocal . '/wp-load.php');
        include ($location . $rootlocal . '/wp-includes/pluggable.php');
        
        // Return changes
        $seasonid = $_POST['season-list'];
		$save = $_POST['fixcreatehidden'];
		//Need the following values for season chaning or creating
		// The competitions
        $qryComps =  $wpdb->get_results($wpdb->prepare("SELECT CompetitionId, CompetitionName, NrOfTeams FROM competitions WHERE SeasonId = %d", $seasonid));
	 	// The divisions
		$nrDivs =  $wpdb->get_var($wpdb->prepare("SELECT Count(CompetitionId) FROM competitions WHERE SeasonId = %d", $seasonid));
		// Find the maximum number of teams
		$maxTeams =  $wpdb->get_var($wpdb->prepare("SELECT Max(NrOfTeams) FROM competitions WHERE SeasonId = %d", $seasonid));
		// If the seeason changes then we need to change all the tables for the creation
		if ($save == "Season changed") {
			
			
			$the_form = $the_form .'<div id="thefixtable"><table class="ftab-fixcreatetable">
										<thead><tr>
										<th class="ftab-weekno">Week No.</th>
										<th class="ftab-date">Week Date</th>
										</tr></thead><tbody>';
				
			for ($i=1; $i < ($maxTeams*2)+1; $i++) {
				$the_form = $the_form .
					'<tr>
						<td class="ftab-weekno">' . $i . '</td>
						<td class="ftab-date"><input type="date" id="date' . $i . '"></input></td>';
 				$the_form = $the_form . '</tr>';
		 	}
			$the_form = $the_form . '</tbody></table>';
			// Now out the rules in about matching teams so they avoid each other on home nights
			if ($maxTeams == 16){
				$the_form = $the_form . '<div>Match team pairs in the numbers below so they do not cross: 1 and 16, 2 and 9, 3 and 10, 4 and 11, 5 and 12, 6 and 13, 7 and 14, 8 and 15</div>';
			}
			if ($maxTeams == 14){
				$the_form = $the_form . '<div>Match team pairs in the numbers below so they do not cross: 1 and 8, 2 and 9, 3 and 10, 4 and 11, 5 and 12, 6 and 13, 7 and 14</div>';
			}
			if ($maxTeams == 12){
				$the_form = $the_form . '<div>Match team pairs in the numbers below so they do not cross: 1 and 12, 2 and 7, 3 and 8, 4 and 9, 5 and 10, 6 and 11</div>';
			}
			if ($maxTeams == 10){
				$the_form = $the_form . '<div>Match team pairs in the numbers below so they do not cross: 1 and 10, 2 and 6, 3 and 7, 4 and 8, 5 and 9</div>';
			}
			
			$the_form = $the_form .'<table class="ftab-fixcreatetable">
									<thead><tr>
									<th class="ftab-weekno">Week No.</th>
									<th class="ftab-weekno">Team No.</th>';
				
			$divs = array();
			foreach($qryComps as $Comp) {
			 	 $the_form = $the_form .'<th class="ftab-team">' . $Comp->CompetitionName . '</th>';
				 array_push($divs,$Comp->CompetitionId);
			 }					
			$the_form = $the_form .	'</tr></thead><tbody>';

			 for ($i=1; $i < $maxTeams+1; $i++) {
			 	// Helpful formatting for the user so they can see which teams should be matched
			 	switch ($maxTeams){
					case 16:
						switch ($i){
							case 1:
							case 16:
								$pairclass = "pair1";
							break;
							case 2:
							case 9:
								$pairclass = "pair2";
							break;
							case 3:
							case 10:
								$pairclass = "pair3";
							break;
							case 4:
							case 11:
								$pairclass = "pair4";
							break;
							case 5:
							case 12:
								$pairclass = "pair5";
							break;
							case 6:
							case 13:
								$pairclass = "pair6";
							break;
							case 7:
							case 14:
								$pairclass = "pair7";
							break;
							case 8:
							case 15:
								$pairclass = "pair8";
							break;
						}
					break;
					case 14:
						switch ($i){
							case 1:
							case 8:
								$pairclass = "pair1";
							break;
							case 2:
							case 9:
								$pairclass = "pair2";
							break;
							case 3:
							case 10:
								$pairclass = "pair3";
							break;
							case 4:
							case 11:
								$pairclass = "pair4";
							break;
							case 5:
							case 12:
								$pairclass = "pair5";
							break;
							case 6:
							case 13:
								$pairclass = "pair6";
							break;
							case 7:
							case 14:
								$pairclass = "pair7";
							break;
						}
					break;
					case 12:
						switch ($i){
							case 1:
							case 12:
								$pairclass = "pair1";
							break;
							case 2:
							case 7:
								$pairclass = "pair2";
							break;
							case 3:
							case 8:
								$pairclass = "pair3";
							break;
							case 4:
							case 9:
								$pairclass = "pair4";
							break;
							case 5:
							case 10:
								$pairclass = "pair5";
							break;
							case 6:
							case 11:
								$pairclass = "pair6";
							break;
						}
					break;
					case 10:
						switch ($i){
							case 1:
							case 10:
								$pairclass = "pair1";
							break;
							case 2:
							case 6:
								$pairclass = "pair2";
							break;
							case 3:
							case 7:
								$pairclass = "pair3";
							break;
							case 4:
							case 8:
								$pairclass = "pair4";
							break;
							case 5:
							case 9:
								$pairclass = "pair5";
							break;
						}
					break;
			 	}
				
				
				$the_form = $the_form .
					'<tr>
						<td class="ftab-weekno">' . $i . '</td>
						<td class="ftab-weekno">' . $i . '</td>';
						
			 	 for ($j=1; $j < $nrDivs+1; $j++) {
			 	 	 
					 //Now put the teams into dropdowns
					 $teamdropdown = getTeambyDivSelect($seasonid,$divs[$j-1]);
					// Put in a rest week option for odd numbered teams in divisions - we put in a funny 999991/2/3 number so it can be identified as a rest week in the JS and the MySQL - a zero doesn't work!
					 $teamdropdown = $teamdropdown . '<option value="99999' . $j . '">Rest</option>';
					 
					 $the_form = $the_form . '<td class="ftab-team ' . $pairclass . '"><select name="team' . $i . '.' . $j . '" id="team' . $i . '.' . $j . '" class="team-list">';
					 $the_form = $the_form . $teamdropdown . '</select></td>';
			 	} 
 				$the_form = $the_form . '</tr>';
		 	}
			 $the_form = $the_form . '</tbody></table></div>';
	
	        $returntext = array("Table"=>$the_form
	                            );
	                 
	        echo json_encode($returntext);
			die();// wordpress may print out a spurious zero without this - can be particularly bad if using json 
	    }// $save = season changed
        
        // If the create button has been pressed then off we go...
        if ($save == "Validation"){
        	
			$returntext = array();
			$returntext['NrDivs'] = $nrDivs;
			$returntext['MaxTeams'] = $maxTeams;
		    $validationmessage = "";
			// Lets get the week values and add them to the returntext array
        	for ($i=1; $i < ($maxTeams*2)+1; $i++) {
        		// First make a note if any of the fields are blank for validation purposes
			 	if (empty($_POST['date' . $i])) {
			 		$validationmessage = $validationmessage . "Week " . $i . " date is blank.<br>";
			 	} else {
			 		$returntext['Week' . $i] = $_POST['date' . $i];
			 	}
        		
		 	}
			
			// Now lets get the teams
			for ($i=1; $i < $maxTeams+1; $i++) {
			 	for ($j=1; $j < $nrDivs+1; $j++) {
			 		// First make a note if any of the fields are blank for validation purposes
			 		if (empty($_POST['teamd' . $i . 't' . $j])) {
			 			$validationmessage = $validationmessage . "Team " . $i . " in the " . $j . " division is blank.<br>";
			 		} else {
			 			$returntext['TeamP' . $i . 'D' . $j] = $_POST['teamd' . $i . 't' . $j];
			 		}
			 	} 
		 	}
			// Now check for duplicates in the array
			if (count($returntext) === count(array_unique($returntext))) {
				// No duplicates
	
			} else {
				// Duplicates
				$validationmessage = $validationmessage . "There are duplicate week dates or teams in one or more of your divisions or week dates";
			}
			
	        // Now for the validation check
	        if (empty($validationmessage)) {
	        	$success = "Success";
				$returntext['Validation'] = $validationmessage;
			} else {
				$success = "Fail";
				$returntext['Validation'] = $validationmessage;
	        }
			
	        //Set the validation success
			$returntext['Success'] = $success;
			
	        echo json_encode($returntext);
			die();// wordpress may print out a spurious zero without this - can be particularly bad if using json 
			
        }
	}
	// Fixtures create end -------------------------------------------------------------------------

	// Fixture import ------------------------------------------------------------------------------
	add_shortcode('admin_fixtureimport_sc', 'admin_fixtureimport');
	function admin_fixtureimport(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];

		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
			// If the form has been submitted then do the calcs and return a message
			if (isset($_POST['submit'])) {
				 if (!empty($_FILES['csv_data']['name'])) { // Check for file if empty
				 	if(pathinfo( $_FILES['csv_data']['name'],PATHINFO_EXTENSION) =='csv') {  //Check to make sure csv
					
					} else {
						echo 'only csv file allowed'; 
					}
				 } else {
				 	echo 'empty'; 
				 }
				 // File ok and file a csv
				 $filename = $_FILES['csv_data']['name'];
				 $tmpname  = $_FILES['csv_data']['tmp_name'];
				 $filesize = $_FILES['csv_data']['size'];
				 $filetype = $_FILES['csv_data']['type'];
				 echo $fileName;
				 
				 // Open the csv file
				$fp = fopen($tmpname,"r");
				$counter =1;
				$errortext = "";
				$errorcount = 0;
				//parse the csv file row by row
				while(($row = fgetcsv($fp,"1500",",")) != FALSE)
				{
				    //insert csv data into mysql table
				    //$sql = "INSERT INTO tbl_books (name, author, isbn) VALUES('" . implode("','",$row) . "')";
				    //echo implode("','",$row);
				    //echo $row[0];
					//echo '<br>';
					
					// Now let's check the csv as we go row by row, will record errors, if none, then we can go through the file again and import
					// First check the column names to make sure theyre right - first row should be column names
					if ($counter==1){
						if($row[0] != 'Weekno'){
							$errortext = $errortext . "Column 1 header should be set as Weekno<br>";
							$errorcount = $errorcount+1;
						}
						if($row[1] != 'PlayDate'){
							$errortext = $errortext . "Column 1 header should be set as PlayDate<br>";
							$errorcount = $errorcount+1;
						}
						if($row[2] != 'Hteam'){
							$errortext = $errortext . "Column 1 header should be set as Hteam<br>";
							$errorcount = $errorcount+1;
						}
						if($row[3] != 'Ateam'){
							$errortext = $errortext . "Column 1 header should be set as Ateam<br>";
							$errorcount = $errorcount+1;
						}
					} else { // end first row check
						// Now check each row...
						$weekno = $row[0];
						$playdate = $row[1];
						$hteam = $row[2];
						$ateam = $row[3];
						
						// Lets check week number by seeing it it's in the range 1-30 - can only be this as max 16 teams in division so max 30 weeks
						if ($weekno < 1 OR $weekno > 30){
							$errortext = $errortext . 'Row ' . $counter . ': Weekno should be in range 1-30<br>';
							$errorcount = $errorcount+1;
						}
						
						// Lets check play date to see if it's valid
						$chkdate = date_parse($playdate);
						if ($chkdate["error_count"] == 0 && checkdate($chkdate["month"], $chkdate["day"], $chkdate["year"])){
						   // Valid 
						} else {
						    $errortext = $errortext . 'Row ' . $counter . ': Playdate should be a valid date - format yyyy-mm-dd<br>' . $playdate;
							$errorcount = $errorcount+1;
						}
						
						// Now is it a valid home team
						// Let's count
						$hcount = $wpdb->get_var($wpdb->prepare("SELECT Count(TeamId) FROM teams WHERE SeasonId = %d AND TeamName = %s" , $_POST['season-list'],$hteam));
						if ($hcount == 1) {
							//1 Team so it's valid team for the season
						} else {
							// Could be a Rest week.... so check
							if ($hteam == 'Rest') {
								//So Rest so it's valid team for the season
							} else {
								$errortext = $errortext . 'Row ' . $counter . ': Home team does not exist as a team for that season ' . $hteam. '<br>';
								$errorcount = $errorcount+1;
							}
						}
						
						// Now is it a valid away team
						// Let's count
						$acount = $wpdb->get_var($wpdb->prepare("SELECT Count(TeamId) FROM teams WHERE SeasonId = %d AND TeamName = %s" , $_POST['season-list'],$ateam));
						if ($acount == 1) {
							//1 Team so it's valid team for the season
						} else {
							if ($ateam == 'Rest') {
								//So Rest so it's valid team for the season
							} else {
								$errortext = $errortext . 'Row ' . $counter . ': Away team does not exist as a team for that season ' . $ateam. '<br>';
								$errorcount = $errorcount+1;
							}
						}
						
					}
					
					$counter = $counter+1;
				}
				fclose($fp);
				
				if ($errorcount == 0) {
					// No errors so we can load it up
					$fp = fopen($tmpname,"r");
					$counter = 1;
					while(($row = fgetcsv($fp,"1500",",")) != FALSE)
					{
						// Now let's check the csv as we go row by row, will record errors, if none, then we can go through the file again and import
						// First check the column names to make sure theyre right - first row should be column names
						if ($counter > 1) { //Miss the header row
							
							$weekno = $row[0];
							$playdate = $row[1];
							$hteam = $row[2];
							$ateam = $row[3];
														
							// We need to get some other variables for the fixtures table
							// Home team ID
							if ($hteam == 'Rest') {
								$hid = 0;
							} else {
								$hid = $wpdb->get_var($wpdb->prepare("SELECT TeamId FROM teams WHERE SeasonId = %d AND TeamName = %s" , $_POST['season-list'],$hteam));
							}
							// Away team ID
							if ($ateam == 'Rest') {
								$aid = 0;
							} else {
								$aid = $wpdb->get_var($wpdb->prepare("SELECT TeamId FROM teams WHERE SeasonId = %d AND TeamName = %s" , $_POST['season-list'],$ateam));
							}
							// Club ID for the home team
							//Check for Rest team first
							if ($hid == 0) {
								//Set to the away team if it is Rest
								$cid = $wpdb->get_var($wpdb->prepare("SELECT ClubId FROM teams WHERE TeamId = %d", $aid));
							} else {
								$cid = $wpdb->get_var($wpdb->prepare("SELECT ClubId FROM teams WHERE TeamId = %d", $hid));
							}
							// Competition ID
							//Check for Rest team first
							if ($hid == 0) {
								$kid = $wpdb->get_var($wpdb->prepare("SELECT CompetitionId FROM divisionteams WHERE TeamId = %d", $aid));
							} else {
								$kid = $wpdb->get_var($wpdb->prepare("SELECT CompetitionId FROM divisionteams WHERE TeamId = %d", $hid));
							}
							// So now we have all the data so INSERT into database table
							
							//echo 'Importing row ' . $counter . ' : ' . $weekno . ' ' . $playdate . ' ' . $hid . ' ' . $aid . ' ' . $kid . ' ' . $cid . '<br>';
							$wpdb->insert(fixtures,
                                                        array(
                                                            'SeasonId'=>$_POST['season-list'],
                                                            'Weekno'=>$weekno,
                                                            'PlayDate'=>$playdate,
                                                            'HomeTeamId'=>$hid,
                                                            'AwayTeamId'=>$aid,
                                                            'CompetitionId'=>$kid,
                                                            'VenueClubId'=>$cid
                                                              ),
                                                        array(
                                                            '%d',
                                                            '%d',
                                                            '%s',
                                                            '%d',
                                                            '%d',
                                                            '%d',
                                                            '%d'
                                                              )
                                                        ); //end wpdb insert
						} //end counter loop
						$counter = $counter+1;
					} //end while loop
					echo 'Finished importing';
				
				} else {
					// Errors so report them
					echo 'There are ' . $errorcount . ' errors in the csv file.<br>';
					echo $errortext;
				}
				fclose($fp);
				 
			} else {
				
				//Show the form for processing the file
				//Get the season - only allow fixture import into an open season
				$qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons WHERE StatusFlag = 'O'");
				$instructions = "<br><br>This importer is to be used in conjunction with the Fixtures YY-YY.xls file (where YY are years). That Excel file is where the fixtures can be created and a CSV file exported for import here.
								<br>This importer validates the file before loading to make sure that everything is ok. You need to have created the new season, divisions, teams, clubs etc for the new season fixtures before import.
								<br>Everything needed by the fixtures needs to be in place before the import is run.<br></div>";
				echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
				$the_form = $the_form . '<form id="theFixImportForm" method="post" action="" enctype="multipart/form-data">';
	             //<input name="action" type="hidden" value="the_ajax_fiximport" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form -->
	             $the_form = $the_form . '      
	             <div id="wrapper">
	             <label>Season:</label>
	            	<select autofocus name="season-list" id="season-list">
	           		<option value="">Select Season</option>';
	            	foreach($qrySeason as $season) {
                           $the_form = $the_form . '   
                           <option value="' . $season->SeasonId . '">' . $season->SeasonDesc .'</option>';
	            	}
					$the_form = $the_form . '</select><br>
	             
	             <input type="hidden" name="MAX_FILE_SIZE" value="1000000">
				 <label>Upload CSV:</label>
				 <input type="file" name="csv_data"> <input type="submit" name="submit" value="import">';
	             $the_form = $the_form . '</div></form><br>';	 
				 echo $the_form;
			} // end else
		}
		
	// Fixture import end --------------------------------------------------------------------------
	
	// Clubs  ----------------------------------------------------------------------------
	add_shortcode('admin_clubs_sc', 'admin_clubs');
	function admin_clubs(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Clubs button (right) to create a new club. To create a MapId go to Google Maps and type in the postcode. Click 'Share' and in the dialog box that opens choose
					    'Embed Map', 'Medium' and copy the link that is produced into the MapId box. To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the clubs table
		$tblclubs = new ajaxCRUD("Clubs", "clubs", "ClubId");
		
		// Applying masks to fields to help with inputss
		$tblclubs->modifyFieldWithClass("Mobile", "phone");
		$tblclubs->modifyFieldWithClass("Tel", "phone");
		$tblclubs->modifyFieldWithClass("PostCode", "postcode");
		
		// Don't display the primary key of the table
		$tblclubs->omitPrimaryKey();
		// Add an order
		$tblclubs->addOrderBy("ORDER BY ClubName ASC");
		// Show the table
		$tblclubs->displayAddFormTop();
		$tblclubs->showTable();
	}
	// Clubs end -------------------------------------------------------------------------
	
	// Sponsors ----------------------------------------------------------------------------
	add_shortcode('admin_sponsors_sc', 'admin_sponsors');
	function admin_sponsors(){
		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Sponsor button (right) to add a new sponsor to the league. The ImageRef field must be the name of the image that has been added to the Media library (
						Dashboard..Media...Library). LiveSponsor field is to identify a current paid sponsor. To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the teams sponsor
		$tblsponsors = new ajaxCRUD("Sponsors", "sponsors", "SponsorId","SponsorName ASC",1);
		
		// Applying masks to fields to help with inputss
		$tblsponsors->modifyFieldWithClass("SponsorMob", "phone");
		$tblsponsors->modifyFieldWithClass("SponsorTel", "phone");
		$tblsponsors->modifyFieldWithClass("SponsorFax", "phone");
		
		// Don't display the primary key of the table
		$tblsponsors->omitPrimaryKey();
		// Add an order
		$tblsponsors->addOrderBy("ORDER BY SponsorName ASC");
		// Show the table
		$tblsponsors->displayAddFormTop();
		$tblsponsors->showTable();	
	}
	// Sponsors end -------------------------------------------------------------------------
	
	// Breaks - League ----------------------------------------------------------------------------
	add_shortcode('admin_playerbreaks_sc', 'admin_playerbreaks');
	function admin_playerbreaks(){
		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Player Break button (right) to add a new player break to the league. To change values in the table below just click on the relevant cell and it
						will enable a change to be made. To find the PlayerId for the relevant season/player/team go to http://www.bdcsnooker.org/administration/players/ first, find the
						player for the right season and make a note of the ID number. Use that Id to search here. Similarly go to http://www.bdcsnooker.org/administration/admin-basics/admin-fixtures/
						and find the right fixture Id for the match where the break was made. Use those 2 IDs to find the break. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the teams sponsor
		$tblpbreaks = new ajaxCRUD("Player Breaks", "playerbreaks", "BreakId","PlayerId ASC",1);
				
		// Add the relationships so proper dropdowns from other tables appear					 
		$tblpbreaks->defineRelationship("SeasonId", "seasons", "SeasonId", "SeasonDesc","SeasonId DESC",1);
		$tblpbreaks->defineRelationship("PlayerId", "uniqueplayers", "UPlayerId", "CONCAT(Forename,' ',Surname)","Surname ASC",1);
		
		
		// Add a select box at the top
		$tblpbreaks->addAjaxFilterBox('PlayerId', 25);
		$tblpbreaks->addAjaxFilterBox('FixtureId', 25);
		
		// Don't display the primary key of the table
		$tblpbreaks->omitPrimaryKey();
		// Add an order
		$tblpbreaks->addOrderBy("ORDER BY FixtureId ASC");
		// Show the table
		$tblpbreaks->displayAddFormTop();
		$tblpbreaks->showTable();
		
	}
	// Breaks end -------------------------------------------------------------------------
	
	// Breaks - Cups  ----------------------------------------------------------------------------
	add_shortcode('admin_playercupbreaks_sc', 'admin_playercupbreaks');
	function admin_playercupbreaks(){
		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Player Break button (right) to add a new player break made in a cup game. To change values in the table below just click on the relevant cell and it
						will enable a change to be made. To find the PlayerId for the relevant season/player/team go to http://www.bdcsnooker.org/administration/players/ first, find the
						player for the right season and make a note of the ID number. Use that Id to search here. Also use http://www.bdcsnooker.org/administration/admin-teams-in-division/ 
						to find the home and away team IDs. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the teams sponsor
		$tblpbreaks = new ajaxCRUD("Player Cup Breaks", "playercompbreaks", "BreakId");
		$tblpbreaks->defineRelationship("PlayerId", "uniqueplayers", "UPlayerId", "CONCAT(Forename,' ',Surname)","Surname ASC",1);
		$tblpbreaks->defineRelationship("HomeTeamId", "teams", "TeamId", "CONCAT(SeasonId, ' ', TeamName)","TeamId DESC",1);
		$tblpbreaks->defineRelationship("AwayTeamId", "teams", "TeamId", "CONCAT(SeasonId, ' ', TeamName)","TeamId DESC",1);
		$tblpbreaks->defineRelationship("SeasonId", "seasons", "SeasonId", "SeasonDesc","SeasonId DESC",1);
		
		// Add a select box at the top
		$tblpbreaks->addAjaxFilterBox('PlayerId', 25);
		$tblpbreaks->addAjaxFilterBox('SeasonId', 25);
		
		// Don't display the primary key of the table
		$tblpbreaks->omitPrimaryKey();
		// Add an order
		$tblpbreaks->addOrderBy("ORDER BY BreakId DESC");
		// Show the table
		$tblpbreaks->displayAddFormTop();
		$tblpbreaks->showTable();
	
	
		}
	// Breaks Cups end -------------------------------------------------------------------------
	
	// Penalties ----------------------------------------------------------------------------
	add_shortcode('admin_penalties_sc', 'admin_penalties');
	function admin_penalties(){
		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Penalties button (right) to add a penalty for a team in a particular season.
						 To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the teams table
		$tblpenalties = new ajaxCRUD("Penalties", "penalties", "PenaltyId");
		
		// Add the relationships so proper dropdowns from other tables appear					 
		$tblpenalties->defineRelationship("SeasonId", "seasons", "SeasonId", "SeasonDesc","SeasonId DESC",1);
		$tblpenalties->defineRelationship("TeamId", "teams", "TeamId", "CONCAT(SeasonId, ' ', TeamName)","TeamId DESC",1);
		$tblpenalties->addValueOnInsert("PenaltyDate", "NOW()");
		$tblpenalties->addOrderBy("ORDER BY PenaltyId desc");
		
		// Apply a date picker to the date fields
		$tblpenalties->modifyFieldWithClass("PenaltyDate", "datepicker");
		
		// User friendly descriptions for table columns
		$tblpenalties->displayAs("SeasonId", "Season");
		$tblpenalties->displayAs("TeamId", "Team name");
		
		// Add a select box at the top
		$tblpenalties->addAjaxFilterBox('SeasonId', 25);
		
		// Don't display the primary key of the table
		$tblpenalties->omitPrimaryKey();
		// Show the table
		$tblpenalties->displayAddFormTop();
		$tblpenalties->showTable();
	}
	// Penalties end -------------------------------------------------------------------------
	
	// Competitions ----------------------------------------------------------------------------
	add_shortcode('admin_competitions_sc', 'admin_competitions');
	function admin_competitions(){
		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Competitions button (right) to create a new Division. You need to add all divisions that are being played for a season to this table.
					 	The start date is the first fixture date of the new season. To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the competitions table
		$tblcompetitions = new ajaxCRUD("Competitions", "competitions", "CompetitionId");
		$tblcompetitions->modifyFieldWithClass("StartDate", "datepicker");
		
		// Add the relationships so proper dropdowns from other tables appear					 
		$tblcompetitions->defineRelationship("SeasonId", "seasons", "SeasonId", "SeasonDesc","SeasonId DESC",1);
		$tblcompetitions->defineRelationship("SponsorId", "sponsors", "SponsorId", "SponsorName","SponsorName ASC");
		
		// User friendly descriptions for table columns
		$tblcompetitions->displayAs("SponsorId", "Sponsor name");
		$tblcompetitions->displayAs("SeasonId", "Season name");
		
		// Add a select box at the top
		$tblcompetitions->addAjaxFilterBox('SeasonId', 25);
		// Apply a date picker to the date fields
		$tblcompetitions->modifyFieldWithClass("StartDate", "datepicker");
		
		// Don't display the primary key of the table
		$tblcompetitions->omitPrimaryKey();
		// Add an order
		$tblcompetitions->addOrderBy("ORDER BY SeasonId DESC");
		// Show the table
		$tblcompetitions->displayAddFormTop();
		$tblcompetitions->showTable();
	}
	// Competitions end -------------------------------------------------------------------------
	
	// Division sort order ----------------------------------------------------------------------------
	add_shortcode('admin_divsortorder_sc', 'admin_divsortorder');
	function admin_divsortorder(){
		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Division Sort Order button (right) to add the sort order for showing divisions. The order sequence is the priority order for sorting the divisions into - so
								 points first, then whatever next. Need to use the column names of the actual division table: Points, MatchesWon, MatchesLost, MatchesDrawn, Diff, FramesFor, FramesAgainst.
								 You need to add DESC (descending) or ASC (ascending) after the column name. You need 4 sorting orders for each season. To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the competitions table
		$tbldivorders = new ajaxCRUD("Division Sort Order", "divisiontableorder", "TabOrderId");
		
		// Add the relationships so proper dropdowns from other tables appear					 
		$tbldivorders->defineRelationship("SeasonId", "seasons", "SeasonId", "SeasonDesc","SeasonId DESC",1);
		
		// User friendly descriptions for table columns
		$tbldivorders->displayAs("SeasonId", "Season name");
		
		// Add a select box at the top
		$tbldivorders->addAjaxFilterBox('SeasonId', 25);

		// Don't display the primary key of the table
		$tbldivorders->omitPrimaryKey();
		// Add an order
		$tbldivorders->addOrderBy("ORDER BY SeasonId DESC");
		// Show the table
		$tbldivorders->displayAddFormTop();
		$tbldivorders->showTable();
	}
		// Competitions end -------------------------------------------------------------------------
	
	// Teams in competitions ----------------------------------------------------------------------------
	add_shortcode('admin_teamsincomps_sc', 'admin_teamsincomps');
	function admin_teamsincomps(){

		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Teams in Division button (right) to attach a team to a division for a particular season. Make sure you
			  have added those teams to the database first. The Current season number and season name is shown above. When adding teams to a division 
			  for a particular season make sure you match the season number that precedes the team name. To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the competitions table
		$tblteamincomps = new ajaxCRUD("Teams in Division", "divisionteams", "TeamDivId");
		
		// Add the relationships so proper dropdowns from other tables appear					 
		$tblteamincomps->defineRelationship("SeasonId", "seasons", "SeasonId", "SeasonDesc","SeasonId DESC",1);
		$tblteamincomps->defineRelationship("CompetitionId", "competitions", "CompetitionId", "CONCAT(SeasonId, ': ', CompetitionName)","SeasonId DESC",1);
		$tblteamincomps->defineRelationship("TeamId", "teams", "TeamId", "CONCAT(SeasonId, ': ', TeamName)","TeamId DESC",1,"WHERE SeasonId = " . $GLOBALS["seasonid"] . "");
		
		// User friendly descriptions for table columns
		$tblteamincomps->displayAs("CompetitionId", "Division name");
		$tblteamincomps->displayAs("SeasonId", "Season name");
		$tblteamincomps->displayAs("TeamId", "Team name");
		
		// Add an order
		$tblteamincomps->addOrderBy("ORDER BY SeasonId DESC");
		
		// Add a select box at the top
		$tblteamincomps->addAjaxFilterBox('SeasonId', 25);

		// Show the table
		$tblteamincomps->displayAddFormTop();
		$tblteamincomps->setLimit(20);
		$tblteamincomps->showTable();
	}
	// Teams in Competitions end -------------------------------------------------------------------------
	
	// Tournaments ----------------------------------------------------------------------------
	add_shortcode('admin_tournaments_sc', 'admin_tournaments');
	function admin_tournaments(){
		// Put the instructions up and starting text
		$instructions = "<br><br>Use the Add Tournaments button (right) to add a new tournament for a particular season.<br>
						 Handicapped is 1 for Yes and 0 for No.<br>
						 Format is: 0 Singles, 1 Doubles, 2 Team tournament, 3 Billiards.<br>To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the competitions table
		$tbltournaments = new ajaxCRUD("Tournaments", "tournament", "TournamentId");
		
		// Add the relationships so proper dropdowns from other tables appear					 
		$tbltournaments->defineRelationship("SeasonId", "seasons", "SeasonId", "SeasonDesc","SeasonId DESC",1);
		$tbltournaments->defineRelationship("SponsorId", "sponsors", "SponsorId", "SponsorName","SponsorName ASC",1);
		
		// User friendly descriptions for table columns
		$tbltournaments->displayAs("SeasonId", "Season");
		$tbltournaments->displayAs("SponsorId", "Sponsor name");
		
		// Add a select box at the top
		$tbltournaments->addAjaxFilterBox('SeasonId', 25);
		// Add an order
		$tbltournaments->addOrderBy("ORDER BY SeasonId DESC");
		// Don't display the primary key of the table
		$tbltournaments->omitPrimaryKey();
		// Show the table
		$tbltournaments->displayAddFormTop();
		$tbltournaments->showTable();
	}
	// Tournaments end -------------------------------------------------------------------------
	
	// Team Tournament rounds ----------------------------------------------------------------------------
	add_shortcode('admin_teamtournorounds_sc', 'admin_teamtournorounds');
	function admin_teamtournorounds(){
		$instructions = "<br><br>Use the Add Tournament Round button (right) to add a new round to an existing tournament for a particular season.<br>
						 Name is the description of the Round eg. Round 1 (by 1 Jan).<br>
						 Sequence is the round order, eg. Round 1 is 1, Round 2 is 2 etc..<br>To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the table
		$tbltournamentrounds = new ajaxCRUD("Tournament Rounds", "teamtournoround", "TeamRoundId");
		
		// Add the relationships so proper dropdowns from other tables appear and limit Format to 2 for Team comps					 
		$tbltournamentrounds->defineRelationship("TournamentId", "tournament", "TournamentId", "Name","Name DESC", 0,"WHERE Format = '2'");
		$tbltournamentrounds->addAjaxFilterBox('TournamentId', 25);
		
		// User friendly descriptions for table columns
		$tbltournamentrounds->displayAs("TournamentId", "Tournament name");
		// Add an order
		$tbltournamentrounds->addOrderBy("ORDER BY Tournament DESC");
		// Don't display the primary key of the table
		$tbltournamentrounds->omitPrimaryKey();
		
		// Show the table
		$tbltournamentrounds->displayAddFormTop();
		$tbltournamentrounds->showTable();
	}	
	// Team Tournaments rounds end -------------------------------------------------------------------------
			
	// Team Tournament fixtures ----------------------------------------------------------------------------
	add_shortcode('admin_teamtournofix_sc', 'admin_teamtournofix');
	function admin_teamtournofix(){
		$instructions = "<br><br>Use the Add Tournament Entries button (right) to add a new round to an existing tournament for a particular season.
						<br>To change values in the table below just click on the relevant cell and it
						will enable a change to be made. If there is a Bye always use 1 Bye from the list of teams (it will be at the bottom of the drop down list). Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		
		/* Useful code but not used but kept for reference
		$valid_teamvals = array(array(0 => 0, 1=> "Bye"));
		
		$teams = $wpdb->get_results("Select TeamId, SeasonId, TeamName FROM teams");
		foreach ($teams as $teams) {
					
				//$valid_teamvals[$teams->TeamId] =  $teams->TeamName;
				//array_push($valid_teamvals,$teams->SeasonId . $teams->TeamName);
				$valid_teamvals[$teams->TeamId] =  array(0 => $teams->TeamId, 1 => $teams->SeasonId . " " . $teams->TeamName); //$teams->SeasonId . $teams->TeamName;
		}
		*/ 
		
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the table
		$tbltournofix = new ajaxCRUD("Tournament Entries", "teamtournofixture", "TeamFixtureId");
		
		// Add the relationships so proper dropdowns from other tables appear				 
		$tbltournofix->defineRelationship("TournamentRoundId", "teamtournoround", "TeamRoundId", "CONCAT(Tournament, ': ', Name)","TeamRoundId DESC");
		$tbltournofix->defineRelationship("HomeEntryId", "teams", "TeamId", "CONCAT(SeasonId, ' ', TeamName)","TeamId DESC",1);
		$tbltournofix->defineRelationship("AwayEntryId", "teams", "TeamId", "CONCAT(SeasonId, ' ', TeamName)","TeamId DESC",1);
		$tbltournofix->defineRelationship("ClubId", "clubs", "ClubId", "ClubName","ClubName ASC",1);
		
		// Add a select box at the top
		$tbltournofix->addAjaxFilterBox('TournamentRoundId', 25);
		$tbltournofix->modifyFieldWithClass("FixtureDate", "datepicker");
		
		// User friendly descriptions for table columns
		$tbltournofix->displayAs("TournamentRoundId", "Tournament and round name");
		$tbltournofix->displayAs("HomeEntryId", "Home team");
		$tbltournofix->displayAs("AwayEntryId", "Away team");
		$tbltournofix->displayAs("ClubId", "Club name");
		
		// Apply a date picker to the date fields
		$tbltournofix->modifyFieldWithClass("FixtureDate", "datepicker");
		
		// Order the table
		$tbltournofix->addOrderBy("ORDER BY TeamFixtureId desc");
				
		// Don't display the primary key of the table
		$tbltournofix->omitPrimaryKey();
		
		// Show the table
		$tbltournofix->displayAddFormTop();
		$tbltournofix->showTable();
	}	
	// Team Tournaments fixtures end -------------------------------------------------------------------------
	
	// Player Tournament entry ----------------------------------------------------------------------------
	add_shortcode('admin_playertournoentry_sc', 'admin_playertournoentry');
	function admin_playertournoentry(){
		$instructions = "<br><br>Use the Add Tournament Rounds button (right) to add a new round to an existing tournament for a particular season.<br>
						 Name field does not need completing as it is automatically filled.<br>To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the table
		$tblplayertournoentry = new ajaxCRUD("Player Tournament Entry", "playertournoentry", "TournamentEntryId");
		
		// Add the relationships so proper dropdowns from other tables appear and limit Format to 2 for Team comps					 
		$tblplayertournoentry->defineRelationship("TournamentId", "tournament", "TournamentId", "Name", "Name DESC" ,1,"WHERE Format = 3 or Format = 1 or Format = 0");
		$tblplayertournoentry->defineRelationship("UPlayer1Id", "uniqueplayers", "UPlayerId", "CONCAT(Forename,' ',Surname)","Surname ASC",1);
		$tblplayertournoentry->defineRelationship("UPlayer2Id", "uniqueplayers", "UPlayerId", "CONCAT(Forename,' ',Surname)","Surname ASC",1);
		
		$tblplayertournoentry->addAjaxFilterBox('TournamentId', 25);
		
		// User friendly descriptions for table columns
		$tblplayertournoentry->displayAs("TournamentId", "Tournament name");
		// Add an order
		$tblplayertournoentry->addOrderBy("ORDER BY TournamentId DESC");
		// Don't display the primary key of the table
		$tblplayertournoentry->omitPrimaryKey();
		
		// Show the table
		$tblplayertournoentry->displayAddFormTop();
		$tblplayertournoentry->showTable();
	}	
	// Player Tournaments entry end -------------------------------------------------------------------------
	
	// Player Tournament rounds ----------------------------------------------------------------------------
	add_shortcode('admin_playertournorounds_sc', 'admin_playertournorounds');
	function admin_playertournorounds(){
		$instructions = "<br><br>Use the Add Tournament Rounds button (right) to add a new round to an existing tournament for a particular season.<br>
						 Name is the description of the Round eg. Round 1 (by 1 Jan).<br>
						 Sequence is the round order, eg. Round 1 is 1, Round 2 is 2 etc..<br>To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the table
		$tblplayertournorounds = new ajaxCRUD("Tournament Rounds", "playertournoround", "PlayerRoundId");
		
		// Add the relationships so proper dropdowns from other tables appear and limit Format to 2 for Team comps					 
		$tblplayertournorounds->defineRelationship("TournamentId", "tournament", "TournamentId", "Name","Name DESC", 0,"WHERE Format = 3 or Format = 1 or Format = 0");
		$tblplayertournorounds->addAjaxFilterBox('TournamentId', 25);
		
		// User friendly descriptions for table columns
		$tblplayertournorounds->displayAs("TournamentId", "Tournament name");
		// Don't display the calculated field called Tournament
		$tblplayertournorounds->omitField("Tournament");
		$tblplayertournorounds->omitAddField("Tournament");
		
		// Don't display the primary key of the table
		$tblplayertournorounds->omitPrimaryKey();
		// Add an order
		$tblplayertournorounds->addOrderBy("ORDER BY Tournament DESC");
		// Show the table
		$tblplayertournorounds->displayAddFormTop();
		$tblplayertournorounds->showTable();
	}	
	// Player Tournaments rounds end -------------------------------------------------------------------------
	
	// Player Tournament fixtures ----------------------------------------------------------------------------
	add_shortcode('admin_playertournofix_sc', 'admin_playertournofix');
	function admin_playertournofix(){
		$instructions = "<br><br>Use the Add Tournament Entries button (right) to add a new round to an existing tournament for a particular season.<br>
						 Match the Tournament number that precedes the tournament name to the Tournament number that precedes the player name.<br>
						 When you select a Tournament and Round name it will alter the dropdowns for the Home and Away players. You need to refresh the page to select another tournament.
						 To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";
		echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
		
		// Uses the ajaxCRUD php classes that are in the php folder
		// Set the table
		$tblplayertournofix = new ajaxCRUD("Tournament Fixtures	", "playertournofixture", "PlayerFixtureId");
		
		// Add the relationships so proper dropdowns from other tables appear				 
		$tblplayertournofix->defineRelationship("PlayerTournoRoundId", "playertournoround", "PlayerRoundId", "CONCAT(TournamentId, ': ',Tournament, ': ', Name)","PlayerRoundId DESC");
		$tblplayertournofix->defineRelationship("HomeEntryId", "playertournoentry", "TournamentEntryId", "CONCAT(TournamentId,': ',Name)","TournamentEntryId DESC",1);
		$tblplayertournofix->defineRelationship("AwayEntryId", "playertournoentry", "TournamentEntryId", "CONCAT(TournamentId,': ',Name)","TournamentEntryId DESC",1);
		$tblplayertournofix->defineRelationship("ClubId", "clubs", "ClubId", "ClubName","ClubName ASC");
		
		// Add a select box at the top
		$tblplayertournofix->addAjaxFilterBox('PlayerTournoRoundId', 25);
		$tblplayertournofix->modifyFieldWithClass("FixtureDate", "datepicker");
		
		// User friendly descriptions for table columns
		$tblplayertournofix->displayAs("PlayerTournoRoundId", "Tournament and round name");
		$tblplayertournofix->displayAs("HomeEntryId", "Home team");
		$tblplayertournofix->displayAs("AwayEntryId", "Away team");
		$tblplayertournofix->displayAs("ClubId", "Club name");
		
		// Apply a date picker to the date fields
		$tblplayertournofix->modifyFieldWithClass("FixtureDate", "datepicker");
		
		// Order the table
		$tblplayertournofix->addOrderBy("ORDER BY PlayerFixtureId desc");
				
		// Don't display the primary key of the table
		$tblplayertournofix->omitPrimaryKey();
		
		// Show the table
		$tblplayertournofix->displayAddFormTop();
		$tblplayertournofix->showTable();
	}	
	// Team Tournaments fixtures end -------------------------------------------------------------------------
	
	// Checks for missing results for the current season
	add_shortcode('admin_checkmissingresults_sc', 'admin_checkmissingresults');
	function admin_checkmissingresults(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		$tfix='';					 
		
					
		$missings = $wpdb->get_results('call check_missingresults(' . $GLOBALS["seasonid"] . ')');
		// Check if it's empty because if it is no fixtures to display
		if (empty($missings)) {
				$tfix = $tfix . 'No missing results.<br><br>';
		} else {
			$tfix=$tfix . '<table class="mtab-missingfix">
				<thead><tr>
					<th class="mtab-compname">Comp Name</th>
					<th class="mtab-weekno">WeekNo</th>
					<th class="mtab-date">Date</th>
					<th class="mtab-fixhome">Home Team</th>
					<th class="mtab-fixaway">Away Team</th>
				</thead></tr><tbody>';
				
			foreach ($missings as $missing) {
			
				
				$tfix = $tfix . '
								<tr>
									<td class="mtab-compname">' .$missing->CompetitionName . '</td>
									<td class="mtab-weekno">' .$missing->Weekno . '</td>
									<td class="mtab-date">' .date("d-M",strtotime($missing->Playdate)). '</td>
									<td class="mtab-fixhome"><a href="http://www.bdcsnooker.org/teams?teamid=' . $missing->HomeTeamId . '&seasonid=' . $GLOBALS["seasonid"] . '">' .$missing->HomeTeam . '</a></td>
									<td class="mtab-fixaway"><a href="http://www.bdcsnooker.org/teams?teamid=' . $missing->AwayTeamId . '&seasonid=' . $GLOBALS["seasonid"] . '">' .$missing->AwayTeam. '</a></td>
								</tr>';
			}
			
			$tfix= $tfix. '</tbody></table>';
		}
		echo $tfix;
	}
	// End Missing results
	
	// -------------------- ADMINISTRATION FUNCTIONS END ------------------------------

	// -------------------- PAGE HANDLING STUFF ---------------------------------------
	// HANDLES COMPETITION ENTRIES
	add_shortcode('competition_entry_sc','competition_entry');
	function competition_entry(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		 include ($location . $rootlocal . '/wp-config.php');
         include ($location . $rootlocal . '/wp-load.php');
         include ($location . $rootlocal . '/wp-includes/pluggable.php'); 
		
		// Check the global team tournament id and seasonid so we can prefill the team tourno table
		if (isset($_GET['teamtournoid'])) {
    		$GLOBALS["teamtournoid"] = $_GET['teamtournoid'];
		}
		if (isset($_GET['seasonid'])) {
    		$GLOBALS["seasonid"] = $_GET['seasonid'];
		}
		
		$qryPlayer = $wpdb->get_results("SELECT * FROM uniqueplayers ORDER BY Surname ASC");

		$instructions = 'Please enter your competition entry details here. If you are entering a doubles competition make sure you complete both your name and the player 2 field. If you are a new player to the league and your name does not feature in the lists please <a href="mailto:admin@bdcsnooker.org?Subject=Competition%20Entry%20new%20player" target="_top">email admin@bdcsnooker.org</a>.</div>';
		$the_form = '<div class="instruction_text">' . $instructions;
		
		$the_form = $the_form . '
            <form id="theCompEntryForm">';
             
            // GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
            $qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons ORDER BY SeasonId DESC");
                 
            $the_form = $the_form . '      
            <div class="frmDropDown">';
             
            $the_form = $the_form . '<label>Your name:</label>
             <select autofocus name="player1-list" id="player1-list">';
            
             foreach($qryPlayer as $player) {
 				// Check the global player so we can set it to selected
				$selected = '';
				if ($player->Forename <> '(absent)'){
					$the_form = $the_form . '   
                    <option ' . $selected . 'value="' . $player->UPlayerId . '">' . $player->Forename . ' ' . $player->Surname .'</option>';    
				}
             }
             $the_form = $the_form . '' . '</select><br><br>
             
            <label for="tel">Tel No: </label>
            <input name="tel" id="tel" type="number" required><br><br>
          
            <label for="email">Email: </label>
            <input name="email" id="email" type="email"><br><br>';
  
            // PUT SEASON IN
            $the_form = $the_form . ' <label>Season:</label>
             <select autofocus name="season-list" id="season-list">
             <option value="">Select Season</option>';
             foreach($qrySeason as $season) {
         		// Check the global season so we can set it to selected
				$selected = '';
				if ($GLOBALS["seasonid"] === $season->SeasonId){
					$selected = 'selected = "selected" ';
					 $the_form = $the_form . '<option ' . $selected . 'value="' . $season->SeasonId . '">' . $season->SeasonDesc .'</option>';
				}
             }
			//NOW PUT THE TOURNAMENT DROPDOWN IN
			$tournodropdown = getTournoSelect($GLOBALS["seasonid"],"Entries");
			
             $the_form = $the_form . '</select><br><br>
                            <label>Tourno:</label>
                            <select name="comptourno-list" id="comptourno-list" class="comptourno-list">';
             $the_form = $the_form . $tournodropdown;
             $the_form = $the_form . '' . '</select><br><br>';
             
             // NOW PUT THE PLAYERS DROPDOWN IN
             $the_form = $the_form . '<label>Player 2 (for doubles only):</label>
             <select autofocus name="player2-list" id="player2-list">';
            
             foreach($qryPlayer as $player) {
 				// Check the global player so we can set it to selected
				$selected = '';
				
                // Check the global player so we can set it to selected
				$selected = '';
				if ($player->Forename <> '(absent)'){
					$the_form = $the_form . '   
                    <option ' . $selected . 'value="' . $player->UPlayerId . '">' . $player->Forename . ' ' . $player->Surname .'</option>';    
				}
             }
             $the_form = $the_form . '' . '</select><br><br>';
             
             
             
             $the_form = $the_form . '<input name="action" type="hidden" value="ajax_add_compentry" />&nbsp';
             wp_nonce_field( 'compentry_nonce' );
             
             $the_form = $the_form . '<input type="button" value="Submit details" onclick=
             "ajax_compentry_submit()">';
             $the_form = $the_form . '</form><br>You can see all entries to the competitions on the <a href="http://www.bdcsnooker.org/competitions/view-competition-entries/">Competitions Entry Page.</a><br><br>';
			 
             $the_form = $the_form . '<div id="messageDiv" class="messageDiv"></div>';
			 echo $the_form;
	}
	
	function ajax_add_compentry(){
	    global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
	    include ($location . $rootlocal . '/wp-config.php');
        include ($location . $rootlocal . '/wp-load.php');
        include ($location . $rootlocal . '/wp-includes/pluggable.php'); 
        
        // Nonce check
        //$nonce = $_POST['_nonce'];
        //if ( ! wp_verify_nonce( $nonce, 'compentry_nonce' ) )
        //die ( 'Busted!');
        
        // process form data
        // Check that data is filled
        $player1 = $_POST['player1-list'];
        $tel = $_POST['tel'];
        $email = $_POST['email'];
        $tourno = $_POST['comptourno-list'];
        $player2 = $_POST['player2-list'];
        $season = $_POST['season-list'];
        
        //Now validate the form data
        if (!$player1 > 0){
            $errors[1] = 'Please choose your name from the list';   
        }
        
        if (!$tourno > 0){
            $errors[2] = 'Please choose a tournament to enter from the list';   
        }
        
        if (!stristr($email,"@") OR !stristr($email,".")) {
            $errors[3] = 'Your email is invalid.';
        }
       
        if (strlen($tel) < 9) {
            $errors[4] = 'Your telephone number is invalid.';
        }
         
        if (!$player2 > 0) {
            
            // Can be empty if it's not a doubles tournament
            $tnaments = $wpdb->get_results($wpdb->prepare("SELECT TournamentId FROM tournament WHERE SeasonId = %d AND Format = %d", $season, 1));
        
            foreach ($tnaments as $tnament) {
                if ($tnament->TournamentId == $tourno) {
                    break;
                } else {
                    $errors[5] = 'Please select a second player for the doubles entry.';
                }
            }
        }
        
        
        if (empty($errors))
                {
                    // INSERT record
                    
                    $doubles = $wpdb->get_var($wpdb->prepare("SELECT Format FROM `tournament` WHERE TournamentId = %d",$tourno ));
                    $tname = $wpdb->get_var($wpdb->prepare("SELECT Name FROM `tournament` WHERE TournamentId = %d",$tourno ));
                    $pname1 = $wpdb->get_var($wpdb->prepare("SELECT ForeName FROM `uniqueplayers` WHERE UPlayerId = %d",$player1 ));
                    $pname2 = $wpdb->get_var($wpdb->prepare("SELECT ForeName FROM `uniqueplayers` WHERE UPlayerId = %d",$player2 ));
                    
                    // If the tournament is a singles then we set the player2 to absent player
                    if ($doubles == 1) {
                       $player2 = $player2;
					} else {
                    	$player2 = 1;
                    }
                        
                    $wpdb->insert(compentry,
                                    array(
                                        'SeasonId'=>$season,
                                        'TournoId'=>$tourno,
                                        'Player1Id'=>$player1,
                                        'Player2Id'=>$player2,
                                        'Email'=>$email,
                                        'Telephone'=>$tel,
                                        'Paid'=>0
                                    ),
                                    array(
                                        '%d',
                                        '%d',
                                        '%d',
                                        '%d',
                                        '%s',
                                        '%s',
                                        '%d'
                                    )
                                 ); //end wpdb insert
                                 
                    
                    // Send results to ajax return
                    $successmessage = '<h2>Entry has been saved successfully</h2><br>';
                    if ($doubles == 1) {
                        $successmessage = $successmessage . $pname1 . ' and ' . $pname2 . ' entered into ' . $tname;
                        
                    } else {
                        $successmessage = $successmessage . $pname1 . ' entered into ' . $tname;
                    }
                    
                    $successmessage = $successmessage . '<br><br>';
                    echo $successmessage;
                    die();// wordpress may print out a spurious zero without this - can be particularly bad if using json
                } else {
                    // Errors found
                    $errormessage = '<h2>Entry has NOT been saved successfully</h2>';
                    for ($i = 1; $i < 6; $i++) {
                        if (strlen($errors[$i]) > 0) {
                            $errormessage = $errormessage . $errors[$i] . '<br>';    
                        }
                    }
                    echo $errormessage;
                    die();// wordpress may print out a spurious zero without this - can be particularly bad if using json
                }
	}
	
	add_shortcode('competition_entry_view_sc','competition_entry_view');
	function competition_entry_view(){
	    
	    global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		include ($location . $rootlocal . '/wp-config.php');
        include ($location . $rootlocal . '/wp-load.php');
        include ($location . $rootlocal . '/wp-includes/pluggable.php'); 
		
		if (isset($_GET['seasonid'])) {
    		$GLOBALS["seasonid"] = $_GET['seasonid'];
		}
		
		$instructions = 'Please see a list of competition entries received below. If your entry is not on the list please go to the <a href="http://www.bdcsnooker.org/competitions/competition-entry/">Entry Page.</a> If you are listed in a competition you do not want to enter please <a href="mailto:admin@bdcsnooker.org?Subject=Competition%20Entry%20error" target="_top">email admin@bdcsnooker.org</a> to get yourself removed.</div>';
		
		$entrytext = '<div class="instruction_text">' . $instructions;
	
		
		// Get all the tournaments from this season and non-team (hence the 2)
		$tournos = $wpdb->get_results($wpdb->prepare('SELECT TournamentId, Name FROM tournament WHERE SeasonId = %d AND Format <> %d AND Name NOT LIKE "%Shipton%"',$GLOBALS["seasonid"],2));
		// Loop over the tournaments, give name and then loop round the entries
		foreach ($tournos as $tourno){
		    $entrytext = $entrytext . '<h2>' . $tourno->Name . '</h2>';
		    
		    //Get the entries for that tournament
		    $entries = $wpdb->get_results($wpdb->prepare("SELECT c.Player1Id as c1pid, c.Player2Id as c2pid, u1.Forename as u1f, u1.Surname as u1s, u2.Forename as u2f, u2.Surname as u2s FROM compentry AS c
                INNER JOIN uniqueplayers as u1 ON c.Player1Id = u1.UPlayerId
                INNER JOIN uniqueplayers as u2 ON c.Player2Id = u2.UPlayerId
                WHERE TournoId = %d",$tourno->TournamentId ));
		    
		    foreach ($entries as $entry){
		        
		        if ($entry->c2pid == 1){
		            //Singles
		            $entrytext = $entrytext . $entry->u1f . ' ' .  $entry->u1s . '<br>';    
		        } else {
		            //Doubles
		            $entrytext = $entrytext . $entry->u1f . ' ' .  $entry->u1s . ' / ' . $entry->u2f . ' ' .  $entry->u2s . '<br>';
		        }
		        
		    }
		    $entrytext = $entrytext . '<br>';
		}
		
		return $entrytext;
	}
	
	// HANDLES CLUBS WIDGET **************************************************************
	add_shortcode('clubs_rep_sc','clubs_rep');
	function clubs_rep($atts){
		
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		// get attibutes and set defaults
        extract(shortcode_atts(array(
                'season' => 1,
       ), $atts));
					
		ob_start(); // why ob... http://wordpress.stackexchange.com/questions/6730/custom-shortcode-in-widget-forced-to-top-of-widget
		?>
		<div class="bdscltools-table"><table >
			<thead><tr>
				<td>Match</th>
				<td>Date</th>
				<td>Competition</th>
				<td>Home/Away</th>
				<td>Opponent</th>
			</tr></thead>			
			<tr>
				<td>Club 1</td>
				<td>Club 2</td>
				<td>Club 3</td>
				<td>Club 4</td>
				<td>Club 5</td>
			</tr>
			<tr>
				<td>Club 1</td>
				<td>Club 2</td>
				<td>Club 3</td>
				<td>Club 4</td>
				<td>Club 5</td>
			</tr>
			<tr>
				<td>Club 1</td>
				<td>Club 2</td>
				<td>Club 3</td>
				<td>Club 4</td>
				<td>Club 5</td>
			</tr>
			<tr>
				<td>Club 1</td>
				<td>Club 2</td>
				<td>Club 3</td>
				<td>Club 4</td>
				<td>Club 5</td>
			</tr>
			<tr>
				<td>Club 1</td>
				<td>Club 2</td>
				<td>Club 3</td>
				<td>Club 4</td>
				<td>Club 5</td>
			</tr>
			<tr>
				<td>Club 1</td>
				<td>Club 2</td>
				<td>Club 3</td>
				<td>Club 4</td>
				<td>Club 5</td>
			</tr>
			<tr>
				<td>Club 1</td>
				<td>Club 2</td>
				<td>Club 3</td>
				<td>Club 4</td>
				<td>Club 5</td>
			</tr>
		</table></div>
		<?php
		return ob_get_clean();
	}
	// *********************************************************************************
	
	//HANDLES CLUBS PAGE DISPLAY
	add_shortcode('clubs_display_sc','clubs_display');
	function clubs_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		 include ($location . $rootlocal . '/wp-config.php');
         include ($location . $rootlocal . '/wp-load.php');
         include ($location . $rootlocal . '/wp-includes/pluggable.php'); 
		
		// Check the global teamid and seasonid so we can prefill the team table
		if (isset($_GET['clubid'])) {
    		$GLOBALS["clubid"] = $_GET['clubid'];
		}
		
		$the_form = '
             <form id="theClubForm">';
             
             // GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
             $qryclub = $wpdb->get_results("SELECT * FROM clubs ");
                 
             $the_form = $the_form . '      
             <div id="wrapper">
             <div class="frmDropDown">
             <div class="row">
             <label>Clubs:</label>
             <select autofocus name="club-list" id="club-list" onchange="club_select(\'club\')">
             <option value="">Select Club</option>';
            
			 $clubdropdown = getClubSelect();
			 $clubtabletext = getClubTable($GLOBALS["clubid"]);
			 
             foreach($qryclub as $club) {
     				// Check the global club so we can set it to selected
					$selected = '';
					if ($GLOBALS["clubid"] === $club->ClubId){
						$selected = 'selected = "selected" ';
					}
					$the_form = $the_form . $clubdropdown;
                    //$the_form = $the_form . '   
                    //<option ' . $selected . ' value="' . $club->ClubId . '">' . $club->ClubName .'</option>';
             }              
             $the_form = $the_form . '</select><br>
             <input name="action" type="hidden" value="the_ajax_clubsdisplay" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form -->
             </form><br>';
			 $the_form = $the_form . '<hr><div id="clubs-tab">' . $clubtabletext . '</div>';
			 
			 echo $the_form;
	}
	
	function ajax_return_clubsdisplay(){
		global $rootlocal;
        global $wpdb;
        $location = $_SERVER['DOCUMENT_ROOT'];
                
        include ($location . $rootlocal . '/wp-config.php');
        include ($location . $rootlocal . '/wp-load.php');
        include ($location . $rootlocal . '/wp-includes/pluggable.php');
        
        // Return changes
        
		$clubid = $_POST['club-list'];
		
		$clublist = getClubSelect();
		$clubtabletext = getClubTable($clubid);
		
        $returntext = array("Club"=>$clublist,
        					"Table"=>$clubtabletext
                            );
                 
        echo json_encode($returntext); 
         
          
        die();// wordpress may print out a spurious zero without this - can be particularly bad if using json        	
	}
	
	//**********************************************************************************
	
	// HANDLES THE TEAMS PAGE **********************************************************
	add_shortcode('teams_display_sc', 'teams_display');
	function teams_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');

		// Check the global teamid and seasonid so we can prefill the team table
		if (isset($_GET['teamid'])) {
    		$GLOBALS["teamid"] = $_GET['teamid'];
		}
		if (isset($_GET['seasonid'])) {
    		$GLOBALS["seasonid"] = $_GET['seasonid'];
		}
		
		$teamtable = getTeamTable($GLOBALS["seasonid"],$GLOBALS["teamid"]);
		
		$the_form = '
             <form id="theTeamsForm">';
             
             // GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
             $qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons ORDER BY SeasonId DESC");
                 
             $the_form = $the_form . '      
             <div id="wrapper">
             <label>Season:</label>
             <select autofocus name="season-list" id="season-list" onchange="team_select(\'season\')">
             <option value="">Select Season</option>';
            
             foreach($qrySeason as $season) {
             				// Check the global season so we can set it to selected
							$selected = '';
							if ($GLOBALS["seasonid"] === $season->SeasonId){
								$selected = 'selected = "selected" ';
							}
                            $the_form = $the_form . '   
                            <option ' . $selected . 'value="' . $season->SeasonId . '">' . $season->SeasonDesc .'</option>';
             }
			//Now put the team drop down in
			$teamdropdown = getTeamSelect($GLOBALS["seasonid"]);
			
             $the_form = $the_form . '</select><br>
                            <label>Team:</label>
                            <select name="team-list" id="team-list" class="team-list" onchange="team_select(\'team\')">';
			 $the_form = $the_form . $teamdropdown;				
                            
             $the_form = $the_form . '' . '</select>
             <input name="action" type="hidden" value="the_ajax_teamdisplay" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form -->
             </form><br>';
			 
			 $the_form = $the_form . '<hr><div id="team-tab">' . $teamtable . '</div>';
			 echo $the_form;
	}
	
	function ajax_return_teamdisplay(){
		global $rootlocal;
        global $wpdb;
        $location = $_SERVER['DOCUMENT_ROOT'];
                
        include ($location . $rootlocal . '/wp-config.php');
        include ($location . $rootlocal . '/wp-load.php');
        include ($location . $rootlocal . '/wp-includes/pluggable.php');
        
		//check_ajax_referer( 'wpajaxhandler_nonce' );
        // Return changes
        $seasonid = $_POST['season-list'];
		$teamid = $_POST['team-list'];
		
		$teamlist = getTeamSelect($seasonid);
		$teamtabletext = getTeamTable($seasonid,$teamid);
		
        $returntext = array("Team"=>$teamlist,
        					"Table"=>$teamtabletext
                            );
                 
        echo json_encode($returntext); 
         
          
        die();// wordpress may print out a spurious zero without this - can be particularly bad if using json        	
	}
	
	// *********************************************************************************
	
	// HANDLES THE PLAYERS PAGE **********************************************************
	add_shortcode('players_display_sc', 'players_display');
	function players_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		// Check the global playerid so we can prefill the player table
		if (isset($_GET['playerid'])) {
    		$GLOBALS["playerid"] = $_GET['playerid'];
		}
		$playertable = getPlayerTable($GLOBALS["playerid"]);
		
		$the_form = '
             <form id="thePlayersForm">';
             
             // GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
             $qryPlayer = $wpdb->get_results("SELECT * FROM uniqueplayers ORDER BY Surname ASC");
                 
             $the_form = $the_form . '      
             <div id="wrapper">
             <label>Player:</label>
             <select autofocus name="player-list" id="player-list" onchange="player_select(\'player\')">
             <option value="">Select Player</option>';
            
             foreach($qryPlayer as $player) {
             				// Check the global player so we can set it to selected
							$selected = '';
							if ($GLOBALS["playerid"] === $player->UPlayerId){
								$selected = 'selected = "selected" ';
							}
                            $the_form = $the_form . '   
                            <option ' . $selected . 'value="' . $player->UPlayerId . '">' . $player->Forename . ' ' . $player->Surname .'</option>';
             }
			//NOW PUT THE DIVISION DROPDOWN IN
             $the_form = $the_form . '</select><br>';
			 /*
                            <label>Team:</label>
                            <select name="team-list" id="team-list" class="team-list" onchange="team_select(\'team\')">
                            <option value="">Select Team</option>';
                            
             $the_form = $the_form . '' . '</select>*/
             $the_form = $the_form . '<input name="action" type="hidden" value="the_ajax_playerdisplay" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form -->
             </form><br>';
			 
			 $the_form = $the_form . '<hr><div id="player-tab">' . $playertable . '</div>';
			 echo $the_form;
	}
	
	function ajax_return_playerdisplay(){
		global $rootlocal;
        global $wpdb;
        $location = $_SERVER['DOCUMENT_ROOT'];
                
        include ($location . $rootlocal . '/wp-config.php');
        include ($location . $rootlocal . '/wp-load.php');
        include ($location . $rootlocal . '/wp-includes/pluggable.php');
        
        // Return changes
        //$seasonid = $_POST['season-list'];
		$playerid = $_POST['player-list'];
		
		//$playerlist = getPlayerSelect($seasonid);
		$playertabletext = getPlayerTable($playerid);
		
        $returntext = array("Table"=>$playertabletext
                            );
                 
        echo json_encode($returntext); 
         
          
        die();// wordpress may print out a spurious zero without this - can be particularly bad if using json        	
	}
	
	// HANDLES THE RANKING PTS PAGE **********************************************************
	add_shortcode('rankings_display_sc', 'rankings_display');
	function rankings_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		// Check the global seasonid so we can prefill the rankings table
		if (isset($_GET['seasonid'])) {
    		$GLOBALS["seasonid"] = $_GET['seasonid'];
		}
		$rankingstable = getRankingsTable($GLOBALS["seasonid"],500,0,0);
		
		$the_form = '
             <form id="theRankingsForm">';
             
             // GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
             $qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons ORDER BY SeasonId DESC");
                 
             $the_form = $the_form . '      
             <div id="wrapper">
             <label>Season:</label>
             <select autofocus name="season-list" id="season-list" onchange="ranking_select(\'season\')">
             <option value="">Select Season</option>';
            
             foreach($qrySeason as $season) {
             				// Check the global season so we can set it to selected
							$selected = '';
							if ($GLOBALS["seasonid"] === $season->SeasonId){
								$selected = 'selected = "selected" ';
							}
                            $the_form = $the_form . '   
                            <option ' . $selected . 'value="' . $season->SeasonId . '">' . $season->SeasonDesc .'</option>';
             }
             $the_form = $the_form . '</select><br>';
             $the_form = $the_form . '<input name="action" type="hidden" value="the_ajax_rankingsdisplay" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form -->
             </form><br>';
			 
			 $the_form = $the_form . '<hr><div id="rankings-tab">' . $rankingstable . '</div>';
			 echo $the_form;
	}
	
	function ajax_return_rankingsdisplay(){
		global $rootlocal;
        global $wpdb;
        $location = $_SERVER['DOCUMENT_ROOT'];
                
        include ($location . $rootlocal . '/wp-config.php');
        include ($location . $rootlocal . '/wp-load.php');
        include ($location . $rootlocal . '/wp-includes/pluggable.php');
        
        // Return changes
        $seasonid = $_POST['season-list'];
		
		//$playerlist = getPlayerSelect($seasonid);
		$rankingstabletext = getRankingsTable($seasonid,500,0,0);
		
        $returntext = array("Table"=>$rankingstabletext
                            );
                 
        echo json_encode($returntext); 
         
          
        die();// wordpress may print out a spurious zero without this - can be particularly bad if using json        
	}
	
	// *********************************************************************************
	
	// HANDLES THE FULL HISTORY OF BREAKS PAGE *****************************************
	add_shortcode('allbreaks_display_sc', 'allbreaks_display');
	function allbreaks_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		// Check the global seasonid so we can prefill the rankings table
		//if (isset($_GET['seasonid'])) {
    	//	$GLOBALS["seasonid"] = $_GET['seasonid'];
		//}
		$allbreakstable = getAllBreaksTable(101,0,0,2,1);
		
		/* Commented out as may wish to add a season filter at some point...
		$the_form = '
             <form id="theRankingsForm">';
             
             // GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
             $qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons ORDER BY SeasonId DESC");
                 
             $the_form = $the_form . '      
             <div id="wrapper">
             <label>Season:</label>
             <select autofocus name="season-list" id="season-list" onchange="ranking_select(\'season\')">
             <option value="">Select Season</option>';
            
             foreach($qrySeason as $season) {
             				// Check the global season so we can set it to selected
							$selected = '';
							if ($GLOBALS["seasonid"] === $season->SeasonId){
								$selected = 'selected = "selected" ';
							}
                            $the_form = $the_form . '   
                            <option ' . $selected . 'value="' . $season->SeasonId . '">' . $season->SeasonDesc .'</option>';
             }
             $the_form = $the_form . '</select><br>';
             $the_form = $the_form . '<input name="action" type="hidden" value="the_ajax_rankingsdisplay" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form -->
             </form><br>';
			 
			 $the_form = $the_form . '<hr><div id="rankings-tab">' . $rankingstable . '</div>';
			 echo $the_form;
		 */
		 $the_form = $the_form . '<div id="breaks-tab">' . $allbreakstable . '</div>';
		 echo $the_form; 
	}
	
	// *********************************************************************************
	add_shortcode('cupbreaks_display_sc', 'cupbreaks_display');
	function cupbreaks_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		// Check the global seasonid so we can prefill the rankings table
		//if (isset($_GET['seasonid'])) {
    	//	$GLOBALS["seasonid"] = $_GET['seasonid'];
		//}
		$allbreakstable = getAllBreaksTable(101,0,0,1,1);
		
		/* Commented out as may wish to add a season filter at some point...
		$the_form = '
             <form id="theRankingsForm">';
             
             // GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
             $qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons ORDER BY SeasonId DESC");
                 
             $the_form = $the_form . '      
             <div id="wrapper">
             <label>Season:</label>
             <select autofocus name="season-list" id="season-list" onchange="ranking_select(\'season\')">
             <option value="">Select Season</option>';
            
             foreach($qrySeason as $season) {
             				// Check the global season so we can set it to selected
							$selected = '';
							if ($GLOBALS["seasonid"] === $season->SeasonId){
								$selected = 'selected = "selected" ';
							}
                            $the_form = $the_form . '   
                            <option ' . $selected . 'value="' . $season->SeasonId . '">' . $season->SeasonDesc .'</option>';
             }
             $the_form = $the_form . '</select><br>';
             $the_form = $the_form . '<input name="action" type="hidden" value="the_ajax_rankingsdisplay" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form -->
             </form><br>';
			 
			 $the_form = $the_form . '<hr><div id="rankings-tab">' . $rankingstable . '</div>';
			 echo $the_form;
		 */
		 $the_form = $the_form . '<div id="breaks-tab">' . $allbreakstable . '</div>';
		 echo $the_form; 
	}
	
	// *********************************************************************************
	
	
	// HANDLES THE PLAYERS HEAD TO HEAD PAGE *****************************************
	add_shortcode('headtohead_display_sc', 'headtohead_display');
	function headtohead_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		$headtoheadtable = ""; //getPlayerTable($GLOBALS["playerid"]);
		
		$the_form = '
             <form id="theHeadtoHeadForm">';
             
             // GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
             $qryPlayer = $wpdb->get_results("SELECT * FROM uniqueplayers ORDER BY Surname ASC");
                 
             $the_form = $the_form . '      
             <div id="wrapper">
             <label>Select Player 1:</label>
             <select autofocus name="player1-list" id="player1-list" onchange="headtohead_select(\'player1\')">
             <option value="">Select Player 1</option>';
            
             foreach($qryPlayer as $player) {
             				// Check the global player so we can set it to selected
							
                            $the_form = $the_form . '   
                            <option value="' . $player->UPlayerId . '">' . $player->Forename . ' ' . $player->Surname .'</option>';
             }
			//NOW PUT THE DIVISION DROPDOWN IN
             $the_form = $the_form . '</select><br>';
			 $the_form = $the_form . '<label>Select Player 2:</label>
             						  <select autofocus name="player2-list" id="player2-list" onchange="headtohead_select(\'player2\')">
             						  <option value="">Select Player 2</option>';
			  $the_form = $the_form . '</select><br>';
			  
             $the_form = $the_form . '<input name="action" type="hidden" value="the_ajax_headtoheaddisplay" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form -->
             </form><br>';
			 
			 $the_form = $the_form . '<hr><div id="headtohead-tab">' . $headtoheadtable . '</div>';
			 echo $the_form;
	}
	
	function ajax_return_headtoheaddisplay(){
		global $rootlocal;
        global $wpdb;
        $location = $_SERVER['DOCUMENT_ROOT'];
                
        include ($location . $rootlocal . '/wp-config.php');
        include ($location . $rootlocal . '/wp-load.php');
        include ($location . $rootlocal . '/wp-includes/pluggable.php');
        
        // Return changes
        $player1id = $_POST['player1-list'];
		$player2id = $_POST['player2-list'];
		
		$playerlist = $wpdb->get_results($wpdb->prepare("SELECT CONCAT(u1.Forename,' ', u1.Surname) as p1Name, u1.UPlayerId as u1ID
													FROM fixtureresultdetails as d 
													INNER JOIN fixtures as f on f.FixtureId = d.FixtureId
													INNER JOIN teams as t ON f.HomeTeamId = t.TeamId
													INNER JOIN teams as s ON f.AwayTeamId = s.TeamId
													INNER JOIN players as p1 ON p1.PlayerId = d.HomePlayerId
													INNER JOIN players as p2 ON p2.PlayerId = d.AwayPlayerId
													INNER JOIN uniqueplayers as u1 ON p1.UPlayerId = u1.UPlayerId
													INNER JOIN uniqueplayers as u2 ON p2.UPlayerId = u2.UPlayerId
													WHERE (u1.UPlayerId = %d OR u2.UPlayerId = %d)
	                                                UNION
	                                                SELECT CONCAT(u2.Forename,' ', u2.Surname) as p2Name, u2.UPlayerId as u2ID
													FROM fixtureresultdetails as d 
													INNER JOIN fixtures as f on f.FixtureId = d.FixtureId
													INNER JOIN teams as t ON f.HomeTeamId = t.TeamId
													INNER JOIN teams as s ON f.AwayTeamId = s.TeamId
													INNER JOIN players as p1 ON p1.PlayerId = d.HomePlayerId
													INNER JOIN players as p2 ON p2.PlayerId = d.AwayPlayerId
													INNER JOIN uniqueplayers as u1 ON p1.UPlayerId = u1.UPlayerId
													INNER JOIN uniqueplayers as u2 ON p2.UPlayerId = u2.UPlayerId
													WHERE (u1.UPlayerId = %d OR u2.UPlayerId = %d)
	                                                ORDER BY p1Name ASC",$player1id,$player1id,$player1id,$player1id));
		
		$player2list = '<option value="">Select Player 2</option>';	
		foreach ($playerlist as $player){
				$selected = "";
                $player2list = $player2list . '   
                 <option value="' . $player->u1ID . '">' . $player->p1Name .'</option>';
		}
		
		$headtoheadtabletext = getMatchesTable(0,$player1id,$player2id,0);
		
        $returntext = array("Player2"=>$player2list,
        					"Table"=>$headtoheadtabletext,
                           );
                 
        echo json_encode($returntext); 
         
          
        die();// wordpress may print out a spurious zero without this - can be particularly bad if using json        
	}

	// HANDLES THE DIVISIONS TABLES PAGES **********************************************
	add_shortcode('division_display_sc', 'division_display');
	function division_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		 include ($location . $rootlocal . '/wp-config.php');
         include ($location . $rootlocal . '/wp-load.php');
         include ($location . $rootlocal . '/wp-includes/pluggable.php'); 
		
		// Check the global teamid and seasonid so we can prefill the team table
		if (isset($_GET['seasonid'])) {
    		$GLOBALS["seasonid"] = $_GET['seasonid'];
		}
		if (isset($_GET['divisionid'])) {
    		$GLOBALS["divisionid"] = $_GET['divisionid'];
		}
		
		$divisiontable =  getDivTable($GLOBALS["seasonid"], $GLOBALS["divisionid"], 99, 0, 0, 0);
		$averagestable =  getPlayerAveragesTable($GLOBALS["seasonid"], $GLOBALS["divisionid"], 0, 16, 0, 0);
		$breakstable = getPlayerBreaksTable($GLOBALS["seasonid"], $GLOBALS["divisionid"],16,0,0);
				
		$the_form = '
             <form id="theDivisionForm">';
             
             // GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
             $qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons ORDER BY SeasonId DESC");
                 
             $the_form = $the_form . '      
             <div id="wrapper">
             <div class="frmDropDown">
             <label>Season:</label>
             <select autofocus name="season-list" id="season-list" onchange="division_select(\'season\')">
             <option value="">Select Season</option>';
            
             foreach($qrySeason as $season) {
             				// Check the global season so we can set it to selected
							$selected = '';
							if ($GLOBALS["seasonid"] === $season->SeasonId){
								$selected = 'selected = "selected" ';
							}
                            $the_form = $the_form . '   
                            <option ' . $selected . ' value="' . $season->SeasonId . '">' . $season->SeasonDesc .'</option>';
             }
			//NOW PUT THE DIVISION DROPDOWN IN
			$divdropdown = getCompSelect($GLOBALS["seasonid"],1);
			
             $the_form = $the_form . '</select><br>
                            <label>Division:</label>
                            <select name="division-list" id="division-list" class="division-list" onchange="division_select(\'division\')">';
                            
			 $the_form = $the_form . $divdropdown;	
                           // <option value="">Select Division</option>';
                            
             $the_form = $the_form . '' . '</select>
             <input name="action" type="hidden" value="the_ajax_divisiondisplay" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form -->
             </form><br>';
			 
			 $the_form = $the_form . '<hr><div id="division-tab">' . $divisiontable . '</div><div id="averages-tab">' . $averagestable . '</div><div id="breaks-tab">' . $breakstable . '</div></div>';
			 echo $the_form;
	}
	
	function ajax_return_divisiondisplay(){
		global $rootlocal;
        global $wpdb;
        $location = $_SERVER['DOCUMENT_ROOT'];
                
        include ($location . $rootlocal . '/wp-config.php');
        include ($location . $rootlocal . '/wp-load.php');
        include ($location . $rootlocal . '/wp-includes/pluggable.php');
        
        // Return changes
        $seasonid = $_POST['season-list'];
		$divid = $_POST['division-list'];
		
		$divlist = getCompSelect($seasonid,1);
		$divtabletext = getDivTable($seasonid, $divid,99,0,0,0);
		$fixtabletext = getWeeklyFixturesbyDiv($seasonid,$divid,8,0);  //seasonid, compid, range (next 14 days), type 0 =table
		$restabletext = getWeeklyResultsbyDiv($seasonid,$divid,-7,0);  //seasonid, compid, range (last 8 days), type 0 =table
		$averagestabletext = getPlayerAveragesTable($seasonid, $divid,0,16, 0, 0);
		$breakstabletext = getPlayerBreaksTable($seasonid,$divid,16,0,0);
		$weekbreakstabletext = getAllBreaksTable(10,1,1,3,$divid);
		
        $returntext = array("Division"=>$divlist,
        					"Table"=>$divtabletext,
        					"Fixture"=>$fixtabletext,
        					"Result"=>$restabletext,
        					"Averages"=>$averagestabletext,
        					"Breaks"=>$breakstabletext,
        					"WkBreaks"=>$weekbreakstabletext
                            );
                 
        echo json_encode($returntext); 
         
          
        die();// wordpress may print out a spurious zero without this - can be particularly bad if using json        
	}
	
	// *********************************************************************************
	
	// HANDLES THE SHOWING CURRENT SEASON FIXTURES PAGE **********************************************
	add_shortcode('seasonfix_display_sc', 'seasonfix_display');
	function seasonfix_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		 include ($location . $rootlocal . '/wp-config.php');
         include ($location . $rootlocal . '/wp-load.php');
         include ($location . $rootlocal . '/wp-includes/pluggable.php'); 
		
		// Check the global teamid and seasonid so we can prefill the team table
		if (isset($_GET['seasonid'])) {
    		$GLOBALS["seasonid"] = $_GET['seasonid'];
		}
		
		//$compid = 51;
		//$seasonid = 17;
		
		$comps = $wpdb->get_results($wpdb->prepare("SELECT c.CompetitionId, c.CompetitionName FROM competitions AS c WHERE c.SeasonId = %d ",$GLOBALS["seasonid"] ));
		$tfix='';
		foreach ($comps as $comps) {
			$tfix = $tfix . 'Go to... <a href="#' . $comps->CompetitionName . '">' . $comps->CompetitionName . '</a><br>';
		}
		$tfix = $tfix . '<br>';
		$comps = $wpdb->get_results($wpdb->prepare("SELECT c.CompetitionId, c.CompetitionName FROM competitions AS c WHERE c.SeasonId = %d ",$GLOBALS["seasonid"] ));
		foreach ($comps as $comps) {
			$tfix = $tfix . '<a id="' . $comps->CompetitionName . '"></a>';
			$tfix = $tfix . '<h3>' . $comps->CompetitionName . '</h3>';
			//$fix =$wpdb->get_results($wpdb->prepare("SELECT f.Weekno as WeekNo, f.PlayDate as Date, t.TeamName as HomeTeam, s.TeamName as AwayTeam, f.HomeTeamId as HomeId, f.AwayTeamId as AwayId FROM fixtures as f
			//						INNER JOIN teams as t ON f.HomeTeamId = t.TeamId
			//						INNER JOIN teams as s ON f.AwayTeamId = s.TeamId
			//						WHERE f.SeasonId = %d AND f.CompetitionId = %d",$GLOBALS["seasonid"], $comps->CompetitionId ));
									
			$fix =$wpdb->get_results($wpdb->prepare("SELECT f.fixtureid as FixId, f.Weekno as WeekNo, f.PlayDate as Date, f.HomeTeamId as HomeId, f.AwayTeamId as AwayId FROM fixtures as f
									WHERE f.SeasonId = %d AND f.CompetitionId = %d",$GLOBALS["seasonid"], $comps->CompetitionId ));
								 
			
			
			if (count($fix) > 0 ) {
					
				$tfix=$tfix . '<table class="mtab-weekfix">
						<thead><tr>
							<th class="mtab-weekno">Wk</th>
							<th class="mtab-date">Date</th>
							<th class="mtab-fixhome">Home</th>
							<th class="mtab-fixhome"></th>
							<th class="mtab-fixaway">Away</th>
							<th class="mtab-fixaway"></th>
						</thead></tr><tbody>';
				 
				foreach ($fix as $fix) {
					
					$hscore = $wpdb->get_var($wpdb->prepare("SELECT HomeScore FROM fixtureresults WHERE FixtureId = %d",$fix->FixId ));
					$ascore = $wpdb->get_var($wpdb->prepare("SELECT AwayScore FROM fixtureresults WHERE FixtureId = %d",$fix->FixId ));
			
					//If a team is a Rest week then we need to set the team as Rest 	
					if ($fix->HomeId == 0){
						$hteam = 'Rest';
					} else {
						$hteam = $wpdb->get_var($wpdb->prepare("SELECT Teamname FROM teams WHERE TeamId = %d" , $fix->HomeId));
					}	
						
					if ($fix->AwayId == 0){
						$ateam = 'Rest';
					} else {
						$ateam = $wpdb->get_var($wpdb->prepare("SELECT Teamname FROM teams WHERE TeamId = %d" , $fix->AwayId));
					}
					
					$tfix = $tfix . '
								<tr>
									<td class="mtab-weekno">' .$fix->WeekNo . '</td>
									<td class="mtab-date">' .date("d-M",strtotime($fix->Date)). '</td>
									<td class="mtab-fixhome"><a href="http://www.bdcsnooker.org/teams?teamid=' . $fix->HomeId . '&seasonid=' . $GLOBALS["seasonid"] . '">' .$hteam . '</a></td>
									<td class="mtab-fixhome">' . $hscore . '</td>
									<td class="mtab-fixaway"><a href="http://www.bdcsnooker.org/teams?teamid=' . $fix->AwayId . '&seasonid=' . $GLOBALS["seasonid"] . '">' .$ateam. '</a></td>
									<td class="mtab-fixaway">' . $ascore . '</td>
								</tr>';
				}
						
				$tfix= $tfix. '</tbody></table>';
			} else {
				$tfix = $tfix . "No fixtures to display";
			} // end $tfix
		} // end $comps	
		
		echo $tfix;
	}
	
	// *********************************************************************************
	
	// HANDLES THE EXPORTING WEEKLY TABLES AND FIXTURES TO CSV **********************************************
	add_shortcode('export_weekly_sc', 'export_weekly');
	function export_weekly(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		 include ($location . $rootlocal . '/wp-config.php');
         include ($location . $rootlocal . '/wp-load.php');
         include ($location . $rootlocal . '/wp-includes/pluggable.php'); 
		error_reporting(E_ALL);
		
		// Check the global teamid and seasonid so we can prefill the team table
		// If the form has been submitted thenexport the file
		if (isset($_POST['submit'])) {
		
			$seasonid = $_POST['season-list'];
			$competitionid = $wpdb->get_var($wpdb->prepare("SELECT CompetitionId FROM competitions WHERE SeasonId = %d LIMIT 1",$seasonid));
			
			$weekid = $_POST['week-list'];
			 $weekdesc = $wpdb->get_var($wpdb->prepare("SELECT DISTINCT PlayDate FROM fixtures WHERE CompetitionId = %d AND Weekno = %d",$competitionid, $weekid));
			 
			 $date_row = array();
			 
								
			 //$weekdesc = date('d M y',$weekdesc);
			 			
			// Get the division table results
			 // We need the sort order for the division table as this could change over years depending on the rules		
			 // Get 1st, 2nd, 3rd and 4th orders...		
			 $taborder1 = $wpdb->get_var($wpdb->prepare("SELECT OrderName FROM divisiontableorder WHERE SeasonId = %d AND OrderSequence = %d",$seasonid,1));
			 $taborder2 = $wpdb->get_var($wpdb->prepare("SELECT OrderName FROM divisiontableorder WHERE SeasonId = %d AND OrderSequence = %d",$seasonid,2));
			 $taborder3 = $wpdb->get_var($wpdb->prepare("SELECT OrderName FROM divisiontableorder WHERE SeasonId = %d AND OrderSequence = %d",$seasonid,3));
			 $taborder4 = $wpdb->get_var($wpdb->prepare("SELECT OrderName FROM divisiontableorder WHERE SeasonId = %d AND OrderSequence = %d",$seasonid,4));
			 $orderclause = '';
			 // Build the order clause for the table_results select statement
			 if ($taborder1 != NULL) {
			 	$orderclause = $orderclause . $taborder1 . ',';
			 }
			 if ($taborder2 != NULL) {
			 	$orderclause = $orderclause . $taborder2 . ',';
			 }
			 if ($taborder3 != NULL) {
			 	$orderclause = $orderclause . $taborder3 . ',';
			 }
			 if ($taborder4 != NULL) {
			 	$orderclause = $orderclause . $taborder4 . ',';
			 }
			 if ($orderclause == '') {
			 	// If the orderclause is blank then we default to this...
			 	$orderclause = 'ORDER BY Points DESC, Diff DESC, MatchesWon DESC';
			 } else {
			 	// If orderclause set then use it...remove trailing comma and add the ORDER BY
				$orderclause = 'ORDER BY ' . rtrim($orderclause,",");
			 }
			 
			 $divtext = '<form name="selectall">
						 <textarea name="tablebody" style="width:100%;height:500px;border:1px solid #999;" wrap="off" >';

			 $blankrow = '<tr>
 						 	<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						 </tr>';
			 
			 $divtext = $divtext . '<table><tr>
								<td>Week</td>
								<td>' . $weekdesc . '</td>
								<td></td>
								<td></td>
								<td></td>
						</tr>';			 
			 			 
			 // Loop round the divisions
			 
			 $comps = $wpdb->get_results($wpdb->prepare("SELECT CompetitionId FROM competitions WHERE SeasonId = %d",$seasonid));		 
			 foreach ($comps as $comp)	{
			 	
			 	 $divtext = $divtext .= '<tr>
								<td>Team</td>
								<td>W</td>
								<td>L</td>
								<td>D</td>
								<td>Pts</td>
								</tr>';
				 $table_results = $wpdb->get_results($wpdb->prepare("SELECT * FROM divisiontables INNER JOIN teams on 
																	 divisiontables.teamid=teams.teamid 
																	 WHERE divisiontables.SeasonId = %d AND CompetitionId = %d " . $orderclause . "",$seasonid,$comp->CompetitionId ));			  	
				 // Spew out the table into the data_rows array
				 foreach ( $table_results as $u ) {
				 	
					$divtext = $divtext .
						'<tr>
							<td>' .$u->TeamName . '</td>
							<td>' .$u->MatchesWon . '</td>
							<td>' .$u->MatchesDrawn . '</td>
							<td>' .$u->MatchesLost . '</td>
							<td>' .$u->Points . '</td>
						</tr>';
				 }
				 
				 $divtext = $divtext . $blankrow;
			 } //$comps
			 $divtext = $divtext . $blankrow;
			 // Now the results
			 $fix_results = $wpdb->get_results($wpdb->prepare("SELECT C.CompetitionName as CompName, F.PlayDate as PlayDate,TH.TeamName as HomeTeam,FR.HomeScore,
	       									FR.AwayScore,TA.TeamName as AwayTeam, F.HomeTeamId as HomeId, F.AwayTeamId as AwayId FROM fixtureresults as FR
										    INNER JOIN fixtures as F ON F.FixtureId = FR.FixtureId
										    INNER JOIN teams AS TH ON F.HomeTeamId=TH.TeamId
									        INNER JOIN teams AS TA ON F.AwayTeamId=TA.TeamId
									        INNER JOIN competitions as C ON F.CompetitionId = C.CompetitionId
										    WHERE F.SeasonId =%d AND Weekno = %d",$seasonid, $weekid));	 
			 // Spew out the table into the table javascript:this.form.tablebody.focus();this.form.tablebody.copy()
			 $data_rows = array(); 
				 foreach ( $fix_results as $u ) {
				 		
						$divtext = $divtext .
						'<tr>
							<td>' .$u->CompName . '</td>
							<td>' .$u->PlayDate . '</td>
							<td>' .$u->HomeTeam . '</td>
							<td>' .$u->HomeScore . '</td>
							<td>' .$u->AwayTeam . '</td>
							<td>' .$u->AwayScore . '</td>
						</tr>';
				 } 
			 $divtext = $divtext . '</table></textarea><input type="button" value="Copy Text" onClick="javascript:this.form.tablebody.select();document.execCommand(\'copy\');">
									</form>';
			 
			 return $divtext;
			 
			 /* This was to export csv but it doesnt work...
			 // Open the file...
			 $fh = @fopen( 'php://output', 'w' );
			 // Set the filename for export
		     $filename = 'results-week-' . $weekid . '.csv';
			 header("Pragma: public");
			 header("Content-disposition: attachment;filename={$filename}");
			 header("Content-Type: text/csv; charset=utf-8");
		     header("Expires: 0");
		     header("Cache-control: must-revalidate, post-check=0, pre-check=0");
		     header("Cache-control: private", false);		      
			 // Set the header row for the tables		 
			 $header_row = array(
								0 => 'Team Name',
								1 => 'W',
								2 => 'D',
								3 => 'L',
								4 => 'Pts',
								);
			 // Set a blank row for formatting purposes
			 $blank_row = array(
								0 => '',
								1 => '',
								2 => '',
								3 => '',
								4 => '',
								);
			 fputcsv( $fh, $blank_row );
			 fputcsv( $fh, $date_row );
			 // Loop round the divisions	 
			 $comps = $wpdb->get_results($wpdb->prepare("SELECT CompetitionId FROM competitions WHERE SeasonId = %d",$seasonid));		 
			 foreach ($comps as $comp)	{
	
			 	 $data_rows = array(); 
				 $table_results = $wpdb->get_results($wpdb->prepare("SELECT * FROM divisiontables INNER JOIN teams on 
																	 divisiontables.teamid=teams.teamid 
																	 WHERE divisiontables.SeasonId = %d AND CompetitionId = %d " . $orderclause . "",$seasonid,$comp->CompetitionId ));			  	
				 // Spew out the table into the data_rows array
				 foreach ( $table_results as $u ) {
						$row = array();
						$row[0] = $u->TeamName;
						$row[1] = $u->MatchesWon;
						$row[2] = $u->MatchesDrawn;
						$row[3] = $u->MatchesLost;
						$row[4] = $u->Points;
						$data_rows[] = $row;
				 } 
				 // Put the data into the csv file
				 
				 fputcsv( $fh, $blank_row );
				 fputcsv( $fh, $header_row );
				 
				 foreach ( $data_rows as $data_row ) {
				 	fputcsv( $fh, $data_row );
				 }
				 
			 } //$comps
			 
			 // Now the results
			 $fix_results = $wpdb->get_results($wpdb->prepare("SELECT C.CompetitionName as CompName, F.PlayDate as PlayDate,TH.TeamName as HomeTeam,FR.HomeScore,
	       									FR.AwayScore,TA.TeamName as AwayTeam, F.HomeTeamId as HomeId, F.AwayTeamId as AwayId FROM fixtureresults as FR
										    INNER JOIN fixtures as F ON F.FixtureId = FR.FixtureId
										    INNER JOIN teams AS TH ON F.HomeTeamId=TH.TeamId
									        INNER JOIN teams AS TA ON F.AwayTeamId=TA.TeamId
									        INNER JOIN competitions as C ON F.CompetitionId = C.CompetitionId
										    WHERE F.SeasonId =%d AND Weekno = %d",$seasonid, $weekid));	 
			 // Spew out the table into the data_rows array
			 fputcsv( $fh, $blank_row );
			 $data_rows = array(); 
				 foreach ( $fix_results as $u ) {
						$row = array();
						$row[0] = $u->CompName;
						$row[1] = $u->PlayDate;
						$row[2] = $u->HomeTeam;
						$row[3] = $u->HomeScore;
						$row[4] = $u->AwayTeam;
						$row[5] = $u->AwayScore;
						$data_rows[] = $row;
				 } 
			     foreach ( $data_rows as $data_row ) {
				 	fputcsv( $fh, $data_row );
				 }
			 //fputcsv( $fh, $blank_row );
			 // Close the file
			 fclose( $fh );
			 die();
		  */
		  } else {
		  		if (isset($_GET['seasonid'])) {
	    			$GLOBALS["seasonid"] = $_GET['seasonid'];
				}
		  		$comps = $wpdb->get_var($wpdb->prepare("SELECT CompetitionId FROM competitions WHERE SeasonId = %d",$GLOBALS["seasonid"]));
				
				// Show the form itself if no submission
				$the_form = 'Click the button to create the weekly text. It will show on the screen then press Copy Text. You can then paste into Excel and save as CSV.<br><br>';
				// Fill the season select and show the form
	            $the_form = $the_form . '<form id="theExportForm" method="post" action="">';
	            
	            //NOW PUT THE WEEK DROPDOWN IN
	            $weeklist = getWeekSelect($comps);
				$the_form = $the_form .'<label>Season:</label>
             				<select name="season-list" id="season-list" onchange="fixture_select(\'competition\')">';
            	$qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons WHERE StatusFlag = 'O'");
	             foreach($qrySeason as $season) {
	                            $the_form = $the_form . '   
	                            <option value="' . $season->SeasonId . '">' . $season->SeasonDesc .'</option>';
	             }
                $the_form = $the_form . '</select><br><br><label>Week:</label>
                            <select name="week-list" id="week-list" class="week-list">';
							
	            $the_form = $the_form . $weeklist .'</select>      
	            <div id="wrapper"><input type="submit" name="submit" value ="Export weekly"></div></form><br><br>';
					echo $the_form;
			} // end else
	}
	
	// *********************************************************************************
	
	// HANDLES THE DISPLAY OF FLOATERS **********************************************
	add_shortcode('floater_display_sc', 'floater_display');	
	function floater_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		 include ($location . $rootlocal . '/wp-config.php');
         include ($location . $rootlocal . '/wp-load.php');
         include ($location . $rootlocal . '/wp-includes/pluggable.php'); 
		
		// Check the global teamid and seasonid so we can prefill the team table
		if (isset($_GET['seasonid'])) {
    		$GLOBALS["seasonid"] = $_GET['seasonid'];
		}
		
		$seasonid = $GLOBALS["seasonid"];
		$ftext = 'Below are the players who have played for multiple teams this season as floaters:<br>';
		
		//Get the players for the season who have played for more than one team
		$table_results = $wpdb->get_results($wpdb->prepare("SELECT u.UPlayerId as UId, CONCAT(u.ForeName,' ', u.Surname) as pName, Count(teamId) FROM players as p
															INNER JOIN uniqueplayers as u ON u.UPlayerId = p.UPlayerId
															WHERE p.SeasonId = %d
															GROUP BY UId
															HAVING Count(teamId) > 1
															ORDER BY Count(teamId) DESC",$seasonid));
															
		// With those players loop through and find the teams they played for, display, count etc													
		foreach ($table_results as $floaters) {
			$ftext= $ftext. '<br><b>'. $floaters->pName . '</b><br>';
			
			// Get the team names and ids for each team they played for
			$team_results = $wpdb->get_results($wpdb->prepare("SELECT PlayerId, UPlayerId, TeamName, p.TeamId FROM players as p
															INNER JOIN teams as t ON t.TeamId = p.TeamId
															WHERE UPlayerId = %d AND p.SeasonId = %d",$floaters->UId,$seasonid));
			// Loop through each team
			foreach ($team_results as $teams) {	

			// Now count how many fixtures they've played for that team and report it
				$fcount = $wpdb->get_var($wpdb->prepare("SELECT Count(FixtureId) FROM bdcsnook_snkdb.fixtureresultdetails WHERE HomePlayerId = %d OR AwayPlayerId = %d",$teams->PlayerId,$teams->PlayerId));
				$ftext= $ftext. $teams->TeamName .' ' . $fcount . ' games played.<br>';
			}
			
			
		}
			
		return $ftext;
	}
	
	
	// *********************************************************************************
	
	// HANDLES THE DISPLAY OF SHIPTON QUALIFIERS **********************************************
	add_shortcode('shipton_display_sc', 'shipton_display');	
	function shipton_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		 include ($location . $rootlocal . '/wp-config.php');
         include ($location . $rootlocal . '/wp-load.php');
         include ($location . $rootlocal . '/wp-includes/pluggable.php'); 
		
		// Check the global teamid and seasonid so we can prefill the team table
		if (isset($_GET['seasonid'])) {
    		$GLOBALS["seasonid"] = $_GET['seasonid'];
		}
		
		$seasonid = $GLOBALS["seasonid"];
		$plavtext = 'Below are the players who currently qualify to play in the Ken Shipton end of year tournament:<br><br>';
		
		// Get the live season competitions
		$qrycomp = $wpdb->get_results($wpdb->prepare("SELECT CompetitionId, CompetitionName, RankingPts FROM competitions WHERE SeasonId = %d",$seasonid));
		
		// Loop through each competition
		foreach ($qrycomp as $comp) {
			
			$plavtext = $plavtext . '<b>' . $comp->CompetitionName . '</b><br><br>';
			
			$plavtext = $plavtext . '<table class="mtab-paverages">';
			// Set the table header		
			$plavtext= $plavtext.'
								<thead><tr>
								<th class="ftab-rank">Rank</th>
								<th class="ftab-player1">Player</th>
								<th class="ftab-form">Form</th>
								<th class="ftab-played">Played</th>
								<th class="ftab-won">Won</th>
								<th class="ftab-lost">Lost</th>
								<th class="ftab-average">Average (%)</th>
								</tr></thead><tbody>';
				
				// Find a team in the division to use as a counter for their number of fixtures played
				$dumteamid = $wpdb->get_var($wpdb->prepare("SELECT Min(TeamId) FROM divisiontables WHERE SeasonId = %d AND CompetitionId = %d",$seasonid,$comp->CompetitionId));
				// Now count the fixtures played - only dividing by 2 (when it should be 4 because 4 players a team) as each match plays 2 frames
				$fixcount = $wpdb->get_var($wpdb->prepare("SELECT Count(fd.FixtureId)/2 FROM fixtureresultdetails as fd
											INNER JOIN fixtures as f ON f.FixtureId = fd.FixtureId
											WHERE f.Hometeamid = %d OR f.Awayteamid = %d",$dumteamid,$dumteamid));
				// Now count the games that are unplayed in the season because a team may have dropped out
				$unplayedcount = $wpdb->get_var($wpdb->prepare("SELECT Count(fd.FixtureId)/4 FROM fixtureresultdetails as fd
											INNER JOIN fixtures as f ON f.FixtureId = fd.FixtureId
											WHERE (f.Hometeamid = %d OR f.Awayteamid = %d) AND f.Unplayed=1",$dumteamid,$dumteamid));
				
				// So overall fixcount to be used for Shipton is all matches less those unplayed
				$fixcount = $fixcount - $unplayedcount;
				$average_results = $wpdb->get_results($wpdb->prepare("SELECT p.UPlayerId as UId, CONCAT(u.Forename,' ' , u.Surname) as Name, p.FramesWon as FW, p.FramesLost as FL, 
																     p.Average as AV,  If((p.Frameswon+p.FramesLost)>=%d,1,0) as Shipton FROM playeraverages as p
																	INNER JOIN uniqueplayers as u ON u.UPlayerId = p.UPlayerId
																	WHERE SeasonId = %d AND CompetitionId = %d
																	ORDER BY p.Average DESC, p.FramesWon DESC , p.UPlayerId ASC",floor(($fixcount*0.7)),$seasonid,$comp->CompetitionId));
		
				  $counter = 1;
				  
				  // Shipton is 8 from  Prem and 4 from Divs 1 and 2 so use RankingPts to differentiate which competition is the Premier
				  if ($comp->RankingPts == 3000) {
				  	$stopcount = 8; // Set a stopcount to only display this many rows below
				  } else {
				  	$stopcount = 4;
				  }
				  // Loop through the averages
			      foreach ($average_results as $average) {
						// Only display if the player meets the Shipton average games played requirement and that they are in the top stopcount rows	
						if (($average->Shipton == 1) AND ($counter < $stopcount+1) ) {
							// Lets get the latest form for each player
							$formtext = getPlayerForm($average->UId, $seasonid,$comp->CompetitionId);
							
							$totframes = $average->FW+$average->FL;
						  	// Print the row
									$plavtext = $plavtext .
									'<tr>';				
						  				$plavtext = $plavtext . '<td class="ftab-rank">' . $counter . '</td>';
										$plavtext = $plavtext .
										'<td class="ftab-player1"><a href="http://www.bdcsnooker.org/players?playerid=' . $average->UId . '">' .$average->Name  . '</a></td>
										<td class="ftab-form">' .$formtext . '</td>
										<td class="ftab-played">' . $totframes . '</td>
										<td class="ftab-won">' .$average->FW . '</td>
										<td class="ftab-lost">' .$average->FL . '</td>
										<td class="ftab-average">' . round($average->AV*100,2) . '</td>
									</tr>';
								
								$counter = $counter+1;

						} // end loop of average
				} // for comp
				 $plavtext = $plavtext . '</tbody></table>';
		}
			
		return $plavtext;
	}
	
	
	// *********************************************************************************
	
	
	// HANDLES THE TEAM COMPETITIONS PAGES *********************************************
	add_shortcode('teamtourno_display_sc', 'teamtourno_display');
	function teamtourno_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		 include ($location . $rootlocal . '/wp-config.php');
         include ($location . $rootlocal . '/wp-load.php');
         include ($location . $rootlocal . '/wp-includes/pluggable.php'); 
		
		// Check the global team tournament id and seasonid so we can prefill the team tourno table
		if (isset($_GET['teamtournoid'])) {
    		$GLOBALS["teamtournoid"] = $_GET['teamtournoid'];
		}
		if (isset($_GET['seasonid'])) {
    		$GLOBALS["seasonid"] = $_GET['seasonid'];
		}
		$tournotabletext = getTeamTournoTable($GLOBALS["seasonid"],$GLOBALS["teamtournoid"]);
		
		$the_form = '
             <form id="theTeamTournoForm">';
             
             // GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
             $qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons ORDER BY SeasonId DESC");
                 
             $the_form = $the_form . '      
             <div id="wrapper">
             <div class="frmDropDown">
             <div class="row">
             <label>Season:</label>
             <select autofocus name="season-list" id="season-list" onchange="teamtourno_select(\'season\')">
             <option value="">Select Season</option>';
            
             foreach($qrySeason as $season) {
             		// Check the global season so we can set it to selected
					$selected = '';
					if ($GLOBALS["seasonid"] === $season->SeasonId){
						$selected = 'selected = "selected" ';
					}
                    $the_form = $the_form . '   
                    <option ' . $selected . 'value="' . $season->SeasonId . '">' . $season->SeasonDesc .'</option>';
             }
			//NOW PUT THE TOURNAMENT DROPDOWN IN
			$teamtournodropdown = getTournoSelect($GLOBALS["seasonid"],"Team");
			
             $the_form = $the_form . '</select><br>
                            <label>Tournament:</label>
                            <select name="teamtourno-list" id="teamtourno-list" class="teamtourno-list" onchange="teamtourno_select(\'tournament\')">';
             $the_form = $the_form . $teamtournodropdown;
                           // <option value="">Select Tournament</option>';
                            
             $the_form = $the_form . '' . '</select>
             <input name="action" type="hidden" value="the_ajax_teamtournodisplay" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form -->
             </form><br>';
			 
			 $the_form = $the_form . '<hr><div id="tourno-tab">' . $tournotabletext . '</div>';
			 echo $the_form;
	}
		
	function ajax_return_teamtournodisplay(){
		global $rootlocal;
        global $wpdb;
        $location = $_SERVER['DOCUMENT_ROOT'];
                
        include ($location . $rootlocal . '/wp-config.php');
        include ($location . $rootlocal . '/wp-load.php');
        include ($location . $rootlocal . '/wp-includes/pluggable.php');
        
        // Return changes
        $seasonid = $_POST['season-list'];
		$tournoid = $_POST['teamtourno-list'];
		
		$teamtournolist = getTournoSelect($seasonid,"Team");
		$tabletext = getTeamTournoTable($seasonid,$tournoid);
		
        $returntext = array("Tournament"=>$teamtournolist,
        					"Table"=>$tabletext
                            );
                 
        echo json_encode($returntext); 
         
          
        die();// wordpress may print out a spurious zero without this - can be particularly bad if using json        
		
	}
	
	// *********************************************************************************
	
	// HANDLES THE PLAYER COMPETITIONS PAGES *********************************************
	add_shortcode('playertourno_display_sc', 'playertourno_display');
	function playertourno_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		 include ($location . $rootlocal . '/wp-config.php');
         include ($location . $rootlocal . '/wp-load.php');
         include ($location . $rootlocal . '/wp-includes/pluggable.php'); 
		
		// Check the global player tournament id and seasonid so we can prefill the team tourno table
		if (isset($_GET['playertournoid'])) {
    		$GLOBALS["playertournoid"] = $_GET['playertournoid'];
		}
		if (isset($_GET['seasonid'])) {
    		$GLOBALS["seasonid"] = $_GET['seasonid'];
		}
		$tournotabletext = getPlayerTournoTable($GLOBALS["seasonid"],$GLOBALS["playertournoid"]);
		
		$the_form = '
             <form id="thePlayerTournoForm">';
             
             // GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
             $qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons ORDER BY SeasonId DESC");
                 
             $the_form = $the_form . '      
             <div id="wrapper">
             <div class="frmDropDown">
             <div class="row">
             <label>Season:</label>
             <select autofocus name="season-list" id="season-list" onchange="playertourno_select(\'season\')">
             <option value="">Select Season</option>';
            
             foreach($qrySeason as $season) {
             	// Check the global season so we can set it to selected
				$selected = '';
				if ($GLOBALS["seasonid"] === $season->SeasonId){
					$selected = 'selected = "selected" ';
				}
                $the_form = $the_form . '   
                <option ' . $selected . 'value="' . $season->SeasonId . '">' . $season->SeasonDesc .'</option>';
             }
			//NOW PUT THE TOURNAMENT DROPDOWN IN
			$playertournodropdown = getTournoSelect($GLOBALS["seasonid"],"Player");
			
             $the_form = $the_form . '</select><br>
                            <label>Tournament:</label>
                            <select name="playertourno-list" id="playertourno-list" class="playertourno-list" onchange="playertourno_select(\'tournament\')">';
			 $the_form = $the_form . $playertournodropdown;
			 
             //               <option value="">Select Tournament</option>';
                            
             $the_form = $the_form . '' . '</select>
             <input name="action" type="hidden" value="the_ajax_playertournodisplay" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form -->
             </form><br>';
			 
			 $the_form = $the_form . '<hr><div id="tourno-tab">' . $tournotabletext . '</div>';
			 echo $the_form;
	}
		
	function ajax_return_playertournodisplay(){
		global $rootlocal;
        global $wpdb;
        $location = $_SERVER['DOCUMENT_ROOT'];
                
        include ($location . $rootlocal . '/wp-config.php');
        include ($location . $rootlocal . '/wp-load.php');
        include ($location . $rootlocal . '/wp-includes/pluggable.php');
        
        // Return changes
        $seasonid = $_POST['season-list'];
		$tournoid = $_POST['playertourno-list'];
		
		$playertournolist = getTournoSelect($seasonid,"Player");
		$tabletext = getPlayerTournoTable($seasonid,$tournoid);
		
        $returntext = array("Tournament"=>$playertournolist,
        					"Table"=>$tabletext
                            );
                 
        echo json_encode($returntext); 
        die();// wordpress may print out a spurious zero without this - can be particularly bad if using json		
	}
	
	// *********************************************************************************
	
	// HANDLES THE SPONSORS PAGES ******************************************************
	add_shortcode('sponsors_display_sc', 'sponsors_display');
	
	function sponsors_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		 include ($location . $rootlocal . '/wp-config.php');
         include ($location . $rootlocal . '/wp-load.php');
         include ($location . $rootlocal . '/wp-includes/pluggable.php'); 
		 
		 $qrysponsors = $wpdb->get_results("SELECT * FROM sponsors WHERE LiveSponsor = 1 ORDER BY SponsorOrder ASC" );
		 
		 $sponsortext = '';
		 $tabclass = '<table class="ftab-sponsors">';
		 
		 foreach ($qrysponsors as $sponsor){
		 	
			// Set name
			$sponsortext = $sponsortext . '<h3>' . $sponsor->SponsorName . '</h3>';
			// Make the table
			$sponsortext = $sponsortext . '' . $tabclass. '
					    <thead><tr>
						<th class="ftab-contact">Contact details</th>
						<th class="ftab-image"> </th>
						</tr></thead><tbody>';
						
			// Get the image for the sponsor now... a bit messy	
			$imgs = get_images_from_media_library($sponsor->ImageRef);		
			
			$sponsortext = $sponsortext .
					'<tr>
						<td class="ftab-contact">Email: ' . $sponsor->SponsorEmail . '<br>Tel: ' . $sponsor->SponsorTel . '<br>Mobile: ' . $sponsor->SponsorMobile .'<br>Website: <a href="' . $sponsor->SponsorURL . '">' . $sponsor->SponsorURL . '</a></td>
						<td class="ftab-image"><a href="' . $sponsor->SponsorURL . '"><img src="' . $imgs . '" alt="' . $sponsor->SponsorName .'"></img></a></td>
					</tr>';
					
			$sponsortext = $sponsortext . '</tbody></table><br>';
		 }
		
		 echo $sponsortext;
	}

	// HANDLES SHOWING THE OPEN COMPETITIONS IN A NORMAL TABLE FOR A NORMAL PAGE
	add_shortcode('comps_display_sc', 'comps_display');
	function comps_display(){
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
		
		 $compstext = getOpenCompetitions($GLOBALS["seasonid"],0); // 0 for a normal table
		 echo $compstext;
	}
	// *********************************************************************************
	
	// Function for looping around media library to find relevant sponsor image
	function get_images_from_media_library($imageref) {
	    $args = array(
	        'post_type' => 'attachment',
	        'post_mime_type' =>'image',
	        'post_status' => 'inherit',
	        'posts_per_page' => 100,
	        'orderby' => 'rand'
	    );
	    $query_images = new WP_Query( $args );
	    $images = '';
	    foreach ( $query_images->posts as $image) {
	    	
			// Strip the image name from the guid which is the full URL of the image stored in the media library
			$imagename = (string)substr($image->guid, strrpos($image->guid, '/')+1);
			// Check if the same and return the right guid for the sponsor
	    	if ($imagename == $imageref) {
	    		$images = $image->guid;
			}
	    }
	    return $images;
	}
	
	
	// Function for returning fixtures for a team based on teamid and season id
	function getWeeklyFixturesbyDiv($seasonid,$competitionid,$range){
		// seasonid is the season in the db
		// competitionid is the division from the competitions table
		// range is the number of days you want to show fixtures for
		
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		// Set globals
		$GLOBALS["seasonid"] = $seasonid;
		$GLOBALS["divisionid"] = $competitionid;
		
		if ($competitionid == 999) { // all comps for a season required 
			//$fix =$wpdb->get_results('call sp_getWeeklyFixtures(' . $seasonid . ', ' . $range . ')');
			$fix =$wpdb->get_results($wpdb->prepare("SELECT f.Weekno as WeekNo, f.PlayDate as Date, t.TeamName as HomeTeam, s.TeamName as AwayTeam, f.HomeTeamId as HomeId, f.AwayTeamId as AwayId FROM fixtures as f
								INNER JOIN teams as t ON f.HomeTeamId = t.TeamId
								INNER JOIN teams as s ON f.AwayTeamId = s.TeamId
								WHERE f.SeasonId = %d AND f.PlayDate BETWEEN ADDDATE(NOW(),-1) AND ADDDATE(NOW(),%d) ",$seasonid,$range ));
		
		} else { // just one comp for a season
			//$fix =$wpdb->get_results('call sp_getWeeklyFixturesByDiv(' . $seasonid . ', ' . $competitionid . ', '. $range . ')');

			$loopadd  = 0;
			while (count($fix) == 0 && $loopadd < 35 ) {
				$fix =$wpdb->get_results($wpdb->prepare("SELECT f.Weekno as WeekNo, f.PlayDate as Date, t.TeamName as HomeTeam, s.TeamName as AwayTeam, f.HomeTeamId as HomeId, f.AwayTeamId as AwayId FROM fixtures as f
									INNER JOIN teams as t ON f.HomeTeamId = t.TeamId
									INNER JOIN teams as s ON f.AwayTeamId = s.TeamId
									WHERE f.SeasonId = %d AND f.CompetitionId = %d
									AND f.PlayDate BETWEEN ADDDATE(NOW(),-1) AND ADDDATE(NOW(),%d) ",$seasonid,$competitionid,$range + $loopadd));
				$loopadd = $loopadd + 7;
			}
			
		}

		$tfix='';					 
		
		if (count($fix) > 0 ) {
				
			$tfix=$tfix . '<table class="mtab-weekfix">
					<thead><tr>
						<th class="mtab-weekno">Wk</th>
						<th class="mtab-date">Date</th>
						<th class="mtab-fixhome">Home Team</th>
						<th class="mtab-fixaway">Away Team</th>
					</thead></tr><tbody>';
			 
			foreach ($fix as $fix) {
				
				$tfix = $tfix . '
							<tr>
								<td class="mtab-weekno">' .$fix->WeekNo . '</td>
								<td class="mtab-date">' .date("d-M",strtotime($fix->Date)). '</td>
								<td class="mtab-fixhome"><a href="http://www.bdcsnooker.org/teams?teamid=' . $fix->HomeId . '&seasonid=' . $seasonid . '">' .$fix->HomeTeam . '</a></td>
								<td class="mtab-fixaway"><a href="http://www.bdcsnooker.org/teams?teamid=' . $fix->AwayId . '&seasonid=' . $seasonid . '">' .$fix->AwayTeam. '</td>
							</tr>';
			}
					
			$tfix= $tfix. '</tbody></table><div align="center">Rest fixtures not shown</div>';
		} else {
			$tfix = $tfix . "No fixtures to display";
		}
		
		
		return $tfix;
	}
	
	// Function for returning results for a team based on teamid and season id and putting them into a table
	function getWeeklyResultsbyDiv($seasonid,$competitionid,$range,$type){
		// seasonid is the season in the db
		// competitionid is the division from the competitions table, but if its 999 then it's all comps for that season
		// range is the number of days you want to show fixtures for
		// type 0 is whether you want result returned as a table (for a widget or page) or 1 as text (for scrolling)
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		// Set globals
		$GLOBALS["seasonid"] = $seasonid;
		$GLOBALS["divisionid"] = $competitionid;
		
		// Create results for that range and season and competition
		if ($competitionid == 999) { // all comps for a season required
			$fixresults=$wpdb->get_results($wpdb->prepare("SELECT F.WeekNo, F.CompetitionId, C.CompetitionName as CompName, F.PlayDate as PlayDate,TH.TeamName as HomeTeam,FR.HomeScore,
	       									FR.AwayScore,TA.TeamName as AwayTeam, F.HomeTeamId as HomeId, F.AwayTeamId as AwayId FROM fixtureresults as FR
										    INNER JOIN fixtures as F ON F.FixtureId = FR.FixtureId
										    INNER JOIN teams AS TH ON F.HomeTeamId=TH.TeamId
									        INNER JOIN teams AS TA ON F.AwayTeamId=TA.TeamId
									        INNER JOIN competitions as C ON F.CompetitionId = C.CompetitionId
										    WHERE F.SeasonId =%d AND F.PlayDate BETWEEN ADDDATE(NOW(),%d) AND ADDDATE(NOW(),1)",$seasonid,$range ));	
		} else { // just one comp for a season
			$fixresults=$wpdb->get_results($wpdb->prepare("SELECT F.WeekNo, F.CompetitionId, C.CompetitionName as CompName, F.PlayDate as PlayDate,TH.TeamName as HomeTeam,FR.HomeScore,
	       									FR.AwayScore,TA.TeamName as AwayTeam, F.HomeTeamId as HomeId, F.AwayTeamId as AwayId FROM fixtureresults as FR
										    INNER JOIN fixtures as F ON F.FixtureId = FR.FixtureId
										    INNER JOIN teams AS TH ON F.HomeTeamId=TH.TeamId
									        INNER JOIN teams AS TA ON F.AwayTeamId=TA.TeamId
									        INNER JOIN competitions as C ON F.CompetitionId = C.CompetitionId
										    WHERE F.SeasonId = %d AND F.CompetitionId = %d
										    AND F.PlayDate BETWEEN ADDDATE(NOW(),%d) AND ADDDATE(NOW(),1)",$seasonid,$competitionid,$range));
		}					
		$rfix='';
		
		if ($type==0) {  // create for a table
			$rfix= $rfix . '<table class="mtab-weekresult">
					<thead><tr>
						<th class="mtab-weekno">Wk</th>
						<th class="mtab-date">Date</th>
						<th class="mtab-fixhome">Home</th>
						<th class="mtab-result">Result</th>
						<th class="mtab-fixaway">Away</th>
					</tr></thead><tbody>';
		} else {  // for just text
		   $rfix=$rfix . '<div class "scrolltext">';
		}
		
		$lastcompname = "";
		
		if (count($fixresults) > 0 ) {
			
			foreach ($fixresults as $fixresults){
				if ($lastcompname == $fixresults->CompName) {
					$divname = "";
				} else {
					$divname = $fixresults->CompName . ' - Week ' . $fixresults->WeekNo . ': ';
					$lastcompname = $fixresults->CompName;
				}
				
				if ($type==0) {
					$rfix = $rfix . '
								<tr>
									<td class="mtab-weekno">' .$fixresults->WeekNo . '</td>
									<td class="mtab-date">' .date("d-M",strtotime($fixresults->PlayDate)). '</td>
									<td class="mtab-fixhome"><a href="http://www.bdcsnooker.org/teams?teamid=' . $fixresults->HomeId . '&seasonid=' . $seasonid . '">' .$fixresults->HomeTeam . '</a></td>
									<td class="mtab-result">' .$fixresults->HomeScore. ' - ' .$fixresults->AwayScore. '</td>
									<td class="mtab-fixaway"><a href="http://www.bdcsnooker.org/teams?teamid=' . $fixresults->AwayId . '&seasonid=' . $seasonid . '">' .$fixresults->AwayTeam . '</a></td>
								</tr>';
				} else {
					$rfix = $rfix . $divname;
					$rfix = $rfix . '<a href="http://www.bdcsnooker.org/teams?teamid=' . $fixresults->HomeId . '&seasonid=' . $seasonid . '">' .$fixresults->HomeTeam . '
									 </a>&nbsp;' .$fixresults->HomeScore. ' - ' .$fixresults->AwayScore. '&nbsp;
									 <a href="http://www.bdcsnooker.org/teams?teamid=' . $fixresults->AwayId . '&seasonid=' . $seasonid . '">' .$fixresults->AwayTeam . '</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				}

				
			
			}
			if ($type==0) {
				$rfix= $rfix. '</tbody></table>';
			} else {
				$rfix= $rfix. '</div>';
			}
		} else {
			$rfix = "No results to display";
		}
		return $rfix;
	}
	
	// Function for returning results for the latest player competitions played
	function getPlayerCompResults($seasonid, $range,$type){
		// seasonid is the season in the db - not used at the moment as just implemented Dec17 may use with seasonid later
		// range is the number of days you want to show fixtures for
		// type 0 is whether you want result returned as a table (for a widget or page) or 1 as text (for scrolling)
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		// Set globals
		$GLOBALS["seasonid"] = $seasonid;
		$GLOBALS["divisionid"] = $competitionid;
		
		// Create results for that range and season and competition
		
		$fixresults=$wpdb->get_results($wpdb->prepare("SELECT pf.RoundDesc as RoundName, pt1.Name as HomeName, pt2.Name as AwayName, pf.HomeScore as HomeSc, pf.AwayScore as AwaySc
														FROM playertournofixture AS pf
														INNER JOIN playertournoround AS pr ON pf.PlayerTournoRoundId = pr.PlayerRoundId
														INNER JOIN tournament AS t ON pr.TournamentId = t.TournamentId
														INNER JOIN playertournoentry as pt1 ON pf.HomeEntryId = pt1.TournamentEntryId
														INNER JOIN playertournoentry as pt2 ON pf.AwayEntryId = pt2.TournamentEntryId
														WHERE (pf.HomeScore <> 0 OR pf.AwayScore <> 0) AND pf.Timestamp BETWEEN ADDDATE(NOW(),%d) AND ADDDATE(NOW(),1)
														ORDER BY TIMESTAMP DESC, pf.FixtureDate DESC
														LIMIT 10",$range ));						
		$rfix='';
		
		if ($type==0) {  // create for a table
			$rfix= $rfix . '<table class="mtab-weekresult">
					<thead><tr>
						<th class="mtab-round">Round</th>
						<th class="mtab-fixhome">Home</th>
						<th class="mtab-result">Result</th>
						<th class="mtab-fixaway">Away</th>
					</tr></thead><tbody>';
		} else {  // for just text
		   $rfix=$rfix . '<div class "scrolltext">';
		}
		
		if (count($fixresults) > 0 ) {
			
			foreach ($fixresults as $fixresults){
				
				if ($type==0) {
					$rfix = $rfix . '
								<tr>
									<td class="mtab-round">' .$fixresults->RoundName . '</td>
									<td class="mtab-fixhome">' . $fixresults->HomeName . '</td>
									<td class="mtab-result">' .$fixresults->HomeSc. ' - ' .$fixresults->AwaySc. '</td>
									<td class="mtab-fixaway">' . $fixresults->AwayName . '</td>
								</tr>';
				} else {
					$rfix = $rfix . $fixresults->RoundName . ':&nbsp;';
					$rfix = $rfix . $fixresults->HomeName . '
									 &nbsp;' .$fixresults->HomeSc. ' - ' .$fixresults->AwaySc. '&nbsp;&nbsp;'
									 .$fixresults->AwayName . '</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				}
			
			}
			if ($type==0) {
				$rfix= $rfix. '</tbody></table>';
			} else {
				$rfix= $rfix. '</div>';
			}
		} else {
			$rfix = "No results to display";
		}
		return $rfix;
	}

	//Function for returning a team tournament table based on seasonid and tournamentid
	function getTeamTournoTable($seasonid,$tournoid){
		//seasonid = relates to which season
		//tournoid = relates to the actual tournament no. in the db
		
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
	
		$tournotabtext = '';
		
		// First the sponsor logo
		$sponid = $wpdb->get_var($wpdb->prepare("SELECT SponsorId FROM tournament WHERE TournamentId = %d" , $tournoid));
		// Now the image name
		$imageref = $wpdb->get_var($wpdb->prepare("SELECT ImageRef FROM sponsors WHERE SponsorId = %d" , $sponid));
		// Get the image for the sponsor now... a bit messy	
		$imgs = get_images_from_media_library($imageref);
		
		$tabclass = 'ftab-teamtourno';
		
		// Now make the table for the sponsor image but only if theres a sponsor - 12 is the No Sponsor row in the db
		if ($sponid <> 12){
			$tournotabtext = $tournotabtext . '<table class="' . $tabclass . '">
								<thead></thead><tbody><tr>
								<td class="ftab-image"><img src="' . $imgs . '" alt="' . $sponid .'"></img></td>
								</tr></tbody></table>';
		}
					
		// Get the tournament description so we can show it
		$tournodesc = $wpdb->get_var($wpdb->prepare("SELECT Description FROM tournament WHERE TournamentId = %d", $tournoid));
		$tournoname = $wpdb->get_var($wpdb->prepare("SELECT Name FROM tournament WHERE TournamentId = %d", $tournoid));
		
		$tournotabtext = $tournotabtext . '<h3>' . $tournoname . '</h3>';
		$tournotabtext = $tournotabtext . '<p>' . $tournodesc . '</p><br>';
		
		// First get the Rounds for the competition ID selected
		$rounds = $wpdb->get_results($wpdb->prepare("SELECT * FROM teamtournoround WHERE TournamentId = %d ORDER BY Sequence ASC",$tournoid ));	
	    
	    // The loop through each round
		foreach ($rounds as $round) {	
			// Display round name
			$tournotabtext = $tournotabtext . '<h3>' . $round->Name . '</h3>';
		
			// Set the table header and then get the data	
			$tournotabtext = $tournotabtext . '' . '<table class="' . $tabclass . '">
					    <tr>
						<th class="ftab-player1">Home</th>
						<th class="ftab-result">Result</th>
						<th class="ftab-player2">Away</th>
						<th class="ftab-club">Venue</th>
						<th class="ftab-date">Date</th>
						</tr><tbody>';
			// Now get the fixtures by round
			$fixtures = $wpdb->get_results($wpdb->prepare("SELECT * FROM teamtournofixture WHERE TournamentRoundId = %d" , $round->TeamRoundId ));			
			foreach ($fixtures as $fixture) {
				// Need to look up home and away and club and set result
				$hometeam = $wpdb->get_var($wpdb->prepare("SELECT TeamName FROM teams WHERE TeamId = %d" , $fixture->HomeEntryId ));
				$awayteam = $wpdb->get_var($wpdb->prepare("SELECT TeamName FROM teams WHERE TeamId = %d" , $fixture->AwayEntryId ));
				$clubname = $wpdb->get_var($wpdb->prepare("SELECT ClubName FROM clubs WHERE ClubId = %d" , $fixture->ClubId ));
				$result = $fixture->HomeScore . ' - ' . $fixture->AwayScore;
				
				// Format the date
				$fixdate = date('d M y',strtotime($fixture->FixtureDate));
				// Some checks on bye games or games not played yet as they are 0-0
				if ($hometeam === Null){
					$hometeam = "Bye";
					$result = "Bye";
				}
				
				if ($awayteam === Null){
					$awayteam = "Bye";
					$result = "Bye";
				}
				if ($awayteam == "Bye" or $hometeam == "Bye") {
					$result = "Bye";
				}
				if ($result == "0 - 0") {
					$result = "-";
				}
				$hbolds = "";
				$hbolde = "";
				$abolds = "";
				$abolde = "";
				if ($fixture->HomeScore > $fixture->AwayScore){
					$hbolds = "<b>";
					$hbolde = "</b>";
				}
				if ($fixture->AwayScore > $fixture->HomeScore){
					$abolds = "<b>";
					$abolde = "</b>";
				}
				if ($awayteam == "Bye") {
					$hbolds = "<b>";
					$hbolde = "</b>";
				}
				if ($hometeam == "Bye") {
					$abolds = "<b>";
					$abolde = "</b>";
				}
				
				$tournotabtext = $tournotabtext .
					'<tr>
						<td class="ftab-player1">' . $hbolds . '<a href="http://www.bdcsnooker.org/teams?teamid=' . $fixture->HomeEntryId . '&seasonid=' . $seasonid . '">' . $hometeam . $hbolde . '</a></td>
						<td class="ftab-result">' . $result . '</td>
						<td class="ftab-player2">' . $abolds . '<a href="http://www.bdcsnooker.org/teams?teamid=' . $fixture->AwayEntryId . '&seasonid=' . $seasonid . '">' . $awayteam . $abolde . '</a></td>
						<td class="ftab-club"><a href="http://www.bdcsnooker.org/clubs?clubid=' . $fixture->ClubId . '">' . $clubname . '</a></td>
						<td class="ftab-date">' . $fixdate . '</td>
					</tr>';
			}
						
			$tournotabtext = $tournotabtext . '</tbody></table><br>';
		}	
		
		return $tournotabtext;
	}
	
	//Function for returning a team tournament table based on seasonid and tournamentid
	function getPlayerTournoTable($seasonid,$tournoid){
		//seasonid = relates to which season
		//tournoid = relates to the actual tournament no. in the db
		
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
	
		$tournotabtext = '';
		// First the sponsor logo
		$sponid = $wpdb->get_var($wpdb->prepare("SELECT SponsorId FROM tournament WHERE TournamentId = %d" ,$tournoid));
		// Now the image name
		$imageref = $wpdb->get_var($wpdb->prepare("SELECT ImageRef FROM sponsors WHERE SponsorId = %d" , $sponid));
		// Get the image for the sponsor now... a bit messy	
		$imgs = get_images_from_media_library($imageref);
		
		$tabclass = 'ftab-playertourno';
		
		// Now make the table for the sponsor image but only if theres a sponsor - 12 is the No Sponsor row in the db
		if ($sponid <> 12){
			$tournotabtext = $tournotabtext . '<table class="' . $tabclass . '">
							<thead></thead><tbody><tr>
							<td class="ftab-image"><img src="' . $imgs . '" alt="' . $sponid .'"></img></td>
							</tr></tbody></table>';
		}
							
		// Get the tournament description so we can show it
		$tournodesc = $wpdb->get_var($wpdb->prepare("SELECT Description FROM tournament WHERE TournamentId = %d" , $tournoid));
		$tournoname = $wpdb->get_var($wpdb->prepare("SELECT Name FROM tournament WHERE TournamentId = %d" , $tournoid));
		$tournohandicap = $wpdb->get_var($wpdb->prepare("SELECT Handicapped FROM tournament WHERE TournamentId = %d" , $tournoid));
		
		$tournotabtext = $tournotabtext . '<h3>' . $tournoname . '</h3>';
		$tournotabtext = $tournotabtext . '<p>' . $tournodesc . '</p><br>';
		
		// First get the Rounds for the tournament ID selected
		$rounds = $wpdb->get_results($wpdb->prepare("SELECT * FROM playertournoround WHERE TournamentId = %d ORDER BY Sequence ASC",$tournoid ));	
	    
	    // The loop through each round
		foreach ($rounds as $round) {	
			// Display round name
			$tournotabtext = $tournotabtext . '<h3>' . $round->Name . '</h3>';
		
			// Set the table header and then get the data	
			$tournotabtext = $tournotabtext . '' . '<table class="' . $tabclass . '">
					    <thead><tr>
						<th class="ftab-player1">Home</th>
						<th class="ftab-result">Result</th>
						<th class="ftab-player2">Away</th>
						<th class="ftab-club">Venue</th>
						<th class="ftab-date">Date</th>
						</tr></thead><tbody>';
			// Now get the fixtures by round
			$fixtures = $wpdb->get_results($wpdb->prepare("SELECT * FROM playertournofixture WHERE PlayerTournoRoundId = %d",$round->PlayerRoundId ));			
			foreach ($fixtures as $fixture) {
				// Need to look up home and away and club and set result
				$hometeam = $wpdb->get_var($wpdb->prepare("SELECT Name FROM playertournoentry WHERE TournamentEntryId = %d" , $fixture->HomeEntryId));
				$awayteam = $wpdb->get_var($wpdb->prepare("SELECT Name FROM playertournoentry WHERE TournamentEntryId = %d" , $fixture->AwayEntryId));
				
				// Need to convert the entry id into a unique player for the clickable link
				// Need to check if its a singles or doubles
				$hplayer1id = $wpdb->get_var($wpdb->prepare("SELECT UPlayer1Id FROM playertournoentry WHERE TournamentEntryId =  %d" , $fixture->HomeEntryId));
				$hplayer2id = $wpdb->get_var($wpdb->prepare("SELECT UPlayer2Id FROM playertournoentry WHERE TournamentEntryId =  %d" , $fixture->HomeEntryId));
				$aplayer1id = $wpdb->get_var($wpdb->prepare("SELECT UPlayer1Id FROM playertournoentry WHERE TournamentEntryId =  %d" , $fixture->AwayEntryId));
				$aplayer2id = $wpdb->get_var($wpdb->prepare("SELECT UPlayer2Id FROM playertournoentry WHERE TournamentEntryId =  %d" , $fixture->AwayEntryId));
				
				// Need to check if there's handicaps and create text if necessary
				$homehandicap = '';
				$awayhandicap = '';
				if ($tournohandicap == 1){
					$homehandicap = $wpdb->get_var($wpdb->prepare("SELECT Handicap FROM playertournoentry WHERE TournamentEntryId = %d" ,$fixture->HomeEntryId));
					$homehandicap = ' (' . $homehandicap . ')';
					if ($fixture->AwayEntryId <> 0){
						$awayhandicap = $wpdb->get_var($wpdb->prepare("SELECT Handicap FROM playertournoentry WHERE TournamentEntryId = %d" , $fixture->AwayEntryId));
						$awayhandicap = ' (' . $awayhandicap . ')';
					}
				}
				// Get the clubname
				$clubname = $wpdb->get_var($wpdb->prepare("SELECT ClubName FROM clubs WHERE ClubId = %d" , $fixture->ClubId));
				$result = $fixture->HomeScore . ' - ' . $fixture->AwayScore;
				
				// Format the date
				$fixdate = date('d M y',strtotime($fixture->FixtureDate));
				
				// Create the link text for home and away players
				// First home team - check if slash in name as this is the name separator for doubles
				$hinclslash = strpos($hometeam,'/');
				if ($hinclslash === false) {
					// Singles
				    $hlink = '<a href="http://www.bdcsnooker.org/players?playerid=' . $hplayer1id . '">' . $hometeam . $homehandicap . '</a>';
				} else {
					// Doubles
					$h1playername = substr($hometeam,0,$hinclslash-1);
					$h2playername = substr($hometeam,$hinclslash+2,40);
				    $hlink = '<a href="http://www.bdcsnooker.org/players?playerid=' . $hplayer1id . '">' . $h1playername . '</a> / <a href="http://www.bdcsnooker.org/players?playerid=' . $hplayer2id . '">' . $h2playername . '</a> ' .$homehandicap ;
				}
				// Next away team - check if slash in name as this is the name separator for doubles
				$ainclslash = strpos($awayteam,'/');
				if ($ainclslash === false) {
					// Singles
					$alink = '<a href="http://www.bdcsnooker.org/players?playerid=' . $aplayer1id . '">' . $awayteam . $awayhandicap . '</a>';
				} else {
					// Doubles
					$a1playername = substr($awayteam,0,$ainclslash-1);
					$a2playername = substr($awayteam,$ainclslash+2,40);
				    $alink = '<a href="http://www.bdcsnooker.org/players?playerid=' . $aplayer1id . '">' . $a1playername . '</a> / <a href="http://www.bdcsnooker.org/players?playerid=' . $aplayer2id . '">' . $a2playername . '</a> ' .$awayhandicap ;
				}
				
				// Fill in the byes
				if ($hometeam === Null){
					$hometeam = "Bye";
					$result = "Bye";
					$hlink = $hometeam . $homehandicap;
				}
				
				if ($awayteam === Null){
					$awayteam = "Bye";
					$result = "Bye";
					$alink = $awayteam . $awayhandicap;
				}
				if ($result == "0 - 0") {
					$result = "-";
				}
				$hbolds = "";
				$hbolde = "";
				$abolds = "";
				$abolde = "";
				if ($fixture->HomeScore > $fixture->AwayScore){
					$hbolds = "<b>";
					$hbolde = "</b>";
				}
				if ($fixture->AwayScore > $fixture->HomeScore){
					$abolds = "<b>";
					$abolde = "</b>";
				}
				if ($awayteam == "Bye") {
					$hbolds = "<b>";
					$hbolde = "</b>";
				}
				if ($hometeam == "Bye") {
					$abolds = "<b>";
					$abolde = "</b>";
				}
				
				$tournotabtext = $tournotabtext .
					'<tr>
						<td class="ftab-player1">' . $hbolds . $hlink . $hbolde .'</td>
						<td class="ftab-result">' . $result . '</td>
						<td class="ftab-player2">' . $abolds .$alink . $abolde . '</td>
						<td class="ftab-club"><a href="http://www.bdcsnooker.org/clubs?clubid=' . $fixture->ClubId . '">' . $clubname . '</a></td>
						<td class="ftab-date">' . $fixdate . '</td>
					</tr>';
			}
						
			$tournotabtext = $tournotabtext . '</tbody></table><br>';
		}
		
		
		return $tournotabtext;
	}

	//Function for returning a league table based on seasonid and competitionid
	function getDivTable($seasonid,$competitionid,$returnrows,$minitab,$widtype,$snippet) {
		//seasonid = relates to which season
		//competitionid = relates to the actual division no. in the db
		//returnrows = enables the function just to return the top x rows
		//minitab = 1=only return Name/Played/Pts cols, 0=all cols
		//widtype = if the table's going on a widget that needs to be scrollable=1,0=not on a widget
		//snippet = 0 = full table, n = the teamid to show only the rows around a that particular chosen team in the table
		
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		$divtext = '';
		// First the sponsor logo
		$sponid = $wpdb->get_var($wpdb->prepare("SELECT SponsorId FROM competitions WHERE CompetitionId = %d" , $competitionid));
		// Now the image name
		$imageref = $wpdb->get_var($wpdb->prepare("SELECT ImageRef FROM sponsors WHERE SponsorId = %d" , $sponid));
		// Get the image for the sponsor now... a bit messy	
		$imgs = get_images_from_media_library($imageref);
		//Get the team photo image for the team table
		$imageref2 = $wpdb->get_var($wpdb->prepare("SELECT ImageRef FROM teams WHERE TeamId = %d" , $snippet));
		$imgs2 = get_images_from_media_library($imageref2);
		
		// For image sponsor
		if ($widtype==1){
			$tabclass = '<table class="mtab-imgtable">';
		} else {
			$tabclass = '<table class="ftab-imgtable">';
		}
		$divtext = '' . $tabclass;
		// Now make the table for the sponsor image
		$divtext = $divtext .
					'<thead></thead><tbody><tr>
						<td class="ftab-image"><img src="' . $imgs . '" alt="' . $imageref .'"></img></td>';
						
		if ($snippet == 0) {
			$divtext = $divtext . '</tr></tbody></table>';
		} else {
			$divtext = $divtext . '</tr><tr><td class="ftab-image"><img src="' . $imgs2 . '" alt="' . $imageref2 .'"></img></tr></tbody></table>';
		}
					
					
		
		// For the div table
		// If it's a snippet we need to make it a different class because we don't need the colours for promotion / relegation
		if ($snippet == 0){
			if ($widtype==1){
				$tabclass = '<table class="mtab-divtable">';
			} else {
				$tabclass = '<table class="ftab-divtable">';
			}
		} else {
				$tabclass = '<table class="stab-divtable">';
		}
		$divtext = $divtext . $tabclass;
		//Is this a mini table or full?
		if ($minitab==0) {
			$divtext= $divtext.'
								<thead><tr>
								<th class="ftab-team">Team</th>
								<th class="ftab-form">Form</th>
								<th class="ftab-played">P</th>
								<th class="ftab-won">W</th>
								<th class="ftab-draw">D</th>
								<th class="ftab-lost">L</th>
								<th class="ftab-for">F</th>
								<th class="ftab-agst">A</th>
								<th class="ftab-diff">+/-</th>
								<th class="ftab-pts">Pts</th>
								</tr></thead>';
		} else {
			$divtext= $divtext.'<thead><tr>
								<th class="mtab-team">Team</th>
								<th class="mtab-played">P</th>
								<th class="mtab-pts">Pts</th>
								</tr></thead><tbody>';
		}
				
		 // Get the division table results
		 // We need the sort order for the division table as this could change over years depending on the rules		
		 // Get 1st, 2nd, 3rd and 4th orders...		
		 $taborder1 = $wpdb->get_var($wpdb->prepare("SELECT OrderName FROM divisiontableorder WHERE SeasonId = %d AND OrderSequence = %d",$seasonid,1));
		 $taborder2 = $wpdb->get_var($wpdb->prepare("SELECT OrderName FROM divisiontableorder WHERE SeasonId = %d AND OrderSequence = %d",$seasonid,2));
		 $taborder3 = $wpdb->get_var($wpdb->prepare("SELECT OrderName FROM divisiontableorder WHERE SeasonId = %d AND OrderSequence = %d",$seasonid,3));
		 $taborder4 = $wpdb->get_var($wpdb->prepare("SELECT OrderName FROM divisiontableorder WHERE SeasonId = %d AND OrderSequence = %d",$seasonid,4));
		 $orderclause = '';
		 // Build the order clause for the select statement
		 if ($taborder1 != NULL) {
		 	$orderclause = $orderclause . $taborder1 . ',';
		 }
		 if ($taborder2 != NULL) {
		 	$orderclause = $orderclause . $taborder2 . ',';
		 }
		 if ($taborder3 != NULL) {
		 	$orderclause = $orderclause . $taborder3 . ',';
		 }
		 if ($taborder4 != NULL) {
		 	$orderclause = $orderclause . $taborder4 . ',';
		 }
		 if ($orderclause == '') {
		 	// If the orderclause is blank then we default to this...
		 	$orderclause = 'ORDER BY Points DESC, Diff DESC, MatchesWon DESC';
		 } else {
		 	// If orderclause set then use it...remove trailing comma and add the ORDER BY
			$orderclause = 'ORDER BY ' . rtrim($orderclause,",");
		 }
		 
		 $table_results = $wpdb->get_results($wpdb->prepare("SELECT * FROM divisiontables INNER JOIN teams on 
															 divisiontables.teamid=teams.teamid 
															 WHERE divisiontables.SeasonId = %d AND CompetitionId = %d " . $orderclause . "",$seasonid,$competitionid ));	
	      
		  $counter = 1;

	      foreach ($table_results as $table_results) {
	 			// Get team form
	 			$formtext = getTeamForm($table_results->TeamId);
			
				// Now display the table
				if ($minitab==0) {	
					// So if the snippet (i.e. just the single row) is not 0  and a teamid then show all rows- if not only show the teamid
					if ($snippet == 0) {
						$divtext = $divtext .
						'<tr>
							<td class="ftab-team"><a href="http://www.bdcsnooker.org/teams?teamid=' . $table_results->TeamId . '&seasonid=' . $table_results->SeasonId .'">' .$table_results->TeamName . '</a></td>
							<td class="ftab-form">' .$formtext . '</td>
							<td class="ftab-played">' .$table_results->MatchesPlayed . '</td>
							<td class="ftab-won">' .$table_results->MatchesWon . '</td>
							<td class="ftab-draw">' .$table_results->MatchesDrawn . '</td>
							<td class="ftab-lost">' .$table_results->MatchesLost . '</td>
							<td class="ftab-for">' .$table_results->FramesFor . '</td>
							<td class="ftab-agst">' .$table_results->FramesAgainst . '</td>
							<td class="ftab-diff">' .$table_results->Diff . '</td>
							<td class="ftab-pts">' .$table_results->Points . '</td>
						</tr>';
					} elseif ($snippet == $table_results->TeamId) {
						switch ($counter) {
							case 1:
								$ordinal = 'st';
							break;
							case 2:
								$ordinal = 'nd';
							break;
							case 3:
								$ordinal = 'rd';
							break;
							default:
								$ordinal = 'th';
						}
						$divtext = $divtext .
						'<tr>
							<td class="ftab-team"><a href="http://www.bdcsnooker.org/teams?teamid=' . $table_results->TeamId . '&seasonid=' . $table_results->SeasonId .'">' .$table_results->TeamName . '  (' .$counter . $ordinal . ')' . '</a></td>
							<td class="ftab-form">' .$formtext . '</td>
							<td class="ftab-played">' .$table_results->MatchesPlayed . '</td>
							<td class="ftab-won">' .$table_results->MatchesWon . '</td>
							<td class="ftab-draw">' .$table_results->MatchesDrawn . '</td>
							<td class="ftab-lost">' .$table_results->MatchesLost . '</td>
							<td class="ftab-for">' .$table_results->FramesFor . '</td>
							<td class="ftab-agst">' .$table_results->FramesAgainst . '</td>
							<td class="ftab-diff">' .$table_results->Diff . '</td>
							<td class="ftab-pts">' .$table_results->Points . '</td>
						</tr>';
					}
				} else {
					// So if the snippet is not 0  and a teamid then show all rows- if not only show the teamid
					if ($snippet == 0) {
						$divtext = $divtext .
						'<tr>
							<td class="ftab-team">' .$table_results->TeamName . '</td>
							<td class="ftab-played">' .$table_results->MatchesPlayed . '</td>
							<td class="ftab-pts">' .$table_results->Points . '</td>
						</tr>';
					} elseif ($snippet == $table_results->TeamId) {
						$divtext = $divtext .
						'<tr>
							<td class="ftab-team">' .$table_results->TeamName . '</td>
							<td class="ftab-played">' .$table_results->MatchesPlayed . '</td>
							<td class="ftab-pts">' .$table_results->Points . '</td>
						</tr>';
					}
				}
				
				$counter = $counter+1;
				//Only return the rows the call actually requires
				if ($counter==$returnrows) break;
			}
			
	        $divtext = $divtext . '</tbody></table>';
			
			return $divtext;
	}
	
	// Function for getting the form of a team over the last 5 games
	function getTeamForm($teamid){
		//teamid = team id for selected season
		
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		// This bit fills in the form bits for the team. Gets the fixtures and the win lose draw and then puts it into text
		$teamform = $wpdb->get_results($wpdb->prepare("SELECT f.WeekNo as Week, f.HomeTeamId, f.AwayTeamId, f.PlayDate, r.HomeScore as HomeScore, r.AwayScore as AwayScore,
												IF(f.HomeTeamId=%d,IF(r.HomeScore>r.AwayScore,'W',IF(r.AwayScore>r.HomeScore,'L','D')),IF(r.HomeScore>r.AwayScore,'L',IF(r.AwayScore>r.HomeScore,'W','D'))) as Form,
												IF(f.HomeTeamId=%d,t2.TeamName,t1.TeamName) as Opponent
												FROM fixtureresults as r
												INNER JOIN fixtures as f ON f.FixtureId=r.FixtureId
												INNER JOIN teams as t1 ON f.HomeTeamId=t1.TeamId
												INNER JOIN teams as t2 ON f.AwayTeamId=t2.TeamId
												WHERE f.HomeTeamId = %d OR f.AwayTeamId = %d
												ORDER BY f.PlayDate DESC LIMIT 5",$teamid,$teamid,$teamid,$teamid));
		$formtext = "";					
		$counter =1;	
		foreach ($teamform as $form) {
			if ($form->Form == "W") {
				$formtext = $formtext . '<img class="form-' . $counter . '" src="http://www.bdcsnooker.org/wp-content/plugins/bdscl-tools/images/form-won.jpg" 
										 class="masterTooltip" title=" Week:' . $form->Week . ' - ' . $form->Opponent .' (' . $form->HomeScore .'-' . $form->AwayScore .') "></img>';
			}
			if ($form->Form == "D") {
				$formtext = $formtext . '<img class="form-' . $counter . '" src="http://www.bdcsnooker.org/wp-content/plugins/bdscl-tools/images/form-draw.jpg" alt="' . $form->Form .'" 
										class="masterTooltip" title=" Week:' . $form->Week . ' - ' . $form->Opponent .' (' . $form->HomeScore .'-' . $form->AwayScore .') "></img>';
			}
			if ($form->Form == "L") {
				$formtext = $formtext . '<img class="form-' . $counter . '" src="http://www.bdcsnooker.org/wp-content/plugins/bdscl-tools/images/form-lost.jpg" alt="' . $form->Form .'" 
										class="masterTooltip" title=" Week:' . $form->Week . ' - ' . $form->Opponent .' (' . $form->HomeScore .'-' . $form->AwayScore .') "></img>';
			}
			$counter= $counter+1;
		}
		
		return $formtext;
	}
	
	//Function for returning a player averages table based on seasonid and competitionid
	function getPlayerAveragesTable($seasonid,$competitionid,$shipton,$returnrows,$minitab,$widtype) {
		//seasonid = relates to which season
		//competitionid = relates to the actual division no. in the db
		//shipton = relates to: 1 = only show players who've played 70% as per Ken Shipton, 0= show all
		//returnrows = enables the function just to return the top x rows
		//minitab = 1=only return Name/Played/Pts cols, 0=all cols
		//widtype = if the table's going on a widget that needs to be scrollable=1,0=not on a widget
		
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		$plavtext = '<h3><div onclick="toggle(\'shipton-hide\');">Player Averages</div></h3><div class="clickable-text" onclick="toggle(\'shipton-hide\');"> Click here to show all players</div>';
	
		if ($widtype==1){
			$tabclass = '<table class="mtab-paverages">';
		} else {
			$tabclass = '<table class="ftab-paverages">';
		}
		$plavtext = $plavtext . $tabclass;
			
		//Is this a mini table or full?
		if ($minitab==0) {
			$plavtext= $plavtext.'
								<thead><tr>
								<th class="ftab-rank">Rank</th>
								<th class="ftab-player1">Player</th>
								<th class="ftab-form">Form</th>
								<th class="ftab-played">Played</th>
								<th class="ftab-won">Won</th>
								<th class="ftab-lost">Lost</th>
								<th class="ftab-average">Average (%)</th>
								</tr></thead><tbody>';
		} else {
			$plavtext= $plavtext.'<thead><tr>
								<th class="mtab-rank">Rank</th>
								<th class="mtab-player1">Player</th>
								<th class="mtab-played">Played</th>
								<th class="mtab-average">Average (%)</th>
								</tr></thead><tbody>';
		}
		
		// Find a team in the division to use as a counter for their number of fixtures played
		$dumteamid = $wpdb->get_var($wpdb->prepare("SELECT Min(TeamId) FROM divisiontables WHERE SeasonId = %d AND CompetitionId = %d",$seasonid,$competitionid));
		// Now count the fixtures played - only dividing by 2 (when it should be 4 because 4 players a team) as each match plays 2 frames
		$fixcount = $wpdb->get_var($wpdb->prepare("SELECT Count(fd.FixtureId)/2 FROM fixtureresultdetails as fd
									INNER JOIN fixtures as f ON f.FixtureId = fd.FixtureId
									WHERE f.Hometeamid = %d OR f.Awayteamid = %d",$dumteamid,$dumteamid));
		// Now count the games that are unplayed in the season because a team may have dropped out
		$unplayedcount = $wpdb->get_var($wpdb->prepare("SELECT Count(fd.FixtureId)/4 FROM fixtureresultdetails as fd
									INNER JOIN fixtures as f ON f.FixtureId = fd.FixtureId
									WHERE (f.Hometeamid = %d OR f.Awayteamid = %d) AND f.Unplayed=1",$dumteamid,$dumteamid));
		
		// So overall fixcount to be used for Shipton is all matches less those unplayed
		$fixcount = $fixcount - $unplayedcount;
		$average_results = $wpdb->get_results($wpdb->prepare("SELECT p.UPlayerId as UId, CONCAT(u.Forename,' ' , u.Surname) as Name, p.FramesWon as FW, p.FramesLost as FL, 
														     p.Average as AV,  If((p.Frameswon+p.FramesLost)>=%d,1,0) as Shipton FROM playeraverages as p
															INNER JOIN uniqueplayers as u ON u.UPlayerId = p.UPlayerId
															WHERE SeasonId = %d AND CompetitionId = %d
															ORDER BY p.Average DESC, p.FramesWon DESC , p.UPlayerId ASC",floor(($fixcount*0.7)),$seasonid,$competitionid));

		  $counter = 1;
		  $rowhide = '';
		  $shiprank = 0;
	      foreach ($average_results as $average) {
				
				// Lets get the latest form for each player
				$formtext = getPlayerForm($average->UId, $seasonid,$competitionid);
				
				$totframes = $average->FW+$average->FL;
			  	if ($average->Shipton == 0) {
			  		$shipclass = "shipton-hide";
					
				} else {
					$shipclass = "";
					$shiprank = $shiprank+1;		
			  	}
				// Now check total frames played to make sure it's 70% of total to display
				if ($totframes > ($totcheck)){
					
					if ($minitab==0) {	
						
						$plavtext = $plavtext .
						'<tr class="'. $shipclass .'">';
						
						// Let's only rank those who qualify for the shipton because theyve played enough games
							if ($average->Shipton == 0) {
			  					$plavtext = $plavtext . '<td class="ftab-rank">-</td>';
			  				} else {
			  					$plavtext = $plavtext . '<td class="ftab-rank">' . $shiprank . '</td>';
			  				}
			  				
							$plavtext = $plavtext .
							'<td class="ftab-player1"><a href="http://www.bdcsnooker.org/players?playerid=' . $average->UId . '">' .$average->Name  . '</a></td>
							<td class="ftab-form">' .$formtext . '</td>
							<td class="ftab-played">' . $totframes . '</td>
							<td class="ftab-won">' .$average->FW . '</td>
							<td class="ftab-lost">' .$average->FL . '</td>
							<td class="ftab-average">' . round($average->AV*100,2) . '</td>
						</tr>';
					} else {
						$plavtext = $plavtext .
						'<tr class="'. $shipclass .'">';
						
						// Let's only rank those who qualify for the shipton because theyve played enough games
							if ($average->Shipton == 0) {
			  					$plavtext = $plavtext . '<td class="mtab-rank">-</td>';
			  				} else {
			  					$plavtext = $plavtext . '<td class="mtab-rank">' . $shiprank . '</td>';
			  				}
							
							$plavtext = $plavtext .
							'<td class="mtab-player1 '. $shipclass .'"><a href="http://www.bdcsnooker.org/players?playerid=' . $average->UId . '">' .$average->Name . '</a></td>
							<td class="mtab-played '. $shipclass .'">' . $average->FW  + $average->FL . '</td>
							<td class="mtab-average '. $shipclass .'">' . round($average->AV*100,2) . '</td>
						</tr>';
					} // end minitab
					
					$counter = $counter+1;
					//Only return the rows the call actually requires and hide the rest using a css class
					if ($counter===$returnrows) {
						$rowhide = 'class="hiddenrow-averages"';
					}
				} // end totframes >
			} // end loop of average
			
	        $plavtext = $plavtext . '</tbody></table>';
			
			return $plavtext;
	}
	
	//Function for returning breaks by player based on seasonid and competitionid
	function getPlayerBreaksTable($seasonid,$competitionid,$returnrows,$minitab,$widtype) {
		//seasonid = relates to which season
		//competitionid = relates to the actual division no. in the db
		//returnrows = enables the function just to return the top x rows
		//minitab = 1=only return smaller table, 0=all cols
		//widtype = if the table's going on a widget that needs to be scrollable=1,0=not on a widget
		
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		$plbrktext = '<h3>Player Breaks</h3><div class="clickable-text" onclick="toggle(\'hiddenrow-breaks\');"> Click here to show all breaks</div>';
	
		if ($widtype==1){
			$tabclass = '<table class="mtab-pbreaks">';
		} else {
			$tabclass = '<table class="ftab-pbreaks">';
		}
		$plbrktext = $plbrktext . $tabclass;
		
		//Is this a mini table or full?
		if ($minitab==0) {
			$plbrktext= $plbrktext.'
								<thead><tr>
								<th class="ftab-rank">Rank</th>
								<th class="ftab-fixhome">Player</th>
								<th class="ftab-fixfull">Fixture</th>
								<th class="ftab-date">Date</th>
								<th class="ftab-break">Break</th>
								</tr></thead><tbody>';
			
		} else {
			$plbrktext= $plbrktext.'
								<thead><tr>
								<th class="ftab-rank">Rank</th>
								<th class="ftab-fixhome">Player</th>
								<th class="ftab-break">Break</th>
								</tr></thead><tbody>';
		}
		
		// Get the breaks list - stored procedure
  		  //$break_results = $wpdb->get_results('call sp_getPlayerBreaks(' . $seasonid . ', ' . $competitionid . ')');
		  $break_results = $wpdb->get_results($wpdb->prepare("SELECT u.UPlayerId as UId, CONCAT(u.ForeName,' ', u.Surname) as pName, f.PlayDate as pDate, b.BreakScore as bScore, 
												      t1.TeamId as HId, t2.TeamId as AId, t1.TeamName as HomeTeam, t2.TeamName as AwayTeam FROM playerbreaks as b			
												INNER JOIN fixtures as f ON f.FixtureId = b.FixtureId
												INNER JOIN players as pl ON pl.PlayerId = b.PlayerId
												INNER JOIN uniqueplayers as u ON u.UPlayerId = pl.UPlayerId
												INNER JOIN teams as t1 ON t1.TeamId  = f.HomeTeamId
												INNER JOIN teams as t2 ON t2.TeamId  = f.AwayTeamId
												WHERE f.SeasonId = %d AND f.CompetitionId = %d											
												ORDER BY b.BreakScore DESC", $seasonid, $competitionid));
		  
		  
		  $counter = 1;
		  $rowhide = '';	
	      foreach ($break_results as $break) {
				if ($minitab==0) {
					$plbrktext = $plbrktext .
							'<tr '. $rowhide . '>
								<td class="ftab-rank">' . $counter . '</td>
								<td class="ftab-fixhome"><a href="http://www.bdcsnooker.org/players?playerid=' . $break->UId . '">' . $break->pName  . '</a></td>
								<td class="ftab-fixfull"><a href="http://www.bdcsnooker.org/teams?teamid=' . $break->HId . '&seasonid=' . $seasonid . '">' . $break->HomeTeam . '</a> v <a href="http://www.bdcsnooker.org/teams?teamid=' . $break->AId . '&seasonid=' . $seasonid . '">' . $break->AwayTeam . '</a></td>
								<td class="ftab-date">' . date("d-M-y",strtotime($break->pDate)) . '</td>
								<td class="ftab-break">' . $break->bScore . '</td>
							</tr>';
				} else {			
					$plbrktext = $plbrktext .
							'<tr '. $rowhide . '>
								<td class="mtab-rank">' . $counter . '</td>
								<td class="mtab-fixhome"><a href="http://www.bdcsnooker.org/players?playerid=' . $break->UId . '">' . $break->pName  . '</a></td>
								<td class="mtab-break">' . $break->bScore . '</td>
							</tr>';
				}
				$counter = $counter+1;
				//Only return the rows the call actually requires
				if ($counter===$returnrows) {
					$rowhide = 'class="hiddenrow-breaks"';
				}
					
			} // end loop of break
			
	        $plbrktext = $plbrktext . '</tbody></table>';
			
			return $plbrktext;
	}

	//Function for returning a team tables based on seasonid and teamid
	function getTeamTable($seasonid,$teamid) {
		//seasonid = relates to which season
		//teamid = relates to the actual team no. in the db
		
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		        
		$teamtext = '';
		$tabclass = 'ftab-teams';
		$teamname = $wpdb->get_var($wpdb->prepare("SELECT TeamName FROM teams WHERE TeamId = %d" , $teamid));
		
		// Let's get the division table snippet first
		// Need competition id
		$competitionId = $wpdb->get_var($wpdb->prepare("SELECT d.CompetitionId as ID FROM divisionteams as d
											INNER JOIN competitions AS c ON d.CompetitionId = c.CompetitionId WHERE d.TeamId = %d" , $teamid));
		$compName = $wpdb->get_var($wpdb->prepare("SELECT c.CompetitionName FROM divisionteams as d
											INNER JOIN competitions AS c ON d.CompetitionId = c.CompetitionId WHERE d.TeamId = %d" , $teamid));									
		$teamtext = $teamtext . '<h3>' . $teamname . ': ' . $compName .'</h3>';
		$teamtext = $teamtext . getDivTable($seasonid, $competitionId, 99, 0, 0, $teamid);
			
		// First lets get results
		
		$teamtext = $teamtext . '<h3>' . $teamname . ': Fixtures and Results</h3><div class="clickable-text"> Click the fixture row to show the players</div>';
		// Now get fixtures
		// header table first
		$teamtext = $teamtext . '<table class="' . $tabclass . '">';
		$teamtext = $teamtext . '<thead><tr>
								<th class="ftab-fixweek">Wk</th>
								<th class="ftab-fixdate">Date</th>
								<th class="ftab-fixopp">Opponent</th>
								<th class="ftab-fixloc">H/A</th>
								<th class="ftab-fixres">Result</th>
								</tr></thead>';
		// Now data table						
		$teamtext = $teamtext . '<tbody>';
		//$fixtures = $wpdb->get_results($wpdb->prepare("SELECT * FROM fixtures where HomeTeamId = %d or AwayTeamId = %d ORDER BY Weekno ASC", $teamid,$teamid));	
	  
		$fixtures = $wpdb->get_results($wpdb->prepare("SELECT * FROM (
								(SELECT FixtureId, SeasonId, Weekno, PlayDate As TheDate, HomeTeamId, AwayTeamId, CompetitionId, VenueClubId, 'League' AS Type FROM fixtures as F where F.HomeTeamId = %d or F.AwayTeamId = %d )
									UNION ALL
									    (SELECT TeamFixtureId, TournamentRoundId, ClubId, FixtureDate as TheDate, HomeEntryId,  AwayEntryId,  HomeScore, AwayScore, 'Cup' AS Type FROM teamtournofixture as T where T.HomeEntryId = %d OR T.AwayEntryId = %d)
									   ) results
									ORDER BY TheDate ASC", $teamid,$teamid, $teamid,$teamid));
	  
	     foreach ($fixtures as $fixture) {
	     		
				// Lets get the result if there is one and the details
				// Plus lets get the weeknumber if League and just put Cup if not a league game
				if ($fixture->Type == 'League'){
					$homescore = $wpdb->get_var($wpdb->prepare("SELECT HomeScore FROM fixtureresults WHERE FixtureId = %d" ,  $fixture->FixtureId ));
					$awayscore = $wpdb->get_var($wpdb->prepare("SELECT AwayScore FROM fixtureresults WHERE FixtureId = %d" ,  $fixture->FixtureId ));
					$wknumber = $fixture->Weekno;
				}
			
				if ($fixture->Type == 'Cup'){
					$homescore = $fixture->CompetitionId;
					$awayscore = $fixture->VenueClubId;
					
					$whichcup = $wpdb->get_var($wpdb->prepare("SELECT Tournament FROM teamtournoround WHERE TeamRoundId = %d",$fixture->SeasonId));
					$whichrnd = $wpdb->get_var($wpdb->prepare("SELECT Name FROM teamtournoround WHERE TeamRoundId = %d",$fixture->SeasonId));
					$whichseq = $wpdb->get_var($wpdb->prepare("SELECT Sequence FROM teamtournoround WHERE TeamRoundId = %d",$fixture->SeasonId));
					
					if (strpos($whichrnd, 'Final') !== false) {
						if (strpos($whichrnd, 'Semi') !== false) {
						    $rnd = "SF";
						} else {
							$rnd = "Final";
						}
					}
					if (strpos($whichrnd, 'Quarter') !== false) {
					    $rnd = "QF";
					}
					if (strpos($whichrnd, 'Round') !== false) {
					    $rnd = "R" . $whichseq;
					}
					
					
					if (strpos($whichcup, 'Presidents') !== false) {
					    $wknumber = "PH Cup " . $rnd;
					}
					if (strpos($whichcup, 'Orme Plate') !== false) {
					    $wknumber = "Orme Plate " . $rnd;
					}
					if (strpos($whichcup, 'Orme Shield') !== false) {
					    $wknumber = "Orme Shield " . $rnd;
					}
					
				}
				
				// Get the opponent name
				$hometeam = $wpdb->get_var($wpdb->prepare("SELECT TeamName FROM teams where TeamId = %d" , $fixture->HomeTeamId ));
				$hometeamid = $wpdb->get_var($wpdb->prepare("SELECT TeamId FROM teams where TeamId = %d" , $fixture->HomeTeamId ));
				$awayteam = $wpdb->get_var($wpdb->prepare("SELECT TeamName FROM teams where TeamId = %d" , $fixture->AwayTeamId ));
				$awayteamid = $wpdb->get_var($wpdb->prepare("SELECT TeamId FROM teams where TeamId = %d" , $fixture->AwayTeamId ));
				
				
				// If there's no team then it must be a rest week
				if ($awayteam == ''){
					$awayteam = 'Rest';
				}
				if ($hometeam == ''){
					$hometeam = 'Rest';
				}
				// Now find the chosen team by finding if home or away
				if ($fixture->HomeTeamId === $teamid){
					$homeoraway = "Home";
					$opponent = $awayteam;
					$opponentid = $awayteamid;
				} else {
					$homeoraway = "Away";
					$opponent = $hometeam;
					$opponentid = $hometeamid;
				}
				
				// Format the date
				//$fixdate = date('d M y',strtotime($fixture->PlayDate));
				$fixdate = date('d M y',strtotime($fixture->TheDate));
				// Find out if team won lost or drew so we can format score cell
				
				if ($homeoraway == "Home") {
					if ($homescore > $awayscore){
						// Won
						$resultclass = "game-won";
					}
					if ($homescore == $awayscore){
						// Won
						$resultclass = "game-drew";
					}
					if ($homescore < $awayscore){
						// Won
						$resultclass = "game-lost";
					}
				}
				if ($homeoraway == "Away") {
					if ($awayscore > $homescore){
						// Won
						$resultclass = "game-won";
					}
					if ($awayscore == $homescore){
						// Won
						$resultclass = "game-drew";
					}
					if ($awayscore < $homescore){
						// Won
						$resultclass = "game-lost";
					}
				}
				
				if (is_null($homescore)){
					$resultclass = "game-unplayed";
				}
				$teamtext = $teamtext .
					'<tr onclick="toggle(\'hiddenrow-details-'.  $fixture->FixtureId . '\');">
						<td class="ftab-fixweek" >' . $wknumber . '</td>
						<td class="ftab-fixdate">' . $fixdate . '</td>
						<td class="ftab-fixopp"><a href="http://www.bdcsnooker.org/teams?teamid=' . $opponentid . '&seasonid=' . $seasonid . '">' . $opponent . '</td>
						<td class="ftab-fixloc">' . $homeoraway . '</td>';
				if ($homeoraway == "Home") {
					$teamtext = $teamtext . '<td class="ftab-fixres ' . $resultclass .'">' . $homescore . ' - ' . $awayscore . '</td></tr>';
				} else {
					$teamtext = $teamtext . '<td class="ftab-fixres ' . $resultclass .'">' . $awayscore . ' - ' . $homescore . '</td></tr>';
				}
						
					
				// Now lets add the fixture result details
				$fixdetails = $wpdb->get_results($wpdb->prepare("SELECT * FROM fixtureresultdetails WHERE FixtureId = %d" , $fixture->FixtureId));
				foreach ($fixdetails as $fixdetail){
					// Get names
					$hplayerid = $wpdb->get_var($wpdb->prepare("SELECT UPlayerId FROM players WHERE PlayerId = %d" , $fixdetail->HomePlayerId));
					$hplayername = $wpdb->get_var($wpdb->prepare("SELECT CONCAT(Forename, ' ', Surname) FROM uniqueplayers WHERE UPlayerId = %d" , $hplayerid ));
					$aplayerid = $wpdb->get_var($wpdb->prepare("SELECT UPlayerId FROM players WHERE PlayerId = %d" , $fixdetail->AwayPlayerId ));
					$aplayername = $wpdb->get_var($wpdb->prepare("SELECT CONCAT(Forename, ' ', Surname) FROM uniqueplayers WHERE UPlayerId = %d" ,$aplayerid ));
					// Get any breaks
					$hbreaks = $wpdb->get_results($wpdb->prepare("SELECT * FROM playerbreaks WHERE FixtureId = %d AND PlayerId = %d" , $fixture->FixtureId, $fixdetail->HomePlayerId ));
					$abreaks = $wpdb->get_results($wpdb->prepare("SELECT * FROM playerbreaks WHERE FixtureId = %d AND PlayerId = %d" , $fixture->FixtureId, $fixdetail->AwayPlayerId ));
					$hbreaklist='';
					$abreaklist='';
					// Loop around them for home and away players
					foreach ($hbreaks as $break){
						$hbreaklist = $hbreaklist . $break->BreakScore . ', ';
					}
					foreach ($abreaks as $break){
						$abreaklist = $abreaklist . $break->BreakScore . ', ';
					}
					// Top and tail with brackets if there are any breaks
					if ($hbreaklist <> '') {
						$hbreaklist = '(' . rtrim($hbreaklist,', ') . ')';
					}
					if ($abreaklist <> '') {
						$abreaklist = '(' . rtrim($abreaklist,', ') . ')';
					}
					
					$teamtext = $teamtext .
					'<tr class="hiddenrow-details-' .  $fixture->FixtureId . '">
						<td class="ftab-fixweek"></td>
						<td class="ftab-fixhome"><a href="http://www.bdcsnooker.org/players?playerid=' . $hplayerid . '">' . $hplayername . '</a></td>
						<td class="ftab-fixres">' . $fixdetail->HomeScore . ' ' . $hbreaklist . '</td>
						<td class="ftab-fixaway"><a href="http://www.bdcsnooker.org/players?playerid=' . $aplayerid . '">' . $aplayername . '</a></td>
						<td class="ftab-fixres">' . $fixdetail->AwayScore . ' ' . $abreaklist . '</td>
					</tr>';
					
				}
				
				$teamtext = $teamtext . '<style type="text/css">.hiddenrow-details-'.  $fixture->FixtureId .' {display: none;}</style>';
		 }	
	     $teamtext = $teamtext . '</tbody></table><br>';
	   	
		// Now get Player data
		$tabclass = 'ftab-players';
		$teamtext = $teamtext . '<h3>' . $teamname . ': Players</h3>';
		// header table first
		$teamtext = $teamtext . '<table class="' . $tabclass . '">';
		$teamtext = $teamtext . '<thead>
								<tr>
								<th class="ftab-player" rowspan="2">Player</th>
								<th class="ftab-form" rowspan="2">Form</th>
								<th class="ftab-home" colspan="4">Home</th>
								<th class="ftab-away" colspan="4">Away</th>
								<th class="ftab-combined" colspan="4">Combined</th>
								</tr>
								<tr>
								<th class="ftab-hplayed">P</th>
								<th class="ftab-hwon">Won</th>
								<th class="ftab-hlost">Lost</th>
								<th class="ftab-hperc">%</th>
								<th class="ftab-aplayed">P</th>
								<th class="ftab-awon">Won</th>
								<th class="ftab-alost">Lost</th>
								<th class="ftab-aperc">%</th>
								<th class="ftab-tplayed">P</th>
								<th class="ftab-twon">Won</th>
								<th class="ftab-tlost">Lost</th>
								<th class="ftab-tperc">%</th>
								</tr>
								</thead>';
								
		// Find all the players that belong in the team
		 $qryplayers = $wpdb->get_results($wpdb->prepare("SELECT PlayerId, UPlayerId FROM players WHERE TeamId = %d" , $teamid));
		 foreach ($qryplayers as $player){
		 	// Find player name
		 	$playername = $wpdb->get_var($wpdb->prepare("SELECT CONCAT(Forename,' ', Surname) FROM uniqueplayers WHERE UPlayerId = %d" , $player->UPlayerId ));
		 	
		 	// Get frames played -* 2 as we are just counting fixtures yet each fixture contains 2 frames
		 	$hplayed = $wpdb->get_var($wpdb->prepare("SELECT Count(FixtureId) FROM fixtureresultdetails where HomePlayerId = %d" , $player->PlayerId)) * 2;
		 	$aplayed = $wpdb->get_var($wpdb->prepare("SELECT Count(FixtureId) FROM fixtureresultdetails where AwayPlayerId = %d" , $player->PlayerId)) * 2;
		 	
		 	// When player at home their frames WON
		 	$hhscore = $wpdb->get_var($wpdb->prepare("SELECT Sum(HomeScore) FROM fixtureresultdetails where HomePlayerId = %d" , $player->PlayerId));
		 	// When player at home their frames LOST
		 	$ahscore = $wpdb->get_var($wpdb->prepare("SELECT Sum(AwayScore) FROM fixtureresultdetails where HomePlayerId = %d" , $player->PlayerId));
		 	// When player at away their frames WON		 	
		 	$hascore = $wpdb->get_var($wpdb->prepare("SELECT Sum(AwayScore) FROM fixtureresultdetails where AwayPlayerId = %d" , $player->PlayerId));
		 	// When player at away their frames LOST	 	
		 	$aascore = $wpdb->get_var($wpdb->prepare("SELECT Sum(HomeScore) FROM fixtureresultdetails where AwayPlayerId = %d" , $player->PlayerId));
			
			$tplayed = $hplayed + $aplayed;
			// When player at home/away their total frames WON		 
			$thscore = $hhscore + $hascore;
			// When player at home/away their total frames LOST
			$tascore = $ahscore + $aascore;
			
			// Now work out averages
			if ($hplayed === 0) {
				$hperc = 0;
			} else {
				// Home average
				$hperc = round((float)($hhscore / $hplayed) * 100 );
			}
			if ($aplayed === 0) {
				$aperc = 0;
			} else {
				// Away average
				$aperc = round((float)($hascore / $aplayed) * 100 );
			}
			if (($hplayed + $aplayed) === 0) {
				$tperc = 0;
			} else {
				// Total average
				$tperc = round((float)(($hhscore + $hascore) / ($hplayed + $aplayed)) * 100 );
			}
			
			// Now lets get the form for each player...
			$formtext = getPlayerForm($player->UPlayerId, $seasonid, $competitionId);
			
		 	$teamtext = $teamtext . '<tr>
		 						<td id="playerid-' . $player->PlayerId . ' " class="ftab-player"><a href="http://www.bdcsnooker.org/players?playerid=' . $player->UPlayerId . '">' . $playername . '</a></td>
		 						<td class="ftab-form">' .$formtext . '</td>
		 						<td class="ftab-hplayed">' . $hplayed . '</td>
								<td class="ftab-hwon">' . $hhscore . '</td>
								<td class="ftab-hlost">' . $ahscore . '</td>
								<td class="ftab-hperc">' . $hperc . '</td>
								<td class="ftab-aplayed">' . $aplayed . '</td>
								<td class="ftab-awon">' . $hascore . '</td>
								<td class="ftab-alost">' . $aascore . '</td>
								<td class="ftab-aperc">' . $aperc . '</td>
								<td class="ftab-tplayed">' . ($hplayed + $aplayed) . '</td>
								<td class="ftab-twon">' . $thscore . '</td>
								<td class="ftab-tlost">' . $tascore . '</td>
								<td class="ftab-tperc">' . $tperc . '</td>
								</tr>';		 	
		 }
		
		 $teamtext = $teamtext . '</table>';	
		 return $teamtext;
	}
	
	// Function to get the form of a player over the last 5 games
	function getPlayerForm($playerid, $seasonid, $competitionid){
		//playerid = unique player id
		//seasonid = the season id
		//competitionid = division id of the player
		
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		// This bit fills in the form bits for the player. Gets the fixtures and the win lose draw and then puts it into text
		$playerform = $wpdb->get_results($wpdb->prepare("SELECT f.SeasonId, f.WeekNo as Week, f.PlayDate, t.TeamName, s.TeamName, CONCAT(u1.Forename,' ', u1.Surname) as HomePlayer, d.HomeScore, CONCAT(u2.Forename,' ', u2.Surname) as AwayPlayer, d.AwayScore,
													IF(u1.UPlayerId=%d,IF(d.HomeScore>d.AwayScore,'W',IF(d.AwayScore>d.HomeScore,'L','D')),IF(d.HomeScore>d.AwayScore,'L',IF(d.AwayScore>d.HomeScore,'W','D'))) as Form,
													IF(u1.UPlayerId=%d,CONCAT(u2.Forename,' ', u2.Surname),CONCAT(u1.Forename,' ', u1.Surname)) as Opponent
												FROM fixtureresultdetails as d 
												INNER JOIN fixtures as f on f.FixtureId = d.FixtureId
												INNER JOIN teams as t ON f.HomeTeamId = t.TeamId
												INNER JOIN teams as s ON f.AwayTeamId = s.TeamId
												INNER JOIN players as p1 ON p1.PlayerId = d.HomePlayerId
												INNER JOIN players as p2 ON p2.PlayerId = d.AwayPlayerId
												INNER JOIN uniqueplayers as u1 ON p1.UPlayerId = u1.UPlayerId
												INNER JOIN uniqueplayers as u2 ON p2.UPlayerId = u2.UPlayerId
												WHERE (u1.UPlayerId = %d OR u2.UPlayerId = %d) AND f.SeasonId = %d AND f.CompetitionId = %d
												ORDER BY f.PlayDate DESC LIMIT 5",$playerid,$playerid,$playerid,$playerid,$seasonid,$competitionid ));
		$formtext = "";
		$counter =1;				
		foreach ($playerform as $form) {
			if ($form->Form == "W") {
				$formtext = $formtext . '<img class="form-' . $counter . '" src="http://www.bdcsnooker.org/wp-content/plugins/bdscl-tools/images/form-won.jpg" 
										 class="masterTooltip" title=" Week:' . $form->Week . ' - ' . $form->Opponent .' (' . $form->HomeScore .'-' . $form->AwayScore .') "></img>';
			}
			if ($form->Form == "D") {
				$formtext = $formtext . '<img class="form-' . $counter . '" src="http://www.bdcsnooker.org/wp-content/plugins/bdscl-tools/images/form-draw.jpg" alt="' . $form->Form .'" 
										class="masterTooltip" title=" Week:' . $form->Week . ' - ' . $form->Opponent .' (' . $form->HomeScore .'-' . $form->AwayScore .') "></img>';
			}
			if ($form->Form == "L") {
				$formtext = $formtext . '<img class="form-' . $counter . '" src="http://www.bdcsnooker.org/wp-content/plugins/bdscl-tools/images/form-lost.jpg" alt="' . $form->Form .'" 
										class="masterTooltip" title=" Week:' . $form->Week . ' - ' . $form->Opponent .' (' . $form->HomeScore .'-' . $form->AwayScore .') "></img>';
			}
			$counter=$counter+1;
		}
		
		return $formtext;
	}
	
	//Function get all the breaks ordered from the highest since records began
	function getAllBreaksTable($returnrows,$minitab,$widtype,$allshow,$competitionid) {
		//returnrows = enables the function just to return the top x rows
		//minitab = 1=only return Rank/Name/Pts cols, 0=all cols
		//widtype = if the table's going on a widget that needs to be scrollable=1,0=not on a widget
		//allshow = 3: Best breaks in a week by a division; 2: for every break from league and competition, 1:Just Cups, 0: Just league
		//competitionid = ID of comp in table used only for allshow=3
		
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		 
		$highbreakstext = '';
		if ($widtype==1){
			$tabclass = '<table class="mtab-pbreaks">';
		} else {
			$tabclass = '<table class="ftab-pbreaks">';
		}
		$highbreakstext = $highbreakstext . $tabclass;
			
		// Now the query to get the results - stored proc
		//$qrybreaks = $wpdb->get_results('call getAllBreaks()');
		$range = -7;
		if ($allshow == 3) {
			$qrybreaks = $wpdb->get_results($wpdb->prepare("SELECT CONCAT(u.Forename,' ' , u.Surname) AS Name, s.SeasonDesc, f.PlayDate as PlayDate, th.TeamName as HomeTeam, ta.TeamName as AwayTeam,
									   b.Breakscore, u.UPlayerId as UId, th.TeamId as HId, ta.TeamId as AId, s.SeasonId as SId, c.CompetitionId, 'League' FROM playerbreaks as b 
								        INNER JOIN fixtures as f ON f.FixtureId = b.FixtureId 
										INNER JOIN players as p ON p.PlayerId = b.PlayerId 
										INNER JOIN uniqueplayers as u ON u.UPlayerId = p.UPlayerId 
										INNER JOIN seasons as s ON s.SeasonId = f.SeasonId
										INNER JOIN teams as th ON th.TeamId = f.HomeTeamId
										INNER JOIN teams as ta ON ta.TeamId = f.AwayTeamId
                                        INNER JOIN competitions as c ON f.CompetitionId = c.CompetitionId
                                        WHERE PlayDate BETWEEN ADDDATE(NOW(),%d) AND ADDDATE(NOW(),1) AND c.CompetitionId = %d
										ORDER BY Breakscore DESC
										LIMIT 1000;",$range,$competitionid));
			
		}
		if ($allshow == 2) {
			$qrybreaks = $wpdb->get_results("SELECT CONCAT(u.Forename,' ' , u.Surname) AS Name, s.SeasonDesc, f.PlayDate, th.TeamName as HomeTeam, ta.TeamName as AwayTeam,
									   b.Breakscore, u.UPlayerId as UId, th.TeamId as HId, ta.TeamId as AId, s.SeasonId as SId, 'League' FROM playerbreaks as b 
								        INNER JOIN fixtures as f ON f.FixtureId = b.FixtureId 
										INNER JOIN players as p ON p.PlayerId = b.PlayerId 
										INNER JOIN uniqueplayers as u ON u.UPlayerId = p.UPlayerId 
										INNER JOIN seasons as s ON s.SeasonId = f.SeasonId
										INNER JOIN teams as th ON th.TeamId = f.HomeTeamId
										INNER JOIN teams as ta ON ta.TeamId = f.AwayTeamId
										UNION
										SELECT CONCAT(u.Forename,' ' , u.Surname) as Name, s.SeasonDesc, b.Date, th.TeamName as HomeTeam, ta.TeamName as AwayTeam,
										b.BreakScore, u.UPlayerId as UId, th.TeamId as HId, ta.TeamId as AId, b.SeasonId as SId, b.Competition  FROM playercompbreaks as b 
										INNER JOIN uniqueplayers as u ON u.UPlayerId = b.PlayerId  
										INNER JOIN seasons as s ON s.SeasonId = b.SeasonId
										INNER JOIN teams as th ON th.TeamId = b.HomeTeamId
										INNER JOIN teams as ta ON ta.TeamId = b.AwayTeamId
										ORDER BY BreakScore DESC
										LIMIT 1000;");
		}
		if ($allshow == 1) {
			$qrybreaks = $wpdb->get_results("SELECT CONCAT(u.Forename,' ' , u.Surname) as Name, s.SeasonDesc, b.Date as PlayDate, th.TeamName as HomeTeam, ta.TeamName as AwayTeam,
										b.BreakScore as Breakscore, u.UPlayerId as UId, th.TeamId as HId, ta.TeamId as AId, b.SeasonId as SId, b.Competition as League FROM playercompbreaks as b 
										INNER JOIN uniqueplayers as u ON u.UPlayerId = b.PlayerId  
										INNER JOIN seasons as s ON s.SeasonId = b.SeasonId
										INNER JOIN teams as th ON th.TeamId = b.HomeTeamId
										INNER JOIN teams as ta ON ta.TeamId = b.AwayTeamId
										ORDER BY PlayDate DESC
										LIMIT 1000;");
		}
		if ($allshow == 0) {
			$qrybreaks = $wpdb->get_results("SELECT CONCAT(u.Forename,' ' , u.Surname) AS Name, s.SeasonDesc, f.PlayDate as PlayDate, th.TeamName as HomeTeam, ta.TeamName as AwayTeam,
									   b.Breakscore, u.UPlayerId as UId, th.TeamId as HId, ta.TeamId as AId, s.SeasonId as SId, 'League' FROM playerbreaks as b 
										
								        INNER JOIN fixtures as f ON f.FixtureId = b.FixtureId 
										INNER JOIN players as p ON p.PlayerId = b.PlayerId 
										INNER JOIN uniqueplayers as u ON u.UPlayerId = p.UPlayerId 
										INNER JOIN seasons as s ON s.SeasonId = f.SeasonId
										INNER JOIN teams as th ON th.TeamId = f.HomeTeamId
										INNER JOIN teams as ta ON ta.TeamId = f.AwayTeamId
										ORDER BY PlayDate DESC
										LIMIT 1000;");
		}
		
		
		//Is this a mini table or full?
		if ($minitab==0) {
			if ($allshow == 2) {
				$highbreakstext= $highbreakstext.'
									<thead><tr>
									<th class="ftab-rank">Rank</th>
									<th class="ftab-player1">Player</th>
									<th class="ftab-season">Season</th>
									<th class="ftab-date">Date</th>
									<th class="ftab-fixfull">Fixture</th>
									<th class="ftab-break">Break</th>
									<th class="ftab-fixfull">Competition</th>
									</tr></thead><tbody>';
			} else {
				$highbreakstext= $highbreakstext.'
									<thead><tr>
									<th class="ftab-player1">Player</th>
									<th class="ftab-season">Season</th>
									<th class="ftab-date">Date</th>
									<th class="ftab-fixfull">Fixture</th>
									<th class="ftab-break">Break</th>
									<th class="ftab-fixfull">Competition</th>
									</tr></thead><tbody>';
			}
		} else {
			if ($allshow == 3) {
						$highbreakstext= $highbreakstext.'<thead>
									<th class="mtab-player1">Player</th>
									<th class="mtab-date">Date</th>
									<th class="mtab-fixfull">Fix</th>
									<th class="mtab-break">Brk</th>
									</thead><tbody>';
			}
			if ($allshow == 2) {
				$highbreakstext= $highbreakstext.'<thead><tr>
									<th class="mtab-rank">Rank</th>
									<th class="mtab-player1">Player</th>
									<th class="mtab-date">Date</th>
									<th class="mtab-break">Break</th>
									</tr></thead><tbody>';
			} 
			if ($allshow < 2) {
				
				$highbreakstext= $highbreakstext.'<thead><tr>
									<th class="mtab-player1">Player</th>
									<th class="mtab-date">Date</th>
									<th class="mtab-break">Break</th>
									</tr></thead><tbody>';
			}
		}
		 
		$counter = 1;
		
		if (count($qrybreaks) > 0 ) {
		
			foreach ($qrybreaks as $break) {
					
		 	if ($minitab==0) {
		 		if ($allshow == 2) {
					$highbreakstext = $highbreakstext .
					'<tr '. $rowhide . '>
						<td class="ftab-weekno">' . $counter . '</td>
						<td class="ftab-player1"><a href="http://www.bdcsnooker.org/players?playerid=' . $break->UId . '">' .$break->Name  . '</a></td>
						<td class="ftab-season">' . $break->SeasonDesc . '</td>
						<td class="ftab-date">' . date("d-M-y",strtotime($break->PlayDate)) . '</td>
						<td class="ftab-fixfull"><a href="http://www.bdcsnooker.org/teams?teamid=' . $break->HId . '&seasonid=' . $break->SId . '">' . $break->HomeTeam . '</a> v <a href="http://www.bdcsnooker.org/teams?teamid=' . $break->AId . '&seasonid=' . $break->SId . '">' . $break->AwayTeam . '</a></td>
						<td class="ftab-bpts">' . $break->Breakscore . '</td>
						<td class="ftab-fixfull">' . $break->League . '</td>		
					</tr>';
				} else {
					$highbreakstext = $highbreakstext .
					'<tr '. $rowhide . '>
						<td class="ftab-player1"><a href="http://www.bdcsnooker.org/players?playerid=' . $break->UId . '">' .$break->Name  . '</a></td>
						<td class="ftab-season">' . $break->SeasonDesc . '</td>
						<td class="ftab-date">' . date("d-M-y",strtotime($break->PlayDate)) . '</td>
						<td class="ftab-fixfull"><a href="http://www.bdcsnooker.org/teams?teamid=' . $break->HId . '&seasonid=' . $break->SId . '">' . $break->HomeTeam . '</a> v <a href="http://www.bdcsnooker.org/teams?teamid=' . $break->AId . '&seasonid=' . $break->SId . '">' . $break->AwayTeam . '</a></td>
						<td class="ftab-bpts">' . $break->Breakscore . '</td>
						<td class="ftab-fixfull">' . $break->League . '</td>		
					</tr>';
				}
			} else {
				if ($allshow == 3) {
					$highbreakstext = $highbreakstext .
					'<tr '. $rowhide . '>
						<td class="mtab-player1"><a href="http://www.bdcsnooker.org/players?playerid=' . $break->UId . '">' .$break->Name  . '</a></td>
						<td class="mtab-date">' . date("d-M",strtotime($break->PlayDate)) . '</td>
						<td class="mtab-fixfull"><a href="http://www.bdcsnooker.org/teams?teamid=' . $break->HId . '&seasonid=' . $break->SId . '">' . $break->HomeTeam . '</a> v <a href="http://www.bdcsnooker.org/teams?teamid=' . $break->AId . '&seasonid=' . $break->SId . '">' . $break->AwayTeam . '</a></td>
						<td class="mtab-bpts">' . $break->Breakscore . '</td>	
					</tr>';
				}
				if ($allshow == 2) {
					$highbreakstext = $highbreakstext .
					'<tr '. $rowhide . '>
						<td class="mtab-weekno">' . $counter . '</td>
						<td class="mtab-player1"><a href="http://www.bdcsnooker.org/players?playerid=' . $break->UId . '">' .$break->Name  . '</a></td>
						<td class="mtab-date">' . date("d-M-y",strtotime($break->PlayDate)) . '</td>
						<td class="mtab-bpts">' . $break->Breakscore . '</td>	
					</tr>';
				} 
				if ($allshow < 2) {
					$highbreakstext = $highbreakstext .
					'<tr '. $rowhide . '>
						<td class="mtab-player1"><a href="http://www.bdcsnooker.org/players?playerid=' . $break->UId . '">' .$break->Name  . '</a></td>
						<td class="mtab-date">' . date("d-M-y",strtotime($break->PlayDate)) . '</td>
						<td class="mtab-bpts">' . $break->Breakscore . '</td>	
					</tr>';
				}
			} // end minitab
			
			$counter = $counter+1;
			//Only return the rows the call actually requires and hide the rest using a css class
			if ($counter===$returnrows) {
				$rowhide = 'class="hiddenrow-averages"';
			}
			
		} // end break loop
		
		} else {
			$highbreakstext = "No breaks to display";
		}

		$highbreakstext = $highbreakstext . '</tbody></table>';
		return $highbreakstext;
	}
	
	//Function for returning rankings table based on seasonid
	function getRankingsTable($seasonid,$returnrows,$minitab,$widtype) {
		//seasonid = relates to which season
		//returnrows = enables the function just to return the top x rows
		//minitab = 1=only return Rank/Name/Pts cols, 0=all cols
		//widtype = if the table's going on a widget that needs to be scrollable=1,0=not on a widget
		
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		        
		$rankingtext = '';
		if ($widtype==1){
			$tabclass = '<table class="mtab-pranking">';
		} else {
			$tabclass = '<table class="ftab-pranking">';
		}
		$rankingtext = $rankingtext . $tabclass;
			
		// Get the season name for the ranking sql command
		$season1 = $wpdb->get_var($wpdb->prepare("SELECT SeasonDesc FROM seasons WHERE SeasonId = %d", $seasonid));
		// Take the first second part of the year and adds the 20 for century and set vars for this season and the one before
		$season1 = '20' . substr($season1,5,2); //
		$season0 = $season1-1;
		$season1desc = $season0 .  '-' . substr($season1,2,2) ;
		$season0desc = $season0-1 . '-' . substr($season0,2,2) ;
		$seasonid0 = $seasonid-1;
		// Now the query to get the results
		$qryrankings = $wpdb->get_results($wpdb->prepare("SELECT UId,Name,LP1, CP1, BP1, If(Isnull(Yr1),0,Yr1) AS Yr1, If(Isnull(Yr0),0,Yr0) AS Yr0, If(Isnull(Yr1),0,Yr1)+If(Isnull(Yr0),0,Yr0) AS RTot
						FROM
						(
						SELECT u.UPlayerId as UId, CONCAT(u.Forename,' ' , u.Surname) AS Name, Sum(Case when SeasonId = %d then LeaguePts END) AS LP1, Sum(Case when SeasonId = %d then CompetitionPts END) AS CP1, Sum(Case when SeasonId = %d then BreakPts END) AS BP1,
						 Sum(Case when SeasonId = %d then LeaguePts+CompetitionPts+BreakPts END) AS Yr1,
						 Sum(Case when SeasonId = %d then LeaguePts+CompetitionPts+BreakPts END) AS Yr0
						FROM playerrankings as p
						INNER JOIN uniqueplayers as u ON u.UPlayerId = p.UPlayerId
						WHERE SeasonId = %d  or SeasonId = %d
						GROUP BY u.UPlayerId
						ORDER BY Yr1 DESC
						 ) AS d
						 GROUP BY UId
						 ORDER BY RTot DESC",$seasonid, $seasonid, $seasonid, $seasonid, $seasonid0, $seasonid, $seasonid0));
		//$qryrankings = $wpdb->get_results($rankquery);
		
		//Is this a mini table or full?
		if ($minitab==0) {
			$rankingtext= $rankingtext.'
								<thead><tr>
								<th class="ftab-weekno">Rank</th>
								<th class="ftab-player1">Player</th>
								<th class="ftab-lpts">League Pts</th>
								<th class="ftab-cpts">Comp Pts</th>
								<th class="ftab-bpts">Break Pts</th>
								<th class="ftab-bpts">' . $season1desc . '</th>
								<th class="ftab-bpts">' . $season0desc . '</th>
								<th class="ftab-tpts">Total</th>
								<th class="ftab-hcap">Handicap</th>
								</tr></thead><tbody>';
		} else {
			$rankingtext= $rankingtext.'<thead><tr>
								<th class="mtab-player1">Player</th>
								<th class="mtab-pts">Total</th>
								<th class="mtab-hcap">Handicap</th>
								</tr></thead><tbody>';
		}
		 
		$counter = 1;

		foreach ($qryrankings as $rank) {
			
			// Now get the handicap from the Handicap table
			$handicap = $wpdb->get_var($wpdb->prepare("SELECT Handicap FROM playerhandicaps WHERE SeasonId = %d AND UPlayerId = %d", $seasonid,$rank->UId));
		
		 	if ($minitab==0) {			
				$rankingtext = $rankingtext .
				'<tr '. $rowhide . '>
					<td class="ftab-weekno">' . $counter . '</td>
					<td class="ftab-player1"><a href="http://www.bdcsnooker.org/players?playerid=' . $rank->UId . '">' .$rank->Name  . '</a></td>
					<td class="ftab-lpts">' . $rank->LP1 . '</td>
					<td class="ftab-cpts">' .$rank->CP1 . '</td>
					<td class="ftab-bpts">' .$rank->BP1 . '</td>
					<td class="ftab-bpts">' . $rank->Yr1 . '</td>
					<td class="ftab-bpts">' . $rank->Yr0 . '</td>
					<td class="ftab-tpts">' . $rank->RTot . '</td>
					<td class="ftab-hcap">' . $handicap . '</td>
					
				</tr>';
			} else {
				$rankingtext = $rankingtext .
				'<tr '. $rowhide . '>
					<td class="mtab-weekno">' . $counter . '</td>
					<td class="mtab-player1"><a href="http://www.bdcsnooker.org/players?playerid=' . $rank->UId . '">' .$rank->Name . '</a></td>
					<td class="mtab-pts">' . $rank->RTot . '</td>
					<td class="mtab-hcap">' . $handicap . '</td>
				</tr>';
			} // end minitab
			
			$counter = $counter+1;
			//Only return the rows the call actually requires and hide the rest using a css class
			if ($counter===$returnrows) {
				$rowhide = 'class="hiddenrow-averages"';
			}
			
		} // end rank loop
		
		$rankingtext = $rankingtext . '</tbody></table>';
		//$rankingtext ="xxxx " . $sqlquery;	
		return $rankingtext;
	}
	
	//Function for returning player tables based on playerid
	function getPlayerTable($playerid) {
		//playerid = relates to which unique player
		
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		$playertext = '';
		$breakstext = '';
		$breakdown =  '';
		$career = '';
		$cbreaks30 = 0;
		$cbreaks40 = 0;
		$cbreaks50 = 0;
		$cbreaks60 = 0;
		$cbreaks70 = 0;
		$cbreaks80 = 0;
		$cbreaks90 = 0;
		$cbreaks100 = 0;
		$cbreaks147 = 0;
		$cthehighbreak = 0;
		
		// This is for the div class seas-wrapper that we set to 0 or 1 so we can alternate background colors via css for each season
		$wrapperno = 0;
		
		// First lets get the unique player
		$playername = $wpdb->get_var($wpdb->prepare("SELECT CONCAT(Forename, ' ' , Surname) FROM uniqueplayers WHERE UPlayerId = %d", $playerid));
		
		// Set player name
		$playertext = $playertext . '<h3>' . $playername . '</h3>';
		
		// Get the seasons played by that player
		$qryseasplayed = $wpdb->get_results($wpdb->prepare("SELECT p.PlayerId as ThePlayerId, p.SeasonId as TheSeasonId, SeasonDesc, p.TeamId as TheTeamId, t.TeamName as Team FROM players as p
												INNER JOIN seasons as s on p.Seasonid = s.SeasonId
												INNER JOIN teams as t on p.TeamId = t.TeamId
												WHERE UPlayerId = %d ORDER BY p.SeasonId DESC",$playerid));
		
		// Loop around the seasons and work out the totals for matches played won lost etc
		$lastseason = 0; //for checking with $thisseason to display player cup data
		foreach ($qryseasplayed as $season){
			// Get competition name
			$comp = $wpdb->get_var($wpdb->prepare("SELECT Max(CompetitionName) FROM fixtures as f 
									INNER JOIN competitions as c ON f.CompetitionId = c.CompetitionId
									WHERE f.SeasonId = %d and (HomeTeamId = %d OR AwayTeamId = %d)", $season->TheSeasonId, $season->TheTeamId, $season->TheTeamId));	
			//Catch the seasonid as if it's repeated , i.e play for more than 1 team in a season, we only need to show the players cup comps data once.
			$thisseason = $season->TheSeasonId;
			// Set the header
			// set the wrapper code so we can alternate colours
			if ($wrapperno==0) {
				$wrapperno = 1;
			} else {
				$wrapperno = 0;
			}
			$breakdown = $breakdown . '<div class="seas-wrapper' . $wrapperno . '"><h4>' . $season->SeasonDesc . ': ' . $season->Team . ': ' . $comp . '</h4>';
			$breakdown = $breakdown . '<h3>League Frames</h3>';
			// Create the table header
			$breakdown = $breakdown . '<table class="ftab-frames">';
			$breakdown = $breakdown . '<thead>
								<tr>
								<th class="ftab-home" colspan="4">Home</th>
								<th class="ftab-away" colspan="4">Away</th>
								<th class="ftab-combined" colspan="4">Combined</th>
								</tr>
								<tr>
								<th class="ftab-hplayed">P</th>
								<th class="ftab-hwon">Won</th>
								<th class="ftab-hlost">Lost</th>
								<th class="ftab-hperc">%</th>
								<th class="ftab-aplayed">P</th>
								<th class="ftab-awon">Won</th>
								<th class="ftab-alost">Lost</th>
								<th class="ftab-aperc">%</th>
								<th class="ftab-tplayed">P</th>
								<th class="ftab-twon">Won</th>
								<th class="ftab-tlost">Lost</th>
								<th class="ftab-tperc">%</th>
								</tr>
								</thead>';
				
			// Get frames played -* 2 as we are just counting fixtures yet each fixture contains 2 frames
		 	$hplayed = $wpdb->get_var($wpdb->prepare("SELECT Count(FixtureId) FROM fixtureresultdetails where HomePlayerId = %d" ,$season->ThePlayerId)) * 2;
		 	$aplayed = $wpdb->get_var($wpdb->prepare("SELECT Count(FixtureId) FROM fixtureresultdetails where AwayPlayerId = %d" ,$season->ThePlayerId)) * 2;
		 	
		 	// When player at home their frames WON
		 	$hhscore = $wpdb->get_var($wpdb->prepare("SELECT Sum(HomeScore) FROM fixtureresultdetails where HomePlayerId = %d",$season->ThePlayerId));
		 	// When player at home their frames LOST
		 	$ahscore = $wpdb->get_var($wpdb->prepare("SELECT Sum(AwayScore) FROM fixtureresultdetails where HomePlayerId = %d",$season->ThePlayerId));
		 	// When player at away their frames WON		 	
		 	$hascore = $wpdb->get_var($wpdb->prepare("SELECT Sum(AwayScore) FROM fixtureresultdetails where AwayPlayerId = %d",$season->ThePlayerId));
		 	// When player at away their frames LOST	 	
		 	$aascore = $wpdb->get_var($wpdb->prepare("SELECT Sum(HomeScore) FROM fixtureresultdetails where AwayPlayerId = %d",$season->ThePlayerId));
			// Total games played	
			$tplayed = $hplayed + $aplayed;
			// When player at home/away their total frames WON		 
			$thscore = $hhscore + $hascore;
			// When player at home/away their total frames LOST
			$tascore = $ahscore + $aascore;
			
			// Now set career totals so we can use them later to create a total summary
			$chplayed = $chplayed + $hplayed;
			$caplayed = $caplayed + $aplayed;
			$chhscore = $chhscore + $hhscore;
			$cahscore = $cahscore + $ahscore;
			$chascore = $chascore + $hascore;
			$caascore = $caascore + $aascore;
			
			// Now work out averages
			if ($hplayed === 0) {
				$hperc = 0;
			} else {
				// Home average
				$hperc = round((float)($hhscore / $hplayed) * 100 );
			}
			if ($aplayed === 0) {
				$aperc = 0;
			} else {
				// Away average
				$aperc = round((float)($hascore / $aplayed) * 100 );
			}
			if (($hplayed + $aplayed) === 0) {
				$tperc = 0;
			} else {
				// Total average
				$tperc = round((float)(($hhscore + $hascore) / ($hplayed + $aplayed)) * 100 );
			}
			// Now out those values into a table
		 	$breakdown = $breakdown . '<tr>
		 						<td class="ftab-hplayed">' . $hplayed . '</td>
								<td class="ftab-hwon">' . $hhscore . '</td>
								<td class="ftab-hlost">' . $ahscore . '</td>
								<td class="ftab-hperc">' . $hperc . '</td>
								<td class="ftab-aplayed">' . $aplayed . '</td>
								<td class="ftab-awon">' . $hascore . '</td>
								<td class="ftab-alost">' . $aascore . '</td>
								<td class="ftab-aperc">' . $aperc . '</td>
								<td class="ftab-tplayed">' . ($hplayed + $aplayed) . '</td>
								<td class="ftab-twon">' . $thscore . '</td>
								<td class="ftab-tlost">' . $tascore . '</td>
								<td class="ftab-tperc">' . $tperc . '</td>
								</tr>';		 	
			     		
			$breakdown = $breakdown . '</table>';
			
			// Now lets create a breaks table...
			// Header first
			$breakdown = $breakdown . '<h3>League Breaks</h3>';
			$breakstext= '<table class="ftab-breaks">';
			$breakstext = $breakstext . '<thead>
								<tr>
								<th class="ftab-hbreak">Top</th>
								<th class="ftab-b30">0-30</th>
								<th class="ftab-b40">31-40</th>
								<th class="ftab-b50">41-50</th>
								<th class="ftab-b60">51-60</th>
								<th class="ftab-b70">61-70</th>
								<th class="ftab-b80">71-80</th>
								<th class="ftab-b90">81-90</th>
								<th class="ftab-b100">91-100</th>
								<th class="ftab-b147">100+</th>
								</tr>
								</thead><tbody>';
			
			// Now the detail
			$breaks30 = 0;
			$breaks40 = 0;
			$breaks50 = 0;
			$breaks60 = 0;
			$breaks70 = 0;
			$breaks80 = 0;
			$breaks90 = 0;
			$breaks100 = 0;
			$breaks147 = 0;
			$thehighbreak = 0;
			$breaks30text = "";
			$breaks40text = "";
			$breaks50text = "";
			$breaks60text = "";
			$breaks70text = "";
			$breaks80text = "";
			$breaks90text = "";
			$breaks100text = "";
			$breaks147text = "";
			$qrybreaks = $wpdb->get_results($wpdb->prepare("SELECT BreakScore FROM fixtures as f INNER JOIN playerbreaks as p ON f.FixtureId = p.FixtureId
											 WHERE f.SeasonId = %d AND p.PlayerId = %d",$season->TheSeasonId, $season->ThePlayerId));
			foreach ($qrybreaks as $break){
				// First check the high break
				if ($break->BreakScore > $thehighbreak){
					$thehighbreak = $break->BreakScore;
				}
				if ($thehighbreak > $cthehighbreak){
					$cthehighbreak = $thehighbreak;
				}
				// Set the new break
				$thebreak = $break->BreakScore;		
				// Allocate to number range		 	
				switch ($thebreak){
					case $thebreak<=30:
						$breaks30 = $breaks30 + 1;
						$cbreaks30 = $cbreaks30 + 1;
						$breaks30text = $breaks30text . $thebreak . ', ';
					break;
					case $thebreak<=40:
						$breaks40 = $breaks40 + 1;
						$cbreaks40 = $cbreaks40 + 1;
						$breaks40text = $breaks40text . $thebreak. ', ';
					break;	
					case $thebreak<=50:
						$breaks50 = $breaks50 + 1;
						$cbreaks50 = $cbreaks50 + 1;
						$breaks50text = $breaks50text . $thebreak. ', ';
					break;
					case $thebreak<=60:
						$breaks60 = $breaks60 + 1;
						$cbreaks60 = $cbreaks60 + 1;
						$breaks60text = $breaks60text . $thebreak. ', ';
					break;
					case $thebreak<=70:
						$breaks70 = $breaks70 + 1;
						$cbreaks70 = $cbreaks70 + 1;
						$breaks70text = $breaks70text . $thebreak. ', ';
					break;
					case $thebreak<=80:
						$breaks80 = $breaks80 + 1;
						$cbreaks80 = $cbreaks80 + 1;
						$breaks80text = $breaks80text . $thebreak. ', ';
					break;
					case $thebreak<=90:
						$breaks90 = $breaks90 + 1;
						$cbreaks90 = $cbreaks90 + 1;
						$breaks90text = $breaks90text . $thebreak. ', ';
					break;
					case $thebreak<=100:
						$breaks100 = $breaks100 + 1;
						$cbreaks100 = $cbreaks100 + 1;
						$breaks100text = $breaks100text . $thebreak. ', ';
					break;
					case $thebreak<=147:
						$breaks147 = $breaks147 + 1;
						$cbreaks147 = $cbreaks147 + 1;
						$breaks147text = $breaks147text . $thebreak. ', ';
					break;
				}	
			} // end break loop
			$breakstext = $breakstext . '<tr>
								<td class="ftab-hbreak">' . $thehighbreak . '</td>
								<td class="ftab-b30">' . $breaks30 . '<br>' . rtrim($breaks30text,', ') . '</td>
								<td class="ftab-b40">' . $breaks40 . '<br>' . rtrim($breaks40text,', ') . '</td>
								<td class="ftab-b50">' . $breaks50 . '<br>' . rtrim($breaks50text,', ') . '</td>
								<td class="ftab-b60">' . $breaks60 . '<br>' . rtrim($breaks60text,', ') . '</td>
								<td class="ftab-b70">' . $breaks70 . '<br>' . rtrim($breaks70text,', ') . '</td>
								<td class="ftab-b80">' . $breaks80 . '<br>' . rtrim($breaks80text,', ') . '</td>
								<td class="ftab-b90">' . $breaks90 . '<br>' . rtrim($breaks90text,', ') . '</td>
								<td class="ftab-b100">' . $breaks100 . '<br>' . rtrim($breaks100text,', ') . '</td>
								<td class="ftab-b147">' . $breaks147 . '<br>' . rtrim($breaks147text,', ') . '</td>
								</tr>';
			$breakstext = $breakstext . '</tbody></table>';
			
			// Now lets create a table for the matches played...
			// Call the function, season, playerid and 0 for playerid2 as it's not a head to head			
			$matchestext = getMatchesTable($season->TheSeasonId, $playerid, 0, $season->TheTeamId);	
			// Now lets look at the cup matches
			// Firstly check if the season has changed as we will already have piped out the player cup data once already
			$cuptext = '';
			if ($lastseason !== $thisseason) {
				// How many tournos for the season
				$qrycuptournoscount =  $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM playertournoentry as p 
															 INNER JOIN tournament as t ON t.TournamentId = p.TournamentId
															 WHERE t.SeasonId = %d AND (p.UPlayer1Id = %d OR p.UPlayer2Id = %d)", $season->TheSeasonId, $playerid, $playerid));
				
				
				if ($qrycuptournoscount > 0) {
					$cuptext = '<h3>Player Singles/Doubles Cup Matches</h3>';
					$cuptext= $cuptext . '<table class="ftab-matches">';
					$cuptext = $cuptext . '<thead>
									<tr>	
									<th class="ftab-tourno">Tournament</th>
									<th class="ftab-round">Got to..</th>
									<th class="ftab-fixres">Total FW</th>
									<th class="ftab-fixres">Total FL</th>
									</tr>
									</thead><tbody>';
					// Player in tournaments so lets loop around
					$qrycuptournos = $wpdb->get_results($wpdb->prepare("SELECT p.TournamentEntryId, p.TournamentId, p.UPlayer1Id, p.UPlayer2Id, p.Name, t.SeasonId, t.name as TournoName 
														 FROM playertournoentry as p INNER JOIN tournament as t ON t.TournamentId = p.TournamentId 
														 WHERE t.SeasonId = %d AND (p.UPlayer1Id = %d OR p.UPlayer2Id = %d)", $season->TheSeasonId, $playerid, $playerid));	
					foreach ($qrycuptournos as $cuptourno){
						$cthf = 0;
						$ctaf = 0;
						//Now loop around the fixtures in each cup tourno played
						$qrycuptournoresults = $wpdb->get_results($wpdb->prepare("SELECT f.HomeEntryId as HomeId, f.AwayEntryId as AwayId, f.FixtureDate, r.name, f.HomeScore, f.AwayScore FROM playertournofixture as f
																	INNER JOIN playertournoround as r ON f.PlayerTournoRoundId = r.PlayerRoundId
																	WHERE HomeEntryId = %d or AwayEntryId = %d",$cuptourno->TournamentEntryId, $cuptourno->TournamentEntryId ));
						//Get the round they got to before going out
						$qrycuptournomax = $wpdb->get_var($wpdb->prepare("SELECT r.Name FROM playertournofixture as f
																	INNER JOIN playertournoround as r ON f.PlayerTournoRoundId = r.PlayerRoundId
																	WHERE HomeEntryId = %d or AwayEntryId = %d
																	ORDER BY  f.PlayerFixtureId DESC LIMIT 1",$cuptourno->TournamentEntryId,$cuptourno->TournamentEntryId ));
						$finalresult = '';
						$opponent = '';							
					    // Get their last result in the comp to display
					    $qryFinalresult = $wpdb->get_results($wpdb->prepare("SELECT f.HomeEntryId as HomeId, f.AwayEntryId as AwayId, f.HomeScore as HScore, f.AwayScore as AScore FROM playertournofixture as f
															  INNER JOIN playertournoround as r ON f.PlayerTournoRoundId = r.PlayerRoundId
															  WHERE HomeEntryId = %d or AwayEntryId = %d
															  ORDER BY  f.PlayerFixtureId DESC LIMIT 1",$cuptourno->TournamentEntryId,$cuptourno->TournamentEntryId));
						foreach ($qryFinalresult as $Finalresult){
							if ($Finalresult->HomeId == $cuptourno->TournamentEntryId){
								$opponent = $wpdb->get_var($wpdb->prepare("SELECT Name FROM playertournoentry where TournamentEntryId = %d",$Finalresult->AwayId));
							// Player at home
								if ($Finalresult->HScore > $Finalresult->AScore){
									// Player won
									$finalresult = 'Won (' . $Finalresult->HScore . ' - ' . $Finalresult->AScore . ')';
									
								} 
								if ($Finalresult->AScore > $Finalresult->HScore){
									// Player lost
									$finalresult = 'Lost (' . $Finalresult->HScore . ' - ' . $Finalresult->AScore . ')';
								}
							} else {
							//Player away
							$opponent = $wpdb->get_var($wpdb->prepare("SELECT Name FROM playertournoentry where TournamentEntryId = %d",$Finalresult->HomeId));
							if ($Finalresult->AScore > $Finalresult->HScore){
									// Player won
									$finalresult = 'Won (' . $Finalresult->AScore . ' - ' . $Finalresult->HScore . ')';
								} 
							if ($Finalresult->HScore > $Finalresult->AScore){
									// Player lost
									$finalresult = 'Lost (' . $Finalresult->AScore . ' - ' . $Finalresult->HScore . ')';
								}
							} // end if
							
						} //$qryFinalresult
	
						foreach ($qrycuptournoresults as $cuptournoresult){
							if ($cuptournoresult->HomeId == $cuptourno->TournamentEntryId){
								// Player at home
								$cthf = $cthf + $cuptournoresult->HomeScore;
								$ctaf = $ctaf + $cuptournoresult->AwayScore;
							} else {
								//Player away
								$cthf = $cthf + $cuptournoresult->AwayScore;
								$ctaf = $ctaf + $cuptournoresult->HomeScore;
							}
						} // $qrycuptournoresults
																	
						$cuptext = $cuptext . '<tr>
									<td class="ftab-tourno">' . $cuptourno->TournoName . '</td>
									<td class="ftab-date">' . $qrycuptournomax . ': ' . $finalresult . ' - ' . $opponent . '</td>							
									<td class="ftab-fixres">' . $cthf . '</td>
									<td class="ftab-fixres">' . $ctaf . '</td>
									</tr>';
					} //$qrycuptournos
																	
				  } //$qrycuptournoscount						 
					$cuptext = $cuptext . '</tbody></table>';								 
			} //end the lastseason thisseason check									 
								 
			$lastseason = $season->TheSeasonId;
			
			// Concatenate for each season
			$breakdown = $breakdown. $breakstext . $matchestext . $cuptext . '</div>';
		} // end season loop
		
		// Set final career totals
		$ctplayed = $chplayed + $caplayed;
		// When player at home/away their total frames WON		 
		$cthscore = $chhscore + $chascore;
		// When player at home/away their total frames LOST
		$ctascore = $cahscore + $caascore;
		// Now work out career averages
		if ($chplayed === 0) {
			$chperc = 0;
		} else {
			// Home average
			$chperc = round((float)($chhscore / $chplayed) * 100 );
		}
		if ($caplayed === 0) {
			$caperc = 0;
		} else {
			// Away average
			$caperc = round((float)($chascore / $caplayed) * 100 );
		}
		 
		if (($chplayed + $caplayed) === 0) {
			$ctperc = 0;
		} else {
			// Total average
			$ctperc = round((float)(($chhscore + $chascore) / ($chplayed + $caplayed)) * 100 );
		}
		// Lets construct the cup breaks data...
			$cupbreaks30 = 0;
			$cupbreaks40 = 0;
			$cupbreaks50 = 0;
			$cupbreaks60 = 0;
			$cupbreaks70 = 0;
			$cupbreaks80 = 0;
			$cupbreaks90 = 0;
			$cupbreaks100 = 0;
			$cupbreaks147 = 0;
			$cupthehighbreak = 0;
			$cupbreaks30text = "";
			$cupbreaks40text = "";
			$cupbreaks50text = "";
			$cupbreaks60text = "";
			$cupbreaks70text = "";
			$cupbreaks80text = "";
			$cupbreaks90text = "";
			$cupbreaks100text = "";
			$cupbreaks147text = "";
			$qrycupbreaks = $wpdb->get_results($wpdb->prepare("SELECT s.SeasonDesc as Season, th.TeamName as HomeTeam, ta.TeamName as AwayTeam,
																b.BreakScore, b.Competition  FROM playercompbreaks as b 
																INNER JOIN uniqueplayers as u ON u.UPlayerId = b.PlayerId  
																INNER JOIN seasons as s ON s.SeasonId = b.SeasonId
																INNER JOIN teams as th ON th.TeamId = b.HomeTeamId
																INNER JOIN teams as ta ON ta.TeamId = b.AwayTeamId
																WHERE u.UPlayerId = %d",$playerid));
			foreach ($qrycupbreaks as $break){
				// First check the high break
				if ($break->BreakScore > $cupthehighbreak){
					$cupthehighbreak = $break->BreakScore;
				}

				// Set the new break
				$thebreak = $break->BreakScore;		
				// Allocate to number range		 	
				switch ($thebreak){
					case $thebreak<=30:
						$cupbreaks30 = $cupbreaks30 + 1;
						$cupbreaks30text = $cupbreaks30text . '<span title="' . $break->Season . ': ' . $break->HomeTeam . ' v ' . $break->AwayTeam . ' - ' . $break->Competition . '">' . $thebreak . '</span>, ';
					break;
					case $thebreak<=40:
						$cupbreaks40 = $cupbreaks40 + 1;
						$cupbreaks40text = $cupbreaks40text . '<span title="' . $break->Season . ': ' . $break->HomeTeam . ' v ' . $break->AwayTeam . ' - ' . $break->Competition . '">' . $thebreak . '</span>, ';
					break;	
					case $thebreak<=50:
						$cupbreaks50 = $cupbreaks50 + 1;
						$cupbreaks50text = $cupbreaks50text . '<span title="' . $break->Season . ': ' . $break->HomeTeam . ' v ' . $break->AwayTeam . ' - ' . $break->Competition . '">' . $thebreak . '</span>, ';
					break;
					case $thebreak<=60:
						$cupbreaks60 = $cupbreaks60 + 1;
						$cupbreaks60text = $cupbreaks60text . '<span title="' . $break->Season . ': ' . $break->HomeTeam . ' v ' . $break->AwayTeam . ' - ' . $break->Competition . '">' . $thebreak . '</span>, ';
					break;
					case $thebreak<=70:
						$cupbreaks70 = $cupbreaks70 + 1;
						$cupbreaks70text = $cupbreaks70text . '<span title="' . $break->Season . ': ' . $break->HomeTeam . ' v ' . $break->AwayTeam . ' - ' . $break->Competition . '">' . $thebreak . '</span>, ';
					break;
					case $thebreak<=80:
						$cupbreaks80 = $cupbreaks80 + 1;
						$cupbreaks80text = $cupbreaks80text . '<span title="' . $break->Season . ': ' . $break->HomeTeam . ' v ' . $break->AwayTeam . ' - ' . $break->Competition . '">' . $thebreak . '</span>, ';
					break;
					case $thebreak<=90:
						$cupbreaks90 = $cupbreaks90 + 1;
						$cupbreaks90text = $cupbreaks90text . '<span title="' . $break->Season . ': ' . $break->HomeTeam . ' v ' . $break->AwayTeam . ' - ' . $break->Competition . '">' . $thebreak . '</span>, ';
					break;
					case $thebreak<=100:
						$cupbreaks100 = $cupbreaks100 + 1;
						$cupbreaks100text = $cupbreaks100text . '<span title="' . $break->Season . ': ' . $break->HomeTeam . ' v ' . $break->AwayTeam . ' - ' . $break->Competition . '">' . $thebreak . '</span>, ';
					break;
					case $thebreak<=147:
						$cupbreaks147 = $cupbreaks147 + 1;
						$cupbreaks147text = $cupbreaks147text . '<span title="' . $break->Season . ': ' . $break->HomeTeam . ' v ' . $break->AwayTeam . ' - ' . $break->Competition . '">' . $thebreak . '</span>, ';
					break;
				}	
			} // end break loop
		
		
		// Now create table header
		
		$career = $career . '<div class="seas-wrapper1"><h4>Career Total</h4></div>';
		$career = $career . '<h3>League Frames</h3>';
		$career = $career . '<table class="ftab-frames">';
		$career = $career . '<thead>
							<tr>
							<th class="ftab-home" colspan="4">Home</th>
							<th class="ftab-away" colspan="4">Away</th>
							<th class="ftab-combined" colspan="4">Combined</th>
							</tr>
							<tr>
							<th class="ftab-hplayed">P</th>
							<th class="ftab-hwon">Won</th>
							<th class="ftab-hlost">Lost</th>
							<th class="ftab-hperc">%</th>
							<th class="ftab-aplayed">P</th>
							<th class="ftab-awon">Won</th>
							<th class="ftab-alost">Lost</th>
							<th class="ftab-aperc">%</th>
							<th class="ftab-tplayed">P</th>
							<th class="ftab-twon">Won</th>
							<th class="ftab-tlost">Lost</th>
							<th class="ftab-tperc">%</th>
							</tr>
							</thead>';	
		// And now put in the career data
		$career = $career . '<tr>
	 						<td class="ftab-hplayed">' . $chplayed . '</td>
							<td class="ftab-hwon">' . $chhscore . '</td>
							<td class="ftab-hlost">' . $cahscore . '</td>
							<td class="ftab-hperc">' . $chperc . '</td>
							<td class="ftab-aplayed">' . $caplayed . '</td>
							<td class="ftab-awon">' . $chascore . '</td>
							<td class="ftab-alost">' . $caascore . '</td>
							<td class="ftab-aperc">' . $caperc . '</td>
							<td class="ftab-tplayed">' . ($chplayed + $caplayed) . '</td>
							<td class="ftab-twon">' . $cthscore . '</td>
							<td class="ftab-tlost">' . $ctascore . '</td>
							<td class="ftab-tperc">' . $ctperc . '</td>
							</tr>';		 	
		     		
		$career = $career . '</table>';
		
		// And now put in the career breaks
		$career = $career . '<h3>League Breaks</h3>';
		$career=  $career . '<table class="ftab-breaks">';
		$career = $career . '<thead>
							<tr>
							<th class="ftab-hbreak">Top</th>
							<th class="ftab-b30">0-30</th>
							<th class="ftab-b40">31-40</th>
							<th class="ftab-b50">41-50</th>
							<th class="ftab-b60">51-60</th>
							<th class="ftab-b70">61-70</th>
							<th class="ftab-b80">71-80</th>
							<th class="ftab-b90">81-90</th>
							<th class="ftab-b100">91-100</th>
							<th class="ftab-b147">100+</th>
							</tr>
							</thead>';
		$career = $career . '<tr>
							<td class="ftab-hbreak">' . $cthehighbreak . '</td>
							<td class="ftab-b30">' . $cbreaks30 . '</td>
							<td class="ftab-b40">' . $cbreaks40 . '</td>
							<td class="ftab-b50">' . $cbreaks50 . '</td>
							<td class="ftab-b60">' . $cbreaks60 . '</td>
							<td class="ftab-b70">' . $cbreaks70 . '</td>
							<td class="ftab-b80">' . $cbreaks80 . '</td>
							<td class="ftab-b90">' . $cbreaks90 . '</td>
							<td class="ftab-b100">' . $cbreaks100 . '</td>
							<td class="ftab-b147">' . $cbreaks147 . '</td>
							</tr>';
		$career = $career . '</table>';					
		// And now put in the CUP breaks in
		$career = $career . '<h3>Cup Breaks</h3>';
		$career=  $career . '<table class="ftab-breaks">';
		$career = $career . '<thead>
							<tr>
								<th class="ftab-hbreak">Top</th>
								<th class="ftab-b30">0-30</th>
								<th class="ftab-b40">31-40</th>
								<th class="ftab-b50">41-50</th>
								<th class="ftab-b60">51-60</th>
								<th class="ftab-b70">61-70</th>
								<th class="ftab-b80">71-80</th>
								<th class="ftab-b90">81-90</th>
								<th class="ftab-b100">91-100</th>
								<th class="ftab-b147">100+</th>
							</tr>
							</thead>';
		$career = $career . '<tr>
								<td class="ftab-hbreak">' . $cupthehighbreak . '</td>
								<td class="ftab-b30">' . $cupbreaks30 . '<br>' . rtrim($cupbreaks30text,', ') . '</td>
								<td class="ftab-b40">' . $cupbreaks40 . '<br>' . rtrim($cupbreaks40text,', ') . '</td>
								<td class="ftab-b50">' . $cupbreaks50 . '<br>' . rtrim($cupbreaks50text,', ') . '</td>
								<td class="ftab-b60">' . $cupbreaks60 . '<br>' . rtrim($cupbreaks60text,', ') . '</td>
								<td class="ftab-b70">' . $cupbreaks70 . '<br>' . rtrim($cupbreaks70text,', ') . '</td>
								<td class="ftab-b80">' . $cupbreaks80 . '<br>' . rtrim($cupbreaks80text,', ') . '</td>
								<td class="ftab-b90">' . $cupbreaks90 . '<br>' . rtrim($cupbreaks90text,', ') . '</td>
								<td class="ftab-b100">' . $cupbreaks100 . '<br>' . rtrim($cupbreaks100text,', ') . '</td>
								<td class="ftab-b147">' . $cupbreaks147 . '<br>' . rtrim($cupbreaks147text,', ') . '</td>
							</tr>';
		$career = $career . '</table>';						
							
		$playertext = $playertext . $career . $breakdown;
		return $playertext;
	}
	
	//Function for returning club data based on clubid
	function getClubTable($clubid){
		
		//clubid = relates to which club
		
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		$clubtext = '';
		// Get the data from the database
		$clubname = $wpdb->get_var($wpdb->prepare("SELECT ClubName FROM clubs WHERE ClubId = %d",$clubid));
		
		$contactname = $wpdb->get_var($wpdb->prepare("SELECT ContactName FROM clubs WHERE ClubId = %d",$clubid));
		
		$tel = $wpdb->get_var($wpdb->prepare("SELECT Tel FROM clubs WHERE ClubId = %d",$clubid));
		
		$mobile = $wpdb->get_var($wpdb->prepare("Select Mobile FROM clubs WHERE ClubId = %d",$clubid));
		$address1 = $wpdb->get_var($wpdb->prepare("SELECT Address1 FROM clubs WHERE ClubId = %d",$clubid));
		$address2 = $wpdb->get_var($wpdb->prepare("SELECT Address2 FROM clubs WHERE ClubId = %d",$clubid));
		$address3 = $wpdb->get_var($wpdb->prepare("SELECT Address3 FROM clubs WHERE ClubId = %d",$clubid));
		$district = $wpdb->get_var($wpdb->prepare("SELECT District FROM clubs WHERE ClubId = %d",$clubid));
		$postcode = $wpdb->get_var($wpdb->prepare("SELECT PostCode FROM clubs WHERE ClubId = %d",$clubid));
		$mapid =  $wpdb->get_var($wpdb->prepare("SELECT MapId FROM clubs WHERE ClubId = %d",$clubid));
		
		// Set the class of the table for css
		$tabclass = 'ftab-clubs';
		// Set the name
		$clubtext = '<h3>' . $clubname .'</h3>';
		// Make the table
		$clubtext = $clubtext . '<table class="' . $tabclass . '">
					    <thead><tr>
						<th class="ftab-contact">Contact details</th>
						<th class="ftab-address">Address</th>
						</tr></thead><tbody>';
		
		$clubtext = $clubtext .
					'<tr>
						<td class="ftab-contact">Name: ' . $contactname . '<br>Tel: ' . $tel . '<br>Mobile: ' . $mobile .'</td>
						<td class="ftab-address">' . $address1 . '<br>' . $address2 . '<br>' . $address3 . '<br>' . $postcode . '</td>
					</tr>';
		
		$clubtext = $clubtext . '</tbody></table><br>';
		
		$clubtext = $clubtext . '<table class="' . $tabclass . '">
					    <thead><tr>
						<th class="ftab-map">Map</th>
						</tr></thead><tbody>';
		
		$clubtext = $clubtext .
					'<tr>
						<td class="ftab-map">' . $mapid .'</td>
					</tr>';
		
		$clubtext = $clubtext . '</tbody></table><br>';
		
		return $clubtext;
	}
	
	// Function for returning match details for a player or players head to head
    function getMatchesTable($seasonid, $player1id, $player2id, $teamid){
    	// player1id = id of player1
    	// player2id = id of player2 if its a head to head query. If not head to head player2id will be set to zero
    	// seasonid  = id of season, unless it's an all season query for head to head and then it will be zero
    	// teamid = id of the team unless it's not required and then it will be zero
    	
		global $rootlocal;
		global $wpdb;
		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');
		
		$playername = $wpdb->get_var($wpdb->prepare("SELECT CONCAT(Forename, ' ' , Surname) FROM uniqueplayers WHERE UPlayerId = %d" ,$player1id));
		
		$matchestext = '<h3>League Matches</h3>';
			$matchestext= $matchestext . '<table class="ftab-matches">';
			$matchestext = $matchestext . '<thead>
								<tr>';
			if ($player2id != 0) {
				$matchestext = $matchestext . '<th class="ftab-date">Date</th>';					
			} else {
				$matchestext = $matchestext . '<th class="ftab-weekno">Week</th>
											   <th class="ftab-date">Date</th>';
			}
			$matchestext = $matchestext . '<th class="ftab-team">Team</th>
								<th class="ftab-player">Player</th>
								<th class="ftab-hbreak">Breaks</th>
								<th class="ftab-score">F</th>
								<th class="ftab-score">F</th>
								<th class="ftab-abreak">Breaks</th>
								<th class="ftab-player">Player</th>
								<th class="ftab-team">Team</th>
								</tr>
								</thead><tbody>';
								
			// So if player2id = 0 then it's not head to head so just get that players matches witht the teamid as could have played for multiple teams in a season
			if ($player2id == 0) {
				$qrymatches = $wpdb->get_results($wpdb->prepare("SELECT  d.FixtureId, f.Weekno, f.PlayDate, t.TeamId as t1TId, t.TeamName as t1Name, s.TeamId as t2TId, s.TeamName as t2Name, d.HomePlayerId, d.AwayPlayerId, d.HomeScore, d.AwayScore, 
											  		  u1.UPlayerId as UPlayerId1, CONCAT(u1.Forename,' ', u1.Surname) as p1Name, u2.UPlayerId  as UPlayerId2, CONCAT(u2.Forename,' ', u2.Surname) as p2Name
												FROM fixtureresultdetails as d 
													INNER JOIN fixtures as f on f.FixtureId = d.FixtureId
													INNER JOIN teams as t ON f.HomeTeamId = t.TeamId
													INNER JOIN teams as s ON f.AwayTeamId = s.TeamId
													INNER JOIN players as p1 ON p1.PlayerId = d.HomePlayerId
													INNER JOIN players as p2 ON p2.PlayerId = d.AwayPlayerId
													INNER JOIN uniqueplayers as u1 ON p1.UPlayerId = u1.UPlayerId
													INNER JOIN uniqueplayers as u2 ON p2.UPlayerId = u2.UPlayerId
												WHERE f.SeasonId = %d
													AND ((u1.UPlayerId = %d AND t.TeamId = %d) OR (u2.UPlayerId = %d AND s.Teamid=%d))
													",$seasonid,$player1id,$teamid,$player1id,$teamid));
			} else {
			// So player2id not 0 then it's a head to head so get those matches matches
				$qrymatches = $wpdb->get_results($wpdb->prepare("SELECT f.SeasonId as SeasonId, d.FixtureId, f.Weekno, f.PlayDate, t.TeamId as t1TId, t.TeamName as t1Name, s.TeamId as t2TId, s.TeamName as t2Name, d.HomePlayerId, d.AwayPlayerId, d.HomeScore, d.AwayScore, 
											  		  u1.UPlayerId as UPlayerId1, CONCAT(u1.Forename,' ', u1.Surname) as p1Name, u2.UPlayerId  as UPlayerId2, CONCAT(u2.Forename,' ', u2.Surname) as p2Name
												FROM fixtureresultdetails as d 
													INNER JOIN fixtures as f on f.FixtureId = d.FixtureId
													INNER JOIN teams as t ON f.HomeTeamId = t.TeamId
													INNER JOIN teams as s ON f.AwayTeamId = s.TeamId
													INNER JOIN players as p1 ON p1.PlayerId = d.HomePlayerId
													INNER JOIN players as p2 ON p2.PlayerId = d.AwayPlayerId
													INNER JOIN uniqueplayers as u1 ON p1.UPlayerId = u1.UPlayerId
													INNER JOIN uniqueplayers as u2 ON p2.UPlayerId = u2.UPlayerId
												WHERE (u1.UPlayerId = %d AND u2.UPlayerId = %d) OR (u1.UPlayerId = %d AND u2.UPlayerId = %d)",$player1id,$player2id,$player2id,$player1id ));
			}
			
			foreach ($qrymatches as $match) {
				//Find any breaks
				$hbrktext = '';
				$abrktext = '';
				$homebreaks = $wpdb->get_results($wpdb->prepare("SELECT BreakScore FROM playerbreaks WHERE PlayerId = %d AND FixtureId = %d",$match->HomePlayerId,$match->FixtureId));
				foreach ($homebreaks as $hbreak){
					$hbrktext =$hbrktext . $hbreak->BreakScore . ',';
				}
				$awaybreaks = $wpdb->get_results($wpdb->prepare("SELECT BreakScore FROM playerbreaks WHERE PlayerId = %d AND FixtureId = %d",$match->AwayPlayerId,$match->FixtureId));
				foreach ($awaybreaks as $abreak){
					$abrktext =$abrktext . $abreak->BreakScore . ',';
				}
				$hbrktext = rtrim($hbrktext,",");
				$abrktext = rtrim($abrktext,",");
				// Some bolding of the selected player
				if ($playername == $match->p1Name){
					$hbolds = '<b>';
					$hbolde = '</b>';
				} else {
					$hbolds = '';
					$hbolde = '';
				}
				if ($playername == $match->p2Name){
					$abolds = '<b>';
					$abolde = '</b>';
				} else {
					$abolds = '';
					$abolde = '';
				}
				
				// If head to head then no need to show week just the date
				if ($player2id != 0) {
					$matchestext =  $matchestext . '<tr>
								<td class="ftab-date">' .date("d-M-y",strtotime($match->PlayDate)).  '</td>';
					
				} else {
					// If NOT head to head then show week and the date
					$matchestext =  $matchestext . '<tr>
									<td class="ftab-weekno">' . $match->Weekno . '</td>
									<td class="ftab-date">' .date("d-M",strtotime($match->PlayDate)).  '</td>';
				} //end if
				
				$matchestext =  $matchestext . '
								<td class="ftab-team"><a href="http://www.bdcsnooker.org/teams?teamid=' . $match->t1TId . '&seasonid=' . $match->SeasonId .'">' .$match->t1Name . '</a></td>								
								<td id="playerid-' . $match->UPlayerId1 . ' " class="ftab-player">' . $hbolds . '<a href="http://www.bdcsnooker.org/players?playerid=' . $match->UPlayerId1 . '">' . $match->p1Name . '</a>' . $hbolde . '</td>		
								<td class="ftab-hbreak">' . $hbolds . $hbrktext . $hbolde .'</td>
								<td class="ftab-fixres">' . $hbolds . $match->HomeScore . $hbolde . '</td>
								<td class="ftab-fixres">' . $abolds. $match->AwayScore . $abolde . '</td>
								<td class="ftab-abreak">' . $abolds. $abrktext . $abolde .'</td>
								<td id="playerid-' . $match->UPlayerId2 . ' " class="ftab-player">' . $abolds . '<a href="http://www.bdcsnooker.org/players?playerid=' . $match->UPlayerId2 . '">' . $match->p2Name . '</a>' . $abolde . '</td>
								<td class="ftab-team"><a href="http://www.bdcsnooker.org/teams?teamid=' . $match->t2TId . '&seasonid=' . $match->SeasonId .'">' .$match->t2Name . '</a></td>
								</tr>';
				
			}
			$matchestext = $matchestext . '</tbody></table>';
			return $matchestext;
    }
	
	function getOpenCompetitions($seasonid,$type){
		
		//seasonid = relates to which season
		// $type = 0 = normal table for a page, 1 = for a widget, 2 = for scrolling text
		
		global $rootlocal;
		global $wpdb;

		$location = $_SERVER['DOCUMENT_ROOT'];
				
		include ($location . $rootlocal . '/wp-config.php');
		include ($location . $rootlocal . '/wp-load.php');
		include ($location . $rootlocal . '/wp-includes/pluggable.php');

		$opencomptext = '';
		// Get the data from the database
		$numdiv = $wpdb->get_var("SELECT Count(*) FROM competitions as c INNER JOIN seasons AS s ON s.SeasonId = c.SeasonId WHERE s.StatusFlag = 'O'");
		$divisions = $wpdb->get_results("SELECT c.CompetitionId as CId, c.SeasonId as SId, c.CompetitionName AS cName, s.StatusFlag FROM competitions as c 
											INNER JOIN seasons AS s ON s.SeasonId = c.SeasonId WHERE s.StatusFlag = 'O'");
		$numtourno = $wpdb->get_var($wpdb->prepare("SELECT Count(*) FROM tournament WHERE SeasonId = %d",$seasonid));
		$tournaments = $wpdb->get_results("SELECT TournamentId, Name, Format FROM tournament WHERE SeasonId = " . $seasonid . "");
		
		// Normal table
		if ($type===0) {
			$tabclass = 'ftab-comps';
			// Make the table for a normal page
			// Want a table of 4 cols only so work out how many rows
			$tabrows = ceil(($numdiv + $numtourno)/4);
			// Lets push the cells and text into an array
			$comps = array();
			foreach ($divisions as $division){
				array_push($comps,'<td class="ftab-compname"><a href="http://www.bdcsnooker.org/divisions/?divisionid=' .  $division->CId . '&seasonid=' . $seasonid . '">' . $division->cName . '</a></td>');
			}
			foreach ($tournaments as $tournament){
				// Create link as its different page for player tournos as opposed to team tournos
				if ($tournament->Format == 2){
					$link ='<a href="http://www.bdcsnooker.org/competitions/team-competitions/?teamtournoid=' .  $tournament->TournamentId . '&seasonid=' . $seasonid . '">';
				} else {
					$link ='<a href="http://www.bdcsnooker.org/competitions/player-competitions/?playertournoid=' .  $tournament->TournamentId . '&seasonid=' . $seasonid . '">';
				}
				array_push($comps,'<td class="ftab-compname">' . $link . $tournament->Name . '</a></td>');
			}

			$opencomptext = $opencomptext . '<table class="' . $tabclass . '">
										     <thead><tr></tr></thead>
										     <tbody>';
			$counter = 0;								 
			for ($j=1; $j < $tabrows+1 ; $j++) { // rows loop
				$opencomptext = $opencomptext . '<tr>';
				for ($i=1; $i < 5 ; $i++) { // cols loop
					$opencomptext = $opencomptext . '<td>' . $comps[$counter] . '</td>';
					$counter= $counter+1;
				}
				$opencomptext = $opencomptext . '</tr>';
			}
			$opencomptext = $opencomptext . '</tbody></table>';
		}

		// Make the table for a widget
		if ($type===1) {
			$tabclass = 'mtab-comps';	
			$opencomptext = $opencomptext . '<table class="' . $tabclass . '">
						    <thead><tr>	
							</tr></thead><tbody>';
			foreach ($divisions as $division){
				$opencomptext = $opencomptext .
						'<tr>
							<td class="ftab-compname"><a href="http://www.bdcsnooker.org/divisions/?divisionid=' .  $division->CId . '&seasonid=' . $seasonid . '">' . $division->cName . '</a></td>
						 </tr>';
			}
			foreach ($tournaments as $tournament){
				// Create link as its different page for player tournos as opposed to team tournos
				if ($tournament->Format == 2){
					$link ='<a href="http://www.bdcsnooker.org/competitions/team-competitions/?teamtournoid=' .  $tournament->TournamentId . '&seasonid=' . $seasonid . '">';
				} else {
					$link ='<a href="http://www.bdcsnooker.org/competitions/player-competitions/?playertournoid=' .  $tournament->TournamentId . '&seasonid=' . $seasonid . '">';
				}
				$opencomptext = $opencomptext .
						'<tr>
							<td class="ftab-compname">' . $link . $tournament->Name . '</a></td>
						 </tr>';
			}
			$opencomptext = $opencomptext . '</tbody></table>';
		} 

		// Make the table for a scrolling text widget
		if ($type===2) {	
			$opencomptext = $opencomptext . '<div class "scrolltext">';
			foreach ($divisions as $division){
				$opencomptext = $opencomptext .
						'<a href="http://www.bdcsnooker.org/divisions/?divisionid=' .  $division->CId . '&seasonid=' . $seasonid . '">' . $division->cName . '</a>&nbsp;|&nbsp;';
			}
			foreach ($tournaments as $tournament){
				// Create link as its different page for player tournos as opposed to team tournos
				if ($tournament->Format == 2){
					$link ='<a href="http://www.bdcsnooker.org/competitions/team-competitions/?teamtournoid=' .  $tournament->TournamentId . '&seasonid=' . $seasonid . '">';
				} else {
					$link ='<a href="http://www.bdcsnooker.org/competitions/player-competitions/?playertournoid=' .  $tournament->TournamentId . '&seasonid=' . $seasonid . '">';
				}
				$opencomptext = $opencomptext .
						$link . $tournament->Name . '</a>&nbsp;|&nbsp;';
			}
		} 
		
		
		return $opencomptext;
	}
	//----------------------------------------------------------------------------------------------------------
	// BDSCL WIDGETS TO DISPLAY CERTAIN TABLES
		// SCROLLING OPEN COMPETITIONS WIDGET
		class scrollcompetitions_widget extends WP_Widget{
	
			function __construct(){
				$params = array(
					'description'=>'Use this widget to show vertical scrolling open competitions',
						'name'=>'BDSCL Scrolling Open Competitions Text'
				);
				parent::__construct('ScrollingCompetition','',$params);
			}
			
			public function form($instance){
				extract($instance);
				// Check if options exist, if its null, put default or new option in place for use in the form
				// Title
				if ( isset( $instance[ 'title' ] ) ) {
						$title = $instance[ 'title' ];
					} else {
						$title = __( 'Open competitions', 'scrollcompetitions_widget_domain' );
				}
				// Speed
				if ( isset( $instance[ 'speed' ] ) ) {
						$speed = $instance[ 'speed' ];
					} else {
						$speed = __( '5', 'scrollcompetitions_widget_domain' );
				}
				// Direction
				if ( isset( $instance[ 'direction' ] ) ) {
						$direction = $instance[ 'direction' ];
					} else {
						$direction = __( 'left', 'scrollcompetitions_widget_domain' );
				}
				?>
				
				
				<p>
					<label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
					<input
						class="widefat"
						id="<?php echo $this->get_field_id('title'); ?>"
						name="<?php echo $this->get_field_name('title'); ?>"
						value="<?php if( isset($title) ) echo esc_attr($title); ?>"
					>	
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('speed'); ?>">Speed:</label>
						<textarea
							class="widefat"
							rows="1"
							id="<?php echo $this->get_field_id('speed'); ?>"
							name="<?php echo $this->get_field_name('speed'); ?>"
						><?php if( isset($speed) ) echo esc_attr($speed); ?></textarea>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('direction'); ?>">Direction:</label>
						 <select class='widefat' id="<?php echo $this->get_field_id('direction'); ?>"
				                name="<?php echo $this->get_field_name('direction'); ?>" type="text">
				          <option value='up'<?php echo ($direction=='up')?'selected':''; ?>>
				            Up
				          </option>
				          <option value='down'<?php echo ($direction=='down')?'selected':''; ?>>
				            Down
				          </option> 
				          <option value='left'<?php echo ($direction=='left')?'selected':''; ?>>
				            Left
				          </option> 
				          <option value='right'<?php echo ($direction=='right')?'selected':''; ?>>
				            Right
				          </option> 
				        </select> 
				</p>
				<?php
				
			}
			
			public function widget($args,$instance){
				extract($args);
				extract($instance);
				
				echo $before_widget;
				echo $before_title . $title . $after_title;
				$description = getOpenCompetitions($GLOBALS["seasonid"],2); //season, type
				echo '<marquee class="marquee_text" behavior="scroll" scrollAmount="'.$speed.'" direction="'.$direction.'"><div id="scrolltext">'.$description.'</div></marquee>';
				echo $after_widget;
			}
			
			// Updating widget replacing old instances with new
			public function update( $new_instance, $old_instance ) {
				$instance = array();
				$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
				$instance['speed'] = ( ! empty( $new_instance['speed'] ) ) ? strip_tags( $new_instance['speed'] ) : '';
				$instance['direction'] = ( ! empty( $new_instance['direction'] ) ) ? strip_tags( $new_instance['direction'] ) : '';
				return $instance;
			}
		}
		
		function scrollcompetitions_load_widget(){
			register_widget('scrollcompetitions_widget');
		}
		add_action('widgets_init','scrollcompetitions_load_widget');
		
		//END OF SCROLLING OPEN COMPETITIONS WIDGET
	
	
		// SCROLLING LATEST RESULT WIDGET
		class scrollresult_widget extends WP_Widget{
	
			function __construct(){
				$params = array(
					'description'=>'Use this widget to show vertical scrolling latest results',
						'name'=>'BDSCL Scrolling Latest Results Text'
				);
				parent::__construct('ScrollingResult','',$params);
			}
			
			public function form($instance){
				extract($instance);
				// Check if options exist, if its null, put default or new option in place for use in the form
				// Title
				if ( isset( $instance[ 'title' ] ) ) {
						$title = $instance[ 'title' ];
					} else {
						$title = __( 'Latest results', 'scrollresult_widget_domain' );
				}
				// Type of scroll - either league results or player competitions
				if ( isset( $instance[ 'type' ] ) ) {
						$type = $instance[ 'type' ];
					} else {
						$type = __( 'League', 'scrollresult_widget_domain' );
				}
				// Speed
				if ( isset( $instance[ 'speed' ] ) ) {
						$speed = $instance[ 'speed' ];
					} else {
						$speed = __( '5', 'scrollresult_widget_domain' );
				}
				// Direction
				if ( isset( $instance[ 'direction' ] ) ) {
						$direction = $instance[ 'direction' ];
					} else {
						$direction = __( 'left', 'scrollresult_widget_domain' );
				}
				?>
				
				
				<p>
					<label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
					<input
						class="widefat"
						id="<?php echo $this->get_field_id('title'); ?>"
						name="<?php echo $this->get_field_name('title'); ?>"
						value="<?php if( isset($title) ) echo esc_attr($title); ?>"
					>	
				</p>
				
				<p>
					<label for="<?php echo $this->get_field_id('type'); ?>">Results type:</label>
						 <select class='widefat' id="<?php echo $this->get_field_id('type'); ?>"
				                name="<?php echo $this->get_field_name('type'); ?>" type="text">
				          <option value='league'<?php echo ($type=='league')?'selected':''; ?>>
				            League results
				          </option>
				          <option value='player'<?php echo ($type=='player')?'selected':''; ?>>
				            Player competitions
				          </option> 
				        </select> 
				</p>
				
				<p>
					<label for="<?php echo $this->get_field_id('speed'); ?>">Speed:</label>
						<textarea
							class="widefat"
							rows="1"
							id="<?php echo $this->get_field_id('speed'); ?>"
							name="<?php echo $this->get_field_name('speed'); ?>"
						><?php if( isset($speed) ) echo esc_attr($speed); ?></textarea>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('direction'); ?>">Direction:</label>
						 <select class='widefat' id="<?php echo $this->get_field_id('direction'); ?>"
				                name="<?php echo $this->get_field_name('direction'); ?>" type="text">
				          <option value='up'<?php echo ($direction=='up')?'selected':''; ?>>
				            Up
				          </option>
				          <option value='down'<?php echo ($direction=='down')?'selected':''; ?>>
				            Down
				          </option> 
				          <option value='left'<?php echo ($direction=='left')?'selected':''; ?>>
				            Left
				          </option> 
				          <option value='right'<?php echo ($direction=='right')?'selected':''; ?>>
				            Right
				          </option> 
				        </select> 
				</p>
				<?php
				
			}
			
			public function widget($args,$instance){
				extract($args);
				extract($instance);
				
				echo $before_widget;
				echo $before_title . $title . $after_title;
				if ($type == 'league') {
					$description = getWeeklyResultsbyDiv($GLOBALS["seasonid"],999,-7,1); //season, competition,range,type
				} else {
					$description = getPlayerCompResults($GLOBALS["seasonid"],-7,1); //season,range,type
				}
				
				echo '<marquee class="marquee_text" behavior="scroll" scrollAmount="'.$speed.'" direction="'.$direction.'"><div id="scrolltext">'.$description.'</div></marquee>';
				echo $after_widget;
			}
			
			// Updating widget replacing old instances with new
			public function update( $new_instance, $old_instance ) {
				$instance = array();
				$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
				$instance['type'] = ( ! empty( $new_instance['type'] ) ) ? strip_tags( $new_instance['type'] ) : '';
				$instance['speed'] = ( ! empty( $new_instance['speed'] ) ) ? strip_tags( $new_instance['speed'] ) : '';
				$instance['direction'] = ( ! empty( $new_instance['direction'] ) ) ? strip_tags( $new_instance['direction'] ) : '';
				return $instance;
			}
		}
		
		function scrollresult_load_widget(){
			register_widget('scrollresult_widget');
		}
		add_action('widgets_init','scrollresult_load_widget');
		
		//END OF SCROLLING LATEST RESULT WIDGET
		
		// COMPETITION LIST WIDGET FOR A PARTICULAR SEASON ///////////////////////////////////////////////////
		class competitionlist_widget extends WP_Widget {

			function __construct() {
				parent::__construct(
				// Base ID of your widget
				'competitionlist_widget', 
		
				// Widget name will appear in UI
				__('BDSCL Current Competition display', 'competitionlist_widget_domain'), 
		
				// Widget description
				array( 'description' => __( 'Displays the current list of open competitions depending on season', 'competitionlist_widget_domain' ), ) 
				);
			}
	
			// Creating widget front-end
			// This is where the action happens
			public function widget( $args, $instance ) {
				$title = apply_filters( 'widget_title', $instance['title'] );
				// before and after widget arguments are defined by themes
				echo $args['before_widget'];
				if ( ! empty( $title ) )
				echo $args['before_title'] . $title . $args['after_title'];
		
				// This is where you run the code and display the output
				// Get the open competitions
				$complisttext = getOpenCompetitions($GLOBALS["seasonid"],1); //1 for a widget
				echo __( '<div id="competitionlist_text">'.$complisttext.'</div>', 'competitionlist_widget_domain' );
				
				echo $args['after_widget'];
			}
					
			// Widget Backend 
			public function form( $instance ) {
				if ( isset( $instance[ 'title' ] ) ) {
						$title = $instance[ 'title' ];
					} else {
						$title = __( 'Open Competitions', 'competitionlist_widget_domain' );
				}
				
				// Widget admin form
				?>
				<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
				</p>
				<?php 
			}
				
			// Updating widget replacing old instances with new
			public function update( $new_instance, $old_instance ) {
				$instance = array();
				$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
				return $instance;
			}
			
		} // Class competitionlist_widget ends here --------------------------------------------------------------------------------------------

		// Register and load the widget
		function competitionlist_load_widget() {
			register_widget( 'competitionlist_widget' );
		}
		add_action( 'widgets_init', 'competitionlist_load_widget' );
		// END OF COMPETITIONS WIDGET //////////////////////////////////////////////////////////////////////////		
		
		// FIXTURES WIDGET FOR A PARTICULAR DIV AND SEASON ///////////////////////////////////////////////////
		class fixturebydiv_widget extends WP_Widget {

			function __construct() {
				parent::__construct(
				// Base ID of your widget
				'fixturebydiv_widget', 
		
				// Widget name will appear in UI
				__('BDSCL Fixture by Season and Division', 'fixturebydiv_widget_domain'), 
		
				// Widget description
				array( 'description' => __( 'Displays the particular fixtures for a division depending on season', 'fixturebydiv_widget_domain' ), ) 
				);
			}
	
			// Creating widget front-end
			// This is where the action happens
			public function widget( $args, $instance ) {
				$title = apply_filters( 'widget_title', $instance['title'] );
				// before and after widget arguments are defined by themes
				echo $args['before_widget'];
				if ( ! empty( $title ) )
				echo $args['before_title'] . $title . $args['after_title'];
		
				// This is where you run the code and display the output
				// Get the latest fixtures that are coming
				$fixtabletext = getWeeklyFixturesbyDiv($GLOBALS["seasonid"],$GLOBALS["divisionid"],8,0); //seasonid, compid, range (next 14 days), type 0 =table
				echo __( '<div id="fixture_text">'.$fixtabletext.'</div>', 'fixturebydiv_widget_domain' );
				
				echo $args['after_widget'];
			}
					
			// Widget Backend 
			public function form( $instance ) {
				if ( isset( $instance[ 'title' ] ) ) {
						$title = $instance[ 'title' ];
					} else {
						$title = __( '', 'fixturebydiv_widget_domain' );
				}
				
				// Widget admin form
				?>
				<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
				</p>
				<?php 
			}
				
			// Updating widget replacing old instances with new
			public function update( $new_instance, $old_instance ) {
				$instance = array();
				$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
				return $instance;
			}
			
		} // Class fixture_widget ends here --------------------------------------------------------------------------------------------

		// Register and load the widget
		function fixturebydiv_load_widget() {
			register_widget( 'fixturebydiv_widget' );
		}
		add_action( 'widgets_init', 'fixturebydiv_load_widget' );
		// END OF FIXTURES WIDGET //////////////////////////////////////////////////////////////////////////
		
		// RESULTS WIDGET FOR A PARTICULAR DIV AND SEASON ///////////////////////////////////////////////////
		class resultbydiv_widget extends WP_Widget {

			function __construct() {
				parent::__construct(
				// Base ID of your widget
				'resultbydiv_widget', 
		
				// Widget name will appear in UI
				__('BDSCL Results by Season and Division', 'resultbydiv_widget_domain'), 
		
				// Widget description
				array( 'description' => __( 'Displays the particular results for a division depending on season', 'resultbydiv_widget_domain' ), ) 
				);
			}
	
			// Creating widget front-end
			// This is where the action happens
			public function widget( $args, $instance ) {
				$title = apply_filters( 'widget_title', $instance['title'] );
				// before and after widget arguments are defined by themes
				echo $args['before_widget'];
				if ( ! empty( $title ) )
				echo $args['before_title'] . $title . $args['after_title'];
		
				// This is where you run the code and display the output
				// Get the latest fixtures that are coming
				$restabletext = getWeeklyResultsbyDiv($GLOBALS["seasonid"],$GLOBALS["divisionid"],-7,0); //seasonid, compid, range (last 8 days), type 0 =table
				echo __( '<div id="result_text">'.$restabletext.'</div>', 'resultbydiv_widget_domain' );
				
				echo $args['after_widget'];
			}
					
			// Widget Backend 
			public function form( $instance ) {
				if ( isset( $instance[ 'title' ] ) ) {
						$title = $instance[ 'title' ];
					} else {
						$title = __( 'Latest Results', 'resultbydiv_widget_domain' );
				}
				
				// Widget admin form
				?>
				<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
				</p>
				<?php 
			}
				
			// Updating widget replacing old instances with new
			public function update( $new_instance, $old_instance ) {
				$instance = array();
				$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
				return $instance;
			}
			
		} // Class fixture_widget ends here --------------------------------------------------------------------------------------------

		// Register and load the widget
		function resultbydiv_load_widget() {
			register_widget( 'resultbydiv_widget' );
		}
		add_action( 'widgets_init', 'resultbydiv_load_widget' );
		// END OF RESULTS WIDGET //////////////////////////////////////////////////////////////////////////
		
		// BREAKS WIDGET FOR A PARTICULAR DIV AND SEASON ///////////////////////////////////////////////////
		class breakbydiv_widget extends WP_Widget {

			function __construct() {
				parent::__construct(
				// Base ID of your widget
				'breakbydiv_widget', 
		
				// Widget name will appear in UI
				__('BDSCL Breaks by Season and Division', 'breakbydiv_widget_domain'), 
		
				// Widget description
				array( 'description' => __( 'Displays the particular breaks for a division depending on season', 'breakbydiv_widget_domain' ), ) 
				);
			}
	
			// Creating widget front-end
			// This is where the action happens
			public function widget( $args, $instance ) {
				$title = apply_filters( 'widget_title', $instance['title'] );
				// before and after widget arguments are defined by themes
				echo $args['before_widget'];
				if ( ! empty( $title ) )
				echo $args['before_title'] . $title . $args['after_title'];
		
				// This is where you run the code and display the output
				// Get the latest fixtures that are coming
				$breaktabletext = getAllBreaksTable(10,1,1,3,$GLOBALS["divisionid"]); //getWeeklyResultsbyDiv($GLOBALS["seasonid"],$GLOBALS["divisionid"],-7,0); //seasonid, compid, range (last 8 days), type 0 =table
				echo __( '<div id="break_text">'.$breaktabletext.'</div>', 'breakbydiv_widget_domain' );
				
				echo $args['after_widget'];
			}
					
			// Widget Backend 
			public function form( $instance ) {
				if ( isset( $instance[ 'title' ] ) ) {
						$title = $instance[ 'title' ];
					} else {
						$title = __( 'Latest Breaks', 'breakbydiv_widget_domain' );
				}
				
				// Widget admin form
				?>
				<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
				</p>
				<?php 
			}
				
			// Updating widget replacing old instances with new
			public function update( $new_instance, $old_instance ) {
				$instance = array();
				$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
				return $instance;
			}
			
		} // Class break-widget ends here --------------------------------------------------------------------------------------------

		// Register and load the widget
		function breakbydiv_load_widget() {
			register_widget( 'breakbydiv_widget' );
		}
		add_action( 'widgets_init', 'breakbydiv_load_widget' );
		// END OF BREAKS WIDGET //////////////////////////////////////////////////////////////////////////

		// TABLES WIDGET FOR A PARTICULAR DIVISION  ///////////////////////////////////////////////////
		class divtables_widget extends WP_Widget {

			function __construct() {
				parent::__construct(
				// Base ID of your widget
				'bdscl_tables_widget', 
		
				// Widget name will appear in UI
				__('BDSCL Tables display widget', 'bdscl_tables_widget_domain'), 
		
				// Widget description
				array( 'description' => __( 'Displays a cut down divisional table', 'bdscl_tables_widget_domain' ), ) 
				);
			}
	
			// Creating widget front-end
			// This is where the action happens
			public function widget( $args, $instance ) {
				// Options for the widget
				$title = apply_filters( 'widget_title', $instance['title'] );
				$season = $instance['season'];
				$division = $instance['division'];
				$rowstoshow = $instance['rowstoshow'];
				
				// Before widget arguments are defined by themes
				echo $args['before_widget'];
				if ( ! empty( $title ) )
				echo $args['before_title'] . $title . $args['after_title'];

				// This is where you run the code and display the output
				$tabtext = getDivTable($season, $division, $rowstoshow, 1, 1,0);
				echo __( '<div class="mtab-divtable">'.$tabtext.'</div>', 'divtables_widget_domain' );
				
				// After widget arguments are defined by themes
				echo $args['after_widget'];
			}
					
			// Widget Backend 
			public function form( $instance ) {
				// Check if options exist, if its null, put default or new option in place for use in the form
				// Title
				if ( isset( $instance[ 'title' ] ) ) {
						$title = $instance[ 'title' ];
					} else {
						$title = __( 'Tables', 'divtables_widget_domain' );
				}
				// Season
				if ( isset( $instance[ 'season' ] ) ) {
						$season = $instance[ 'season' ];
					} else {
						$season = __( '14', 'divtables_widget_domain' );
				}
				// Division
				if ( isset( $instance[ 'division' ] ) ) {
						$division = $instance[ 'division' ];
					} else {
						$division = __( '40', 'divtables_widget_domain' );
				}

				// Rows to display
				if ( isset( $instance[ 'rowstoshow' ] ) ) {
						$rowstoshow = $instance[ 'rowstoshow' ];
					} else {
						$rowstoshow = __( '99', 'divtables_widget_domain' );
				}
				// Widget admin form
				?>
				<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
				</p>
				
				<p>
				<label for="<?php echo $this->get_field_id( 'season' ); ?>"><?php _e( 'Season Number from database:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'season' ); ?>" name="<?php echo $this->get_field_name( 'season' ); ?>" type="text" value="<?php echo esc_attr( $season ); ?>" />
				</p>
				
				<p>
				<label for="<?php echo $this->get_field_id( 'division' ); ?>"><?php _e( 'Division Number from database:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'division' ); ?>" name="<?php echo $this->get_field_name( 'division' ); ?>" type="text" value="<?php echo esc_attr( $division ); ?>" />
				</p>

				<p>
				<label for="<?php echo $this->get_field_id( 'rowstoshow' ); ?>"><?php _e( 'Nr of rows to display:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'rowstoshow' ); ?>" name="<?php echo $this->get_field_name( 'rowstoshow' ); ?>" type="text" value="<?php echo esc_attr( $rowstoshow ); ?>" />
				</p>

				<?php 
			}
				
			// Updating widget replacing old instances with new
			public function update( $new_instance, $old_instance ) {
				$instance = array();
				$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
				$instance['season'] = ( ! empty( $new_instance['season'] ) ) ? strip_tags( $new_instance['season'] ) : '';
				$instance['division'] = ( ! empty( $new_instance['division'] ) ) ? strip_tags( $new_instance['division'] ) : '';
				$instance['rowstoshow'] = ( ! empty( $new_instance['rowstoshow'] ) ) ? strip_tags( $new_instance['rowstoshow'] ) : '';
				return $instance;
			}
			
		} // Class bdscl_tables_widget ends here

		// Register and load the widget
		function divtables_load_widget() {
			register_widget( 'divtables_widget' );
		}
		add_action( 'widgets_init', 'divtables_load_widget' );
		// END OF TABLES WIDGET //////////////////////////////////////////////////////////////////////////
	//----------------------------------------------------------------------------------------------------------	

		// FUNCTION TO SHOW ANY TABLE
		add_shortcode('display_anytable_sc', 'display_anytable');
		function display_anytable($atts) {
    		// get attributes - argument is passed in WordPress page tname="tablename", otherwise default is None
    		extract(shortcode_atts(array(
    				'tname' => 'None',
    				),$atts));
    
    		global $rootlocal;
    		global $wpdb;
    		$location = $_SERVER['DOCUMENT_ROOT'];
    				
    		include ($location . $rootlocal . '/wp-config.php');
    		include ($location . $rootlocal . '/wp-load.php');
    		include ($location . $rootlocal . '/wp-includes/pluggable.php');
           	
            //Get the comps for that season ID and create a tab for each
            echo '<div id="wrapper">';
            
            $qrytable = $wpdb->get_results("SELECT * FROM " . $tname . "", ARRAY_A);
           	$table_name = $tname;
           	$collist = array();
    
           	// Set the table header up by looping around the selected table columns
           	echo '<table class="fulltable"><thead><tr>';	
           	foreach ( $wpdb->get_col( "DESC " . $table_name, 0 ) as $column_name ) {
        		echo '<th>' . $column_name . '</th>';
        		// Store the column names in an array for later use
        		$collist[]= $column_name;
    		}
			// Then we add on columns for Edit and Delete
			echo '<th></th>';
			echo '<th></th>';
    		echo '</thead><tbody>'; 
    		
			// This is useful as collecting an array with numerically indexed array means you can produce a full table like this
			/*$rows = $wpdb->get_results("SELECT * from ". $tname ." ; ",ARRAY_N);
			for($i=0;$i<count($rows);$i++) {
		            echo "<tr>";
		            for($j=0;$j<count($rows[$i]);$j++) {
		                echo "<td>".$rows[$i][$j]."</td>";
		         	}           
            		echo "</tr>";
       		}*/
			
			// But you can also do an associative array and print out the table with the actual column headers collected previously.
    		foreach ($qrytable as $row) {
    			$count = 0;	
				echo '<tr>';
    	 		while ($count < count($collist)) {
    	 			$test = $collist[$count];
    	 	    	echo '<td id="' . $collist[$count] . '|' . $row[$collist[$count]] .'">' . $row[$collist[$count]] . '</td>';
    	 	    	$count = $count+1;
    	    	}
				//Then we need to add in columns and images for edit and delete
				echo '<td><input name="edit" id="edit" type="button" class="button" onClick="edit_select(\'' . $collist[0]. ',' .$row[$collist[0]] . ',' . $tname . '\')" value="Edit"></td>';
				echo '<td><input name="delete" id="delete" type="button" class="button" onClick="delete_select(\'' . $collist[0]. ',' .$row[$collist[0]] . ',' . $tname . '\')" value="Delete"></td>';
    	    	echo '</tr>';
				
    		}
    		echo '</tbody>'; 
    		echo '</table>';

    		//Close the wrapper div
           	echo '</div>';
	   	}
        //----------------------------------------------------------------------------------------------------------
	   
	    // *** THIS IS ALL FOR UPDATING THE RESULTS OF FIXTURES..... *********************************
	    // RETURN AJAX FUNCTION THAT SHOWS THE AJAX STUFF IS WORKING ON THE BDSCL_FIXTURES_DISPLAY (RESULTS) screen
        function ajax_return_function(){
            global $rootlocal;
            global $wpdb;
            $location = $_SERVER['DOCUMENT_ROOT'];
                
            include ($location . $rootlocal . '/wp-config.php');
            include ($location . $rootlocal . '/wp-load.php');
            include ($location . $rootlocal . '/wp-includes/pluggable.php');
        
        
            // Return changes
             $seasonid = $_POST['season-list'];
             $competitionid = $_POST['competition-list'];
             $weekid = $_POST['week-list'];
             $fixtureid = $_POST['fixture-list'];
             $save = $_POST['savehidden'];
             
             // So if the Save button has been clicked then do the save routine
             // If not then do the selection updates
             if ($save == 'Saving') {
                // Lets get the other POST data for the saving process
                $hplayer1 = $_POST['homeplayer1-list'];
                $hscore1 = $_POST['htscore1'];
                $hbreak1 = $_POST['htbreak1'];
                $aplayer1 = $_POST['awayplayer1-list'];
                $ascore1 = $_POST['atscore1'];
                $abreak1 = $_POST['atbreak1'];
               
                $hplayer2 = $_POST['homeplayer2-list'];
                $hscore2 = $_POST['htscore2'];
                $hbreak2 = $_POST['htbreak2'];
                $aplayer2 = $_POST['awayplayer2-list'];
                $ascore2 = $_POST['atscore2'];
                $abreak2 = $_POST['atbreak2'];
                
                $hplayer3 = $_POST['homeplayer3-list'];
                $hscore3 = $_POST['htscore3'];
                $hbreak3 = $_POST['htbreak3'];
                $aplayer3 = $_POST['awayplayer3-list'];
                $ascore3 = $_POST['atscore3'];
                $abreak3 = $_POST['atbreak3'];
                
                $hplayer4 = $_POST['homeplayer4-list'];
                $hscore4 = $_POST['htscore4'];
                $hbreak4 = $_POST['htbreak4'];
                $aplayer4 = $_POST['awayplayer4-list'];
                $ascore4 = $_POST['atscore4'];
                $abreak4 = $_POST['atbreak4'];  
                 
                // First lets validate the data on the screen
                
                // The season, comp, week and fixture first -------------------------------------
                if ($seasonid === '') {
                    $errors['Season'] = 'Season selection is required';
                }
                if ($competitionid === '') {
                    $errors['Competition'] = 'Competition selection is required';
                }
                if ($weekid === '') {
                    $errors['Week'] = 'Week selection is required';
                }
                if ($fixtureid === '') {
                    $errors['Fixture'] = 'Fixture selection is required';
                }
                // End season, comp, week and fixture validation---------------------------------
                
                // Check for duplication in the PLAYER selections first -------------------------
                $hparray = array($hplayer1,$hplayer2,$hplayer3,$hplayer4);
                // Need to remove any (absent) - key 1 - players that can be duplicate
                foreach (array_keys($hparray, '1') as $key) {
				    unset($hparray[$key]);
				}               
                
                if (count(array_unique($hparray)) < count($hparray)){
                    //Duplicates....
                    $errors['HomePlayers'] = 'Duplicate home players found';
                }
                
                $aparray = array($aplayer1,$aplayer2,$aplayer3,$aplayer4);
                // Need to remove any (absent) - key 0 - players that can be duplicate
                foreach (array_keys($aparray, '1') as $key) {
				    unset($aparray[$key]);
				}               
                
                if (count(array_unique($aparray)) < count($aparray)){
                    //Duplicates
                    $errors['AwayPlayers'] = 'Duplicate away players found';
                }
                // End PLAYER data validation --------------------------------------------------
        
                // Validate MATCH FRAMES scores add up to 2 ---------------------------------------
                // Need to convert these to values first
               
                if (! (int)$hscore1 + (int)$ascore1 === 2){
                    $errors['ScoreMatch1'] = 'Scores for match 1 are incorrect';
                }
                if (! (int)$hscore2 + (int)$ascore2 === 2){
                    $errors['ScoreMatch2'] = 'Scores for match 2 are incorrect';
                }
                if (! (int)$hscore3 + (int)$ascore3 === 2){
                    $errors['ScoreMatch3'] = 'Scores for match 3 are incorrect';
                }
                if (! (int)$hscore4 + (int)$ascore4 === 2){
                    $errors['ScoreMatch4'] = 'Scores for match 4 are incorrect';
                }
                // End of MATCH FRAMES scores validation------------------------------------------
                
                // Finally lets validate the BREAKS ---------------------------------------------
                // Home breaks first... loop around 4 times and evaluate each break
                // Use 2 loops: $j loops twice for home and away, $i loops 4 times for each match in a fixture
                for ($j=1;$j<3;$j++){
                  if ($j==1){
                      $prefix = 'h';
                      $fullfix = 'Home';
                  } else {
                      $prefix = 'a';
                      $fullfix = 'Away';
                  }
                  for ($i=1; $i<5; $i++) {
                    if (strlen(${$prefix . 'break' . $i}) > 0) {
                        // So something there but are there multiple breaks? Check by counting semi-colons
                        if (substr_count(${$prefix . 'break' . $i}, ';') > 0) {
                           // Multiple semi colons, therefore breaks, so lets parse up
                            ${$prefix . 'breakarr' . $i} = preg_split ('/;/', ${$prefix . 'break' . $i});
                            // Now lets loop over the each break array item and make sure they're a number and in the range
                            foreach(${$prefix . 'breakarr' . $i} as $val) {
                               if(is_numeric($val)) {
                                 // Its a number but in what range....
                                 if ($val < 1 Or $val > 147 ) {
                                     // Can't be a valid break so error out
                                     $errors[$fullfix . 'Breaks' . $i] = 'Break for ' . $fullfix . ' player ' . $i . ' is not valid';
                                 }
                                 }  else {
                                   //Not a number so lets error out
                                   $errors[$fullfix . 'Breaks' . $i] = 'Break for ' . $fullfix . ' player ' . $i . ' is not valid';  
                                 }
                            } // End foreach
                        } else {
                           //So no semi-colons therefore only one break but is it a valid number
                           if(is_numeric(${$prefix . 'break' . $i})) {
                             // Its a number but in what range....
                             if (${$prefix . 'break' . $i} < 1 Or ${$prefix . 'break' . $i} > 147 ) {
                                 // Can't be a valid break so error out
                                 $errors[$fullfix . 'Breaks' . $i] = 'Break for ' . $fullfix . ' player ' . $i . ' is not valid';
                             }
                           }  else {
                             //Not a number so lets error out
                             $errors[$fullfix . 'Breaks' . $i] = 'Break for ' . $fullfix . ' player ' . $i . ' is not valid';  
                           }
                        }
                      } // End of check if there's anything in the break cell
                    
                  } //End of $i for loop
                } // End if $j for loop 
                // End of BREAK Validation ------------------------------------------------------
                
                // No errors then in the data so we can go ahead and save the data to the database
                if (empty($errors))
                {
                    // Is it an UPDATE or an INSERT record 
                    // So let's check fixtureresultdetails table to see if the fixture has already been added
                    // $qryfix will be 0 if not added and 4 if it has
                    $numfix = $wpdb->get_var($wpdb->prepare("SELECT Count(*) FROM fixtureresultdetails WHERE FixtureId = %d",$fixtureid));
                    
                    if ($numfix == 0){
                        // INSERT queries required, First the actual fixtureresult and then the breaks
                        // Loop through the 4 Matches of the fixture....
                        // We have to update 3 tables in order.. fixtureresults first with the result
                        // Then fixtureresult details with the matches in the fixture and then any breaks into playerbreaks
                        // So fixureresults first.....
                        $wpdb->insert(fixtureresults,
                                    array(
                                        'FixtureId'=>$fixtureid,
                                        'HomeScore'=>$hscore1+$hscore2+$hscore3+$hscore4,
                                        'AwayScore'=>$ascore1+$ascore2+$ascore3+$ascore4
                                    ),
                                    array(
                                        '%d',
                                        '%d',
                                        '%d'
                                    )
                                 ); //end wpdb insert
                                 
                        // And now fixtureresultdetails....
                        for ($i=1; $i<5;$i++){
                            $wpdb->insert(fixtureresultdetails,
                                    array(
                                        'FixtureId'=>$fixtureid,
                                        'Sequence'=> $i,
                                        'HomePlayerId'=>${'hplayer' . $i},
                                        'AwayPlayerId'=>${'aplayer' . $i},
                                        'HomeScore'=>${'hscore' . $i},
                                        'AwayScore'=>${'ascore' . $i}
                                    ),
                                    array(
                                        '%d',
                                        '%d',
                                        '%d',
                                        '%d',
                                        '%d',
                                        '%d'
                                    )
                                 ); //end wpdb insert
                                 
                        } //End for loop
                        
                        // And now the breaks.... it's a bew fixture so no need to tidy up old breaks that have changed
                        // We also need the last BreakId so we can add to it as its not an incremental Id
                        $breakidnr = $wpdb->get_var($wpdb->prepare("SELECT BreakId FROM playerbreaks ORDER BY BreakId DESC LIMIT 1",""));
                        $breakidnr = $breakidnr+1;
                         
                        for ($j=1;$j<3;$j++){
                          if ($j==1){
                              $prefix = 'h';
                          } else {
                              $prefix = 'a';
                          }
                          for ($i=1; $i<5; $i++) {
                            if (strlen(${$prefix . 'break' . $i}) > 0) {
                                // So something there but are there multiple breaks? Check by counting semi-colons
                                if (substr_count(${$prefix . 'break' . $i}, ';') > 0) {
                                   // Multiple semi colons, therefore breaks, so lets parse up
                                    ${$prefix . 'breakarr' . $i} = preg_split ('/;/', ${$prefix . 'break' . $i});
                                    // Now lets loop over the each break array item and save
                                    foreach(${$prefix . 'breakarr' . $i} as $val) {
                                          $wpdb->insert(playerbreaks,
                                                        array(
                                                            'BreakId'=>$breakidnr,
                                                            'PlayerId'=>${$prefix . 'player' . $i},
                                                            'BreakScore'=>$val,
                                                            'FixtureId'=>$fixtureid,
                                                              ),
                                                        array(
                                                            '%d',
                                                            '%d',
                                                            '%d',
                                                            '%d'
                                                              )
                                                        ); //end wpdb insert
                                           //Increment the break no. 
                                           $breakidnr = $breakidnr+1;             
                                    } // End foreach
                                    
                                } else {
                                   //So no semi-colons therefore only one break
                                   $wpdb->insert(playerbreaks,
                                                        array(
                                                            'BreakId'=>$breakidnr,
                                                            'PlayerId'=>${$prefix . 'player' . $i},
                                                            'BreakScore'=>${$prefix . 'break' . $i},
                                                            'FixtureId'=>$fixtureid
                                                              ),
                                                        array(
                                                            '%d',
                                                            '%d',
                                                            '%d',
                                                            '%d'
                                                              )
                                                        ); //end wpdb insert 
                                    $breakidnr = $breakidnr+1; 
                                }
                              } // End of check if there's anything in the break cell
                            
                          } //End of $i for loop
                        } // End if $j for loop 
                    } // end of numfix=0
                    
                    if ($numfix == 4) {
                        // UPDATE queries required, First the actual fixtureresult, fixtureresultdetails and then the breaks
                        // The fixtureresult may need updating so lets do it
                         $wpdb->update(fixtureresults,
                                    array(
                                        'FixtureId'=>$fixtureid,
                                        'HomeScore'=>$hscore1+$hscore2+$hscore3+$hscore4,
                                        'AwayScore'=>$ascore1+$ascore2+$ascore3+$ascore4
                                    ),
                                    array (
                                         'FixtureId'=>$fixtureid,
                                            ),
                                    array(
                                        '%d',
                                        '%d',
                                        '%d'
                                    ),
                                    array (
                                        '%d'
                                    )
                                 ); //end wpdb insert
                                        
                        // Now fixtureresultdetails - the matches in the fixture
                        for ($i=1; $i<5;$i++){
                            $wpdb->update(fixtureresultdetails,
                                            array(
                                                'HomePlayerId'=>${'hplayer' . $i},
                                                'AwayPlayerId'=>${'aplayer' . $i},
                                                'HomeScore'=>${'hscore' . $i},
                                                'AwayScore'=>${'ascore' . $i}
                                            ),
                                            array (
                                                'FixtureId'=>$fixtureid,
                                                'Sequence'=>$i
                                            ),
                                            array(
                                                '%d',
                                                '%d',
                                                '%d',
                                                '%d'
                                            ),
                                            array (
                                                '%d',
                                                '%d'
                                            )
                                         ); //end wpdb insert
                        } //End for loop 
                        
                        // And finally any breaks...
                        // Delete all the current breaks and add new in an insert because sifting the breaks is a pain
                        // So delete all the existing breaks...if any...
                        $wpdb->delete(playerbreaks,
                                        array(
                                            'FixtureId'=>$fixtureid
                                        ),
                                        array (
                                            '%d'
                                        )
                                     ); //end wpdb insert
                        // Now reapply the new breaks as INSERT             
                        // We also need the last BreakId so we can add to it as its not an incremental Id
                        $breakidnr = $wpdb->get_var($wpdb->prepare("SELECT BreakId FROM playerbreaks ORDER BY BreakId DESC LIMIT 1",""));
                        $breakidnr = $breakidnr+1;
                         
                        for ($j=1;$j<3;$j++){
                          if ($j==1){
                              $prefix = 'h';
                          } else {
                              $prefix = 'a';
                          }
                          for ($i=1; $i<5; $i++) {
                            if (strlen(${$prefix . 'break' . $i}) > 0) {
                                // So something there but are there multiple breaks? Check by counting semi-colons
                                if (substr_count(${$prefix . 'break' . $i}, ';') > 0) {
                                   // Multiple semi colons, therefore breaks, so lets parse up
                                    ${$prefix . 'breakarr' . $i} = preg_split ('/;/', ${$prefix . 'break' . $i});
                                    // Now lets loop over the each break array item and save
                                    foreach(${$prefix . 'breakarr' . $i} as $val) {
                                          $wpdb->insert(playerbreaks,
                                                        array(
                                                            'BreakId'=>$breakidnr,
                                                            'PlayerId'=>${$prefix . 'player' . $i},
                                                            'BreakScore'=>$val,
                                                            'FixtureId'=>$fixtureid,
                                                              ),
                                                        array(
                                                            '%d',
                                                            '%d',
                                                            '%d',
                                                            '%d'
                                                              )
                                                        ); //end wpdb insert
                                           //Increment the break no. 
                                           $breakidnr = $breakidnr+1;             
                                    } // End foreach
                                    
                                } else {
                                   //So no semi-colons therefore only one break
                                   $wpdb->insert(playerbreaks,
                                                        array(
                                                            'BreakId'=>$breakidnr,
                                                            'PlayerId'=>${$prefix . 'player' . $i},
                                                            'BreakScore'=>${$prefix . 'break' . $i},
                                                            'FixtureId'=>$fixtureid
                                                              ),
                                                        array(
                                                            '%d',
                                                            '%d',
                                                            '%d',
                                                            '%d'
                                                              )
                                                        ); //end wpdb insert 
                                    $breakidnr = $breakidnr+1; 
                                }
                              } // End of check if there's anything in the break cell
                            
                          } //End of $i for loop
                        } // End if $j for loop 
                                     
                    } // if num==4
                    
                    // Send results to ajax return
                    $success = 'Result has been saved successfully';
                    $returntext = array("SaveSuccess"=>$success,
                                        );
                    echo json_encode($returntext);   
                    die();// wordpress may print out a spurious zero without this - can be particularly bad if using json
                } else {
                    // Errors found
                    $success = 'Fixture has not saved - see below';
                    $returntext = array("SaveSuccess"=>$success,
                                        "Errors"=>$errors
                                       );
                    echo json_encode($returntext); 
                    die();// wordpress may print out a spurious zero without this - can be particularly bad if using json
                }
                                 
             } else {
                
                 $complist = getCompSelect($seasonid,0);
                 $weeklist = getWeekSelect($competitionid);
                 $fixlist = getFixSelect($seasonid,$competitionid,$weekid);
                 $hplayerlist = getPlaySelect($fixtureid,'Home');
                 $aplayerlist = getPlaySelect($fixtureid,'Away');
                 $fixdetlist = getFixDetails($fixtureid);
                 
                 //$returntext = '[Competition]' . $complist . '[Week]' . $weeklist . '[Fixture]' . $fixlist .'[End]';
                
                 $returntext = array("Competition"=>$complist,
                                     "Week"=>$weeklist,
                                     "Fixture"=>$fixlist,
                                     "HPlayer"=>$hplayerlist,
                                     "APlayer"=>$aplayerlist,
                                     "FixDetails"=>$fixdetlist,
                                     "Save"=>$save
                                    );
                 
                 echo json_encode($returntext); 
                 
                  
                 die();// wordpress may print out a spurious zero without this - can be particularly bad if using json
               }
             }
        
		// THIS IS THE BIT THAT DISPLAYS THE DATA FOR THE RESULTS PAGE
        add_shortcode("bdscl_fixtures_display", "bdscl_fixtures_display");
        function bdscl_fixtures_display(){
             global $rootlocal;
             global $wpdb;
             $location = $_SERVER['DOCUMENT_ROOT'];
                    
             include ($location . $rootlocal . '/wp-config.php');
             include ($location . $rootlocal . '/wp-load.php');
             include ($location . $rootlocal . '/wp-includes/pluggable.php');   
             $instructions = "<br>Select season, div, week and fixture. When entering breaks separate with a semi-colon (;).</div>";
			 echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
             $the_form = '
             <form id="theForm">';
             
             // GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
             $qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons ORDER BY SeasonId DESC");
                 
             $the_form = $the_form . '      
             <div id="wrapper">
             <div class="frmDropDown">
             <div class="row">
             <label>Season:</label>
             <select name="season-list" id="season-list" onchange="fixture_select(\'competition\')">
             <option value="">Select Season</option>';
            
             foreach($qrySeason as $season) {
                            $the_form = $the_form . '   
                            <option value="' . $season->SeasonId . '">' . $season->SeasonDesc .'</option>';
             }
            
             //NOW PUT THE COMPETITION DROPDOWN IN
             $the_form = $the_form . '</select><br>
                            <label>Competition:</label>
                            <select name="competition-list" id="competition-list" class="competition-list" onchange="fixture_select(\'week\')">
                            <option value="">Select Competition</option>';
                            
             $the_form = $the_form . '' . '</select>';
             
             //NOW PUT THE WEEK DROPDOWN IN
             $the_form = $the_form . '</select><br>
                            <label>Week:</label>
                            <select name="week-list" id="week-list" class="week-list" onchange="fixture_select(\'fixture\')">
                            <option value="">Select Week</option>';
                            
             //NOW PUT THE FIXTURES DROPDOWN IN
             $the_form = $the_form . '</select><br>
                            <label>Fixture:</label>
                            <select name="fixture-list" id="fixture-list" class="fixture-list" onchange="fixture_select(\'player\')">
                            <option value="">Select Fixture</option>';
            
             $the_form = $the_form . '</select>';
             
             $the_form= $the_form . '<br><br>';
             
             //NOW FOR THE FIXTURE DETAILS TO GO IN
             // Header for the details
              $the_form=$the_form . '<table class="ftab-results"><thead>
              						 <tr>
              						 <th id="hts1">Order</td>
              						 <th id="homeplayer-list">Home</td>
              						 <th id="htscore">Frames</td>
              						 <th id="htbreak">Breaks</td>
              						 <th id="awayplayer-list">Away</td>
              						 <th id="atscore">Frames</td>
              						 <th id="atbreak">Breaks</td>
              						 </tr>
              						 </thead>
              						 <tbody>';
			  
             $the_form= $the_form . '
			 <tr>
             <td><input name="hts1" type="number" value="1" id="hts1" ></td>
             <td><select name="homeplayer1-list" id="homeplayer1-list">
                    <option value="- Select Player -">- Select Player -</option>
             </select></td>
             <td><input name="htscore1" type="number" value="" id="htscore1" onchange="upd_frames(this)"></td>
             <td class="atab-breaks"><input name="htbreak1" type="text" value="" id="htbreak1" class="atab-breaks"></td>
             <td><select name="awayplayer1-list" id="awayplayer1-list">
                    <option value="- Select Player -">- Select Player -</option>
             </select></td>
             <td><input name="atscore1" type="number" value="" id="atscore1" onchange="upd_frames(this)"></td>
             <td><input name="atbreak1" type="text" value="" id="atbreak1" ></td>
             </tr>
             <tr> 
             <td><input name="hts2" type="number" value="2" id="hts2" ></td>
             <td><select name="homeplayer2-list" id="homeplayer2-list">
                    <option value="- Select Player -">- Select Player -</option>
             </select></td>
             <td><input name="htscore2" type="number" value="" id="htscore2" onchange="upd_frames(this)"></td>
             <td><input name="htbreak2" type="text" value="" id="htbreak2"></td>
             <td><select name="awayplayer2-list" id="awayplayer2-list">
                    <option value="- Select Player -">- Select Player -</option>
             </select></td>
             <td><input name="atscore2" type="number" value="" id="atscore2" onchange="upd_frames(this)"></td>
             <td><input name="atbreak2" type="text" value="" id="atbreak2" ></td>
             </tr>
             <tr>
             <td><input name="hts3" type="number" value="3" id="hts3" ></td>
             <td><select name="homeplayer3-list" id="homeplayer3-list">
                    <option value="- Select Player -">- Select Player -</option>
             </select></td>
             <td><input name="htscore3" type="number" value="" id="htscore3" onchange="upd_frames(this)"></td>
             <td><input name="htbreak3" type="text" value="" id="htbreak3" ></td>
             <td><select name="awayplayer3-list" id="awayplayer3-list">
                    <option value="- Select Player -">- Select Player -</option>
             </select></td>
             <td><input name="atscore3" type="number" value="" id="atscore3" onchange="upd_frames(this)"></td>
             <td><input name="atbreak3" type="text" value="" id="atbreak3" ></td>
             </tr>
             <tr>
             <td><input name="hts4" type="number" value="4" id="hts4"></td>
             <td><select name="homeplayer4-list" id="homeplayer4-list">
                    <option value="- Select Player -">- Select Player -</option>
             </select></td>
             <td><input name="htscore4" type="number" value="" id="htscore4" onchange="upd_frames(this)"></td>
             <td><input name="htbreak4" type="text" value="" id="htbreak4" ></td>
             <td><select name="awayplayer4-list" id="awayplayer4-list">
                    <option value="- Select Player -">- Select Player -</option>
             </select></td>
             <td><input name="atscore4" type="number" value="" id="atscore4" onchange="upd_frames(this)"></td>
             <td><input name="atbreak4" type="text" value="" id="atbreak4" ></td>
             </tr>
             
			 </tbody></table>
             <input name="action" type="hidden" value="the_ajax_hook" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form -->
             <input name="save" id="save" type="button" class="button" onClick="document.getElementById(\'savehidden\').value=\'Saving\';document.getElementById(\'save\').style.backgroundColor=\'#00ff00\';fixture_select(\'save\')" value="Save">
             <input name="savehidden" type="text" value="" id="savehidden" style="width:240px"><br>
             
             </form><br><div name="errortext" type="text" value="" id="errortext" style="width:100%"></div><br>';
             return $the_form;
        }
        // *** END OF UPDATING THE RESULTS OF FIXTURES..... *********************************
        
        // THIS FUNCTION CALLS THE STORED PROCEDURES TO CALCULATE THE TABLES AND AVERAGES
		add_shortcode('stats_sp_call_sc','stats_sp_call');
		function stats_sp_call()	{
			global $rootlocal;
			global $wpdb;
			
			// If the form has been submitted then do the calcs and return a message
			if (isset($_POST['submit'])) {
				  // Get the season id selected
				  $qryComps = $wpdb->get_results($wpdb->prepare("SELECT CompetitionId FROM competitions WHERE SeasonId = %d" , $_POST['season-list']));
				  // Get the comps for that season
				  foreach($qryComps as $comp) {
					// Loop around the comps and call the stored procedure with CompId and SeasonId
					$statsleague = $wpdb->query('call upd_divtables(' . $comp->CompetitionId . ',' . $_POST['season-list'] . ')');
				  	$statsaverages = $wpdb->query('call upd_playeraverages(' . $comp->CompetitionId . ',' . $_POST['season-list'] . ')');
					
				  }
				  // Then call the rankings and handicaps
				  $statsrankings = $wpdb->query('call upd_rankings(' . $_POST['season-list'] . ')');
				  $statshandicap = $wpdb->query('call upd_handicaps(' . $_POST['season-list'] . ')');
				  
				  // If 0 returned then everything ok....
				  if (($statsleague + $statsaverages + $statsrankings + $statshandicap)== 0) {
					  $the_form = $the_form . 'Division, averages and ranking tables calculated successfully.';
					  } else {
					  $the_form = $the_form . 'A problem may have occured please check division, averages and rankings tables.';	
					  }
				echo $the_form;	
			} else {
				// Show the form itself if no submission
				$the_form = 'Choose season and click button to update division tables, player averages and player rankings. This will take a few seconds so please be patient....<br><br>';
				// Fill the season select and show the form
				$qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons ORDER BY SeasonId DESC");
	            $the_form = $the_form . '<form id="theCalcForm" method="post" action="">';
	            $the_form = $the_form . '      
	            <div id="wrapper">
	            <label>Season:</label>
	            <select autofocus name="season-list" id="season-list" onchange="division_select(\'season\')">
	            <option value="">Select Season</option>';
	            
	            foreach($qrySeason as $season) {
	            				// Check the global season so we can set it to selected
							$selected = '';
							if ($GLOBALS["seasonid"] === $season->SeasonId){
								$selected = 'selected = "selected" ';
							}
	                           $the_form = $the_form . '   
	                           <option ' . $selected . ' value="' . $season->SeasonId . '">' . $season->SeasonDesc .'</option>';
	            }
					$the_form = $the_form . '</select>
										<input type="submit" name="submit" value ="Update all">
				   	  					</div></form><br><br>';
					echo $the_form;
			} // end else
		}
        
        // THIS IS A FUNCTION THAT GETS A LIST OF COMPETITIONS BASED ON THE SEASON SELECTED ----------------------------------------------------        
        function getCompSelect($seasonid, $selType){
	         // $seasonid = Passes in the season from which to get the competitinos
	         // $selType = 1 or 0 - 1 means that the Global division id will be auto selected in the list for pre-loaded pages with the first division. 0 = nothing selected
	         global $rootlocal;
	         global $wpdb;
	         $location = $_SERVER['DOCUMENT_ROOT'];
	                
	         include ($location . $rootlocal . '/wp-config.php');
	         include ($location . $rootlocal . '/wp-load.php');
	         include ($location . $rootlocal . '/wp-includes/pluggable.php');
	         
	         $qrycomp = $wpdb->get_results($wpdb->prepare("SELECT CompetitionId, CompetitionName FROM competitions WHERE SeasonId = %d",$seasonid));
	         
	         $comp_select = '<option value="">Select Division</option>';
	         foreach($qrycomp as $competition) {
	         					// Check the global division so we can set it to selected
	         					$selected = '';
								if ($selType == 1){
									if ($GLOBALS["divisionid"] === $competition->CompetitionId){
										$selected = 'selected = "selected" ';
									}
								}
								
	                        $comp_select = $comp_select . ' 
	                        <option ' . $selected . ' value="' . $competition->CompetitionId . '">' . $competition->CompetitionName .'</option>';
	         }
	         return $comp_select;
         }
         
		 // THIS IS A FUNCTION THAT GETS A LIST OF TEAMS BASED ON THE SEASON SELECTED ----------------------------------------------------
        function getTeamSelect($seasonid){
	         global $rootlocal;
	         global $wpdb;
	         $location = $_SERVER['DOCUMENT_ROOT'];
	                
	         include ($location . $rootlocal . '/wp-config.php');
	         include ($location . $rootlocal . '/wp-load.php');
	         include ($location . $rootlocal . '/wp-includes/pluggable.php');
	         
	         $qryteams = $wpdb->get_results($wpdb->prepare("Select TeamId, TeamName FROM teams WHERE SeasonId = %d",$seasonid));
	         
	         $team_select = '<option value="">Select Team</option>';
	         foreach($qryteams as $team) {
     				// Check the global team so we can set it to selected
					$selected = '';
					if ($GLOBALS["teamid"] === $team->TeamId){
						$selected = 'selected = "selected" ';
					}
                    $team_select = $team_select . ' 
                    <option ' . $selected . ' value="' . $team->TeamId . '">' . $team->TeamName .'</option>';
	         }
	         return $team_select;
         }
		 
		 // THIS IS A FUNCTION THAT GETS A LIST OF TEAMS BASED ON THE SEASON AND COMPETITION SELECTED ----------------------------------------------------
        function getTeambyDivSelect($seasonid, $competitionid){
	         global $rootlocal;
	         global $wpdb;
	         $location = $_SERVER['DOCUMENT_ROOT'];
	                
	         include ($location . $rootlocal . '/wp-config.php');
	         include ($location . $rootlocal . '/wp-load.php');
	         include ($location . $rootlocal . '/wp-includes/pluggable.php');
	         
	         $qryteams = $wpdb->get_results($wpdb->prepare("SELECT t.TeamId as TId, t.TeamName as Name, d.CompetitionId  FROM teams AS t
															INNER JOIN divisionteams AS d ON d.TeamId = t.TeamId WHERE t.SeasonId = %d AND d.CompetitionId = %d",$seasonid,$competitionid));
	         
	         $team_select = '<option value="">Select Team</option>';
	         foreach($qryteams as $team) {
     				// Check the global team so we can set it to selected
					$selected = '';
					if ($GLOBALS["teamid"] === $team->TId){
						$selected = 'selected = "selected" ';
					}
                    $team_select = $team_select . ' 
                    <option ' . $selected . ' value="' . $team->TId . '">' . $team->Name .'</option>';
	         }
	         return $team_select;
         }
         
		 // THIS IS A FUNCTION THAT GETS A LIST OF CLUBS ----------------------------------------------------
        function getClubSelect(){
	         global $rootlocal;
	         global $wpdb;
	         $location = $_SERVER['DOCUMENT_ROOT'];
	                
	         include ($location . $rootlocal . '/wp-config.php');
	         include ($location . $rootlocal . '/wp-load.php');
	         include ($location . $rootlocal . '/wp-includes/pluggable.php');
	         
	         $qryclub = $wpdb->get_results("SELECT ClubId, ClubName FROM clubs");
	         
	         $club_select = '<option value="">Select Club</option>';
	         foreach($qryclub as $club) {
	         				// Check the global club so we can set it to selected
							$selected = '';
							if ($GLOBALS["clubid"] === $club->ClubId){
								$selected = 'selected = "selected" ';
							}
	                        $club_select = $club_select . ' 
	                        <option ' . $selected . ' value="' . $club->ClubId . '">' . $club->ClubName .'</option>';
	         }
	         return $club_select;
         }
		 
		 // THIS IS A FUNCTION THAT GETS A LIST OF TEAM TOURNAMENTS BASED ON THE SEASON SELECTED ----------------------------------------------------
		function getTournoSelect($seasonid,$type){
			 // $type: Team, Player, All, Entries: is to separate team and player tournaments plus can get All. Entries removes Ken Shipton as people can't enter that tournament
			 
	         global $rootlocal;
	         global $wpdb;
	         $location = $_SERVER['DOCUMENT_ROOT'];
	                
	         include ($location . $rootlocal . '/wp-config.php');
	         include ($location . $rootlocal . '/wp-load.php');
	         include ($location . $rootlocal . '/wp-includes/pluggable.php');
	         
			 // We set Format to 2 for team tournaments and 1 2 3 for player tournaments
			 
			 if ($type === "Team"){
			 	$qrytourno = $wpdb->get_results($wpdb->prepare("SELECT * FROM tournament WHERE ((SeasonId = %d AND Format = %d))",$seasonid,2));
			 } 
	         if ($type === "Player"){
			 	$qrytourno = $wpdb->get_results($wpdb->prepare("SELECT * FROM tournament WHERE ((SeasonId = %d AND (Format <> %d)))",$seasonid,2));
			 } 
			 
			 if ($type === "All"){
			 	$qrytourno = $wpdb->get_results($wpdb->prepare("SELECT * FROM tournament WHERE SeasonId = %d",$seasonid));
			 } 
			 
			 if ($type === "Entries"){
			 	$qrytourno = $wpdb->get_results($wpdb->prepare("SELECT * FROM tournament WHERE SeasonId = %d AND Format <> %d AND Name NOT LIKE '%Shipton%'",$seasonid,2));
			 }
			 
	         $tourno_select = '<option value="">Select Tournament</option>';
	         foreach($qrytourno as $tourno) {	
	         	// Check the global team/player so we can set it to selected
				$selected = '';
				if ($type === "Team"){
					if ($GLOBALS["teamtournoid"] === $tourno->TournamentId){
						$selected = 'selected = "selected" ';
					}
				}
				if ($type === "Player"){
					if ($GLOBALS["playertournoid"] === $tourno->TournamentId){
						$selected = 'selected = "selected" ';
					}
				}
				
				$tourno_select = $tourno_select . '   
	            <option ' . $selected . ' value="' . $tourno->TournamentId . '">' . $tourno->Name .'</option>';
	         }
	         return $tourno_select;
         }

         // THIS IS A FUNCTION THAT GETS A LIST OF FIXTURES BASED ON THE COMPETITION SELECTED -----------------------------------------------------------
        function getFixSelect($seasonid, $competitionid, $weekid){
	         global $rootlocal;
	         global $wpdb;
	         $location = $_SERVER['DOCUMENT_ROOT'];
	                
	         include ($location . $rootlocal . '/wp-config.php');
	         include ($location . $rootlocal . '/wp-load.php');
	         include ($location . $rootlocal . '/wp-includes/pluggable.php');
	         // CONCAT(TeamName(HomeTeamId),' v ',TeamName(AwayTeamId) )  CONCAT(HomeTeamId,' v ',AwayTeamId)
	         $qryfix = $wpdb->get_results($wpdb->prepare("SELECT FixtureId, SeasonId, Weekno, PlayDate, 
	                                        CONCAT(TeamName(HomeTeamId),' v ',TeamName(AwayTeamId))
	                                        as FixtureDesc, CompetitionId, VenueClubId, HomeTeamId, AwayTeamId FROM fixtures 
	                                        WHERE ((SeasonId = %d) AND (CompetitionId = %d) 
	                                        AND (WeekNo = %d))",$seasonid,$competitionid,$weekid));
	         
	         $fix_select = '<option value="">Select Fixture</option>';
	         foreach($qryfix as $fixture) {
	                        $fix_select = $fix_select . '   
	                        <option value="' . $fixture->FixtureId . '">' . $fixture->FixtureDesc .'</option>';
	         }
	         return $fix_select;
	    }
	         
	         // THIS IS A FUNCTION THAT GETS A LIST OF WEEKS BASED ON THE COMPETITION SELECTED --------------------------------------------------------------
	    function getWeekSelect($competitionid){
	        global $rootlocal;
	        global $wpdb;
	        $location = $_SERVER['DOCUMENT_ROOT'];
	               
	        include ($location . $rootlocal . '/wp-config.php');
	        include ($location . $rootlocal . '/wp-load.php');
	        include ($location . $rootlocal . '/wp-includes/pluggable.php');
	        
	        $qryweek = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT WeekNo, PlayDate FROM fixtures WHERE CompetitionId = %d",$competitionid));
	        
	        $week_select = '<option value="">Select Week</option>';
	        foreach($qryweek as $week) {
	                       $week_select = $week_select . ' 
	                       <option value="' . $week->WeekNo . '">' . date('d M y',strtotime($week->PlayDate)) .'</option>';
	        }
	        return $week_select;
        }
         
         // THIS IS A FUNCTION THAT GETS A LIST OF PLAYERS BASED ON THE FIXTURE AND HOME OR AWAY TEAM IDS ---------------------------------------------
        function getPlaySelect($fixtureid,$homeoraway){
	         global $rootlocal;
	         global $wpdb;
	         $location = $_SERVER['DOCUMENT_ROOT'];
	                
	         include ($location . $rootlocal . '/wp-config.php');
	         include ($location . $rootlocal . '/wp-load.php');
	         include ($location . $rootlocal . '/wp-includes/pluggable.php');
	          
	         if ($homeoraway == 'Home') {
	             //$qryTeamId = $wpdb->get_results($wpdb->prepare("SELECT HomeTeamId FROM fixtures WHERE FixtureId = %d",$fixtureid));
	             $qryTeamId = $wpdb->get_var($wpdb->prepare("SELECT HomeTeamId FROM fixtures WHERE FixtureId = %d",$fixtureid));
	         } else {
	             //$qryTeamId = $wpdb->get_results($wpdb->prepare("SELECT AwayTeamId FROM fixtures WHERE FixtureId = %d",$fixtureid));
	             $qryTeamId = $wpdb->get_var($wpdb->prepare("SELECT AwayTeamId FROM fixtures WHERE FixtureId = %d",$fixtureid));
	         }
	         $team_select = $qryTeamId;
	          
			 $qryplayer = $wpdb->get_results($wpdb->prepare("SELECT PlayerId, uniqueplayers.UPlayerId, CONCAT(ForeName,' ', Surname) AS PlayerName FROM players 
															 INNER JOIN uniqueplayers ON players.UPlayerId = uniqueplayers.UPlayerId
															 WHERE (players.TeamId = %d)",$team_select));
			 
	         foreach($qryplayer as $player) {
	                        $player_select = $player_select . ' 
	                        <option value="' . $player->PlayerId . '">' . $player->PlayerName .'</option>';
	         }
			 // Now add in the absent player
			  $player_select = $player_select . '<option value="1">(absent)</option>';
	         return $player_select;
         }
        
           // THIS IS A FUNCTION THAT GETS A LIST OF PLAYERS BASED ON THE FIXTURE AND HOME OR AWAY TEAM IDS ---------------------------------------------
        function getFixDetails($fixtureid){
	         global $rootlocal;
	         global $wpdb;
	         $location = $_SERVER['DOCUMENT_ROOT'];
	                
	         include ($location . $rootlocal . '/wp-config.php');
	         include ($location . $rootlocal . '/wp-load.php');
	         include ($location . $rootlocal . '/wp-includes/pluggable.php');
	         
	         // First we need to check whether the fixtureid has been saved as a fixturedetail before, i.e.
	         // has it been saved before and this is an edit, or is this the first time save
	         
	         $qryFixDetail = $wpdb->get_var($wpdb->prepare("SELECT FixtureId FROM fixtureresultdetails WHERE FixtureId = %d",$fixtureid));
	         
	         if (is_null($qryFixDetail)){
	            // if null means that this fixture has not been entered before so there's nothing to do
	         } else {
	            // this means the fixture has been saved before therefore we should get that data and put it into relevant cells
	            // So get the data from the database
	            $qryFixDetail = $wpdb->get_results($wpdb->prepare("SELECT * FROM fixtureresultdetails WHERE FixtureId = %d",$fixtureid));
	            // Now lets rip through each of the 4 matches in a fixture and harvest the data
	            foreach($qryFixDetail as $fixdetail) {
	                // Now let's get any breaks that may have been stored. Home player then away player...
	                $hbreaktext = '';
	                $abreaktext = '';
	                $qryhomebrkDetail = $wpdb->get_results($wpdb->prepare("SELECT PlayerId, BreakScore FROM playerbreaks WHERE FixtureId = %d AND PlayerId = %d",$fixtureid,$fixdetail->HomePlayerId));
	                $count = 1;
	                foreach($qryhomebrkDetail as $hbreak) {
	                    if ($count>1){
	                        $hbreaktext = $hbreaktext . ';';
	                    }
	                    $hbreaktext = $hbreaktext .  $hbreak->BreakScore;
	                    $count = $count+1;
	                }
	                $qryawaybrkDetail = $wpdb->get_results($wpdb->prepare("SELECT PlayerId, BreakScore FROM playerbreaks WHERE FixtureId = %d AND PlayerId = %d",$fixtureid,$fixdetail->AwayPlayerId));
	                $count = 1;
	                foreach($qryawaybrkDetail as $abreak) {
	                    if ($count>1){
	                        $abreaktext = $abreaktext . ';';
	                    }
	                    $abreaktext = $abreaktext . $abreak->BreakScore;
	                    $count = $count+1;
	                }
	                
	                // Now let's put the data into a string to pass back to the form
	                $fixturedetails_select = $fixturedetails_select . 
	                      'homeplayer' .  $fixdetail->Sequence . '='.  $fixdetail->HomePlayerId .''.
	                      ',htscore' .    $fixdetail->Sequence . '='.  $fixdetail->HomeScore . ''.
	                      ',htbreak' .    $fixdetail->Sequence . '='.  $hbreaktext . '' .
	                      ',awayplayer' . $fixdetail->Sequence . '='.  $fixdetail->AwayPlayerId . ''.
	                      ',atscore' .    $fixdetail->Sequence . '=' . $fixdetail->AwayScore . ''. 
	                      ',atbreak' .    $fixdetail->Sequence . '=' . $abreaktext . ',' .
	                      '';           
	            }
         	}
          	//$qrybrkDetail = $wpdb->get_var($wpdb->prepare("SELECT PlayerId, BreakScore FROM playerbreaks WHERE FixtureId = %d",$fixtureid));
           	return $fixturedetails_select;
         }
        
         
	     //----------------------------------------------------------------------------------------------------------	

		// RESULTS LIST WIDGET FOR A PARTICULAR SEASON ///////////////////////////////////////////////////
		class resultsbyweek_widget extends WP_Widget {

			function __construct() {
				parent::__construct(
				// Base ID of your widget
				'resultsbyweek_widget', 
		
				// Widget name will appear in UI
				__('BDSCL Results display by week', 'resultsbyweek_widget_domain'), 
		
				// Widget description
				array( 'description' => __( 'Displays the results by week', 'resultsbyweek_widget_domain' ), ) 
				);
			}
				

			// Creating widget front-end
			// This is where the action happens
			public function widget( $args, $instance ) {
				$title = apply_filters( 'widget_title', $instance['title'] );
				// before and after widget arguments are defined by themes
				echo $args['before_widget'];
				if ( ! empty( $title ) )
				echo $args['before_title'] . $title . $args['after_title'];
		
				// This is where you run the code and display the output
				// Get the details of the results...
				
				//$complisttext = getOpenCompetitions($GLOBALS["seasonid"],1); //1 for a widget
				$resultslisttext = getAllResultsbyDiv2(19,56);

				echo __( '<div id="resultslist_text">'.$resultslisttext.'</div>', 'resultsbyweek_widget_domain' );
				
				echo $args['after_widget'];
			}
					
			// Widget Backend 
			public function form( $instance ) {
				if ( isset( $instance[ 'title' ] ) ) {
						$title = $instance[ 'title' ];
					} else {
						$title = __( 'Results by week', 'resultsbyweek_widget_domain' );
				}
				
				// Widget admin form
				?>
				<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
				</p>
				<?php 
			}
				
			// Updating widget replacing old instances with new
			public function update( $new_instance, $old_instance ) {
				$instance = array();
				$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
				return $instance;
			}
			
		} 
		// Class resultsbyweek_widget ends here --------------------------------------------------------------------------------------------
		// Register and load the widget
		function resultsbyweek_load_widget() {
			register_widget( 'resultsbyweek_widget' );
		}
		add_action( 'widgets_init', 'resultsbyweek_load_widget' );

		// Function for returning results for a specific week and division
		function getAllResultsbyDivWeek($seasonid,$competitionid,$weekid){
			// seasonid is the season in the db
			// competitionid is the division from the competitions table
		
			global $rootlocal;
			global $wpdb;
			$location = $_SERVER['DOCUMENT_ROOT'];
					
			include ($location . $rootlocal . '/wp-config.php');
			include ($location . $rootlocal . '/wp-load.php');
			include ($location . $rootlocal . '/wp-includes/pluggable.php');
			
			// Set globals
			$GLOBALS["seasonid"] = $seasonid;
			$GLOBALS["divisionid"] = $competitionid;

			if ($weekid == "") {
				$weekid == 1;
			}

			$resultstext = '';
			$resultstext = $resultstext . '';

			$fix = $wpdb->get_results($wpdb->prepare("SELECT F.WeekNo, F.CompetitionId, C.CompetitionName as CompName, F.PlayDate as PlayDate,TH.TeamName as HomeTeam,
											FR.HomeScore, FR.AwayScore,TA.TeamName as AwayTeam, F.HomeTeamId as HomeId, F.AwayTeamId as AwayId FROM fixtureresults as FR
										    INNER JOIN fixtures as F ON F.FixtureId = FR.FixtureId
										    INNER JOIN teams AS TH ON F.HomeTeamId=TH.TeamId
									        INNER JOIN teams AS TA ON F.AwayTeamId=TA.TeamId
									        INNER JOIN competitions as C ON F.CompetitionId = C.CompetitionId
										    WHERE F.SeasonId = %d AND F.CompetitionId = %d AND F.WeekNo = %d",$GLOBALS["seasonid"],$GLOBALS["divisionid"],$weekid));

			$weekno = 0;

			foreach ($fix as $fixresults) {
				
				$resultstext = $resultstext . '
					<a href="http://www.bdcsnooker.org/teams?teamid=' . $fixresults->HomeId . '&seasonid=' . $seasonid . '">' .$fixresults->HomeTeam . '</a>
					' .$fixresults->HomeScore. ' - ' .$fixresults->AwayScore. 
					'<a href="http://www.bdcsnooker.org/teams?teamid=' . $fixresults->AwayId . '&seasonid=' . $seasonid . '">' .$fixresults->AwayTeam . '</a><br>';

				$weekno = $fixresults->WeekNo;

			}

			$resultstext = $resultstext . '</div>';

			return $resultstext;
		}

		// THIS IS THE BIT THAT DISPLAYS THE DATA FOR THE RESULTS PAGE
        add_shortcode("resultsbyweek_display_sc", "resultsbyweek_display");
        function resultsbyweek_display(){
            global $rootlocal;
            global $wpdb;
            $location = $_SERVER['DOCUMENT_ROOT'];
                   
            include ($location . $rootlocal . '/wp-config.php');
            include ($location . $rootlocal . '/wp-load.php');
            include ($location . $rootlocal . '/wp-includes/pluggable.php');   
             
            // Check the global teamid and seasonid so we can prefill the team table
			if (isset($_GET['seasonid'])) {
	    		$GLOBALS["seasonid"] = $_GET['seasonid'];
			}
			if (isset($_GET['divisionid'])) {
	    		$GLOBALS["divisionid"] = $_GET['divisionid'];
			}

			$resbyweektext = getAllResultsbyDivWeek($GLOBALS["seasonid"],$GLOBALS["divisionid"],1);
				
         	$the_form = '
	        <form id="theResultsbyWeekForm">';
	         
	        // GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
	        $qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons ORDER BY SeasonId DESC");
	             
	        $the_form = $the_form . '      
	        <div id="wrapper">
	        <div class="frmDropDown">
	        <div class="row">
	        <label>Season:</label>
	        <select name="season-list" id="season-list" onchange="resbyweek_select(\'competition\')">
	        <option value="">Select Season</option>';
	        
	        foreach($qrySeason as $season) {
             				// Check the global season so we can set it to selected
							$selected = '';
							if ($GLOBALS["seasonid"] === $season->SeasonId){
								$selected = 'selected = "selected" ';
							}
                            $the_form = $the_form . '   
                            <option ' . $selected . ' value="' . $season->SeasonId . '">' . $season->SeasonDesc .'</option>';
             }
	        
	       //NOW PUT THE DIVISION DROPDOWN IN
			$divdropdown = getCompSelect($GLOBALS["seasonid"],1);
			
            $the_form = $the_form . '</select><br>
                        	<label>Division:</label>
                        	<select name="division-list" id="division-list" class="division-list" onchange="resbyweek_select(\'division\')">';
                            
			$the_form = $the_form . $divdropdown;	

	        //NOW PUT THE WEEK DROPDOWN IN
	        $weeklist = getWeekSelect($GLOBALS["divisionid"]);
	        $the_form = $the_form . '</select><br>
	                       <label>Week:</label><div class="wkbtninline">
	                       <select name="week-list" id="week-list" class="week-list" onchange="resbyweek_select(\'results\')">';
	        $the_form = $the_form . $weeklist;  

	        //NOW PUT THE FIXTURES DROPDOWN IN
	        $the_form = $the_form . '</select>';
	        
	       	//Next button for the previous week
	        $the_form= $the_form . '&nbsp<input type="button" class="paginatebtn" value="Next" onclick="resbyweek_select(\'nextresults\')">';
	        //Previous button for the previous week
	        $the_form= $the_form . '&nbsp<input type="button" class="paginatebtn" value="Prev" onclick="resbyweek_select(\'prevresults\')">';

	        $the_form= $the_form . '</div><br>';
	        
	        // Header for the details        
	        $the_form = $the_form . '' . '
	        <input name="action" type="hidden" value="the_ajax_resultsbyweekdisplay" />&nbsp; <!-- this puts the action the_ajax_hook into the serialized form -->
	        </form><br>';

	        $the_form = $the_form . '<div id="resbyweek-tab">' . '' . '</div>';

	        return $the_form;
        }

        function ajax_return_resultsbyweekdisplay(){
			global $rootlocal;
	        global $wpdb;
	        $location = $_SERVER['DOCUMENT_ROOT'];
	                
	        include ($location . $rootlocal . '/wp-config.php');
	        include ($location . $rootlocal . '/wp-load.php');
	        include ($location . $rootlocal . '/wp-includes/pluggable.php');
	        
	        // Return changes
	        $seasonid = $_POST['season-list'];
			$divisionid = $_POST['division-list'];
			$weekid = $_POST['week-list'];

			$divlist = getCompSelect($seasonid,1);
			$weeklist = getWeekSelect($divisionid);
			$thisresbyweektext = getAllResultsbyDivWeek($seasonid,$divisionid,$weekid);
			$prevresbyweektext = getAllResultsbyDivWeek($seasonid,$divisionid,$weekid-1);
			$nextresbyweektext = getAllResultsbyDivWeek($seasonid,$divisionid,$weekid+1);

	        $returntext = array("Division"=>$divlist,
	        					"Week"=>$weeklist,
	        					"ThisWkResults"=>$thisresbyweektext,
	        					"PrevWkResults"=>$prevresbyweektext,
	        					"NextWkResults"=>$nextresbyweektext,
	                            );
	                 
	        echo json_encode($returntext); 
	         
	          
	        die();// wordpress may print out a spurious zero without this - can be particularly bad if using json        
		}

		add_shortcode("fixbyweek_display_sc", "fixbyweek_display");
        function fixbyweek_display(){
            global $rootlocal;
            global $wpdb;
            $location = $_SERVER['DOCUMENT_ROOT'];
                   
            include ($location . $rootlocal . '/wp-config.php');
            include ($location . $rootlocal . '/wp-load.php');
            include ($location . $rootlocal . '/wp-includes/pluggable.php'); 

             // Check the global teamid and seasonid so we can prefill the team table
			if (isset($_GET['seasonid'])) {
	    		$GLOBALS["seasonid"] = $_GET['seasonid'];
			}
			if (isset($_GET['divisionid'])) {
	    		$GLOBALS["divisionid"] = $_GET['divisionid'];
			}

			$qryweek = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT WeekNo, PlayDate FROM fixtures WHERE CompetitionId = %d ORDER BY WeekNo Desc",$GLOBALS["seasonid"]));
	        
	        $navtext = 'Click on a week to show the fixtures for the: ' . $GLOBALS["seasondesc"] . '<br>
	        			If you want a complete view of the full season fixtures then click <a href="http://www.bdcsnooker.org/competitions/current-season-fixture-list/">here...</a><br><br>';
	        $navtext = $navtext . '<div class="wkbtninline">';
	        foreach($qryweek as $week) {
	                       $navtext = $navtext . '<input type="button" class="paginatebtn" value="' . $week->WeekNo . '" onclick="">';
	        }
	        
	        $navtext = $navtext . '</div>';

	        $fix =$wpdb->get_results($wpdb->prepare("SELECT F.WeekNo, F.CompetitionId, C.CompetitionName as CompName, F.PlayDate as PlayDate, F.HomeTeamId as HomeId,
	        											F.AwayTeamId as AwayId FROM fixtures as F
									        			INNER JOIN competitions as C ON F.CompetitionId = C.CompetitionId
										    			WHERE F.SeasonId = %d",$GLOBALS["seasonid"]));
		
			$tfix='';					 
			
			if (count($fix) > 0 ) {
					
				$tfix=$tfix . '<table class="mtab-weekfix">
						<thead><tr class="tab-header">
							<th class="mtab-weekno">Wk</th>
							<th class="mtab-date">Date</th>
							<th class="mtab-div">Division</th>
							<th class="mtab-fixhome">Home Team</th>
							<th class="mtab-fixaway">Away Team</th>
						</thead></tr><tbody>';
				 
				foreach ($fix as $fix) {
					
					//If a team is a Rest week then we need to set the team as Rest 	
					if ($fix->HomeId == 0){
						$hteam = 'Rest';
					} else {
						$hteam = $wpdb->get_var($wpdb->prepare("SELECT Teamname FROM teams WHERE TeamId = %d" , $fix->HomeId));
					}	
						
					if ($fix->AwayId == 0){
						$ateam = 'Rest';
					} else {
						$ateam = $wpdb->get_var($wpdb->prepare("SELECT Teamname FROM teams WHERE TeamId = %d" , $fix->AwayId));
					}

					if ($fix->WeekNo == 1) {
						$style = "style=\"display: table-row\";";
					} else {
						$style = "";
					}
					$tfix = $tfix . '
								<tr class="fixrows fixweek' . $fix->WeekNo .'" ' . $style . '>
									<td class="mtab-weekno">' .$fix->WeekNo . '</td>
									<td class="mtab-date">' .date("d-M",strtotime($fix->PlayDate)). '</td>
									<td class="mtab-div">' . $fix->CompName . '</td>
									<td class="mtab-fixhome"><a href="http://www.bdcsnooker.org/teams?teamid=' . $fix->HomeId . '&seasonid=' . $seasonid . '">' .$hteam . '</a></td>
									<td class="mtab-fixaway"><a href="http://www.bdcsnooker.org/teams?teamid=' . $fix->AwayId . '&seasonid=' . $seasonid . '">' .$ateam. '</td>
								</tr>';
				}
						
				$tfix= $tfix. '</tbody></table>';
			} else {
				$tfix = $tfix . "No fixtures to display";
			}

	        return $navtext . $tfix;
		}

		// NEW ADMIN HERE TO START TO DEPRECATE AJAXCRUD ******************************
		
		add_shortcode('admin_bdscl_seasons_sc', 'admin_bdscl_seasons');
		function admin_bdscl_seasons(){
			global $rootlocal;
			global $wpdb;
			$location = $_SERVER['DOCUMENT_ROOT'];
			
			// Put the instructions up and starting text
			$instructions = "<br><br>Use the Add Seasons button (right) to create a new season. Set the season to (O) Open to make it the current 
						season and make sure old seasons are (C) Closed. To change values in the table below just click on the relevant cell and it
						will enable a change to be made. Use the ENTER key to commit a change.</div>";

			echo '<div class="instruction_text">Current season is: ' . $GLOBALS["seasonid"] . ': ' . $GLOBALS["seasondesc"] . $instructions;
             $the_table = '<table><thead></thead><tbody>';
             
             // GET THE DEFAULT SET OF SEASONS TO FILL THE FIRST SELECT DROPDOWN
             $qrySeason = $wpdb->get_results("SELECT * FROM seasons ORDER BY SeasonId DESC");
            
             foreach($qrySeason as $season) {
                            $the_table = $the_table . '<tr>
                            
                            <td>' . $season->SeasonId . '</td>
                            <td>' . $season->SeasonDesc . '</td>
                            <td>' . $season->StatusFlag . '</td>
                            
                            </tr>';
                            
             }
            
             
             $the_table = $the_table . '</tbody></table>';
			
			 echo $the_table;
		}
		
		// ****************************************************************************
?>