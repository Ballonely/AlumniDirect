const API_BASE = "../api/";

// Pages that show the search bar
const SEARCH_PAGES = ['directory', 'applications', 'history'];

function goTo(pageId) {
  document.querySelectorAll('.view-page').forEach(page => {
    const isActivePage = page.id === `page-${pageId}`;
    page.classList.toggle('hidden', !isActivePage);
    page.classList.toggle('active', isActivePage);
  });

  document.querySelectorAll('.nav-link').forEach(link => {
    if (link.dataset.target === pageId) {
      link.classList.add('is-active');
    } else {
      link.classList.remove('is-active');
    }
  });

  // Show search bar only on directory / applications / history
  const searchWrap = document.getElementById('header-search-wrap');
  if (searchWrap) {
    searchWrap.classList.toggle('hidden', !SEARCH_PAGES.includes(pageId));
  }

  const mobileMenu = document.getElementById('mobile-menu');
  if (mobileMenu && !mobileMenu.classList.contains('hidden') && window.innerWidth < 768) {
    mobileMenu.classList.add('hidden');
  }

  if (pageId === 'directory') loadDirectory();
  if (pageId === 'applications') loadPending();
  if (pageId === 'dashboard') loadDashboardStats();
  if (pageId === 'history') loadHistory();
}

document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    goTo(link.dataset.target);
  });
});

// ── Shared fetch helpers ────────────────────────────────────
async function apiGet(path) {
  const res = await fetch(API_BASE + path);
  if (res.status === 401) {
    window.location.href = "pb_login.html?error=session";
    throw new Error("Not authenticated");
  }
  if (!res.ok) throw new Error("API returned status " + res.status);
  const data = await res.json();
  if (data.error) throw new Error(data.error);
  return data;
}

async function apiPost(path, body) {
  const url = API_BASE + path;
  const res = await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });
  if (res.status === 401) {
    window.location.href = "pb_login.html?error=session";
    throw new Error("Not authenticated");
  }
  // Catch 404 before trying to parse the HTML error page as JSON
  if (res.status === 404) {
    throw new Error(`API file not found: ${url} — copy set_archive_status.php into your api/ folder.`);
  }
  const text = await res.text();
  let data;
  try {
    data = JSON.parse(text);
  } catch {
    throw new Error(`API returned non-JSON (HTTP ${res.status}): ${text.slice(0, 200)}`);
  }
  if (!res.ok || data.error) throw new Error(data.error || "Request failed");
  return data;
}

// ── Dashboard ───────────────────────────────────────────────
async function loadDashboardStats() {
  try {
    const stats = await apiGet("get_dashboard_stats.php");
    document.getElementById('stat-total-verified').textContent = stats.totalVerifiedAlumni.toLocaleString();
    document.getElementById('stat-recent-updates').textContent = stats.profileUpdatesLast7Days.toLocaleString();
    document.getElementById('dashboard-pending-count').textContent = stats.pendingVerifications;
    document.getElementById('sidebar-pending-count').textContent = stats.pendingVerifications;
    document.getElementById('sidebar-pending-count').style.display = stats.pendingVerifications > 0 ? 'flex' : 'none';
    document.getElementById('pending-count-pill').textContent = `${stats.pendingVerifications} Pending`;
  } catch (err) {
    console.error("Failed to load dashboard stats:", err);
  }
}

// ── Alumni Directory ────────────────────────────────────────
let directoryPage = 1;

function currentDirectoryQuery() {
  const q = document.getElementById('global-search')?.value.trim() || "";
  const batch = document.getElementById('filter-batch')?.value || "";
  const program = document.getElementById('filter-program')?.value || "";
  const params = new URLSearchParams({ page: directoryPage });
  if (q) params.set('q', q);
  if (batch) params.set('batch', batch);
  if (program) params.set('program', program);
  return params.toString();
}

