<?php
/**
 * edit_car.php — Edit an existing car record.
 * Admins: full form (all fields).
 * Users:  restricted form (status + description only).
 */
require 'config.php';
require 'layout.php';
requireLogin();

$pdo   = getPDO();
$plate = trim($_GET['plate'] ?? '');

if ($plate === '') {
    flash('danger', 'Invalid plate number.');
    header('Location: cars.php'); exit;
}

$car = $pdo->prepare('
    SELECT c.plate_number, cd.brand, cd.car_model, cd.color, cd.year, cd.mileage,
           cd.price, cd.fuel_type, cd.status, cd.description, c.created_at, c.updated_at
    FROM cars c
    JOIN car_data cd ON c.plate_number = cd.plate_number
    WHERE c.plate_number = ?
');
$car->execute([$plate]);
$car = $car->fetch();

if (!$car) {
    flash('danger', 'Car not found.');
    header('Location: cars.php'); exit;
}

$errors = [];
$vals   = $car;

/* ══════════════════════════════════════════════════════════
   POST handler — two separate paths by role
   ══════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (isAdmin()) {
        /* ── Admin: update all fields ───────────────────── */
        $vals['brand']        = postStr('brand', 80);
        $vals['car_model']    = postStr('car_model', 120);
        $vals['plate_number'] = strtoupper(postStr('plate_number', 20));
        $vals['color']        = postStr('color', 50);
        $vals['year']         = postStr('year', 4);
        $vals['mileage']      = postStr('mileage', 10);
        $vals['price']        = postStr('price', 12);
        $vals['fuel_type']    = postStr('fuel_type', 20);
        $vals['status']       = postStr('status', 20);
        $vals['description']  = postStr('description', 2000);

        // Validation
        if ($vals['brand'] === '')        $errors[] = 'Brand is required.';
        if ($vals['car_model'] === '')    $errors[] = 'Model is required.';
        if ($vals['plate_number'] === '') $errors[] = 'Plate number is required.';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $vals['color'])) $errors[] = 'Invalid color value.';
        if ($vals['year'] !== '' && ($vals['year'] < 1886 || $vals['year'] > (int)date('Y') + 1))
            $errors[] = 'Invalid year.';

        $allowedFuels    = ['petrol','diesel','electric','hybrid','lpg','other'];
        $allowedStatuses = ['available','sold','reserved'];
        if (!in_array($vals['fuel_type'], $allowedFuels))  $errors[] = 'Invalid fuel type.';
        if (!in_array($vals['status'], $allowedStatuses))  $errors[] = 'Invalid status.';

        if (!$errors) {
            try {
                $pdo->beginTransaction();

                // Update car_data (vehicle attributes)
                $pdo->prepare("
                    UPDATE car_data SET
                        brand=:brand, car_model=:car_model, color=:color,
                        year=:year, mileage=:mileage, price=:price,
                        fuel_type=:fuel_type, status=:status, description=:description
                    WHERE plate_number=:old_plate
                ")->execute([
                    ':brand'        => ucwords(strtolower($vals['brand'])),
                    ':car_model'    => ucwords(strtolower($vals['car_model'])),
                    ':color'        => $vals['color'],
                    ':year'         => $vals['year']    !== '' ? (int)$vals['year']    : null,
                    ':mileage'      => $vals['mileage'] !== '' ? (int)$vals['mileage'] : null,
                    ':price'        => $vals['price']   !== '' ? (float)$vals['price'] : null,
                    ':fuel_type'    => $vals['fuel_type'],
                    ':status'       => $vals['status'],
                    ':description'  => $vals['description'] !== '' ? $vals['description'] : null,
                    ':old_plate'    => $plate,
                ]);

                // If plate number changed, update cars table (CASCADE updates car_data FK)
                if ($vals['plate_number'] !== $plate) {
                    $pdo->prepare("UPDATE cars SET plate_number=:new_plate WHERE plate_number=:old_plate")
                        ->execute([':new_plate' => $vals['plate_number'], ':old_plate' => $plate]);
                }

                $pdo->commit();
                flash('success', '✅ Car updated successfully!');
                header('Location: cars.php'); exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                if ($e->errorInfo[1] === 1062)
                    $errors[] = 'Plate number "' . h($vals['plate_number']) . '" already belongs to another car.';
                else
                    $errors[] = 'Database error: ' . $e->getMessage();
            }
        }

    } else {
        /* ── User: update status + description only ─────── */
        $allowedStatuses = ['available','sold','reserved'];
        $vals['status']      = postStr('status', 20);
        $vals['description'] = postStr('description', 2000);

        if (!in_array($vals['status'], $allowedStatuses))
            $errors[] = 'Invalid status value.';

        if (!$errors) {
            try {
                $pdo->prepare("
                    UPDATE car_data SET status=:status, description=:description WHERE plate_number=:plate
                ")->execute([
                    ':status'      => $vals['status'],
                    ':description' => $vals['description'] !== '' ? $vals['description'] : null,
                    ':plate'       => $plate,
                ]);
                flash('success', '✅ Car status and notes updated!');
                header('Location: cars.php'); exit;
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$token = csrfToken();
renderHeader('Edit Car');
?>

<div class="page-header">
  <div>
    <h1 class="page-title"><?= isAdmin() ? 'Edit Car' : 'Update Status &amp; Notes' ?></h1>
    <p class="page-subtitle">
      <?= h($car['brand']) ?> <?= h($car['car_model']) ?> &mdash;
      <code><?= h($car['plate_number']) ?></code>
      <?php if (!isAdmin()): ?>
        <span style="color:var(--text-dim);font-size:.8rem;margin-left:.5rem">
          (You can update status and description only)
        </span>
      <?php endif; ?>
    </p>
  </div>
  <a href="cars.php" class="btn btn-ghost">← Back to Inventory</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul style="margin:0;padding-left:1.2rem">
      <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if (isAdmin()): ?>
<!-- ══ ADMIN: Full edit form ══════════════════════════════════ -->
<div class="card" style="max-width:780px">
  <form method="post" action="edit_car.php?plate=<?= urlencode($plate) ?>">
    <input type="hidden" name="csrf_token" value="<?= h($token) ?>">

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="brand">Brand *</label>
        <input id="brand" type="text" name="brand" class="form-control"
               value="<?= h($vals['brand']) ?>" required maxlength="80">
      </div>
      <div class="form-group">
        <label class="form-label" for="car_model">Model *</label>
        <input id="car_model" type="text" name="car_model" class="form-control"
               value="<?= h($vals['car_model']) ?>" required maxlength="120">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="plate_number">Plate Number *</label>
        <input id="plate_number" type="text" name="plate_number" class="form-control"
               value="<?= h($vals['plate_number']) ?>" required maxlength="20"
               style="text-transform:uppercase">
      </div>
      <div class="form-group">
        <label class="form-label" for="colorInput">Color *</label>
        <div style="display:flex;gap:.75rem;align-items:center">
          <span id="colorPreview" data-color-for="colorInput" class="color-swatch"
                style="width:36px;height:36px;border-radius:8px;flex-shrink:0;background:<?= h($vals['color']) ?>"></span>
          <input id="colorInput" type="color" name="color" class="form-control"
                 value="<?= h($vals['color']) ?>" style="height:42px;padding:.3rem">
        </div>
      </div>
    </div>

    <div class="form-row-3">
      <div class="form-group">
        <label class="form-label" for="year">Year</label>
        <input id="year" type="number" name="year" class="form-control"
               value="<?= h((string)($vals['year'] ?? '')) ?>"
               min="1886" max="<?= date('Y')+1 ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="mileage">Mileage (km)</label>
        <input id="mileage" type="number" name="mileage" class="form-control"
               value="<?= h((string)($vals['mileage'] ?? '')) ?>" min="0">
      </div>
      <div class="form-group">
        <label class="form-label" for="price">Price (€)</label>
        <input id="price" type="number" name="price" class="form-control"
               value="<?= h((string)($vals['price'] ?? '')) ?>" min="0" step="0.01">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="fuel_type">Fuel Type</label>
        <select id="fuel_type" name="fuel_type" class="form-control">
          <?php foreach(['petrol','diesel','electric','hybrid','lpg','other'] as $f): ?>
            <option value="<?= $f ?>" <?= ($vals['fuel_type']??'')===$f?'selected':'' ?>><?= ucfirst($f) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="status">Status</label>
        <select id="status" name="status" class="form-control">
          <?php foreach(['available','sold','reserved'] as $s): ?>
            <option value="<?= $s ?>" <?= ($vals['status']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="description">Description / Notes</label>
      <textarea id="description" name="description" class="form-control"
                placeholder="Optional notes…" maxlength="2000"><?= h($vals['description'] ?? '') ?></textarea>
    </div>

    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem">
      <a href="cars.php" class="btn btn-ghost">Cancel</a>
      <button type="submit" class="btn btn-primary">💾 Save Changes</button>
    </div>
  </form>
</div>

<?php else: ?>
<!-- ══ USER: Restricted form (status + description only) ════ -->
<div class="card" style="max-width:580px">

  <!-- Read-only car details -->
  <div style="background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:var(--radius-sm);padding:1rem 1.2rem;margin-bottom:1.5rem;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem .5rem;font-size:.9rem;">
      <div><span style="color:var(--text-dim);font-size:.78rem;text-transform:uppercase;font-weight:600;">Brand</span><br><?= h($car['brand']) ?></div>
      <div><span style="color:var(--text-dim);font-size:.78rem;text-transform:uppercase;font-weight:600;">Model</span><br><?= h($car['car_model']) ?></div>
      <div><span style="color:var(--text-dim);font-size:.78rem;text-transform:uppercase;font-weight:600;">Plate</span><br><code><?= h($car['plate_number']) ?></code></div>
      <div><span style="color:var(--text-dim);font-size:.78rem;text-transform:uppercase;font-weight:600;">Color</span><br>
        <span class="color-swatch" style="background:<?= h($car['color']) ?>"></span> <?= h($car['color']) ?></div>
      <?php if ($car['year']): ?>
      <div><span style="color:var(--text-dim);font-size:.78rem;text-transform:uppercase;font-weight:600;">Year</span><br><?= h($car['year']) ?></div>
      <?php endif; ?>
      <?php if ($car['price'] !== null): ?>
      <div><span style="color:var(--text-dim);font-size:.78rem;text-transform:uppercase;font-weight:600;">Price</span><br>€ <?= number_format($car['price'], 0, '.', ',') ?></div>
      <?php endif; ?>
    </div>
  </div>

  <form method="post" action="edit_car.php?plate=<?= urlencode($plate) ?>">
    <input type="hidden" name="csrf_token" value="<?= h($token) ?>">

    <div class="form-group">
      <label class="form-label" for="status">Status</label>
      <div style="display:flex;gap:.6rem;flex-wrap:wrap">
        <?php foreach(['available','sold','reserved'] as $s): ?>
          <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;padding:.55rem 1rem;
                        background:<?= ($vals['status']??'')===$s ? 'rgba(59,130,246,.18)' : 'rgba(255,255,255,.04)' ?>;
                        border:1px solid <?= ($vals['status']??'')===$s ? 'var(--accent)' : 'var(--border)' ?>;
                        border-radius:var(--radius-sm);transition:all .2s;">
            <input type="radio" name="status" value="<?= $s ?>"
                   <?= ($vals['status']??'')===$s?'checked':'' ?>
                   style="accent-color:var(--accent)">
            <span class="badge badge-<?= $s ?>"><?= ucfirst($s) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="form-group" style="margin-top:1rem">
      <label class="form-label" for="description">Description / Notes</label>
      <textarea id="description" name="description" class="form-control"
                placeholder="Add notes about this car's condition, extras, etc."
                maxlength="2000"><?= h($vals['description'] ?? '') ?></textarea>
    </div>

    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem">
      <a href="cars.php" class="btn btn-ghost">Cancel</a>
      <button type="submit" class="btn btn-primary">💾 Save</button>
    </div>
  </form>
</div>
<?php endif; ?>

<?php renderFooter(); ?>
