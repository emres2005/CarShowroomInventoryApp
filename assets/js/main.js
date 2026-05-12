/**
 * main.js — AutoVault Showroom
 * Client-side enhancements: confirm dialogs, search filter, notifications.
 */

/* ── Confirm delete dialog ───────────────────────────────── */
function openConfirm(msg, formId) {
  const overlay = document.getElementById('confirmOverlay');
  const msgEl   = document.getElementById('confirmMsg');
  const btn      = document.getElementById('confirmOk');
  if (!overlay || !msgEl || !btn) return;
  msgEl.textContent = msg;
  overlay.classList.add('open');
  btn.onclick = () => {
    overlay.classList.remove('open');
    document.getElementById(formId)?.submit();
  };
}

function closeConfirm() {
  document.getElementById('confirmOverlay')?.classList.remove('open');
}

/* ── Live table search ───────────────────────────────────── */
function initTableSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;
  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

/* ── Auto-dismiss flash alerts ───────────────────────────── */
function initAlerts() {
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 4500);
  });
}

/* ── Color input preview label ───────────────────────────── */
function initColorPreview() {
  document.querySelectorAll('input[type="color"]').forEach(input => {
    const preview = document.querySelector(`[data-color-for="${input.id}"]`);
    if (preview) {
      preview.style.backgroundColor = input.value;
      input.addEventListener('input', () => { preview.style.backgroundColor = input.value; });
    }
  });
}

/* ── Password visibility toggle ──────────────────────────── */
function togglePassword(btnId, inputId) {
  const btn   = document.getElementById(btnId);
  const input = document.getElementById(inputId);
  if (!btn || !input) return;
  btn.addEventListener('click', () => {
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.textContent = isText ? '👁' : '🙈';
  });
}

/* ── DOMContentLoaded ────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initAlerts();
  initTableSearch('carSearch', 'carsTable');
  initTableSearch('userSearch', 'usersTable');
  initColorPreview();
  togglePassword('togglePw', 'password');
  togglePassword('togglePw2', 'password_confirm');

  // Escape key closes dialog
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeConfirm();
  });

  // Click-outside closes dialog
  document.getElementById('confirmOverlay')?.addEventListener('click', e => {
    if (e.target === e.currentTarget) closeConfirm();
  });
});
