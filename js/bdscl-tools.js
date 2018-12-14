// BEGIN AJAX RETURN FUNCTIONS FOR DEALING WITH CHANGING DROP DOWNS ACROSS MANY PAGES ***************************************************

function fixture_select($selected){
         
        jQuery.post(the_ajax_script.ajaxurl, jQuery("#theForm").serialize()
        ,
            function(response_from_the_action_function){
              //Clear the errortext
              jQuery("#errortext").html(''); 
              // Return the response and JSON parse to access the array
              $returntext = JSON.parse(response_from_the_action_function);		      // Lets check for a Save click
              $saveclicked = jQuery("#save").css('backgroundColor');
              if ($saveclicked == 'rgb(0, 255, 0)') {
                  //So save clicked but did we return a save success?
                  $savesuccess = $returntext.SaveSuccess;
                  $errors = '';
                  if ($savesuccess == 'Pass') {
                      //All good here
                      jQuery("#errortext").html('No Errors found, data saved successfully');
                  } else {
                     for (var prop in $returntext.Errors) {
                        $errors += prop + ": " + $returntext.Errors[prop] + '<br/>';
                     }
                     jQuery("#errortext").html($errors);
                  }   
                  jQuery("#savehidden").val($savesuccess);
                  jQuery("#save").css('backgroundColor','rgb(37,168,35)'); 
              } else {              	
                  jQuery("#savehidden").val('Not Saving');
                  // The get the array parts and put them in relevant vars
                  $comptext = $returntext.Competition;
                  $weektext = $returntext.Week;
                  $fixtext = $returntext.Fixture;
                  $hplaytext = $returntext.HPlayer;
                  $aplaytext = $returntext.APlayer;
                  $fixdettext = $returntext.FixDetails;
                  // Depending on what select box has been changed make the next drop down contain the right options
                  // So The first season select passes 'competition' as $selected, so that we know to update the competition select box 
                  if ($selected == "competition") {
                    jQuery("#competition-list").empty();
                    jQuery("#competition-list").removeAttr("disabled").append($comptext);   
                    jQuery("#week-list").empty();
                    jQuery("#week-list").append("<option value=''>Select Week</option>");
                    jQuery("#fixture-list").empty();
                    jQuery("#fixture-list").append("<option value=''>Select Fixture</option>");
                  }
                  // So competition select change so update week select options
                  if ($selected == "week") {
                    jQuery("#week-list").empty();
                    jQuery("#week-list").removeAttr("disabled").append($weektext);   
                    jQuery("#fixture-list").empty();
                    jQuery("#fixture-list").append("<option value=''>Select Fixture</option>");   
                  }
                  // So week select change so update fixture select options
                  if ($selected == "fixture") {
                    jQuery("#fixture-list").empty();
                    jQuery("#fixture-list").removeAttr("disabled").append($fixtext);                  
                  }
                  // So fixture select change so update player select options
                  if ($selected == "player") {
                    jQuery("#homeplayer1-list").empty();
                    jQuery("#homeplayer2-list").empty();
                    jQuery("#homeplayer3-list").empty();
                    jQuery("#homeplayer4-list").empty();
                    jQuery("#awayplayer1-list").empty();
                    jQuery("#awayplayer2-list").empty();
                    jQuery("#awayplayer3-list").empty();
                    jQuery("#awayplayer4-list").empty();
                    jQuery("#homeplayer1-list").removeAttr("disabled").append($hplaytext);
                    jQuery("#awayplayer1-list").removeAttr("disabled").append($aplaytext);                    
                    jQuery("#homeplayer2-list").removeAttr("disabled").append($hplaytext);
                    jQuery("#awayplayer2-list").removeAttr("disabled").append($aplaytext);
                    jQuery("#homeplayer3-list").removeAttr("disabled").append($hplaytext);
                    jQuery("#awayplayer3-list").removeAttr("disabled").append($aplaytext);
                    jQuery("#homeplayer4-list").removeAttr("disabled").append($hplaytext);
                    jQuery("#awayplayer4-list").removeAttr("disabled").append($aplaytext);
                    
                  // Now lets put in the data that may already have been saved for this item
                  // First fixdettext will only have data if the fixture has been saved before 
                  // If this is first time save then fixdettext will be null so need to check and handle
                     if ($fixdettext === null){
                         // So this is completely new fixture to save so let's just clear down score and breaks
                         for (i = 1; i < 5; i++) {
                             jQuery('#htscore' + i).val('');
                             jQuery('#atscore' + i).val('');
                             jQuery('#htbreak' + i).val('');
                             jQuery('#atbreak' + i).val('');
                         }
                         
                     } else {
                      // Not a new save so lets slice up fixdettext details 
                         $arr = $fixdettext.split(","); 
                         // Four matches so lets loop 4 times and set the value of the scores and players and breaks
                         $count = 0;
                         for (i = 1; i < 5; i++) {
                         
                            $htplayer = $arr[$count];  
                            $htscore = $arr[$count+1];
                            $htbreak = $arr[$count+2]; 
                            $atplayer = $arr[$count+3];
                            $atscore = $arr[$count+4];
                            $atbreak = $arr[$count+5];
                            jQuery('#homeplayer' + i + '-list').val($htplayer.slice($htplayer.indexOf('=')+1),1);
                            jQuery('#awayplayer' + i + '-list').val($atplayer.slice($atplayer.indexOf('=')+1),1);
                            jQuery('#htscore' + i).val($htscore.slice($htscore.indexOf('=')+1),1);
                            jQuery('#atscore' + i).val($atscore.slice($atscore.indexOf('=')+1),1);
                            jQuery('#htbreak' + i).val($htbreak.slice($htbreak.indexOf('=')+1),1);
                            jQuery('#atbreak' + i).val($atbreak.slice($atbreak.indexOf('=')+1),1);
                            $count= $count+6;   
                           } 
                     } //else of fixdettext null check    
                  } // if $selected == "player")
                } // if Saving
            } // end sub function
        ); // end jquery
    } // end main function

