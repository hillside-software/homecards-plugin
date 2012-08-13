
//jQuery('#' + sel.attr('id') + "_auto").tokenInput('http://www.myhomecards.com/AjaxHandler.aspx?Action=GetFieldOptions&FieldName=' + sel.attr('name') + '&MLS=DEN', {crossDomain: true, allowCustomEntry: true, searchDelay: 400, jsonContainer: 'data',  theme: "facebook", preventDuplicates: true } ); /* pre-set default values: prePopulate: optObjectList, */
//.tokenInput('http://www.myhomecards.com/AjaxHandler.aspx?Action=GetFieldOptions&FieldName=' + sel.attr('name') + '&MLS=DEN', {crossDomain: true, allowCustomEntry: true, searchDelay: 400, jsonContainer: 'data',  theme: "facebook", preventDuplicates: true } ); /* pre-set default values: prePopulate: optObjectList, */
function autoCreateAutoComplete(sel) {
	var splitCsv = function (val) { return val.split( /,\s*/ ); }
	var extractLast = function (term) { return splitCsv(term).pop(); }
	jQuery('#' + sel.attr('id') + "_auto")
	// don't navigate away from the field on tab when selecting an item
	.bind( "keydown", function( event ) {
		if ( event.keyCode === $.ui.keyCode.TAB && $( this ).data( "autocomplete" ).menu.active ) {
			event.preventDefault();
		}
	})
	.autocomplete({
		source: function( request, response ) {
			$.getJSONP( 'http://www.myhomecards.com/AjaxHandler.aspx?Action=GetFieldOptions&FieldName=' + sel.attr('name') + '&MLS=DEN', {
				Query: extractLast(request.term)
			}, response );
		},
		search: function() {
			// custom minLength
			var term = extractLast( this.value );
			if ( term.length < 2 ) {
				return false;
			}
		},
		focus: function() {
			// prevent value inserted on focus
			return false;
		},
		select: function( event, ui ) {
			var terms = splitCsv(this.value);
			// remove the current input
			terms.pop();
			// add the selected item
			terms.push( ui.item.value );
			// add placeholder to get the comma-and-space at the end
			terms.push( "" );
			this.value = terms.join( ", " );
			return false;
		}
	});
}