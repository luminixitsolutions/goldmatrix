<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auragold_carat_dashboard_image_schema.php';

header('Content-Type: application/json');

$user_id = $_SESSION['Admin']['id'] ?? 0;
$action  = $_POST['action'] ?? ($_GET['action'] ?? '');
$table   = 'tbl_carat';

auragold_ensure_tbl_carat_dashboard_images($conn);

$has_metal_col = function_exists('auragold_tbl_has_column') && auragold_tbl_has_column($conn, 'tbl_carat', 'metal_id');
$has_img_cols = function_exists('auragold_tbl_has_column') && auragold_tbl_has_column($conn, 'tbl_carat', 'dashboard_image_path');

$resolve_metal_name = static function ($conn, $metal_id) {
    $metal_id = (int) $metal_id;
    if ($metal_id <= 0) {
        return '';
    }
    $r = @getRecord('SELECT display_name FROM tbl_metal WHERE id = ' . $metal_id . ' AND status = 1 LIMIT 1');
    return is_array($r) ? trim((string) ($r['display_name'] ?? '')) : '';
};

$carat_delete_dashboard_upload = static function (string $relativePath): void {
    $relativePath = str_replace('\\', '/', $relativePath);
    $relativePath = ltrim($relativePath, '/');
    if ($relativePath === '' || strpos($relativePath, '..') !== false) {
        return;
    }
    if (strpos($relativePath, 'uploads/metal-dashboard/') !== 0) {
        return;
    }
    $full = realpath(__DIR__ . '/../' . $relativePath);
    $base = realpath(__DIR__ . '/../uploads/metal-dashboard');
    if ($full === false || $base === false || strpos($full, $base) !== 0) {
        return;
    }
    if (is_file($full)) {
        @unlink($full);
    }
};

