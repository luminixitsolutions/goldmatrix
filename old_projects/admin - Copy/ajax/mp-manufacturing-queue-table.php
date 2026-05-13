<?php
/**
 * Manufacturing Process — full table: Jobwork Queue activity + weight adjustments (all mfg queue columns).
 */
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

/** When set, only rows for this job work order are returned (job card print / history). */
$filter_jobwork_order_id = isset($_GET['jobwork_order_id']) ? (int) $_GET['jobwork_order_id'] : 0;

$jchk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_order_items'");
$has_items = ($jchk && mysqli_num_rows($jchk) > 0);
if ($jchk) {
    mysqli_free_result($jchk);
}

$ji_cols = [];
if ($has_items) {
    $ji_cols_q = @mysqli_query($conn, 'SHOW COLUMNS FROM tbl_jobwork_order_items');
    if ($ji_cols_q) {
        while ($c = mysqli_fetch_assoc($ji_cols_q)) {
            $fn = (string)($c['Field'] ?? '');
            if ($fn !== '') {
                $ji_cols[$fn] = true;
            }
        }
        mysqli_free_result($ji_cols_q);
    }
}

$ji = function ($expr) use ($has_items) {
    return $has_items ? '(SELECT ' . $expr . ' FROM tbl_jobwork_order_items ji WHERE ji.jobwork_order_id = j.id ORDER BY ji.id ASC LIMIT 1)' : 'NULL';
};

$ji_tag = $ji('ji.barcode');
$ji_design = $ji('ji.design_no');
$ji_product = $ji('ji.product_name');
$ji_qty = $ji('ji.quantity');
$ji_carat = $ji('ji.carat');
$ji_final = $ji('ji.final_weight');
$ji_net = $ji('ji.net_weight');
$ji_gross = $ji('ji.gross_weight');
$ji_less = $ji('ji.less_weight');
$ji_purity_w = $ji('ji.purity_weight');
$ji_net_amt = $ji('ji.net_amount');
$ji_diamond_expr = 'NULL';
if (!empty($ji_cols['diamond_weight']) && !empty($ji_cols['diamond_wt'])) {
    $ji_diamond_expr = 'COALESCE(ji.diamond_weight, ji.diamond_wt, 0)';
} elseif (!empty($ji_cols['diamond_weight'])) {
    $ji_diamond_expr = 'COALESCE(ji.diamond_weight, 0)';
} elseif (!empty($ji_cols['diamond_wt'])) {
    $ji_diamond_expr = 'COALESCE(ji.diamond_wt, 0)';
}
$ji_diamond = $ji($ji_diamond_expr);

$mfg_sec = '';
$sc = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_orders LIKE 'manufacturing_time_seconds'");
if ($sc && mysqli_num_rows($sc) > 0) {
    $mfg_sec = ', j.manufacturing_time_seconds';
}
if ($sc) {
    mysqli_free_result($sc);
}

function mp_mfg_fmt_wt($n) {
    if ($n === null || $n === '') {
        return '—';
    }
    $f = (float)$n;
    return number_format($f, 3, '.', '');
}

function mp_mfg_fmt_money($n) {
    if ($n === null || $n === '') {
        return '—';
    }
    $f = (float)$n;
    return number_format($f, 2, '.', '');
}

function mp_mfg_dt($dt) {
    if ($dt === null || $dt === '') {
        return '—';
    }
    $t = strtotime($dt);
    if ($t === false) {
        return '—';
    }
    return date('d-m-Y H:i:s', $t);
}

function mp_mfg_sort_ts($dt) {
    if ($dt === null || $dt === '') {
        return 0;
    }
    $t = strtotime($dt);
    return ($t === false) ? 0 : $t;
}

function mp_mfg_infer_metal($product) {
    $p = strtolower((string)$product);
    if (strpos($p, 'gold') !== false) {
        return 'Gold';
    }
    if (strpos($p, 'silver') !== false) {
        return 'Silver';
    }
    if (strpos($p, 'platinum') !== false) {
        return 'Platinum';
    }
    return '—';
}

/** Job card / queue: "CASTING (VIJAY) ==> SETTING (AJAY)" style labels. */
function mp_mfg_flow_stage($dept, $user) {
    $d = strtoupper(trim((string)$dept));
    $u = strtoupper(trim((string)$user));
    if ($d === '' && $u === '') {
        return '—';
    }
    if ($d === '') {
        return $u !== '' ? $u : '—';
    }
    if ($u === '') {
        return $d;
    }
    return $d . ' (' . $u . ')';
}

function mp_mfg_diamond_wt($storedDiamond, $gross, $net, $less) {
    $sd = ($storedDiamond !== null && $storedDiamond !== '') ? (float)$storedDiamond : null;
    if ($sd !== null && is_finite($sd) && $sd >= 0) {
        return mp_mfg_fmt_wt($sd);
    }
    $g = ($gross !== null && $gross !== '') ? (float)$gross : null;
    $n = ($net !== null && $net !== '') ? (float)$net : null;
    $l = ($less !== null && $less !== '') ? (float)$less : null;
    if ($g === null || !is_finite($g)) {
        return '—';
    }
    if ($n === null || !is_finite($n)) {
        $n = 0;
    }
    if ($l === null || !is_finite($l)) {
        $l = 0;
    }
    $d = $g - $n - $l;
    if ($d < 0) {
        $d = 0;
    }
    return mp_mfg_fmt_wt($d);
}

