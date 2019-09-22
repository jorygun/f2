

 <h3 style="border-top:1px solid black;">Actions on this record</h3>
Fields left blank will not be changed.
	<form method="post">
		<input type='hidden' name ='uid' value ='<?=$uid?>'>
	 	<table>
	 		<columns>
	 		<col width="50%">
	 		<col width="50%">
	 		</columns>

	 	<tr><td><p><b>Change email address</b><br>This will change email_status to E1 and send out a verification email. This change will occur before any of the other actions listed below. If suggested by
	 	someone else is checked, then an explanatory email also goes to the new
	 	address.</p> </td><td><input type='text' name = 'new_email' size=60>
	 	<br><input type=checkbox name='suggested_email' id='suggested_email' >New Email suggested by someone else. <input type=text id='informant' name='informant' placeholder='Another FLAME member'oninput="check_the_box('suggested_email',true);"></td></tr>

	 	<tr style="background-color:#F90; ">
	 		<td><p><b>Update user status</b><?=$new_warning?></td>
	 		<td><select name='new_status'><?=$user_status_options?></select></td>
	 	</tr>

	 	<tr><td><p><b>Change User Name</b><br></p></td><td>
	 	New User Name: <input type='text' name='new_name' size=40></td></tr>



	  	<tr><td><b>No Bulk</b> Set/Clear the No Bulk tag for this users.</td><td>No Bulk <input type="checkbox" name=nobulk <?=$nobulkchecked?> >
	  	<input type='hidden' name='nobulkchecked' value='<?=$nobulkchecked?>' >
	  	</td></tr>

	  	<tr><td><b>Change Email Status</b> <?=$validateEmailButton?></td> <td>email_status (currently ${mdd['email_status']} ):
	  	<select name='email_status'><option value=''>Leave as ${mdd['email_status']}</option>
	  		<?=$email_status_options?></select><br>
	  		(Note: changing to A1 will send a validation email.)

	  	</td></tr>
	  		
	  	<tr><td><b>Admin Status</b></td><td>(currently ${mdd['admin_status']}):
	  	<input type="text" size="4" name="admin_status">
	  	</td></tr>
	
		<tr><td><b>Test Status</b></td><td>(currently ${mdd['test_status']}):
	  	<input type="text" size="4" name="test_status">
	  	</td></tr>
	  	
	  

	  	<tr><td><p><b>Update user's current information.</b> For deceased members, indicate date and other info.</td><td>
	  	<textarea  name='current' cols = '40' rows = '8'>${mdd['user_current']}</textarea></td></tr>

	  	<tr><td><p><b>Update the Admin Note.</b>  </td><td>
	  	<textarea  name='admin_note' cols = '40' rows = '8'>${mdd['admin_note']}</textarea></td></tr>

	  


	  	<tr><td ><input type='submit' name='Update' value='Update' style='background:#6F6; width:12em;'></td><td></td></tr>

	  	</table>

		</form>

<hr>