/** @return array{ok:bool, path:string, message:string} path is relative admin path or '' */
$carat_save_dashboard_upload = static function (int $carat_id): array {
    if ($carat_id <= 0 || empty($_FILES['dashboard_image']) || !isset($_FILES['dashboard_image']['error'])) {
        return ['ok' => false, 'path' => '', 'message' => ''];
    }
    if ((int) $_FILES['dashboard_image']['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => '', 'message' => 'Upload failed'];
    }
    $tmp = (string) ($_FILES['dashboard_image']['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'path' => '', 'message' => 'Invalid upload'];
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
    $mime = $finfo ? (string) finfo_file($finfo, $tmp) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    if ($mime === '' || !isset($allowed[$mime])) {
        return ['ok' => false, 'path' => '', 'message' => 'Only JPG, PNG, GIF, WEBP allowed'];
    }
    $ext = $allowed[$mime];
    $maxBytes = 3 * 1024 * 1024;
    $size = filesize($tmp);
    if ($size === false || $size > $maxBytes) {
        return ['ok' => false, 'path' => '', 'message' => 'Image too large (max 3MB)'];
    }
    $dir = __DIR__ . '/../uploads/metal-dashboard';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return ['ok' => false, 'path' => '', 'message' => 'Could not create upload folder'];
    }
    $name = 'carat_' . $carat_id . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $destFs = $dir . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($tmp, $destFs)) {
        return ['ok' => false, 'path' => '', 'message' => 'Could not save file'];
    }
    $relative = 'uploads/metal-dashboard/' . $name;
    return ['ok' => true, 'path' => $relative, 'message' => ''];
};

$normalize_ext_url = static function (string $u): string {
    $u = trim($u);
    if ($u === '') {
        return '';
    }
    if (strlen($u) > 1000) {
        return '';
    }
    if (!preg_match('#^https?://#i', $u)) {
        return '';
    }
    return $u;
};

/* ================= GET (for edit modal) ================= */
if ($action === 'get') {
    $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid id']);
        exit;
    }
    if (!auragold_master_can_mutate_row($conn, $table, $id)) {
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }
    $cols = 'id,name,metal_id,purity,description';
    if ($has_img_cols) {
        $cols .= ',dashboard_image_path,dashboard_image_url';
    }
    $r = getRecord('SELECT ' . $cols . ' FROM tbl_carat WHERE id = ' . $id . ' AND status = 1 LIMIT 1');
    if (!is_array($r)) {
        echo json_encode(['status' => 'error', 'message' => 'Not found']);
        exit;
    }
    $out = ['status' => 'success', 'row' => $r];
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

/* ================= ADD ================= */
if ($action === 'add') {

    $name   = esc($_POST['name'] ?? '');
    $purity = esc($_POST['purity'] ?? '');
    $desc   = esc($_POST['description'] ?? '');
    $metal_id = isset($_POST['metal_id']) ? (int) $_POST['metal_id'] : 0;
    $ext_url_in = $normalize_ext_url((string) ($_POST['dashboard_image_url'] ?? ''));
    $ext_url_sql = mysqli_real_escape_string($conn, $ext_url_in);
    $clear_img = isset($_POST['clear_dashboard_image']) && (string) $_POST['clear_dashboard_image'] === '1';

    if ($name == '') {
        echo json_encode(["status"=>"error","message"=>"Carat name required"]);
        exit;
    }

    $bid = auragold_master_branch_id_for_writes($conn, $table);

    if ($has_metal_col) {
        if ($metal_id <= 0) {
            echo json_encode(["status"=>"error","message"=>"Metal is required"]);
            exit;
        }
        $mn = $resolve_metal_name($conn, $metal_id);
        if ($mn === '') {
            echo json_encode(["status"=>"error","message"=>"Invalid metal"]);
            exit;
        }
        if ($has_img_cols) {
            mysqli_query($conn,"
                INSERT INTO tbl_carat (name, metal_id, purity, description, dashboard_image_url, branch_id, created_by)
                VALUES ('$name','$metal_id','$purity','$desc','" . ($clear_img ? '' : $ext_url_sql) . "','$bid','$user_id')
            ");
        } else {
            mysqli_query($conn,"
                INSERT INTO tbl_carat (name, metal_id, purity, description, branch_id, created_by)
                VALUES ('$name','$metal_id','$purity','$desc','$bid','$user_id')
            ");
        }
    } else {
        if ($has_img_cols) {
            mysqli_query($conn,"
                INSERT INTO tbl_carat (name, purity, description, dashboard_image_url, branch_id, created_by)
                VALUES ('$name','$purity','$desc','" . ($clear_img ? '' : $ext_url_sql) . "','$bid','$user_id')
            ");
        } else {
            mysqli_query($conn,"
                INSERT INTO tbl_carat (name, purity, description, branch_id, created_by)
                VALUES ('$name','$purity','$desc','$bid','$user_id')
            ");
        }
    }

    $new_id = mysqli_insert_id($conn);

    $saved_path_display = '';
    $img_path_esc = '';
    if ($has_img_cols && !$clear_img && !empty($_FILES['dashboard_image']) && (int) ($_FILES['dashboard_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $up = $carat_save_dashboard_upload((int) $new_id);
        if (!$up['ok'] && $up['message'] !== '') {
            echo json_encode(['status' => 'error', 'message' => $up['message']]);
            exit;
        }
        if ($up['ok'] && $up['path'] !== '') {
            $saved_path_display = $up['path'];
            $img_path_esc = mysqli_real_escape_string($conn, $up['path']);
            mysqli_query($conn, "UPDATE tbl_carat SET dashboard_image_path = '$img_path_esc' WHERE id = " . (int) $new_id);
        }
    }

    $out = [
        "status"=>"success",
        "id"=>$new_id,
        "name"=>$name,
        "purity"=>$purity,
        "description"=>$desc,
    ];
    if ($has_metal_col) {
        $out['metal_id'] = $metal_id;
        $out['metal_name'] = $resolve_metal_name($conn, $metal_id);
    }
    if ($has_img_cols) {
        $out['dashboard_image_path'] = $saved_path_display;
        $out['dashboard_image_url'] = $clear_img ? '' : $ext_url_in;
        $out['has_dashboard_thumb'] = ($saved_path_display !== '' || ($clear_img ? false : $ext_url_in !== ''));
    }
    echo json_encode($out);
    exit;
}

/* ================= UPDATE ================= */
if ($action === 'update') {

    $id     = intval($_POST['id']);
    $name   = esc($_POST['name'] ?? '');
    $purity = esc($_POST['purity'] ?? '');
    $desc   = esc($_POST['description'] ?? '');
    $metal_id = isset($_POST['metal_id']) ? (int) $_POST['metal_id'] : 0;
    $ext_url_in = $normalize_ext_url((string) ($_POST['dashboard_image_url'] ?? ''));
    $ext_url_sql = mysqli_real_escape_string($conn, $ext_url_in);
    $clear_img = isset($_POST['clear_dashboard_image']) && (string) $_POST['clear_dashboard_image'] === '1';

    if ($id == 0 || $name == '') {
        echo json_encode(["status"=>"error","message"=>"Invalid data"]);
        exit;
    }

    if (!auragold_master_can_mutate_row($conn, $table, $id)) {
        echo json_encode(["status"=>"error","message"=>"Access denied for this branch"]);
        exit;
    }

    $prev_path = '';
    if ($has_img_cols) {
        $prev = @getRecord('SELECT dashboard_image_path FROM tbl_carat WHERE id = ' . (int) $id . ' LIMIT 1');
        if (is_array($prev)) {
            $prev_path = trim((string) ($prev['dashboard_image_path'] ?? ''));
        }
    }

    if ($has_metal_col) {
        if ($metal_id <= 0) {
            echo json_encode(["status"=>"error","message"=>"Metal is required"]);
            exit;
        }
        $mn = $resolve_metal_name($conn, $metal_id);
        if ($mn === '') {
            echo json_encode(["status"=>"error","message"=>"Invalid metal"]);
            exit;
        }
        if ($has_img_cols && $clear_img) {
            if ($prev_path !== '') {
                $carat_delete_dashboard_upload($prev_path);
            }
            mysqli_query($conn,"
                UPDATE tbl_carat
                SET name='$name',
                    metal_id='$metal_id',
                    purity='$purity',
                    description='$desc',
                    dashboard_image_path=NULL,
                    dashboard_image_url=NULL,
                    modified_by='$user_id'
                WHERE id='$id'
            ");
        } elseif ($has_img_cols) {
            $path_sql = mysqli_real_escape_string($conn, $prev_path);
            if (!empty($_FILES['dashboard_image']) && (int) ($_FILES['dashboard_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $up = $carat_save_dashboard_upload((int) $id);
                if (!$up['ok']) {
                    echo json_encode(['status' => 'error', 'message' => $up['message'] !== '' ? $up['message'] : 'Upload failed']);
                    exit;
                }
                if ($up['ok'] && $up['path'] !== '') {
                    if ($prev_path !== '' && $prev_path !== $up['path']) {
                        $carat_delete_dashboard_upload($prev_path);
                    }
                    $path_sql = mysqli_real_escape_string($conn, $up['path']);
                }
            }
            mysqli_query($conn,"
                UPDATE tbl_carat
                SET name='$name',
                    metal_id='$metal_id',
                    purity='$purity',
                    description='$desc',
                    dashboard_image_path='$path_sql',
                    dashboard_image_url='$ext_url_sql',
                    modified_by='$user_id'
                WHERE id='$id'
            ");
        } else {
            mysqli_query($conn,"
                UPDATE tbl_carat
                SET name='$name',
                    metal_id='$metal_id',
                    purity='$purity',
                    description='$desc',
                    modified_by='$user_id'
                WHERE id='$id'
            ");
        }
    } else {
        if ($has_img_cols && $clear_img) {
            if ($prev_path !== '') {
                $carat_delete_dashboard_upload($prev_path);
            }
            mysqli_query($conn,"
                UPDATE tbl_carat
                SET name='$name',
                    purity='$purity',
                    description='$desc',
                    dashboard_image_path=NULL,
                    dashboard_image_url=NULL,
                    modified_by='$user_id'
                WHERE id='$id'
            ");
        } elseif ($has_img_cols) {
            $path_sql = mysqli_real_escape_string($conn, $prev_path);
            if (!empty($_FILES['dashboard_image']) && (int) ($_FILES['dashboard_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $up = $carat_save_dashboard_upload((int) $id);
                if (!$up['ok']) {
                    echo json_encode(['status' => 'error', 'message' => $up['message'] !== '' ? $up['message'] : 'Upload failed']);
                    exit;
                }
                if ($up['ok'] && $up['path'] !== '') {
                    if ($prev_path !== '' && $prev_path !== $up['path']) {
                        $carat_delete_dashboard_upload($prev_path);
                    }
                    $path_sql = mysqli_real_escape_string($conn, $up['path']);
                }
            }
            mysqli_query($conn,"
                UPDATE tbl_carat
                SET name='$name',
                    purity='$purity',
                    description='$desc',
                    dashboard_image_path='$path_sql',
                    dashboard_image_url='$ext_url_sql',
                    modified_by='$user_id'
                WHERE id='$id'
            ");
        } else {
            mysqli_query($conn,"
                UPDATE tbl_carat
                SET name='$name',
                    purity='$purity',
                    description='$desc',
                    modified_by='$user_id'
                WHERE id='$id'
            ");
        }
    }

    $out = [
        "status"=>"success",
        "id"=>$id,
        "name"=>$name,
        "purity"=>$purity,
        "description"=>$desc
    ];
    if ($has_metal_col) {
        $out['metal_id'] = $metal_id;
        $out['metal_name'] = $resolve_metal_name($conn, $metal_id);
    }
    if ($has_img_cols) {
        $r2 = @getRecord('SELECT dashboard_image_path, dashboard_image_url FROM tbl_carat WHERE id = ' . (int) $id . ' LIMIT 1');
        $dp = is_array($r2) ? trim((string) ($r2['dashboard_image_path'] ?? '')) : '';
        $du = is_array($r2) ? trim((string) ($r2['dashboard_image_url'] ?? '')) : '';
        $out['dashboard_image_path'] = $dp;
        $out['dashboard_image_url'] = $du;
        $out['has_dashboard_thumb'] = ($dp !== '' || $du !== '');
    }
    echo json_encode($out);
    exit;
}

/* ================= DELETE ================= */
if ($action === 'delete') {

    $id = intval($_POST['id']);

    if (!auragold_master_can_mutate_row($conn, $table, $id)) {
        echo json_encode(["status"=>"error","message"=>"Access denied for this branch"]);
        exit;
    }

    if ($has_img_cols) {
        $prev = @getRecord('SELECT dashboard_image_path FROM tbl_carat WHERE id = ' . (int) $id . ' LIMIT 1');
        if (is_array($prev)) {
            $p = trim((string) ($prev['dashboard_image_path'] ?? ''));
            if ($p !== '') {
                $carat_delete_dashboard_upload($p);
            }
        }
    }

    mysqli_query($conn,"
        UPDATE tbl_carat
        SET status=0, modified_by='$user_id'
        WHERE id='$id'
    ");

    echo json_encode(["status"=>"success"]);
    exit;
}

echo json_encode(["status"=>"error","message"=>"Invalid action"]);
