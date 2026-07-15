# Complete Setup Guide — Staff/Admin Panel

Everything here has been rewritten to match your **actual** `db.php` and
`login.php` (global `$pdo`, no invented helper functions) — not the
earlier draft. Copy-paste guide below, in order.

---

## 1. Database — run these in phpMyAdmin (or `mysql -u root < file.sql`), in order

1. Your existing `schema.sql`
2. **`schema_staff_additions.sql`** (included here) — adds `modification_detail`,
   `modification_attachment`, and a real `status` column on `modifications`.
   Without this, Approve/Deny has no data to act on.
3. Your existing `random_data.sql`
4. **`staff_seed.sql`** (included here) — creates the first staff account.
5. **`modifications_seed.sql`** (included here, optional) — 2 sample pending
   requests so the Applications tab has something to show immediately.

---

## 2. Files — copy into your existing folders

| File | Destination | Action |
|---|---|---|
| `api/login.php` | `api/login.php` | **Overwrite your existing one.** See "what changed" below. |
| `api/require_staff.php` | `api/require_staff.php` | New file. |
| `api/get_dashboard_stats.php` | `api/get_dashboard_stats.php` | New file. |
| `api/get_alumni_directory.php` | `api/get_alumni_directory.php` | New file. |
| `api/get_pending_modifications.php` | `api/get_pending_modifications.php` | New file. |
| `api/decide_modification.php` | `api/decide_modification.php` | New file. |
| `pages/pb_login.html` | `pages/pb_login.html` | **Overwrite your existing one.** Only change: the email field now also accepts a staff ID (`type="email"` → `type="text"`, label updated). |
| `pages/pv_staff.php` | `pages/pv_staff.php` | New file — this is your old `index.html`, rebuilt: session-gated like `pv_main.php`, trimmed to Dashboard / Alumni Directory / Applications / Sign Out only. |
| `pages/pv_staff.js` | `pages/pv_staff.js` | New file — was `script.js`, now pulls real data instead of mock arrays. |
| `styles/pv_staff.css` | `styles/pv_staff.css` | New file. |

You do **not** need to touch `db.php`, `get_account.php`, `get_alumni.php`,
`get_alumni_detail.php`, `get_image.php`, `logout.php`, `register.php`,
`student_*.php`, or `update_account.php` — all untouched.

---

## 3. What changed in `login.php`, exactly

Your original just checked email + password and sent everyone to
`pv_main.php`. The new version:

```php
$stmt = $pdo->prepare(
    'SELECT a.account_ID, a.first_Name, a.last_Name, a.school_ID, a.password,
            s.staff_ID, s.staff_level
     FROM account a
     LEFT JOIN staff s ON s.account_ID = a.account_ID
     WHERE a.email = ? OR a.school_ID = ?'
);
$stmt->execute([$identifier, $identifier]);
```

— looks the person up by **either** their email **or** their `school_ID`
(so `00-1001` works in the same field as `Firstadmin123@gmail.com`), then
after the password check, looks at whether that account has a matching
row in `staff`. If yes, it's staff → `pv_staff.php`. If no → `pv_main.php`,
exactly like before. Nothing about the alumni login path changed.

---

## 4. Test it

Go to `pages/pb_login.html`, sign in with either:
- **Email:** `Firstadmin123@gmail.com`
- **Staff ID:** `00-1001`
- **Password:** `Admin123!`

You should land on `pages/pv_staff.php` with:
- **Dashboard** — real counts (verified alumni, pending verifications, updates in the last 7 days)
- **Alumni Directory** — real records, with working search/filter/pagination
- **Applications** — the 2 seeded pending requests, with real old/new value diffs. Approve writes the change into `account`; Deny just marks it denied.
- **Sign Out** — top-right profile menu, or bottom of the sidebar. Uses your existing `api/logout.php`, unchanged.

Log in with a regular alumni email/password instead, and you land on
`pv_main.php` exactly as before — the alumni flow is untouched.

---

## 5. One limitation worth knowing

`decide_modification.php` only writes to a whitelisted set of `account`
columns (name, contact info, bio, employer, occupation). If you later let
alumni request changes to `graduation` or `awards` too, that whitelist
and the apply-logic need to grow to match. Happy to extend it when you
get there.
