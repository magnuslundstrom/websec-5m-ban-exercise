<?php
// Written by Magnus Lundstrøm and Adam Daniel Toth

require __DIR__ . '/db.php';
session_start();
$db = new Database();

if ($_POST) {
    $email = htmlspecialchars($_POST['email']);
    $password = htmlspecialchars($_POST['password']);

    $sql = 'SELECT * FROM users WHERE email = :email LIMIT 1';
    $user = $db->prepare($sql)->bindAndExecute(['email', $email])->getOne();

    if (!$user) {
        sendError(500, 'User cannot be found', __LINE__);
    }

    if ($user->banned) {
        $sql = 'SELECT login_attempt FROM logins WHERE user_fk = :userId ORDER BY login_attempt DESC LIMIT 1';
        $latestLoginAttempt = $db->prepare($sql)->bindAndExecute(['userId', $user->user_id])->getOne();

        $latestInEpoch = strtotime($latestLoginAttempt->login_attempt);
        $currentTime = time();

        if ($currentTime - $latestInEpoch >= 3) {
            // Update banned status on profile to 0
            $sql = 'UPDATE users SET banned = 0 WHERE user_id = :userId';
            $db->prepare($sql)->bindAndExecute(['userId', $user->user_id]);

            // Delete all login attemps for the particular user
            $sql = 'DELETE FROM logins WHERE user_fk = :userId';
            $db->prepare($sql)->bindAndExecute(['userId', $user->user_id]);
        } else {
            sendError(500, 'You\'re banned bro!', __LINE__);
        }
    }

    if ($user->password != $password) {
        // Insert login attempt
        $sql = 'INSERT INTO logins (user_fk) VALUES (:userId)';
        $db->prepare($sql)->bindAndExecute(['userId', $user->user_id]);

        $sql = 'SELECT count(*) as count FROM logins WHERE user_fk = :userId';
        $count = $db->prepare($sql)->bindAndExecute(['userId', $user->user_id])->getOne();

        // If more 3 or more attempts we set banned status to 1 and send response
        if ($count->count >= 3) {
            $sql = 'UPDATE users SET banned = 1 WHERE user_id = :userId';
            $db->prepare($sql)->bindAndExecute(['userId', $user->user_id]);
            sendError(500, 'You\'re banned bro!', __LINE__);
        }
        sendError(500, 'Wrong password', __LINE__);
    }

    $_SESSION['loggedIn'] = 1;
    // If login is correct we remove all the current attempts for the particular user
    $sql = 'DELETE FROM logins WHERE user_fk = :userId';
    $db->prepare($sql)->bindAndExecute(['userId', $user->user_id]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web sec</title>
</head>
<body>
    <?=$_SESSION['loggedIn'] ? 'Welcome back' : 'Please login'?>
    <form action="" method="POST">
        <input type="text" name="email" placeholder="Email" value="a@a.com">
        <input type="text" name="password" placeholder="Password" value="12345">
        <button>Login!</button>
    </form>
</body>
</html>

<?php
function sendError($errorCode, $errorString, $errorLine)
{
    $_SESSION['loggedIn'] = 0;
    http_response_code($errorCode);
    header('Content-Type: application/json');
    echo '{"message":"' . $errorString . '", "line":"' . $errorLine . '"}';
    exit();
}
?>
