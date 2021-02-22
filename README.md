# Simple PHP user register and login class

PHP OOP design with injection safe PDO MySQL queries, this is an easy to read class for a user registration, login,
authentication and logout system.

### Features

This code is bare bone with no front-end design or usage aside from basic registration, login, logout and an account
page that can only be viewed when logged in.

* PDO MySQL pre-prepared queries (injection proof).
* Password hashing.
* Lock IP address from login attempt after certain amount of fails in X time.
* Honey pot login and registration form to prevent bots.
* Secure session management for user authentication.
* Login and register page cannot be viewed when logged in.
* Only 2 lines to check if user is logged in or not, redirect if not.
* Login attempt failed message suppression. Presents brute force and username guessing.
* Usage of [PHPMailer](https://github.com/PHPMailer/PHPMailer) for account activation emails.

### Requires

* PHP 7.4 minimum (tested with 7.4.2).
* MySQL database.

### Installation

1. Copy contents to working web directory.
2. Run ```authdb.sql``` into MySQL.
3. Edit ```class.php``` : ```configAndConnect```

* Add website name and url.
* Edit failed attempts allowed, lock time and if accounts need activation via email.
* Add Email SMTP details (If using account email activation)
* Add MySQL database connection details

### Usage

Ensure the class is called on your page (with relevant directory depth)

```php
require_once('class.php');
//or
require_once('../class.php');
```

**Protecting page:**

To protect a page, user must be logged in to view:

```php
$session = new sessionManage();
$logged_in = $session->checkIsLoggedIn(true, '' . configAndConnect::URL . 'login/');
//Page content......
```

If the session is not set which means the user isn't logged in they will be directed to the websites login page.

View `account/index.php` for an example along with account data fetching.

The session is the users uid (id). If you want to fetch data like posts for the user define the uid as being the
session:

```php
$uid = $_SESSION['uid'];
```
