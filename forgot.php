<?php
require_once __DIR__ . '/bootstrap.php';

/**
 * forgot.php (เวอร์ชันไม่ส่งอีเมล)
 * - GET  : ฟอร์มกรอก username หรือ email
 * - POST : ถ้าพบผู้ใช้ -> สร้าง session รีเซ็ตชั่วคราว -> ไป reset.php
 */

$pdo = pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'start_reset') {
  if (!csrf_check($_POST['csrf'] ?? '')) { http_response_code(400); die('Bad CSRF'); }

  $ident = trim($_POST['ident'] ?? '');
  if ($ident === '') {
    set_toast('กรุณากรอกชื่อผู้ใช้หรืออีเมล', 'danger');
    header('Location: forgot.php'); exit;
  }

  // หา user ด้วยอีเมลก่อน ถ้าไม่มีลองด้วย username
  $user = null;
  try {
    $st = $pdo->prepare("SELECT id, username FROM users WHERE email = ? LIMIT 1");
    $st->execute([$ident]);
    $user = $st->fetch();
  } catch (Throwable $e) {}
  if (!$user) {
    $st = $pdo->prepare("SELECT id, username FROM users WHERE username = ? LIMIT 1");
    $st->execute([$ident]);
    $user = $st->fetch();
  }

  // เพื่อความปลอดภัย แสดงข้อความกลาง ๆ เหมือนกันเสมอ
  if ($user) {
    $_SESSION['reset_uid']        = (int)$user['id'];
    $_SESSION['reset_username']   = $user['username'];
    $_SESSION['reset_started_at'] = time();         // ใช้ตรวจอายุสิทธิ์ใน reset.php
    header('Location: reset.php'); exit;
  }

  set_toast('ถ้าข้อมูลถูกต้อง ระบบจะพาคุณไปตั้งรหัสผ่านใหม่', 'success');
  header('Location: forgot.php'); exit;
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ลืมรหัสผ่าน | <?=h(APP_NAME)?></title>
<style>
  body{margin:0;background:#0f172a;color:#e5e7eb;font:15px/1.6 ui-sans-serif,system-ui}
  .center{display:grid;place-items:center;min-height:100vh;padding:24px}
  .card{background:#111827;border:1px solid #1f2a44;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,.35);padding:28px;max-width:520px;width:100%}
  h1{margin:0 0 10px}
  .subtitle{color:#9ca3af;margin-bottom:18px}
  .input{width:100%;background:#0b1020;border:1px solid #203052;color:#e5e7eb;padding:12px 14px;border-radius:12px;outline:none}
  .input:focus{border-color:#38bdf8;box-shadow:0 0 0 3px rgba(56,189,248,.15)}
  .btn{appearance:none;border:none;background:linear-gradient(135deg,#22d3ee,#8b5cf6);color:#00131a;font-weight:800;padding:12px 16px;border-radius:12px;cursor:pointer}
  .row{display:flex;gap:10px;flex-wrap:wrap}
  a{color:#22d3ee;text-decoration:none}
  .toast{position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:9999;background:#0b1020;border:1px solid #203052;padding:10px 14px;border-radius:10px}
</style>
</head>
<body>
<div class="center">
  <form class="card" method="post" autocomplete="off">
    <h1>ลืมรหัสผ่าน</h1>
    <div class="subtitle">กรอก <b>อีเมล</b> หรือ <b>ชื่อผู้ใช้</b> แล้วไปตั้งรหัสผ่านใหม่ได้ทันที (ไม่ต้องรออีเมล)</div>
    <div style="margin:12px 0">
      <input class="input" name="ident" placeholder="username หรือ email" required maxlength="191">
    </div>
    <div class="row" style="margin-top:6px">
      <input type="hidden" name="action" value="start_reset">
      <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
      <button class="btn">ดำเนินการต่อ</button>
      <a href="index.php" style="align-self:center">← กลับหน้าเข้าสู่ระบบ</a>
    </div>
  </form>
</div>

<?php if (!empty($_SESSION['toast'])): ?>
  <div class="toast"><?=h($_SESSION['toast'])?></div>
  <?php unset($_SESSION['toast'], $_SESSION['toast_type']); ?>
<?php endif; ?>
</body>
</html>
