<?php
// history_log.php — หน้าแสดงประวัติสินค้า (อ่านง่าย)
require_once __DIR__ . '/bootstrap.php';
need_login();
if (!is_admin()) die('Forbidden');

$db = pdo();

/* ==== Filters ==== */
$product_id = (int)($_GET['product_id'] ?? 0);
$action     = trim($_GET['action'] ?? '');
$user_id    = (int)($_GET['user_id'] ?? 0);
$start      = trim($_GET['start'] ?? '');
$end        = trim($_GET['end'] ?? '');

$where = []; $params = [];
if ($product_id) { $where[] = 'h.product_id = ?'; $params[] = $product_id; }
if (in_array($action, ['add','update','delete'], true)) { $where[] = 'h.action = ?'; $params[] = $action; }
if ($user_id) { $where[] = 'h.user_id = ?'; $params[] = $user_id; }
if ($start !== '') { $where[] = 'h.created_at >= ?'; $params[] = $start.' 00:00:00'; }
if ($end   !== '') { $where[] = 'h.created_at <= ?'; $params[] = $end.' 23:59:59'; }
$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

/* ==== Reference data ==== */
$products = $db->query("SELECT id, sku, name FROM products ORDER BY name ASC")->fetchAll();
$users    = $db->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll();
$catMap   = [];
foreach ($db->query("SELECT id,name FROM categories") as $r) {
  $catMap[(int)$r['id']] = $r['name'];
}

/* ==== Pagination ==== */
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 25;
$off  = ($page - 1) * $per;

$countSql = "SELECT COUNT(*) c FROM product_history h $whereSql";
$stc = $db->prepare($countSql); $stc->execute($params);
$total = (int)($stc->fetch()['c'] ?? 0);

$sql = "SELECT h.*, p.sku, p.name AS product_name, u.username
        FROM product_history h
        LEFT JOIN products p ON p.id = h.product_id
        LEFT JOIN users u ON u.id = h.user_id
        $whereSql
        ORDER BY h.created_at DESC, h.id DESC
        LIMIT $per OFFSET $off";
$sth = $db->prepare($sql); $sth->execute($params);
$rows = $sth->fetchAll();

/* ==== Helpers ==== */

function money($x){ return number_format((float)$x, 2); }
function mapLabel($k){
  static $L=['sku'=>'รหัสสินค้า','name'=>'ชื่อสินค้า','unit'=>'หน่วย','price'=>'ราคา','qty'=>'คงเหลือ','category_id'=>'หมวดหมู่'];
  return $L[$k] ?? $k;
}
function catName($id, $catMap){ if ($id===null||$id==='') return '—'; $id=(int)$id; return $catMap[$id] ?? ('#'.$id); }