async function loadDirectory() {
  const tbody = document.getElementById('directory-table-body');
  try {
    const data = await apiGet("get_alumni_directory.php?" + currentDirectoryQuery());
    renderDirectory(data.alumni);

    const start = data.total === 0 ? 0 : (data.page - 1) * data.perPage + 1;
    const end = Math.min(data.page * data.perPage, data.total);
    document.getElementById('directory-showing-text').textContent =
      `Showing ${start} to ${end} of ${data.total} records`;
    document.getElementById('dir-page-indicator').textContent = data.page;
    document.getElementById('dir-prev-page').disabled = data.page <= 1;
    document.getElementById('dir-next-page').disabled = end >= data.total;
  } catch (err) {
    console.error("Failed to load directory:", err);
    tbody.innerHTML = `<tr><td colspan="7" style="padding:2rem;text-align:center;color:var(--color-on-surface-variant)">Could not load the directory. Is the API running?</td></tr>`;
  }
}

function renderDirectory(alumniList) {
  const tbody = document.getElementById('directory-table-body');
  tbody.innerHTML = '';

  if (!alumniList.length) {
    tbody.innerHTML = `<tr><td colspan="7" style="padding:2rem;text-align:center;color:var(--color-on-surface-variant)">No matching alumni records.</td></tr>`;
    return;
  }

  alumniList.forEach(alumni => {
    const isPending  = alumni.status === 'Pending Update';
    const isArchived = !!alumni.is_archived;

    let statusBadge;
    if (isArchived) {
      statusBadge = `<span class="dir-status-badge dir-status-badge--archived">Archived</span>`;
    } else if (isPending) {
      statusBadge = `<span class="dir-status-badge dir-status-badge--pending">Pending Update</span>`;
    } else {
      statusBadge = `<span class="dir-status-badge dir-status-badge--verified">${escapeHtml(alumni.status)}</span>`;
    }

    // Accept any common casing the API might use for the account ID
    const accountId = alumni.accountId ?? alumni.account_ID ?? alumni.account_id ?? null;
    if (!accountId) {
      console.warn('renderDirectory: missing accountId. Keys received from API:', Object.keys(alumni));
    }

    const tr = document.createElement('tr');
    tr.className = 'dir-row' + (isArchived ? ' dir-row--archived' : '');

    const archiveBtn = document.createElement('button');
    archiveBtn.className = 'dir-archive-btn' + (isArchived ? ' dir-archive-btn--restore' : '');
    archiveBtn.title = isArchived ? 'Restore Account' : 'Archive Account';
    archiveBtn.innerHTML = `<span class="material-symbols-outlined">${isArchived ? 'unarchive' : 'archive'}</span>`;
    // Store data on the element — avoids quote-escaping issues with names
    archiveBtn.dataset.accountId  = accountId;
    archiveBtn.dataset.name       = alumni.name;
    archiveBtn.dataset.isArchived = isArchived ? '1' : '0';
    archiveBtn.addEventListener('click', () => {
      openArchiveModal(
        parseInt(archiveBtn.dataset.accountId, 10),
        archiveBtn.dataset.name,
        archiveBtn.dataset.isArchived === '1'
      );
    });

    const actionsTd = document.createElement('td');
    actionsTd.className = 'dir-td dir-td--right';
    actionsTd.appendChild(archiveBtn);

    tr.innerHTML = `
      <td class="dir-td dir-td--checkbox">
        <input type="checkbox" class="table-checkbox">
      </td>
      <td class="dir-td dir-td--name">${escapeHtml(alumni.name)}</td>
      <td class="dir-td dir-td--mono">${escapeHtml(alumni.id)}</td>
      <td class="dir-td">${escapeHtml(String(alumni.batch))}</td>
      <td class="dir-td">${escapeHtml(alumni.program)}</td>
      <td class="dir-td dir-td--center">${statusBadge}</td>
    `;
    tr.appendChild(actionsTd);
    tbody.appendChild(tr);
  });
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

document.getElementById('filter-batch')?.addEventListener('change', () => { directoryPage = 1; loadDirectory(); });
document.getElementById('filter-program')?.addEventListener('change', () => { directoryPage = 1; loadDirectory(); });
document.getElementById('global-search')?.addEventListener('input', debounce(() => { directoryPage = 1; loadDirectory(); }, 350));
document.getElementById('dir-prev-page')?.addEventListener('click', () => { if (directoryPage > 1) { directoryPage--; loadDirectory(); } });
document.getElementById('dir-next-page')?.addEventListener('click', () => { directoryPage++; loadDirectory(); });

function debounce(fn, ms) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

// ── Applications / Verification Queue ──────────────────────
let pendingApplications = [];
let currentReviewIndex = 0;

async function loadPending() {
  try {
    const data = await apiGet("get_pending_modifications.php");
    pendingApplications = data.pending;
    currentReviewIndex = 0;
    loadReviewCard(0);
  } catch (err) {
    console.error("Failed to load pending modifications:", err);
  }
}

function renderQueueStrip() {
  const strip = document.getElementById('queue-strip');
  strip.innerHTML = '';

  pendingApplications.forEach((app, index) => {
    const isActive = index === currentReviewIndex;
    const btn = document.createElement('button');
    btn.className = 'queue-chip' + (isActive ? ' queue-chip--active' : '');
    btn.textContent = app.name;
    btn.onclick = () => loadReviewCard(index);
    strip.appendChild(btn);
  });
}

function loadReviewCard(index) {
  if (pendingApplications.length === 0) {
    document.getElementById('review-card').classList.add('hidden');
    const emptyState = document.getElementById('queue-empty-state');
    emptyState.classList.remove('hidden');
    emptyState.classList.add('is-visible');
    renderQueueStrip();
    return;
  }

  currentReviewIndex = index;
  const app = pendingApplications[index];

  document.getElementById('review-card').classList.remove('hidden');
  const emptyState = document.getElementById('queue-empty-state');
  emptyState.classList.add('hidden');
  emptyState.classList.remove('is-visible');

  document.getElementById('rv-initials').textContent = app.initials;
  document.getElementById('rv-name').textContent = app.name;
  document.getElementById('rv-meta').textContent = app.meta;
  document.getElementById('rv-date').textContent = app.date;

  const currentContainer = document.getElementById('rv-current-fields');
  currentContainer.innerHTML = '';
  app.current.forEach(field => {
    currentContainer.innerHTML += `
      <div>
        <p class="rv-field-label">${escapeHtml(field.label)}</p>
        <p class="rv-field-value rv-field-value--old">${escapeHtml(field.value)}</p>
      </div>
    `;
  });

  const requestedContainer = document.getElementById('rv-requested-fields');
  requestedContainer.innerHTML = '';
  app.requested.forEach(field => {
    requestedContainer.innerHTML += `
      <div>
        <p class="rv-field-label">${escapeHtml(field.label)}</p>
        <p class="rv-field-value rv-field-value--new">${escapeHtml(field.value)}</p>
      </div>
    `;
  });

  const blobsContainer = document.getElementById('rv-blobs');
  blobsContainer.innerHTML = '';
  app.blobs.forEach(blob => {
    blobsContainer.innerHTML += `
      <button class="blob-chip">
        <span class="material-symbols-outlined" data-icon="attach_file">attach_file</span>
        ${escapeHtml(blob.name)}
      </button>
    `;
  });

  document.getElementById('admin-comment').value = '';
  renderQueueStrip();
}

window.decideApplication = async function (decision) {
  if (pendingApplications.length === 0) return;

  const app = pendingApplications[currentReviewIndex];
  const modificationId = parseInt(app.id.replace('req-', ''), 10);
  const comment = document.getElementById('admin-comment').value.trim();

  const card = document.getElementById('review-card');
  card.style.opacity = '0.5';
  card.style.pointerEvents = 'none';

  try {
    await apiPost("decide_modification.php", { modificationId, decision, comment });

    pendingApplications.splice(currentReviewIndex, 1);
    if (currentReviewIndex >= pendingApplications.length) {
      currentReviewIndex = Math.max(0, pendingApplications.length - 1);
    }

    loadReviewCard(currentReviewIndex);
    loadDashboardStats();
  } catch (err) {
    console.error("Failed to decide application:", err);
    alert("Could not save that decision. Please try again.");
  } finally {
    card.style.opacity = '1';
    card.style.pointerEvents = 'auto';
  }
};

// ── Archive / Restore modal ─────────────────────────────────
let _archiveModal = { accountId: null, name: '', isArchived: false };

window.openArchiveModal = function(accountId, name, isArchived) {
  _archiveModal = { accountId, name, isArchived };

  const modal      = document.getElementById('archive-modal');
  const titleEl    = document.getElementById('archive-modal-title');
  const bodyEl     = document.getElementById('archive-modal-body');
  const iconEl     = modal.querySelector('.modal-icon-wrap .material-symbols-outlined');
  const confirmBtn = document.getElementById('archive-modal-confirm');

  if (isArchived) {
    titleEl.textContent    = 'Restore Account';
    bodyEl.innerHTML       = `Restore <strong>${escapeHtml(name)}</strong>? Their account will be reactivated and they will be able to log in again.`;
    confirmBtn.textContent = 'Restore';
    confirmBtn.className   = 'modal-btn modal-btn--primary';
    iconEl.textContent     = 'unarchive';
    modal.querySelector('.modal-icon-wrap').className = 'modal-icon-wrap modal-icon-wrap--success';
  } else {
    titleEl.textContent    = 'Archive Account';
    bodyEl.innerHTML       = `Archive <strong>${escapeHtml(name)}</strong>? Their account will be disabled and they will no longer be able to log in.`;
    confirmBtn.textContent = 'Archive';
    confirmBtn.className   = 'modal-btn modal-btn--danger';
    iconEl.textContent     = 'archive';
    modal.querySelector('.modal-icon-wrap').className = 'modal-icon-wrap modal-icon-wrap--warning';
  }

  modal.classList.remove('hidden');
};

window.closeArchiveModal = function() {
  document.getElementById('archive-modal').classList.add('hidden');
};

window.confirmArchive = async function() {
  const { accountId, name, isArchived } = _archiveModal;
  const confirmBtn = document.getElementById('archive-modal-confirm');
  confirmBtn.disabled = true;
  confirmBtn.textContent = 'Saving…';

  try {
    await apiPost('set_archive_status.php', { accountId, archive: !isArchived });
    closeArchiveModal();
    loadDirectory();
  } catch (err) {
    console.error('Archive action failed:', err);
    // Show the real error message instead of a generic one
    alert(err.message);
  } finally {
    confirmBtn.disabled = false;
    confirmBtn.textContent = isArchived ? 'Restore' : 'Archive';
  }
};

// ── Initial load ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  goTo('dashboard');
});

