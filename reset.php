<?php
require_once __DIR__ . '/bootstrap.php';

/**
 * reset.php (ไม่ใช้เมล/ไม่ใช้ token)
 * เข้าได้ต่อเมื่อมี session จาก forgot.php และยังไม่เกิน 10 นาที
 */

$pdo = pdo();

// ==== ตรวจสิทธิ์จาก session ====
$uid   = (int)($_SESSION['reset_uid']        ?? 0);
$uname = (string)($_SESSION['reset_username'] ?? '');
$since = (int)($_SESSION['reset_started_at']  ?? 0);

$MAX_AGE = 10 * 60; // 10 นาที
$valid_session = $uid > 0 && $since > 0 && (time() - $since) <= $MAX_AGE;
$state = $valid_session ? 'form' : 'invalid';

// ==== POST: บันทึกรหัสผ่านใหม่ ====
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'do_reset') {
  if (!csrf_check($_POST['csrf'] ?? '')) { http_response_code(400); die('Bad CSRF'); }
  if (!$valid_session) {
    $state = 'invalid';
  } else {
    $p1 = (string)($_POST['password']  ?? '');
    $p2 = (string)($_POST['password2'] ?? '');

    $fails = [];
    if ($p1 !== $p2) $fails[] = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
    $policyErr = null;
    if (!password_policy_ok($p1, $policyErr)) $fails[] = $policyErr;

    if ($fails) {
      $error = implode(' • ', $fails);
      $state = 'form';
    } else {
      try {
        $hash = password_hash($p1, PASSWORD_DEFAULT);
        $st = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?");
        $st->execute([$hash, $uid]);

        unset($_SESSION['reset_uid'], $_SESSION['reset_username'], $_SESSION['reset_started_at']);

        set_toast('เปลี่ยนรหัสผ่านเรียบร้อยแล้ว คุณสามารถเข้าสู่ระบบด้วยรหัสใหม่ได้เลย', 'success');
        header('Location: index.php'); exit;
      } catch (Throwable $e) {
        $error = 'เกิดข้อผิดพลาดในการบันทึกรหัสผ่าน กรุณาลองใหม่';
        $state = 'form';
      }
    }
  }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ตั้งรหัสผ่านใหม่ | <?=h(APP_NAME)?></title>
<style>
  :root{
    --bg:#0b1224; --card:#0f172a; --muted:#1b2338; --text:#e5e7eb; --dim:#9fb4d4;
    --ring:#38bdf8; --pri:#22d3ee; --pri2:#8b5cf6; --good:#10b981; --bad:#ef4444;
  }
  *{box-sizing:border-box}
  body{margin:0;background:radial-gradient(1200px 600px at 10% -20%, rgba(34,211,238,.06), transparent),
                   radial-gradient(1000px 600px at 90% 0%, rgba(139,92,246,.06), transparent),
                   var(--bg);
       color:var(--text);font:15px/1.6 ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial}
  a{color:var(--pri);text-decoration:none}
  .center{display:grid;place-items:center;min-height:100vh;padding:28px}
  .panel{
    width:100%;max-width:840px;
    background:linear-gradient(180deg, rgba(30,41,59,.8), rgba(15,23,42,.9));
    border:1px solid rgba(56,189,248,.15);
    border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.45), inset 0 1px 0 rgba(255,255,255,.03);
    padding:28px 26px 26px;
    backdrop-filter: blur(6px);
  }
  h1{margin:0 0 12px;font-size:34px;letter-spacing:.2px}
  .subtitle{color:var(--dim);margin:0 0 18px}
  .row{display:flex;gap:12px;flex-wrap:wrap}
  .field{margin:14px 0}
  label{display:block;margin:0 0 6px;font-weight:700}
  .note{
    color:#d1e7ff;background:linear-gradient(180deg,#0b1020,#0b1020);
    border:1px solid #203052;border-radius:14px;padding:12px 14px
  }

  /* อินพุตแนว glass */
  .input{
    width:100%;background:linear-gradient(180deg,#0b1020,#0a0f1d);
    border:1px solid #203052;color:var(--text);
    padding:12px 14px;border-radius:12px;outline:none;transition:border .2s, box-shadow .2s
  }
  .input::placeholder{color:#6b7b97}
  .input:focus{border-color:var(--ring);box-shadow:0 0 0 3px rgba(56,189,248,.14)}

  /* ปุ่มหลัก/รอง */
  .btn{
    appearance:none;border:none;background:linear-gradient(135deg,var(--pri),var(--pri2));
    color:#00131a;font-weight:800;padding:12px 18px;border-radius:14px;cursor:pointer;
    box-shadow:0 8px 25px rgba(34,211,238,.18);
  }
  .btn:hover{filter:saturate(1.05) brightness(1.03)}
  .btn-outline{
    background:transparent;color:var(--text);border:1px solid #233152;padding:12px 18px;border-radius:14px
  }

  /* ชุด “ช่อง + ปุ่ม” แบบ Grid ให้ปุ่มอยู่ขวาและกึ่งกลางแนวตั้งอัตโนมัติ */
  .input-row{
    display:grid;grid-template-columns: 1fr auto;gap:10px;align-items:center;
  }
  .toggle{
    background:linear-gradient(180deg,#0d1428,#0b1020);
    border:1px solid #233152;border-radius:12px;color:#cfe3ff;
    padding:10px 12px;cursor:pointer;line-height:1;white-space:nowrap;
    box-shadow:0 6px 18px rgba(0,0,0,.35);
  }
  .toggle:hover{border-color:#335384}

  /* meter สวยขึ้น */
  .meter{height:8px;border-radius:6px;background:#111a2f;overflow:hidden;margin-top:8px;position:relative}
  .meter>span{display:block;height:100%;width:0;
    background:linear-gradient(90deg,#f87171,#fb923c,#fbbf24,#34d399,#22c55e)}
  .policy{display:flex;justify-content:space-between;gap:8px;font-size:13px;color:#9fb4d4;margin-top:6px}
  .policy b{color:#d6e9ff}

  /* กล่องแจ้งเตือน */
  .alerts{margin:14px 0}
  .alert{padding:12px 14px;border-radius:12px;border:1px solid}
  .alert-danger{background:#2a0e15;border-color:#5b2530;color:#fecaca}

  @media (max-width:560px){
    h1{font-size:28px}
  }
</style>
</head>
<body>
<div class="center">
  <div class="panel">
    <?php if ($state === 'invalid'): ?>
      <h1>สิทธิ์รีเซ็ตรหัสผ่านหมดอายุ</h1>
      <div class="subtitle">โปรดกลับไปเริ่มขั้นตอนใหม่อีกครั้ง</div>
      <div class="row" style="margin-top:8px">
        <a class="btn-outline" href="forgot.php" style="padding:10px 14px;border-radius:12px">← กลับไปเริ่มใหม่</a>
      </div>
    <?php else: ?>
      <h1>ตั้งรหัสผ่านใหม่</h1>
      <div class="subtitle">สำหรับผู้ใช้: <b><?=h($uname)?></b> <span style="opacity:.7">(สิทธิ์นี้จะหมดอายุในไม่เกิน 10 นาที)</span></div>

      <div class="note">
        โปรดตั้งรหัสผ่านที่มีความซับซ้อน โดยประกอบด้วยอักษรตัวใหญ่–ตัวเล็ก ตัวเลข และอักขระพิเศษ
        และอย่างน้อย 8 อักขระ
      </div>

      <div class="alerts">
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?=h($error)?></div>
        <?php endif; ?>
      </div>

      <form method="post" autocomplete="off">
        <div class="field">
          <label for="pwd1">รหัสผ่านใหม่</label>
          <div class="input-row">
            <input class="input" id="pwd1" type="password" name="password" required minlength="8" placeholder="เช่น My$tr0ngPass!">
            <button type="button" class="toggle" aria-label="แสดง/ซ่อนรหัสผ่าน" onclick="toggle('pwd1', this)">แสดง</button>
          </div>
          <div class="meter" aria-hidden="true"><span id="meter"></span></div>
          <div class="policy"><span>ความแข็งแรง: <b id="tip">อ่อนมาก</b></span><span id="lenHint"></span></div>
        </div>

        <div class="field">
          <label for="pwd2">ยืนยันรหัสผ่านใหม่</label>
          <div class="input-row">
            <input class="input" id="pwd2" type="password" name="password2" required minlength="8" placeholder="พิมพ์ซ้ำอีกครั้ง">
            <button type="button" class="toggle" aria-label="แสดง/ซ่อนรหัสผ่าน" onclick="toggle('pwd2', this)">แสดง</button>
          </div>
        </div>

        <div class="row" style="margin-top:14px">
          <input type="hidden" name="action" value="do_reset">
          <input type="hidden" name="csrf"  value="<?=h(csrf_token())?>">
          <button class="btn">บันทึกรหัสผ่านใหม่</button>
          <a class="btn-outline" href="index.php">ยกเลิก</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
// toggle show/hide
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
const lenHint = document.getElementById('lenHint');

function score(s){
  let sc = 0;
  if (s.length >= 8) sc++;
  if (/[A-Z]/.test(s)) sc++;
  if (/[a-z]/.test(s)) sc++;
  if (/\d/.test(s))    sc++;
  if (/[^A-Za-z0-9]/.test(s)) sc++;
  return sc; // 0..5
}
function render(){
  if (!pwd1) return;
  const s = pwd1.value;
  const sc = score(s);
  const pct = (sc/5)*100;
  if (meter){ meter.firstElementChild.style.width = pct + '%'; }
  if (tip){
    const labels = ['อ่อนมาก','อ่อน','พอใช้','ดี','แข็งแรง'];
    tip.textContent = labels[Math.max(0, sc-1)] || 'อ่อนมาก';
  }
  if (lenHint){
    lenHint.textContent = s.length ? `${s.length} ตัวอักษร` : '';
  }
}
if (pwd1){ pwd1.addEventListener('input', render); render(); }
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
