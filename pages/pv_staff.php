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

session_start();

if (empty($_SESSION['staff_ID'])) {
    header('Location: pb_login.html?error=session');
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
<title>Alumni Network Manager | USC Alumni Admin Portal</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "secondary": "#795900",
                        "surface-container-highest": "#dde4de",
                        "tertiary-container": "#61252f",
                        "secondary-fixed": "#ffdf9f",
                        "on-surface-variant": "#414941",
                        "on-secondary": "#ffffff",
                        "on-primary-fixed-variant": "#15512c",
                        "warning-gold": "#F9BC15",
                        "surface-container-lowest": "#ffffff",
                        "on-primary": "#ffffff",
                        "primary": "#002b12",
                        "surface-container-low": "#eef5ef",
                        "background": "#f4fbf5",
                        "error": "#ba1a1a",
                        "primary-fixed-dim": "#97d5a5",
                        "surface-container": "#e9f0ea",
                        "tertiary-fixed-dim": "#ffb2ba",
                        "on-tertiary-container": "#de8b95",
                        "on-error": "#ffffff",
                        "inverse-surface": "#2b322e",
                        "tertiary-fixed": "#ffd9dc",
                        "on-primary-fixed": "#00210d",
                        "tertiary": "#450f1a",
                        "surface-dim": "#d5dcd6",
                        "primary-container": "#004320",
                        "on-error-container": "#93000a",
                        "primary-fixed": "#b3f1bf",
                        "success-green": "#2E7D32",
                        "secondary-fixed-dim": "#fabd16",
                        "on-tertiary-fixed": "#3b0713",
                        "on-secondary-container": "#6c5000",
                        "on-secondary-fixed": "#261a00",
                        "error-container": "#ffdad6",
                        "surface-container-high": "#e3eae4",
                        "on-tertiary-fixed-variant": "#72323c",
                        "surface-bright": "#f4fbf5",
                        "inverse-primary": "#97d5a5",
                        "surface-variant": "#dde4de",
                        "error-red": "#C62828",
                        "surface-tint": "#306a42",
                        "secondary-container": "#fdc01b",
                        "inverse-on-surface": "#ebf2ed",
                        "background-alt": "#FFFFFF",
                        "on-background": "#161d1a",
                        "on-primary-container": "#75b083",
                        "outline": "#717970",
                        "on-secondary-fixed-variant": "#5c4300",
                        "on-surface": "#161d1a",
                        "on-tertiary": "#ffffff",
                        "outline-variant": "#c0c9be",
                        "surface": "#f4fbf5"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "gutter": "24px",
                        "container-max": "1280px",
                        "margin-mobile": "16px",
                        "unit": "8px",
                        "margin-desktop": "40px"
                    },
                    "fontFamily": {
                        "body-md": ["Inter"],
                        "label-md": ["Inter"],
                        "headline-sm": ["Libre Baskerville"],
                        "body-lg": ["Inter"],
                        "display-lg": ["Libre Baskerville"],
                        "headline-md": ["Libre Baskerville"],
                        "headline-lg-mobile": ["Libre Baskerville"],
                        "title-lg": ["Inter"]
                    },
                    "fontSize": {
                        "body-md": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                        "label-md": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600" }],
                        "headline-sm": ["24px", { "lineHeight": "32px", "fontWeight": "700" }],
                        "body-lg": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                        "display-lg": ["48px", { "lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700" }],
                        "headline-md": ["32px", { "lineHeight": "40px", "fontWeight": "700" }],
                        "headline-lg-mobile": ["28px", { "lineHeight": "36px", "fontWeight": "700" }],
                        "title-lg": ["20px", { "lineHeight": "28px", "fontWeight": "600" }]
                    }
                }
            }
        }
    </script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;family=Libre+Baskerville:wght@400;700&amp;display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../styles/pv_staff.css">
<style>
  nav .nav-link,
  nav .nav-link:visited {
    color: rgba(255, 255, 255, 0.72) !important;
  }
  nav .nav-link:hover {
    color: #ffffff !important;
    background-color: rgba(255, 255, 255, 0.08) !important;
  }
  nav .nav-link.is-active {
    color: #ffffff !important;
    background-color: rgba(255, 255, 255, 0.12) !important;
    border-left: 3px solid #fabd16;
    padding-left: calc(1rem - 3px);
    font-weight: 600;
  }
  nav .nav-link.is-active .material-symbols-outlined {
    color: #fabd16 !important;
  }
