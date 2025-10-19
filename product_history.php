<?php
// product_history.php
require_once __DIR__ . '/bootstrap.php';
need_login(); // ให้เฉพาะผู้ล็อกอินเข้าได้ (ถ้าจะจำกัดเฉพาะแอดมิน ให้ใช้: if(!is_admin()) { http_response_code(403); exit('Forbidden'); })

$db = pdo();

/* ===== รับพารามิเตอร์กรอง/แบ่งหน้า ===== */
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
$user_id    = isset($_GET['user_id'])    ? (int)$_GET['user_id']    : null;
$action     = isset($_GET['action'])     ? trim($_GET['action'])    : '';
$date_from  = isset($_GET['date_from'])  ? trim($_GET['date_from']) : '';
$date_to    = isset($_GET['date_to'])    ? trim($_GET['date_to'])   : '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 20;
$offset     = ($page - 1) * $per_page;

$where = [];
$params = [];

// เงื่อนไข
if ($product_id) { $where[] = "ph.product_id = :pid"; $params[':pid'] = $product_id; }
if ($user_id)    { $where[] = "ph.user_id = :uid";    $params[':uid'] = $user_id; }
if (in_array($action, ['add','update','delete'], true)) {
  $where[] = "ph.action = :act"; $params[':act'] = $action;
}
if ($date_from !== '') {
  $where[] = "ph.created_at >= :df"; $params[':df'] = $date_from.' 00:00:00';
}
if ($date_to !== '') {
  $where[] = "ph.created_at <= :dt"; $params[':dt'] = $date_to.' 23:59:59';
}

$where_sql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

/* ===== นับจำนวนทั้งหมดสำหรับแบ่งหน้า ===== */
$sql_count = "SELECT COUNT(*) AS c FROM product_history ph $where_sql";
$st = $db->prepare($sql_count);
$st->execute($params);
$total = (int)($st->fetch()['c'] ?? 0);
$pages = max(1, (int)ceil($total / $per_page));

/* ===== ดึงรายการ ===== */
$sql = "
  SELECT
    ph.id, ph.product_id, ph.user_id, ph.action, ph.details, ph.created_at,
    p.sku, p.name AS product_name,
    u.username
  FROM product_history ph
  LEFT JOIN products p ON p.id = ph.product_id
  LEFT JOIN users    u ON u.id = ph.user_id
  $where_sql
  ORDER BY ph.created_at DESC, ph.id DESC
  LIMIT :limit OFFSET :offset
";
$st = $db->prepare($sql);
foreach ($params as $k => $v) $st->bindValue($k, $v);
$st->bindValue(':limit',  $per_page, PDO::PARAM_INT);
$st->bindValue(':offset', $offset,   PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

/* ===== ใช้เป็นตัวเลือกดรอปดาวน์ ===== */
$products = $db->query("SELECT id, CONCAT('[',sku,'] ',name) AS label FROM products ORDER BY id DESC LIMIT 200")->fetchAll();
$users    = $db->query("SELECT id, username FROM users ORDER BY username")->fetchAll();

/* ===== ฟังก์ชันช่วยแสดง details (JSON/ข้อความ) ===== */
function fmt_details($d) {
  if ($d === null || $d === '') return '-';
  $t = trim($d);
  // ถ้าเป็น JSON ให้ pretty print
  $decoded = json_decode($t, true);
  if (json_last_error() === JSON_ERROR_NONE) {
    return '<pre style="white-space:pre-wrap;word-break:break-word;margin:0;">'.h(json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)).'</pre>';
  }
  // ไม่ใช่ JSON ก็แสดงข้อความปกติ
  return nl2br(h($t));
}

