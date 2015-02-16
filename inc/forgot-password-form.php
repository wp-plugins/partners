<?php foreach( $form->errors as $error ): ?>
	<div class="mighty-form-error"><?php echo $error; ?></div>
<?php endforeach; ?>
<div class="mighty-field">
	<label class="mighty-field-label" for="email">Email</label>
	<input class="mighty-field-input mighty-input-text" name="email" value="<?php echo $form->fields['email']->value; ?>" type="text">
</div>
<div class="mighty-control">
	<input class="mighty-control-submit" type="submit" name="submit" value="Submit">
</div>