/* JS for the fixture creation season selector and create button */
function createfix_select($selected){
	
	jQuery.post(the_ajax_script.ajaxurl, jQuery("#theCreateFixturesForm").serialize()
        ,
            function(response_from_the_action_function){
            	
              jQuery("#errortext").html('');
            	
              // Return the response and JSON parse to access the array
              $returntext = JSON.parse(response_from_the_action_function);
              
              $teamtabletext = $returntext.Table;
              
              
              
              // If the season drop down changes...
              if ($selected == "season") {
                     jQuery("#thefixtable").html($teamtabletext);
              }
              
              // If the create fixtures button has been pressed
              if ($selected == "validate") {
              	
              		//Get the success or fail first
              		$success = $returntext.Success;
              		
              		// Is there a validation issue
              		if ($success == "Fail") {
              			// Failed so report the errors
              			$validation = $returntext.Validation;
              			jQuery("#errortext").html($validation);
              		} else {
              			// Validation has passed so we are good to go
              			//Get all the data
              			$nrDivs = $returntext.NrDivs;
              			$maxTeams = $returntext.MaxTeams;
              			jQuery("#errortext").html("Passed");
             		}
              		
                     
              }
              
            } // end sub function
        ); // end jquery
}


/* JS for the Team tournament display screen */
function teamtourno_select($selected){
	
	jQuery.post(the_ajax_script.ajaxurl, jQuery("#theTeamTournoForm").serialize()
        ,
            function(response_from_the_action_function){
              // Return the response and JSON parse to access the array
              $returntext = JSON.parse(response_from_the_action_function);
              
              $teamtournotext = $returntext.Tournament;
              $teamtabletext = $returntext.Table;
              
              // If the season drop down changes...
              if ($selected == "season") {
                    jQuery("#teamtourno-list").empty();
              		jQuery("#teamtourno-list").removeAttr("disabled").append($teamtournotext);              
              }
              // If the tournament drop down changes...
              if ($selected == "tournament") {
                    jQuery("#tourno-tab").html($teamtabletext);         
              }   
            } // end sub function
        ); // end jquery
}

