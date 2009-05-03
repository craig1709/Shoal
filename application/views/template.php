<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Shoal</title>
		<link rel="stylesheet" href="<?php echo url::base(); ?>assets/css/master.php" type="text/css">
	</head>
	<body>
	
		<div id="wrapper">
		
			<div id="header">
				<h1>Shoal</h1>
				<div id="user-box"><?php echo ($loggedin) ? html::anchor('users/logout','Logout') : html::anchor('users/login','Login') ; ?></div>
			</div>
			
			<?php echo menu::render($menu); ?>
			
			<div id="content">
				<?php echo $content; ?>
			</div>
		
		</div>
	
	</body>
</html>