// ── Modification History ────────────────────────────────────
let historyPage   = 1;
let historyFilter = '';   // '' | 'Approved' | 'Denied'
let historyDays   = 7;

// Tab clicks
document.querySelectorAll('.history-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.history-tab').forEach(t => t.classList.remove('is-active'));
    tab.classList.add('is-active');
    historyFilter = tab.dataset.filter;
    historyPage   = 1;
    loadHistory();
  });
});

// Days dropdown
document.getElementById('history-days-select')?.addEventListener('change', (e) => {
  historyDays = parseInt(e.target.value, 10);
  historyPage = 1;
  loadHistory();
});

// Pagination
document.getElementById('hist-prev-page')?.addEventListener('click', () => {
  if (historyPage > 1) { historyPage--; loadHistory(); }
});
document.getElementById('hist-next-page')?.addEventListener('click', () => {
  historyPage++;
  loadHistory();
});

async function loadHistory() {
  const list     = document.getElementById('history-list');
  const emptyEl  = document.getElementById('history-empty');
  const pagination = document.getElementById('history-pagination-top');

  list.innerHTML = '<p style="padding:2rem;text-align:center;color:var(--color-on-surface-variant)">Loading…</p>';
  emptyEl.classList.add('hidden');

  try {
    const params = new URLSearchParams({
      days:   historyDays,
      page:   historyPage,
    });
    if (historyFilter) params.set('status', historyFilter);

    const data = await apiGet('get_modification_history.php?' + params.toString());
    renderHistory(data.history);

    const start = data.total === 0 ? 0 : (data.page - 1) * data.perPage + 1;
    const end   = Math.min(data.page * data.perPage, data.total);

    if (data.total > 0) {
      pagination.style.display = 'flex';
      document.getElementById('history-showing-text').textContent =
        `Showing ${start}–${end} of ${data.total} records`;
      document.getElementById('hist-page-indicator').textContent = data.page;
      document.getElementById('hist-prev-page').disabled = data.page <= 1;
      document.getElementById('hist-next-page').disabled = end >= data.total;
    } else {
      pagination.style.display = 'none';
    }
  } catch (err) {
    console.error('Failed to load history:', err);
    list.innerHTML = `<p style="padding:2rem;text-align:center;color:var(--color-on-surface-variant)">Could not load history.</p>`;
  }
}

