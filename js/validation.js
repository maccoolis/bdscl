
	/* ajaxCRUD validation javascript (optional) */

	$(document).ready(function(){
		doValidation();
	});

	function doValidation(){

		// mask some fields with desired input mask
		// use "modifyFieldWithClass" to set a css class on the fields you need to mask (e.g. phone, zip)
		$("input.phone").mask("(99999) 999999");
		$("input.postcode").mask("*******");

		//add any other validation entries here
		$("input.pincode").mask("999999"); // Indian Pincode

		//put a date picker on a field (comment this out if you do not use calendars)
		try{
			$( ".datepicker" ).datepicker({
				dateFormat: 'yy-mm-dd',
				showOn: "button",
					buttonImage: "/wp-content/plugins/bdscl-tools/images/calendar.gif",
					buttonImageOnly: true,
				onClose: function(){
					this.focus();
					//$(this).submit();
				}
			});
		}
		catch(err){
			//no fields have a datepicker on them
		}
	}

