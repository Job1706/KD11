<?php
require_once __DIR__ . '/bootstrap.php';
$pdo = pdo();

/* ---------- Simple captcha (letters+digits, session based) ---------- */
function make_code($len=5){
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // ตัด 0O1I ออกเพื่อลดสับสน
  $s=''; for($i=0;$i<$len;$i++) $s .= $chars[random_int(0, strlen($chars)-1)];
  return $s;
}
if (empty($_SESSION['captcha_code'])) $_SESSION['captcha_code'] = make_code();
if (isset($_GET['refresh_captcha'])) { $_SESSION['captcha_code'] = make_code(); header('Location: signup.php'); exit; }

/* ---------- Handle submit ---------- */
$errors = [];
$invalid = []; // เก็บรายชื่อฟิลด์ที่ไม่ผ่าน เพื่อไฮไลท์ในฟอร์ม

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='signup') {
  if (!csrf_check($_POST['csrf']??'')) { http_response_code(400); die('Bad CSRF'); }

  $fname = trim($_POST['first_name'] ?? '');
  $lname = trim($_POST['last_name'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pwd1 = (string)($_POST['password'] ?? '');
  $pwd2 = (string)($_POST['password2'] ?? '');
  $captcha = strtoupper(trim($_POST['captcha'] ?? ''));

  if ($fname===''){ $errors[]='กรุณากรอกชื่อ'; $invalid['first_name']=true; }
  if ($lname===''){ $errors[]='กรุณากรอกนามสกุล'; $invalid['last_name']=true; }
  if ($username==='' || !preg_match('/^[a-z0-9._-]{3,30}$/',$username)){
    $errors[]='ชื่อผู้ใช้ไม่ถูกต้อง (a-z,0-9,._- 3–30 ตัว)'; $invalid['username']=true;
  }
  if ($email==='' || !filter_var($email, FILTER_VALIDATE_EMAIL)){
    $errors[]='อีเมลไม่ถูกต้อง'; $invalid['email']=true;
  }
  $policyErr=null; 
  if (!password_policy_ok($pwd1,$policyErr)){ 
    $errors[]=$policyErr; $invalid['password']=true; 
  }
  if ($pwd1!==$pwd2){ 
    $errors[]='รหัสผ่านทั้งสองช่องไม่ตรงกัน'; 
    $invalid['password']=true; 
    $invalid['password2']=true; 
  }
  if ($captcha==='' || $captcha !== ($_SESSION['captcha_code'] ?? '')){ 
    $errors[]='รหัสยืนยันความเป็นมนุษย์ไม่ถูกต้อง'; 
    $invalid['captcha']=true; 
  }

  // uniq check
  if (!$errors) {
    $dupU = $pdo->prepare("SELECT 1 FROM users WHERE username=? LIMIT 1"); $dupU->execute([$username]);
    if ($dupU->fetch()){ $errors[]='ชื่อผู้ใช้นี้ถูกใช้แล้ว'; $invalid['username']=true; }
    $dupE = $pdo->prepare("SELECT 1 FROM users WHERE email=? LIMIT 1"); $dupE->execute([$email]);
    if ($dupE->fetch()){ $errors[]='อีเมลนี้ถูกใช้แล้ว'; $invalid['email']=true; }
  }

  if (!$errors) {
    $hash = password_hash($pwd1, PASSWORD_DEFAULT);
    $st = $pdo->prepare("INSERT INTO users(username,email,password_hash,role) VALUES (?,?,?,'user')");
    $st->execute([$username, $email, $hash]);

    // เคลียร์ captcha และโชว์ flash เด่นๆ
    $_SESSION['captcha_code'] = make_code();
    set_toast('สมัครสมาชิกสำเร็จ! เข้าสู่ระบบด้วยบัญชีใหม่ได้เลย','success');
    header('Location: index.php'); exit;
  }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>สมัครสมาชิก | <?=h(APP_NAME)?></title>
<style>
  :root{
    --bg:#0b1224; --card:#0f172a; --muted:#1b2338; --text:#e5e7eb; --dim:#9fb4d4;
    --ring:#38bdf8; --pri:#22d3ee; --pri2:#8b5cf6; --danger:#ef4444; --ok:#10b981;
  }
  *{box-sizing:border-box}
  body{margin:0;background:radial-gradient(1200px 600px at 10% -20%, rgba(34,211,238,.06), transparent),
                   radial-gradient(1000px 600px at 90% 0%, rgba(139,92,246,.06), transparent),
                   var(--bg);
       color:var(--text);font:15px/1.6 ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial}
  .wrap{max-width:980px;margin:24px auto;padding:0 14px}
  .panel{
    background:linear-gradient(180deg, rgba(30,41,59,.78), rgba(15,23,42,.92));
    border:1px solid rgba(56,189,248,.15);
    border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.45), inset 0 1px 0 rgba(255,255,255,.03);
    padding:22px 22px 24px; backdrop-filter:blur(6px);
  }
  h1{margin:0 0 6px;font-size:32px}
  .sub{color:var(--dim);margin-bottom:16px}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .full{grid-column:1 / -1}
  @media (max-width:720px){ .grid{grid-template-columns:1fr} }

  label{display:block;margin:0 0 6px;font-weight:700}
  .input{
    width:100%;background:linear-gradient(180deg,#0b1020,#0a0f1d);
    border:1px solid #203052;color:var(--text);
    padding:12px 14px;border-radius:12px;outline:none;transition:border .2s,box-shadow .2s
  }
  .input::placeholder{color:#6b7b97}
  .input:focus{border-color:var(--ring);box-shadow:0 0 0 3px rgba(56,189,248,.14)}

  .input-row{display:grid;grid-template-columns:1fr auto;gap:10px;align-items:center}
  .toggle{background:linear-gradient(180deg,#0d1428,#0b1020);border:1px solid #233152;border-radius:12px;
          color:#cfe3ff;padding:10px 12px;cursor:pointer;white-space:nowrap}

  .hint{font-size:13px;color:#9fb4d4}
  .meter{height:8px;border-radius:6px;background:#111a2f;overflow:hidden;margin-top:8px}
  .meter>span{display:block;height:100%;width:0;background:linear-gradient(90deg,#f87171,#fb923c,#fbbf24,#34d399,#22c55e)}

  .captchaBox{ display:flex;align-items:center;gap:10px;flex-wrap:wrap }
  .captchaPic{
    min-width:140px;padding:10px 14px;border-radius:12px;
    background:
      repeating-linear-gradient(45deg, rgba(34,211,238,.07) 0 6px, transparent 6px 12px),
      linear-gradient(180deg,#0d1428,#0b1020);
    border:1px solid #233152;
    font:700 22px/1.1 ui-monospace,Menlo,Consolas,monospace;
    letter-spacing:6px; color:#dbeafe; text-shadow:0 0 8px rgba(56,189,248,.25);
  }
  .btn{
    appearance:none;border:none;background:linear-gradient(135deg,var(--pri),var(--pri2));
    color:#00131a;font-weight:800;padding:12px 18px;border-radius:14px;cursor:pointer;
    box-shadow:0 8px 25px rgba(34,211,238,.18);
  }
  .btn-outline{background:transparent;color:var(--text);border:1px solid #233152;padding:12px 18px;border-radius:14px}
  .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:12px}

  /* ---- ALERT with motion ---- */
  .alert-popup{
    display:flex;justify-content:space-between;align-items:center;gap:12px;
    background:#2a0e15;border:1px solid #5b2530;color:#fecaca;
    padding:14px 18px;border-radius:12px;margin-bottom:16px;
    box-shadow:0 5px 20px rgba(0,0,0,.3); animation:fadeIn .35s ease, shake .45s ease;
  }
  .alert-message{font-size:15px;line-height:1.4}
  .alert-close{background:transparent;border:none;color:#fecaca;font-size:20px;cursor:pointer}

  @keyframes fadeIn{ from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:translateY(0)} }
  @keyframes shake{
    10%, 90% { transform: translateX(-1px); }
    20%, 80% { transform: translateX(2px); }
    30%, 50%, 70% { transform: translateX(-4px); }
    40%, 60% { transform: translateX(4px); }
  }

  /* ไฮไลท์ช่องผิด */
  .is-invalid{
    border-color: #7f1d1d !important;
    box-shadow: 0 0 0 3px rgba(239,68,68,.15) !important;
    animation: glow .9s ease;
  }
  @keyframes glow{
    0%{ box-shadow: 0 0 0 0 rgba(239,68,68,.35); }
    100%{ box-shadow: 0 0 0 3px rgba(239,68,68,.15); }
  }
</style>
</head>
<body>
<div class="wrap">
  <div class="panel">
    <h1>สมัครสมาชิก</h1>
    <div class="sub">กรอกข้อมูลด้านล่างให้ครบถ้วน</div>

    <?php if ($errors): ?>
      <div class="alert-popup" id="alertBox" role="alert" aria-live="assertive">
        <div class="alert-message">⚠️ <?=h(implode(' • ',$errors))?></div>
        <button class="alert-close" type="button" onclick="closeAlert()">×</button>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off" novalidate>
      <div class="grid">
        <div>
          <label for="fname">ชื่อ</label>
          <input id="fname" class="input <?=isset($invalid['first_name'])?'is-invalid':''?>" 
                 name="first_name" required placeholder="เช่น กานต์" 
                 value="<?=h($_POST['first_name']??'')?>">
        </div>
        <div>
          <label for="lname">นามสกุล</label>
          <input id="lname" class="input <?=isset($invalid['last_name'])?'is-invalid':''?>" 
                 name="last_name" required placeholder="เช่น ติ์เด่น" 
                 value="<?=h($_POST['last_name']??'')?>">
        </div>

        <div>
          <label for="username">ชื่อผู้ใช้</label>
          <input id="username" class="input <?=isset($invalid['username'])?'is-invalid':''?>" 
                 name="username" required pattern="[a-z0-9._-]{3,30}" 
                 placeholder="a-z 0-9 . _ -" value="<?=h($_POST['username']??'')?>">
        </div>
        <div>
          <label for="email">อีเมล</label>
          <input id="email" class="input <?=isset($invalid['email'])?'is-invalid':''?>" 
                 type="email" name="email" required placeholder="your@email.com" 
                 value="<?=h($_POST['email']??'')?>">
        </div>

        <div class="full">
          <label for="pwd1">รหัสผ่าน</label>
          <div class="input-row">
            <input id="pwd1" class="input <?=isset($invalid['password'])?'is-invalid':''?>" 
                   type="password" name="password" required minlength="8"
                   placeholder="อย่างน้อย 8 ตัว มี a-z, A-Z, ตัวเลข และอักขระพิเศษ">
            <button class="toggle" type="button" onclick="toggle('pwd1',this)">แสดง</button>
          </div>
          <div class="meter"><span id="meter"></span></div>
          <div class="hint" id="tip">ความแข็งแรง: อ่อนมาก</div>
        </div>

        <div class="full">
          <label for="pwd2">ยืนยันรหัสผ่าน</label>
          <div class="input-row">
            <input id="pwd2" class="input <?=isset($invalid['password2'])?'is-invalid':''?>" 
                   type="password" name="password2" required minlength="8" placeholder="พิมพ์ซ้ำอีกครั้ง">
            <button class="toggle" type="button" onclick="toggle('pwd2',this)">แสดง</button>
          </div>
        </div>

        <div class="full">
          <label>ยืนยันความเป็นมนุษย์</label>
          <div class="captchaBox">
            <div class="captchaPic" aria-hidden="true"><?=h($_SESSION['captcha_code'])?></div>
            <input class="input <?=isset($invalid['captcha'])?'is-invalid':''?>" style="max-width:260px" 
                   name="captcha" required placeholder="พิมพ์ตามตัวอักษรด้านซ้าย" value="<?=h($_POST['captcha']??'')?>">
            <a class="btn-outline" href="?refresh_captcha=1">สุ่มใหม่</a>
          </div>
        </div>
      </div>

      <div class="actions">
        <input type="hidden" name="action" value="signup">
        <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
        <button class="btn" type="submit">สร้างบัญชี</button>
        <a class="btn-outline" href="index.php">← กลับหน้าเข้าสู่ระบบ</a>
      </div>
    </form>
  </div>
</div>

<script>
// ปิดกล่องแจ้งเตือน
function closeAlert(){ const b=document.getElementById('alertBox'); if(b) b.style.display='none'; }

// toggle show/hide password
function toggle(id, btn){
  const el = document.getElementById(id);
  if (!el) return;
  const toText = el.type === 'password';
  el.type = toText ? 'text' : 'password';
  btn.textContent = toText ? 'ซ่อน' : 'แสดง';
}

// strength meter
const pwd1  = document.getElementById('pwd1');
const meter = document.getElementById('meter');
const tip   = document.getElementById('tip');
function score(s){
  let sc = 0;
  if (s.length >= 8) sc++;
  if (/[A-Z]/.test(s)) sc++;
  if (/[a-z]/.test(s)) sc++;
  if (/\d/.test(s))    sc++;
  if (/[^A-Za-z0-9]/.test(s)) sc++;
  return sc;
}
function render(){
  if (!pwd1) return;
  const sc = score(pwd1.value);
  const pct = (sc/5)*100;
  if (meter){ meter.style.width = pct + '%'; }
  if (tip){
    const label = ['อ่อนมาก','อ่อน','พอใช้','ดี','แข็งแรง'][Math.max(0, sc-1)] || 'อ่อนมาก';
    tip.textContent = 'ความแข็งแรง: ' + label;
  }
}
if (pwd1){ pwd1.addEventListener('input', render); render(); }

// ถ้ามี error: โฟกัสช่องแรกที่ผิด + เลื่อนมุมมองไปบนสุด + สั่นกล่อง alert อีกรอบ
<?php if ($errors): ?>
  (function(){
    const firstInvalid = document.querySelector('.is-invalid');
    if (firstInvalid){ firstInvalid.focus({preventScroll:true}); }
    const alertBox = document.getElementById('alertBox');
    if (alertBox){
      alertBox.scrollIntoView({behavior:'smooth', block:'start'});
      // re-trigger shake
      alertBox.style.animation='none';
      void alertBox.offsetWidth; // reflow
      alertBox.style.animation='fadeIn .35s ease, shake .45s ease';
    }
  })();
<?php endif; ?>
</script>

<?php if (!empty($_SESSION['toast'])): ?>
  <div style="position:fixed;top:20px;left:50%;transform:translateX(-50%);
              background:#0b1020;border:1px solid #203052;padding:10px 14px;border-radius:10px;z-index:9999">
    <?=h($_SESSION['toast'])?>
  </div>
  <?php unset($_SESSION['toast'], $_SESSION['toast_type']); ?>
<?php endif; ?>
</body>
</html>
