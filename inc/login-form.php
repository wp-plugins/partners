<?php foreach( $form->errors as $error ): ?>
	<div class="mighty-form-error"><?php echo $error; ?></div>
<?php endforeach; ?>
<div class="mighty-field">
	<label class="mighty-field-label" for="email">Email</label>
	<input class="mighty-field-input mighty-input-text" name="email" value="<?php echo $form->fields['email']->value; ?>" type="text">
</div>
<div class="mighty-field">
	<label class="mighty-field-label" for="password">Password</label>
	<input class="mighty-field-input mighty-input-text" name="password" value="" type="password">
</div>
<div class="mighty-control">
	<input class="mighty-control-submit" type="submit" name="submit" value="Submit">
</div>
<p><a href="/partners/forgot-password/">I forgot my password.</a></p>
<p><a href="/partners/register/">Register</a> to become a partner?</p>
