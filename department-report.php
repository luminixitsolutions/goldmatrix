<?php
session_start();
require_once 'config.php';

$report_columns = [
    ['key' => 'opening', 'label' => 'OPENING'],
    ['key' => 'other_in', 'label' => 'OTHER IN'],
    ['key' => 'other_out', 'label' => 'OTHER OUT'],
    ['key' => 'other_net', 'label' => 'OTHER NET'],
    ['key' => 'main_office_in', 'label' => 'MAIN OFFICE IN'],
    ['key' => 'main_office_out', 'label' => 'MAIN OFFICE OUT'],
    ['key' => 'main_office_net', 'label' => 'MAIN OFFICE NET'],
    ['key' => 'casting_in', 'label' => 'CASTING IN'],
    ['key' => 'loss', 'label' => 'LOSS'],
    ['key' => 'closing', 'label' => 'CLOSING'],
];

$default_date = isset($_GET['report_date']) ? trim((string) $_GET['report_date']) : date('d-m-Y');
if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $default_date)) {
    $default_date = date('d-m-Y');
}

$departments = [];
$tbl = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_departments'");
if ($tbl && mysqli_num_rows($tbl) > 0) {
    mysqli_free_result($tbl);
    $departments = getList("SELECT id, dept_name FROM tbl_departments WHERE status = 1 ORDER BY dept_name ASC");
}
/**
 * Build department report rows from jobwork queue activity, order line weights, and weight adjustments.
 *
 * @param mysqli $conn
 * @param array<int,array{id:int,dept_name:string}> $departments
 * @param array $report_columns
 * @param string $dateDmY dd-mm-yyyy
 * @return array<int,array{title:string,rows:array,sums:array}>
 */