</style>
</head>
<body class="bg-background text-on-background font-body-md text-body-md flex min-h-screen">

<nav id="mobile-menu" aria-label="Sidebar" class="bg-primary-container h-screen w-64 fixed left-0 top-0 border-r border-outline-variant z-50 flex flex-col py-unit hidden md:flex transition-transform">
  <div class="px-gutter mb-8 mt-4 flex items-center gap-3">
    <img src="../images/usc_logo.png" alt="USC Logo" class="w-14 h-14 shrink-0 rounded-full object-contain bg-white border border-outline-variant" />
    <div>
      <h1 class="font-headline-sm text-headline-sm text-white m-0 leading-tight">USC Alumni</h1>
      <p class="font-label-md text-label-md text-primary-fixed-dim uppercase tracking-wider mt-1">Admin Portal</p>
    </div>
  </div>

  <ul class="flex-1 flex flex-col gap-1 px-unit list-none p-0 m-0">
    <li>
      <a class="nav-link flex items-center gap-3 px-4 py-3 rounded-DEFAULT transition-colors duration-200 ease-in-out font-body-md text-body-md" data-target="dashboard" href="#">
        <span class="material-symbols-outlined" data-icon="dashboard">dashboard</span>
        Dashboard
      </a>
    </li>
    <li>
      <a class="nav-link flex items-center gap-3 px-4 py-3 rounded-DEFAULT transition-colors duration-200 ease-in-out font-body-md text-body-md" data-target="directory" href="#">
        <span class="material-symbols-outlined" data-icon="group">group</span>
        Alumni Directory
      </a>
    </li>
    <li>
      <a class="nav-link flex items-center gap-3 px-4 py-3 rounded-DEFAULT transition-colors duration-200 ease-in-out font-body-md text-body-md" data-target="applications" href="#">
        <span class="material-symbols-outlined" data-icon="fact_check">fact_check</span>
        Applications
        <span class="ml-auto bg-error-red text-white text-[10px] font-bold leading-none rounded-full w-5 h-5 flex items-center justify-center" id="sidebar-pending-count">4</span>
      </a>
    </li>
  </ul>

  <ul class="flex flex-col gap-1 px-unit pb-4 border-t border-white/10 pt-4 list-none m-0">
    <li>
      <a class="flex items-center gap-3 px-4 py-2 rounded-DEFAULT text-white/60 hover:text-white hover:bg-white/10 transition-colors duration-200 ease-in-out font-body-md text-body-md" href="../api/logout.php">
        <span class="material-symbols-outlined" data-icon="logout">logout</span>
        Sign Out
      </a>
    </li>
  </ul>
</nav>

