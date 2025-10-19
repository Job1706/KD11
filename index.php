<?php
require_once __DIR__ . '/bootstrap.php';

$pdo = pdo();

/* ---------- Handle Login ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
  if (!csrf_check($_POST['csrf'] ?? '')) { http_response_code(400); die('Bad CSRF'); }

  $ident = trim($_POST['ident'] ?? '');      // รับได้ทั้ง username หรือ email
  $pass  = (string)($_POST['password'] ?? '');

  if ($ident === '' || $pass === '') {
    set_toast('กรุณากรอกชื่อผู้ใช้/อีเมล และรหัสผ่าน', 'danger');
    header('Location: index.php'); exit;
  }

  try {
    $st = $pdo->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ? OR email = ? LIMIT 1");
    $st->execute([$ident, $ident]);
    $u = $st->fetch();

    if (!$u || !password_verify($pass, $u['password_hash'])) {
      // ❌ ผิด: แจ้งเตือนเด่นๆ (ไม่มีหลอดโหลด)
      set_toast('ชื่อผู้ใช้/อีเมล หรือรหัสผ่านไม่ถูกต้อง', 'danger');
      header('Location: index.php'); exit;
    }

    // ✅ ถูก: เข้าสู่ระบบ
    $_SESSION['user'] = [
      'id' => (int)$u['id'],
      'username' => $u['username'],
      'email' => $u['email'],
      'role' => $u['role'],
    ];
    set_toast('เข้าสู่ระบบสำเร็จ ยินดีต้อนรับคุณ ' . $u['username'], 'success');
    header('Location: wms.php'); exit;

  } catch (Throwable $e) {
    // ❗ ผิดพลาดฝั่งเซิร์ฟเวอร์
    error_log('LOGIN ERROR: '.$e->getMessage());
    set_toast('ระบบขัดข้อง กรุณาลองใหม่อีกครั้ง', 'danger');
    header('Location: index.php'); exit;
  }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>เข้าสู่ระบบ | <?=h(APP_NAME)?></title>
<style>
  :root{ --bg:#0f172a; --card:#111827; --muted:#1f2937; --text:#e5e7eb; --dim:#9ca3af; --ring:#38bdf8; --pri:#22d3ee; --pri2:#8b5cf6; }
  *{box-sizing:border-box}
  body{margin:0;background:#0f172a;color:var(--text);font:15px/1.6 ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial}
  a{color:var(--pri);text-decoration:none}
  .center{display:grid;place-items:center;min-height:100vh;padding:24px}
  .card{background:linear-gradient(180deg,#0e1324 0%,var(--card) 100%);border:1px solid #1f2a44;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,.35);padding:28px;max-width:460px;width:100%}
  h1{margin:0 0 10px}
  .subtitle{color:var(--dim);margin-bottom:18px}
  .field{margin:12px 0}
  label{display:block;margin-bottom:6px;font-weight:700}
  .input{width:100%;background:#0b1020;border:1px solid #203052;color:var(--text);padding:12px 14px;border-radius:12px;outline:none;transition:.2s}
  .input:focus{border-color:var(--ring);box-shadow:0 0 0 3px rgba(56,189,248,.15)}
  .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .btn{appearance:none;border:none;background:linear-gradient(135deg,var(--pri),var(--pri2));color:#00131a;font-weight:800;padding:12px 16px;border-radius:12px;cursor:pointer}
  .link{color:var(--dim)}
  /* แอนิเมชันสั่นเมื่อผิด */
  .shake{animation:shake .36s cubic-bezier(.36,.07,.19,.97) both}
  @keyframes shake {
    10%, 90% { transform: translateX(-2px); }
    20%, 80% { transform: translateX(4px); }
    30%, 50%, 70% { transform: translateX(-6px); }
    40%, 60% { transform: translateX(6px); }
  }
  /* ปุ่มตา show/hide รหัสผ่าน */
  .pwd-wrap{position:relative}
  .toggle{
    position:absolute;right:8px;top:50%;transform:translateY(-50%);
    background:transparent;border:1px solid #233152;border-radius:10px;color:#cfe3ff;padding:6px 8px;cursor:pointer
  }
</style>
</head>
<body>

<?php
// ✅ เรียกแถบแจ้งเตือนเด่นๆ (จาก bootstrap.php)
if (function_exists('flash_render')) { flash_render(); }
?>

<div class="center">
  <form id="loginForm" class="card" method="post" autocomplete="off">
    <h1>เข้าสู่ระบบ</h1>
    <div class="subtitle">กรอก <b>ชื่อผู้ใช้หรืออีเมล</b> และ <b>รหัสผ่าน</b></div>

    <div class="field">
      <label for="ident">ชื่อผู้ใช้หรืออีเมล</label>
      <input id="ident" class="input" name="ident" placeholder="username หรือ email" required>
    </div>

    <div class="field pwd-wrap">
      <label for="password">รหัสผ่าน</label>
      <input id="password" class="input" type="password" name="password" required placeholder="••••••••">
      <button class="toggle" type="button" onclick="togglePwd()">แสดง</button>
    </div>

    <div class="row" style="margin-top:6px">
      <input type="hidden" name="action" value="login">
      <input type="hidden" name="csrf"  value="<?=h(csrf_token())?>">
      <button class="btn" type="submit">เข้าสู่ระบบ</button>
      <a class="link" href="forgot.php">ลืมรหัสผ่าน?</a>
      <span class="link">|</span>
      <a class="link" href="signup.php">สมัครสมาชิก</a>
    </div>
  </form>
</div>

<script>
/* ปิด “หลอดโหลด” ถ้ามีโค้ดเดิมเรียกใช้ — เราจะไม่โชว์มันอีก */
window.addEventListener('DOMContentLoaded', () => {
  // ถ้ามี element loader อยู่ในธีมเดิม ให้ซ่อนไปเลย
  const loader = document.querySelector('.loader, .loading-overlay, #loading');
  if (loader) loader.style.display = 'none';
});

// form submit: ไม่โชว์ loader, ถ้ากรอกไม่ครบให้สั่นแทน
document.getElementById('loginForm').addEventListener('submit', function(e){
  if (!this.checkValidity()) {
    e.preventDefault();
    this.classList.remove('shake'); // รีเซ็ตอนิเมชัน
    void this.offsetWidth;          // reflow
    this.classList.add('shake');    // เล่นใหม่
  }
});

// toggle show/hide password
function togglePwd(){
  const el = document.getElementById('password');
  const btn = document.querySelector('.toggle');
  if (!el) return;
  const toText = el.type === 'password';
  el.type = toText ? 'text' : 'password';
  btn.textContent = toText ? 'ซ่อน' : 'แสดง';
}
</script>
</body>
</html>
