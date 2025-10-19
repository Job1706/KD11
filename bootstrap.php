<?php
/*************************************************
 * Bootstrap (Session, DB, Helpers) for WMS
 * - สร้างฐานข้อมูล/ตารางให้ครบอัตโนมัติ
 * - เพิ่ม/บังคับ users.email (UNIQUE, NOT NULL) แบบ migrate อัตโนมัติ
 * - รองรับ Forgot Password (password_resets)
 * - ผู้ใช้เริ่มต้น: admin/user (รหัส 1234)
 * - ตาราง product_history + ฟังก์ชัน log
 *************************************************/

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
  date_default_timezone_set('Asia/Bangkok');
}

/* ===== App / DB Config (อ่านจาก ENV ได้) ===== */
if (!defined('APP_NAME')) define('APP_NAME', getenv('APP_NAME') ?: 'KD management System');

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'db');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'wms');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'app_user');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: 'StrongP@ss!');

if (!defined('MAIL_FROM')) define('MAIL_FROM', getenv('MAIL_FROM') ?: 'no-reply@example.com');

/* ===== PDO Single Instance ===== */
function pdo(): PDO {
  static $pdo;
  if ($pdo) return $pdo;

  $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
  $retries = 20;
  while (true) {
    try {
      $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]);
      $pdo->exec("SET time_zone = '+07:00'");
      return $pdo;
    } catch (Throwable $e) {
      if ($retries-- <= 0) throw $e;
      sleep(1);
    }
  }
}