function auragold_department_report_build_tables($conn, array $departments, array $report_columns, string $dateDmY): array
{
    $dt = DateTime::createFromFormat('d-m-Y', $dateDmY);
    $reportYmd = $dt ? $dt->format('Y-m-d') : date('Y-m-d');

    $keys = array_column($report_columns, 'key');
    $emptyRow = function () use ($keys) {
        $r = ['name' => '—'];
        foreach ($keys as $k) {
            $r[$k] = 0.0;
        }
        return $r;
    };

    $findDeptId = static function (array $depts, array $needles): ?int {
        foreach ($depts as $d) {
            $n = strtoupper(trim((string) ($d['dept_name'] ?? '')));
            foreach ($needles as $needle) {
                if ($n !== '' && strpos($n, $needle) !== false) {
                    return (int) $d['id'];
                }
            }
        }
        return null;
    };
    $mainDeptId = $findDeptId($departments, ['MAIN OFFICE', 'MAIN']);
    $castDeptId = $findDeptId($departments, ['CASTING', 'CAST']);

    $hasActivity = false;
    $tAct = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_queue_activity'");
    if ($tAct && mysqli_num_rows($tAct) > 0) {
        $hasActivity = true;
    }
    if ($tAct) {
        mysqli_free_result($tAct);
    }

    $wtCol = 'final_weight';
    $tCol = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_order_items LIKE 'final_weight'");
    if (!$tCol || mysqli_num_rows($tCol) === 0) {
        $wtCol = 'gross_weight';
    }
    if ($tCol) {
        mysqli_free_result($tCol);
    }

    $jwoWt = [];
    $tItems = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_order_items'");
    if ($tItems && mysqli_num_rows($tItems) > 0) {
        mysqli_free_result($tItems);
        $qWt = 'SELECT jobwork_order_id, COALESCE(SUM(`' . mysqli_real_escape_string($conn, $wtCol) . '`), 0) AS w FROM tbl_jobwork_order_items GROUP BY jobwork_order_id';
        $rw = @mysqli_query($conn, $qWt);
        if ($rw) {
            while ($row = mysqli_fetch_assoc($rw)) {
                $jwoWt[(int) $row['jobwork_order_id']] = (float) $row['w'];
            }
            mysqli_free_result($rw);
        }
    } elseif ($tItems) {
        mysqli_free_result($tItems);
    }

    /** @var array<int,array<int,array<string,float>>> $cell */
    $cell = [];
    $touch = static function (&$cell, int $deptId, int $userId) use ($emptyRow) {
        if ($userId < 1) {
            return;
        }
        if (!isset($cell[$deptId])) {
            $cell[$deptId] = [];
        }
        if (!isset($cell[$deptId][$userId])) {
            $cell[$deptId][$userId] = $emptyRow();
            $cell[$deptId][$userId]['name'] = '';
        }
    };

    $chains = [];
    if ($hasActivity) {
        $ymd = mysqli_real_escape_string($conn, $reportYmd);
        $sqlDay = 'SELECT * FROM tbl_jobwork_queue_activity WHERE DATE(created_at) = \'' . $ymd . '\' ORDER BY jobwork_order_id ASC, created_at ASC, id ASC';
        $dayActs = function_exists('getList') ? getList($sqlDay) : [];
        if (!is_array($dayActs)) {
            $dayActs = [];
        }

        $jwoIds = [];
        foreach ($dayActs as $a) {
            $jwoIds[(int) ($a['jobwork_order_id'] ?? 0)] = true;
        }
        $jwoIds = array_keys(array_filter($jwoIds));

        if (!empty($jwoIds)) {
            $in = implode(',', array_map('intval', $jwoIds));
            $sqlAll = 'SELECT * FROM tbl_jobwork_queue_activity WHERE jobwork_order_id IN (' . $in . ') ORDER BY jobwork_order_id ASC, created_at ASC, id ASC';
            $allActs = function_exists('getList') ? getList($sqlAll) : [];
            if (!is_array($allActs)) {
                $allActs = [];
            }
            foreach ($allActs as $a) {
                $jid = (int) ($a['jobwork_order_id'] ?? 0);
                if ($jid < 1) {
                    continue;
                }
                if (!isset($chains[$jid])) {
                    $chains[$jid] = [];
                }
                $chains[$jid][] = $a;
            }
        }

        $prevFor = static function (array $chain, array $act): ?array {
            $prev = null;
            foreach ($chain as $row) {
                if ((int) ($row['id'] ?? 0) === (int) ($act['id'] ?? 0)) {
                    return $prev;
                }
                $prev = $row;
            }
            return null;
        };

        foreach ($dayActs as $act) {
            $jwoId = (int) ($act['jobwork_order_id'] ?? 0);
            $T = (int) ($act['to_dept_id'] ?? 0);
            $W = (int) ($act['to_user_id'] ?? 0);
            if ($jwoId < 1 || $T < 1) {
                continue;
            }
            $wt = isset($jwoWt[$jwoId]) ? (float) $jwoWt[$jwoId] : 0.0;
            $chain = $chains[$jwoId] ?? [];
            $prev = $prevFor($chain, $act);
            $F = $prev ? (int) ($prev['to_dept_id'] ?? 0) : 0;
            $PU = $prev ? (int) ($prev['to_user_id'] ?? 0) : 0;

            $fEff = $F > 0 ? $F : ($mainDeptId !== null ? $mainDeptId : 0);

            if ($W > 0) {
                $touch($cell, $T, $W);
                if ($mainDeptId !== null && (int) $T === (int) $mainDeptId) {
                    $cell[$T][$W]['main_office_in'] += $wt;
                }
                if ($castDeptId !== null && (int) $T === (int) $castDeptId) {
                    $cell[$T][$W]['casting_in'] += $wt;
                    if ($mainDeptId !== null && $fEff === (int) $mainDeptId) {
                        $cell[$T][$W]['main_office_in'] += $wt;
                    }
                }
                $isFromOther = $fEff > 0
                    && ($mainDeptId === null || $fEff !== (int) $mainDeptId)
                    && ($castDeptId === null || $fEff !== (int) $castDeptId);
                if ($isFromOther) {
                    $cell[$T][$W]['other_in'] += $wt;
                }
            }

            if ($fEff > 0 && $T > 0 && $fEff !== $T && $PU > 0) {
                if ($mainDeptId !== null && $fEff === (int) $mainDeptId) {
                    $touch($cell, (int) $mainDeptId, $PU);
                    $cell[$mainDeptId][$PU]['main_office_out'] += $wt;
                } else {
                    $touch($cell, $fEff, $PU);
                    $cell[$fEff][$PU]['other_out'] += $wt;
                }
            }
        }
    }

    $lossByDeptUser = [];
    $tWadj = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_weight_adjustments'");
    $hasWadj = ($tWadj && mysqli_num_rows($tWadj) > 0);
    if ($tWadj) {
        mysqli_free_result($tWadj);
    }
    $hasDeptUser = false;
    $cdu = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_orders LIKE 'department_user_id'");
    if ($cdu && mysqli_num_rows($cdu) > 0) {
        $hasDeptUser = true;
    }
    if ($cdu) {
        mysqli_free_result($cdu);
    }
    $hasDeptCol = false;
    $cdd = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_orders LIKE 'department_id'");
    if ($cdd && mysqli_num_rows($cdd) > 0) {
        $hasDeptCol = true;
    }
    if ($cdd) {
        mysqli_free_result($cdd);
    }

    if ($hasWadj && $hasDeptCol) {
        $ymd = mysqli_real_escape_string($conn, $reportYmd);
        $sel = 'w.jobwork_order_id, w.adjustment_type, w.weight_grams, j.department_id';
        if ($hasDeptUser) {
            $sel .= ', j.department_user_id';
        } else {
            $sel .= ', 0 AS department_user_id';
        }
        $sqlLoss = 'SELECT ' . $sel . ' FROM tbl_jobwork_weight_adjustments w INNER JOIN tbl_jobwork_orders j ON j.id = w.jobwork_order_id WHERE DATE(w.created_at) = \'' . $ymd . '\'';
        $lossRows = function_exists('getList') ? getList($sqlLoss) : [];
        if (!is_array($lossRows)) {
            $lossRows = [];
        }
        foreach ($lossRows as $lr) {
            $du = $hasDeptUser ? (int) ($lr['department_user_id'] ?? 0) : 0;
            $dd = (int) ($lr['department_id'] ?? 0);
            if ($dd < 1 || $du < 1) {
                continue;
            }
            $wg = (float) ($lr['weight_grams'] ?? 0);
            $typ = strtolower(trim((string) ($lr['adjustment_type'] ?? 'reduce')));
            $signed = ($typ === 'add') ? -$wg : $wg;
            if (!isset($lossByDeptUser[$dd])) {
                $lossByDeptUser[$dd] = [];
            }
            if (!isset($lossByDeptUser[$dd][$du])) {
                $lossByDeptUser[$dd][$du] = 0.0;
            }
            $lossByDeptUser[$dd][$du] += $signed;
        }
    }

    $userIds = [];
    foreach ($cell as $du => $users) {
        foreach ($users as $uid => $_) {
            if ($uid > 0) {
                $userIds[$uid] = true;
            }
        }
    }
    foreach ($lossByDeptUser as $du => $users) {
        foreach ($users as $uid => $_) {
            if ($uid > 0) {
                $userIds[$uid] = true;
            }
        }
    }
    $names = [];
    if (!empty($userIds)) {
        $in = implode(',', array_map('intval', array_keys($userIds)));
        $nq = 'SELECT id, name FROM tbl_customers WHERE id IN (' . $in . ')';
        $nr = @mysqli_query($conn, $nq);
        if ($nr) {
            while ($row = mysqli_fetch_assoc($nr)) {
                $names[(int) $row['id']] = trim((string) ($row['name'] ?? ''));
            }
            mysqli_free_result($nr);
        }
    }

    $out = [];
    foreach ($departments as $d) {
        $deptId = (int) ($d['id'] ?? 0);
        $label = trim((string) ($d['dept_name'] ?? ''));
        if ($label === '' || $deptId < 1) {
            continue;
        }

        $rows = [];
        $deptUsers = isset($cell[$deptId]) ? $cell[$deptId] : [];
        foreach ($lossByDeptUser[$deptId] ?? [] as $uid => $lv) {
            if ($uid > 0 && !isset($deptUsers[$uid])) {
                $deptUsers[$uid] = $emptyRow();
                $deptUsers[$uid]['name'] = '';
            }
        }

        if (empty($deptUsers)) {
            $rows[] = $emptyRow();
        } else {
            ksort($deptUsers, SORT_NUMERIC);
            foreach ($deptUsers as $uid => $r) {
                if ($uid < 1) {
                    continue;
                }
                $r['name'] = isset($names[$uid]) && $names[$uid] !== '' ? $names[$uid] : ('User #' . $uid);
                if (isset($lossByDeptUser[$deptId][$uid])) {
                    $r['loss'] += (float) $lossByDeptUser[$deptId][$uid];
                }
                $r['other_net'] = (float) $r['other_in'] - (float) $r['other_out'];
                $r['main_office_net'] = (float) $r['main_office_in'] - (float) $r['main_office_out'];
                $opening = (float) $r['opening'];
                $r['closing'] = $opening + $r['main_office_net'] + $r['other_net'] + (float) $r['casting_in'] - (float) $r['loss'];
                $rows[] = $r;
            }
        }

        $sums = [];
        foreach ($report_columns as $col) {
            $sums[$col['key']] = 0.0;
        }
        foreach ($rows as $r) {
            foreach ($report_columns as $col) {
                $k = $col['key'];
                $sums[$k] += (float) ($r[$k] ?? 0);
            }
        }

        $out[] = [
            'title' => strtoupper($label),
            'rows' => $rows,
            'sums' => $sums,
        ];
    }

    return $out;
}

