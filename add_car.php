<?php
/**
 * add_car.php — Add a new car to inventory
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layout.php';
requireAdmin();

$errors = [];
$vals   = ['brand'=>'','car_model'=>'','plate_number'=>'','color'=>'#3b82f6',
           'year'=>'','mileage'=>'','price'=>'','fuel_type'=>'petrol',
           'status'=>'available','description'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

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

    $carService = new \App\Services\CarService();
    $result = $carService->createCar($vals);

    if ($result['success']) {
        flash('success', 'Car "' . ucwords(strtolower($vals['brand'])) . ' ' . ucwords(strtolower($vals['car_model'])) . '" added successfully!');
        header('Location: cars.php');
        exit;
    } else {
        $errors = $result['errors'];
    }
}

$token = csrfToken();
renderHeader('Add Car');
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Add New Car</h1>
    <p class="page-subtitle">Fill in the details below to add a car to the inventory.</p>
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

<div class="card" style="max-width:780px">
  <form method="post" action="add_car.php">
    <input type="hidden" name="csrf_token" value="<?= h($token) ?>">

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="brand">Brand *</label>
        <input id="brand" type="text" name="brand" class="form-control"
               placeholder="e.g. Toyota" value="<?= h($vals['brand']) ?>" required maxlength="80">
      </div>
      <div class="form-group">
        <label class="form-label" for="car_model">Model *</label>
        <input id="car_model" type="text" name="car_model" class="form-control"
               placeholder="e.g. Corolla" value="<?= h($vals['car_model']) ?>" required maxlength="120">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="plate_number">Plate Number *</label>
        <input id="plate_number" type="text" name="plate_number" class="form-control"
               placeholder="e.g. ABC-1234" value="<?= h($vals['plate_number']) ?>"
               required maxlength="20" style="text-transform:uppercase">
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
               placeholder="e.g. 2022" value="<?= h($vals['year']) ?>"
               min="1886" max="<?= date('Y')+1 ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="mileage">Mileage (km)</label>
        <input id="mileage" type="number" name="mileage" class="form-control"
               placeholder="e.g. 45000" value="<?= h($vals['mileage']) ?>" min="0">
      </div>
      <div class="form-group">
        <label class="form-label" for="price">Price (€)</label>
        <input id="price" type="number" name="price" class="form-control"
               placeholder="e.g. 18500" value="<?= h($vals['price']) ?>" min="0" step="0.01">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="fuel_type">Fuel Type</label>
        <select id="fuel_type" name="fuel_type" class="form-control">
          <?php foreach(['petrol','diesel','electric','hybrid','lpg','other'] as $f): ?>
            <option value="<?= $f ?>" <?= $vals['fuel_type']===$f?'selected':'' ?>><?= ucfirst($f) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="status">Status</label>
        <select id="status" name="status" class="form-control">
          <?php foreach(['available','sold','reserved'] as $s): ?>
            <option value="<?= $s ?>" <?= $vals['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="description">Description / Notes</label>
      <textarea id="description" name="description" class="form-control"
                placeholder="Optional notes about condition, extras, etc." maxlength="2000"><?= h($vals['description']) ?></textarea>
    </div>

    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem">
      <a href="cars.php" class="btn btn-ghost">Cancel</a>
      <button type="submit" class="btn btn-primary">+ Add Car</button>
    </div>
  </form>
</div>

<?php renderFooter(); ?>
