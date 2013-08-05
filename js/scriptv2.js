var selectedRadio;
var stops;
var workingArray = new Array();

function setupTextFields(fieldSelector)
{
	/*
	 * TODO: Update Get All Stops flow to set workingArray as ENTIRE array. --
	 * TODO: Add code to display service icon on the right side of the text fields when link is clicked or field blurs.
	 * TODO: Add search function that scores each stop based on # of matched chars.
	 */
	var textField = $(fieldSelector);
	var othrField;
	var fieldText;
	var othrFieldText;

	activeField = fieldSelector.replace("#", "")
	switch(activeField)
	{
		case "origin":
		fieldText = "Start typing an origin...";
		othrField = $('#destin');
		othrFieldText = "Start typing a destination...";
		break;
		case "destin":
		fieldText = "Start typing a destination...";
		othrField = $('#origin');
		othrFieldText = "Start typing an origin...";
		break;
	}

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
	    var resultList = $('ul'+fieldSelector+'Results');

	    // Do Search
	    if(search_string == '' || search_string == ' ' || search_string == fieldText)
	    {
	    	resultList.fadeOut();
	    }else{
	    	resultList.fadeIn();
	    	var html = search(search_string);
	    	// console.log("HTML: "+html);
	    	resultList.html(html);

	    	$('.link').click(function(e)
	        {
	        	e.preventDefault();
	        	// $(this).removeAttr('href');
	        	id = $(this).attr('id');
	        	var radio;
	        	for (var i = workingArray.length - 1; i >= 0; i--) {
	        		if(workingArray[i]['id'] == id)
	        		{
	        			value = workingArray[i];
	        		}
	        	};

	        	radio = $('#'+value['stop_type_id']);
	        	console.log('Other Field Value: '+othrField.val());
	        	if( othrField.val() != othrFieldText && (!checkForStopOnService(othrField.val(), value['stop_type_id'])) )
	        	{
	        		 othrField.val(othrFieldText);
	        		 othrField.css('background-image', 'none');

	        	}
	        	radio.attr('checked', 'checked');
	        	console.log(radio.attr('value') + ' ' + radio.attr('checked'));
	        	textField.val(value['name']);

	        	var image;
	        	switch(value['service_id'])
	        	{
	        		case '1':
	        		image = 'images/red.png';
	        		break;
	        		case '2':
	        		image = 'images/green.png';
	        		break;
	        		case '3':
	        		image = 'images/rail.png';
	        		break;
	        		case '4':
	        		image = 'images/dart.png';
	        		break;
	        	}

	        	console.log(value['service_id']);
	        	textField.css('background-image', 'url('+image+')');
	        	textField.css('background-size', '6%');
	        	textField.css('background-repeat', 'no-repeat');
	        	textField.css('background-position', 'right center')

	        	resultList.fadeOut();
	            return false;
	        });

	        $(this).data('timer', setTimeout(search, 100)) // Setting a timer of 100ms on the search method.
	    }return false; 
	});
}

function checkForStopOnService(stop,service)
{
	for (var i = workingArray.length - 1; i >= 0; i--)
	{
		console.log("i = "+i);
		console.log("Does "+workingArray[i]['stop_type_id']+" match "+service+"? ");// + workingArray[i]['stop_type_id'] == service);
		console.log("Does "+workingArray[i]['name']+" match "+stop+"? " + workingArray[i]['name'] == stop);
		if(workingArray[i]['stop_type_id'] == service && workingArray[i]['name'] == stop)
		{
			return true;
		}
	};
	return false;
}

