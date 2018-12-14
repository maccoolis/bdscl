<?php

if(!empty($_POST["season_id"])) {
	global $wpdb;
	require_once('config.php');
	
	$results = $wpdb->get_results("SELECT * FROM fixtures WHERE SeasonId = '" . $_POST["season_id"] . "'");
	
	echo '<option value="">Select Fixture</option>';

	foreach($results as $fixture) {
		echo '<option value="' . $fixture->FixtureId . '">'. $fixture->FixtureId . '</option>';
	}
	
}

	//LOAD OTHER PHP FILES
	foreach ( glob( plugin_dir_path( __FILE__ ) . "php/*.php" ) as $file ) {
    	include_once $file;
	}
	
	/*Stuff worth keeping for now
		
        echo '<div id="wrapper">';
        $qrySeason = $wpdb->get_results("SELECT SeasonId, SeasonDesc FROM seasons WHERE StatusFlag = 'O' ORDER BY SeasonId DESC");
        
        if(isset($Season) and strlen($Season) > 0) {
			$qryComp=$wpdb->get_results("SELECT CompetitionId, SeasonId, CompetitionName, StartDate, DivisionFlag, SponsorId FROM Competitions WHERE (SeasonId = ' . $Season .') ORDER BY Competitionid"); 
		} else {
			$qryComp=$wpdb->get_results("SELECT CompetitionId, SeasonId, CompetitionName, StartDate, DivisionFlag, SponsorId FROM Competitions ORDER BY Competitionid"); 
			}
		$message  = "Test";
        echo '<form method="post" name="frm_results" action="">';
        echo '<select id="selSeason" onchange="chngSeason	()"><option value="">Select one</option>';
	    // The season drop down list
	    foreach ($qrySeason as $qrySeason) {
        	echo  '<option value="' . $qrySeason->SeasonDesc .'">' . $qrySeason->SeasonDesc .'</option>';
        }
        echo '</select>';
        // The competition drop down list
         echo '<select id="selCompetition"><option value="">Select one</option>';
	    foreach ($qryComp as $qryComp) {
        	echo  '<option value="' . $qryComp->CompetitionName .'">' . $qryComp->CompetitionName .'</option>';
        }
        echo '</select>';
      	echo '</form>';
      	echo '<p id="Text"></p>';
      	echo '</div>';
      	   	if(!empty($_POST["season_id"])) {
	
			$results = $wpdb->get_results("SELECT * FROM fixtures WHERE SeasonId = '" . $_POST["season_id"] . "'");
			
			echo '<option value="">Select Fixture</option>';

			foreach($results as $fixture) {
				echo '<option value="' . $fixture->FixtureId . '">'. $fixture->FixtureId . '</option>';
			}
		}
		
		 	echo '<script>
      	function getFixtures(val) {
			jQuery.ajax({
				type: "POST",
				data:\'season_id=\'+val,
				success: function(data){
					jQuery("#fixture-list").html(data);
				}
			});
			
		}
		</script>';
		*/
		
		add_action('wp_ajax_update_selector', 'update_selector');
		
		function update_selector() {
		        global $wpdb; // this is how you get access to the database
		       	
		        $seasonID = $_POST['seasonID'];
		        //$postID = $_POST['postID'];
		 
		    	$array = $wpdb->get_results("SELECT * FROM competitions WHERE SeasonId = " . $seasonID . "");

		        foreach($array as $competition)
		        {
		                $competitionID = $competition->CompetitionID;
		                $competitionName = $competition->CompetitionName;
		               
		                $option .= "<option value='".$competitionID."'>".$competitionName."</option";
		     
		        }
		       
		        echo '' . $option . '';
		 
		        die();
		}//end function
		
	
?>