<?php
/* Logs the user out by destroying the session, both server-side and
   the session cookie itself. session_destroy() alone only clears the
   server-side data — the PHPSESSID cookie stays in the browser until
   it expires or is explicitly cleared. */

session_start();
session_unset();
session_destroy();

// Explicitly expire the session cookie in the browser.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

header('Location: ../pages/pb_login.html');
exit;
