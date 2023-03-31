var intScore = 0, strVerdict = '', strLog = '', passLength = 0;
var checkPassword = function(passwd)
{
	intScore = 0, strVerdict = '', strLog = '', passLength = passwd.length;

	if(passLength <= 2)
	{
		strVerdict = 'In-secure', strLog = 'Password too short.';
		return;
	}
	
	// PASSWORD LENGTH
	if (passLength<5)
	{
		intScore = (intScore+3)
		strLog   = strLog + "3 points for length (" + passLength + ")\n"
	}
	else if (passLength>4 && passLength<8) // length between 5 and 7
	{
		intScore = (intScore+6)
		strLog   = strLog + "6 points for length (" + passLength + ")\n"
	}
	else if (passLength>7 && passLength<16)// length between 8 and 15
	{
		intScore = (intScore+12)
		strLog   = strLog + "12 points for length (" + passLength + ")\n"
	}
	else if (passLength>15)// length 16 or more
	{
		intScore = (intScore+18)
		strLog   = strLog + "18 point for length (" + passLength + ")\n"
	}				
	
	// LETTERS (Not exactly implemented as dictacted above because of my limited understanding of Regex)
	if (passwd.match(/[a-z]/)) // [verified] at least one lower case letter
	{
		intScore = (intScore+1)
		strLog   = strLog + "1 point for at least one lower case char\n"
	}
	
	if (passwd.match(/[A-Z]/))// [verified] at least one upper case letter
	{
		intScore = (intScore+5)
		strLog   = strLog + "5 points for at least one upper case char\n"
	}
	
	// NUMBERS
	if (passwd.match(/\d+/))// [verified] at least one number
	{
		intScore = (intScore+5)
		strLog   = strLog + "5 points for at least one number\n"
	}
	
	if (passwd.match(/(.*[0-9].*[0-9].*[0-9])/))// [verified] at least three numbers
	{
		intScore = (intScore+5)
		strLog   = strLog + "5 points for at least three numbers\n"
	}				
	
	// SPECIAL CHAR
	if (passwd.match(/.[!,@,#,$,%,^,&,*,(,),?,_,~]/))// [verified] at least one special character
	{
		intScore = (intScore+5)
		strLog   = strLog + "5 points for at least one special char\n"
	}

	// [verified] at least two special characters
	if (passwd.match(/(.*[!,@,#,$,%,^,&,*,(,),?,_,~].*[!,@,#,$,%,^,&,*,(,),?,_,~])/))
	{
		intScore = (intScore+5)
		strLog   = strLog + "5 points for at least two special chars\n"
	}			
	
	// COMBOS
	if (passwd.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/))// [verified] both upper and lower case
	{
		intScore = (intScore+2)
		strLog   = strLog + "2 combo points for upper and lower letters\n"
	}

	if (passwd.match(/([a-zA-Z])/) && passwd.match(/([0-9])/))// [verified] both letters and numbers
	{
		intScore = (intScore+2)
		strLog   = strLog + "2 combo points for letters and numbers\n"
	}		 
	
	// [verified] letters, numbers, and special characters
	if (passwd.match(/([a-zA-Z0-9].*[!,@,#,$,%,^,&,*,(,),?,_,~])|([!,@,#,$,%,^,&,*,(,),?,_,~].*[a-zA-Z0-9])/))
	{
		intScore = (intScore+2)
		strLog   = strLog + "2 combo points for letters, numbers and special chars\n"
	}			

	if(intScore < 16)
	{
	   strVerdict = "very weak"
	}
	else if (intScore > 15 && intScore < 25)
	{
	   strVerdict = "weak"
	}
	else if (intScore > 24 && intScore < 35)
	{
	   strVerdict = "mediocre"
	}
	else if (intScore > 34 && intScore < 45)
	{
	   strVerdict = "strong"
	}
	else
	{
	   strVerdict = "stronger"
	}
}

function showPasswordStrength(fieldID)
{
	$(fieldID).focusin(function()
	{
		$('div.score').fadeIn('slow');
	}).keyup(function()
	{
		checkPassword($(this).val());
		$('#passwordScore').remove();
		$('div.score span b').width(0).animate({width: intScore}, 100);
		//console.log('Score: '+intScore+' ('+strVerdict+')\nLog: '+strLog);
		$('<div id="passwordScore">'+intScore+'/50</div>').appendTo('div.tipsy-inner');
	});
}