$department_tables = auragold_department_report_build_tables($conn, $departments, $report_columns, $default_date);

function auragold_format_dept_report_num(float $v): string
{
    return number_format($v, 3, '.', '');
}
?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title>Department Report - AuraGold Software</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <?php include 'header-script.php'; ?>
</head>

<style>
html, body {
    height: 100vh;
    overflow-x: hidden !important;
    overflow-y: hidden !important;
    background: #f2f3f7;
}

.layout-content {
    height: calc(100vh - 60px);
    overflow: hidden;
}

.container-fluid {
    height: 100%;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    padding: 8px 10px 10px !important;
}

.dept-report-shell {
    height: 100%;
    border: 1px solid #d6dbea;
    border-radius: 12px;
    background: #f7f8fc;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.dept-report-header {
    height: 34px;
    background: #f8f9ff;
    border-bottom: 1px solid #d6dbea;
    position: relative;
}

.title-chip {
    position: absolute;
    right: 10px;
    top: 5px;
    border: 1px solid #c8b7f9;
    color: #5c3fb3;
    background: #ece6ff;
    border-radius: 0 16px 16px 0;
    padding: 3px 14px;
    font-size: 13px;
    font-weight: 700;
}

.dept-report-toolbar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    padding: 8px 10px;
    border-bottom: 1px solid #d6dbea;
    background: #fafbff;
}