$rows = [];

$wchk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_weight_adjustments'");
$has_weight = ($wchk && mysqli_num_rows($wchk) > 0);
if ($wchk) {
    mysqli_free_result($wchk);
}

$achk_w = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_queue_activity'");
$has_activity_for_weight = ($achk_w && mysqli_num_rows($achk_w) > 0);
if ($achk_w) {
    mysqli_free_result($achk_w);
}

if ($has_weight) {
    $wsrc_mp = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_weight_adjustments LIKE 'source_department_id'");
    if (!$wsrc_mp || mysqli_num_rows($wsrc_mp) === 0) {
        @mysqli_query($conn, 'ALTER TABLE tbl_jobwork_weight_adjustments ADD COLUMN source_department_id int(11) DEFAULT NULL AFTER created_by_user_id, ADD COLUMN source_user_id int(11) DEFAULT NULL AFTER source_department_id');
    }
    if ($wsrc_mp) {
        mysqli_free_result($wsrc_mp);
    }

    $queue_at_event_sql = $has_activity_for_weight
        ? '(SELECT a.jobwork_queue_no FROM tbl_jobwork_queue_activity a WHERE a.jobwork_order_id = w.jobwork_order_id AND a.created_at <= w.created_at ORDER BY a.created_at DESC, a.id DESC LIMIT 1) AS queue_at_event'
        : 'NULL AS queue_at_event';

    $sql = 'SELECT w.id AS adjustment_id, w.jobwork_order_id, w.adjustment_type, w.weight_grams, w.remark, w.created_at,
        j.jobwork_queue_no, j.jobwork_no, j.sale_order_id, j.sale_order_no, j.customer_name,
        ' . $queue_at_event_sql . ',
        COALESCE(w.source_department_id, j.department_id) AS stock_dept_id,
        (CASE WHEN w.source_user_id IS NOT NULL AND w.source_user_id > 0 THEN w.source_user_id ELSE j.department_user_id END) AS stock_user_id,
        j.status AS jwo_status,
        d.dept_name,
        cu.name AS worker_name
        ' . $mfg_sec . '
        , ' . $ji_tag . ' AS item_tag,
        ' . $ji_design . ' AS item_design,
        ' . $ji_product . ' AS item_product,
        ' . $ji_qty . ' AS item_qty,
        ' . $ji_carat . ' AS item_carat,
        ' . $ji_less . ' AS item_less_wt,
        ' . $ji_purity_w . ' AS item_purity_wt,
        ' . $ji_gross . ' AS item_gross_wt,
        ' . $ji_net . ' AS item_net_wt,
        ' . $ji_diamond . ' AS item_diamond_wt,
        ' . $ji_net_amt . ' AS item_net_amount
        FROM tbl_jobwork_weight_adjustments w
        INNER JOIN tbl_jobwork_orders j ON j.id = w.jobwork_order_id
        LEFT JOIN tbl_departments d ON d.id = COALESCE(w.source_department_id, j.department_id)
        LEFT JOIN tbl_customers cu ON cu.id = (CASE WHEN w.source_user_id IS NOT NULL AND w.source_user_id > 0 THEN w.source_user_id ELSE j.department_user_id END)
        ORDER BY w.created_at DESC, w.id DESC
        LIMIT 500';

    $list = function_exists('getList') ? @getList($sql) : null;
    if (!is_array($list)) {
        $list = [];
    }

    foreach ($list as $r) {
        $adj = isset($r['adjustment_type']) && $r['adjustment_type'] === 'add' ? 'add' : 'reduce';
        $w = isset($r['weight_grams']) ? (float)$r['weight_grams'] : 0;
        $remark = isset($r['remark']) ? trim((string)$r['remark']) : '';
        $qn = trim((string)($r['queue_at_event'] ?? ''));
        if ($qn === '') {
            $qn = trim((string)($r['jobwork_queue_no'] ?? ''));
        }
        if ($qn === '') {
            $qn = 'JWQ-' . (int)($r['jobwork_order_id'] ?? 0);
        }
        $tag = trim((string)($r['item_tag'] ?? ''));
        if ($tag === '') {
            $tag = '—';
        }
        $design = trim((string)($r['item_design'] ?? ''));
        if ($design === '') {
            $design = '—';
        }
        $product = trim((string)($r['item_product'] ?? ''));
        $act_label = $adj === 'add' ? 'Add weight' : 'Reduce weight';
        $is_loss_reduce = ($adj === 'reduce' && stripos($remark, 'auto loss') !== false);
        $comment = $remark !== '' ? ($act_label . ': ' . $remark) : $act_label;
        if ($is_loss_reduce) {
            $comment = 'Outward · loss · ' . ($remark !== '' ? $remark : $act_label);
        }

        $wt_display = mp_mfg_fmt_wt($w);
        if ($adj === 'reduce') {
            $wt_display = '-' . $wt_display;
        }

        $ca = $r['created_at'] ?? null;
        $gross = $r['item_gross_wt'] ?? null;
        $netw = $r['item_net_wt'] ?? null;
        $lessw = $r['item_less_wt'] ?? null;

        $issueWt = '—';
        $recvWt = '—';
        if ($adj === 'reduce') {
            $issueWt = mp_mfg_fmt_wt($w);
        } else {
            $recvWt = mp_mfg_fmt_wt($w);
        }

        $jwoId = (int)($r['jobwork_order_id'] ?? 0);
        $deptId = (int)($r['stock_dept_id'] ?? 0);
        $userId = (int)($r['stock_user_id'] ?? 0);
        $mfgSec = isset($r['manufacturing_time_seconds']) ? (int)$r['manufacturing_time_seconds'] : 0;

        $rows[] = [
            '_sort' => mp_mfg_sort_ts($ca),
            'row_kind' => 'weight',
            'source_id' => (int)($r['adjustment_id'] ?? 0),
            'adjustment_id' => (int)($r['adjustment_id'] ?? 0),
            'jobwork_order_id' => $jwoId,
            'jobwork_queue_no_attr' => $qn,
            'department_id' => $deptId,
            'department_user_id' => $userId,
            'jobwork_no' => trim((string)($r['jobwork_no'] ?? '')),
            'sale_order_id' => (int)($r['sale_order_id'] ?? 0),
            'sale_order_no' => trim((string)($r['sale_order_no'] ?? '')),
            'first_product' => $product,
            'manufacturing_seconds' => $mfgSec,
            'queue_no' => $qn,
            'comment' => $comment,
            'product_name' => $product !== '' ? $product : '—',
            'active' => isset($r['jwo_status']) && $r['jwo_status'] !== '' ? (string)$r['jwo_status'] : '—',
            'image_urls' => '—',
            'against_queue' => '—',
            'against_invoice' => trim((string)($r['sale_order_no'] ?? '')) !== '' ? trim((string)$r['sale_order_no']) : '—',
            'metal' => mp_mfg_infer_metal($product),
            'description' => $product !== '' ? $product : '—',
            'dust_wastage_wt' => ($lessw !== null && $lessw !== '') ? mp_mfg_fmt_wt($lessw) : '—',
            'loss_wt' => ($adj === 'reduce' && $w > 0) ? mp_mfg_fmt_wt($w) : '—',
            'profit_wt' => isset($r['item_net_amount']) ? mp_mfg_fmt_money($r['item_net_amount']) : '—',
            'tag_no' => $tag,
            'total_wt' => $wt_display,
            'metal_wt' => ($netw !== null && $netw !== '') ? mp_mfg_fmt_wt($netw) : '—',
            'diamond_wt' => mp_mfg_diamond_wt(($r['item_diamond_wt'] ?? null), $gross, $netw, $lessw),
            'purity_wt' => isset($r['item_purity_wt']) ? mp_mfg_fmt_wt($r['item_purity_wt']) : '—',
            'carat_name' => isset($r['item_carat']) && trim((string)$r['item_carat']) !== '' ? trim((string)$r['item_carat']) : '—',
            'total_quantity' => isset($r['item_qty']) ? mp_mfg_fmt_wt($r['item_qty']) : '—',
            'date_time' => mp_mfg_dt($ca),
            'branch_name' => '—',
            'design_no' => $design,
            'department_name' => trim((string)($r['dept_name'] ?? '')) !== '' ? trim((string)$r['dept_name']) : '—',
            'user_name' => trim((string)($r['worker_name'] ?? '')) !== '' ? trim((string)$r['worker_name']) : '—',
            'status' => isset($r['jwo_status']) && $r['jwo_status'] !== '' ? (string)$r['jwo_status'] : '—',
            'issue_wt' => $issueWt,
            'receive_wt' => $recvWt,
            'balance_wt' => '—',
            'stock_flow_type' => $adj === 'add' ? 'inward' : 'outward',
            'weight_event' => $adj === 'add' ? 'add' : ($is_loss_reduce ? 'loss' : 'issue'),
            'department_flow' => mp_mfg_flow_stage($r['dept_name'] ?? '', $r['worker_name'] ?? ''),
        ];
    }
}

