const API_BASE = "../api/";

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

  const mobileMenu = document.getElementById('mobile-menu');
  if (mobileMenu && !mobileMenu.classList.contains('hidden') && window.innerWidth < 768) {
    mobileMenu.classList.add('hidden');
  }

  if (pageId === 'directory') loadDirectory();
  if (pageId === 'applications') loadPending();
  if (pageId === 'dashboard') loadDashboardStats();
}

document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    goTo(link.dataset.target);
  });
});

// ── Shared fetch helper: bounces to staff login on a dead/expired session ──
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
  const res = await fetch(API_BASE + path, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });
  if (res.status === 401) {
    window.location.href = "pb_login.html?error=session";
    throw new Error("Not authenticated");
  }
  const data = await res.json();
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
    tbody.innerHTML = `<tr><td colspan="7" class="py-8 text-center text-on-surface-variant">Could not load the directory. Is the API running?</td></tr>`;
  }
}

function renderDirectory(alumniList) {
  const tbody = document.getElementById('directory-table-body');
  tbody.innerHTML = '';

  if (!alumniList.length) {
    tbody.innerHTML = `<tr><td colspan="7" class="py-8 text-center text-on-surface-variant">No matching alumni records.</td></tr>`;
    return;
  }

  alumniList.forEach(alumni => {
    const isPending = alumni.status === 'Pending Update';
    const statusClass = isPending
      ? 'bg-warning-gold/20 text-secondary'
      : 'bg-success-green/10 text-success-green';

    const tr = document.createElement('tr');
    tr.className = 'border-b border-outline-variant hover:bg-surface-container-highest/50 transition-colors';
    tr.innerHTML = `
      <td class="py-3 px-6 text-center">
        <input type="checkbox" class="rounded-[2px] border-outline-variant text-primary-container focus:ring-primary-container cursor-pointer w-4 h-4">
      </td>
      <td class="py-3 px-6 font-semibold text-on-surface">${escapeHtml(alumni.name)}</td>
      <td class="py-3 px-6 text-on-surface-variant font-mono text-sm">${escapeHtml(alumni.id)}</td>
      <td class="py-3 px-6 text-on-surface-variant">${escapeHtml(String(alumni.batch))}</td>
      <td class="py-3 px-6 text-on-surface-variant">${escapeHtml(alumni.program)}</td>
      <td class="py-3 px-6 text-center">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${statusClass}">
          ${escapeHtml(alumni.status)}
        </span>
      </td>
      <td class="py-3 px-6 text-right">
        <button class="p-2 text-on-surface-variant hover:text-primary transition-colors" title="Edit Record">
          <span class="material-symbols-outlined text-[20px]" data-icon="edit">edit</span>
        </button>
      </td>
    `;
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
    btn.className = `shrink-0 px-4 py-2 rounded-full font-label-md text-label-md transition-colors border ${isActive ? 'bg-primary-container text-white border-primary-container' : 'bg-surface-container-lowest text-on-surface-variant border-outline-variant hover:bg-surface-container-low'}`;
    btn.textContent = app.name;
    btn.onclick = () => loadReviewCard(index);
    strip.appendChild(btn);
  });
}

function loadReviewCard(index) {
  if (pendingApplications.length === 0) {
    document.getElementById('review-card').classList.add('hidden');
    document.getElementById('queue-empty-state').classList.remove('hidden');
    document.getElementById('queue-empty-state').classList.add('flex');
    renderQueueStrip();
    return;
  }

  currentReviewIndex = index;
  const app = pendingApplications[index];

  document.getElementById('review-card').classList.remove('hidden');
  document.getElementById('queue-empty-state').classList.add('hidden');
  document.getElementById('queue-empty-state').classList.remove('flex');

  document.getElementById('rv-initials').textContent = app.initials;
  document.getElementById('rv-name').textContent = app.name;
  document.getElementById('rv-meta').textContent = app.meta;
  document.getElementById('rv-date').textContent = app.date;

  const currentContainer = document.getElementById('rv-current-fields');
  currentContainer.innerHTML = '';
  app.current.forEach(field => {
    currentContainer.innerHTML += `
      <div>
        <p class="font-label-md text-label-md text-on-surface-variant mb-1">${escapeHtml(field.label)}</p>
        <p class="font-body-md text-body-md text-on-surface bg-surface-container-low px-3 py-2 border border-outline-variant rounded-DEFAULT line-through opacity-70">${escapeHtml(field.value)}</p>
      </div>
    `;
  });

  const requestedContainer = document.getElementById('rv-requested-fields');
  requestedContainer.innerHTML = '';
  app.requested.forEach(field => {
    requestedContainer.innerHTML += `
      <div>
        <p class="font-label-md text-label-md text-on-surface-variant mb-1">${escapeHtml(field.label)}</p>
        <p class="font-body-md text-body-md text-primary bg-white px-3 py-2 border border-success-green/30 rounded-DEFAULT font-medium shadow-sm">${escapeHtml(field.value)}</p>
      </div>
    `;
  });

  const blobsContainer = document.getElementById('rv-blobs');
  blobsContainer.innerHTML = '';
  app.blobs.forEach(blob => {
    blobsContainer.innerHTML += `
      <button class="bg-surface-container-lowest border border-outline-variant rounded-full px-4 py-1.5 flex items-center gap-2 hover:bg-surface-container-low transition-colors font-label-md text-label-md text-on-surface">
        <span class="material-symbols-outlined text-[16px] text-primary" data-icon="attach_file">attach_file</span>
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
    loadDashboardStats(); // keep sidebar/dashboard pending counts in sync
  } catch (err) {
    console.error("Failed to decide application:", err);
    alert("Could not save that decision. Please try again.");
  } finally {
    card.style.opacity = '1';
    card.style.pointerEvents = 'auto';
  }
};

// ── Initial load ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  goTo('dashboard');
});
