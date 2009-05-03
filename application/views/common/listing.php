<h2><?php echo $title; ?></h2>

<?php echo menu::render($alphanum, "alpha", TRUE, 3); ?>

<ul id="listing">
<?php
	foreach ($entries as $entry) {
		echo '<li>' . html::anchor('users/details/'.$entry['id'], $entry['name']) . '</li>';
	}
?>
</ul>
