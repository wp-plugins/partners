<div class="wrap">

	<!--<div class="icon32" id="icon-options-general"><br></div>-->

	<?php if (!empty($status)): ?>
	<div class="<?php echo $status['error'] ? 'error' : 'updated'; ?> fade" id="message"><p><strong><?php echo $status['message']; ?></strong></p></div>
	<?php endif; ?>

	<h2>Edit Approved Email</h2>

	<p>This is the email that will be sent to a user when they are approved.</p>

	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
	<?php echo $nonce; ?>

	<table class="form-table">
	<tbody>
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
		<td><textarea class="large-text code" cols="50" rows="10" name="<?php echo $option_name . '[' . $n . ']'; ?>"><?php if (!empty($option[$n])) echo stripslashes($option[$n]); ?></textarea>
		<p style="color:#666;font-size:12px;">Available shortcodes: <strong>{site_url}</strong> the site's url, <strong>{password}</strong> the randomly generated password for the user (only generated if the user does not have a password set), <strong>{password_message}</strong> the message that includes the users generated password (e.g. A random password has been generated for you: Abx#49iopE09)</p></td>
	</tr>
	</tbody>
	</table>

	<p class="submit"><input type="submit" value="Save Changes" class="button-primary" name="Submit"></p>

	</form>

</div>
