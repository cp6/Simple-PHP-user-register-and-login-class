<?php

/*
 * Config values and database connection
 */

class configAndConnect
{
    //Website full domain URL
    CONST URL = 'http://127.0.0.1/view_beta/';

    //Website name
    CONST WEBSITE_NAME = '';

    //Failed attempts before account temp locked for 10 mins
    CONST FAIL_ATTEMPTS_ALLOWED = '4';

    //Accounts must be activated from email
    CONST REQUIRE_EMAIL_ACTIVATION = true;

    //Settings for email activation sender
    CONST EMAIL_ADDRESS = '';
    CONST EMAIL_HOST = '';
    CONST SMTP_PORT = '';
    CONST SMTP_USERNAME = '';
    CONST SMTP_PASSWORD = '';

    //MySQL server connection details
    CONST DB_HOSTNAME = '127.0.0.1';
    CONST DB_NAME = 'auth';
    CONST DB_USERNAME = 'root';
    CONST DB_PASSWORD = '';

    public function db_connect(): object
    {
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);
        return new PDO("mysql:host=" . self::DB_HOSTNAME . ";dbname=" . self::DB_NAME . ";charset=utf8mb4", self::DB_USERNAME, self::DB_PASSWORD, $options);
    }
}

/*
 * Handle registering, detail sanitization and activation email sending
 */

class doRegisterAttempt extends configAndConnect
{
    private string $username;
    private string $stated_password;
    private string $email;
    private int $uid;
    private string $key;

    public function __construct($username, $password, $email)
    {
        $this->username = $username;
        $this->stated_password = $password;
        $this->email = $email;
    }

    public function db_connect(): object
    {
        return (new configAndConnect)->db_connect();
    }

    public function attemptRegister(): string
    {
        if ($this->validateUsername() == 1 && $this->validatePassword() && $this->validatePassword()) {
            $this->insertAccount();
            if (configAndConnect::REQUIRE_EMAIL_ACTIVATION) {
                $this->generateActivateKey();
                $this->sendVerifyEmail();
                return "Account registered, please check for activation email";
            } else {
                $this->manualActivateAccount();
                return "Account registered and activated";
            }
        } elseif ($this->validateUsername() == 2) {
            return "Username already exists, please choose another one";
        } elseif ($this->validateUsername() == 3) {
            return "a{$this->validateUsername()}a Username must be between 3 and 24 characters in length";
        }
    }

    public function validateUsername(): int
    {
        $db = $this->db_connect();
        if (strlen($this->username) >= 3 && strlen($this->username) <= 24) {
            $select = $db->prepare("SELECT `uid` FROM `users` WHERE `username` = :username LIMIT 1;");
            $select->execute(array(':username' => $this->username));
            if ($select->rowCount() > 0) {//Row found for username
                //Username already exists
                return 2;
            } else {
                //Can use username
                return 1;
            }
        } else {
            //Username must be between 3 and 24 characters in length
            return 3;
        }
    }

    public function validatePassword(): bool
    {
        if (strlen($this->stated_password) >= 8 && strlen($this->stated_password) <= 54) {
            return true;
        } else {
            //Password must be between 3 and 54 characters in length
            return false;
        }
    }

    public function validateEmail(): bool
    {
        if (filter_var($this->email, FILTER_VALIDATE_EMAIL) && strlen($this->email) <= 60) {
            return true;//Valid email address
        } else {
            return false;
        }
    }

    public function insertAccount(): void
    {
        $hashed_password = password_hash($this->stated_password, PASSWORD_DEFAULT);//Hash the submitted password
        $db = $this->db_connect();
        $insert = $db->prepare("INSERT IGNORE INTO `users` (`username`, `password`, `email`) VALUES (?,?,?)");
        $insert->execute([$this->username, $hashed_password, $this->email]);//Create the user you defined in the form
        $this->uid = $db->lastInsertId();
    }

    public function manualActivateAccount(): void
    {
        $db = $this->db_connect();
        $update = $db->prepare("UPDATE `users` SET `activated` = 1 WHERE `uid` = :uid LIMIT 1;");
        $update->execute(array(':uid' => $this->uid));
    }

