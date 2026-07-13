<?php
session_start();
// Ensure this path matches your file structure. If db.php is in 'api/', this is correct.
require_once __DIR__ . '/../api/db.php'; 

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = trim($_POST['school_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strpos($school_id, '011-') !== 0) {
        $error = 'Invalid ID format. Staff IDs must begin with 011-.';
    } elseif ($school_id === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        // Query to check account and staff linkage
        $stmt = $pdo->prepare('
            SELECT a.account_ID, a.password, s.staff_ID, s.staff_level 
            FROM account a 
            INNER JOIN staff s ON a.account_ID = s.account_ID 
            WHERE a.school_ID = ?
        ');
        $stmt->execute([$school_id]);
        $staff = $staff_data = $stmt->fetch();

        if ($staff && password_verify($password, $staff['password'])) {
            session_regenerate_id(true);
            $_SESSION['staff_logged_in'] = true;
            $_SESSION['staff_id'] = $staff['staff_ID'];
            $_SESSION['account_id'] = $staff['account_ID'];
            header('Location: staff_dashboard.php');
            exit;
        } else {
            // This error occurs if user doesn't exist OR is not in the 'staff' table
            $error = 'Invalid credentials or the account is not linked as Staff.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login | Dugtong Carolinian</title>
    <!-- UPDATED: Ensure these paths match your folder name (css vs styles) -->
    <link rel="stylesheet" href="../styles/pb_login.css">
    <link rel="stylesheet" href="../styles/pb_staff.css">
</head>
<body>
    <div class="left-panel">
        <div class="bg-slide"></div>
        <div class="green-overlay"></div>
        <div class="left-content">
            <h2 class="usc-label">University of San Carlos <span>staff portal</span></h2>
            <h1>Dugtong <span>Carolinian</span></h1>
            <div class="left-divider"></div>
            <p>Secure administrative access. Manage alumni records, verify modifications, and oversee community integrity.</p>
        </div>
    </div>

    <div class="right-panel">
        <div class="right-content">
            <div class="login-box staff-login-box">
                <h2>Staff Authentication</h2>
                <p class="login-subtitle">Enter your 011- faculty ID to continue</p>
                
                <?php if ($error): ?>
                    <span class="field-hint" style="color: #ff6b6b; display:block; margin-bottom: 10px;"><?= htmlspecialchars($error) ?></span>
                <?php endif; ?>

                <form method="POST" action="staff_login.php">
                    <div class="input-box">
                        <input type="text" name="school_id" id="school_id" placeholder=" " required>
                        <label for="school_id">Staff ID (e.g., 011-XXXX)</label>
                    </div>
                    
                    <div class="input-box">
                        <input type="password" name="password" id="password" placeholder=" " required>
                        <label for="password">Password</label>
                    </div>

                    <button type="submit">Access Portal</button>
                    
                    <div class="register-link">
                        <p><a href="pb_landing.html">← Back to Main Directory</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>