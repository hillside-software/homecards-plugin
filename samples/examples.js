
jQuery(document).ready(function($) {
	
	/****
	Here is an example of the getQrCodeImageUrl function
	****/
	$("body").append("<img />").attr("src", getQrCodeImageUrl("http://qrcd.co/?wID=10001", "12", "n/a")).css({width: "125px"});
	
	
	/****
	Here is how we can use the StringHelpers extension magic!
	****/
	var testString = "Hello World";
	
	/*
	This is an example implementation of a validation attribute: 'data-maxlength'...
	
	*/
	$('textarea[data-maxlength]').bind('mouseup keyup change focus blur', function(e) {
		var maxlength= $(this).data('maxlength');
		this.value = this.value.left(maxlength); // << Note the use of my custom function .left()
	});
	
	
	
	
}

