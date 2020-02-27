<?php
require_once('../class.php');
$session = new sessionManage();
$session->redirectIfLoggedIn();//If logged in redirect to account page
?>
<html lang="en">
<head>
    <title>Register</title>
    <style>.username {display: none}</style>
</head>
<body>
<form method="post" action="register_handle.php">
    <label for="THE_username">Create username:</label>
    <input class="username" id="username" name="username" minlength="3" maxlength="24" type="text">
    <input type="text" minlength="3" maxlength="24" aria-label="username" class="form-control" name="THE_username"
           id="THE_username"
           aria-describedby="username"
           placeholder="Create Username" required>
    <label for="THE_email">Your Email:</label>
    <input type="email" minlength="6" maxlength="60" aria-label="email" class="form-control" name="THE_email"
           id="THE_email"
           aria-describedby="email"
           placeholder="Your email" required>
    <label for="THE_password">Create password:</label>
    <input type="password" minlength="8" maxlength="54" aria-label="password" class="form-control" name="THE_password"
           id="THE_password" placeholder="Password" required>
    <button type="submit" value="submit" class="btn">Create</button>
</form>
</body>
</html>