    public function generateActivateKey(): void
    {
        $this->key = substr(md5(rand()), 0, 24);
        $db = $this->db_connect();
        $insert_key = $db->prepare("INSERT IGNORE INTO `activate_keys` (`key`, `uid`) VALUES (?, ?)");
        $insert_key->execute([$this->key, $this->uid]);
    }

    public function sendVerifyEmail(): void
    {
        require_once('PHPMailer/PHPMailer.php');
        require_once('PHPMailer/SMTP.php');
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = configAndConnect::EMAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Port = configAndConnect::SMTP_PORT;
        $mail->Username = configAndConnect::SMTP_USERNAME;
        $mail->Password = configAndConnect::SMTP_PASSWORD;

        $mail->setFrom(configAndConnect::EMAIL_ADDRESS, configAndConnect::WEBSITE_NAME);
        $mail->addAddress($this->email, 'New Account');

        $mail->isHTML(true);
        $mail->Subject = 'Account activation';
        $mail->Body = "<a href='" . configAndConnect::URL . "activate.php?key={$this->key}'>Click here to activate<a>";
        $mail->AltBody = "" . configAndConnect::URL . "activate.php?key={$this->key} URL to activate";
        $mail->send();
    }

    public function verifyAccount($key): bool
    {
        $db = $this->db_connect();
        $select = $db->prepare("SELECT `uid` FROM `activate_keys` WHERE `key` = :key LIMIT 1;");
        $select->execute(array(':key' => $key));
        if ($select->rowCount() > 0) {//Row found for key
            $result = $select->fetch();
            $update = $db->prepare("UPDATE `users` SET `activated` = 1 WHERE `uid` = :uid LIMIT 1;");
            $update->execute(array(':uid' => $result['uid']));
            $delete = $db->prepare("DELETE FROM `activate_keys` WHERE `key` = :key LIMIT 1;");
            $delete->execute(array(':key' => $key));
            //Account activated
            return true;
        } else {
            //Key is invalid
            return false;
        }
    }

}

/*
 * Handle login attempts and authentication
 */

class doLoginAttempt extends configAndConnect
{
    private string $username;
    private string $stated_password;
    private string $real_password;
    private string $ip_address;
    public int $uid;

    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->stated_password = $password;
        $this->ip_address = $_SERVER['REMOTE_ADDR'];
    }

    public function db_connect(): object
    {
        return (new configAndConnect)->db_connect();
    }

    public function getUserDataForUsername(): bool
    {
        $db = $this->db_connect();
        $select = $db->prepare("SELECT `uid`, `username`, `password` FROM `users` WHERE `username` = :username LIMIT 1;");
        $select->execute(array(':username' => $this->username));
        if ($select->rowCount() > 0) {//Row found for username
            $result = $select->fetch();
            $this->uid = $result['uid'];
            $this->real_password = $result['password'];
            return true;
        } else {//Username not found
            return false;
        }
    }

    public function checkPasswordCorrect(): bool
    {
        if (password_verify($this->stated_password, $this->real_password)) {
            return true;//Password is correct
        } else {
            return false;//Bad password
        }
    }

    public function doLoginWasSuccess(): void
    {
        $db = $this->db_connect();
        $update = $db->prepare("UPDATE `users` SET `login_count` = (login_count + 1), `last_login_at` = NOW(), `last_login_ip` = :last_ip WHERE `uid` = :uid LIMIT 1;");
        $update->execute(array(':last_ip' => $this->ip_address, ':uid' => $this->uid));
    }

    public function addLoginFailCount(): void
    {
        $db = $this->db_connect();
        $update = $db->prepare("UPDATE `users` SET login_fails = (login_fails + 1), `last_fail` = NOW() WHERE `username` = :username LIMIT 1;");
        $update->execute(array(':username' => $this->username));
    }

    public function addLoginFailAttempt(): void
    {
        $db = $this->db_connect();
        $insert = $db->prepare('INSERT IGNORE INTO `login_attempts` (`username`, `ip`) VALUES (?, ?)');
        $insert->execute([$this->username, $this->ip_address]);
    }

    public function getRecentFailCount(): int
    {
        $db = $this->db_connect();
        $select = $db->prepare("SELECT COUNT(*) as the_count FROM `login_attempts` WHERE `ip` = :ip AND `datetime` > (NOW() - INTERVAL 10 MINUTE);");
        $select->execute(array(':ip' => $this->ip_address));
        return $select->fetch()['the_count'];//login fails for IP in last 10 minutes
    }

    public function attemptLogin($redirect_to = '')
    {
        if ($this->getRecentFailCount() >= configAndConnect::FAIL_ATTEMPTS_ALLOWED) {//IP has had X or more fails in last 10 mins
            return "IP Address has been locked for 10 minutes";
        }
        if ($this->getUserDataForUsername()) {//Username found
            if ($this->checkPasswordCorrect()) {//Password is correct
                $this->doLoginWasSuccess();
                session_start();
                $_SESSION['user'] = $this->uid;//Set session as uid
                header("Location: $redirect_to");
                exit;
            } else {//Password is wrong
                $this->addLoginFailCount();//Add 1 onto login fail count
                $this->addLoginFailAttempt();//ip and datetime into login attempt fail logs
                //return "Password is wrong for {$this->username}";//Dont use this, helps brute forcing.
                return "Failed login";//Be vague in error response
            }
        } else {
            //return "Username: {$this->username} not found in DB";
            return "Failed login";//Be vague in error response
        }
    }
}

