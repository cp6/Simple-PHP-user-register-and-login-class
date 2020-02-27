<?php
require_once('../class.php');
$session = new sessionManage();
$logged_in = $session->checkIsLoggedIn(true, '' . configAndConnect::URL . 'login/');//If not logged redirect to login page

$is_activated = $session->IsAccountActivated();
if ($is_activated) {//Account is activated
    $user_details = new accountDetails($_SESSION['user']);
    $user_details_array = $user_details->accountData();//Array for user account details

    echo "Welcome {$user_details_array['username']}<br>";
    echo "Your user id: {$user_details_array['uid']}<br>";
    echo "Account created: {$user_details_array['created']}<br>";
    echo "Account email: {$user_details_array['email']}<br>";
    echo "Logged in: {$user_details_array['login_count']} times<br>";
    echo "Failed login attempts: {$user_details_array['login_fails']}<br>";
    echo "Last Failed login attempt: {$user_details_array['last_fail']}<br>";
    echo "<a href='../logout/'>Logout</a>";
} else {
    echo "Please check for activation email";
}