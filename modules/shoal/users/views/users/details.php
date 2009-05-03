<h2>User Details - <?php echo $user->name; ?></h2>

<p>Username: <?php echo $user->username; ?></p>
<p>DOB: <?php echo $user->dob->date; ?></p>
<p>Gender: <?php echo strtoupper($user->gender); ?></p>
<p>Joined: <?php echo $user->joined->date; ?></p>
