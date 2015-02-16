<?php foreach( $form->errors as $error ): ?>
	<div class="mighty-form-error"><?php echo $error; ?></div>
<?php endforeach; ?>
<div class="mighty-field">
	<label class="mighty-field-label" for="first_name">First Name</label>
	<input class="mighty-field-input mighty-input-text" name="first_name" value="<?php echo $form->fields['first_name']->value; ?>" type="text">
</div>
<div class="mighty-field">
	<label class="mighty-field-label" for="last_name">Last Name</label>
	<input class="mighty-field-input mighty-input-text" name="last_name" value="<?php echo $form->fields['last_name']->value; ?>" type="text">
</div>
<div class="mighty-field">
	<label class="mighty-field-label" for="email">Email</label>
	<input class="mighty-field-input mighty-input-text" name="email" value="<?php echo $form->fields['email']->value; ?>" type="text">
</div>
<div class="mighty-control">
	<input class="mighty-control-submit" type="submit" name="submit" value="Submit">
</div>
<p>I've already registered, <a href="/partners/login/">Login</a>.</p>