/*
 * Handles sessions: 'visitor is logged in', logout
 */

class sessionManage extends configAndConnect
{
    public int $uid;

    public function sessionStartIfNone()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();//No session stated... so start one
        }
    }

    public function checkIsLoggedIn($redirect = true, $redirect_to = "" . configAndConnect::URL . "login/"): bool
    {
        $this->sessionStartIfNone();//Start session if none already started
        if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
            $this->uid = $_SESSION['user'];
            return true;//Logged in
        } else {
            if ($redirect) {//Not logged in and do a redirect
                header("Location: $redirect_to");
                exit;
            }
            return false;
        }
    }

    public function redirectIfLoggedIn($redirect_to = "" . configAndConnect::URL . "account/")
    {
        $this->sessionStartIfNone();//Start session if none already started
        if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
            $this->uid = $_SESSION['user'];
            header("Location: $redirect_to");
            exit;
        }
    }

    public function logout($redirect = false, $redirect_to = ''): bool
    {
        $this->sessionStartIfNone();
        if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {//Logged in
            session_destroy();
            unset($_SESSION['user']);
            $db = (new configAndConnect)->db_connect();
            $update = $db->prepare("UPDATE `users` SET `logged_out` = NOW() WHERE `uid` = :uid LIMIT 1;");
            $update->execute(array(':uid' => $this->uid));
            if ($redirect) {//Redirect after logout
                header("Location: $redirect_to");
                exit;
            } else {
                return true;
            }
        } else {//Was not logged in to begin with
            return false;
        }
    }


    public function IsAccountActivated(): bool
    {
        $db = (new configAndConnect)->db_connect();
        $select = $db->prepare("SELECT `activated` FROM `users` WHERE `uid` = :uid LIMIT 1;");
        $select->execute(array(':uid' => $this->uid));
        if ($select->fetch()['activated']) {
            return true;//Yes
        } else {
            return false;//No
        }
    }

}

/*
 * Details and data for logged in account
 */

class accountDetails extends configAndConnect
{
    public int $uid;

    public function __construct()
    {
        $this->uid = $_SESSION['user'];
    }

    public function accountData(): array
    {
        $db = (new configAndConnect)->db_connect();
        $select = $db->prepare("SELECT `username`, `created`, `login_count`, `login_fails`, `last_fail`, `email` FROM `users` WHERE `uid` = :uid LIMIT 1;");
        $select->execute(array(':uid' => $this->uid));
        $result = $select->fetch();
        return array(
            'uid' => $this->uid,
            'username' => $result['username'],
            'created' => $result['created'],
            'login_count' => $result['login_count'],
            'login_fails' => $result['login_fails'],
            'last_fail' => $result['last_fail'],
            'email' => $result['email']
        );
    }
}