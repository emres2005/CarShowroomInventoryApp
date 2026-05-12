<?php
/**
 * index.php — Dashboard
 */
require 'config.php';
require 'layout.php';

requireLogin();

$pdo = getPDO();

// ── Stats ─────────────────────────────────────────────────
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status='available')  AS available,
        SUM(status='sold')       AS sold,
        SUM(status='reserved')   AS reserved,
        SUM(price)               AS total_value,
        AVG(price)               AS avg_price
    FROM cars
")->fetch();

// ── Recent 5 cars ─────────────────────────────────────────
$recent = $pdo->query("
    SELECT id, brand, car_model, plate_number, color, status, price, created_at
    FROM cars
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();

// ── By fuel type ──────────────────────────────────────────
$byFuel = $pdo->query("
    SELECT fuel_type, COUNT(*) AS cnt FROM cars GROUP BY fuel_type ORDER BY cnt DESC
")->fetchAll();

renderHeader('Dashboard');

$fmtPrice = fn($v) => $v ? '€ ' . number_format($v, 0, '.', ',') : '—';
$statusBadge = fn($s) => "<span class=\"badge badge-{$s}\">{$s}</span>";
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Welcome back, <strong><?= h($_SESSION['username']) ?></strong> — here's your showroom at a glance.</p>
  </div>
  <?php if (isAdmin()): ?>
    <a href="add_car.php" class="btn btn-primary">＋ Add Car</a>
  <?php endif; ?>
</div>

<!-- Stats grid -->
<div class="stats-grid">
  <div class="stat-card">
    <span class="stat-icon">🚗</span>
    <span class="stat-label">Total Cars</span>
    <span class="stat-value"><?= (int)$stats['total'] ?></span>
  </div>
  <div class="stat-card">
    <span class="stat-icon">✅</span>
    <span class="stat-label">Available</span>
    <span class="stat-value" style="color:var(--success)"><?= (int)$stats['available'] ?></span>
  </div>
  <div class="stat-card">
    <span class="stat-icon">🏷️</span>
    <span class="stat-label">Reserved</span>
    <span class="stat-value" style="color:var(--warning)"><?= (int)$stats['reserved'] ?></span>
  </div>
  <div class="stat-card">
    <span class="stat-icon">🤝</span>
    <span class="stat-label">Sold</span>
    <span class="stat-value" style="color:var(--danger)"><?= (int)$stats['sold'] ?></span>
  </div>
  <div class="stat-card">
    <span class="stat-icon">💰</span>
    <span class="stat-label">Total Value</span>
    <span class="stat-value" style="font-size:1.3rem"><?= $fmtPrice($stats['total_value']) ?></span>
    <span class="stat-sub">avg <?= $fmtPrice($stats['avg_price']) ?></span>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;flex-wrap:wrap;">

  <!-- Recent additions -->
  <div class="card">
    <div class="card-title">Recent Additions</div>
    <?php if ($recent): ?>
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Car</th><th>Plate</th><th>Color</th><th>Status</th><th>Price</th><th>Added</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $c): ?>
          <tr>
            <td><strong><?= h($c['brand']) ?> <?= h($c['car_model']) ?></strong></td>
            <td><code><?= h($c['plate_number']) ?></code></td>
            <td>
              <span class="color-swatch" style="background:<?= h($c['color']) ?>"></span>
              <?= h($c['color']) ?>
            </td>
            <td><?= $statusBadge(h($c['status'])) ?></td>
            <td><?= $fmtPrice($c['price']) ?></td>
            <td style="color:var(--text-muted);font-size:.8rem"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="margin-top:.75rem;text-align:right">
      <a href="cars.php" class="btn btn-ghost btn-sm">View All →</a>
    </div>
    <?php else: ?>
      <p style="color:var(--text-muted)">
        No cars in inventory yet.
        <?php if (isAdmin()): ?><a href="add_car.php">Add one now →</a><?php endif; ?>
      </p>
    <?php endif; ?>
  </div>

  <!-- Fuel breakdown -->
  <div class="card">
    <div class="card-title">By Fuel Type</div>
    <?php if ($byFuel): ?>
      <?php foreach ($byFuel as $row): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid var(--border);">
          <span style="text-transform:capitalize;font-size:.9rem"><?= h($row['fuel_type']) ?></span>
          <span class="badge badge-available"><?= (int)$row['cnt'] ?></span>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="color:var(--text-muted)">No data.</p>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
    <div style="margin-top:1.2rem;padding-top:1rem;border-top:1px solid var(--border)">
      <a href="backup.php" class="btn btn-warning" style="width:100%;justify-content:center;">
        💾 Manage Backups
      </a>
    </div>
    <?php endif; ?>
  </div>

</div>

<?php renderFooter(); ?>
