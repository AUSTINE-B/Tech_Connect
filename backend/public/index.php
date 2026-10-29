<?php

declare(strict_types=1);

/** TechConnect PHP API for XAMPP (PHP 8.1+, PDO MySQL). */
date_default_timezone_set('UTC');
const API = '/api/v1';

final class ApiError extends RuntimeException
{
    public function __construct(public int $status, public string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}

function uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);
    return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
}
function stamp(?DateTimeImmutable $time = null): string
{
    return ($time ?? new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
}
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $configFile = dirname(__DIR__) . '/config.local.php';
    $config = is_file($configFile) ? require $configFile : [];
    $host = $config['host'] ?? (getenv('TECHCONNECT_DB_HOST') ?: '127.0.0.1');
    $port = $config['port'] ?? (getenv('TECHCONNECT_DB_PORT') ?: '3306');
    $name = $config['database'] ?? (getenv('TECHCONNECT_DB_NAME') ?: 'techconnect');
    $user = $config['user'] ?? (getenv('TECHCONNECT_DB_USER') ?: 'root');
    $password = $config['password'] ?? (getenv('TECHCONNECT_DB_PASSWORD') ?: '');
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}
function localConfig(): array
{
    $f = dirname(__DIR__) . '/config.local.php';
    return is_file($f) ? (require $f) : [];
}
function q(string $sql, array $params = []): PDOStatement
{
    $s = db()->prepare($sql);
    $s->execute($params);
    return $s;
}
function one(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}
function all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}
function body(): array
{
    $raw = file_get_contents('php://input');
    if (strlen($raw) > 1_000_000) throw new ApiError(413, 'BODY_TOO_LARGE', 'Request body is too large.');
    if ($raw === '' || $raw === false) return [];
    try {
        $object = json_decode($raw, false, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        throw new ApiError(400, 'INVALID_INPUT', 'Request body is invalid JSON.');
    }
    if (!is_object($object)) throw new ApiError(400, 'INVALID_INPUT', 'JSON body must be an object.');
    return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
}
function respond(int $status, array $data): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-Request-ID: ' . $GLOBALS['requestId']);
    echo json_encode(['data' => $data, 'requestId' => $GLOBALS['requestId']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}
function fail(int $status, string $code, string $message): never
{
    throw new ApiError($status, $code, $message);
}
function event(string $rid, ?string $actor, string $type, ?string $from, ?string $to, string $reason = '', array $payload = []): void
{
    q('INSERT INTO session_event(id,request_id,actor_id,event_type,from_status,to_status,reason,payload_json,created_at) VALUES (?,?,?,?,?,?,?,?,?)', [uuid(), $rid, $actor, $type, $from, $to, $reason, json_encode($payload, JSON_UNESCAPED_UNICODE), stamp()]);
}
function audit(?string $actor, string $action, string $type, string $id, string $reason = ''): void
{
    q('INSERT INTO audit_event(id,actor_id,action,entity_type,entity_id,reason,created_at) VALUES (?,?,?,?,?,?,?)', [uuid(), $actor, $action, $type, $id, $reason, stamp()]);
}
function actor(bool $required = true): ?array
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!str_starts_with($header, 'Bearer ')) {
        if ($required) fail(401, 'UNAUTHENTICATED', 'Sign in to continue.');
        return null;
    }
    $token = substr($header, 7);
    $row = one('SELECT u.* FROM auth_session s JOIN app_user u ON u.id=s.user_id WHERE s.token_hash=? AND s.revoked_at IS NULL AND s.expires_at>? AND u.active=1', [hash('sha256', $token), stamp()]);
    if (!$row && $required) fail(401, 'SESSION_EXPIRED', 'Your session expired. Sign in again.');
    return $row;
}
function adminAssetPath(string $path, string $prefix, string $fallback): string
{
    $path = str_replace('\\', '/', trim($path));
    if ($path === '') return $fallback;
    if (!str_starts_with($path, $prefix) || str_contains($path, '..') || !preg_match('#^assets/(Images|Icons)/[A-Za-z0-9 _().-]+$#', $path)) fail(422, 'INVALID_ASSET', 'Choose an image or icon inside the project assets folder.');
    $project = realpath(dirname(__DIR__, 2));
    $root = realpath($project . DIRECTORY_SEPARATOR . 'assets');
    $file = realpath($project . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    if (!$root || !$file || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file)) fail(422, 'INVALID_ASSET', 'The selected image or icon does not exist in the project assets folder.');
    return $path;
}
function roles(array $user, string ...$roles): void
{
    if (!in_array($user['role'], $roles, true)) fail(403, 'FORBIDDEN', 'Your account cannot perform this action.');
}
function publicUser(array $u): array
{
    return ['id' => $u['id'], 'email' => $u['email'], 'name' => $u['display_name'], 'phone' => $u['phone'], 'role' => $u['role'], 'language' => $u['preferred_language']];
}
function session(string $uid, int $status = 200): never
{
    $token = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    $expires = (new DateTimeImmutable('+7 days'))->setTimezone(new DateTimeZone('UTC'));
    q('INSERT INTO auth_session(token_hash,user_id,created_at,expires_at) VALUES (?,?,?,?)', [hash('sha256', $token), $uid, stamp(), stamp($expires)]);
    $u = one('SELECT * FROM app_user WHERE id=?', [$uid]);
    respond($status, ['token' => $token, 'expiresAt' => stamp($expires), 'user' => publicUser($u)]);
}
function record(string $rid, array $user): array
{
    $r = one('SELECT r.*,u.display_name AS customer_name FROM service_request r JOIN app_user u ON u.id=r.customer_id WHERE r.id=?', [$rid]);
    if (!$r) return [];
    $result = $r;
    unset($result['address']);
    $a = one("SELECT id FROM dispatch_assignment WHERE request_id=? AND technician_id=? AND status='ACCEPTED' AND revoked_at IS NULL ORDER BY assigned_at DESC LIMIT 1", [$rid, $user['id']]);
    if ($user['role'] === 'technician' && $a) {
        $result['address'] = $r['address'];
        q('INSERT INTO employee_address_access_log(id,employee_id,request_id,assignment_id,reason,accessed_at) VALUES (?,?,?,?,?,?)', [uuid(), $user['id'], $rid, $a['id'], 'Active job access', stamp()]);
    }
    $result['assignments'] = all('SELECT d.id,d.technician_id,d.status,d.reason,d.eta,d.assigned_at,d.responded_at,u.display_name AS technician_name FROM dispatch_assignment d JOIN app_user u ON u.id=d.technician_id WHERE d.request_id=? ORDER BY d.assigned_at DESC', [$rid]);
    $e = one('SELECT * FROM estimate_version WHERE request_id=? ORDER BY version_number DESC LIMIT 1', [$rid]);
    if ($e) {
        $e['lines'] = all('SELECT * FROM estimate_line WHERE estimate_id=?', [$e['id']]);
        $e['payment'] = one('SELECT id,status,amount_xaf,provider,created_at,verified_at FROM payment_attempt WHERE estimate_id=? ORDER BY created_at DESC LIMIT 1', [$e['id']]);
        $result['estimate'] = $e;
    }
    $result['events'] = all('SELECT event_type,from_status,to_status,reason,created_at FROM session_event WHERE request_id=? ORDER BY created_at', [$rid]);
    return $result;
}
function successRequest(string $rid, array $u, int $status = 200): never
{
    respond($status, ['request' => record($rid, $u)]);
}