function search(query)
{
	var result = false;
    if(query !== '')
    {
    	var html = "";
    	var numResults = 5;
    	var lowQuer = "";
    	var sortedArray = new Array();
    	var sortedArrayCount = 0;

        if(typeof query != 'undefined') lowQuer = query.toLowerCase();

        try
        {
        	workingArrayLength = workingArray.length;
        	queryLength = query.length;
        }catch(e)
        {
        	workingArrayLength = 0;
        	queryLength = 0;
        }

        for (var i = workingArrayLength - 1; i >= 0; i--)
        {
        	matchStart = 0;
        	matchEnd = 0;
        	// console.log('Name: '+workingArray[i]['name']);
        	var lowName = workingArray[i]['name'].toLowerCase();
        	if( lowName.indexOf(lowQuer) != -1 )
        	{
        		name = workingArray[i]['name'];
        		
        		matchStart = lowName.indexOf(lowQuer);
        		matchEnd = matchStart+query.length;
        		trailLen = name.length-matchEnd;

        		workingArray[i]['matchStart'] = matchStart;
        		workingArray[i]['matchEnd'] = matchEnd;
        		workingArray[i]['trailLen'] = trailLen;

        		sortedArray[sortedArrayCount] = workingArray[i];
        		sortedArrayCount++;
        	}
        }
        // SORTED ARRAY CONTAINS ALL SEARCH RESULTS.
        sortedArray.sort( function(a,b) { return (parseInt(b.matchStart) - parseInt(a.matchStart)) } );
        for (var i = sortedArray.length - 1; i >= 0; i--) {
        	// console.log(sortedArray[i].matchStart+' '+sortedArray[i].name)
        	if(numResults > 0){
        		name = sortedArray[i]['name'];
        		leadingString = name.substr(0, sortedArray[i]['matchStart']);
        		matchingString = name.substr(sortedArray[i]['matchStart'], query.length);
        		trailingString = name.substr(sortedArray[i]['matchEnd'], sortedArray[i]['trailLen']);

        		boldHTML = "<li class=\"resultItem\" class='resultItem'><a class=\"link\" id='"+sortedArray[i]['id']+"'>LEADING<span class=\"match\">BOLD</span>TRAILING</a></li>";
        		boldHTML = boldHTML.replace("LEADING", leadingString);
        		boldHTML = boldHTML.replace("BOLD", matchingString);
        		boldHTML = boldHTML.replace("TRAILING", trailingString);
        		html = html+boldHTML;
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

/* 
function getWorkingArray(serviceName)
{
	 *
	 * This function is going to create a new empty array and a new position integer.
	 * It will then use a switch to determine the selected service ID in integer form.
	 * Finally, it will enumarate through the global stops array. While doing this,
	 * it will check to see if the stop type id at the current index matches the selected
	 * service ID that was figured out in the switch statement. If there's a match, the
	 * stop name at that index is added to the array.
	 * After this has been completed, the fully populated array is returned to the caller.
	 *

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
}*/

function compare(a,b) {
	/*
	 * This function is called when the array of stops is being sorted.
	 * If a minus value is returned, then a should go before b.
	 * If a plus value is returned, a should go after b.
	 * if 0 is returned, then a and b are equal!
	 */
	
	if (a['matchStart'] < b['matchStart']) return -1;
	if (a['matchStart'] > b['matchStart']) return 1;
	return 0;
}

function getStops()
{
	/*
	 * This function will retrieve all active stops via AJAX from the API.
	 * There should be code in place to handle unexpected responses from the API.
	 * At the moment, when the API can't connect to the database, the query never
	 * stops running.
	 */

	jQuery.getJSON('./private/api/MTQ1NjQzMTI4NA==/getAllActiveStops/', function(data)
	{
		$('form').activity(false);
		disableForm(true);

		workingArray = data;
		// workingArray = getWorkingArray("luas"); 
		workingArray.sort(compare);
		
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

function setSocialIcon(id)
{
	$('.socialLink > #'+id).css('background-image', 'url(\'images/soc-'+id+'.png\')');
	console.log ( id+" background-image = "+$('.socialLinks > #'+id).css('background-image') );
}

$(document).ready(function()
{
	$('form').activity();
	disableForm();
	getStops();
	
	setSocialIcon('fb'); // Facebook
	setSocialIcon('tw'); // Twitter
	setSocialIcon('ig'); // Instagram

	setupHideRadio('service');
	setupHideRadio('bracket');
	setupHideRadio('journey');

	$('#adultDiv').addClass('bracket_selected');
	$('#singleDiv').addClass('journey_selected');
	$('#luasDiv').addClass('service_selected');
});