.dept-report-toolbar form {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.toolbar-label {
    font-size: 13px;
    font-weight: 600;
    color: #4a5568;
}

.date-input-wrap {
    display: inline-flex;
    align-items: center;
    border: 1px solid #8f79dd;
    background: #fff;
    border-radius: 6px;
    padding: 0 8px;
    height: 28px;
}

.date-input-wrap input {
    border: 0;
    outline: 0;
    font-size: 13px;
    font-weight: 600;
    color: #2d3748;
    width: 92px;
}

.date-input-wrap .feather {
    width: 15px;
    height: 15px;
    color: #6f42c1;
}

.btn-mini {
    height: 28px;
    border: 1px solid #8f79dd;
    color: #4f39ab;
    background: #f6f2ff;
    border-radius: 6px;
    padding: 0 12px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
}

.btn-mini:hover {
    background: #ede7ff;
}

.btn-icon-mini {
    width: 28px;
    height: 28px;
    border: 1px solid #8f79dd;
    background: #f6f2ff;
    color: #4f39ab;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.btn-icon-mini:hover {
    background: #ede7ff;
}

.export-dropdown {
    position: relative;
}

.export-dropdown .dropdown-menu {
    min-width: 140px;
    font-size: 13px;
    border-radius: 8px;
    border: 1px solid #d6dbea;
    box-shadow: 0 8px 20px rgba(31, 41, 55, 0.12);
}

.export-dropdown .dropdown-item {
    padding: 6px 12px;
    font-weight: 600;
    color: #4f39ab;
}

.dept-report-scroll {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 10px 10px 14px;
}

.dept-section {
    margin-bottom: 16px;
}

.dept-section:last-child {
    margin-bottom: 4px;
}

.dept-section-title {
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: #3d4a63;
    margin: 0 0 6px 2px;
}

.table-outer {
    border: 1px solid #c5d4e8;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}

.table-wrap {
    position: relative;
    overflow-x: auto;
    overflow-y: visible;
    max-width: 100%;
}

.dept-report-table {
    width: max-content;
    min-width: 100%;
    border-collapse: collapse;
    margin: 0;
    font-size: 12px;
}

.dept-report-table thead th {
    background: #add8e6;
    border-right: 1px solid #8ebdd4;
    border-bottom: 1px solid #8ebdd4;
    color: #1a365d;
    font-weight: 700;
    padding: 6px 10px;
    white-space: nowrap;
    text-align: right;
}

.dept-report-table thead th:first-child {
    text-align: left;
    min-width: 100px;
}

.dept-report-table tbody td {
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    padding: 5px 10px;
    color: #2d3748;
    text-align: right;
    white-space: nowrap;
}

.dept-report-table tbody td:first-child {
    text-align: left;
    font-weight: 600;
    color: #2c5282;
}

.dept-report-table tfoot td {
    border-right: 1px solid #c5d4e8;
    border-top: 2px solid #8ebdd4;
    background: #e8f4fc;
    padding: 6px 10px;
    font-weight: 800;
    color: #1a365d;
    text-align: right;
    white-space: nowrap;
}

.dept-report-table tfoot td:first-child {
    text-align: left;
}

.table-footer-bar {
    height: 18px;
    border-top: 1px solid #d6dbea;
    background: #f8f7fd;
    position: relative;
}

.scroll-mock {
    position: absolute;
    left: 22px;
    right: 28px;
    top: 3px;
    height: 10px;
    border-radius: 8px;
    background: #bca9d8;
}
</style>

<body>
<?php include 'sidebar.php'; ?>

<div class="layout-content">
    <div class="container-fluid flex-grow-1" style="padding-top:0;padding-bottom:0;">
        <div class="dept-report-shell">
            <div class="dept-report-header">
                <span class="title-chip">Department Report</span>
            </div>

            <div class="dept-report-toolbar">
                <form method="get" action="department-report.php" id="deptReportFilterForm">
                    <span class="toolbar-label">Date</span>
                    <span class="date-input-wrap">
                        <input type="text" name="report_date" id="reportDateInput" value="<?php echo htmlspecialchars($default_date, ENT_QUOTES, 'UTF-8'); ?>" placeholder="dd-mm-yyyy" autocomplete="off" pattern="\d{2}-\d{2}-\d{4}" title="dd-mm-yyyy">
                        <i class="feather icon-calendar"></i>
                    </span>
                    <button type="submit" class="btn-mini">Apply</button>
                </form>

                <div class="dropdown export-dropdown">
                    <button class="btn-mini dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                        Export <i class="feather icon-chevron-down" style="width:14px;height:14px;"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="#" onclick="window.print(); return false;">Print / PDF</a>
                        <a class="dropdown-item" href="#" onclick="alert('Export to Excel can be connected when data API is ready.'); return false;">Excel</a>
                    </div>
                </div>
                <button type="button" class="btn-icon-mini" title="Layout"><i class="feather icon-grid"></i></button>
            </div>

            <div class="dept-report-scroll">
                <?php foreach ($department_tables as $block): ?>
                    <section class="dept-section">
                        <h2 class="dept-section-title"><?php echo htmlspecialchars($block['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <div class="table-outer">
                            <div class="table-wrap">
                                <table class="dept-report-table">
                                    <thead>
                                        <tr>
                                            <th scope="col"></th>
                                            <?php foreach ($report_columns as $col): ?>
                                                <th scope="col"><?php echo htmlspecialchars($col['label'], ENT_QUOTES, 'UTF-8'); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($block['rows'] as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <?php foreach ($report_columns as $col): ?>
                                                    <td><?php echo htmlspecialchars(auragold_format_dept_report_num((float) ($row[$col['key']] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td>Sum:</td>
                                            <?php foreach ($report_columns as $col): ?>
                                                <td><?php echo htmlspecialchars(auragold_format_dept_report_num((float) ($block['sums'][$col['key']] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="table-footer-bar"><div class="scroll-mock"></div></div>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer-script.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('reportDateInput');
    if (!input || typeof $ === 'undefined' || !$.fn.datepicker) return;
    $(input).datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true,
        todayHighlight: true
    }).on('changeDate', function () {
        $(this).datepicker('hide');
    });
});
</script>
</body>
</html>
