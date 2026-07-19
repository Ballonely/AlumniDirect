<?php
/**
 * student_setup.php
 *
 * First-time password setup for a student whose school_id exists in the
 * student table but has no password set yet.
 *
 * GET  ?id=<school_id>        — renders the setup form (validates the ID first)
 * POST (JSON fetch)           — hashes and stores the password, starts session
 *
 * Rate limiting: 5 setup attempts per session per 15-minute window, shared
 * with the login limiter key so brute-forcing via repeated "setup" calls is
 * equally restricted.
 */

session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/db.php';

// Must be defined at top level — PHP does not allow const inside if/function blocks
define('SETUP_MAX',    5);
define('SETUP_WINDOW', 900);

/* ════════════════════════════════════════════════════════════════════════════
   POST handler — save the password, start session, return JSON
   ══════════════════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $now = time();

    if (!isset($_SESSION['student_login_fails'])) {
        $_SESSION['student_login_fails'] = [];
    }
    $_SESSION['student_login_fails'] = array_filter(
        $_SESSION['student_login_fails'],
        fn($t) => ($now - $t) < SETUP_WINDOW
    );
    if (count($_SESSION['student_login_fails']) >= SETUP_MAX) {
        $oldest     = min($_SESSION['student_login_fails']);
        $retryAfter = SETUP_WINDOW - ($now - $oldest);
        http_response_code(429);
        echo json_encode(['error' => 'rate_limited', 'retry_after' => max(1, $retryAfter)]);
        exit;
    }

    $body     = json_decode(file_get_contents('php://input'), true);
    $id       = trim($body['student_id'] ?? '');
    $password = $body['password']  ?? '';
    $confirm  = $body['confirm']   ?? '';

    if ($id === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => 'missing_fields']);
        exit;
    }
    if ($password !== $confirm) {
        http_response_code(422);
        echo json_encode(['error' => 'passwords_mismatch']);
        exit;
    }
    if (strlen($password) < 8) {
        http_response_code(422);
        echo json_encode(['error' => 'password_too_short']);
        exit;
    }

    /* Verify the ID still has no password (guard against double-setup) */
    $stmt = $pdo->prepare('SELECT password FROM student WHERE school_id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row  = $stmt->fetch();

    if ($row === false) {
        http_response_code(404);
        echo json_encode(['error' => 'invalid_id']);
        exit;
    }
    if ($row['password'] !== null) {
        /* Already set — redirect back to normal login */
        http_response_code(409);
        echo json_encode(['error' => 'already_set']);
        exit;
    }

    /* Hash and store */
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $upd  = $pdo->prepare('UPDATE student SET password = ? WHERE school_id = ?');
    $upd->execute([$hash, $id]);

    /* Start session */
    session_regenerate_id(true);
    $_SESSION['student_mode'] = true;
    $_SESSION['student_id']   = $id;
    unset($_SESSION['student_login_fails'], $_SESSION['student_check_attempts']);

    echo json_encode(['ok' => true]);
    exit;
}

/* ════════════════════════════════════════════════════════════════════════════
   GET handler — render the setup page
   ══════════════════════════════════════════════════════════════════════════ */
$id = trim($_GET['id'] ?? '');

if ($id === '') {
    header('Location: ../pages/pb_landing.html');
    exit;
}

/* Verify the ID exists and still needs setup */
$stmt = $pdo->prepare('SELECT password FROM student WHERE school_id = ? LIMIT 1');
$stmt->execute([$id]);
$row  = $stmt->fetch();

if ($row === false || $row['password'] !== null) {
    /* ID invalid or password already set — send back to landing */
    header('Location: ../pages/pb_landing.html');
    exit;
}

