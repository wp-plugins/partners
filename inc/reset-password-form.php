<?php foreach( $form->errors as $error ): ?>
	<div class="mighty-form-error"><?php echo $error; ?></div>
<?php endforeach; ?>
<div class="mighty-field">
	<label class="mighty-field-label" for="password">New Password</label>
	<input class="mighty-field-input mighty-input-text" name="password" value="" type="text" autocomplete="off">
</div>
<div class="mighty-field">
	<label class="mighty-field-label" for="password_confirm">Confirm New Password</label>
	<input class="mighty-field-input mighty-input-text" name="password_confirm" value="" type="text" autocomplete="off">
</div>
<div class="mighty-control">
	<input class="mighty-control-submit" type="submit" name="submit" value="Submit">
</div>
