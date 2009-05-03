<h2>Login</h2>

<form action="<?php echo url::base();?>users/login/" method="post">
	<?php echo (isset($errors)) ? '<p class="error">' . $errors . '</p>' : ''; ?>
	<p><label for="username">Username</label><input type="text" name="username" id="username"></p>
	<p><label for="password">Password</label><input type="password" name="password" id="password"></p>
	<p><label>&nbsp;</label><input type="submit" value="Login"></p>
</form>
