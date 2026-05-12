<?php
/**
 * cars.php — Full inventory listing with search, filter, sort
 */
require 'config.php';
require 'layout.php';
requireLogin();

$pdo = getPDO();

// ── Build WHERE clause from GET params ────────────────────
$where  = [];
$params = [];

if (!empty($_GET['q'])) {
    $where[]       = '(brand LIKE :q OR car_model LIKE :q OR plate_number LIKE :q)';
    $params[':q']  = '%' . $_GET['q'] . '%';
}
if (!empty($_GET['status'])) {
    $where[]          = 'status = :status';
    $params[':status'] = $_GET['status'];
}
if (!empty($_GET['fuel'])) {
    $where[]        = 'fuel_type = :fuel';
    $params[':fuel'] = $_GET['fuel'];
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Sort ──────────────────────────────────────────────────
$allowedSort = ['brand','car_model','plate_number','year','price','status','created_at'];
$sort  = in_array($_GET['sort'] ?? '', $allowedSort) ? $_GET['sort'] : 'created_at';
$order = ($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$flipOrder = ($order === 'ASC') ? 'desc' : 'asc';

$stmt = $pdo->prepare("
    SELECT id, brand, car_model, plate_number, color, year, mileage,
           price, fuel_type, status, created_at
    FROM cars
    {$whereSql}
    ORDER BY {$sort} {$order}
");
$stmt->execute($params);
$cars = $stmt->fetchAll();

$sortLink = fn($col, $label) =>
    "<a href=\"cars.php?" . http_build_query(array_merge($_GET, ['sort'=>$col,'order'=>($sort===$col?$flipOrder:'asc')])) . "\"
        style=\"color:inherit;text-decoration:none\">{$label}" .
    ($sort===$col ? ($order==='ASC'?' ↑':' ↓') : '') . "</a>";

renderHeader('Inventory');
?>

<!-- Confirm delete dialog -->
<div class="dialog-overlay" id="confirmOverlay">
  <div class="dialog-box">
    <h3>⚠️ Confirm Delete</h3>
    <p id="confirmMsg"></p>
    <div class="dialog-actions">
      <button class="btn btn-ghost" onclick="closeConfirm()">Cancel</button>
      <button class="btn btn-danger" id="confirmOk">Delete</button>
    </div>
  </div>
</div>

<div class="page-header">
  <div>
    <h1 class="page-title">Inventory</h1>
    <p class="page-subtitle"><?= count($cars) ?> car<?= count($cars)!=1?'s':'' ?> found</p>
  </div>
  <?php if (isAdmin()): ?>
    <a href="add_car.php" class="btn btn-primary">＋ Add Car</a>
  <?php endif; ?>
</div>

<!-- Filters -->
<form method="get" action="cars.php" class="search-bar" id="filterForm">
  <input type="text" id="carSearch" name="q" class="form-control"
         placeholder="🔍 Search brand, model, plate…"
         value="<?= h($_GET['q'] ?? '') ?>">

  <select name="status" class="form-control" style="max-width:160px" onchange="this.form.submit()">
    <option value="">All Statuses</option>
    <?php foreach(['available','sold','reserved'] as $s): ?>
      <option value="<?= $s ?>" <?= ($_GET['status']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>

  <select name="fuel" class="form-control" style="max-width:160px" onchange="this.form.submit()">
    <option value="">All Fuels</option>
    <?php foreach(['petrol','diesel','electric','hybrid','lpg','other'] as $f): ?>
      <option value="<?= $f ?>" <?= ($_GET['fuel']??'')===$f?'selected':'' ?>><?= ucfirst($f) ?></option>
    <?php endforeach; ?>
  </select>

  <button type="submit" class="btn btn-primary">Filter</button>
  <?php if (!empty($_GET['q']) || !empty($_GET['status']) || !empty($_GET['fuel'])): ?>
    <a href="cars.php" class="btn btn-ghost">Clear</a>
  <?php endif; ?>
</form>

<div class="table-wrapper">
  <table class="data-table" id="carsTable">
    <thead>
      <tr>
        <th>#</th>
        <th><?= $sortLink('brand','Brand / Model') ?></th>
        <th><?= $sortLink('plate_number','Plate') ?></th>
        <th>Color</th>
        <th><?= $sortLink('year','Year') ?></th>
        <th>Mileage</th>
        <th><?= $sortLink('price','Price') ?></th>
        <th>Fuel</th>
        <th><?= $sortLink('status','Status') ?></th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($cars): ?>
      <?php foreach ($cars as $i => $c): ?>
      <tr>
        <td style="color:var(--text-dim);font-size:.8rem"><?= (int)$c['id'] ?></td>
        <td>
          <strong><?= h($c['brand']) ?></strong>
          <span style="color:var(--text-muted)"> <?= h($c['car_model']) ?></span>
        </td>
        <td><code><?= h($c['plate_number']) ?></code></td>
        <td>
          <span class="color-swatch" style="background:<?= h($c['color']) ?>"></span>
          <small><?= h($c['color']) ?></small>
        </td>
        <td><?= $c['year'] ? h($c['year']) : '—' ?></td>
        <td><?= $c['mileage'] !== null ? number_format($c['mileage']) . ' km' : '—' ?></td>
        <td><?= $c['price'] !== null ? '€ ' . number_format($c['price'], 0, '.', ',') : '—' ?></td>
        <td style="text-transform:capitalize"><?= h($c['fuel_type']) ?></td>
        <td><span class="badge badge-<?= h($c['status']) ?>"><?= h($c['status']) ?></span></td>
        <td>
          <div class="actions">
            <?php if (isAdmin()): ?>
              <a href="edit_car.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-ghost" title="Edit car">✏️ Edit</a>
              <!-- Hidden delete form -->
              <form method="post" action="delete_car.php" id="del<?= (int)$c['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              </form>
              <button type="button" class="btn btn-sm btn-danger" title="Delete"
                onclick="openConfirm('Delete <?= h(addslashes($c['brand'] . ' ' . $c['car_model'])) ?> (<?= h(addslashes($c['plate_number'])) ?>)?','del<?= (int)$c['id'] ?>')">
                🗑️
              </button>
            <?php else: ?>
              <a href="edit_car.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-ghost" title="Update status / notes">
                🏷️ Status
              </a>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="10" style="text-align:center;padding:2rem;color:var(--text-muted)">
        No cars match your search.
        <?php if (isAdmin()): ?><a href="add_car.php">Add one →</a><?php endif; ?>
      </td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php renderFooter(); ?>