function render_details(array $row, array $catMap): string {
  $act = $row['action'];
  $data = json_decode((string)($row['details'] ?? ''), true);
  if (!is_array($data)) return nl2br(h((string)$row['details']));

  $before = $data['before']  ?? [];
  $after  = $data['after']   ?? [];
  $changed= $data['changed'] ?? [];

  $html = '';
  if ($act==='add') {
    $html .= "<ul>";
    $html .= "<li>เพิ่มสินค้าใหม่</li>";
    $html .= "<li>รหัส: <b>".h($after['sku']??'')."</b></li>";
    $html .= "<li>ชื่อ: ".h($after['name']??'')."</li>";
    $html .= "<li>หน่วย: ".h($after['unit']??'')."</li>";
    $html .= "<li>ราคา: ฿".money($after['price']??0)."</li>";
    $html .= "<li>จำนวน: ".number_format($after['qty']??0)."</li>";
    $html .= "<li>หมวดหมู่: ".h(catName($after['category_id']??null,$catMap))."</li>";
    $html .= "</ul>";
    return $html;
  }
  if ($act==='update') {
    $html .= "<ul><li><b>แก้ไขข้อมูล</b></li>";
    foreach ($changed as $k=>$chg) {
      $from=$chg['from']??''; $to=$chg['to']??'';
      if ($k==='price'){ $from='฿'.money($from); $to='฿'.money($to); }
      if ($k==='qty'){ $from=number_format((float)$from); $to=number_format((float)$to); }
      if ($k==='category_id'){ $from=catName($from,$catMap); $to=catName($to,$catMap); }
      $html.="<li>".mapLabel($k).": <span style='color:#fca5a5'>".$from."</span> → <span style='color:#86efac'>".$to."</span></li>";
    }
    $html .= "</ul>";
    return $html;
  }
  if ($act==='delete') {
    $html .= "<ul><li><b>ลบสินค้า</b></li>";
    $html .= "<li>รหัส: <b>".h($before['sku']??'')."</b></li>";
    $html .= "<li>ชื่อ: ".h($before['name']??'')."</li>";
    $html .= "<li>ราคา: ฿".money($before['price']??0)."</li>";
    $html .= "<li>จำนวน: ".number_format($before['qty']??0)."</li>";
    $html .= "<li>หมวดหมู่: ".h(catName($before['category_id']??null,$catMap))."</li>";
    $html .= "</ul>";
    return $html;
  }
  return '<pre>'.h(json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)).'</pre>';
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h(APP_NAME)?> | ประวัติสินค้า</title>
<style>
body{background:#0f172a;color:#e5e7eb;font:15px/1.6 system-ui;-webkit-font-smoothing:antialiased;margin:0}
.container{max-width:1200px;margin:24px auto;padding:0 16px}
.card{background:#111827;border:1px solid #1f2a44;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.35)}
.toolbar{padding:14px 16px;border-bottom:1px solid #1e2a46;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.table-wrap{overflow-x:auto;padding:16px}
table{width:100%;border-collapse:collapse;min-width:900px}
th,td{padding:8px 10px;border-bottom:1px dashed #1e2a46;text-align:left;vertical-align:top}
th{color:#aab2c3}
ul{margin:0;padding-left:18px}
.btn{background:#0b1020;border:1px solid #203052;color:#e5e7eb;padding:8px 12px;border-radius:8px;text-decoration:none}
.btn:hover{background:#1e293b}
</style>
</head>
<body>
<div class="container">
<div class="card">
  <div class="toolbar">
    <h3>📜 ประวัติสินค้า</h3>
    <form method="get" style="display:flex;gap:6px;flex-wrap:wrap">
      <select name="product_id">
        <option value="0">สินค้าทั้งหมด</option>
        <?php foreach($products as $p): ?>
          <option value="<?=$p['id']?>" <?= $product_id===$p['id']?'selected':'' ?>>
            <?=h($p['sku'].' — '.$p['name'])?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="action">
        <option value="">ทุกการกระทำ</option>
        <option value="add"    <?= $action==='add'?'selected':'' ?>>เพิ่ม</option>
        <option value="update" <?= $action==='update'?'selected':'' ?>>แก้ไข</option>
        <option value="delete" <?= $action==='delete'?'selected':'' ?>>ลบ</option>
      </select>
      <button class="btn">กรอง</button>
      <a class="btn" href="wms.php">กลับ</a>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>เวลา</th><th>สินค้า</th><th>การกระทำ</th><th>รายละเอียด</th><th>ผู้ใช้</th></tr></thead>
      <tbody>
        <?php if(!$rows): ?>
          <tr><td colspan="5">ไม่มีข้อมูล</td></tr>
        <?php else: foreach($rows as $r): ?>
          <tr>
            <td><?=h($r['created_at'])?></td>
            <td><?=h($r['sku'] ?: '#'.$r['product_id'])?><br><small><?=h($r['product_name'] ?? '')?></small></td>
            <td><?= $r['action']==='add'?'เพิ่ม':($r['action']==='update'?'แก้ไข':'ลบ') ?></td>
            <td><?= render_details($r,$catMap) ?></td>
            <td><?=h($r['username'] ?? 'system')?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</div>
</body>
</html>