$safeId = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Set Your Password | Dugtong Carolinian</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100dvh;
      font-family: 'Inter', sans-serif;
      background: #f4f6f3;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }

    .card {
      background: #fff;
      border-radius: 14px;
      padding: 44px 40px 36px;
      max-width: 440px;
      width: 100%;
      box-shadow: 0 16px 48px rgba(0,0,0,.12);
    }

    .eyebrow {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: #b8962e;
      margin-bottom: 6px;
    }

    h1 {
      font-family: 'Libre Baskerville', serif;
      font-size: 1.45rem;
      color: #1a2d1a;
      margin: 0 0 6px;
    }

    .sub {
      font-size: .875rem;
      color: #555;
      line-height: 1.6;
      margin: 0 0 28px;
    }

    .id-badge {
      display: inline-block;
      background: #eef3eb;
      border: 1px solid #c4d9bb;
      border-radius: 6px;
      padding: 3px 10px;
      font-size: .82rem;
      font-weight: 600;
      color: #2d6a3f;
      margin-bottom: 20px;
      letter-spacing: .04em;
    }

    label {
      display: block;
      font-size: .8rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: 5px;
    }

    .field { margin-bottom: 14px; position: relative; }

    input[type="password"] {
      width: 100%;
      padding: 11px 40px 11px 14px;
      border: 1.5px solid #d1d5db;
      border-radius: 8px;
      font-size: .95rem;
      font-family: inherit;
      color: #1a2d1a;
      outline: none;
      transition: border-color .15s;
    }
    input[type="password"]:focus { border-color: #2d6a3f; }
    input[type="password"].err   { border-color: #dc2626; }

    .toggle-pw {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: #9ca3af;
      padding: 0;
      line-height: 1;
      font-size: 1rem;
    }
    .toggle-pw:hover { color: #555; }

    .strength-bar {
      height: 4px;
      border-radius: 2px;
      background: #e5e7eb;
      margin: 6px 0 4px;
      overflow: hidden;
    }
    .strength-fill {
      height: 100%;
      border-radius: 2px;
      width: 0;
      transition: width .25s, background .25s;
    }
    .strength-label {
      font-size: .75rem;
      color: #9ca3af;
      min-height: 16px;
    }

    .field-error {
      font-size: .78rem;
      color: #dc2626;
      margin: 4px 0 0;
      min-height: 16px;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .form-error {
      background: #fef2f2;
      border: 1px solid #fca5a5;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: .82rem;
      color: #dc2626;
      margin-bottom: 16px;
      display: none;
    }
    .form-error.visible { display: block; }

    .requirements {
      font-size: .78rem;
      color: #6b7280;
      margin: 0 0 22px;
      padding-left: 16px;
      line-height: 1.7;
    }
    .req { color: #9ca3af; }
    .req.met { color: #2d6a3f; }
    .req.met::before { content: '✓ '; }
    .req:not(.met)::before { content: '· '; }

    .actions {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      margin-top: 8px;
    }

    .btn-cancel {
      background: none;
      border: 1.5px solid #d1d5db;
      border-radius: 8px;
      padding: 10px 20px;
      font-size: .875rem;
      color: #555;
      cursor: pointer;
      font-family: inherit;
      transition: background .15s, border-color .15s;
    }
    .btn-cancel:hover { background: #f3f4f6; border-color: #aaa; }

    .btn-submit {
      background: #2d6a3f;
      border: none;
      border-radius: 8px;
      padding: 10px 24px;
      font-size: .875rem;
      font-weight: 600;
      color: #fff;
      cursor: pointer;
      font-family: inherit;
      transition: background .15s;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .btn-submit:hover { background: #1e4f2d; }
    .btn-submit:disabled { opacity: .6; cursor: not-allowed; }

    .spinner {
      display: none;
      width: 14px; height: 14px;
      border: 2px solid rgba(255,255,255,.4);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin .6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>
  <div class="card">
    <p class="eyebrow">Student View · First-time setup</p>
    <h1>Set Your Password</h1>
    <p class="sub">
      Your student ID has been verified. Create a password to access the alumni
      directory in read-only student view.
    </p>

    <div class="id-badge">ID: <?= $safeId ?></div>

    <div class="form-error" id="form-error"></div>

    <div class="field">
      <label for="pw1">Password</label>
      <input type="password" id="pw1" placeholder="At least 8 characters" autocomplete="new-password" />
      <button type="button" class="toggle-pw" aria-label="Show password" onclick="togglePw('pw1', this)">👁</button>
      <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
      <div class="strength-label" id="strength-label"></div>
      <p class="field-error" id="pw1-error"></p>
    </div>

    <ul class="requirements">
      <li class="req" id="req-len">At least 8 characters</li>
      <li class="req" id="req-upper">One uppercase letter</li>
      <li class="req" id="req-digit">One number</li>
    </ul>

    <div class="field">
      <label for="pw2">Confirm Password</label>
      <input type="password" id="pw2" placeholder="Repeat your password" autocomplete="new-password" />
      <button type="button" class="toggle-pw" aria-label="Show password" onclick="togglePw('pw2', this)">👁</button>
      <p class="field-error" id="pw2-error"></p>
    </div>

    <div class="actions">
      <button class="btn-cancel" onclick="window.location.href='../pages/pb_landing.html'">Cancel</button>
      <button class="btn-submit" id="submit-btn" onclick="doSubmit()">
        <span class="spinner" id="spinner"></span>
        Confirm &amp; Sign In
      </button>
    </div>
  </div>

  <script>
    const STUDENT_ID = <?= json_encode($id) ?>;

    function togglePw(fieldId, btn) {
      const input = document.getElementById(fieldId);
      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.textContent = showing ? '👁' : '🙈';
    }

    /*  Strength meter  */
    const pw1    = document.getElementById('pw1');
    const sfill  = document.getElementById('strength-fill');
    const slabel = document.getElementById('strength-label');
    const reqs   = {
      len:   document.getElementById('req-len'),
      upper: document.getElementById('req-upper'),
      digit: document.getElementById('req-digit'),
    };

    function checkStrength(v) {
      const len   = v.length >= 8;
      const upper = /[A-Z]/.test(v);
      const digit = /\d/.test(v);
      reqs.len.classList.toggle('met', len);
      reqs.upper.classList.toggle('met', upper);
      reqs.digit.classList.toggle('met', digit);
      const score = [len, upper, digit, v.length >= 12, /[^A-Za-z0-9]/.test(v)].filter(Boolean).length;
      const colors = ['#ef4444','#f97316','#eab308','#22c55e','#16a34a'];
      const labels = ['Too short','Weak','Fair','Good','Strong'];
      sfill.style.width  = `${score * 20}%`;
      sfill.style.background = colors[score - 1] || '#e5e7eb';
      slabel.textContent = v.length ? labels[score - 1] || '' : '';
      return { len, upper, digit };
    }

    pw1.addEventListener('input', () => {
      checkStrength(pw1.value);
      document.getElementById('pw1-error').textContent = '';
      pw1.classList.remove('err');
      checkMatch();
    });

    const pw2 = document.getElementById('pw2');
    pw2.addEventListener('input', checkMatch);

    function checkMatch() {
      const err = document.getElementById('pw2-error');
      if (pw2.value && pw2.value !== pw1.value) {
        err.textContent = '⚠ Passwords do not match.';
        pw2.classList.add('err');
      } else {
        err.textContent = '';
        pw2.classList.remove('err');
      }
    }

    /*  Submit  */
    async function doSubmit() {
      const btn     = document.getElementById('submit-btn');
      const spinner = document.getElementById('spinner');
      const formErr = document.getElementById('form-error');
      const pw1err  = document.getElementById('pw1-error');
      const pw2err  = document.getElementById('pw2-error');

      formErr.classList.remove('visible');
      pw1err.textContent = '';
      pw2err.textContent = '';

      const { len, upper, digit } = checkStrength(pw1.value);

      if (!len) {
        pw1.classList.add('err');
        pw1err.textContent = '⚠ Password must be at least 8 characters.';
        pw1.focus();
        return;
      }
      if (pw1.value !== pw2.value) {
        pw2.classList.add('err');
        pw2err.textContent = '⚠ Passwords do not match.';
        pw2.focus();
        return;
      }

      btn.disabled = true;
      spinner.style.display = 'inline-block';

      try {
        const res  = await fetch('../api/student_setup.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            student_id: STUDENT_ID,
            password:   pw1.value,
            confirm:    pw2.value,
          }),
        });
        const data = await res.json();

        if (res.ok && data.ok) {
          window.location.href = '../pages/pv_main.php';
          return;
        }

        if (res.status === 429) {
          const mins = Math.ceil((data.retry_after || 60) / 60);
          formErr.textContent = `⚠ Too many attempts. Please wait ${mins} minute${mins > 1 ? 's' : ''} and try again.`;
        } else if (data.error === 'passwords_mismatch') {
          pw2.classList.add('err');
          pw2err.textContent = '⚠ Passwords do not match.';
        } else if (data.error === 'password_too_short') {
          pw1.classList.add('err');
          pw1err.textContent = '⚠ Password must be at least 8 characters.';
        } else if (data.error === 'already_set') {
          formErr.textContent = '⚠ A password is already set for this ID. Please sign in instead.';
          setTimeout(() => { window.location.href = '../pages/pb_landing.html?student_return=' + encodeURIComponent(STUDENT_ID); }, 2000);
        } else {
          formErr.textContent = '⚠ Something went wrong. Please try again.';
        }
        formErr.classList.add('visible');
      } catch {
        formErr.textContent = '⚠ Network error. Please check your connection and try again.';
        formErr.classList.add('visible');
      } finally {
        btn.disabled = false;
        spinner.style.display = 'none';
      }
    }

    /* Allow Enter key to submit */
    [pw1, pw2].forEach(el => el.addEventListener('keydown', e => {
      if (e.key === 'Enter') doSubmit();
    }));
  </script>
</body>
</html>
