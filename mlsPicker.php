<?php
function hc_render_mls_picker() { 
	if(isset($_POST['submit'])) { 
		session_start();
		$_SESSION['mls'] = $_POST['mls']; 
		// echo "<strong style='font-size:1.4em'>You are now searching the <em>".$_SESSION['mls']."</em> database.</strong>"; 
	} ELSE { 
		// echo "<strong style='font-size:1.4em'>You are now searching the <em>Metrolist</em> database.</strong>"; 
	}
	?>
	<form name="mlsPicker" method="POST">
		<label for="mlsProvider">
			Please choose the MLS you would like to search: 
		</label>
		<select name="mls" size='1'>
			<option value="DEN">
				Denver/Central-Colorado
			</option>
			<option value="PPA">
				Pike's Peak 
			</option>
			<option value="IRE">
				Boulder
			</option>
		</select>
		<input type="submit" name="submit" value="Change Database" />
	</form>
	<?php
}