$achk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_queue_activity'");
$has_activity = ($achk && mysqli_num_rows($achk) > 0);
if ($achk) {
    mysqli_free_result($achk);
}

$transfer_loss_sel = ', NULL AS transfer_loss_wt';
if ($has_weight) {
    $transfer_loss_sel = ", (SELECT w.weight_grams FROM tbl_jobwork_weight_adjustments w
        WHERE w.jobwork_order_id = j.id AND w.adjustment_type = 'reduce'
        AND w.created_at <= a.created_at
        AND w.created_at >= DATE_SUB(a.created_at, INTERVAL 60 SECOND)
        ORDER BY w.id DESC LIMIT 1) AS transfer_loss_wt";
}

if ($has_activity) {
    $acf_mp = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_queue_activity LIKE 'from_dept_id'");
    if (!$acf_mp || mysqli_num_rows($acf_mp) === 0) {
        @mysqli_query($conn, 'ALTER TABLE tbl_jobwork_queue_activity ADD COLUMN from_dept_id int(11) DEFAULT NULL AFTER jobwork_queue_no, ADD COLUMN from_user_id int(11) DEFAULT NULL AFTER from_dept_id');
    }
    if ($acf_mp) {
        mysqli_free_result($acf_mp);
    }

    $aca_mp = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_queue_activity LIKE 'activity_action'");
    if (!$aca_mp || mysqli_num_rows($aca_mp) === 0) {
        @mysqli_query($conn, 'ALTER TABLE tbl_jobwork_queue_activity ADD COLUMN activity_action varchar(32) DEFAULT NULL AFTER to_user_id');
    }
    if ($aca_mp) {
        mysqli_free_result($aca_mp);
    }

    $asql = 'SELECT a.id AS activity_id, a.jobwork_order_id, a.created_at, a.jobwork_queue_no AS act_queue_no,
        a.activity_action, a.from_dept_id, a.from_user_id, a.to_dept_id, a.to_user_id,
        j.jobwork_queue_no, j.jobwork_no, j.sale_order_id, j.sale_order_no, j.status AS jwo_status,
        j.department_id, j.department_user_id
        ' . $mfg_sec . '
        , td.dept_name AS dest_dept_name,
        tu.name AS dest_user_name,
        fd.dept_name AS src_dept_name,
        fu.name AS src_user_name,
        ' . $ji_tag . ' AS item_tag,
        ' . $ji_design . ' AS item_design,
        ' . $ji_product . ' AS item_product,
        ' . $ji_qty . ' AS item_qty,
        ' . $ji_carat . ' AS item_carat,
        ' . $ji_final . ' AS item_final_wt,
        ' . $ji_net . ' AS item_net_wt,
        ' . $ji_less . ' AS item_less_wt,
        ' . $ji_purity_w . ' AS item_purity_wt,
        ' . $ji_gross . ' AS item_gross_wt,
        ' . $ji_diamond . ' AS item_diamond_wt,
        ' . $ji_net_amt . ' AS item_net_amount
        ' . $transfer_loss_sel . '
        FROM tbl_jobwork_queue_activity a
        INNER JOIN tbl_jobwork_orders j ON j.id = a.jobwork_order_id
        LEFT JOIN tbl_departments td ON td.id = a.to_dept_id
        LEFT JOIN tbl_customers tu ON tu.id = a.to_user_id
        LEFT JOIN tbl_departments fd ON fd.id = a.from_dept_id
        LEFT JOIN tbl_customers fu ON fu.id = a.from_user_id
        ORDER BY a.created_at DESC, a.id DESC
        LIMIT 500';

    $alist = function_exists('getList') ? @getList($asql) : null;
    if (!is_array($alist)) {
        $alist = [];
    }

    foreach ($alist as $r) {
        // Per-transfer queue no is on the activity row; header j.jobwork_queue_no is the latest only.
        $qn = trim((string)($r['act_queue_no'] ?? ''));
        if ($qn === '') {
            $qn = trim((string)($r['jobwork_queue_no'] ?? ''));
        }
        if ($qn === '') {
            $qn = 'JWQ-' . (int)($r['jobwork_order_id'] ?? 0);
        }
        $tag = trim((string)($r['item_tag'] ?? ''));
        if ($tag === '') {
            $tag = '—';
        }
        $design = trim((string)($r['item_design'] ?? ''));
        if ($design === '') {
            $design = '—';
        }
        $product = trim((string)($r['item_product'] ?? ''));
        $deptn = trim((string)($r['dest_dept_name'] ?? ''));
        $usrn = trim((string)($r['dest_user_name'] ?? ''));
        $srcdeptn = trim((string)($r['src_dept_name'] ?? ''));
        $srcusrn = trim((string)($r['src_user_name'] ?? ''));
        $toDeptId = isset($r['to_dept_id']) && $r['to_dept_id'] !== null && $r['to_dept_id'] !== '' ? (int)$r['to_dept_id'] : 0;
        $toUserId = isset($r['to_user_id']) && $r['to_user_id'] !== null && $r['to_user_id'] !== '' ? (int)$r['to_user_id'] : 0;
        $fromDeptId = isset($r['from_dept_id']) && $r['from_dept_id'] !== null && $r['from_dept_id'] !== '' ? (int)$r['from_dept_id'] : 0;
        $fromUserId = isset($r['from_user_id']) && $r['from_user_id'] !== null && $r['from_user_id'] !== '' ? (int)$r['from_user_id'] : 0;
        $actAction = isset($r['activity_action']) ? trim((string)$r['activity_action']) : '';

        $fw = isset($r['item_final_wt']) ? (float)$r['item_final_wt'] : null;
        $nw = isset($r['item_net_wt']) ? (float)$r['item_net_wt'] : null;
        $gross = $r['item_gross_wt'] ?? null;
        $gw = ($gross !== null && $gross !== '') ? (float)$gross : null;
        $lessw = $r['item_less_wt'] ?? null;
        $ca = $r['created_at'] ?? null;

        // Inward/outward activity: prefer net (metal) and final (queue line total after transfer) over stale gross,
        // so destination dept balance matches transferred weight (e.g. metal 19 after 1g loss, not gross 20).
        $act_total_wt = '—';
        if ($nw !== null && is_finite($nw) && (float)$nw > 0) {
            $act_total_wt = mp_mfg_fmt_wt($nw);
        } elseif ($fw !== null && is_finite($fw) && (float)$fw > 0) {
            $act_total_wt = mp_mfg_fmt_wt($fw);
        } elseif ($gw !== null && is_finite($gw) && (float)$gw > 0) {
            $act_total_wt = mp_mfg_fmt_wt($gw);
        }
        $act_balance_wt = $act_total_wt;

        $tloss = isset($r['transfer_loss_wt']) ? (float)$r['transfer_loss_wt'] : 0.0;
        if (!is_finite($tloss)) {
            $tloss = 0.0;
        }
        $activity_loss_wt = ($tloss > 0.00001) ? mp_mfg_fmt_wt($tloss) : '—';

        $jwoId = (int)($r['jobwork_order_id'] ?? 0);
        $jwoDeptId = (int)($r['department_id'] ?? 0);
        $jwoUserId = (int)($r['department_user_id'] ?? 0);
        $mfgSec = isset($r['manufacturing_time_seconds']) ? (int)$r['manufacturing_time_seconds'] : 0;
        $actId = (int)($r['activity_id'] ?? 0);

        $common = [
            '_sort' => mp_mfg_sort_ts($ca),
            'row_kind' => 'activity',
            'source_id' => $actId,
            'adjustment_id' => 0,
            'jobwork_order_id' => $jwoId,
            'jobwork_queue_no_attr' => $qn,
            'jobwork_no' => trim((string)($r['jobwork_no'] ?? '')),
            'sale_order_id' => (int)($r['sale_order_id'] ?? 0),
            'sale_order_no' => trim((string)($r['sale_order_no'] ?? '')),
            'first_product' => $product,
            'manufacturing_seconds' => $mfgSec,
            'queue_no' => $qn,
            'product_name' => $product !== '' ? $product : '—',
            'active' => isset($r['jwo_status']) && $r['jwo_status'] !== '' ? (string)$r['jwo_status'] : '—',
            'image_urls' => '—',
            'against_queue' => '—',
            'against_invoice' => trim((string)($r['sale_order_no'] ?? '')) !== '' ? trim((string)$r['sale_order_no']) : '—',
            'metal' => mp_mfg_infer_metal($product),
            'description' => $product !== '' ? $product : '—',
            'dust_wastage_wt' => ($lessw !== null && $lessw !== '') ? mp_mfg_fmt_wt($lessw) : '—',
            'loss_wt' => $activity_loss_wt,
            'profit_wt' => isset($r['item_net_amount']) ? mp_mfg_fmt_money($r['item_net_amount']) : '—',
            'tag_no' => $tag,
            'total_wt' => $act_total_wt,
            'metal_wt' => ($nw !== null && is_finite($nw)) ? mp_mfg_fmt_wt($nw) : '—',
            'diamond_wt' => mp_mfg_diamond_wt(($r['item_diamond_wt'] ?? null), $gross, $nw, $lessw),
            'purity_wt' => isset($r['item_purity_wt']) ? mp_mfg_fmt_wt($r['item_purity_wt']) : '—',
            'carat_name' => isset($r['item_carat']) && trim((string)$r['item_carat']) !== '' ? trim((string)$r['item_carat']) : '—',
            'total_quantity' => isset($r['item_qty']) ? mp_mfg_fmt_wt($r['item_qty']) : '—',
            'date_time' => mp_mfg_dt($ca),
            'branch_name' => '—',
            'design_no' => $design,
            'status' => isset($r['jwo_status']) && $r['jwo_status'] !== '' ? (string)$r['jwo_status'] : '—',
            'issue_wt' => '—',
            'receive_wt' => '—',
            'balance_wt' => $act_balance_wt,
        ];

        $filterToDept = $toDeptId > 0 ? $toDeptId : $jwoDeptId;
        $filterToUser = $toUserId > 0 ? $toUserId : $jwoUserId;
        $isJobworkCreate = ($actAction === 'jobwork_create')
            || ($actAction === '' && $fromDeptId < 1);
        $isRealTransfer = ($fromDeptId > 0 && ($fromDeptId !== $toDeptId || $fromUserId !== $toUserId));

        if ($isJobworkCreate) {
            $commentCreate = 'Inward · Job work order · Source: Job Work Order';
            $createDeptName = $srcdeptn !== '' ? $srcdeptn : $deptn;
            $createUserName = $srcusrn !== '' ? $srcusrn : $usrn;
            if ($createDeptName !== '') {
                $commentCreate .= ' · ' . $createDeptName;
            }
            if ($createUserName !== '') {
                $commentCreate .= ' · ' . $createUserName;
            }
            $jwFlowLabel = strtoupper(trim((string)($r['jobwork_no'] ?? '')));
            if ($jwFlowLabel === '') {
                $jwFlowLabel = 'JWO-' . $jwoId;
            }
            $deptFlowCreate = $jwFlowLabel . ' ==> ' . mp_mfg_flow_stage($createDeptName, $createUserName);
            $rows[] = array_merge($common, [
                'department_id' => $filterToDept,
                'department_user_id' => $filterToUser,
                'comment' => $commentCreate,
                'department_name' => $createDeptName !== '' ? $createDeptName : '—',
                'user_name' => $createUserName !== '' ? $createUserName : '—',
                'activity_side' => 'in',
                'stock_flow_type' => 'inward',
                'flow_source' => 'job_work_order',
                'total_wt' => mp_mfg_fmt_wt(0),
                'metal_wt' => mp_mfg_fmt_wt(0),
                'balance_wt' => mp_mfg_fmt_wt(0),
                'diamond_wt' => '—',
                'total_quantity' => mp_mfg_fmt_wt(0),
                'department_flow' => $deptFlowCreate,
            ]);
            continue;
        }

        $commentIn = 'Inward · Received';
        if ($fromDeptId > 0) {
            $commentIn .= ' · transfer_from: ' . ($srcdeptn !== '' ? $srcdeptn : ('Dept #' . $fromDeptId));
            if ($srcusrn !== '') {
                $commentIn .= ' · ' . $srcusrn;
            }
        }
        $commentIn .= ' · To ' . ($deptn !== '' ? $deptn : '—');
        if ($usrn !== '') {
            $commentIn .= ' · ' . $usrn;
        }

        $fromFlow = mp_mfg_flow_stage($srcdeptn, $srcusrn);
        if ($fromFlow === '—') {
            $jf = strtoupper(trim((string)($r['jobwork_no'] ?? '')));
            $fromFlow = $jf !== '' ? $jf : ('JWQ-' . $jwoId);
        }
        $deptFlowXfer = $fromFlow . ' ==> ' . mp_mfg_flow_stage($deptn, $usrn);

        $rows[] = array_merge($common, [
                'department_id' => $filterToDept,
                'department_user_id' => $filterToUser,
                'comment' => $commentIn,
                'department_name' => $deptn !== '' ? $deptn : '—',
                'user_name' => $usrn !== '' ? $usrn : '—',
                'activity_side' => 'in',
                'stock_flow_type' => 'inward',
                'flow_source' => 'department_transfer',
                'department_flow' => $deptFlowXfer . ' · In',
            ]);

        if ($isRealTransfer) {
            $commentOut = 'Outward · Sent · transfer_to: ' . ($deptn !== '' ? $deptn : ('Dept #' . $toDeptId));
            if ($usrn !== '') {
                $commentOut .= ' · ' . $usrn;
            }
            $rows[] = array_merge($common, [
                'department_id' => $fromDeptId,
                'department_user_id' => $fromUserId > 0 ? $fromUserId : 0,
                'comment' => $commentOut,
                'department_name' => $srcdeptn !== '' ? $srcdeptn : '—',
                'user_name' => $srcusrn !== '' ? $srcusrn : '—',
                'activity_side' => 'out',
                'stock_flow_type' => 'outward',
                'flow_source' => 'department_transfer',
                'department_flow' => $deptFlowXfer . ' · Out',
            ]);
        }
    }
}

