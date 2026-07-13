<?php
session_start();
require_once __DIR__ . '/../api/db.php';

if (empty($_SESSION['staff_logged_in'])) {
    header('Location: staff_login.php');
    exit;
}

$staff_id = $_SESSION['staff_id'];
$message = '';

// Handle form submissions (Edit Email, Reset Password, Verify Modification)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_email') {
        $acc_id = (int)$_POST['account_id'];
        $new_email = trim($_POST['new_email']);
        $stmt = $pdo->prepare('UPDATE account SET email = ? WHERE account_ID = ?');
        $stmt->execute([$new_email, $acc_id]);
        $message = "Email updated successfully.";
    } 
    elseif ($action === 'reset_password') {
        $acc_id = (int)$_POST['account_id'];
        $new_password = $_POST['new_password'];
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE account SET password = ? WHERE account_ID = ?');
        $stmt->execute([$hash, $acc_id]);
        $message = "Password reset successfully.";
    }
    elseif ($action === 'verify_mod') {
        $mod_id = (int)$_POST['modification_id'];
        $status = (int)$_POST['status']; // 1 for approve, 2 for reject (assuming 2 is rejected state, or delete row)
        
        if ($status === 1) {
            $stmt = $pdo->prepare('UPDATE modifications SET is_Verified = 1, staff_ID = ? WHERE modification_ID = ?');
            $stmt->execute([$staff_id, $mod_id]);
            $message = "Modification approved.";
        } else {
            $stmt = $pdo->prepare('DELETE FROM modifications WHERE modification_ID = ?');
            $stmt->execute([$mod_id]);
            $message = "Modification rejected and removed.";
        }
    }
}

// Fetch Data
$alumni_stmt = $pdo->query("SELECT account_ID, school_ID, first_Name, last_Name, email FROM account WHERE school_ID NOT LIKE '011-%' ORDER BY last_Name ASC");
$alumni_list = $alumni_stmt->fetchAll();