/* JS for the Player tournament display screen */
function playertourno_select($selected){
	
	jQuery.post(the_ajax_script.ajaxurl, jQuery("#thePlayerTournoForm").serialize()
        ,
            function(response_from_the_action_function){
              // Return the response and JSON parse to access the array
              $returntext = JSON.parse(response_from_the_action_function);
              
              $playertournotext = $returntext.Tournament;
              $playertabletext = $returntext.Table;
              
              // If the season drop down changes...
              if ($selected === "season") {
                    jQuery("#playertourno-list").empty();
              		jQuery("#playertourno-list").removeAttr("disabled").append($playertournotext);              
              }
              // If the tournament drop down changes...
              if ($selected === "tournament") {
                    jQuery("#tourno-tab").html($playertabletext);         
              }   
            } // end sub function
        ); // end jquery
}

/* JS for the Division display screen */
function division_select($selected){
	
	jQuery.post(the_ajax_script.ajaxurl, jQuery("#theDivisionForm").serialize()
        ,
            function(response_from_the_action_function){
              // Return the response and JSON parse to access the array
              $returntext = JSON.parse(response_from_the_action_function);
              
              $divisiontext = $returntext.Division;
              $divtabletext = $returntext.Table;
              $fixtabletext = $returntext.Fixture;
              $restabletext = $returntext.Result;
              $averagestabletext = $returntext.Averages;
              $breakstabletext = $returntext.Breaks;
              $weekbreakstabletext = $returntext.WkBreaks;
              
              // If the season drop down changes...
              if ($selected === "season") {
                    jQuery("#division-list").empty();
              		jQuery("#division-list").removeAttr("disabled").append($divisiontext);              
              }
              // If the division drop down changes...
              if ($selected === "division") {
                    jQuery("#division-tab").html($divtabletext);
                    jQuery("#fixture_text").html($fixtabletext);
                    jQuery("#result_text").html($restabletext); 
                    jQuery("#averages-tab").html($averagestabletext);  
                    jQuery("#breaks-tab").html($breakstabletext);
                    jQuery("#break_text").html($weekbreakstabletext);   
              }   
            } // end sub function
        ); // end jquery
}

/* JS for the Team display screen */
function team_select($selected){
	
	jQuery.post(the_ajax_script.ajaxurl, jQuery("#theTeamsForm").serialize()
        ,
            function(response_from_the_action_function){
              // Return the response and JSON parse to access the array
              $returntext = JSON.parse(response_from_the_action_function);
              
              $teamtext = $returntext.Team;
              $teamtabletext = $returntext.Table;
              
              // If the season drop down changes...
              if ($selected === "season") {
                    jQuery("#team-list").empty();
              		jQuery("#team-list").removeAttr("disabled").append($teamtext);              
              }
              // If the team drop down changes...
              if ($selected === "team") {
                    jQuery("#team-tab").html($teamtabletext);         
              }   
            } // end sub function
        ); // end jquery
}

/* JS for the Team display screen */
function ranking_select($selected){
	
	jQuery.post(the_ajax_script.ajaxurl, jQuery("#theRankingsForm").serialize()
        ,
            function(response_from_the_action_function){
              // Return the response and JSON parse to access the array
              $returntext = JSON.parse(response_from_the_action_function);

              $rankingtext = $returntext.Table;
              
              // If the season drop down changes...
              if ($selected === "season") {
                    jQuery("#rankings-tab").html($rankingtext);              
              }
            } // end sub function
        ); // end jquery
}

/* JS for the Player display screen */
function player_select($selected){
	
	jQuery.post(the_ajax_script.ajaxurl, jQuery("#thePlayersForm").serialize()
        ,
            function(response_from_the_action_function){
              // Return the response and JSON parse to access the array
              $returntext = JSON.parse(response_from_the_action_function);
              
              $playertabletext = $returntext.Table;
              
              // If the player drop down changes...
              if ($selected === "player") {
                    jQuery("#player-tab").html($playertabletext);         
              }   
            } // end sub function
        ); // end jquery
}
/* JS for the Player Head to Head display screen */

function headtohead_select($selected){		
        jQuery.post(the_ajax_script.ajaxurl, jQuery("#theHeadtoHeadForm").serialize() 
        ,           
           function(response_from_the_action_function){              
             // Return the response and JSON parse to access the array              
             $returntext = JSON.parse(response_from_the_action_function);

             $playertabletext = $returntext.Table;
             $player2text = $returntext.Player2;
      
             // If the player drop down changes...              
             if ($selected === "player1") { 
                   jQuery("#player2-list").empty();              		
                   jQuery("#player2-list").removeAttr("disabled").append($player2text);                 
             }                 		      
             if ($selected === "player2") {                   
                   jQuery("#headtohead-tab").html($playertabletext);                   
             }              
           } // end sub function        
        ); // end jquery
}

