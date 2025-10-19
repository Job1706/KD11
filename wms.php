<?php
require_once __DIR__ . '/bootstrap.php';

/* ==========================================================================
 * Feature Pack: session logging + admin users view + auto logo finder
 * ========================================================================= */
if (!function_exists('ensure_user_sessions_table')) {
  function ensure_user_sessions_table() {
    try {
      $pdo = function_exists('pdo') ? pdo() : null;
      if (!$pdo) return;
      $pdo->exec("CREATE TABLE IF NOT EXISTS user_sessions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        username VARCHAR(191) NULL,
        ip_address VARCHAR(64) NULL,
        user_agent TEXT NULL,
        status ENUM('login','logout') NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY (username), KEY (status), KEY (created_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
      // ไม่ให้ล่มระบบ
    }
  }
}

if (!function_exists('log_session_event')) {
  function log_session_event($status, $userArr = null) {
    try {
      $pdo = function_exists('pdo') ? pdo() : null;
      if (!$pdo) return;
      ensure_user_sessions_table();
      $uid = $userArr['id'] ?? null;
      $uname = $userArr['username'] ?? null;
      $ip = $_SERVER['REMOTE_ADDR'] ?? '';
      $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
      $st = $pdo->prepare("INSERT INTO user_sessions (user_id, username, ip_address, user_agent, status) VALUES (?, ?, ?, ?, ?)");
      $st->execute([$uid, $uname, $ip, $agent, $status]);
    } catch (Throwable $e) {
      // เงียบไว้ ไม่ให้ระบบล่ม
    }
  }
}

/* ==== Auto-detect logo (filesystem + web path) ==== */
if (!function_exists('find_logo_pair')) {
  function find_logo_pair(array $candidates, array $exts) {
    $docroot = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT'] ?? ''),'/');
    $basedir = rtrim(str_replace('\\','/', __DIR__),'/');
    $baseurl = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if ($baseurl === '') $baseurl = '/';
    foreach ($candidates as $cand) foreach ($exts as $ext) {
      if (strpos($cand,'./')===0){
        $fs  = $basedir.'/'.ltrim(substr($cand,2),'/').$ext;
        $web = $baseurl.'/'.ltrim(substr($cand,2),'/').$ext;
      } else {
        $fs  = $docroot.'/'.ltrim($cand,'/').$ext;
        $web = '/'.ltrim($cand,'/').$ext;
      }
      if (is_file($fs)) return [$fs,$web];
    }
    return null;
  }
  $LOGO_CANDIDATES = ['/logo','/assets/logo','./logo','./assets/logo'];
  $LOGO_EXTS = ['.png','.jpg','.jpeg','.webp','.svg','.gif'];
  $logoPair = find_logo_pair($LOGO_CANDIDATES, $LOGO_EXTS);
  if (!isset($APP_LOGO_WEB)) {
    $APP_LOGO_WEB = $logoPair ? $logoPair[1] : null;
  }
}

/* ====== Product History (เฉพาะใช้ใน wms.php) ====== */
function ensure_product_history_table_if_needed(PDO $db): void {
  $db->exec("
    CREATE TABLE IF NOT EXISTS product_history(
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      product_id INT UNSIGNED NOT NULL,
      user_id INT UNSIGNED NULL,
      action ENUM('add','update','delete') NOT NULL,
      details TEXT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX (product_id), INDEX (user_id), INDEX (action), INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
  try {
    $db->exec("ALTER TABLE product_history
      ADD CONSTRAINT fk_ph_product
      FOREIGN KEY (product_id) REFERENCES products(id)
      ON DELETE CASCADE ON UPDATE CASCADE");
  } catch (Throwable $e) {}
  try {
    $db->exec("ALTER TABLE product_history
      ADD CONSTRAINT fk_ph_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON DELETE SET NULL ON UPDATE CASCADE");
  } catch (Throwable $e) {}
}
function ph_log_action(int $productId, string $action, $details = null, ?int $userId = null): void {
  $db = pdo();
  static $ensured = false; if(!$ensured){ ensure_product_history_table_if_needed($db); $ensured=true; }
  if (!in_array($action, ['add','update','delete'], true)) $action = 'update';
  if ($userId === null) { $cu = current_user(); $userId = is_array($cu) ? ($cu['id'] ?? null) : null; }
  if (is_array($details)) { $details = json_encode($details, JSON_UNESCAPED_UNICODE); }
  $st = $db->prepare("INSERT INTO product_history(product_id,user_id,action,details)
                      VALUES (:pid,:uid,:act,:det)");
  $st->execute([':pid'=>$productId, ':uid'=>$userId, ':act'=>$action, ':det'=>$details]);
}
function ph_log_added(int $productId, array $afterRow): void {
  ph_log_action($productId,'add',['after'=>$afterRow]);
}
function ph_log_updated(int $productId, array $beforeRow, array $afterRow): void {
  $changed=[]; foreach($afterRow as $k=>$v){ $old=$beforeRow[$k]??null;
    if(is_numeric($v)&&is_numeric($old)){ $v+=0; $old+=0; }
    if($v!==$old){ $changed[$k]=['from'=>$old,'to'=>$v]; }
  }
  ph_log_action($productId,'update',['changed'=>$changed,'before'=>$beforeRow,'after'=>$afterRow]);
}
function ph_log_deleted(int $productId, array $beforeRow): void {
  ph_log_action($productId,'delete',['before'=>$beforeRow]);
}

/* ===== Auth: Login / Logout ===== */
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'login') {
  $u = trim($_POST['username'] ?? '');
  $p = (string)($_POST['password'] ?? '');
  if (!csrf_check($_POST['csrf'] ?? '')) die('Bad CSRF');
  $st = pdo()->prepare("SELECT * FROM users WHERE username=?"); $st->execute([$u]);
  $user = $st->fetch();
  if ($user && password_verify($p, $user['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user'] = ['id'=>$user['id'],'username'=>$user['username'],'role'=>$user['role']];
    // ✅ บันทึกการล็อกอิน
    log_session_event('login', $_SESSION['user'] ?? null);
    header("Location: ?");
    exit;
 } 
 else {
  // แจ้งเตือนเด่นๆ แทนหลอดโหลด
  $_SESSION['login_error'] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
  header("Location: ?");
  exit;
}
}
if ($action === 'logout') {
  if (!csrf_check($_POST['csrf'] ?? '')) die('Bad CSRF');
  // ✅ บันทึกการออกจากระบบ
  if (!empty($_SESSION['user'])) {
    log_session_event('logout', $_SESSION['user'] ?? null);
  }
  $ret = return_to();
  session_destroy();
  header("Location: $ret");
  exit;
}

/* ===== Actions ของระบบคลัง ===== */
if ($action === 'cat_add') {
  need_login(); if (!is_admin()) die('Forbidden');
  if (!csrf_check($_POST['csrf'] ?? '')) die('Bad CSRF');
  $name = trim($_POST['name'] ?? '');
  if ($name!=='') {
    try { pdo()->prepare("INSERT INTO categories(name) VALUES (?)")->execute([$name]); set_toast('เพิ่มหมวดหมู่แล้ว'); }
    catch(Exception $e){
      if ($e instanceof PDOException && $e->getCode()==='23000') set_toast('ชื่อหมวดหมู่ซ้ำ','danger');
      else set_toast('บันทึกหมวดหมู่ไม่สำเร็จ: '.$e->getMessage(),'danger');
    }
  }
  header("Location: ".return_to()); exit;
}
if ($action === 'cat_update') {
  need_login(); if (!is_admin()) die('Forbidden');
  if (!csrf_check($_POST['csrf'] ?? '')) die('Bad CSRF');
  $id = (int)($_POST['id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  if ($id && $name!=='') {
    try { pdo()->prepare("UPDATE categories SET name=? WHERE id=?")->execute([$name,$id]); set_toast('แก้ไขหมวดหมู่แล้ว'); }
    catch(Exception $e){
      if ($e instanceof PDOException && $e->getCode()==='23000') set_toast('ชื่อหมวดหมู่ซ้ำ','danger');
      else set_toast('บันทึกหมวดหมู่ไม่สำเร็จ: '.$e->getMessage(),'danger');
    }
  }
  header("Location: ".return_to()); exit;
}
if ($action === 'cat_delete') {
  need_login(); if (!is_admin()) die('Forbidden');
  if (!csrf_check($_POST['csrf'] ?? '')) die('Bad CSRF');
  $id = (int)($_POST['id'] ?? 0);
  if ($id){
    $inuse = pdo()->prepare("SELECT COUNT(*) c FROM products WHERE category_id=?");
    $inuse->execute([$id]);
    if (($inuse->fetch()['c'] ?? 0) > 0) set_toast('ลบไม่ได้: ยังมีสินค้าผูกหมวดหมู่นี้อยู่','danger');
    else { pdo()->prepare("DELETE FROM categories WHERE id=?")->execute([$id]); set_toast('ลบหมวดหมู่แล้ว'); }
  }
  header("Location: ".return_to()); exit;
}

if ($action === 'add_product') { // ← เพิ่มสินค้า + LOG
  need_login(); if (!is_admin()) die('Forbidden');
  if (!csrf_check($_POST['csrf'] ?? '')) die('Bad CSRF');
  $sku = trim($_POST['sku'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $unit = trim($_POST['unit'] ?? 'pcs');
  $price= max(0, (float)$_POST['price'] ?? 0);
  $qty  = max(0, (int)$_POST['qty'] ?? 0);
  $category_id = (int)($_POST['category_id'] ?? 0) ?: null;
  if ($sku && $name) {
    $st = pdo()->prepare("INSERT INTO products(sku,name,unit,price,qty,category_id) VALUES (?,?,?,?,?,?)");
    try {
      $st->execute([$sku,$name,$unit,$price,$qty,$category_id]);
      $pid = (int)pdo()->lastInsertId();
      if ($qty!==0){
        pdo()->prepare("INSERT INTO stock_moves(product_id,change_qty,note,user_id) VALUES (?,?,?,?)")
             ->execute([$pid,$qty,'Initial stock', current_user()['id'] ?? null]);
      }
      // LOG: บันทึกการเพิ่ม
      $after = ['sku'=>$sku,'name'=>$name,'unit'=>$unit,'price'=>$price,'qty'=>$qty,'category_id'=>$category_id];
      ph_log_added($pid, $after);
      set_toast('เพิ่มสินค้าเรียบร้อย');
    } catch(Exception $e) {
      $msg = $e->getMessage();
      if ($e instanceof PDOException && $e->getCode() === '23000') {
        if (stripos($msg, 'Duplicate') !== false || stripos($msg, '1062') !== false || stripos($msg, 'uniq') !== false)
          set_toast('เพิ่มสินค้าไม่สำเร็จ: รหัสสินค้า (SKU) ซ้ำ','danger');
        else set_toast('เพิ่มสินค้าไม่สำเร็จ: ข้อมูลผิดรูปแบบ/ข้อจำกัดของฐานข้อมูล','danger');
      } else set_toast('เพิ่มสินค้าไม่สำเร็จ: ' . $msg,'danger');
    }
  }
  header("Location: ".return_to()); exit;
}
if ($action === 'update_price') { // ← ปรับราคา + LOG
  need_login(); if (!is_admin()) die('Forbidden');
  if (!csrf_check($_POST['csrf'] ?? '')) die('Bad CSRF');
  $id = (int)($_POST['id'] ?? 0);
  $price = (float)($_POST['price'] ?? 0);
  if ($price < 0) { set_toast('ราคาเป็นค่าติดลบไม่ได้','danger'); header("Location: ".return_to()); exit; }
  $q = pdo()->prepare("SELECT sku,name,unit,price,qty,category_id FROM products WHERE id=?");
  $q->execute([$id]);
  $before = $q->fetch();
  pdo()->prepare("UPDATE products SET price=? WHERE id=?")->execute([$price,$id]);
  if ($before) {
    $after = $before; $after['price'] = $price;
    ph_log_updated($id, $before, $after);
  }
  set_toast('อัปเดตราคาแล้ว'); header("Location: ".return_to()); exit;
}
if ($action === 'stock_move') { // ← รับเข้า/เบิกออก + LOG
  need_login(); if (!is_admin()) die('Forbidden');
  if (!csrf_check($_POST['csrf'] ?? '')) die('Bad CSRF');
  $id = (int)($_POST['id'] ?? 0);
  $delta = (int)($_POST['delta'] ?? 0);
  $note = trim($_POST['note'] ?? '');
  $p = pdo()->prepare("SELECT sku,name,unit,price,qty,category_id FROM products WHERE id=?");
  $p->execute([$id]); $before = $p->fetch();
  if (!$before) { set_toast('ไม่พบสินค้า','danger'); header("Location: ".return_to()); exit; }
  $newQty = (int)$before['qty'] + $delta;
  if ($newQty < 0) { set_toast('จำนวนคงเหลือติดลบไม่ได้','danger'); header("Location: ".return_to()); exit; }
  $db = pdo(); $db->beginTransaction();
  try{
    $u = $db->prepare("UPDATE products SET qty=qty+? WHERE id=?"); $u->execute([$delta,$id]);
    $m = $db->prepare("INSERT INTO stock_moves(product_id,change_qty,note,user_id) VALUES (?,?,?,?)");
    $m->execute([$id,$delta, $note ?: ($delta>0?'รับเข้า':'เบิกออก'), current_user()['id'] ?? null]);
    $db->commit(); set_toast('บันทึกสต็อกเรียบร้อย');
    $after = $before; $after['qty'] = $newQty;
    ph_log_updated($id, $before, $after);
  }catch(Exception $e){ $db->rollBack(); set_toast('เกิดปัญหา, กรุณาลองใหม่','danger'); }
  header("Location: ".return_to()); exit;
}
if ($action === 'delete_product') { // ← ลบสินค้า + LOG
  need_login(); if (!is_admin()) die('Forbidden');
  if (!csrf_check($_POST['csrf'] ?? '')) die('Bad CSRF');
  $id = (int)($_POST['id'] ?? 0);
  $q = pdo()->prepare("SELECT sku,name,unit,price,qty,category_id FROM products WHERE id=?");
  $q->execute([$id]);
  $before = $q->fetch();
  pdo()->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
  if ($before) ph_log_deleted($id, $before);
  set_toast('ลบสินค้าแล้ว'); header("Location: ".return_to()); exit;
}

/* ===== Cart (user) ===== */
if ($action === 'cart_add') {
  need_login(); if (!csrf_check($_POST['csrf'] ?? '')) die('Bad CSRF');
  $pid = (int)($_POST['id'] ?? 0);
  $qtyReq = max(1, (int)$_POST['qty'] ?? 1);
  $st = pdo()->prepare("SELECT id, qty FROM products WHERE id=?"); $st->execute([$pid]); $row = $st->fetch();
  if (!$row) { set_toast('ไม่พบสินค้า','danger'); header("Location: ".return_to()); exit; }
  $cart = cart_get(); $already = (int)($cart[$pid] ?? 0); $want = $already + $qtyReq;
  if ($want > (int)$row['qty']) { set_toast('❌ จำนวนสินค้าไม่เพียงพอ','danger'); header("Location: ".return_to()); exit; }
  cart_add($pid, $qtyReq); set_toast('✅ เพิ่มลงตะกร้าแล้ว');
  $goCart = isset($_POST['go_cart']) && $_POST['go_cart'] === '1';
  $ret = $goCart ? '?tab=user-cart' : return_to(); header("Location: $ret"); exit;
}
if ($action === 'cart_update') {
  need_login(); if (!csrf_check($_POST['csrf'] ?? '')) die('Bad CSRF');
  $pid = (int)($_POST['id'] ?? 0);
  $qtyReq = (int)($_POST['qty'] ?? 1);
  if ($qtyReq <= 0) { cart_remove($pid); set_toast('ลบออกจากตะกร้าแล้ว'); header("Location: ".return_to()); exit; }
  $st = pdo()->prepare("SELECT qty FROM products WHERE id=?"); $st->execute([$pid]); $row = $st->fetch();
  if (!$row) { set_toast('ไม่พบสินค้า','danger'); header("Location: ".return_to()); exit; }
  if ($qtyReq > (int)$row['qty']) { set_toast('❌ จำนวนสินค้าไม่เพียงพอ','danger'); header("Location: ".return_to()); exit; }
  cart_update($pid, $qtyReq); set_toast('อัปเดตตะกร้าแล้ว'); header("Location: ".return_to()); exit;
}
if ($action === 'cart_remove') {
  need_login(); if (!csrf_check($_POST['csrf'] ?? '')) die('Bad CSRF');
  $pid = (int)($_POST['id'] ?? 0);
  cart_remove($pid); set_toast('ลบออกจากตะกร้าแล้ว'); header("Location: ".return_to()); exit;
}
if ($action === 'cart_clear') {
  need_login(); if (!csrf_check($_POST['csrf'] ?? '')) die('Bad CSRF');
  cart_clear(); set_toast('ล้างตะกร้าแล้ว'); header("Location: ".return_to()); exit;
}

/* ===== Data ===== */
$me = current_user();
$keyword = trim($_GET['q'] ?? '');
$cat_filter = (int)($_GET['cat'] ?? 0);
$categories = pdo()->query("SELECT id,name FROM categories ORDER BY name ASC")->fetchAll();

/* products + summary */
$w = []; $params = [];
if ($keyword !== '') { $w[] = "(p.sku LIKE ? OR p.name LIKE ?)"; $kw="%$keyword%"; $params[]=$kw; $params[]=$kw; }
if ($cat_filter) { $w[] = "p.category_id = ?"; $params[] = $cat_filter; }
$where = $w ? ("WHERE ".implode(" AND ", $w)) : "";

$sql = "
  SELECT p.*, c.name AS category_name
  FROM products p
  LEFT JOIN categories c ON c.id = p.category_id
  $where
  ORDER BY p.created_at DESC, p.id DESC
";
$stmt = pdo()->prepare($sql); $stmt->execute($params); $products = $stmt->fetchAll();

/* summary (ตามตัวกรอง) */
$sumStmt = pdo()->prepare("
  SELECT COUNT(*) AS skus, COALESCE(SUM(p.qty),0) AS total_qty, COALESCE(SUM(p.qty * p.price),0) AS total_value
  FROM products p LEFT JOIN categories c ON c.id = p.category_id $where
"); $sumStmt->execute($params);
$sumFiltered = $sumStmt->fetch() ?: ['skus'=>0,'total_qty'=>0,'total_value'=>0];

/* [ADD] สรุปรวมทั้งระบบ + flag มีตัวกรองไหม */
$sumAll = pdo()->query("
  SELECT
    COUNT(*) AS skus,
    COALESCE(SUM(qty),0) AS total_qty,
    COALESCE(SUM(qty * price),0) AS total_value
  FROM products
")->fetch(PDO::FETCH_ASSOC) ?: ['skus'=>0,'total_qty'=>0,'total_value'=>0];
$hasFilter = ($keyword !== '' || $cat_filter);

/* cart */
$cart = cart_get(); $cartItems=[]; $cartTotal=0.0; $cartCount=0;
if ($cart) {
  $ids = implode(',', array_map('intval', array_keys($cart)));
  if ($ids) {
    $rs = pdo()->query("SELECT id, sku, name, unit, price, qty AS stock_qty FROM products WHERE id IN ($ids)");
    foreach ($rs as $row) {
      $pid = (int)$row['id']; $q = (int)($cart[$pid] ?? 0);
      if ($q <= 0) continue;
      $line = [
        'id'=>$pid,'sku'=>$row['sku'],'name'=>$row['name'],'unit'=>$row['unit'],
        'price'=>(float)$row['price'],'stock_qty'=>(int)$row['stock_qty'],
        'qty'=>$q,'subtotal'=>$q*(float)$row['price']
      ];
      $cartItems[]=$line; $cartTotal+=$line['subtotal']; $cartCount+=$q;
    }
  }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=h(APP_NAME)?> | ระบบจัดการสินค้า</title>
<style>
:root{ --bg:#0f172a; --card:#111827; --muted:#1f2937; --text:#e5e7eb; --text-dim:#9ca3af; --pri:#22d3ee; --pri2:#8b5cf6; --bad:#ef4444; --ring:#38bdf8; --radius:18px; --shadow:0 10px 30px rgba(0,0,0,.35); }
*{box-sizing:border-box} html,body{height:100%} body{ margin:0; background: radial-gradient(1200px 600px at 10% -10%, #0b1222 0%, var(--bg) 40%, #0a0f1b 100%); color:var(--text); font:15px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial; }
a{color:var(--pri); text-decoration:none}
.container{max-width:1400px; margin:32px auto; padding:0 16px}
.card{background:linear-gradient(180deg, #0e1324 0%, var(--card) 100%); border:1px solid #1f2a44; border-radius:var(--radius); box-shadow:var(--shadow)}
.center{display:grid; place-items:center; min-height:100vh; padding:24px}
.login{width:100%; max-width:460px; padding:28px; position:relative; overflow:hidden}
.login h1{margin:0 0 6px; font-size:26px}
.subtitle{color:var(--text-dim); margin-bottom:18px}
.field{display:flex; gap:10px; margin:10px 0}
.field label{min-width:86px; color:var(--text-dim); padding-top:10px}
.input,.select{ flex:1; background:#0b1020; border:1px solid #203052; color:var(--text); padding:12px 14px; border-radius:12px; outline:none; transition:.2s;}
.input:focus,.select:focus{border-color:var(--ring); box-shadow:0 0 0 3px rgba(56,189,248,.15)}
.btn{appearance:none; border:none; background:linear-gradient(135deg, var(--pri) , var(--pri2)); color:#00131a; font-weight:700; padding:12px 16px; border-radius:12px; cursor:pointer; transition:transform .08s ease;}
.btn:active{transform:translateY(1px)}
.btn-outline{background:transparent; color:var(--text); border:1px solid #233152}
.btn-min{padding:6px 10px; font-weight:600; border-radius:10px; white-space:nowrap}
.row{display:flex; gap:12px; flex-wrap:wrap}
.toolbar{ padding:14px 18px; border-bottom:1px solid #1e2a46; position:sticky; top:0; background:rgba(13,18,36,.8); border-top-left-radius:var(--radius); border-top-right-radius:var(--radius); display:flex; align-items:center; }
.badge{display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border:1px solid #274062; border-radius:999px; color:#cfe3ff; font-weight:600}
.grid{display:grid; gap:16px}
.grid-cols-2{grid-template-columns: repeat(2, minmax(0,1fr))}
.table-wrap{ overflow-x:auto; } .table{ width:100%; border-collapse:collapse; min-width:1000px; }
.table th,.table td{ padding:12px 10px; border-bottom:1px dashed #1c2a4a; text-align:left; white-space:nowrap; vertical-align:middle; }
.table th{color:#aab2c3; font-weight:700; letter-spacing:.02em}
.table tr:hover{background:#0c1326}
.unit{color:#a7b7d4; border:1px solid #29405e; padding:2px 8px; border-radius:999px; font-size:12px}
.role-chip{padding:4px 10px; border-radius:999px; font-weight:700; background:#0d2436; border:1px solid #204a6b; color:#8fdcff}
.money{font-variant-numeric:tabular-nums;}
.badge-oos{ display:inline-block; padding:2px 8px; border-radius:999px; border:1px solid #5b2530; color:#fda4af; background:#2a0e15; font-size:12px; }
.badge-low{ display:inline-block; padding:2px 8px; border-radius:999px; border:1px solid #7a5a2a; color:#fde68a; background:#2a220e; font-size:12px; }
.hide{display:none}

/* [ADD] KPI blocks */
.kpis{ display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:12px; margin-bottom:12px; }
.kpi{ background:linear-gradient(180deg, #0b1020 0%, #0f1a30 100%); border:1px solid #1f2a44; border-radius:14px; padding:14px; box-shadow:var(--shadow); }
.kpi .label{ color:#aab2c3; font-size:13px; }
.kpi .value{ font-size:22px; font-weight:800; letter-spacing:.02em; margin-top:4px; }
.kpi .sub{ color:#9ca3af; font-size:12px; margin-top:2px; }
.kpi strong.money{ font-variant-numeric: tabular-nums; }
@media (max-width:900px){ .kpis{ grid-template-columns:1fr; } }

/* แจ้งเตือนผิดพลาดบนฟอร์มล็อกอิน */
.alerts{margin-bottom:10px}
.alert{padding:12px 14px;border-radius:12px;border:1px solid}
.alert-danger{background:#2a0e15;border-color:#5b2530;color:#fecaca}
</style>
</head>

<?php if (!empty($_SESSION['toast'])): ?>
  <div style="position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:99999;
              background:#0b1020;border:1px solid #203052;padding:12px 16px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.4)">
    <?=h($_SESSION['toast'])?>
  </div>
  <?php unset($_SESSION['toast'], $_SESSION['toast_type']); ?>
<?php endif; ?>

<body>

<?php $me = current_user(); if (!$me): ?>
  <!-- ===== Login ===== -->
  <div class="center">
    <form class="card login" method="post">
      <h1><?=h(APP_NAME)?></h1>
      <div class="subtitle">เข้าสู่ระบบเพื่อจัดการคลังสินค้า</div>

      <?php if (!empty($_SESSION['login_error'])): ?>
        <div class="alerts">
          <div class="alert alert-danger"><?=h($_SESSION['login_error']); unset($_SESSION['login_error']); ?></div>
        </div>
      <?php endif; ?>

      <div class="small" style="margin-bottom:10px">
        ยังไม่มีบัญชี? <a href="signup.php">สมัครสมาชิก</a> •
        <a href="forgot.php">ลืมรหัสผ่าน?</a>
      </div>

      <div class="field">
        <label>ชื่อผู้ใช้</label>
        <input class="input" name="username" placeholder="admin หรือ user" required maxlength="50" autofocus>
      </div>
      <div class="field">
        <label>รหัสผ่าน</label>
        <input class="input" type="password" name="password" placeholder="1234" required maxlength="100">
      </div>
      <div class="row" style="margin-top:8px">
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
        <button class="btn">เข้าสู่ระบบ</button>
      </div>
    </form>
  </div>

<?php else:
  $tab = $_GET['tab'] ?? (is_admin() ? 'admin-products' : 'user-products');
?>
  <!-- ===== App ===== -->
  <div class="container">
    <div class="card">
      <div class="toolbar row">
        <div class="badge">👤 <?=h($me['username'])?> <span class="role-chip"><?=h($me['role'])?></span></div>
        <form method="get" class="row" style="margin-left:12px;flex:1">
          <input class="input" type="search" name="q" value="<?=h($keyword)?>" placeholder="ค้นหา รหัสสินค้าหรือชื่อสินค้า..." maxlength="200" style="flex:1">
          <select class="select" name="cat">
            <option value="0">ทุกหมวดหมู่</option>
            <?php foreach($categories as $c){ $sel = ((int)($c['id'])===$cat_filter)?'selected':''; echo '<option value="'.$c['id'].'" '.$sel.'>'.h($c['name']).'</option>'; } ?>
          </select>
          <button class="btn-min btn-outline">ค้นหา</button>
          <?php if ($keyword!=='' || $cat_filter): ?><a class="btn-min btn-outline" href="?">ล้าง</a><?php endif; ?>
        </form>
        <form method="post" style="margin-left:auto">
          <input type="hidden" name="action" value="logout"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
          <input type="hidden" name="return_to" value="<?=h($_SERVER['REQUEST_URI'] ?? '?')?>"><button class="btn-min btn-outline">ออกจากระบบ</button>
        </form>
      </div>

      <div class="row" style="padding:12px 16px; gap:8px;">
        <?php if (is_admin()): ?>
          <a class="btn-min btn-outline" href="?tab=admin-products">📦 สินค้าทั้งหมด</a>
          <a class="btn-min btn-outline" href="?tab=admin-add">➕ เพิ่มสินค้า</a>
          <a class="btn-min btn-outline" href="?tab=admin-price">⚙️ จัดการราคา</a>
          <a class="btn-min btn-outline" href="?tab=admin-cats">🏷️ หมวดหมู่</a>
          <a class="btn-min btn-outline" href="history_log.php">📜 ประวัติสินค้า</a>
          <a class="btn-min btn-outline" href="?tab=admin-users-logs">👥 ผู้ใช้งาน & ประวัติ</a>
        <?php else: ?>
          <a class="btn-min btn-outline" href="?tab=user-products">🛍️ รายการสินค้า</a>
          <a class="btn-min btn-outline" href="?tab=user-cart">🧺 ตะกร้าของฉัน</a>
        <?php endif; ?>
      </div>

      <div style="padding:16px">
        <?php if (is_admin()): ?>
          <?php if ($tab==='admin-products'): ?>

            <!-- KPI -->
            <div class="kpis">
              <div class="kpi">
                <div class="label">จำนวนสินค้า (SKU ทั้งหมด)</div>
                <div class="value"><?= number_format((int)$sumAll['skus']) ?></div>
                <?php if ($hasFilter): ?>
                  <div class="sub">ตามตัวกรอง: <?= number_format((int)$sumFiltered['skus']) ?></div>
                <?php endif; ?>
              </div>
              <div class="kpi">
                <div class="label">จำนวนของทั้งหมดในคลัง</div>
                <div class="value"><?= number_format((int)$sumAll['total_qty']) ?> <span class="sub">ชิ้น</span></div>
                <?php if ($hasFilter): ?>
                  <div class="sub">ตามตัวกรอง: <?= number_format((int)$sumFiltered['total_qty']) ?> ชิ้น</div>
                <?php endif; ?>
              </div>
              <div class="kpi">
                <div class="label">มูลค่ารวมทั้งหมดของสินค้า</div>
                <div class="value"><strong class="money">฿<?= number_format((float)$sumAll['total_value'], 2) ?></strong></div>
                <?php if ($hasFilter): ?>
                  <div class="sub">ตามตัวกรอง: ฿<?= number_format((float)$sumFiltered['total_value'], 2) ?></div>
                <?php endif; ?>
              </div>
            </div>

            <div class="table-wrap">
              <table class="table">
                <thead><tr><th>รหัสสินค้า</th><th>ชื่อสินค้า</th><th>หมวดหมู่</th><th>หน่วย</th><th>ราคา/หน่วย</th><th>คงเหลือ</th><th>รับเข้า/เบิกออก</th><th>ลบ</th></tr></thead>
                <tbody>
                <?php if (!$products): ?><tr><td colspan="8" class="small">ยังไม่มีสินค้า — เพิ่มใหม่</td></tr><?php endif; ?>
                <?php foreach($products as $p): $qtyInt=(int)$p['qty']; $rowStyle=$qtyInt<=0?'style="opacity:.55"':''; ?>
                  <tr <?=$rowStyle?>>
                    <td><strong><?=h($p['sku'])?></strong></td>
                    <td><?=h($p['name'])?></td>
                    <td><span class="unit"><?=h($p['category_name'] ?: '—')?></span></td>
                    <td><span class="unit"><?=h($p['unit'])?></span></td>
                    <td class="money"><?=number_format($p['price'],2)?></td>
                    <td><?= $qtyInt<=0 ? '<span class="badge-oos">สินค้าหมด</span>' : ($qtyInt<=5 ? '<span class="badge-low">ใกล้หมด ('.number_format($qtyInt).')</span>' : number_format($qtyInt)) ?></td>
                    <td>
                      <form method="post" class="row" style="gap:6px;align-items:center">
                        <input type="number" class="input" name="delta" step="1" value="1" style="max-width:110px" required>
                        <input type="text" class="input" name="note" placeholder="หมายเหตุ" style="min-width:200px" maxlength="255">
                        <input type="hidden" name="action" value="stock_move">
                        <input type="hidden" name="id" value="<?=$p['id']?>">
                        <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                        <input type="hidden" name="return_to" value="<?=h($_SERVER['REQUEST_URI'] ?? '?')?>">
                        <button class="btn-min btn-outline" onclick="this.form.delta.value=Math.abs(this.form.delta.value)">รับเข้า +</button>
                        <button class="btn-min btn-outline" onclick="this.form.delta.value=-Math.abs(this.form.delta.value)">เบิกออก −</button>
                        <button class="btn-min">บันทึก</button>
                      </form>
                    </td>
                    <td>
                      <form method="post" onsubmit="return confirm('ลบสินค้านี้? การเคลื่อนไหวที่เกี่ยวข้องจะถูกลบด้วย');">
                        <input type="hidden" name="action" value="delete_product">
                        <input type="hidden" name="id" value="<?=$p['id']?>">
                        <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                        <input type="hidden" name="return_to" value="<?=h($_SERVER['REQUEST_URI'] ?? '?')?>">
                        <button class="btn-min btn-outline" style="color:#fca5a5;border-color:#7f1d1d">ลบ</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <th colspan="5" style="text-align:right">รวมตามตัวกรอง</th>
                    <th><?=number_format((int)$sumFiltered['total_qty'])?></th>
                    <th style="text-align:right">มูลค่ารวม</th>
                    <th class="money">฿<?=number_format((float)$sumFiltered['total_value'],2)?></th>
                  </tr>
                </tfoot>
              </table>
            </div>

          <?php elseif ($tab==='admin-add'): ?>
            <form method="post" class="grid grid-cols-2">
              <div><label class="small">รหัสสินค้า</label><input class="input" name="sku" placeholder="เช่น P-0001" required maxlength="64"></div>
              <div><label class="small">ชื่อสินค้า</label><input class="input" name="name" placeholder="เช่น กระดาษ A4" required maxlength="200"></div>
              <div><label class="small">หมวดหมู่</label><select class="select" name="category_id"><option value="">— ไม่ระบุ —</option><?php foreach($categories as $c){ echo '<option value="'.$c['id'].'">'.h($c['name']).'</option>'; } ?></select></div>
              <div><label class="small">หน่วย</label><input class="input" name="unit" value="pcs" maxlength="32"></div>
              <div><label class="small">ราคา/หน่วย (บาท)</label><input class="input" type="number" step="0.01" name="price" value="0"></div>
              <div><label class="small">จำนวนเริ่มต้น</label><input class="input" type="number" step="1" name="qty" value="0"></div>
              <div style="align-self:end">
                <input type="hidden" name="action" value="add_product">
                <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                <input type="hidden" name="return_to" value="<?=h($_SERVER['REQUEST_URI'] ?? '?')?>">
                <button class="btn">บันทึกสินค้าใหม่</button>
              </div>
            </form>

          <?php elseif ($tab==='admin-price'): ?>
            <div class="table-wrap"><table class="table"><thead><tr><th>รหัสสินค้า</th><th>ชื่อสินค้า</th><th>ราคาเดิม</th><th>ราคาใหม่</th><th>บันทึก</th></tr></thead><tbody>
              <?php foreach($products as $p): ?>
              <tr>
                <td><?=h($p['sku'])?></td><td><?=h($p['name'])?></td><td class="money"><?=number_format($p['price'],2)?></td>
                <td><form method="post" class="row" style="gap:6px;align-items:center"><input class="input" type="number" step="0.01" name="price" value="<?=h($p['price'])?>" style="max-width:140px" required>
                  <input type="hidden" name="action" value="update_price"><input type="hidden" name="id" value="<?=$p['id']?>"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="return_to" value="<?=h($_SERVER['REQUEST_URI'] ?? '?')?>">
                  <button class="btn-min">อัปเดตราคา</button></form></td><td></td></tr>
              <?php endforeach; ?>
            </tbody></table></div>

          <?php elseif ($tab==='admin-cats'): ?>
            <form method="post" class="row" style="gap:8px;margin-bottom:12px">
              <input class="input" name="name" placeholder="เพิ่มหมวดหมู่ใหม่" required maxlength="100">
              <input type="hidden" name="action" value="cat_add"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="return_to" value="<?=h($_SERVER['REQUEST_URI'] ?? '?')?>">
              <button class="btn-min">เพิ่ม</button>
            </form>
            <div class="table-wrap"><table class="table"><thead><tr><th>ชื่อหมวดหมู่</th><th>แก้ไขชื่อ</th><th>ลบ</th></tr></thead><tbody>
              <?php foreach($categories as $c): ?>
                <tr><td><?=h($c['name'])?></td>
                  <td><form method="post" class="row" style="gap:6px;align-items:center"><input class="input" name="name" value="<?=h($c['name'])?>" required style="max-width:240px" maxlength="100">
                    <input type="hidden" name="id" value="<?=$c['id']?>"><input type="hidden" name="action" value="cat_update"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="return_to" value="<?=h($_SERVER['REQUEST_URI'] ?? '?')?>">
                    <button class="btn-min">บันทึก</button></form></td>
                  <td><form method="post" onsubmit="return confirm('ลบหมวดหมู่นี้? (จะลบได้ก็ต่อเมื่อไม่มีสินค้าใช้อยู่)');">
                    <input type="hidden" name="id" value="<?=$c['id']?>"><input type="hidden" name="action" value="cat_delete"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="return_to" value="<?=h($_SERVER['REQUEST_URI'] ?? '?')?>">
                    <button class="btn-min btn-outline" style="color:#fca5a5;border-color:#7f1d1d">ลบ</button></form></td></tr>
              <?php endforeach; ?>
            </tbody></table></div>

          <?php elseif ($tab==='admin-users-logs'): ?>
            <?php
              ensure_user_sessions_table();
              $pdo = pdo();
              $online = $pdo->query("
                SELECT username, MAX(status) AS last_status, MAX(created_at) AS last_time
                FROM user_sessions
                GROUP BY username
                HAVING last_status='login'
                ORDER BY last_time DESC
              ")->fetchAll(PDO::FETCH_ASSOC);
              $logs = $pdo->query("
                SELECT username, status, ip_address, user_agent, created_at
                FROM user_sessions
                ORDER BY created_at DESC
                LIMIT 200
              ")->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <h3>👥 ผู้ใช้งานปัจจุบันและประวัติการเข้าใช้</h3>

            <div style="margin-bottom:12px">
              <strong>ผู้ใช้งานออนไลน์ตอนนี้:</strong>
              <ul style="margin:6px 0 0 18px">
                <?php if (!$online): ?>
                  <li style="color:#94a3b8">ไม่มีผู้ใช้งานออนไลน์</li>
                <?php else: foreach ($online as $u): ?>
                  <li>
                    🟢 <?=h($u['username'] ?? '')?>
                    <span class="unit">เข้าสู่ระบบล่าสุด: <?=h($u['last_time'] ?? '')?></span>
                  </li>
                <?php endforeach; endif; ?>
              </ul>
            </div>

            <div class="table-wrap">
              <table class="table">
                <thead>
                  <tr>
                    <th>ชื่อผู้ใช้</th>
                    <th>สถานะ</th>
                    <th>IP</th>
                    <th>อุปกรณ์</th>
                    <th>เวลา</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!$logs): ?>
                    <tr><td colspan="5" class="small">ยังไม่มีประวัติ</td></tr>
                  <?php else: foreach ($logs as $r): ?>
                    <tr>
                      <td><?=h($r['username'])?></td>
                      <td><?=($r['status']==='login' ? '🟢 เข้าระบบ' : '🔴 ออกจากระบบ')?></td>
                      <td><?=h($r['ip_address'])?></td>
                      <td style="max-width:480px;white-space:normal"><?=h($r['user_agent'])?></td>
                      <td><?=h($r['created_at'])?></td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>

            <script>
              setInterval(()=>{ location.reload(); }, 10000);
            </script>

          <?php endif; ?>
        <?php else: ?>
          <?php if ($tab==='user-products'): ?>

            <!-- KPI ให้ผู้ใช้ -->
            <div class="kpis">
              <div class="kpi">
                <div class="label">จำนวนสินค้า (SKU ทั้งหมด)</div>
                <div class="value"><?= number_format((int)$sumAll['skus']) ?></div>
                <?php if ($hasFilter): ?>
                  <div class="sub">ตามตัวกรอง: <?= number_format((int)$sumFiltered['skus']) ?></div>
                <?php endif; ?>
              </div>
              <div class="kpi">
                <div class="label">จำนวนของทั้งหมดในคลัง</div>
                <div class="value"><?= number_format((int)$sumAll['total_qty']) ?> <span class="sub">ชิ้น</span></div>
                <?php if ($hasFilter): ?>
                  <div class="sub">ตามตัวกรอง: <?= number_format((int)$sumFiltered['total_qty']) ?> ชิ้น</div>
                <?php endif; ?>
              </div>
              <div class="kpi">
                <div class="label">มูลค่ารวมทั้งหมดของสินค้า</div>
                <div class="value"><strong class="money">฿<?= number_format((float)$sumAll['total_value'], 2) ?></strong></div>
                <?php if ($hasFilter): ?>
                  <div class="sub">ตามตัวกรอง: ฿<?= number_format((float)$sumFiltered['total_value'], 2) ?></div>
                <?php endif; ?>
              </div>
            </div>

            <div class="table-wrap"><table class="table"><thead><tr><th>รหัสสินค้า</th><th>ชื่อสินค้า</th><th>หมวดหมู่</th><th>หน่วย</th><th>ราคา/หน่วย</th><th>คงเหลือ</th><th>เพิ่มเข้าตะกร้า</th></tr></thead><tbody>
              <?php if (!$products): ?><tr><td colspan="7" class="small">ยังไม่มีสินค้า</td></tr><?php endif; ?>
              <?php foreach($products as $p): $qtyInt=(int)$p['qty']; $rowStyle=$qtyInt<=0?'style="opacity:.55"':''; ?>
                <tr <?=$rowStyle?>>
                  <td><strong><?=h($p['sku'])?></strong></td><td><?=h($p['name'])?></td><td><span class="unit"><?=h($p['category_name'] ?: '—')?></span></td><td><span class="unit"><?=h($p['unit'])?></span></td>
                  <td class="money"><?=number_format($p['price'],2)?></td>
                  <td><?= $qtyInt<=0 ? '<span class="badge-oos">สินค้าหมด</span>' : ($qtyInt<=5 ? '<span class="badge-low">ใกล้หมด ('.number_format($qtyInt).')</span>' : number_format($qtyInt)) ?></td>
                  <td>
                    <form method="post" class="row" style="gap:6px;align-items:center">
                      <input type="number" class="input" name="qty" step="1"
                             value="<?= max(1, min(1, (int)$p['qty'])) ?>" min="1"
                             <?php if ($qtyInt>0): ?> max="<?=$qtyInt?>" <?php endif; ?>
                             style="max-width:110px" <?= $qtyInt<=0?'disabled':'' ?> required>
                      <input type="hidden" name="action" value="cart_add"><input type="hidden" name="id" value="<?=$p['id']?>"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="return_to" value="<?=h($_SERVER['REQUEST_URI'] ?? '?')?>">
                      <button class="btn-min" <?= $qtyInt<=0?'disabled':'' ?>><?= $qtyInt<=0 ? 'สินค้าหมด' : 'เพิ่มลงตะกร้า' ?></button>
                      <?php if ($qtyInt>0): ?><button class="btn-min btn-outline" name="go_cart" value="1">ไปตะกร้า →</button><?php endif; ?>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody></table></div>

          <?php elseif ($tab==='user-cart'): ?>
            <?php $cartCount = array_sum(array_map('intval', $cart)); ?>
            <div class="row" style="margin-bottom:10px">
              <div class="badge">🧺 รายการในตะกร้า: <?=number_format($cartCount)?> ชิ้น • รวม ฿<?=number_format($cartTotal,2)?></div>
              <?php if ($cartItems): ?>
              <form method="post" class="right" onsubmit="return confirm('ล้างตะกร้าทั้งหมด?')">
                <input type="hidden" name="action" value="cart_clear">
                <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                <input type="hidden" name="return_to" value="<?=h($_SERVER['REQUEST_URI'] ?? '?')?>">
                <button class="btn-min btn-outline" style="color:#fca5a5;border-color:#7f1d1d">ล้างตะกร้า</button>
              </form>
              <?php endif; ?>
            </div>
            <div class="table-wrap"><table class="table"><thead><tr><th>รหัสสินค้า</th><th>ชื่อสินค้า</th><th>ราคา/หน่วย</th><th>จำนวน</th><th>คงเหลือ</th><th>ราคารวม</th><th>ลบ</th></tr></thead><tbody>
              <?php if (!$cartItems): ?><tr><td colspan="7" class="small">ตะกร้ายังว่างอยู่</td></tr><?php endif; ?>
              <?php foreach($cartItems as $line): ?>
              <tr>
                <td><?=h($line['sku'])?></td>
                <td><?=h($line['name'])?> <span class="unit">/<?=h($line['unit'])?></span></td>
                <td class="money"><?=number_format($line['price'],2)?></td>
                <td>
                  <form method="post" class="row" style="gap:6px;align-items:center">
                    <input class="input" type="number" step="1" min="0" max="<?=$line['stock_qty']?>" name="qty" value="<?=$line['qty']?>" style="max-width:100px">
                    <input type="hidden" name="id" value="<?=$line['id']?>"><input type="hidden" name="action" value="cart_update"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="return_to" value="<?=h($_SERVER['REQUEST_URI'] ?? '?')?>">
                    <button class="btn-min">อัปเดต</button>
                  </form>
                </td>
                <td><?=number_format($line['stock_qty'])?></td>
                <td class="money"><?=number_format($line['subtotal'],2)?></td>
                <td>
                  <form method="post" onsubmit="return confirm('ลบรายการนี้ออกจากตะกร้า?')">
                    <input type="hidden" name="id" value="<?=$line['id']?>"><input type="hidden" name="action" value="cart_remove"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="return_to" value="<?=h($_SERVER['REQUEST_URI'] ?? '?')?>">
                    <button class="btn-min btn-outline" style="color:#fca5a5;border-color:#7f1d1d">ลบ</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <?php if ($cartItems): ?><tfoot><tr><th colspan="5" style="text-align:right">รวมทั้งสิ้น</th><th class="money">฿<?=number_format($cartTotal,2)?></th><th></th></tr></tfoot><?php endif; ?>
            </table></div>
            <div class="small">* ตะกร้านี้เป็นตัวอย่างฝั่งผู้ใช้ ยังไม่ตัดสต็อกจริงจนกว่า Admin จะดำเนินการขาย/ออกเอกสาร</div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php endif; ?>

</body>
</html>