$mi_m = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_material_issues'");
$mi_i = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_material_issue_items'");
$has_mi = ($mi_m && mysqli_num_rows($mi_m) > 0 && $mi_i && mysqli_num_rows($mi_i) > 0);
if ($mi_m) {
    mysqli_free_result($mi_m);
}
if ($mi_i) {
    mysqli_free_result($mi_i);
}
if ($has_mi) {
    $mi_req_col = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_material_issue_items LIKE 'requested_wt'");
    $mi_has_req = ($mi_req_col && mysqli_num_rows($mi_req_col) > 0);
    if ($mi_req_col) {
        mysqli_free_result($mi_req_col);
    }
    if (!$mi_has_req) {
        $has_mi = false;
    }
}
if ($has_mi) {
    $mi_sql = 'SELECT mi.id AS material_issue_id, mi.material_issue_no, mi.sale_order_id, mi.sale_order_no, mi.customer_name,
        mi.department_id, mi.department_user_id, mi.updated_at, mi.created_at,
        mii.id AS mi_item_id, mii.product_name, mii.design_no, mii.barcode, mii.requested_wt, mii.quantity, mii.carat,
        mii.gross_weight, mii.net_weight, mii.less_weight, mii.purity_weight, mii.final_weight,
        d.dept_name, cu.name AS worker_name
        FROM tbl_material_issues mi
        INNER JOIN tbl_material_issue_items mii ON mii.material_issue_id = mi.id
        LEFT JOIN tbl_departments d ON d.id = mi.department_id
        LEFT JOIN tbl_customers cu ON cu.id = mi.department_user_id
        WHERE mi.department_id IS NOT NULL AND mi.department_id > 0
        AND COALESCE(mii.requested_wt, 0) > 0.00005
        ORDER BY COALESCE(mi.updated_at, mi.created_at) DESC, mii.id DESC
        LIMIT 300';
    $mil = function_exists('getList') ? @getList($mi_sql) : null;
    if (!is_array($mil)) {
        $mil = [];
    }
    foreach ($mil as $r) {
        $rw = isset($r['requested_wt']) ? (float)$r['requested_wt'] : 0.0;
        if (!is_finite($rw) || $rw <= 0) {
            continue;
        }
        $deptId = (int)($r['department_id'] ?? 0);
        $userId = (int)($r['department_user_id'] ?? 0);
        $ca = $r['updated_at'] ?? ($r['created_at'] ?? null);
        $product = trim((string)($r['product_name'] ?? ''));
        $design = trim((string)($r['design_no'] ?? ''));
        if ($design === '') {
            $design = '—';
        }
        $tag = trim((string)($r['barcode'] ?? ''));
        if ($tag === '') {
            $tag = '—';
        }
        $miNo = trim((string)($r['material_issue_no'] ?? ''));
        $wt_display = mp_mfg_fmt_wt($rw);
        $gross = $r['gross_weight'] ?? null;
        $netw = $r['net_weight'] ?? null;
        $lessw = $r['less_weight'] ?? null;
        $itemId = (int)($r['mi_item_id'] ?? 0);
        $miId = (int)($r['material_issue_id'] ?? 0);
        $comment = 'Inward · Material Issue · ' . ($miNo !== '' ? $miNo : ('MI #' . $miId));
        if ($product !== '') {
            $comment .= ' · ' . $product;
        }

        $rows[] = [
            '_sort' => mp_mfg_sort_ts($ca),
            'row_kind' => 'material_issue',
            'source_id' => $itemId > 0 ? $itemId : $miId,
            'adjustment_id' => 0,
            'jobwork_order_id' => 0,
            'material_issue_id' => $miId,
            'jobwork_queue_no_attr' => '',
            'department_id' => $deptId,
            'department_user_id' => $userId,
            'jobwork_no' => $miNo !== '' ? $miNo : '—',
            'sale_order_id' => (int)($r['sale_order_id'] ?? 0),
            'sale_order_no' => trim((string)($r['sale_order_no'] ?? '')),
            'first_product' => $product,
            'manufacturing_seconds' => 0,
            'queue_no' => $miNo !== '' ? $miNo : ('MI-' . $miId),
            'comment' => $comment,
            'product_name' => $product !== '' ? $product : '—',
            'active' => '—',
            'image_urls' => '—',
            'against_queue' => '—',
            'against_invoice' => trim((string)($r['sale_order_no'] ?? '')) !== '' ? trim((string)($r['sale_order_no'])) : '—',
            'metal' => mp_mfg_infer_metal($product),
            'description' => $product !== '' ? $product : '—',
            'dust_wastage_wt' => ($lessw !== null && $lessw !== '') ? mp_mfg_fmt_wt($lessw) : '—',
            'loss_wt' => '—',
            'profit_wt' => '—',
            'tag_no' => $tag,
            'total_wt' => $wt_display,
            'metal_wt' => ($netw !== null && $netw !== '') ? mp_mfg_fmt_wt($netw) : '—',
            'diamond_wt' => mp_mfg_diamond_wt(($r['item_diamond_wt'] ?? null), $gross, $netw, $lessw),
            'purity_wt' => isset($r['purity_weight']) ? mp_mfg_fmt_wt($r['purity_weight']) : '—',
            'carat_name' => isset($r['carat']) && trim((string)$r['carat']) !== '' ? trim((string)$r['carat']) : '—',
            'total_quantity' => isset($r['quantity']) ? mp_mfg_fmt_wt($r['quantity']) : '—',
            'date_time' => mp_mfg_dt($ca),
            'branch_name' => '—',
            'design_no' => $design,
            'department_name' => trim((string)($r['dept_name'] ?? '')) !== '' ? trim((string)$r['dept_name']) : '—',
            'user_name' => trim((string)($r['worker_name'] ?? '')) !== '' ? trim((string)$r['worker_name']) : '—',
            'status' => '—',
            'issue_wt' => '—',
            'receive_wt' => $wt_display,
            'balance_wt' => '—',
                       'stock_flow_type' => 'inward',
            'weight_event' => 'material_issue',
            'flow_source' => 'material_issue',
            'department_flow' => strtoupper($miNo !== '' ? $miNo : ('MI-' . $miId)) . ' ==> ' . mp_mfg_flow_stage($r['dept_name'] ?? '', $r['worker_name'] ?? ''),
        ];
    }
}