?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <title>ประวัติสินค้า | <?= h(APP_NAME) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:20px;background:#f6f7fb;}
    .wrap{max-width:1100px;margin:0 auto;}
    h1{margin:0 0 16px}
    .card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 6px 18px rgba(0,0,0,.06);margin-bottom:16px;}
    .grid{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}
    .grid .full{grid-column:1/-1}
    label{font-size:12px;color:#555;display:block;margin-bottom:6px}
    select,input{width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;background:#fff}
    button,.btn{padding:9px 14px;border:0;border-radius:8px;background:#0d6efd;color:#fff;cursor:pointer}
    .btn-outline{background:#fff;color:#0d6efd;border:1px solid #0d6efd}
    table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden}
    th,td{padding:10px 12px;border-bottom:1px solid #eee;vertical-align:top}
    th{background:#f0f3f8;text-align:left;font-weight:600}
    .actions a{margin-right:8px;text-decoration:none}
    .muted{color:#777}
    .pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px}
    .pill.add{background:#e8f7ee;color:#157347}
    .pill.update{background:#fff3cd;color:#946200}
    .pill.delete{background:#fde2e1;color:#b02a37}
    .pagination{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
    .pagination a,.pagination span{padding:6px 10px;border-radius:8px;border:1px solid #ddd;text-decoration:none;color:#333}
    .pagination .current{background:#0d6efd;color:#fff;border-color:#0d6efd}
    .toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
    .back a{text-decoration:none}
  </style>
</head>
<body>
<div class="wrap">
  <div class="toolbar">
    <h1>📜 ประวัติการเพิ่ม/แก้ไข/ลบสินค้า</h1>
    <div class="back"><a href="wms.php" class="btn btn-outline">← กลับไปหน้าคลัง</a></div>
  </div>

  <div class="card">
    <form method="get" class="grid">
      <div>
        <label>สินค้า</label>
        <select name="product_id">
          <option value="">-- ทั้งหมด --</option>
          <?php foreach($products as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= $product_id==(int)$p['id']?'selected':''; ?>><?= h($p['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>ผู้ใช้</label>
        <select name="user_id">
          <option value="">-- ทั้งหมด --</option>
          <?php foreach($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= $user_id==(int)$u['id']?'selected':''; ?>><?= h($u['username']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>การกระทำ</label>
        <select name="action">
          <option value="">-- ทั้งหมด --</option>
          <option value="add"    <?= $action==='add'?'selected':''; ?>>เพิ่ม</option>
          <option value="update" <?= $action==='update'?'selected':''; ?>>แก้ไข</option>
          <option value="delete" <?= $action==='delete'?'selected':''; ?>>ลบ</option>
        </select>
      </div>
      <div>
        <label>วันที่เริ่ม</label>
        <input type="date" name="date_from" value="<?= h($date_from) ?>">
      </div>
      <div>
        <label>ถึงวันที่</label>
        <input type="date" name="date_to" value="<?= h($date_to) ?>">
      </div>
      <div style="align-self:end;display:flex;gap:8px">
        <button type="submit">ค้นหา</button>
        <a class="btn btn-outline" href="product_history.php">ล้างตัวกรอง</a>
      </div>
      <div class="full muted">ผลลัพธ์ทั้งหมด: <?= number_format($total) ?> รายการ</div>
    </form>
  </div>

  <div class="card">
    <table>
      <thead>
        <tr>
          <th style="width:120px">เวลา</th>
          <th>สินค้า</th>
          <th style="width:110px">การกระทำ</th>
          <th style="width:140px">ผู้ใช้</th>
          <th>รายละเอียด</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="5" class="muted">ไม่พบบันทึก</td></tr>
      <?php else: foreach($rows as $r): ?>
        <tr>
          <td class="muted"><?= h($r['created_at']) ?></td>
          <td>
            <?php if ($r['product_id']): ?>
              <div><strong>#<?= (int)$r['product_id'] ?></strong> <?= h($r['product_name'] ?? '-') ?></div>
              <div class="muted"><?= h($r['sku'] ?? '') ?></div>
              <div><a href="product_history.php?product_id=<?= (int)$r['product_id'] ?>">ดูประวัติสินค้านี้</a></div>
            <?php else: ?>
              -
            <?php endif; ?>
          </td>
          <td>
            <?php
              $cls = 'pill '.h($r['action']);
              $label = $r['action']==='add'?'เพิ่ม':($r['action']==='update'?'แก้ไข':'ลบ');
            ?>
            <span class="<?= $cls ?>"><?= h($label) ?></span>
          </td>
          <td><?= h($r['username'] ?: '-') ?></td>
          <td><?= fmt_details($r['details']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>

    <?php if ($pages > 1): ?>
      <div class="pagination">
        <?php
          // ฟังก์ชันสร้างลิงก์คงพารามิเตอร์ตัวกรอง
          function link_page($p){
            $q = $_GET; $q['page'] = $p;
            return 'product_history.php?' . http_build_query($q);
          }
          $window = 2; // แสดงรอบ ๆ หน้าปัจจุบัน
          for ($p=1; $p<=$pages; $p++) {
            if ($p==1 || $p==$pages || abs($p-$page) <= $window) {
              if ($p==$page) echo '<span class="current">'. $p .'</span>';
              else echo '<a href="'.h(link_page($p)).'">'. $p .'</a>';
            } else {
              // เว้นช่วงด้วย ...
              if ($p==$page-$window-1 || $p==$page+$window+1) echo '<span>…</span>';
            }
          }
        ?>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
