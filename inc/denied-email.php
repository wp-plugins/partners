<div class="wrap">

	<!--<div class="icon32" id="icon-options-general"><br></div>-->

	<?php if (!empty($status)): ?>
	<div class="<?php echo $status['error'] ? 'error' : 'updated'; ?> fade" id="message"><p><strong><?php echo $status['message']; ?></strong></p></div>
	<?php endif; ?>

	<h2>Edit Denied Email</h2>

	<p>This is the email that will be sent to a user when denied. This email is optional, <br/>
	if you choose not to send this email, the user will not get any kind of notice.</p>

	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
	<?php echo $nonce; ?>

	<table class="form-table">
	<tbody>
	<tr valign="top">
		<th scope="row">Send Denied Emails?</th><?php $n = 'send_email'; ?>
		<td><input type="checkbox" name="<?php echo $option_name . '[' . $n . ']'; ?>" value="1"<?php if (!empty($option[$n])) echo ' checked="checked"'; ?>/> When a user is denied, this email will be sent to them.</td>
	</tr>
	<tr valign="top">
		<th scope="row">From Email</th><?php $n = 'from_email'; ?>
		<td><input style="width:99%" type="text" name="<?php echo $option_name . '[' . $n . ']'; ?>" value="<?php if (!empty($option[$n])) echo stripslashes($option[$n]); ?>"></td>
	</tr>
	<tr valign="top">
		<th scope="row">From Name <span>(optional)</span></th><?php $n = 'from_name'; ?>
		<td><input style="width:99%" type="text" name="<?php echo $option_name . '[' . $n . ']'; ?>" value="<?php if (!empty($option[$n])) echo stripslashes($option[$n]); ?>"></td>
	</tr>
	<tr valign="top">
		<th scope="row">Message Subject</th><?php $n = 'subject'; ?>
		<td><input style="width:99%" type="text" name="<?php echo $option_name . '[' . $n . ']'; ?>" value="<?php if (!empty($option[$n])) echo stripslashes($option[$n]); ?>"></td>
	</tr>
	<tr valign="top">
		<th scope="row">Message Body</th><?php $n = 'body'; ?>
		<td><textarea class="large-text code" cols="50" rows="10" name="<?php echo $option_name . '[' . $n . ']'; ?>"><?php if (!empty($option[$n])) echo stripslashes($option[$n]); ?></textarea></td>
	</tr>
	</tbody>
	</table>

	<p class="submit"><input type="submit" value="Save Changes" class="button-primary" name="Submit"></p>

	</form>

</div>