if ($filter_jobwork_order_id > 0) {
    $rows = array_values(array_filter($rows, function ($r) use ($filter_jobwork_order_id) {
        return (int) ($r['jobwork_order_id'] ?? 0) === $filter_jobwork_order_id;
    }));
}

usort($rows, function ($a, $b) {
    return ($b['_sort'] ?? 0) <=> ($a['_sort'] ?? 0);
});
$rows = array_slice($rows, 0, 500);

/** Per jobwork order: weight & qty currently assigned to a department (source of truth for floor stock). */
$jobwork_location_totals = [];
if ($has_items) {
    $jloc_has_du = false;
    $jloc_du_chk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_orders LIKE 'department_user_id'");
    if ($jloc_du_chk && mysqli_num_rows($jloc_du_chk) > 0) {
        $jloc_has_du = true;
    }
    if ($jloc_du_chk) {
        mysqli_free_result($jloc_du_chk);
    }
    $jloc_user_expr = $jloc_has_du ? 'COALESCE(j.department_user_id, 0)' : '0';
    $jloc_group_user = $jloc_has_du ? ', j.department_user_id' : '';
    $jloc_sql = 'SELECT j.id AS jobwork_order_id, j.department_id,
        ' . $jloc_user_expr . ' AS department_user_id,
        SUM(
            CASE
                WHEN COALESCE(ji.final_weight, 0) > 0.0000001 THEN ji.final_weight
                WHEN COALESCE(ji.net_weight, 0) > 0.0000001 THEN ji.net_weight
                WHEN COALESCE(ji.gross_weight, 0) > 0.0000001 THEN ji.gross_weight
                ELSE 0
            END
        ) AS total_wt,
        SUM(COALESCE(ji.quantity, 0)) AS total_qty
        FROM tbl_jobwork_orders j
        INNER JOIN tbl_jobwork_order_items ji ON ji.jobwork_order_id = j.id
        WHERE j.department_id IS NOT NULL AND j.department_id > 0
        GROUP BY j.id, j.department_id' . $jloc_group_user;
    $jl = function_exists('getList') ? @getList($jloc_sql) : null;
    if (is_array($jl)) {
        foreach ($jl as $row) {
            $jobwork_location_totals[] = [
                'jobwork_order_id' => (int) ($row['jobwork_order_id'] ?? 0),
                'department_id' => (int) ($row['department_id'] ?? 0),
                'department_user_id' => (int) ($row['department_user_id'] ?? 0),
                'total_wt' => round((float) ($row['total_wt'] ?? 0), 4),
                'total_qty' => round((float) ($row['total_qty'] ?? 0), 4),
            ];
        }
    }
}

