<?php
require_once('../class.php');
$session = new sessionManage();
$session->redirectIfLoggedIn();//If logged in redirect to account page
?>
<html lang="en">
<head>
    <title>Login</title>
    <style>.username { display: none }</style>
</head>
<body>
<form method="post" action="login_handle.php">
    <label for="THE_username">Your username:</label>
    <input class="username" id="username" name="username" minlength="3" maxlength="24" type="text">
    <input type="text" minlength="3" maxlength="24" aria-label="THE_username" class="form-control" name="THE_username"
           id="THE_username"
           aria-describedby="THE_username"
           placeholder="Your Username" required>
    <label for="THE_password">Your password:</label>
    <input type="password" minlength="8" maxlength="54" aria-label="password" class="form-control" name="THE_password"
           id="THE_password"
           placeholder="Password" required>
    <button type="submit" value="submit" class="btn">Login</button>
</form>
</body>
</html>