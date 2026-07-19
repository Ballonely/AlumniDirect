<?php
/**
 * index.php
 * Was a static index.html with no server-side protection — anyone
 * could open the admin portal directly. This mirrors the guard
 * pv_main.php uses on the alumni side: no valid staff session ->
 * bounce to the staff login page before any HTML is sent, and tell
 * the browser not to cache this page (so back-button after logout
 * can't show a stale copy).
 */

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

if (empty($_SESSION['staff_ID'])) {
    header('Location: pb_login.php?error=session');
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$staffName  = $_SESSION['staff_name'] ?? 'Staff';
$staffLevel = (int) ($_SESSION['staff_level'] ?? 0);
$staffRole  = $staffLevel === 1 ? 'System Administrator' : 'Staff Member';
$staffInitials = strtoupper(implode('', array_map(fn($p) => $p[0] ?? '', explode(' ', $staffName, 2))));

require __DIR__ . '/../api/db.php';
$batchOptions = $pdo->query(
    "SELECT DISTINCT graduation_Year FROM graduation WHERE graduation_Year IS NOT NULL ORDER BY graduation_Year DESC"
)->fetchAll(PDO::FETCH_COLUMN);
$programOptions = $pdo->query(
    "SELECT DISTINCT program_Name FROM program ORDER BY program_Name ASC"
)->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Staff | Dugtong Carolinian</title>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../styles/pv_staff.css">
<link rel="icon" type="image/png" href="../images/usc_logo.png" />
<style>
  /* ============================================================
     CSS CUSTOM PROPERTIES (design tokens)
     ============================================================ */
  :root {
    /* Brought in line with the public landing page's brand tokens
       (--priColor / --secColor / --terColor in pb_landing.css) so the
       admin portal reads as the same product, not a different app. */
    --color-primary:                  #015e2f;
    --color-primary-container:        #00763a;
    --color-primary-fixed:            #b3f1bf;
    --color-primary-fixed-dim:        #97d5a5;
    --color-on-primary:               #ffffff;
    --color-on-primary-container:     #75b083;
    --color-on-primary-fixed:         #00210d;
    --color-on-primary-fixed-variant: #15512c;

    --color-secondary:                #795900;
    --color-secondary-container:      #fcbf16;
    --color-secondary-fixed:          #ffdf9f;
    --color-secondary-fixed-dim:      #fabd16;
    --color-on-secondary:             #ffffff;
    --color-on-secondary-container:   #6c5000;
    --color-on-secondary-fixed:       #261a00;
    --color-on-secondary-fixed-variant: #5c4300;

    --color-tertiary:                 #450f1a;
    --color-tertiary-container:       #61252f;
    --color-tertiary-fixed:           #ffd9dc;
    --color-tertiary-fixed-dim:       #ffb2ba;
    --color-on-tertiary:              #ffffff;
    --color-on-tertiary-container:    #de8b95;
    --color-on-tertiary-fixed:        #3b0713;
    --color-on-tertiary-fixed-variant:#72323c;

    --color-background:               #f4fbf5;
    --color-surface:                  #f4fbf5;
    --color-surface-bright:           #f4fbf5;
    --color-surface-dim:              #d5dcd6;
    --color-surface-variant:          #dde4de;
    --color-surface-tint:             #306a42;
    --color-surface-container-lowest: #ffffff;
    --color-surface-container-low:    #eef5ef;
    --color-surface-container:        #e9f0ea;
    --color-surface-container-high:   #e3eae4;
    --color-surface-container-highest:#dde4de;
    --color-on-background:            #161d1a;
    --color-on-surface:               #161d1a;
    --color-on-surface-variant:       #414941;
    --color-inverse-surface:          #2b322e;
    --color-inverse-on-surface:       #ebf2ed;
    --color-inverse-primary:          #97d5a5;

    --color-outline:                  #717970;
    --color-outline-variant:          #c0c9be;

    --color-error:                    #ba1a1a;
    --color-error-container:          #ffdad6;
    --color-on-error:                 #ffffff;
    --color-on-error-container:       #93000a;
    --color-error-red:                #C62828;

    --color-warning-gold:             #F9BC15;
    --color-success-green:            #2E7D32;

    --color-background-alt:           #FFFFFF;

    --radius:       0.25rem;
    --radius-lg:    0.5rem;
    --radius-xl:    0.75rem;
    --radius-full:  9999px;

    --spacing-unit:           8px;
    --spacing-gutter:         24px;
    --spacing-margin-mobile:  16px;
    --spacing-margin-desktop: 40px;
    --container-max:          1280px;

    /* Typography */
    --font-sans:    'Inter', sans-serif;
    --font-serif:   'Libre Baskerville', serif;
    --font-accent:  'Montserrat', sans-serif;

    --text-body-md-size:   14px;
    --text-body-md-lh:     20px;
    --text-body-lg-size:   16px;
    --text-body-lg-lh:     24px;
    --text-label-md-size:  12px;
    --text-label-md-lh:    16px;
    --text-label-md-ls:    0.05em;
    --text-headline-sm-size: 24px;
    --text-headline-sm-lh:   32px;
    --text-headline-md-size: 32px;
    --text-headline-md-lh:   40px;
    --text-headline-lg-mobile-size: 28px;
    --text-headline-lg-mobile-lh:   36px;
    --text-title-lg-size:  20px;
    --text-title-lg-lh:    28px;
    --text-display-lg-size: 48px;
    --text-display-lg-lh:   56px;
    --text-display-lg-ls:  -0.02em;
  }

  /* ============================================================
     BASE / RESET
     ============================================================ */
  *, *::before, *::after { box-sizing: border-box; }

  body {
    background-color: var(--color-background);
    color: var(--color-on-background);
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    line-height: var(--text-body-md-lh);
    font-weight: 400;
    display: flex;
    min-height: 100vh;
    margin: 0;
  }

  /* ============================================================
     NAV LINK COLOURS (kept from original <style> block)
     ============================================================ */
  nav .nav-link,
  nav .nav-link:visited {
    color: rgba(255,255,255,.72) !important;
  }
  nav .nav-link:hover {
    color: #fff !important;
    background-color: rgba(255,255,255,.08) !important;
  }
  nav .nav-link.is-active {
    color: #fff !important;
    background-color: rgba(255,255,255,.12) !important;
    border-left: 3px solid #fcbf16;
    padding-left: calc(1rem - 3px);
    font-weight: 600;
  }
  nav .nav-link.is-active .material-symbols-outlined {
    color: #fcbf16 !important;
  }

  /* ============================================================
     TYPOGRAPHY HELPERS
     ============================================================ */
  .t-body-md   { font-family: var(--font-sans);  font-size: var(--text-body-md-size);  line-height: var(--text-body-md-lh);  font-weight: 400; }
  .t-body-lg   { font-family: var(--font-sans);  font-size: var(--text-body-lg-size);  line-height: var(--text-body-lg-lh);  font-weight: 400; }
  .t-label-md  { font-family: var(--font-sans);  font-size: var(--text-label-md-size); line-height: var(--text-label-md-lh); letter-spacing: var(--text-label-md-ls); font-weight: 600; }
  .t-headline-sm { font-family: var(--font-serif); font-size: var(--text-headline-sm-size); line-height: var(--text-headline-sm-lh); font-weight: 700; }
  .t-headline-md { font-family: var(--font-serif); font-size: var(--text-headline-md-size); line-height: var(--text-headline-md-lh); font-weight: 700; }
  .t-title-lg  { font-family: var(--font-sans);  font-size: var(--text-title-lg-size); line-height: var(--text-title-lg-lh); font-weight: 600; }

  /* ============================================================
     SIDEBAR NAV
     ============================================================ */
  #mobile-menu {
    background: linear-gradient(165deg, #0a1a10 0%, #012d16 55%, var(--color-primary) 130%);
    height: 100vh;
    width: 18rem;
    position: fixed;
    left: 0;
    top: 0;
    border-right: 1px solid rgba(252, 191, 22, 0.12);
    z-index: 50;
    display: flex;
    flex-direction: column;
    padding-top: var(--spacing-unit);
    padding-bottom: var(--spacing-unit);
    transition: transform 0.2s ease-in-out;
  }

  /* Hidden on mobile by default; shown md+ */
  @media (max-width: 767px) {
    #mobile-menu { display: none; }
    #mobile-menu.is-open { display: flex; }
  }
  @media (min-width: 768px) {
    #mobile-menu { display: flex !important; }
  }

  .sidebar-logo-area {
    padding-left: var(--spacing-gutter);
    padding-right: var(--spacing-gutter);
    margin-bottom: 2rem;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }
  .sidebar-logo-area img {
    width: 60px;
    height: 60px;
    object-fit: contain;
    filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4));
    transition: transform 0.2s ease;
  }
  .sidebar-logo-area:hover img {
    transform: scale(1.07);
  }
  .sidebar-logo-title {
    font-family: var(--font-serif);
    font-weight: 400;
    font-size: 17px;
    color: #fff;
    letter-spacing: 0.3px;
    line-height: 1.25;
    margin: 0;
  }
  .sidebar-logo-caption {
    font-family: var(--font-accent);
    font-size: 11px;
    color: var(--color-secondary-container);
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin: 3px 0 0;
  }

  .sidebar-nav-primary {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 0 var(--spacing-unit);
    list-style: none;
    margin: 0;
  }
  .sidebar-nav-secondary {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: var(--spacing-unit) var(--spacing-unit);
    border-top: 1px solid rgba(255,255,255,.10);
    list-style: none;
    margin: 0;
  }

  .nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: var(--radius);
    transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
    font-family: var(--font-accent);
    font-size: 15;
    font-weight: 600;
    line-height: var(--text-body-md-lh);
    letter-spacing: 1px;
    text-decoration: none;
    text-transform: uppercase;
  }

  .nav-signout {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    color: rgba(255,255,255,.60);
    text-decoration: none;
    text-transform: uppercase;
    transition: color 0.2s, background-color 0.2s;
    font-family: var(--font-accent);
    font-size: 15;
    letter-spacing: 1px;
    font-weight: 600;
    line-height: var(--text-body-md-lh);
  }
  .nav-signout:hover {
    color: #fff;
    background-color: rgba(255,255,255,.10);
  }

  .sidebar-badge {
    margin-left: auto;
    background-color: var(--color-error-red);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    line-height: 1;
    border-radius: var(--radius-full);
    width: 1.25rem;
    height: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  /* ============================================================
     MAIN CONTENT AREA
     ============================================================ */
  main {
    flex: 1;
    margin-left: 0;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
  }
  @media (min-width: 768px) {
    main { margin-left: 18rem; }
  }

  /* ============================================================
     HEADER
     ============================================================ */
  .site-header {
    background-color: white;
    position: sticky;
    top: 0;
    z-index: 40;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 4rem;
    padding-left: var(--spacing-margin-desktop);
    padding-right: var(--spacing-margin-desktop);
  }

  .header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
  }
  .header-site-title {
    font-family: var(--font-accent);
    font-size: var(--text-headline-sm-size);
    line-height: var(--text-headline-sm-lh);
    font-weight: 700;
    color: var(--color-primary);
    display: none;
  }
  @media (min-width: 768px) {
    .header-site-title { display: block; }
  }

  .btn-mobile-menu {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    color: var(--color-primary);
    transition: color 0.2s;
    display: block;
  }
  .btn-mobile-menu:hover { color: var(--color-primary-container); }
  @media (min-width: 768px) {
    .btn-mobile-menu { display: none; }
  }

  .header-search-wrap {
    flex: 1;
    max-width: 28rem;
    margin: 0 2rem;
    position: relative;
    display: none;
  }
  @media (min-width: 640px) {
    .header-search-wrap { display: block; }
  }
  .header-search-wrap .material-symbols-outlined {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-outline);
    pointer-events: none;
  }
  .header-search-input {
    width: 100%;
    background-color: var(--color-surface-container-lowest);
    border: 1px solid var(--color-outline-variant);
    border-radius: var(--radius-full);
    padding: 0.5rem 1rem 0.5rem 2.5rem;
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    line-height: var(--text-body-md-lh);
    color: var(--color-on-surface);
    transition: border-color 0.3s, box-shadow 0.3s;
    outline: none;
  }
  .header-search-input::placeholder { color: var(--color-outline); }
  .header-search-input:focus {
    border-color: var(--color-primary-container);
    box-shadow: 0 0 0 2px rgba(0,67,32,.25);
  }

  .header-actions {
    display: flex;
    align-items: center;
    gap: 1.5rem;
  }



  .profile-btn-wrap { position: relative; }
  .profile-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    color: var(--color-primary);
    font-weight: 700;
    transition: opacity 0.2s;
  }
  .profile-btn:hover { opacity: 0.8; }
  .profile-text {
    text-align: right;
    line-height: 1.2;
    display: none;
  }
  @media (min-width: 640px) {
    .profile-text { display: block; }
  }
  .profile-text .name {
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    letter-spacing: var(--text-label-md-ls);
    font-weight: 600;
    text-transform: uppercase;
    display: block;
  }
  .profile-text .role {
    font-size: 11px;
    color: var(--color-on-surface-variant);
    font-weight: 400;
    text-transform: none;
  }
  .avatar {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: var(--radius-full);
    background-color: var(--color-primary);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    border: 1px solid var(--color-outline-variant);
    flex-shrink: 0;
  }

  #profile-menu {
    position: absolute;
    right: 0;
    top: 3rem;
    background-color: var(--color-surface-container-lowest);
    border: 1px solid var(--color-outline-variant);
    border-radius: var(--radius);
    box-shadow: 0 4px 12px rgba(0,0,0,.12);
    width: 10rem;
    padding: 0.25rem 0;
    z-index: 50;
  }
  #profile-menu.hidden { display: none; }
  #profile-menu a {
    display: block;
    padding: 0.5rem 1rem;
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    color: var(--color-on-surface);
    text-decoration: none;
    transition: background-color 0.2s;
  }
  #profile-menu a:hover { background-color: var(--color-surface-container-low); }

  /* ============================================================
     PAGE LAYOUT
     ============================================================ */
  .view-page {
    display: flex;
    flex-direction: column;
    flex: 1;
  }

  .page-inner {
    padding: var(--spacing-gutter);
    flex: 1;
    max-width: var(--container-max);
    margin-left: auto;
    margin-right: auto;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }
  @media (min-width: 1024px) {
    .page-inner {
      padding-left: var(--spacing-margin-desktop);
      padding-right: var(--spacing-margin-desktop);
    }
  }

  /* ============================================================
     PAGE HEADER ROW
     ============================================================ */
  .page-header-row {
    margin-top: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
  }
  @media (min-width: 768px) {
    .page-header-row {
      flex-direction: row;
      align-items: flex-end;
    }
  }
  .page-title {
    font-family: var(--font-serif);
    font-size: clamp(1.8em, 3.5vw, 2.8em);
    line-height: var(--text-headline-md-lh);
    font-weight: 400;
    color: black;
    margin: 0 0 0.6rem;
  }
  .page-title::after {
    content: '';
    display: block;
    width: 36px;
    height: 3px;
    margin-top: 10px;
    background: var(--color-secondary-container);
    border-radius: 2px;
  }
  .page-subtitle {
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    line-height: var(--text-body-md-lh);
    color: var(--color-on-surface-variant);
    margin: 0;
  }

  /* ============================================================
     STAT CARDS (Dashboard)
     ============================================================ */
  .stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  @media (min-width: 640px) {
    .stats-grid { grid-template-columns: repeat(3, 1fr); }
  }

  .stat-card {
    background-color: var(--color-surface-container-lowest);
    border: 1px solid var(--color-outline-variant);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  .stat-card--warning {
    border-left: 2px solid var(--color-warning-gold);
  }
  .stat-card--clickable {
    transition: box-shadow 0.2s, transform 0.15s;
    border: 1px solid var(--color-outline-variant);
  }
  .stat-card--clickable:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,.10);
    transform: translateY(-1px);
  }

  .stat-card-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }
  .stat-icon {
    width: 3rem;
    height: 3rem;
    border-radius: var(--radius);
    background-color: var(--color-surface-container-low);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-primary-container);
  }
  .stat-icon--warning {
    background-color: rgba(249,188,21,.15);
    color: var(--color-secondary);
  }

  .badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.625rem;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 600;
  }
  .badge--success {
    background-color: rgba(46,125,50,.10);
    color: var(--color-success-green);
  }
  .badge--error {
    background-color: rgba(198,40,40,.10);
    color: var(--color-error-red);
  }
  .badge--neutral {
    background-color: var(--color-surface-container-high);
    color: var(--color-on-surface-variant);
  }
  .badge--outline {
    background-color: var(--color-surface-container-high);
    color: var(--color-on-surface-variant);
    padding: 0.375rem 0.75rem;
  }

  .stat-label {
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    line-height: var(--text-label-md-lh);
    letter-spacing: var(--text-label-md-ls);
    font-weight: 600;
    color: var(--color-on-surface-variant);
    text-transform: uppercase;
    margin: 0 0 0.25rem;
  }
  .stat-value {
    font-family: var(--font-serif);
    font-size: var(--text-headline-md-size);
    line-height: var(--text-headline-md-lh);
    font-weight: 700;
    color: var(--color-primary);
    line-height: 1;
    margin: 0;
  }
  .stat-value--secondary { color: var(--color-secondary); }

  /* ============================================================
     QUICK ACTION CARDS (Dashboard)
     ============================================================ */
  .action-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  @media (min-width: 640px) {
    .action-grid { grid-template-columns: repeat(2, 1fr); }
  }

  .action-card {
    text-align: left;
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    cursor: pointer;
    border: none;
    transition: background-color 0.2s;
    width: 100%;
  }
  .action-card--primary {
    background-color: var(--color-primary-container);
    color: #fff;
  }
  .action-card--primary:hover { background-color: var(--color-primary); }
  .action-card--surface {
    background-color: var(--color-surface-container-lowest);
    border: 1px solid var(--color-outline-variant);
    color: var(--color-on-surface);
  }
  .action-card--surface:hover { background-color: var(--color-surface-container-low); }

  .action-card-label {
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    line-height: var(--text-label-md-lh);
    letter-spacing: var(--text-label-md-ls);
    font-weight: 600;
    text-transform: uppercase;
    margin: 0 0 0.25rem;
  }
  .action-card--primary .action-card-label { color: var(--color-primary-fixed-dim); }
  .action-card--surface .action-card-label { color: var(--color-on-surface-variant); }

  .action-card-title {
    font-family: var(--font-serif);
    font-size: var(--text-headline-sm-size);
    line-height: var(--text-headline-sm-lh);
    font-weight: 700;
    margin: 0;
  }
  .action-card--surface .action-card-title { color: var(--color-primary); }

  .action-arrow {
    font-size: 2rem;
    opacity: 0.7;
    transition: transform 0.2s;
    flex-shrink: 0;
  }
  .action-card:hover .action-arrow { transform: translateX(0.25rem); }
  .action-card--surface .action-arrow { color: var(--color-on-surface-variant); }

  /* ============================================================
     INFO PILL / LAST-UPDATED BAR
     ============================================================ */
  .info-pill {
    background-color: var(--color-surface-container-lowest);
    border: 1px solid var(--color-outline-variant);
    border-radius: var(--radius);
    padding: 0.5rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    line-height: var(--text-body-md-lh);
    color: var(--color-on-surface-variant);
  }
  .info-pill .material-symbols-outlined { font-size: 18px; }

  /* ============================================================
     FILTER TOOLBAR (Directory)
     ============================================================ */
  .filter-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
  }

  .select-wrap {
    position: relative;
  }
  .select-wrap .material-symbols-outlined {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-outline);
    pointer-events: none;
    font-size: 20px;
  }
  .filter-select {
    appearance: none;
    background-color: var(--color-surface-container-lowest);
    border: 1px solid var(--color-outline-variant);
    border-radius: var(--radius);
    padding: 0.5rem 2.5rem 0.5rem 1rem;
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    line-height: var(--text-body-md-lh);
    color: var(--color-on-surface);
    cursor: pointer;
    outline: none;
    transition: background-color 0.3s, border-color 0.3s;
  }
  .filter-select:hover { background-color: var(--color-surface-container-low); }
  .filter-select:focus { border-color: var(--color-primary-container); }


  /* ============================================================
     DATA TABLE
     ============================================================ */
  .table-card {
    background-color: var(--color-surface-container-lowest);
    border: 1px solid var(--color-outline-variant);
    border-radius: var(--radius-lg);
    overflow: hidden;
    flex: 1;
    display: flex;
    flex-direction: column;
  }
  .table-scroll { overflow-x: auto; flex: 1; }

  .data-table {
    width: 100%;
    text-align: left;
    border-collapse: collapse;
  }
  .data-table thead tr {
    background-color: var(--color-surface-container-low);
    border-bottom: 1px solid var(--color-outline-variant);
  }
  .data-table th {
    padding: 1rem 1.5rem;
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    line-height: var(--text-label-md-lh);
    letter-spacing: var(--text-label-md-ls);
    font-weight: 600;
    color: var(--color-on-surface-variant);
    text-transform: uppercase;
  }
  .data-table th.col-checkbox { width: 3rem; text-align: center; }
  .data-table th.col-status   { text-align: center; }
  .data-table th.col-actions  { text-align: right; }

  .th-sortable {
    cursor: pointer;
    transition: color 0.2s;
  }
  .th-sortable:hover { color: var(--color-primary); }
  .th-sortable .sort-icon {
    font-size: 16px;
    opacity: 0;
    transition: opacity 0.2s;
    vertical-align: middle;
  }
  .th-sortable:hover .sort-icon { opacity: 1; }
  .th-inner { display: flex; align-items: center; gap: 0.25rem; }

  .data-table tbody {
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    line-height: var(--text-body-md-lh);
  }

  .table-checkbox {
    border-radius: 2px;
    border: 1px solid var(--color-outline-variant);
    accent-color: var(--color-primary-container);
    cursor: pointer;
    width: 1rem;
    height: 1rem;
  }
  .table-checkbox:focus { outline: 2px solid var(--color-primary-container); }

  /* ============================================================
     TABLE PAGINATION FOOTER
     ============================================================ */
  .table-footer {
    background-color: var(--color-surface-container-low);
    border-top: 1px solid var(--color-outline-variant);
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .table-footer-info {
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    line-height: var(--text-body-md-lh);
    color: var(--color-on-surface-variant);
  }
  .pagination {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  .btn-page {
    padding: 0.25rem;
    border-radius: var(--radius);
    border: none;
    background: none;
    cursor: pointer;
    color: var(--color-on-surface-variant);
    transition: background-color 0.2s;
    line-height: 0;
  }
  .btn-page:hover { background-color: var(--color-surface-container-highest); }
  .btn-page:disabled { opacity: 0.5; cursor: not-allowed; }
  .page-indicator {
    width: 2rem;
    height: 2rem;
    border-radius: var(--radius);
    background-color: var(--color-primary-container);
    color: #fff;
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  /* ============================================================
     APPLICATIONS / REVIEW QUEUE
     ============================================================ */
  .queue-strip {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    overflow-x: auto;
    padding-bottom: 0.25rem;
  }

  .pending-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: var(--radius-full);
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    line-height: var(--text-label-md-lh);
    font-weight: 600;
    background-color: var(--color-surface-container-high);
    color: var(--color-on-surface-variant);
  }

  /* Review card */
  .review-card {
    background-color: var(--color-surface-container-lowest);
    border: 1px solid var(--color-outline-variant);
    border-radius: var(--radius-lg);
    overflow: hidden;
  }

  .review-card-header {
    background-color: var(--color-surface-container-low);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--color-outline-variant);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  .rv-identity {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }
  .rv-initials-circle {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: var(--radius-full);
    background-color: rgba(0,67,32,.10);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-primary-container);
    font-weight: 700;
    font-size: 0.875rem;
  }
  .rv-name {
    font-family: var(--font-serif);
    font-size: var(--text-headline-sm-size);
    line-height: var(--text-headline-sm-lh);
    font-weight: 700;
    color: var(--color-primary);
    line-height: 1.2;
    margin: 0;
  }
  .rv-meta {
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    line-height: var(--text-body-md-lh);
    color: var(--color-on-surface-variant);
    margin: 0;
  }
  .rv-date-block { text-align: right; }
  .rv-date-label {
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    letter-spacing: var(--text-label-md-ls);
    font-weight: 600;
    color: var(--color-on-surface-variant);
    text-transform: uppercase;
    margin: 0;
  }
  .rv-date-value {
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    line-height: var(--text-body-md-lh);
    color: var(--color-on-surface);
    font-weight: 600;
    margin: 0;
  }

  .review-cols {
    display: grid;
    grid-template-columns: 1fr;
  }
  @media (min-width: 640px) {
    .review-cols {
      grid-template-columns: 1fr 1fr;
    }
    .review-cols > *:not(:first-child) {
      border-left: 1px solid var(--color-outline-variant);
    }
  }
  .review-cols > *:not(:last-child) {
    border-bottom: 1px solid var(--color-outline-variant);
  }
  @media (min-width: 640px) {
    .review-cols > *:not(:last-child) { border-bottom: none; }
  }

  .review-col-panel {
    padding: 1.5rem;
  }
  .review-col-panel--changes {
    background-color: rgba(46,125,50,.03);
  }

  .review-section-title {
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    line-height: var(--text-label-md-lh);
    letter-spacing: var(--text-label-md-ls);
    font-weight: 600;
    text-transform: uppercase;
    color: var(--color-on-surface-variant);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 1rem;
  }
  .review-section-title--changes { color: var(--color-secondary); }
  .review-section-title .material-symbols-outlined { font-size: 18px; }

  .rv-fields { display: flex; flex-direction: column; gap: 1rem; }

  /* Bottom verification / comment row */
  .review-bottom {
    border-top: 1px solid var(--color-outline-variant);
    display: grid;
    grid-template-columns: 1fr;
  }
  @media (min-width: 640px) {
    .review-bottom { grid-template-columns: 1fr 1fr; }
    .review-bottom > *:not(:first-child) { border-left: 1px solid var(--color-outline-variant); }
  }
  .review-bottom > *:not(:last-child) { border-bottom: 1px solid var(--color-outline-variant); }
  @media (min-width: 640px) {
    .review-bottom > *:not(:last-child) { border-bottom: none; }
  }

  .review-blobs-panel { padding: 1.5rem; }
  .blobs-list { display: flex; flex-wrap: wrap; gap: 0.5rem; }
  .blob-section-title {
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    letter-spacing: var(--text-label-md-ls);
    font-weight: 600;
    text-transform: uppercase;
    color: var(--color-on-surface-variant);
    margin: 0 0 0.75rem;
  }

  .review-comment-panel { padding: 1.5rem; }
  .comment-label {
    display: block;
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    letter-spacing: var(--text-label-md-ls);
    font-weight: 600;
    text-transform: uppercase;
    color: var(--color-on-surface-variant);
    margin: 0 0 0.75rem;
  }
  .comment-optional {
    font-weight: 400;
    letter-spacing: 0;
    text-transform: none;
    color: rgba(65,73,65,.70);
  }
  .comment-textarea {
    width: 100%;
    background-color: #fff;
    border: 1px solid var(--color-outline-variant);
    border-radius: var(--radius);
    padding: 0.625rem 0.75rem;
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    color: var(--color-on-surface);
    outline: none;
    resize: none;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .comment-textarea:focus {
    border-color: var(--color-primary-container);
    box-shadow: 0 0 0 1px var(--color-primary-container);
  }

  /* Action buttons footer */
  .review-card-footer {
    background-color: var(--color-surface-container);
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--color-outline-variant);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
  }
  .btn-deny {
    background-color: transparent;
    border: 2px solid var(--color-error-red);
    color: var(--color-error-red);
    border-radius: var(--radius);
    padding: 0.5rem 1.5rem;
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    letter-spacing: var(--text-label-md-ls);
    text-transform: uppercase;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: background-color 0.2s;
  }
  .btn-deny:hover { background-color: rgba(198,40,40,.05); }
  .btn-deny .material-symbols-outlined { font-size: 18px; }

  .btn-approve {
    background-color: var(--color-primary-container);
    color: #fff;
    border: 2px solid var(--color-primary-container);
    border-radius: var(--radius);
    padding: 0.5rem 1.5rem;
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    letter-spacing: var(--text-label-md-ls);
    text-transform: uppercase;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 1px 2px rgba(0,0,0,.08);
    transition: background-color 0.2s;
  }
  .btn-approve:hover { background-color: var(--color-primary); }
  .btn-approve .material-symbols-outlined { font-size: 18px; }

  /* Empty state */
  .empty-state {
    background-color: var(--color-surface-container-lowest);
    border: 1px solid var(--color-outline-variant);
    border-radius: var(--radius-lg);
    padding: 4rem;
    display: none;           /* toggled via JS */
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 0.75rem;
  }
  .empty-state.is-visible { display: flex; }
  .empty-state .material-symbols-outlined { font-size: 40px; color: var(--color-success-green); }
  .empty-state-title {
    font-family: var(--font-serif);
    font-size: var(--text-headline-sm-size);
    line-height: var(--text-headline-sm-lh);
    font-weight: 700;
    color: var(--color-primary);
    margin: 0;
  }
  .empty-state-body {
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    line-height: var(--text-body-md-lh);
    color: var(--color-on-surface-variant);
    max-width: 24rem;
    margin: 0;
  }

  /* ============================================================
     DIRECTORY TABLE ROWS (JS-injected)
     ============================================================ */
  .dir-row {
    border-bottom: 1px solid var(--color-outline-variant);
    transition: background-color 0.15s;
  }
  .dir-row:hover { background-color: rgba(221,228,222,.50); }

  .dir-td {
    padding: 0.75rem 1.5rem;
    color: var(--color-on-surface-variant);
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    line-height: var(--text-body-md-lh);
  }
  .dir-td--checkbox { text-align: center; }
  .dir-td--name     { font-weight: 600; color: var(--color-on-surface); }
  .dir-td--mono     { font-family: monospace; font-size: 0.8125rem; }
  .dir-td--center   { text-align: center; }
  .dir-td--right    { text-align: right; }

  .dir-status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.125rem 0.625rem;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 600;
  }
  .dir-status-badge--verified {
    background-color: rgba(46,125,50,.10);
    color: var(--color-success-green);
  }
  .dir-status-badge--pending {
    background-color: rgba(249,188,21,.20);
    color: var(--color-secondary);
  }

  /* ============================================================
     QUEUE CHIPS (JS-injected)
     ============================================================ */
  .queue-chip {
    flex-shrink: 0;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-full);
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    line-height: var(--text-label-md-lh);
    letter-spacing: var(--text-label-md-ls);
    font-weight: 600;
    border: 1px solid var(--color-outline-variant);
    background-color: var(--color-surface-container-lowest);
    color: var(--color-on-surface-variant);
    cursor: pointer;
    transition: background-color 0.2s;
  }
  .queue-chip:hover { background-color: var(--color-surface-container-low); }
  .queue-chip--active {
    background-color: var(--color-primary-container);
    color: #fff;
    border-color: var(--color-primary-container);
  }

  /* ============================================================
     REVIEW FIELD VALUES (JS-injected)
     ============================================================ */
  .rv-field-label {
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    line-height: var(--text-label-md-lh);
    letter-spacing: var(--text-label-md-ls);
    font-weight: 600;
    color: var(--color-on-surface-variant);
    margin: 0 0 0.25rem;
  }
  .rv-field-value {
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    line-height: var(--text-body-md-lh);
    padding: 0.5rem 0.75rem;
    border-radius: var(--radius);
    margin: 0;
  }
  .rv-field-value--old {
    background-color: var(--color-surface-container-low);
    border: 1px solid var(--color-outline-variant);
    color: var(--color-on-surface);
    text-decoration: line-through;
    opacity: 0.7;
  }
  .rv-field-value--new {
    background-color: #fff;
    border: 1px solid rgba(46,125,50,.30);
    color: var(--color-primary);
    font-weight: 500;
    box-shadow: 0 1px 2px rgba(0,0,0,.06);
  }

  /* ============================================================
     DIRECTORY — ARCHIVED ROW STATE
     ============================================================ */
  .dir-row--archived td { opacity: 0.55; }
  .dir-row--archived .dir-td--name { text-decoration: line-through; }

  .dir-status-badge--archived {
    background-color: rgba(113,121,112,.15);
    color: var(--color-outline);
  }

  /* Action cell wrapper */
  .dir-actions {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
  }

  .dir-archive-btn {
    background: none;
    border: 1px solid var(--color-outline-variant);
    border-radius: var(--radius);
    padding: 0.375rem;
    cursor: pointer;
    color: var(--color-on-surface-variant);
    line-height: 0;
    transition: color 0.2s, background-color 0.2s, border-color 0.2s;
  }
  .dir-archive-btn:hover {
    color: var(--color-error-red);
    background-color: rgba(198,40,40,.06);
    border-color: var(--color-error-red);
  }
  .dir-archive-btn--restore:hover {
    color: var(--color-success-green);
    background-color: rgba(46,125,50,.06);
    border-color: var(--color-success-green);
  }
  .dir-archive-btn .material-symbols-outlined { font-size: 20px; }

  /* ============================================================
     ARCHIVE / RESTORE CONFIRMATION MODAL
     ============================================================ */
  .modal-backdrop {
    position: fixed;
    inset: 0;
    background-color: rgba(0,0,0,.40);
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
  }
  .modal-backdrop.hidden { display: none; }

  .modal-box {
    background-color: var(--color-surface-container-lowest);
    border-radius: var(--radius-lg);
    box-shadow: 0 8px 32px rgba(0,0,0,.22);
    width: 100%;
    max-width: 26rem;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--color-outline-variant);
  }
  .modal-title {
    font-family: var(--font-serif);
    font-size: var(--text-headline-sm-size);
    font-weight: 700;
    color: var(--color-primary);
    margin: 0;
  }
  .modal-close-btn {
    background: none;
    border: none;
    padding: 0.25rem;
    cursor: pointer;
    color: var(--color-on-surface-variant);
    line-height: 0;
    border-radius: var(--radius);
    transition: background-color 0.2s;
  }
  .modal-close-btn:hover { background-color: var(--color-surface-container-low); }

  .modal-body {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    text-align: center;
  }
  .modal-icon-wrap {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .modal-icon-wrap--warning {
    background-color: rgba(198,40,40,.10);
    color: var(--color-error-red);
  }
  .modal-icon-wrap--success {
    background-color: rgba(46,125,50,.10);
    color: var(--color-success-green);
    background-color: rgba(198,40,40,.10);
    color: var(--color-error-red);
  }
  .modal-icon-wrap--warning .material-symbols-outlined,
  .modal-icon-wrap--success .material-symbols-outlined { font-size: 28px; }
  .modal-body-text {
    font-family: var(--font-sans);
    font-size: var(--text-body-lg-size);
    line-height: var(--text-body-lg-lh);
    color: var(--color-on-surface-variant);
    margin: 0;
  }
  .modal-body-text strong { color: var(--color-on-surface); font-weight: 600; }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--color-outline-variant);
    background-color: var(--color-surface-container);
  }

  .modal-btn {
    border-radius: var(--radius);
    padding: 0.5rem 1.5rem;
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    letter-spacing: var(--text-label-md-ls);
    text-transform: uppercase;
    font-weight: 700;
    cursor: pointer;
    border: 2px solid transparent;
    transition: background-color 0.2s, color 0.2s;
  }
  .modal-btn:disabled { opacity: 0.6; cursor: not-allowed; }

  .modal-btn--ghost {
    background-color: transparent;
    border-color: var(--color-outline-variant);
    color: var(--color-on-surface-variant);
  }
  .modal-btn--ghost:hover { background-color: var(--color-surface-container-high); }

  .modal-btn--danger {
    background-color: var(--color-error-red);
    border-color: var(--color-error-red);
    color: #fff;
  }
  .modal-btn--danger:hover { background-color: #b71c1c; }

  .modal-btn--primary {
    background-color: var(--color-primary-container);
    border-color: var(--color-primary-container);
    color: #fff;
  }
  .modal-btn--primary:hover { background-color: var(--color-primary); }

  /* ============================================================
     BLOB CHIPS (JS-injected)
     ============================================================ */
  .blob-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background-color: var(--color-surface-container-lowest);
    border: 1px solid var(--color-outline-variant);
    border-radius: var(--radius-full);
    padding: 0.375rem 1rem;
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    line-height: var(--text-label-md-lh);
    font-weight: 600;
    color: var(--color-on-surface);
    cursor: pointer;
    transition: background-color 0.2s;
  }
  .blob-chip:hover { background-color: var(--color-surface-container-low); }
  .blob-chip .material-symbols-outlined { font-size: 16px; color: var(--color-primary); }

  /* ============================================================
     UTILITY
     ============================================================ */
  /* ============================================================
     HISTORY PAGE
     ============================================================ */
  .history-filter-bar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  .history-tab-group {
    display: inline-flex;
    border: 1px solid var(--color-outline-variant);
    border-radius: var(--radius);
    overflow: hidden;
  }
  .history-tab {
    padding: 0.5rem 1.25rem;
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    font-weight: 600;
    letter-spacing: var(--text-label-md-ls);
    text-transform: uppercase;
    background: var(--color-surface-container-lowest);
    border: none;
    border-right: 1px solid var(--color-outline-variant);
    color: var(--color-on-surface-variant);
    cursor: pointer;
    transition: background-color 0.15s, color 0.15s;
  }
  .history-tab:last-child { border-right: none; }
  .history-tab:hover { background-color: var(--color-surface-container-low); }
  .history-tab.is-active {
    background-color: var(--color-primary-container);
    color: #fff;
  }

  /* History cards list */
  .history-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .history-card {
    background-color: var(--color-surface-container-lowest);
    border: 1px solid var(--color-outline-variant);
    border-radius: var(--radius-lg);
    overflow: hidden;
  }
  .history-card--approved { border-left: 3px solid var(--color-success-green); }
  .history-card--denied   { border-left: 3px solid var(--color-error-red); }

  .history-card-header {
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    border-bottom: 1px solid var(--color-outline-variant);
    background-color: var(--color-surface-container-low);
  }

  .history-identity {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }
  .history-initials {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: var(--radius-full);
    background-color: rgba(0,67,32,.10);
    color: var(--color-primary-container);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8rem;
    flex-shrink: 0;
  }
  .history-alumni-name {
    font-family: var(--font-serif);
    font-size: var(--text-title-lg-size);
    font-weight: 700;
    color: var(--color-primary);
    margin: 0;
    line-height: 1.2;
  }
  .history-alumni-id {
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    color: var(--color-on-surface-variant);
    margin: 0;
  }

  .history-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
  }
  .history-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-full);
    font-family: var(--font-sans);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
  .history-status-badge--approved {
    background-color: rgba(46,125,50,.12);
    color: var(--color-success-green);
  }
  .history-status-badge--denied {
    background-color: rgba(198,40,40,.10);
    color: var(--color-error-red);
  }
  .history-status-badge .material-symbols-outlined { font-size: 14px; }

  .history-date {
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    color: var(--color-on-surface-variant);
  }

  .history-card-body {
    padding: 1rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .history-changes-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }
  @media (min-width: 640px) {
    .history-changes-grid { grid-template-columns: repeat(2, 1fr); }
  }
  @media (min-width: 1024px) {
    .history-changes-grid { grid-template-columns: repeat(3, 1fr); }
  }

  .history-change-row {
    background-color: var(--color-surface-container);
    border-radius: var(--radius);
    padding: 0.5rem 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
  .history-change-label {
    font-family: var(--font-sans);
    font-size: var(--text-label-md-size);
    font-weight: 600;
    letter-spacing: var(--text-label-md-ls);
    text-transform: uppercase;
    color: var(--color-on-surface-variant);
  }
  .history-change-values {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
  }
  .history-change-old {
    color: var(--color-on-surface-variant);
    text-decoration: line-through;
    opacity: 0.7;
  }
  .history-change-arrow {
    color: var(--color-outline);
    font-size: 14px;
  }
  .history-change-new {
    color: var(--color-primary);
    font-weight: 500;
  }

  .history-comment {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    background-color: var(--color-surface-container);
    border-radius: var(--radius);
    padding: 0.625rem 0.75rem;
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    color: var(--color-on-surface-variant);
  }
  .history-comment .material-symbols-outlined { font-size: 16px; margin-top: 2px; flex-shrink: 0; }

  .history-staff-line {
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    color: var(--color-on-surface-variant);
    display: flex;
    align-items: center;
    gap: 0.375rem;
  }
  .history-staff-line .material-symbols-outlined { font-size: 15px; }

  /* Empty / loading states */
  .history-empty {
    background-color: var(--color-surface-container-lowest);
    border: 1px solid var(--color-outline-variant);
    border-radius: var(--radius-lg);
    padding: 4rem 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 0.75rem;
  }
  .history-empty .material-symbols-outlined { font-size: 40px; color: var(--color-outline); }
  .history-empty-title {
    font-family: var(--font-serif);
    font-size: var(--text-headline-sm-size);
    font-weight: 700;
    color: var(--color-primary);
    margin: 0;
  }
  .history-empty-body {
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    color: var(--color-on-surface-variant);
    margin: 0;
  }

  .history-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .history-showing {
    font-family: var(--font-sans);
    font-size: var(--text-body-md-size);
    color: var(--color-on-surface-variant);
  }

  .hidden { display: none !important; }
  .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
</style>
</head>
<body>

<nav id="mobile-menu" aria-label="Sidebar">
  <div class="sidebar-logo-area">
    <img src="../images/usc_logo.png" alt="USC Logo" />
    <div>
      <p class="sidebar-logo-title">University <i>of</i> <br>San Carlos</p>
      <p class="sidebar-logo-caption">Dugtong Carolinian</p>
    </div>
  </div>

  <ul class="sidebar-nav-primary">
    <li>
      <a class="nav-link" data-target="dashboard" href="#">
        <span class="material-symbols-outlined" data-icon="dashboard">dashboard</span>
        Dashboard
      </a>
    </li>
    <li>
      <a class="nav-link" data-target="directory" href="#">
        <span class="material-symbols-outlined" data-icon="group">group</span>
        Alumni Directory
      </a>
    </li>
    <li>
      <a class="nav-link" data-target="applications" href="#">
        <span class="material-symbols-outlined" data-icon="fact_check">fact_check</span>
        Applications
        <span class="sidebar-badge" id="sidebar-pending-count">4</span>
      </a>
    </li>
    <li>
      <a class="nav-link" data-target="history" href="#">
        <span class="material-symbols-outlined" data-icon="manage_history">manage_history</span>
        Mod History
      </a>
    </li>
  </ul>

  <ul class="sidebar-nav-secondary">
    <li>
      <a class="nav-signout" href="../api/logout.php">
        <span class="material-symbols-outlined" data-icon="logout">logout</span>
        Sign Out
      </a>
    </li>
  </ul>
</nav>

<main>

  <header class="site-header">
    <div class="header-left">
      <button onclick="document.getElementById('mobile-menu').classList.toggle('is-open')" aria-label="Open menu" class="btn-mobile-menu">
        <span class="material-symbols-outlined" data-icon="menu">menu</span>
      </button>
      <div class="header-site-title">Alumni Management Module</div>
    </div>

    <div class="header-search-wrap hidden" id="header-search-wrap">
      <span class="material-symbols-outlined" data-icon="search">search</span>
      <input class="header-search-input" id="global-search" placeholder="Search alumni, applications…" type="text"/>
    </div>

    <div class="header-actions">
      <div class="profile-btn-wrap">
        <button class="profile-btn" onclick="document.getElementById('profile-menu').classList.toggle('hidden')">
          <div class="profile-text">
            <span class="name"><?= htmlspecialchars($staffName) ?></span>
            <span class="role"><?= htmlspecialchars($staffRole) ?> &middot; <?= htmlspecialchars($_SESSION['staff_school_ID'] ?? '00-0000') ?></span>
          </div>
          <div class="avatar"><?= htmlspecialchars($staffInitials) ?></div>
        </button>
        <div id="profile-menu" class="hidden">
          <a href="../api/logout.php">Sign Out</a>
        </div>
      </div>
    </div>
  </header>

  <!-- ── DASHBOARD ─────────────────────────────────────────── -->
  <div class="view-page" id="page-dashboard">
    <div class="page-inner">

      <div class="page-header-row">
        <div>
          <h2 class="page-title">Dashboard Overview</h2>
          <p class="page-subtitle">Welcome back. Here is a summary of the network's current status.</p>
        </div>
        <div class="info-pill">
          <span class="material-symbols-outlined" data-icon="calendar_today">calendar_today</span>
          Last updated: Just now
        </div>
      </div>

      <div class="stats-grid">

        <div class="stat-card stagger-1">
          <div class="stat-card-top">
            <div class="stat-icon">
              <span class="material-symbols-outlined" data-icon="school">school</span>
            </div>
            <span class="badge badge--success">
              <span class="material-symbols-outlined" style="font-size:14px" data-icon="trending_up">trending_up</span>
              +2.4%
            </span>
          </div>
          <div>
            <p class="stat-label">Total Verified Alumni</p>
            <p class="stat-value" id="stat-total-verified">—</p>
          </div>
        </div>

        <div class="stat-card stat-card--warning stagger-2">
          <div class="stat-card-top">
            <div class="stat-icon stat-icon--warning">
              <span class="material-symbols-outlined" data-icon="how_to_reg">how_to_reg</span>
            </div>
            <span class="badge badge--error">Requires Action</span>
          </div>
          <div>
            <p class="stat-label">Pending Verifications</p>
            <p class="stat-value stat-value--secondary" id="dashboard-pending-count">4</p>
          </div>
        </div>

        <button class="stat-card stat-card--clickable stagger-3" onclick="goTo('history')" style="text-align:left;width:100%;cursor:pointer;">
          <div class="stat-card-top">
            <div class="stat-icon">
              <span class="material-symbols-outlined" data-icon="edit_document">edit_document</span>
            </div>
            <span class="badge badge--neutral">Past 7 Days</span>
          </div>
          <div>
            <p class="stat-label">Profile Updates</p>
            <p class="stat-value" id="stat-recent-updates">—</p>
          </div>
        </button>

      </div>

      <div class="action-grid">
        <button class="action-card action-card--primary stagger-4" onclick="goTo('applications')">
          <div>
            <p class="action-card-label">Review Queue</p>
            <p class="action-card-title">Review Modification Requests</p>
          </div>
          <span class="material-symbols-outlined action-arrow" data-icon="arrow_forward">arrow_forward</span>
        </button>
        <button class="action-card action-card--surface stagger-4" onclick="goTo('directory')">
          <div>
            <p class="action-card-label">Directory Access</p>
            <p class="action-card-title">Edit an Alumni Profile</p>
          </div>
          <span class="material-symbols-outlined action-arrow" data-icon="arrow_forward">arrow_forward</span>
        </button>
      </div>

    </div>
  </div>

  <!-- ── DIRECTORY ─────────────────────────────────────────── -->
  <div class="view-page" id="page-directory">
    <div class="page-inner">

      <div class="page-header-row">
        <div>
          <h2 class="page-title">Alumni Directory</h2>
          <p class="page-subtitle">Manage and verify registered university alumni records.</p>
        </div>
        <div class="filter-toolbar">
          <div class="select-wrap">
            <select id="filter-batch" class="filter-select">
              <option value="">All Batches</option>
              <?php foreach ($batchOptions as $yr): ?>
                <option value="<?= htmlspecialchars($yr) ?>"><?= htmlspecialchars($yr) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="material-symbols-outlined" data-icon="expand_more">expand_more</span>
          </div>
          <div class="select-wrap">
            <select id="filter-program" class="filter-select">
              <option value="">All Programs</option>
              <?php foreach ($programOptions as $prog): ?>
                <option value="<?= htmlspecialchars($prog) ?>"><?= htmlspecialchars($prog) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="material-symbols-outlined" data-icon="expand_more">expand_more</span>
          </div>

        </div>
      </div>

      <div class="table-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-checkbox">
                  <input class="table-checkbox" type="checkbox"/>
                </th>
                <th class="th-sortable">
                  <div class="th-inner">Name <span class="material-symbols-outlined sort-icon" data-icon="arrow_downward">arrow_downward</span></div>
                </th>
                <th>Alumni ID</th>
                <th>Batch</th>
                <th>Program</th>
                <th class="col-status">Status</th>
                <th class="col-actions">Actions</th>
              </tr>
            </thead>
            <tbody id="directory-table-body"></tbody>
          </table>
        </div>
        <div class="table-footer">
          <span class="table-footer-info" id="directory-showing-text">Loading…</span>
          <div class="pagination">
            <button id="dir-prev-page" class="btn-page" disabled>
              <span class="material-symbols-outlined" data-icon="chevron_left">chevron_left</span>
            </button>
            <span id="dir-page-indicator" class="page-indicator">1</span>
            <button id="dir-next-page" class="btn-page">
              <span class="material-symbols-outlined" data-icon="chevron_right">chevron_right</span>
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── APPLICATIONS ──────────────────────────────────────── -->
  <div class="view-page" id="page-applications">
    <div class="page-inner">

      <div class="page-header-row">
        <div>
          <h2 class="page-title">Pending Modifications</h2>
          <p class="page-subtitle">Review and approve requested changes to alumni records.</p>
        </div>
        <span class="pending-pill" id="pending-count-pill">4 Pending</span>
      </div>

      <div class="queue-strip" id="queue-strip"></div>

      <div class="review-card" id="review-card">
        <div class="review-card-header">
          <div class="rv-identity">
            <div class="rv-initials-circle" id="rv-initials">JC</div>
            <div>
              <p class="rv-name" id="rv-name">Juan Dela Cruz</p>
              <p class="rv-meta" id="rv-meta">BS in Computer Science, Batch 2015</p>
            </div>
          </div>
          <div class="rv-date-block">
            <p class="rv-date-label">Requested on</p>
            <p class="rv-date-value" id="rv-date">Oct 24, 2023 · 14:30 PHT</p>
          </div>
        </div>

        <div class="review-cols">
          <div class="review-col-panel">
            <h4 class="review-section-title">
              <span class="material-symbols-outlined" data-icon="history">history</span>
              Current Record
            </h4>
            <div class="rv-fields" id="rv-current-fields"></div>
          </div>
          <div class="review-col-panel review-col-panel--changes">
            <h4 class="review-section-title review-section-title--changes">
              <span class="material-symbols-outlined" data-icon="description">description</span>
              Requested Changes
            </h4>
            <div class="rv-fields" id="rv-requested-fields"></div>
          </div>
        </div>

        <div class="review-bottom">
          <div class="review-blobs-panel">
            <p class="blob-section-title">Verification Blobs</p>
            <div class="blobs-list" id="rv-blobs"></div>
          </div>
          <div class="review-comment-panel">
            <label class="comment-label" for="admin-comment">
              Admin Comments <span class="comment-optional">(Optional)</span>
            </label>
            <textarea class="comment-textarea" id="admin-comment" placeholder="Add a note regarding this decision…" rows="3"></textarea>
          </div>
        </div>

        <div class="review-card-footer">
          <button class="btn-deny" id="btn-deny" onclick="decideApplication('deny')">
            <span class="material-symbols-outlined" data-icon="close">close</span>
            Deny Request
          </button>
          <button class="btn-approve" id="btn-approve" onclick="decideApplication('approve')">
            <span class="material-symbols-outlined" data-icon="check">check</span>
            Approve Changes
          </button>
        </div>
      </div>

      <div class="empty-state" id="queue-empty-state">
        <span class="material-symbols-outlined" data-icon="task_alt">task_alt</span>
        <p class="empty-state-title">All caught up</p>
        <p class="empty-state-body">There are no pending modification requests left to review.</p>
      </div>

    </div>
  </div>


  <!-- ── HISTORY ───────────────────────────────────────────── -->
  <div class="view-page" id="page-history">
    <div class="page-inner">

      <div class="page-header-row">
        <div>
          <h2 class="page-title">Modification History</h2>
          <p class="page-subtitle">Approved and denied profile changes from the past 7 days.</p>
        </div>
        <div class="history-filter-bar">
          <div class="history-tab-group">
            <button class="history-tab is-active" data-filter="">All</button>
            <button class="history-tab" data-filter="Approved">Approved</button>
            <button class="history-tab" data-filter="Denied">Denied</button>
          </div>
          <div class="select-wrap">
            <select id="history-days-select" class="filter-select">
              <option value="7">Past 7 days</option>
              <option value="14">Past 14 days</option>
              <option value="30">Past 30 days</option>
              <option value="90">Past 90 days</option>
            </select>
            <span class="material-symbols-outlined" data-icon="expand_more">expand_more</span>
          </div>
        </div>
      </div>

      <div class="history-pagination" id="history-pagination-top" style="display:none;">
        <span class="history-showing" id="history-showing-text"></span>
        <div class="pagination">
          <button id="hist-prev-page" class="btn-page" disabled>
            <span class="material-symbols-outlined">chevron_left</span>
          </button>
          <span id="hist-page-indicator" class="page-indicator">1</span>
          <button id="hist-next-page" class="btn-page">
            <span class="material-symbols-outlined">chevron_right</span>
          </button>
        </div>
      </div>

      <div class="history-list" id="history-list"></div>

      <div class="history-empty hidden" id="history-empty">
        <span class="material-symbols-outlined">manage_history</span>
        <p class="history-empty-title">No records found</p>
        <p class="history-empty-body">There are no approved or denied modifications in this time range.</p>
      </div>

    </div>
  </div>

</main>

<!-- ── ARCHIVE / RESTORE CONFIRMATION MODAL ───────────────── -->
<div id="archive-modal" class="modal-backdrop hidden" aria-hidden="true" role="dialog" aria-labelledby="archive-modal-title" aria-modal="true">
  <div class="modal-box">
    <div class="modal-header">
      <h3 class="modal-title" id="archive-modal-title">Archive Account</h3>
      <button class="modal-close-btn" onclick="closeArchiveModal()" aria-label="Close">
        <span class="material-symbols-outlined" data-icon="close">close</span>
      </button>
    </div>
    <div class="modal-body">
      <div class="modal-icon-wrap modal-icon-wrap--warning">
        <span class="material-symbols-outlined" data-icon="archive">archive</span>
      </div>
      <p class="modal-body-text" id="archive-modal-body"></p>
    </div>
    <div class="modal-footer">
      <button class="modal-btn modal-btn--ghost" onclick="closeArchiveModal()">Cancel</button>
      <button class="modal-btn modal-btn--danger" id="archive-modal-confirm" onclick="confirmArchive()">Archive</button>
    </div>
  </div>
</div>

<script src="pv_staff.js"></script>
</body>
</html>
