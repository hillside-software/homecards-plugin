function checkForZero(field)
			{
			    if (field.value == 0 || field.value.length == 0) {
			        alert ("This field can't be 0!");
			        field.focus(); }
			    else
			        calculatePayment(field.form);
			}
			
			function cmdCalc_Click(form)
			{
			    if (form.price.value == 0 || form.price.value.length == 0) {
			        //alert ("The Price field can't be 0!");
			        form.price.focus(); }
			    else if (form.ir.value == 0 || form.ir.value.length == 0) {
			        //alert ("The Interest Rate field can't be 0!");
			        form.ir.focus(); }
			    else if (form.term.value == 0 || form.term.value.length == 0) {
			        //alert ("The Term field can't be 0!");
			        form.term.focus(); }
			    else
			        calculatePayment(form);
			}
			
			function calculatePayment(form)
			{
			    princ = form.price.value - form.dp.value;
			    intRate = (form.ir.value/100) / 12;
			    months = form.term.value * 12;
			    jQuery('#w-mort-pmt').html('$' + Math.floor((princ*intRate)/(1-Math.pow(1+intRate,(-1*months)))*100)/100);
			    jQuery('#w-mort-principle').html('$' + princ);
			    jQuery('#w-mort-payments').html(months);
			}
			