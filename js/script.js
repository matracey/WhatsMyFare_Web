function setupClickToClear(fieldSelector)
{
	var textField = $(fieldSelector);
	var fieldText = textField.val();

	textField.focus(function()
	{
		if (textField.val() == fieldText) { $(this).val(""); };
	}).blur(function()
	{
		if (textField.val() == "") { $(this).val(fieldText); };
	});
}

function setupHideRadio(fieldSelector)
{
	$('.'+fieldSelector+'_selector input:radio').addClass('input_hidden');
	$('.'+fieldSelector+'_selector label').click(function()
	{
		var id = ($(this).attr('id'));
		updateDisplayedFare(id);
		$('.'+fieldSelector+'_selected').removeClass(fieldSelector+'_selected');
	    $('#'+id+'Div').addClass(fieldSelector+'_selected');
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

$(document).ready(function()
{
	// console.log("Hello");
	// Text Field Click to clear
	setupClickToClear('#origin');
	setupClickToClear('#destin');
	
	setupHideRadio('service');
	setupHideRadio('bracket');
	setupHideRadio('journey');

	// var input = $("input:radio");
	// input.click(function()
	// {
	// 	alert(input.attr("id"));
	// });

	$('#adultDiv').addClass('bracket_selected');
	$('#singleDiv').addClass('journey_selected');
	$('#luasDiv').addClass('service_selected');
});