/*!

	@description IMPORTANT NOTE: The StringHelpers() functions DO NOT use Regular Expressions
	@copyright Hillside Software, Inc and Dan Levy - 2011, 2012
	@author Dan Levy
	
	
	@version v0.552
*/

(function() {
	/* Define a wire-up initializer 
		Here's a list of what's added to the String base type
	*/
	function initStringHelpers() {
		var stringHelpers = new StringHelpers();
		String.prototype.equals = stringHelpers.equals;
		String.prototype.htmlEncode = stringHelpers.htmlEncode;
		String.prototype.htmlEncodeForced = stringHelpers.htmlEncodeForced;
		String.prototype.urlEncode = stringHelpers.urlEncode;
		String.prototype.urlDecode = stringHelpers.urlDecode;
		String.prototype.urlEncodeForced = stringHelpers.urlEncodeForced;
		String.prototype.left = stringHelpers.left;
		String.prototype.right = stringHelpers.right;
		String.prototype.padLeft = stringHelpers.padLeft;
		String.prototype.padRight = stringHelpers.padRight;
		String.prototype.endsWith = stringHelpers.endsWith;
		String.prototype.startsWith = stringHelpers.startsWith;
		String.prototype.trim = stringHelpers.trim;
		String.prototype.trimLeft = stringHelpers.trimLeft;
		String.prototype.trimRight = stringHelpers.trimRight;
		String.prototype.obfuscateEmail = stringHelpers.obfuscateEmail;
		
		String.prototype.pcase = stringHelpers.pcase;
		String.prototype.formatNumber = stringHelpers.formatNumber;
		String.prototype.makeNumeric = stringHelpers.makeNumeric;
	}
	
	/*
	 TODO: 
	 Add functions isAlphaNumeric, isAlpha, isNumeric, isInteger, 
	 DONE:
	 trim[End|Start]([removeEach])
	*/
	var StringHelpers = function() {
		var alphaChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		var integerChars = "0123456789";
		var numericChars = ".," + integerChars;
		var safeChars = alphaChars + integerChars;
	
		// Define trimOptions !!!
		var TRIM_ALL = 0, TRIM_LEFT = 1, TRIM_RIGHT = 2;
		function trimHelper(text, matchStrings, trimOptions, ignoreCase) {
			var newLength = -1,
					lastLength = -1;
			var iterationCounter = 0;
			
			if ( typeof ignoreCase == 'undefined' || ignoreCase === null ) { ignoreCase = false;}
			if (typeof matchStrings == 'undefined' || matchStrings == null) { matchStrings = [' ', "	", "\r", "\n"]; } // Auto array-ify the whitespace chars
			if (matchStrings instanceof String) { matchStrings = [matchStrings]; } // Auto array-ify the single value matchStrings value
			
			if ( ignoreCase ) { text = text.toLowerCase(); }
			lastLength = text.length;
			while ( lastLength != newLength ) {
				iterationCounter ++;
				lastLength = text.length;
				for (var i = 0; i < matchStrings.length; i++) {
					var mtch = matchStrings[i] == null ? '' : matchStrings[i].toString();
					var matchLen = mtch.length;
					if ( ignoreCase ) { mtch = mtch.toLowerCase(); }
					if ( text.length >= matchLen ) { // evaluate potential match
						if ( trimOptions === TRIM_ALL ) {
							if ( text.left(matchLen).equals(mtch) ) { text = text.right(text.length - matchLen); }
							if ( text.right(matchLen).equals(mtch) ) { text = text.left(text.length - matchLen); }
						} else if ( trimOptions === TRIM_LEFT ) {
							if ( text.left(matchLen).equals(mtch) ) { text = text.right(text.length - matchLen); }
						} else if ( trimOptions === TRIM_RIGHT ) {
							if ( text.right(matchLen).equals(mtch) ) { text = text.left(text.length - matchLen); }
						}
					}
				}
				newLength = text.length;//set initial length
			}
			return text.toString();
		}
	
	
	this.pcase = function() {
		return (this.toString().replace(/(^|\s)\S/g, function(char) {
			return char.toUpperCase();
		}));
	}

	this.equals = function(strToMatch, ignoreCase) {
		if (typeof ignoreCase || ignoreCase === null ) { ignoreCase = false; }
		if (ignoreCase) {
			if ( this === strToMatch ) { return true; }
		} else {
			if ( this.toLowerCase() === strToMatch.toLowerCase() ) { return true; }
		}
	}
	this.endsWith = function(matchStrings) {
		/* Check if we have a set of arguments; if so, treat as an input array */
		if (arguments.length >= 2) { matchStrings = arguments; }
		if ( typeof matchStrings == 'string' ) { matchStrings = [matchStrings];} // Auto array-ify the single value matchStrings value
		for (var i = 0; i < matchStrings.length; i++) {
			var mtch = matchStrings[i];
			var matchLen = mtch.length;
			if ( this.length >= matchLen ) { // evaluate potential match
				if ( mtch === this.right(matchLen) ) {
					// Success
					return true;
				}
			}
		}
		return false;
	}
	this.startsWith = function(matchStrings) {
		/* Check if we have a set of arguments; if so, treat as an input array */
		if (arguments.length >= 2) { matchStrings = arguments; }
		if ( typeof matchStrings == 'string' ) { matchStrings = [matchStrings];} // Auto array-ify the single value matchStrings value
		for (var i = 0; i < matchStrings.length; i++) {
			var mtch = matchStrings[i];
			var matchLen = mtch.length;
			if ( this.length >= matchLen ) { // evaluate potential match
				if ( this.indexOf(mtch) === 0 ) {
					// Success
					return true;
				}
			}
		}
		return false;
	}
	
	/*
	Removes a given string or array of strings from the beginning and end of the current String
	Example:
	"dan broke this".trim("dan") === " broke this"
	"dan broke this".trim(["dan", "this"]) === " broke "
	"dan broke this".trim([" ", "dan", "this"]) === "broke";
	*/
	this.trim = function(matchStrings, ignoreCase) {
		
		/* Check if we have a set of arguments; if so, treat as an input array */
		if ( arguments[0] instanceof Array ) {
			return trimHelper(this, arguments[0], TRIM_ALL, ignoreCase);
		} else {
			var aLen = arguments.length;
			if ( (aLen >= 2 && typeof arguments[aLen - 1] == "boolean" ) || (aLen >= 1 && typeof arguments[aLen - 1] == "string") ) {
				if ( typeof arguments[aLen - 1] == "boolean" ) {
					ignoreCase = arguments[aLen - 1];// .splice Won't work because arguments is not an Array! .splice(aLen - 1, 1);
					// Don't edit arguments: arguments[aLen - 1] = null;
				}
				var newMatches = [];//new Array
				for (var i = 0; i < arguments.length; i++) {
					if ( typeof arguments[i] == "string" ) newMatches.push(arguments[i]);
				}
				matchStrings = newMatches;
			}
		}
		return trimHelper(this, matchStrings, TRIM_ALL, ignoreCase);
	}
	/*
	Removes a given string (or array of strings) from the beginning of the current String
	*/
	this.trimLeft = function(matchStrings, ignoreCase) {
		/* Check if we have a set of arguments; if so, treat as an input array */
		var aLen = arguments.length;
		if ( (aLen >= 2 && typeof arguments[aLen - 1] == "boolean" ) || (aLen >= 1 && typeof arguments[aLen - 1] == "string") ) {
			if ( typeof arguments[aLen - 1] == "boolean" ) {
				ignoreCase = arguments[aLen - 1];// .splice Won't work because arguments is not an Array! .splice(aLen - 1, 1);
				arguments[aLen - 1] = null;
			}
			matchStrings = arguments;
		}
		return trimHelper(this, matchStrings, TRIM_LEFT, ignoreCase);
	}
	/*
	Removes a given string (or array of strings) from the end of the current String
	*/
	this.trimRight = function(matchStrings, ignoreCase) {
		/* Check if we have a set of arguments; if so, treat as an input array */
		var aLen = arguments.length;
		if ( (aLen >= 2 && typeof arguments[aLen - 1] == "boolean" ) || (aLen >= 1 && typeof arguments[aLen - 1] == "string") ) {
			if ( typeof arguments[aLen - 1] == "boolean" ) {
				ignoreCase = arguments[aLen - 1];// .splice Won't work because arguments is not an Array! .splice(aLen - 1, 1);
				arguments[aLen - 1] = null;
			}
			matchStrings = arguments;
		}
		return trimHelper(this, matchStrings, TRIM_RIGHT, ignoreCase);
	}
	/*
		@description htmlEncode() does basic encoding on < > 
		TODO: See if this regex can be made faster with manual iteration with indexOf!!!!
	*/
		this.htmlEncode = function() {
			return this.replace(/&/g, '&amp;').replace(/\"/g, '&quot;').replace(/\'/g, '&#039;').replace(/&/g, '&amp;').replace(/&/g, '&amp;');
		};
	
	/*
	@description The htmlEncodeForced function replaces ANY non alpha-numeric chars with corresponding HTML decimal-encoded entities
		The 'Forced' suffix indicates EVERYTHING not matching [a-zA-Z0-9] will be encoded
	*/
		this.htmlEncodeForced = function() {
	    var output = [];
			var rawText = this;
	    
	    for (var i = 0; i < rawText.length; i++) {
				var chr     = rawText.charAt(i),
						chrDecimal = rawText.charCodeAt(i);
				if (isNaN(chrDecimal)) { continue; }
				if (safeChars.indexOf(chr) > -1) {
				   output.push(chr);
				} else {
					// Example Hex encoded value: output.push("&#x" + parseInt(charDecimal).toString(16).padLeft(4, "0") + ";");
					// Use simpler, decimal-based 'Numeric Character Reference'
					output.push("&#" + chrDecimal + ";");
				}
	    }
	    return output.join('');
		};
	
	/*
	@description urlEncode() is a shorthand extension wrapper for encodeURIComponent (or the not-to-be-used escape() function)
	*/
		this.urlEncode = function() {
			if ( (typeof this == 'undefined' || this == null) ) return "";
			return encodeURIComponent(this + "");
		};
	/*
	@description urlDecode() is a shorthand extension wrapper for decodeURIComponent (or the not-to-be-used unescape() function)
	*/
		this.urlDecode = function() {
			if ( (typeof this == 'undefined' || this == null) ) return "";
			return decodeURIComponent(this + "");
		};
		/*
			urlEncodeForced(): The 'Forced' suffix indicates EVERYTHING not matching [a-zA-Z0-9] will be encoded
			This will reliably encode up to UTF-16 (multi-byte) chars. Helps ensure safe traversal of data to servers.
			 Note: Don't use escape() because it doesn't reliably handle non ASCII chars, so UTF-8 has plenty chars it'll mess up
		*/
		this.urlEncodeForced = function() {
			var rawText = this;
	    var output = [];
	    for (var i = 0; i < rawText.length; i++) {
				var chr     = rawText.charAt(i),
						charDecimal = rawText.charCodeAt(i);
				if (isNaN(charDecimal)) { continue; }
				if (safeChars.indexOf(chr) > -1) {
				   output.push(chr);
				} else {
					charHex = parseInt(charDecimal).toString(16); 
					/* Info: A Comma === hex val: 2C && decimal value === 44 */
				  if ( charHex.length === 2 ) {charDecimal = charHex;}
				  if ( charHex.length === 3 ) {charDecimal = "u0" + charHex;}
					if ( charHex.length >= 4 ) {charDecimal = "u" + charHex;}
					// By now, charHex.length MUST be 3 (for Hex 00-FF) or 5 (for double byte Hex values - e.g. 084A ) ...
				  output.push("%" + charDecimal);
				}
	    }
	    return output.join('');
		};
		/*
		makeNumeric does not return a real Number
		 type or anything like that, it is just really fast at removing everything that doesn't match [0-9.]
		 But it returns a String!
		*/
		this.makeNumeric = function() {
			var rawText = this.split("");
	    var output = [];
			var nums = integerChars + ".";
	    for (var i = 0; i < rawText.length; i++) {
				if ( nums.indexOf(rawText[i]) > -1 )
				  output.push(rawText[i]);
			}
	    return output.join('');
		};
		/*
		formatNumber returns a String - with commas if requested
		*/
		this.formatNumber = function(decimalPlaces, includeCommas) {
			var rawText =  this.makeNumeric(),
				output = [],
				nums = integerChars + ".",
				decimalValue = "";

			var decimalPoint = (rawText.indexOf(".") > -1 ? rawText.indexOf(".") - 1 : rawText.length);
	    for (var i = 0; i <= decimalPoint; i++) {// Here we start at the decimalPoint, and then work our way out
				if ( includeCommas && (i + 1) % 3 === 0 ) {// Use a modulus to keep shit tight
					output.push(",") // insert , at every 3rd char
				}
				output.push(rawText[i]); // keep adding the chars back
			}
			//output.reverse();
			
			// Now get the amount of decimalPlaces NOTE: This could be faster if we strictly stuck to multiplication, but considering this function is more about formatting than math , iterating through the String as a Char array isn't too bad ;)
			if ( decimalPlaces ) {
				decimalValue = "." + rawText.substring(rawText.indexOf(".") + 1).left(decimalPlaces);
			}
			
	    return output.join('') + decimalValue;
		};
		/*
		left(int length) 
		@description This is a wrapper/helper function - this makes truncating strings syntactically awesome!
		*/
		this.left = function(maxLength) {
			if ( (typeof this == 'undefined' || this == null) ) return "";
			// Check the length of this vs. maxLength
			if ( this.length < maxLength ) { return this; }
			if ( maxLength === 0 ) { return ""; }
			// return the requested substring
			return this.substring(0, maxLength);
		};
	
		/*
		right(int length) 
		@description This is a wrapper/helper function - this makes truncating strings syntactically awesome!
		*/
		this.right = function(maxLength) {
			if ( (typeof this == 'undefined' || this == null) ) return "";
			// Check the length of this vs. maxLength
			if ( this.length < maxLength ) { return this; }
			if ( maxLength === 0 ) { return ""; }
			// return the requested substring
			return this.substring(this.length - maxLength);
		};
	
	/*
		@description the padLeft and padRight functions are intended to handle padding lengths between 1-5, not for handling huge strings (though they will be processed if needed for some reason)
	*/
		this.padLeft = function(maxLength, paddingChar) {
	    var output = this.split('');
	
			if ( output.length >= maxLength ) return this;// No need to modify, the string is longer than the maxLength var
			if ( typeof paddingChar == 'undefined' || paddingChar == null || paddingChar.length < 1 ) { // Set a default 
				paddingChar = " "; // Use a SPACE character
			}
			while ( output.length < maxLength ) {
				output.splice(0, 0, paddingChar);
			}
			return output.join('');
		};
	/*
		@description the padRight and padRight functions are intended to handle maxLength between 1-5, not for handling huge strings (though they will be processed if needed for some reason)
	*/
		this.padRight = function(maxLength, paddingChar) {
	    var output = this.split('');
			if ( output.length >= maxLength ) return this;// No need to modify, the string is longer than the maxLength var
			if ( typeof paddingChar == 'undefined' || paddingChar == null || paddingChar.length < 1 ) { // Set a default 
				paddingChar = " "; // Use a SPACE character
			}
			while ( output.length < maxLength ) {
				output.push(paddingChar);
			}
			return output.join('');
		};
	
	/*
	* TODO: Add LZ77 (Client-side compression) functions
	* 
	*/
	
	/*
	obfuscateEmail() - COMPLETELY ENCODE ALL CHARS TO HTML Entity Encoding - some bots are hip to this (see notes below)
	Notes:
	- The following functions (ObfuscateEmail) may seem too specialized for a String extension method,
		 or perhaps less useful than the Server-side version (after all, this is to make it a bit harder for simple harvester bots trying to find email address floating around the web).
	- Nonetheless, I think this could be useful in JS, for example: generating client-side HTML - (something like a 'HTML Generator for Craigslist')
		 also this will probably be used on our NodeJS platform work!!!	
	- TODO: Add support for rendering out random substring fragments into a couple html <div> elements, requiring JS post-processiong to re-assemble & render out
	*/
		this.obfuscateEmail = function() {
			var email = this;
	    var output = [];
	    
	    for (var i = 0; i < email.length; i++) {
				var charDecimal = email.charCodeAt(i);
				if (isNaN(charDecimal)) { continue; }
				output.push("&#" + charDecimal + ";");
	    }
	    return output.join('');
		};
	}
	
	// LET'S GET THIS SHOW ON THE ROAD!!!
	initStringHelpers();

})();
	