/* ===== DB Bootstrap: สร้าง DB/ตาราง + migrate + seed ===== */
function db_bootstrap() {
  // สร้าง DB (ถ้ายังไม่มี)
  try {
    $tmp = new PDO("mysql:host=".DB_HOST.";charset=utf8mb4", DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $tmp->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  } catch (Throwable $e) {}

  $db = pdo();

  // ตารางหลัก
  $db->exec("
    CREATE TABLE IF NOT EXISTS categories(
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) UNIQUE NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  $db->exec("
    CREATE TABLE IF NOT EXISTS users(
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      username VARCHAR(50) UNIQUE NOT NULL,
      email VARCHAR(190) UNIQUE NOT NULL,
      password_hash VARCHAR(255) NOT NULL,
      role ENUM('admin','user') NOT NULL DEFAULT 'user',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  $db->exec("
    CREATE TABLE IF NOT EXISTS products(
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      sku VARCHAR(64) UNIQUE NOT NULL,
      name VARCHAR(200) NOT NULL,
      unit VARCHAR(32) NOT NULL DEFAULT 'pcs',
      price DECIMAL(12,2) NOT NULL DEFAULT 0,
      qty INT NOT NULL DEFAULT 0,
      category_id INT UNSIGNED NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  $db->exec("
    CREATE TABLE IF NOT EXISTS stock_moves(
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      product_id INT UNSIGNED NOT NULL,
      change_qty INT NOT NULL,
      note VARCHAR(255) NULL,
      user_id INT UNSIGNED NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX (product_id),
      INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  $db->exec("
    CREATE TABLE IF NOT EXISTS password_resets(
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id INT UNSIGNED NOT NULL,
      token_hash CHAR(64) NOT NULL,
      expires_at DATETIME NOT NULL,
      used_at DATETIME NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX (user_id),
      UNIQUE KEY uniq_token (token_hash)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  $db->exec("
    CREATE TABLE IF NOT EXISTS product_history(
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      product_id INT UNSIGNED NOT NULL,
      user_id INT UNSIGNED NULL,
      action ENUM('add','update','delete') NOT NULL,
      details TEXT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX (product_id),
      INDEX (user_id),
      INDEX (action),
      INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  // Migrate email
  try { $db->query("SELECT email FROM users LIMIT 1"); }
  catch (Throwable $e) { try { $db->exec("ALTER TABLE users ADD COLUMN email VARCHAR(190) NULL AFTER username"); } catch (Throwable $e2) {} }
  try { $db->exec("UPDATE users SET email = CONCAT(username,'@example.local') WHERE email IS NULL OR email = ''"); } catch (Throwable $e) {}
  try { $db->exec("ALTER TABLE users MODIFY email VARCHAR(190) NOT NULL"); } catch (Throwable $e) {}
  try { $db->exec("ALTER TABLE users ADD UNIQUE KEY ux_users_email (email)"); } catch (Throwable $e) {}

  // Drop FK เก่า (ignore error)
  foreach ([
    ['products','fk_products_category'],
    ['stock_moves','stock_moves_ibfk_1'],
    ['stock_moves','stock_moves_ibfk_2'],
    ['password_resets','fk_password_resets_user'],
    ['product_history','fk_ph_product'],
    ['product_history','fk_ph_user'],
  ] as [$t,$fk]) { try { $db->exec("ALTER TABLE `$t` DROP FOREIGN KEY `$fk`"); } catch (Throwable $e) {} }

  // บังคับชนิดคอลัมน์
  try { $db->exec("ALTER TABLE products         MODIFY category_id INT UNSIGNED NULL"); } catch (Throwable $e) {}
  try { $db->exec("ALTER TABLE stock_moves      MODIFY product_id  INT UNSIGNED NOT NULL"); } catch (Throwable $e) {}
  try { $db->exec("ALTER TABLE stock_moves      MODIFY user_id     INT UNSIGNED NULL"); } catch (Throwable $e) {}
  try { $db->exec("ALTER TABLE password_resets  MODIFY user_id     INT UNSIGNED NOT NULL"); } catch (Throwable $e) {}
  try { $db->exec("ALTER TABLE product_history  MODIFY product_id  INT UNSIGNED NOT NULL"); } catch (Throwable $e) {}
  try { $db->exec("ALTER TABLE product_history  MODIFY user_id     INT UNSIGNED NULL"); } catch (Throwable $e) {}

  // Add FK ใหม่
  try {
    $db->exec("ALTER TABLE products
      ADD CONSTRAINT fk_products_category
      FOREIGN KEY (category_id) REFERENCES categories(id)
      ON DELETE SET NULL ON UPDATE CASCADE");
  } catch (Throwable $e) {}

  try {
    $db->exec("ALTER TABLE stock_moves
      ADD CONSTRAINT stock_moves_ibfk_1
      FOREIGN KEY (product_id) REFERENCES products(id)
      ON DELETE CASCADE ON UPDATE CASCADE");
  } catch (Throwable $e) {}

  try {
    $db->exec("ALTER TABLE stock_moves
      ADD CONSTRAINT stock_moves_ibfk_2
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON DELETE SET NULL ON UPDATE CASCADE");
  } catch (Throwable $e) {}

  try {
    $db->exec("ALTER TABLE password_resets
      ADD CONSTRAINT fk_password_resets_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON DELETE CASCADE ON UPDATE CASCADE");
  } catch (Throwable $e) {}

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

  // Seed users
  try { $c = (int)($db->query("SELECT COUNT(*) c FROM users")->fetch()['c'] ?? 0); } catch (Throwable $e) { $c = 0; }
  if ($c === 0) {
    $adminHash = password_hash('1234', PASSWORD_DEFAULT);
    $userHash  = password_hash('1234', PASSWORD_DEFAULT);
    $st = $db->prepare("INSERT INTO users(username,email,password_hash,role) VALUES (?,?,?,?), (?,?,?,?)");
    $st->execute([
      'admin','admin@example.com',$adminHash,'admin',
      'user','user@example.com', $userHash, 'user'
    ]);
  } else {
    try { $db->exec("UPDATE users SET email = CONCAT(username,'@example.local') WHERE email IS NULL OR email=''"); } catch (Throwable $e) {}
  }

  // Seed categories
  try { $cat = (int)($db->query("SELECT COUNT(*) c FROM categories")->fetch()['c'] ?? 0); } catch (Throwable $e) { $cat = 0; }
  if ($cat === 0) {
    $db->prepare("INSERT INTO categories(name) VALUES (?), (?), (?), (?), (?)")
       ->execute(['ทั่วไป','อุปกรณ์สำนักงาน','อุปกรณ์คอมพิวเตอร์','อิเล็กทรอนิกส์','ของใช้ในบ้าน']);
  }
}
db_bootstrap();

/* ===== Helpers ===== */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf_token(){ if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_check($t){ return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t); }
function current_user(){ return $_SESSION['user'] ?? null; }
function need_login(){ if (!current_user()) { header("Location: ?"); exit; } }
function is_admin(){ return (current_user()['role'] ?? null) === 'admin'; }

function return_to(): string { return $_POST['return_to'] ?? $_SERVER['HTTP_REFERER'] ?? '?'; }
function set_toast($m,$t='success'){ $_SESSION['toast']=$m; $_SESSION['toast_type']=$t; }

/* Cart helpers */
function cart_get() { return $_SESSION['cart'] ?? []; }
function cart_set($c) { $_SESSION['cart']=$c; }
function cart_add($pid,$qty){ $c=cart_get(); $c[$pid]=max(1,(int)($c[$pid]??0)+$qty); cart_set($c); }
function cart_update($pid,$qty){ $c=cart_get(); $qty=(int)$qty; if($qty<=0) unset($c[$pid]); else $c[$pid]=$qty; cart_set($c); }
function cart_remove($pid){ $c=cart_get(); unset($c[$pid]); cart_set($c); }
function cart_clear(){ unset($_SESSION['cart']); }

/* ========= CONFIG HELPERS ========= */
if (!function_exists('cfg')) {
  function cfg(string $k, $default = null) {
    $v = getenv($k);
    if ($v === false && defined($k)) $v = constant($k);
    return $v !== false && $v !== null && $v !== '' ? $v : $default;
  }
}
if (!defined('BASE_URL')) define('BASE_URL', cfg('BASE_URL'));
if (!function_exists('app_url')) {
  function app_url(string $path = '/'): string {
    $base = BASE_URL;
    if (!$base) {
      $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
      $scheme = $https ? 'https' : 'http';
      $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
      $dir    = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
      if ($dir === '' || $dir === '.') $dir = '';
      $base   = $scheme.'://'.$host.$dir;
    }
    return rtrim($base,'/').'/'.ltrim($path,'/');
  }
}

/* ========= EMAIL (เวอร์ชันเดียว ใช้งานได้จริง) ========= */
if (!function_exists('send_mail')) {
  function send_mail(string $toEmail, string $subject, string $htmlBody, string $textBody = ''): array {
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (is_file($autoload)) require_once $autoload;

    if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
      $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
      try {
        $host = getenv('SMTP_HOST') ?: '';
        if ($host) {
          $mail->isSMTP();
          $mail->Host       = $host;
          $mail->Port       = (int)(getenv('SMTP_PORT') ?: 587);
          $mail->SMTPAuth   = (bool)(getenv('SMTP_AUTH') !== '0');
          $mail->SMTPSecure = getenv('SMTP_SECURE') ?: \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
          $mail->Username   = getenv('SMTP_USER') ?: '';
          $mail->Password   = getenv('SMTP_PASS') ?: '';
        } else {
          $mail->isMail(); // dev: MailHog/MailPit
        }

        $fromEmail = getenv('MAIL_FROM') ?: 'no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
        $fromName  = getenv('MAIL_FROM_NAME') ?: (defined('APP_NAME') ? APP_NAME : 'System');
        $mail->setFrom($fromEmail, $fromName);

        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody));

        if ((int)(getenv('SMTP_DEBUG') ?: 0) > 0) {
          $mail->SMTPDebug  = (int)getenv('SMTP_DEBUG');
          $mail->Debugoutput = function($str, $level){ error_log("SMTP[$level]: $str"); };
        }

        $mail->send();
        return [true, 'OK'];
      } catch (Throwable $e) {
        error_log('MAIL ERROR: ' . $e->getMessage());
        // จะลอง fallback mail() ด้านล่าง
      }
    }

    // fallback: mail()
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . (getenv('MAIL_FROM') ?: 'no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost')) . "\r\n";
    $ok = @mail($toEmail, '=?UTF-8?B?'.base64_encode($subject).'?=', $htmlBody, $headers);
    return [$ok, $ok ? 'OK' : 'mail() failed'];
  }
}

/* ====== THEME: Flash block (ข้อความเด่น ไม่ทับ loader) ====== */
if (!function_exists('flash_render')) {
  function flash_render(): void {
    if (empty($_SESSION['toast'])) return;
    $msg  = (string)($_SESSION['toast'] ?? '');
    $type = (string)($_SESSION['toast_type'] ?? 'success');
    unset($_SESSION['toast'], $_SESSION['toast_type']);
    echo '<div class="flash-stack">
      <div class="flash flash-'.$type.'">
        <div class="flash-title">'.($type==='danger'?'เกิดข้อผิดพลาด':'สำเร็จ').'</div>
        <div class="flash-body">'.h($msg).'</div>
        <button class="flash-close" onclick="this.parentNode.remove()">×</button>
      </div>
    </div>
    <style>
      .flash-stack{position:fixed;top:20px;left:0;right:0;display:grid;place-items:center;z-index:20000;pointer-events:none}
      .flash{pointer-events:auto;min-width:min(820px,calc(100vw - 32px));max-width:min(820px,calc(100vw - 32px));
        background:#0b1020;border:1px solid #203052;border-radius:14px;padding:14px 16px;
        color:#dbeafe;box-shadow:0 10px 30px rgba(0,0,0,.35);display:grid;gap:6px;position:relative}
      .flash-title{font-weight:800}
      .flash-body{color:#cbd5e1}
      .flash-danger{border-color:#7f1d1d;background:#2a0e15;color:#fecaca}
      .flash-success{border-color:#115e43;background:#062a1d;color:#bfffe6}
      .flash-close{all:unset;cursor:pointer;position:absolute;right:10px;top:6px;padding:6px 8px;border-radius:8px}
      @media (max-width:640px){.flash{min-width:calc(100vw - 24px)}}
    </style>';
  }
}

/* ====== Password Reset helpers (schema แบบ token_hash) ====== */
if (!function_exists('ensure_reset_schema')) {
  function ensure_reset_schema(PDO $db): void {
    $db->exec("
      CREATE TABLE IF NOT EXISTS password_resets(
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_th (token_hash),
        INDEX (user_id), INDEX(expires_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
  }
}
ensure_reset_schema(pdo());

if (!function_exists('reset_create_token')) {
  function reset_create_token(int $userId, int $minutes=60): string {
    $raw  = bin2hex(random_bytes(32));
    $hash = hash('sha256', $raw);
    $exp  = (new DateTimeImmutable("+{$minutes} minutes"))->format('Y-m-d H:i:s');
    pdo()->prepare("INSERT INTO password_resets(user_id, token_hash, expires_at) VALUES (?,?,?)")
         ->execute([$userId, $hash, $exp]);
    return $raw;
  }
}
if (!function_exists('reset_find_valid')) {
  function reset_find_valid(string $raw) {
    $hash = hash('sha256', $raw);
    $st = pdo()->prepare("
      SELECT pr.*, u.username, u.id AS uid
      FROM password_resets pr JOIN users u ON u.id=pr.user_id
      WHERE pr.token_hash=? AND pr.used_at IS NULL AND pr.expires_at>NOW()
      LIMIT 1
    ");
    $st->execute([$hash]);
    return $st->fetch();
  }
}
if (!function_exists('reset_mark_used')) {
  function reset_mark_used(int $resetId): void {
    pdo()->prepare("UPDATE password_resets SET used_at=NOW() WHERE id=?")->execute([$resetId]);
  }
}

/* ====== นโยบายรหัสผ่าน (เวอร์ชันเดียว) ====== */
if (!function_exists('password_policy_ok')) {
  function password_policy_ok(string $pw, &$err=null): bool {
    if (strlen($pw) < 8) { $err='อย่างน้อย 8 อักขระ'; return false; }
    if (!preg_match('/[A-Z]/',$pw)) { $err='ต้องมีตัวพิมพ์ใหญ่อย่างน้อย 1'; return false; }
    if (!preg_match('/[a-z]/',$pw)) { $err='ต้องมีตัวพิมพ์เล็กอย่างน้อย 1'; return false; }
    if (!preg_match('/\d/',$pw))    { $err='ต้องมีตัวเลขอย่างน้อย 1'; return false; }
    if (!preg_match('/[^A-Za-z0-9]/',$pw)) { $err='ควรมีอักขระพิเศษอย่างน้อย 1'; return false; }
    return true;
  }
}