<main class="flex-1 ml-0 md:ml-64 flex flex-col min-h-screen">

  <header class="bg-surface top-0 sticky z-40 border-b border-outline-variant flex justify-between items-center h-16 px-margin-desktop shadow-sm">
    <div class="flex items-center gap-4">
      <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" aria-label="Open menu" class="md:hidden text-primary hover:text-primary-container transition-colors">
        <span class="material-symbols-outlined" data-icon="menu">menu</span>
      </button>
      <div class="font-headline-sm text-headline-sm text-primary hidden md:block">
        Alumni Network Manager
      </div>
    </div>

    <div class="flex-1 max-w-md mx-8 hidden sm:block relative">
      <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" data-icon="search">search</span>
      <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-full py-2 pl-10 pr-4 font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary-container focus:ring-2 focus:ring-primary-container/50 transition-all duration-300 ease-in-out placeholder-outline" id="global-search" placeholder="Search alumni, applications…" type="text"/>
    </div>

    <div class="flex items-center gap-6">
      <div class="flex items-center gap-4">
        <button aria-label="Notifications" class="relative text-on-surface-variant hover:text-primary transition-all active:opacity-70">
          <span class="material-symbols-outlined" data-icon="notifications">notifications</span>
          <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-error-red"></span>
        </button>
        <button aria-label="History" class="text-on-surface-variant hover:text-primary transition-all active:opacity-70">
          <span class="material-symbols-outlined" data-icon="history">history</span>
        </button>
      </div>
      <div class="h-8 w-px bg-outline-variant hidden sm:block"></div>
      <div class="relative">
        <button class="flex items-center gap-3 text-primary font-bold hover:opacity-80 transition-opacity" onclick="document.getElementById('profile-menu').classList.toggle('hidden')">
          <div class="text-right hidden sm:block leading-tight">
            <span class="font-label-md text-label-md uppercase block"><?= htmlspecialchars($staffName) ?></span>
            <span class="text-[11px] text-on-surface-variant font-normal normal-case"><?= htmlspecialchars($staffRole) ?> &middot; <?= htmlspecialchars($_SESSION['staff_school_ID'] ?? '00-0000') ?></span>
          </div>
          <div class="w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center text-xs font-bold border border-outline-variant shrink-0"><?= htmlspecialchars($staffInitials) ?></div>
        </button>
        <div id="profile-menu" class="hidden absolute right-0 top-12 bg-surface-container-lowest border border-outline-variant rounded-DEFAULT shadow-lg w-40 py-1 z-50">
          <a href="../api/logout.php" class="block px-4 py-2 text-body-md text-on-surface hover:bg-surface-container-low transition-colors">Sign Out</a>
        </div>
      </div>
    </div>
  </header>

  <div class="view-page flex-col flex-1" id="page-dashboard">
    <div class="p-gutter lg:px-margin-desktop flex-1 max-w-container-max mx-auto w-full flex flex-col gap-8">

      <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
          <h2 class="font-headline-md text-headline-md text-primary mb-1">Dashboard Overview</h2>
          <p class="font-body-md text-body-md text-on-surface-variant">Welcome back. Here is a summary of the network's current status.</p>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-DEFAULT py-2 px-4 flex items-center gap-2 text-body-md text-on-surface-variant">
          <span class="material-symbols-outlined text-[18px]" data-icon="calendar_today">calendar_today</span>
          Last updated: Just now
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6 flex flex-col gap-4 stagger-1">
          <div class="flex justify-between items-start">
            <div class="w-12 h-12 rounded-DEFAULT bg-surface-container-low flex items-center justify-center text-primary-container">
              <span class="material-symbols-outlined" data-icon="school">school</span>
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-success-green/10 text-success-green">
              <span class="material-symbols-outlined text-[14px]" data-icon="trending_up">trending_up</span>
              +2.4%
            </span>
          </div>
          <div>
            <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-1">Total Verified Alumni</p>
            <p class="font-headline-md text-headline-md text-primary leading-none" id="stat-total-verified">—</p>
          </div>
        </div>

        <div class="bg-surface-container-lowest border-l-2 border-warning-gold border-t border-r border-b border-outline-variant rounded-lg p-6 flex flex-col gap-4 stagger-2">
          <div class="flex justify-between items-start">
            <div class="w-12 h-12 rounded-DEFAULT bg-warning-gold/15 flex items-center justify-center text-secondary">
              <span class="material-symbols-outlined" data-icon="how_to_reg">how_to_reg</span>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-error-red/10 text-error-red">
              Requires Action
            </span>
          </div>
          <div>
            <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-1">Pending Verifications</p>
            <p class="font-headline-md text-headline-md text-secondary leading-none" id="dashboard-pending-count">4</p>
          </div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6 flex flex-col gap-4 stagger-3">
          <div class="flex justify-between items-start">
            <div class="w-12 h-12 rounded-DEFAULT bg-surface-container-low flex items-center justify-center text-primary-container">
              <span class="material-symbols-outlined" data-icon="edit_document">edit_document</span>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-surface-container-high text-on-surface-variant">
              Past 7 Days
            </span>
          </div>
          <div>
            <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-1">Profile Updates</p>
            <p class="font-headline-md text-headline-md text-primary leading-none" id="stat-recent-updates">—</p>
          </div>
        </div>

      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <button class="text-left bg-primary-container text-white rounded-lg p-6 flex items-center justify-between gap-4 hover:bg-primary transition-colors group stagger-4" onclick="goTo('applications')">
          <div>
            <p class="font-label-md text-label-md text-primary-fixed-dim uppercase tracking-wider mb-1">Review Queue</p>
            <p class="font-headline-sm text-headline-sm">Review Modification Requests</p>
          </div>
          <span class="material-symbols-outlined text-[32px] opacity-70 group-hover:translate-x-1 transition-transform" data-icon="arrow_forward">arrow_forward</span>
        </button>
        <button class="text-left bg-surface-container-lowest border border-outline-variant rounded-lg p-6 flex items-center justify-between gap-4 hover:bg-surface-container-low transition-colors group stagger-4" onclick="goTo('directory')">
          <div>
            <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-1">Directory Access</p>
            <p class="font-headline-sm text-headline-sm text-primary">Edit an Alumni Profile</p>
          </div>
          <span class="material-symbols-outlined text-[32px] text-on-surface-variant group-hover:translate-x-1 transition-transform" data-icon="arrow_forward">arrow_forward</span>
        </button>
      </div>

    </div>
  </div>

  <div class="view-page flex-col flex-1" id="page-directory">
    <div class="p-gutter lg:px-margin-desktop flex-1 max-w-container-max mx-auto w-full flex flex-col gap-8">

      <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
          <h2 class="font-headline-md text-headline-md text-primary mb-1">Alumni Directory</h2>
          <p class="font-body-md text-body-md text-on-surface-variant">Manage and verify registered university alumni records.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative">
            <select id="filter-batch" class="appearance-none bg-surface-container-lowest border border-outline-variant rounded-DEFAULT py-2 pl-4 pr-10 font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary-container hover:bg-surface-container-low transition-all duration-300 ease-in-out cursor-pointer">
              <option value="">All Batches</option>
              <?php foreach ($batchOptions as $yr): ?>
                <option value="<?= htmlspecialchars($yr) ?>"><?= htmlspecialchars($yr) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none text-[20px]" data-icon="expand_more">expand_more</span>
          </div>
          <div class="relative">
            <select id="filter-program" class="appearance-none bg-surface-container-lowest border border-outline-variant rounded-DEFAULT py-2 pl-4 pr-10 font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary-container hover:bg-surface-container-low transition-all duration-300 ease-in-out cursor-pointer">
              <option value="">All Programs</option>
              <?php foreach ($programOptions as $prog): ?>
                <option value="<?= htmlspecialchars($prog) ?>"><?= htmlspecialchars($prog) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none text-[20px]" data-icon="expand_more">expand_more</span>
          </div>
          <button class="bg-surface-container-lowest border border-outline-variant rounded-DEFAULT py-2 px-4 font-label-md text-label-md text-on-surface uppercase tracking-wider hover:bg-surface-container-low transition-colors flex items-center gap-2 font-bold">
            <span class="material-symbols-outlined text-[18px]" data-icon="filter_list">filter_list</span>
            More Filters
          </button>
          <button class="bg-primary-container text-white border-2 border-primary-container rounded-DEFAULT py-1.5 px-6 font-label-md text-label-md uppercase tracking-wider hover:bg-primary transition-all duration-300 font-bold flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]" data-icon="download">download</span>
            Export
          </button>
        </div>
      </div>

      <div class="bg-surface-container-lowest border border-outline-variant rounded-lg overflow-hidden flex-1 flex flex-col">
        <div class="overflow-x-auto flex-1">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-surface-container-low border-b border-outline-variant">
                <th class="py-4 px-6 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider w-12 text-center">
                  <input class="rounded-[2px] border-outline-variant text-primary-container focus:ring-primary-container cursor-pointer w-4 h-4" type="checkbox"/>
                </th>
                <th class="py-4 px-6 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider cursor-pointer hover:text-primary transition-colors group">
                  <div class="flex items-center gap-1">Name <span class="material-symbols-outlined text-[16px] opacity-0 group-hover:opacity-100 transition-opacity" data-icon="arrow_downward">arrow_downward</span></div>
                </th>
                <th class="py-4 px-6 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Alumni ID</th>
                <th class="py-4 px-6 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Batch</th>
                <th class="py-4 px-6 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Program</th>
                <th class="py-4 px-6 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-center">Status</th>
                <th class="py-4 px-6 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="font-body-md text-body-md" id="directory-table-body">
              </tbody>
          </table>
        </div>
        <div class="bg-surface-container-low border-t border-outline-variant p-4 flex items-center justify-between">
          <span class="font-body-md text-body-md text-on-surface-variant" id="directory-showing-text">Loading…</span>
          <div class="flex items-center gap-2">
            <button id="dir-prev-page" class="p-1 rounded-DEFAULT hover:bg-surface-container-highest text-on-surface-variant disabled:opacity-50 transition-colors" disabled>
              <span class="material-symbols-outlined" data-icon="chevron_left">chevron_left</span>
            </button>
            <span id="dir-page-indicator" class="w-8 h-8 rounded-DEFAULT bg-primary-container text-white font-label-md flex items-center justify-center">1</span>
            <button id="dir-next-page" class="p-1 rounded-DEFAULT hover:bg-surface-container-highest text-on-surface-variant transition-colors">
              <span class="material-symbols-outlined" data-icon="chevron_right">chevron_right</span>
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>

  <div class="view-page flex-col flex-1" id="page-applications">
    <div class="p-gutter lg:px-margin-desktop flex-1 max-w-container-max mx-auto w-full flex flex-col gap-8">

      <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
          <h2 class="font-headline-md text-headline-md text-primary mb-1">Pending Modifications</h2>
          <p class="font-body-md text-body-md text-on-surface-variant">Review and approve requested changes to alumni records.</p>
        </div>
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-label-md font-label-md bg-surface-container-high text-on-surface-variant" id="pending-count-pill">4 Pending</span>
      </div>

      <div class="flex items-center gap-3 overflow-x-auto pb-1" id="queue-strip"></div>

      <div class="bg-surface-container-lowest border border-outline-variant rounded-lg overflow-hidden" id="review-card">
        <div class="bg-surface-container-low px-6 py-4 border-b border-outline-variant flex justify-between items-center flex-wrap gap-2">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-full bg-primary-container/10 flex items-center justify-center text-primary-container font-bold text-sm" id="rv-initials">JC</div>
            <div>
              <p class="font-headline-sm text-headline-sm text-primary leading-tight" id="rv-name">Juan Dela Cruz</p>
              <p class="text-body-md text-on-surface-variant" id="rv-meta">BS in Computer Science, Batch 2015</p>
            </div>
          </div>
          <div class="text-right">
            <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Requested on</p>
            <p class="text-body-md text-on-surface font-semibold" id="rv-date">Oct 24, 2023 · 14:30 PHT</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-outline-variant">
          <div class="p-6">
            <h4 class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant flex items-center gap-2 mb-4">
              <span class="material-symbols-outlined text-[18px]" data-icon="history">history</span>
              Current Record
            </h4>
            <div class="flex flex-col gap-4" id="rv-current-fields"></div>
          </div>
          <div class="p-6 bg-success-green/[0.03]">
            <h4 class="font-label-md text-label-md uppercase tracking-wider text-secondary flex items-center gap-2 mb-4">
              <span class="material-symbols-outlined text-[18px]" data-icon="description">description</span>
              Requested Changes
            </h4>
            <div class="flex flex-col gap-4" id="rv-requested-fields"></div>
          </div>
        </div>

        <div class="border-t border-outline-variant grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-outline-variant">
          <div class="p-6">
            <h4 class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant mb-3">Verification Blobs</h4>
            <div class="flex flex-wrap gap-2" id="rv-blobs"></div>
          </div>
          <div class="p-6">
            <label class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant mb-3 block" for="admin-comment">Admin Comments <span class="font-normal normal-case text-on-surface-variant/70">(Optional)</span></label>
            <textarea class="w-full bg-white border border-outline-variant rounded-DEFAULT py-2.5 px-3 text-on-surface focus:outline-none focus:border-primary-container focus:ring-1 focus:ring-primary-container transition-shadow resize-none" id="admin-comment" placeholder="Add a note regarding this decision…" rows="3"></textarea>
          </div>
        </div>

        <div class="bg-surface-container px-6 py-4 border-t border-outline-variant flex justify-end gap-3">
          <button class="bg-transparent border-2 border-error-red text-error-red rounded-DEFAULT py-2 px-6 font-label-md text-label-md uppercase tracking-wider hover:bg-error-red/5 transition-colors font-bold flex items-center gap-2" id="btn-deny" onclick="decideApplication('deny')">
            <span class="material-symbols-outlined text-[18px]" data-icon="close">close</span>
            Deny Request
          </button>
          <button class="bg-primary-container text-white border-2 border-primary-container rounded-DEFAULT py-2 px-6 font-label-md text-label-md uppercase tracking-wider hover:bg-primary transition-colors font-bold shadow-sm flex items-center gap-2" id="btn-approve" onclick="decideApplication('approve')">
            <span class="material-symbols-outlined text-[18px]" data-icon="check">check</span>
            Approve Changes
          </button>
        </div>
      </div>

      <div class="hidden bg-surface-container-lowest border border-outline-variant rounded-lg p-16 flex-col items-center justify-center text-center gap-3" id="queue-empty-state">
        <span class="material-symbols-outlined text-[40px] text-success-green" data-icon="task_alt">task_alt</span>
        <p class="font-headline-sm text-headline-sm text-primary">All caught up</p>
        <p class="text-body-md text-on-surface-variant max-w-sm">There are no pending modification requests left to review.</p>
      </div>

    </div>
  </div>

</main>

<script src="pv_staff.js"></script>
</body>
</html>