function renderHistory(items) {
  const list    = document.getElementById('history-list');
  const emptyEl = document.getElementById('history-empty');
  list.innerHTML = '';

  if (!items.length) {
    emptyEl.classList.remove('hidden');
    return;
  }

  emptyEl.classList.add('hidden');

  items.forEach(item => {
    const isApproved = item.status === 'Approved';
    const initials   = item.alumniName.split(' ').map(p => p[0] ?? '').join('').toUpperCase().slice(0, 2);

    // Build change rows
    let changesHtml = '';
    if (item.changes.length) {
      const rows = item.changes.map(c => `
        <div class="history-change-row">
          <span class="history-change-label">${escapeHtml(c.label)}</span>
          <div class="history-change-values">
            <span class="history-change-old">${escapeHtml(c.old)}</span>
            <span class="material-symbols-outlined history-change-arrow">arrow_forward</span>
            <span class="history-change-new">${escapeHtml(c.new)}</span>
          </div>
        </div>`).join('');
      changesHtml = `<div class="history-changes-grid">${rows}</div>`;
    }

    // Comment row
    const commentHtml = item.comment ? `
      <div class="history-comment">
        <span class="material-symbols-outlined">comment</span>
        ${escapeHtml(item.comment)}
      </div>` : '';

    const card = document.createElement('div');
    card.className = `history-card history-card--${isApproved ? 'approved' : 'denied'}`;
    card.innerHTML = `
      <div class="history-card-header">
        <div class="history-identity">
          <div class="history-initials">${escapeHtml(initials)}</div>
          <div>
            <p class="history-alumni-name">${escapeHtml(item.alumniName)}</p>
            <p class="history-alumni-id">${escapeHtml(item.alumniId)}</p>
          </div>
        </div>
        <div class="history-meta">
          <span class="history-status-badge history-status-badge--${isApproved ? 'approved' : 'denied'}">
            <span class="material-symbols-outlined">${isApproved ? 'check_circle' : 'cancel'}</span>
            ${item.status}
          </span>
          <span class="history-date">${escapeHtml(item.date)}</span>
        </div>
      </div>
      <div class="history-card-body">
        ${changesHtml}
        ${commentHtml}
        <p class="history-staff-line">
          <span class="material-symbols-outlined">person</span>
          Reviewed by <strong>${escapeHtml(item.staffName)}</strong>
        </p>
      </div>
    `;
    list.appendChild(card);
  });
}