$mods_stmt = $pdo->query("
    SELECT m.modification_ID, m.action_Type, m.modified_Records, m.time_Modified, a.first_Name, a.last_Name 
    FROM modifications m 
    JOIN account a ON m.account_ID = a.account_ID 
    WHERE m.is_Verified = 0 
    ORDER BY m.time_Modified DESC
");
$modifications = $mods_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Staff Portal</title>
    <link rel="stylesheet" href="../styles/pv_main.css">
    <link rel="stylesheet" href="../styles/pb_staff.css">
</head>
<body id="page-account">
    
    <nav class="nav">
        <div class="nav-brand">
            <h1 class="nav-name">Dugtong Carolinian</h1>
            <span class="nav-sub" style="margin-left: 10px;">Staff Portal</span>
        </div>
        <a href="staff_logout.php" class="nav-logout">Secure Logout</a>
    </nav>

    <header class="page-header">
        <div class="header-inner">
            <h1>Staff Dashboard</h1>
            <p>Manage alumni accounts, update emergency credentials, and review profile modifications.</p>
        </div>
    </header>

    <main class="edit-wrapper" style="max-width: 1200px;">
        <?php if ($message): ?>
            <div class="toast success visible" id="sysMessage">
                <div class="toast-icon">✓</div>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
            <script>setTimeout(() => document.getElementById('sysMessage').classList.remove('visible'), 3000);</script>
        <?php endif; ?>

        <div class="edit-shell">
            <aside class="edit-sidebar">
                <div class="sidebar-content">
                    <div class="sidebar-name">Admin Control</div>
                    <div class="sidebar-divider"></div>
                    <button class="staff-tab-btn active" onclick="switchTab('manage-alumni', this)">Alumni Management</button>
                    <button class="staff-tab-btn" onclick="switchTab('manage-verifications', this)">Pending Verifications <span class="badge"><?= count($modifications) ?></span></button>
                </div>
            </aside>

            <section class="edit-body">
                
                <div id="manage-alumni" class="staff-tab-content active">
                    <div class="edit-section revealed">
                        <h3>Alumni Directory Control</h3>
                        <p style="font-size: 13px; color: var(--muted); margin-bottom: 20px;">Issue emergency password resets or update outdated email addresses.</p>
                        
                        <div class="staff-table-wrap">
                            <table class="staff-table">
                                <thead>
                                    <tr>
                                        <th>School ID</th>
                                        <th>Name</th>
                                        <th>Email Address</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alumni_list as $alum): ?>
                                    <tr>
                                        <td style="font-weight: 600;"><?= htmlspecialchars($alum['school_ID']) ?></td>
                                        <td><?= htmlspecialchars($alum['first_Name'] . ' ' . $alum['last_Name']) ?></td>
                                        <td><?= htmlspecialchars($alum['email']) ?></td>
                                        <td style="text-align: right;">
                                            <button class="btn-outline-green btn-sm" onclick="openEmailModal(<?= $alum['account_ID'] ?>, '<?= htmlspecialchars($alum['email'], ENT_QUOTES) ?>')">Edit Email</button>
                                            <button class="btn-outline-red btn-sm" onclick="openPasswordModal(<?= $alum['account_ID'] ?>)">Reset Password</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="manage-verifications" class="staff-tab-content">
                    <div class="edit-section revealed">
                        <h3>Profile Modifications</h3>
                        <p style="font-size: 13px; color: var(--muted); margin-bottom: 20px;">Review and approve/reject changes made by alumni to their records or certifications.</p>
                        
                        <?php if (count($modifications) === 0): ?>
                            <p style="color: var(--muted); font-style: italic;">No pending modifications at this time.</p>
                        <?php else: ?>
                            <div class="staff-table-wrap">
                                <table class="staff-table">
                                    <thead>
                                        <tr>
                                            <th>Account</th>
                                            <th>Action Type</th>
                                            <th>Details</th>
                                            <th>Date Submitted</th>
                                            <th style="text-align: right;">Decision</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($modifications as $mod): ?>
                                        <tr>
                                            <td style="font-weight: 600;"><?= htmlspecialchars($mod['first_Name'] . ' ' . $mod['last_Name']) ?></td>
                                            <td><span class="action-badge <?= strtolower($mod['action_Type']) ?>"><?= htmlspecialchars($mod['action_Type']) ?></span></td>
                                            <td><?= htmlspecialchars($mod['modified_Records']) ?></td>
                                            <td style="font-size: 12px; color: var(--muted);"><?= date('M j, Y g:i A', strtotime($mod['time_Modified'])) ?></td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="verify_mod">
                                                    <input type="hidden" name="modification_id" value="<?= $mod['modification_ID'] ?>">
                                                    <input type="hidden" name="status" value="1">
                                                    <button type="submit" class="btn-green btn-sm">Approve</button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="verify_mod">
                                                    <input type="hidden" name="modification_id" value="<?= $mod['modification_ID'] ?>">
                                                    <input type="hidden" name="status" value="2">
                                                    <button type="submit" class="btn-outline-red btn-sm">Reject</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </section>
        </div>
    </main>

    <div id="emailModal" class="confirm-overlay">
        <div class="confirm-box">
            <h4>Update Alumni Email</h4>
            <p>Enter the new email address for this alumnus. They will use this to log in moving forward.</p>
            <form method="POST">
                <input type="hidden" name="action" value="update_email">
                <input type="hidden" name="account_id" id="email_account_id">
                <input type="email" name="new_email" id="current_email_input" class="staff-modal-input" required>
                <div class="confirm-btns">
                    <button type="button" class="btn-outline-green" onclick="closeModal('emailModal')">Cancel</button>
                    <button type="submit" class="btn-green">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="passwordModal" class="confirm-overlay">
        <div class="confirm-box">
            <h4>Force Password Reset</h4>
            <p>Overwrite the current password. Please ensure you communicate this securely to the alumnus.</p>
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="account_id" id="pw_account_id">
                <input type="text" name="new_password" class="staff-modal-input" placeholder="Enter new temporary password" required minlength="8">
                <div class="confirm-btns">
                    <button type="button" class="btn-outline-green" onclick="closeModal('passwordModal')">Cancel</button>
                    <button type="submit" class="btn-green" style="background: #d32f2f; border-color: #d32f2f;">Force Reset</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabId, element) {
            document.querySelectorAll('.staff-tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.staff-tab-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            element.classList.add('active');
        }

        function openEmailModal(accountId, currentEmail) {
            document.getElementById('emailModal').classList.add('visible');
            document.getElementById('email_account_id').value = accountId;
            document.getElementById('current_email_input').value = currentEmail;
        }

        function openPasswordModal(accountId) {
            document.getElementById('passwordModal').classList.add('visible');
            document.getElementById('pw_account_id').value = accountId;
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('visible');
        }
    </script>
</body>
</html>