$GLOBALS['requestId'] = $_SERVER['HTTP_X_REQUEST_ID'] ?? uuid();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$apiOffset = strpos($path, API);
if ($apiOffset !== false) $path = substr($path, $apiOffset);
$assetOffset = strpos($path, '/assets/');
if ($assetOffset !== false) {
    $assetRoot = realpath(__DIR__ . '/../../assets');
    $assetRel = rawurldecode(substr($path, $assetOffset + strlen('/assets/')));
    $assetFile = $assetRoot ? realpath($assetRoot . DIRECTORY_SEPARATOR . $assetRel) : false;
    if (!$assetRoot || !$assetFile || !str_starts_with($assetFile, $assetRoot . DIRECTORY_SEPARATOR) || !is_file($assetFile)) { http_response_code(404); exit; }
    $ext = strtolower(pathinfo($assetFile, PATHINFO_EXTENSION));
    $types = ['svg'=>'image/svg+xml','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp'];
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'");
    readfile($assetFile);
    exit;
}if (!str_starts_with($path, API)) {
    $pathPart = rawurldecode($path);
    $baseName = basename(rtrim($pathPart, '/'));
    $rel = ($pathPart === '/' || str_ends_with($pathPart, '/')) ? 'index.html' : $baseName;
    $root = realpath(__DIR__ . '/../../frontend');
    $file = realpath($root . DIRECTORY_SEPARATOR . $rel);
    if (!$root || !$file || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file)) {
        http_response_code(404);
        echo 'Not found';
        exit;
    }
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimeTypes = ['css'=>'text/css; charset=utf-8', 'js'=>'application/javascript; charset=utf-8', 'html'=>'text/html; charset=utf-8', 'svg'=>'image/svg+xml', 'json'=>'application/json; charset=utf-8', 'png'=>'image/png', 'jpg'=>'image/jpeg', 'jpeg'=>'image/jpeg', 'webp'=>'image/webp'];
    $mime = $mimeTypes[$extension] ?? (mime_content_type($file) ?: 'application/octet-stream');
    header('Content-Type: ' . $mime);
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'");
    readfile($file);
    exit;
}
try {
    $parts = array_values(array_filter(explode('/', trim(substr($path, strlen(API)), '/')), 'strlen'));
    $data = [];
    if ($method === 'GET' && $path === API . '/health') respond(200, ['status' => 'ok', 'mode' => 'local-development']);
    if ($method === 'GET' && $path === API . '/catalog') respond(200, ['items' => all('SELECT * FROM catalog_item WHERE published=1 ORDER BY category,name_en'), 'notice' => 'Example catalog values only; not approved customer prices.']);
    if ($method === 'GET' && $path === API . '/zones') respond(200, ['zones' => all('SELECT * FROM service_zone WHERE active=1 ORDER BY name')]);
    if ($method === 'POST' && $path === API . '/auth/register') {
        $d = body();
        $name = trim((string)($d['name'] ?? ''));
        $email = strtolower(trim((string)($d['email'] ?? '')));
        $pass = (string)($d['password'] ?? '');
        $phone = trim((string)($d['phone'] ?? ''));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 12 || strlen($phone) < 8) fail(422, 'VALIDATION', 'Provide your name, a valid email, phone, and a password of at least 12 characters.');
        if (($d['consent'] ?? false) !== true) fail(422, 'CONSENT_REQUIRED', 'Accept the privacy notice and account terms before registering.');
        $lang = $d['language'] ?? 'en';
        if (!in_array($lang, ['en', 'fr'], true)) fail(422, 'VALIDATION', 'Choose English or French.');
        $uid = uuid();
        q('INSERT INTO app_user(id,email,display_name,phone,password_hash,password_salt,role,preferred_language,terms_version,consented_at,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [$uid, $email, $name, $phone, password_hash($pass, PASSWORD_BCRYPT), str_repeat('0', 32), 'customer', $lang, (string)($d['termsVersion'] ?? 'v1'), stamp(), stamp()]);
        session($uid, 201);
    }
    if ($method === 'POST' && $path === API . '/auth/login') {
        $d = body();
        $u = one('SELECT * FROM app_user WHERE email=? AND active=1', [strtolower(trim((string)($d['email'] ?? '')))]);
        $pass = (string)($d['password'] ?? '');
        $valid = $u && password_verify($pass, $u['password_hash']);
        // Keep accounts created by the earlier Python MVP working, then upgrade their hash on successful sign-in.
        if ($u && !$valid && preg_match('/^[a-f0-9]{64}$/i', $u['password_hash']) && preg_match('/^[a-f0-9]{32}$/i', $u['password_salt'])) {
            $legacy = hash_pbkdf2('sha256', $pass, hex2bin($u['password_salt']), 310000, 64);
            $valid = hash_equals(strtolower($u['password_hash']), strtolower($legacy));
            if ($valid) q('UPDATE app_user SET password_hash=?,password_salt=? WHERE id=?', [password_hash($pass, PASSWORD_BCRYPT), str_repeat('0', 32), $u['id']]);
        }
        if (!$valid) fail(401, 'INVALID_CREDENTIALS', 'Email or password is incorrect.');
        session($u['id']);
    }
    if ($method === 'GET' && $path === API . '/me') {
        $u = actor();
        respond(200, ['user' => publicUser($u)]);
    }
    if ($method === 'POST' && $path === API . '/auth/logout') {
        $u = actor();
        $token = substr($_SERVER['HTTP_AUTHORIZATION'], 7);
        q('UPDATE auth_session SET revoked_at=? WHERE token_hash=?', [stamp(), hash('sha256', $token)]);
        respond(200, ['loggedOut' => true]);
    }
    $u = actor();
    if ($method === 'GET' && $path === API . '/admin/categories') {
        roles($u, 'admin');
        respond(200, ['categories' => all('SELECT * FROM service_category WHERE active=1 ORDER BY category_name')]);
    }
    if ($method === 'POST' && $path === API . '/admin/categories/save') {
        roles($u, 'admin'); $d=body();
        $old=trim((string)($d['originalName'] ?? '')); $name=trim((string)($d['categoryName'] ?? '')); $trade=strtolower(trim((string)($d['tradeCode'] ?? '')));
        if (strlen($name)<2 || strlen($name)>80 || !preg_match('/^[a-z][a-z0-9_]{1,39}$/',$trade)) fail(422,'VALIDATION','Provide a category name and valid technician trade code.');
        $image=adminAssetPath((string)($d['imagePath'] ?? ''),'assets/Images/','assets/Images/catalog/handyman-1.jpg');
        $icon=adminAssetPath((string)($d['iconPath'] ?? ''),'assets/Icons/','assets/Icons/clarity--tools-solid.svg');
        if ($old==='' && one('SELECT category_name FROM service_category WHERE category_name=?',[$name])) fail(409,'CATEGORY_EXISTS','That category already exists.');
        if ($old!=='') { if (!one('SELECT category_name FROM service_category WHERE category_name=?',[$old])) fail(404,'NOT_FOUND','Category was not found.'); if ($old!==$name && one('SELECT category_name FROM service_category WHERE category_name=?',[$name])) fail(409,'CATEGORY_EXISTS','That category already exists.'); }
        db()->beginTransaction();
        try {
            if ($old==='') q('INSERT INTO service_category(category_name,trade_code,image_path,icon_path,active) VALUES (?,?,?,?,1)',[$name,$trade,$image,$icon]);
            else { q('UPDATE service_category SET category_name=?,trade_code=?,image_path=?,icon_path=? WHERE category_name=?',[$name,$trade,$image,$icon,$old]); q('UPDATE catalog_item SET category=?,trade_code=?,image_path=?,icon_path=? WHERE category=?',[$name,$trade,$image,$icon,$old]); }
            audit($u['id'],$old===''?'CATEGORY_CREATED':'CATEGORY_UPDATED','service_category',$name);
            db()->commit();
        } catch (Throwable $e) { if(db()->inTransaction()) db()->rollBack(); throw $e; }
        respond($old===''?201:200,['category'=>one('SELECT * FROM service_category WHERE category_name=?',[$name])]);
    }
    if ($method === 'GET' && $path === API . '/admin/technicians') {
        roles($u,'admin');
        respond(200,['technicians'=>all("SELECT u.id,u.display_name,u.email,u.phone,u.created_at,GROUP_CONCAT(DISTINCT a.trade_code ORDER BY a.trade_code SEPARATOR ', ') AS trades,GROUP_CONCAT(DISTINCT z.name ORDER BY z.name SEPARATOR ', ') AS zones FROM app_user u LEFT JOIN employee_trade_authorization a ON a.user_id=u.id AND a.active=1 LEFT JOIN service_zone z ON z.code=a.zone_code WHERE u.role='technician' GROUP BY u.id,u.display_name,u.email,u.phone,u.created_at ORDER BY u.display_name")]);
    }
    if ($method === 'POST' && $path === API . '/admin/technicians') {
        roles($u,'admin'); $d=body();
        $name=trim((string)($d['name']??'')); $email=strtolower(trim((string)($d['email']??''))); $phone=trim((string)($d['phone']??'')); $pass=(string)($d['password']??''); $trade=strtolower(trim((string)($d['tradeCode']??''))); $zones=$d['zones']??[];
        if ($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($pass)<12 || !is_array($zones) || !count($zones)) fail(422,'VALIDATION','Provide a name, valid email, password of at least 12 characters, trade, and one or more service areas.');
        if (one('SELECT id FROM app_user WHERE email=?',[$email])) fail(409,'EMAIL_EXISTS','An account with this email already exists.');
        if (!one('SELECT category_name FROM service_category WHERE trade_code=? AND active=1',[$trade])) fail(422,'INVALID_TRADE','Choose a trade used by an active service category.');
        $zones=array_values(array_unique(array_map('strval',$zones)));
        foreach($zones as $zone) if(!one('SELECT code FROM service_zone WHERE code=? AND active=1',[$zone])) fail(422,'INVALID_ZONE','Choose only active service areas.');
        $id=uuid(); $ts=stamp();
        q('INSERT INTO app_user(id,email,display_name,phone,password_hash,password_salt,role,created_at) VALUES (?,?,?,?,?,?,?,?)',[$id,$email,$name,$phone,password_hash($pass,PASSWORD_BCRYPT),str_repeat('0',32),'technician',$ts]);
        foreach($zones as $zone) q('INSERT INTO employee_trade_authorization(user_id,trade_code,zone_code,active) VALUES (?,?,?,1)',[$id,$trade,$zone]);
        audit($u['id'],'TECHNICIAN_CREATED','app_user',$id);
        respond(201,['technician'=>one("SELECT id,email,display_name,phone,role,created_at FROM app_user WHERE id=?",[$id])]);
    }
    if ($method === 'POST' && $path === API . '/admin/catalog/save') {
        roles($u,'admin'); $d=body();
        $code=strtoupper(trim((string)($d['code']??''))); $name=trim((string)($d['name']??'')); $nameFr=trim((string)($d['nameFr']??$name)); $category=trim((string)($d['category']??'')); $description=trim((string)($d['description']??'')); $min=(int)($d['priceMin']??-1); $max=(int)($d['priceMax']??-1); $minutes=(int)($d['plannedMinutes']??45);
        if($name==='' || strlen($name)>180 || $min<0 || $max<$min || $max>100000000 || $minutes<0 || $minutes>1440) fail(422,'VALIDATION','Provide a service name, a valid XAF range, and planned minutes.');
        $cat=one('SELECT * FROM service_category WHERE category_name=? AND active=1',[$category]); if(!$cat) fail(422,'INVALID_CATEGORY','Choose an active service category.');
        if($code==='' ) { do { $code='TC-'.strtoupper(bin2hex(random_bytes(4))); } while(one('SELECT code FROM catalog_item WHERE code=?',[$code])); }
        if(!preg_match('/^[A-Z0-9-]{2,32}$/',$code)) fail(422,'VALIDATION','Use a service code with 2–32 letters, numbers, or hyphens.');
        $old=one('SELECT code FROM catalog_item WHERE code=?',[$code]); $mid=(int)(round((($min+$max)/2)/500)*500); $ts=stamp();
        if($old) q('UPDATE catalog_item SET category=?,trade_code=?,name_en=?,name_fr=?,description=?,price_min_xaf=?,price_max_xaf=?,price_xaf=?,unit=?,planned_minutes=?,published=1,version_code=?,image_path=?,icon_path=? WHERE code=?',[$category,$cat['trade_code'],$name,$nameFr,$description,$min,$max,$mid,'fixed',$minutes,'ADMIN.2026',$cat['image_path'],$cat['icon_path'],$code]);
        else q('INSERT INTO catalog_item(code,category,trade_code,name_en,name_fr,description,price_min_xaf,price_max_xaf,price_xaf,unit,planned_minutes,warranty_days,published,version_code,image_path,icon_path) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1,?,?,?)',[$code,$category,$cat['trade_code'],$name,$nameFr,$description,$min,$max,$mid,'fixed',$minutes,0,'ADMIN.2026',$cat['image_path'],$cat['icon_path']]);
        audit($u['id'],$old?'CATALOG_ITEM_UPDATED':'CATALOG_ITEM_CREATED','catalog_item',$code);
        respond($old?200:201,['item'=>one('SELECT * FROM catalog_item WHERE code=?',[$code])]);
    }
    if ($method === 'GET' && $path === API . '/admin/progress') {
        roles($u, 'admin');
        $statuses = all('SELECT status,COUNT(*) AS job_count FROM service_request GROUP BY status');
        respond(200, ['statuses' => $statuses, 'snapshotAt' => stamp()]);
    }
    if ($method === 'GET' && $path === API . '/staff') {
        roles($u, 'dispatcher', 'supervisor', 'admin');
        respond(200, ['technicians' => all("SELECT u.id,u.display_name,u.email,a.trade_code,a.zone_code FROM app_user u LEFT JOIN employee_trade_authorization a ON a.user_id=u.id AND a.active=1 WHERE u.role='technician' AND u.active=1 ORDER BY u.display_name")]);
    }
    if ($method === 'GET' && $path === API . '/dispatch/queue') {
        roles($u, 'dispatcher', 'supervisor', 'admin');
        respond(200, ['requests' => all("SELECT r.id,r.public_ref,r.customer_id,r.category,r.trade_code,r.zone_code,r.issue,r.preferred_slot,r.status,r.created_at,u.display_name AS customer_name FROM service_request r JOIN app_user u ON u.id=r.customer_id WHERE r.status IN ('REQUESTED','DISPATCHED') ORDER BY r.created_at")]);
    }
    if ($method === 'POST' && $path === API . '/requests') {
        roles($u, 'customer');
        $d = body();
        foreach (['category', 'tradeCode', 'zoneCode', 'issue', 'address', 'preferredSlot'] as $f) if (trim((string)($d[$f] ?? '')) === '') fail(422, 'VALIDATION', 'Complete the service, zone, issue, address, and preferred time fields.');
        if (($d['consent'] ?? false) !== true) fail(422, 'CONSENT_REQUIRED', 'Accept the service privacy notice before submitting.');
        if (!one('SELECT code FROM service_zone WHERE code=? AND active=1', [$d['zoneCode']])) fail(422, 'INVALID_ZONE', 'That service zone is unavailable.');
        $idem = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? null;
        if ($idem && ($old = one('SELECT id FROM service_request WHERE idempotency_key=? AND customer_id=?', [$idem, $u['id']]))) successRequest($old['id'], $u);
        $rid = uuid();
        $ref = 'TC-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $ts = stamp();
        q('INSERT INTO service_request(id,public_ref,customer_id,category,trade_code,zone_code,issue,address,preferred_slot,consent_version,consented_at,status,idempotency_key,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [$rid, $ref, $u['id'], $d['category'], strtolower((string)$d['tradeCode']), $d['zoneCode'], trim($d['issue']), trim($d['address']), $d['preferredSlot'], $d['consentVersion'] ?? 'v1', $ts, 'REQUESTED', $idem, $ts, $ts]);
        event($rid, $u['id'], 'REQUEST_CREATED', null, 'REQUESTED');
        audit($u['id'], 'REQUEST_CREATED', 'service_request', $rid);
        successRequest($rid, $u, 201);
    }
    if ($method === 'GET' && $path === API . '/requests') {
        if ($u['role'] === 'customer') $rows = all('SELECT id FROM service_request WHERE customer_id=? ORDER BY created_at DESC', [$u['id']]);
        elseif ($u['role'] === 'technician') $rows = all('SELECT DISTINCT r.id FROM service_request r JOIN dispatch_assignment d ON d.request_id=r.id WHERE d.technician_id=? AND d.revoked_at IS NULL ORDER BY r.created_at DESC', [$u['id']]);
        elseif (in_array($u['role'], ['dispatcher', 'supervisor', 'admin'], true)) $rows = all('SELECT id FROM service_request ORDER BY created_at DESC LIMIT 100');
        else fail(403, 'FORBIDDEN', 'Your account cannot view requests.');
        respond(200, ['requests' => array_map(fn($r) => record($r['id'], $u), $rows)]);
    }
    if (count($parts) >= 2 && $parts[0] === 'requests') {
        $key = $parts[1];
        $r = one('SELECT * FROM service_request WHERE id=? OR public_ref=?', [$key, $key]);
        if (!$r) fail(404, 'NOT_FOUND', 'Service request was not found.');
        $rid = $r['id'];
        $customer = $u['role'] === 'customer' && $r['customer_id'] === $u['id'];
        $assigned = $u['role'] === 'technician' && (bool)one("SELECT 1 FROM dispatch_assignment WHERE request_id=? AND technician_id=? AND status='ACCEPTED' AND revoked_at IS NULL", [$rid, $u['id']]);
        $offer = $u['role'] === 'technician' && (bool)one("SELECT 1 FROM dispatch_assignment WHERE request_id=? AND technician_id=? AND status IN ('OFFERED','ACCEPTED') AND revoked_at IS NULL", [$rid, $u['id']]);
        $staff = in_array($u['role'], ['dispatcher', 'supervisor', 'admin'], true);
        if (!$customer && !$offer && !$staff) fail(403, 'FORBIDDEN', 'This request is not available to your account.');
        if ($method === 'GET' && count($parts) === 2) successRequest($rid, $u);
        if ($method === 'POST' && ($parts[2] ?? '') === 'assignments') {
            roles($u, 'dispatcher', 'supervisor', 'admin');
            if (!in_array($r['status'], ['REQUESTED', 'DISPATCHED'], true)) fail(409, 'INVALID_STATE', 'This request is no longer available for assignment.');
            $d = body();
            $tid = (string)($d['technicianId'] ?? '');
            if (!one("SELECT id FROM app_user WHERE id=? AND role='technician' AND active=1", [$tid]) || !one('SELECT 1 FROM employee_trade_authorization WHERE user_id=? AND trade_code=? AND zone_code=? AND active=1', [$tid, $r['trade_code'], $r['zone_code']])) fail(422, 'NOT_ELIGIBLE', 'Choose an active technician authorized for this trade and zone.');
            $ts = stamp();
            q("UPDATE dispatch_assignment SET status='REASSIGNED',revoked_at=? WHERE request_id=? AND revoked_at IS NULL", [$ts, $rid]);
            $aid = uuid();
            q('INSERT INTO dispatch_assignment(id,request_id,technician_id,assigned_by,status,reason,eta,assigned_at) VALUES (?,?,?,?,?,?,?,?)', [$aid, $rid, $tid, $u['id'], 'OFFERED', $d['reason'] ?? 'Dispatch assignment', $d['eta'] ?? null, $ts]);
            q("UPDATE service_request SET status='DISPATCHED',updated_at=? WHERE id=?", [$ts, $rid]);
            event($rid, $u['id'], 'ASSIGNED', $r['status'], 'DISPATCHED', (string)($d['reason'] ?? ''), ['technicianId' => $tid]);
            audit($u['id'], 'ASSIGNED', 'service_request', $rid);
            successRequest($rid, $u, 201);
        }
        if ($method === 'POST' && ($parts[2] ?? '') === 'response') {
            roles($u, 'technician');
            $d = body();
            $a = one('SELECT * FROM dispatch_assignment WHERE request_id=? AND technician_id=? AND revoked_at IS NULL ORDER BY assigned_at DESC LIMIT 1', [$rid, $u['id']]);
            if (!$a || $a['status'] !== 'OFFERED') fail(409, 'NO_OFFER', 'There is no pending assignment offer.');
            $decision = $d['decision'] ?? '';
            if (!in_array($decision, ['accept', 'decline'], true)) fail(422, 'VALIDATION', 'Choose accept or decline.');
            $status = $decision === 'accept' ? 'ACCEPTED' : 'DECLINED';
            $next = $decision === 'accept' ? 'ACCEPTED' : 'REQUESTED';
            $ts = stamp();
            q('UPDATE dispatch_assignment SET status=?,responded_at=?,revoked_at=? WHERE id=?', [$status, $ts, $decision === 'decline' ? $ts : null, $a['id']]);
            q('UPDATE service_request SET status=?,updated_at=? WHERE id=?', [$next, $ts, $rid]);
            event($rid, $u['id'], 'ASSIGNMENT_' . $status, $r['status'], $next, (string)($d['reason'] ?? ''));
            audit($u['id'], 'ASSIGNMENT_' . $status, 'dispatch_assignment', $a['id'], (string)($d['reason'] ?? ''));
            successRequest($rid, $u);
        }
        if ($method === 'POST' && ($parts[2] ?? '') === 'diagnosis') {
            roles($u, 'technician');
            if (!$assigned) fail(409, 'ASSIGNMENT_REQUIRED', 'Accept the active assignment before recording diagnosis.');
            if (!in_array($r['status'], ['ACCEPTED', 'DIAGNOSING', 'ESTIMATE_DECLINED'], true)) fail(409, 'INVALID_STATE', 'Diagnosis cannot be changed in this request state.');
            $d = body();
            $items = $d['items'] ?? [];
            if (!is_array($items) || !count($items)) fail(422, 'VALIDATION', 'Add at least one approved work item.');
            $lines = [];
            $subtotal = 0;
            $planned = 0;
            $warranties = [];
            foreach ($items as $it) {
                $qty = (int)($it['quantity'] ?? 1);
                if ($qty < 1 || $qty > 20) fail(422, 'INVALID_QUANTITY', 'Quantity must be between 1 and 20.');
                $c = one('SELECT * FROM catalog_item WHERE code=? AND published=1 AND trade_code=?', [(string)($it['code'] ?? ''), $r['trade_code']]);
                if (!$c) fail(422, 'UNAPPROVED_ITEM', 'Diagnosis contains an item not approved for this trade.');
                $total = (int)$c['price_xaf'] * $qty;
                $subtotal += $total;
                $planned += (int)$c['planned_minutes'] * $qty;
                $lines[] = [$c, $qty, $total];
                if ($c['warranty_days']) $warranties[] = $c['name_en'] . ': ' . $c['warranty_days'] . ' days';
            }
            $eid = uuid();
            $ver = (int)(one('SELECT COALESCE(MAX(version_number),0)+1 AS n FROM estimate_version WHERE request_id=?', [$rid])['n']);
            $ts = stamp();
            q('INSERT INTO estimate_version(id,request_id,version_number,pricebook_version,status,subtotal_xaf,callout_fee_xaf,tax_xaf,total_xaf,warranty_summary,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', [$eid, $rid, $ver, '2026.01', 'SUBMITTED', $subtotal, 0, 0, $subtotal, implode('; ', $warranties) ?: 'No warranty specified', $u['id'], $ts]);
            foreach ($lines as [$c, $qty, $total]) q('INSERT INTO estimate_line(id,estimate_id,catalog_code,description_snapshot,quantity,unit_price_xaf,line_total_xaf,warranty_days) VALUES (?,?,?,?,?,?,?,?)', [uuid(), $eid, $c['code'], $c['name_en'], $qty, $c['price_xaf'], $total, $c['warranty_days']]);
            q("UPDATE service_request SET status='ESTIMATE_PENDING',updated_at=? WHERE id=?", [$ts, $rid]);
            event($rid, $u['id'], 'ESTIMATE_SUBMITTED', $r['status'], 'ESTIMATE_PENDING', '', ['estimateId' => $eid, 'plannedMinutes' => $planned]);
            audit($u['id'], 'ESTIMATE_SUBMITTED', 'estimate_version', $eid);
            successRequest($rid, $u, 201);
        }
        if ($method === 'POST' && ($parts[2] ?? '') === 'work-events') {
            roles($u, 'technician');
            $d = body();
            $action = strtoupper((string)($d['action'] ?? ''));
            if (!in_array($action, ['START', 'PAUSE', 'RESUME', 'COMPLETE'], true)) fail(422, 'VALIDATION', 'Unsupported work event.');
            if (!$assigned) fail(403, 'FORBIDDEN', 'Only the active technician can record work.');
            if ($action === 'START') {
                if ($r['status'] !== 'READY_TO_WORK' || !one("SELECT 1 FROM payment_attempt WHERE request_id=? AND status='SUCCEEDED'", [$rid])) fail(409, 'PAYMENT_REQUIRED', 'Verified payment is required before work starts.');
                $next = 'IN_PROGRESS';
            } elseif ($action === 'PAUSE') {
                if ($r['status'] !== 'IN_PROGRESS' || trim((string)($d['reason'] ?? '')) === '') fail(409, 'INVALID_STATE', 'An active job and pause reason are required.');
                $next = 'PAUSED';
            } elseif ($action === 'RESUME') {
                if ($r['status'] !== 'PAUSED') fail(409, 'INVALID_STATE', 'Only paused work can resume.');
                $next = 'IN_PROGRESS';
            } else {
                if (!in_array($r['status'], ['IN_PROGRESS', 'PAUSED'], true)) fail(409, 'INVALID_STATE', 'Work must be active or paused before submission.');
                $next = 'CUSTOMER_TEST';
            }
            $ts = stamp();
            q('INSERT INTO work_timer_event(id,request_id,technician_id,action,reason,created_at) VALUES (?,?,?,?,?,?)', [uuid(), $rid, $u['id'], $action, (string)($d['reason'] ?? ''), $ts]);
            q('UPDATE service_request SET status=?,updated_at=? WHERE id=?', [$next, $ts, $rid]);
            event($rid, $u['id'], 'WORK_' . $action, $r['status'], $next, (string)($d['reason'] ?? ''));
            audit($u['id'], 'WORK_' . $action, 'service_request', $rid, (string)($d['reason'] ?? ''));
            successRequest($rid, $u);
        }
        if ($method === 'POST' && ($parts[2] ?? '') === 'completion') {
            roles($u, 'customer');
            $d = body();
            $decision = $d['decision'] ?? '';
            if (!in_array($decision, ['complete', 'rework', 'dispute'], true)) fail(422, 'VALIDATION', 'Choose complete, rework, or dispute.');
            if ($r['customer_id'] !== $u['id']) fail(404, 'NOT_FOUND', 'Service request was not found.');
            if ($r['status'] !== 'CUSTOMER_TEST') fail(409, 'INVALID_STATE', 'The service is not waiting for your test yet.');
            $case = ['rework' => 'REWORK', 'dispute' => 'DISPUTE'][$decision] ?? null;
            if ($case) {
                if (trim((string)($d['detail'] ?? '')) === '') fail(422, 'VALIDATION', 'Describe the issue.');
                q('INSERT INTO customer_case(id,request_id,customer_id,case_type,detail,created_at) VALUES (?,?,?,?,?,?)', [uuid(), $rid, $u['id'], $case, trim($d['detail']), stamp()]);
                $next = $decision === 'rework' ? 'REWORK_OPEN' : 'DISPUTE_OPEN';
            } else {
                $next = 'COMPLETED';
                q('UPDATE dispatch_assignment SET status=\'COMPLETED\',revoked_at=? WHERE request_id=? AND revoked_at IS NULL', [stamp(), $rid]);
            }
            q('UPDATE service_request SET status=?,updated_at=? WHERE id=?', [$next, stamp(), $rid]);
            event($rid, $u['id'], 'CUSTOMER_' . strtoupper($decision), $r['status'], $next);
            audit($u['id'], 'CUSTOMER_' . strtoupper($decision), 'service_request', $rid);
            successRequest($rid, $u);
        }
    }
    if (count($parts) === 3 && $parts[0] === 'estimates' && $parts[2] === 'decision' && $method === 'POST') {
        roles($u, 'customer');
        $d = body();
        $e = one('SELECT e.*,r.customer_id,r.status AS request_status FROM estimate_version e JOIN service_request r ON r.id=e.request_id WHERE e.id=?', [$parts[1]]);
        if (!$e || $e['customer_id'] !== $u['id']) fail(404, 'NOT_FOUND', 'Estimate was not found.');
        if ($e['status'] !== 'SUBMITTED' || $e['request_status'] !== 'ESTIMATE_PENDING') fail(409, 'INVALID_STATE', 'This estimate is no longer awaiting a decision.');
        $decision = $d['decision'] ?? '';
        if (!in_array($decision, ['accept', 'decline'], true)) fail(422, 'VALIDATION', 'Choose accept or decline.');
        $st = $decision === 'accept' ? 'ACCEPTED' : 'DECLINED';
        $next = $decision === 'accept' ? 'AWAITING_PAYMENT' : 'ESTIMATE_DECLINED';
        $ts = stamp();
        q('UPDATE estimate_version SET status=?,customer_decision_at=? WHERE id=?', [$st, $ts, $parts[1]]);
        q('UPDATE service_request SET status=?,updated_at=? WHERE id=?', [$next, $ts, $e['request_id']]);
        event($e['request_id'], $u['id'], 'ESTIMATE_' . $st, $e['request_status'], $next);
        audit($u['id'], 'ESTIMATE_' . $st, 'estimate_version', $parts[1]);
        respond(200, ['estimateId' => $parts[1], 'status' => $st, 'requestStatus' => $next]);
    }
    if (count($parts) === 3 && $parts[0] === 'estimates' && $parts[2] === 'payments' && $method === 'POST') {
        roles($u, 'customer');
        $e = one('SELECT e.*,r.customer_id,r.status AS request_status FROM estimate_version e JOIN service_request r ON r.id=e.request_id WHERE e.id=?', [$parts[1]]);
        if (!$e || $e['customer_id'] !== $u['id']) fail(404, 'NOT_FOUND', 'Estimate was not found.');
        if ($e['status'] !== 'ACCEPTED' || $e['request_status'] !== 'AWAITING_PAYMENT') fail(409, 'INVALID_STATE', 'Accept the estimate before payment.');
        $p = one("SELECT id,status,amount_xaf,provider,created_at FROM payment_attempt WHERE estimate_id=? AND status IN ('CREATED','PENDING') ORDER BY created_at DESC LIMIT 1", [$parts[1]]);
        if ($p) respond(200, ['payment' => $p, 'developmentOnly' => true]);
        $key = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? uuid();
        $pid = uuid();
        $ts = stamp();
        q('INSERT INTO payment_attempt(id,request_id,estimate_id,provider,amount_xaf,status,idempotency_key,provider_reference,created_at) VALUES (?,?,?,?,?,?,?,?,?)', [$pid, $e['request_id'], $parts[1], 'development-manual', $e['total_xaf'], 'PENDING', $key, 'LOCAL-' . substr($pid, 0, 8), $ts]);
        event($e['request_id'], $u['id'], 'PAYMENT_PENDING', 'AWAITING_PAYMENT', 'AWAITING_PAYMENT', '', ['paymentId' => $pid]);
        audit($u['id'], 'PAYMENT_CREATED', 'payment_attempt', $pid);
        respond(201, ['payment' => ['id' => $pid, 'amountXaf' => (int)$e['total_xaf'], 'status' => 'PENDING', 'provider' => 'development-manual'], 'developmentOnly' => true]);
    }
    if (count($parts) === 3 && $parts[0] === 'payments' && $parts[2] === 'dev-confirm' && $method === 'POST') {
        if ((localConfig()['dev_payments'] ?? false) !== true && getenv('TECHCONNECT_DEV_PAYMENTS') !== '1') fail(404, 'NOT_FOUND', 'Development payment confirmation is disabled.');
        if ($u['role'] === 'customer') {
            $p = one('SELECT p.*,r.status AS request_status,r.customer_id FROM payment_attempt p JOIN service_request r ON r.id=p.request_id WHERE p.id=? AND r.customer_id=?', [$parts[1], $u['id']]);
        } else {
            roles($u, 'admin');
            $p = one('SELECT p.*,r.status AS request_status,r.customer_id FROM payment_attempt p JOIN service_request r ON r.id=p.request_id WHERE p.id=?', [$parts[1]]);
        }
        if (!$p) fail(404, 'NOT_FOUND', 'Payment was not found.');
        if ($p['status'] === 'SUCCEEDED') respond(200, ['status' => 'SUCCEEDED', 'duplicate' => true]);
        if ($p['status'] !== 'PENDING' || $p['request_status'] !== 'AWAITING_PAYMENT') fail(409, 'INVALID_STATE', 'Payment cannot be confirmed in its current state.');
        $ts = stamp();
        q("UPDATE payment_attempt SET status='SUCCEEDED',verified_at=? WHERE id=?", [$ts, $parts[1]]);
        q("UPDATE service_request SET status='READY_TO_WORK',updated_at=? WHERE id=?", [$ts, $p['request_id']]);
        event($p['request_id'], $u['id'], 'DEVELOPMENT_PAYMENT_CONFIRMED', 'AWAITING_PAYMENT', 'READY_TO_WORK');
        audit($u['id'], 'DEV_PAYMENT_CONFIRM', 'payment_attempt', $parts[1], 'Local development only');
        respond(200, ['status' => 'SUCCEEDED', 'verifiedAt' => $ts, 'developmentOnly' => true]);
    }
    fail(404, 'NOT_FOUND', 'API route was not found.');
} catch (ApiError $e) {
    http_response_code($e->status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Request-ID: ' . $GLOBALS['requestId']);
    echo json_encode(['error' => ['code' => $e->errorCode, 'message' => $e->getMessage(), 'requestId' => $GLOBALS['requestId']]], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('Database error ' . $GLOBALS['requestId'] . ': ' . $e->getMessage());
    $code = $e->getCode() === '23000' ? 'CONFLICT' : 'DATABASE_ERROR';
    http_response_code($code === 'CONFLICT' ? 409 : 500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => ['code' => $code, 'message' => $code === 'CONFLICT' ? 'The record conflicts with an existing value or rule.' : 'Database connection or query failed.', 'requestId' => $GLOBALS['requestId']]]);
} catch (Throwable $e) {
    error_log('Unhandled ' . $GLOBALS['requestId'] . ': ' . $e);
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'The request could not be completed.', 'requestId' => $GLOBALS['requestId']]]);
}