/* JS for the Clubs display screen */

function club_select($selected){
	
	jQuery.post(the_ajax_script.ajaxurl, jQuery("#theClubForm").serialize()
        ,
            function(response_from_the_action_function){
              // Return the response and JSON parse to access the array
              $returntext = JSON.parse(response_from_the_action_function);
              
              $clubtabletext = $returntext.Table;
              
              // If the club drop down changes...
              		jQuery("#clubs-tab").html($clubtabletext);             
              
            } // end sub function
        ); // end jquery
}

// END AJAX RETURN FUNCTIONS FOR DEALING WITH CHANGING DROP DOWNS ACROSS MANY PAGES ***************************************************

// BEGIN OTHER JS FUNCTIONS ***********************************************************************************************************
// Updates the away frame score after home score entered on Admin Results page
function upd_frames($inputbox){
    
    // Capture the frames box being changed and get the value
    $name = $inputbox.id;
    $frames = $inputbox.value;
    
    // Check if the value is text first
    if (isNaN($frames)){
        // If text then lets just set it to a valid frame score like 0
        $frames = '0';
        jQuery('#' + $name ).val('0');
    }
    
    // Find the pair home/away team frames box based on the changed team box
    if ($name.slice(0,1) == 'h') {
        $othername = 'a' + $name.slice(1);
    } else {
        $othername = 'h' + $name.slice(1);    
    }
    // What if both players are absent and the scores have to be 0 0 
    $hlistname = 'homeplayer' + $name.slice(-1) +  '-list';
    $alistname = 'awayplayer' + $name.slice(-1) + '-list';   
    $hval = jQuery('#' + $hlistname + ' option:selected').html();
    $aval = jQuery('#' + $alistname + ' option:selected').html();
   
    
    // First validate the frame score ie. can only be 0,1 or 2, if not set to 0 to be safe
    if ($frames > 2 || $frames < 0){
        jQuery('#' + $name ).val('0');
        // And set its pair to 2 for safety sake
        jQuery('#' + $othername ).val('2');
    }
           
    // Now work out the value
    switch ($frames){
        case '0':
            jQuery('#' + $othername ).val(2);
        break;
        case '1':
            jQuery('#' + $othername ).val(1);
        break;
        case '2':
            jQuery('#' + $othername ).val(0);
        break;
        default:
    } 
    
    // But if both players are absent override with a 0 0 
    if($hval == '(absent)' || $aval == '(absent)') {
    	jQuery('#' + $name ).val('0');
    	jQuery('#' + $othername ).val(0);
    }
}

// Toggles display of hidden rows and sets a background colour
function toggle($element) {
	jQuery(document).ready(function ($) {
		// Toggles show and hide of the element
    	$("." + $element).toggle();
    	// Sets and td children of the element to a certain background color
    	$("." + $element).children('td').css('background-color','#FFF');
  });
}

function changedrpdwn($selname) {
		
		if ($selname == "PlayerTournoRoundId") {
			// Get the dropdown Nr selected in the text
			$name = $selname.id;
			e = document.getElementById($selname);
			$seltext = e.options[e.selectedIndex].text;
			
			end_pos = $seltext.indexOf(':',0);	
			text_to_get = $seltext.substring(0,end_pos);
			
			//In the HomeentryId remove the options for different comps
			e1 = document.getElementById("HomeEntryId");
			e1length = e1.length;
			
			j = 0;
			for (i=0; i<e1length; i=i+1){
			 	$seltext1 = e1.options[j].text;			 	
				end_pos1 = $seltext1.indexOf(':',0);
				text_to_get1 = $seltext1.substring(0,end_pos1);
			 	
	  			if (text_to_get1 != text_to_get){
	  				// Need to keep the Bye in...
	  				if ($seltext1 == "7: Bye"){
	  					//console.write("Sdsd");
	  					j = j+1;
	  				} else {
	  					e1.remove(j);
	  				}
	  			 } else {
	  			 	j = j+1;
	  			 }
	  			 
  			} //end for
  		
  			//In the AwayentryId remove the options for different comps
			e1 = document.getElementById("AwayEntryId");
			e1length = e1.length;
			
			j = 0;
			for (i=0; i<e1length; i=i+1){
			 	$seltext1 = e1.options[j].text;			 	
				end_pos1 = $seltext1.indexOf(':',0);
				text_to_get1 = $seltext1.substring(0,end_pos1);
			 	
	  			if (text_to_get1 != text_to_get){
	  				// Need to keep the Bye in...
	  				if ($seltext1 == "7: Bye"){
	  					//console.write("Sdsd");
	  					j = j+1;
	  				} else {
	  					e1.remove(j);
	  				}
	  			 } else {
	  			 	j = j+1;
	  			 }
	  			 
  			} //end for
  		} //end if
}


