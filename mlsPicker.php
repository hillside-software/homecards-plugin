<?php
function hc_render_mls_picker() { 
	if(isset($_POST['submit'])) { 
		session_start();
		$_SESSION['mls'] = $_POST['mls']; 
	} ELSE { 
	}
	?>
	<form name="mlsPicker" method="POST">
		<label for="mlsProvider">
			Please choose the MLS you would like to search: 
		</label>
		<select name="mls" size='1'>
			<option value="">
				Denver/Central-Colorado
			</option>
			<option value="IRE">
				Boulder
			</option>
		</select>
		<input type="submit" name="submit" value="Change Database" />
	</form>
	<?php
}