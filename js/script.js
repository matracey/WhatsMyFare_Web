var selectedRadio;
var stops;
var workingArray = new Array();

function setupTextFields(fieldSelector)
{
	/*
	 * TODO: Finish the live search code.
	 * TODO: Fix the fieldText variable -- we can't depend on textfield.val.
	 */
	var textField = $(fieldSelector);
	var fieldText = textField.val();

	textField.focus(function()
	{
		if (textField.val() == fieldText) { $(this).val(""); };
	}).blur(function()
	{
		if (textField.val() == "") { $(this).val(fieldText); };
	}).on("keyup", function(e) {
		clearTimeout($.data (this, 'timer') ) // Storing arbitrary timer data on the textfield element.
	    // Set Search String
	    var search_string = $(this).val();
	    var resultList = $('ul#results');

	    // Do Search
	    if(search_string == '' || search_string == fieldText)
	    {
	    	resultList.fadeOut();
	    }else{
	    	resultList.fadeIn();
	    	var html = search(search_string);
	    	// console.log("HTML: "+html);
	    	resultList.html(html);
	        $(this).data('timer', setTimeout(search, 100)) // Setting a timer of 100ms on the search method.
	    }return false; 
	});
}

function search(query)
{
	var result = false;
    if(query !== '')
    {
    	var html = "";
    	var numResults = 5;
    	var lowQuer = "";
        if(typeof query != 'undefined') lowQuer = query.toLowerCase();

        for (var i = workingArray.length - 1; i >= 0; i--)
        {
        	// console.log('Name: '+workingArray[i]['name']);
        	var lowName = workingArray[i]['name'].toLowerCase();
        	if( lowName.indexOf(lowQuer) != -1 && numResults != 0)
        	{
        		name = workingArray[i]['name'];
        		
        		leadingString = name.substr(0, lowName.indexOf(lowQuer));
        		matchingString = name.substr(lowName.indexOf(lowQuer), query.length);
        		trailingString = name.substr(lowName.indexOf(lowQuer)+query.length, name.length);

        		boldHTML = "LEADING<span style='text-decoration:italic;'>BOLD</span>TRAILING";
        		boldHTML = boldHTML.replace("LEADING", leadingString);
        		boldHTML = boldHTML.replace("BOLD", matchingString);
        		boldHTML = boldHTML.replace("TRAILING", trailingString);
        		html = html+"<li style=\"font-weight:normal;\" id='"+workingArray[i]['id']+"' class='resultItem'>"+boldHTML+"</li>";
        		numResults--;
        	}
        };
        result = html;
    }
    return result;    
}

function setupHideRadio(fieldSelector)
{
	$('.'+fieldSelector+'_selector input:radio').addClass('input_hidden');
	$('.'+fieldSelector+'_selector label').click(function()
	{
		selectedRadio = ($(this).attr('id'));
		if(fieldSelector == "service") workingArray = getWorkingArray(selectedRadio);
		else if(fieldSelector == "journey") updateDisplayedFare(selectedRadio);
		$('.'+fieldSelector+'_selected').removeClass(fieldSelector+'_selected');
	    $('#'+selectedRadio+'Div').addClass(fieldSelector+'_selected');
	});
}

function updateDisplayedFare(field)
{
	if (field === 'single' || field === 'return')
	{
		switch(field)
		{
			case 'single':
			$('span#cash').text(cashSingle.toFixed(2));
			$('span#leap').text(leapSingle.toFixed(2));
			break;
			case 'return':
			$('span#cash').text(cashReturn.toFixed(2));
			$('span#leap').text(leapReturn.toFixed(2));
			break;
		}
	};
}

function getWorkingArray(serviceName)
{
	/*
	 * This function is going to create a new empty array and a new position integer.
	 * It will then use a switch to determine the selected service ID in integer form.
	 * Finally, it will enumarate through the global stops array. While doing this,
	 * it will check to see if the stop type id at the current index matches the selected
	 * service ID that was figured out in the switch statement. If there's a match, the
	 * stop name at that index is added to the array.
	 * After this has been completed, the fully populated array is returned to the caller.
	 */

	var array = new Array();
	var aPos = 0;
	var selectedId;
	switch(serviceName)
	{
		case "dart":
		case "rail":
			selectedId = 1;
			break;
		case "luas":
		default:
			selectedId = 2;
			break;
	}
	for (var i = stops.length - 1; i >= 0; i--) {
		if(stops[i]['stop_type_id'] == selectedId)
		{
			// console.log('NAME: '+stops[i]['name']);
			array[aPos] = stops[i];
			aPos++;
		}
	};
	return array;
}

function getStops()
{
	jQuery.getJSON('private/api/MTQ1NjQzMTI4NA==/getAllActiveStops/', function(data)
	{
		$('form').activity(false);
		disableForm(true);
		stops = data;
		workingArray = getWorkingArray("luas"); 

		// Text Field Click to clear
		setupTextFields('#origin');
		setupTextFields('#destin');
	});
}

function disableForm(enable){
	if(!enable || enable == null)
	{
		$('div.form_wrapper').addClass('transparent');
		$('input').attr("disabled", "disabled");
	}else
	{
		$('div.form_wrapper').removeClass('transparent');
		$('input').removeAttr("disabled");
	}
}

$(document).ready(function()
{
	$('form').activity();
	disableForm();
	getStops();
	
	setupHideRadio('service');
	setupHideRadio('bracket');
	setupHideRadio('journey');

	$('#adultDiv').addClass('bracket_selected');
	$('#singleDiv').addClass('journey_selected');
	$('#luasDiv').addClass('service_selected');
});