if ($filter_jobwork_order_id > 0) {
    $jobwork_location_totals = array_values(array_filter($jobwork_location_totals, function ($t) use ($filter_jobwork_order_id) {
        return (int) ($t['jobwork_order_id'] ?? 0) === $filter_jobwork_order_id;
    }));
}

/** Same rule as manufacturing-process.php cards / Jobwork Queue modal: floor weight applies only after a real dept transfer. */
$jwo_floor_transfer_map = [];
$jwo_ids_for_floor = [];
foreach ($rows as $r) {
    $jid = (int) ($r['jobwork_order_id'] ?? 0);
    if ($jid > 0) {
        $jwo_ids_for_floor[$jid] = true;
    }
}
if ($has_activity && count($jwo_ids_for_floor) > 0) {
    $ids_in = implode(',', array_map('intval', array_keys($jwo_ids_for_floor)));
    $xfer_cond = '(LOWER(TRIM(IFNULL(a.activity_action,\'\'))) = \'department_transfer\''
        . ' OR ('
        . ' (a.activity_action IS NULL OR TRIM(IFNULL(a.activity_action,\'\')) = \'\')'
        . ' AND IFNULL(a.from_dept_id,0) > 0'
        . ' AND (IFNULL(a.from_dept_id,0) <> IFNULL(a.to_dept_id,0) OR IFNULL(a.from_user_id,0) <> IFNULL(a.to_user_id,0))'
        . '))';
    $fsql = 'SELECT a.jobwork_order_id, COUNT(*) AS c FROM tbl_jobwork_queue_activity a'
        . ' WHERE a.jobwork_order_id IN (' . $ids_in . ') AND ' . $xfer_cond
        . ' GROUP BY a.jobwork_order_id';
    $flist = function_exists('getList') ? @getList($fsql) : null;
    if (is_array($flist)) {
        foreach ($flist as $fr) {
            $fj = (int) ($fr['jobwork_order_id'] ?? 0);
            if ($fj > 0 && (int) ($fr['c'] ?? 0) > 0) {
                $jwo_floor_transfer_map[$fj] = 1;
            }
        }
    }
}
foreach ($rows as &$r) {
    $jid = (int) ($r['jobwork_order_id'] ?? 0);
    $r['jwo_has_floor_transfer'] = ($jid > 0 && !empty($jwo_floor_transfer_map[$jid])) ? 1 : 0;
}
unset($r);

$out = [];
foreach ($rows as $r) {
    unset($r['_sort']);
    $out[] = $r;
}

echo json_encode([
    'ok' => true,
    'rows' => $out,
    'jobwork_location_totals' => $jobwork_location_totals,
], JSON_UNESCAPED_UNICODE);