// Puts the fixtures a tooltip on the form column on the divisions page
jQuery(document).ready(function($) {
// Tooltip only Text
	$('.masterTooltip').hover(function(){
	        // Hover over code
	        var title = $(this).attr('title');
	        $(this).data('tipText', title).removeAttr('title');
	        $('<p class="tooltip"></p>')
	        .text(title)
	        .appendTo('body')
	        .fadeIn('slow');
	}, function() {
	        // Hover out code
	        $(this).attr('title', $(this).data('tipText'));
	        $('.tooltip').remove();
	}).mousemove(function(e) {
	        var mousex = e.pageX + 20; //Get X coordinates
	        var mousey = e.pageY + 10; //Get Y coordinates
	        $('.tooltip')
	        .css({ top: mousey, left: mousex });
	});
});

function ajax_compentry_submit() {
  // Competition entry form JS
    var compentryform = jQuery('#theCompEntryForm').serialize();
    jQuery.ajax({
        data: "json",
        type:"POST",
        url: "/wp-admin/admin-ajax.php",
        data: compentryform,
        success: function(data) {
             console.log("Form was successful");
             jQuery("#messageDiv").html(data);
         }
    });
}

// END OTHER JS FUNCTIONS ***********************************************************************************************************

// CURRENTLY UNNEEDED
/*function infiniteScrollUp(){
	var self=this,kids=self.children();
	kids.slice(20).hide();
	setInterval(function(){
		kids.filter(':hidden').eq(0).fadeIn();
		kids.eq(0).fadeOut(function(){
			$(this).appendTo(self);
			kids=self.children();
		});
	},1000);
	return this;
}

function tabSwitch(new_tab, new_content) {
     
    document.getElementById('content_1').style.display = 'none';
    document.getElementById('content_2').style.display = 'none';
    document.getElementById('content_3').style.display = 'none';        
    document.getElementById(new_content).style.display = 'block';   
     
 
    document.getElementById('tab_1').className = '';
    document.getElementById('tab_2').className = '';
    document.getElementById('tab_3').className = '';        
    document.getElementById(new_tab).className = 'active';      
 
}


function ReplaceTeamContent(teamid,fixtext) {
	//var container = document.getElementById('fixture_text');
	//container.innerHTML = fixtext;
	 
	jQuery(document).ready(function ($) {
		
		$('.teams-results-div').hide();
		$('#fix-' + teamid).show();	
		$('#res-' + teamid).show();	
		
		//ACCORDION BUTTON ACTION	
		$('div.accordionButton').click(function() {
			$('div.accordionContent').hide();
			$(this).next().show();
					
		});
		//HIDE THE DIVS ON PAGE LOAD	
		//$("div.accordionContent").hide();
 
    });
	



function chngSeason() {
	var x = document.getElementById("selSeason").value;
	document.getElementById("Text").innerHTML = "You selected: " + x;
	var val=form.selSeason.options[form.selSeason.options.selectedIndex].value;
	self.location="test.php?selSeason=" + val ;
}

function getFixtures2(val) {
	jQuery.ajax({
	type: "POST",
	//url: "/bdc/wp-content/plugins/bdscl-tools/php/bdscl-tools.php",
	data:'season_id='+val,
	success: function(data){
		jQuery("#fixture-list").html(data);
	}
	});
}
}
function submit_me(){
    jQuery.post(the_ajax_script.ajaxurl, jQuery("#theForm").serialize()
    ,
    function(response_from_the_action_function){
    jQuery("#response_area").html(response_from_the_action_function);
    }
    );
}
*/


