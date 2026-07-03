<?php
/**
 * ?debug_perf=1 — page timing, SQL log, includes, memory (admin / local only).
 */
if (!function_exists('auragold_perf_bootstrap')) {
    function auragold_perf_enabled(): bool
    {
        if (PHP_SAPI === 'cli') {
            return !empty(getenv('AURAGOLD_DEBUG_PERF'));
        }
        if (!empty($_GET['debug_perf']) && (string) $_GET['debug_perf'] === '1') {
            return true;
        }
        return !empty(getenv('AURAGOLD_DEBUG_PERF'));
    }

    function auragold_perf_bootstrap(): void
    {
        if (!auragold_perf_enabled()) {
            return;
        }
        if (!defined('AURAGOLD_PERF_START')) {
            define('AURAGOLD_PERF_START', microtime(true));
        }
        if (!isset($GLOBALS['auragold_perf'])) {
            $GLOBALS['auragold_perf'] = [
                'queries'   => [],
                'includes'  => [],
                'conn_ms'   => null,
                'slow_ms'   => 300,
            ];
        }
        register_shutdown_function('auragold_perf_render_report');
    }

    function auragold_perf_log_include(string $file): void
    {
        if (!auragold_perf_enabled()) {
            return;
        }
        $GLOBALS['auragold_perf']['includes'][] = [
            'file' => $file,
            'ms'   => round((microtime(true) - AURAGOLD_PERF_START) * 1000, 2),
        ];
    }

    function auragold_perf_log_query(string $sql, float $ms, ?string $source = null): void
    {
        if (!auragold_perf_enabled()) {
            return;
        }
        $GLOBALS['auragold_perf']['queries'][] = [
            'sql'    => $sql,
            'ms'     => round($ms, 2),
            'source' => $source,
        ];
    }

    function auragold_perf_wrap_mysqli_query($link, string $sql)
    {
        $t0 = microtime(true);
        $res = mysqli_query($link, $sql);
        auragold_perf_log_query($sql, (microtime(true) - $t0) * 1000, 'mysqli_query');
        return $res;
    }

    function auragold_perf_set_conn_ms(float $ms): void
    {
        if (!auragold_perf_enabled()) {
            return;
        }
        $GLOBALS['auragold_perf']['conn_ms'] = round($ms, 2);
    }

    function auragold_perf_render_report(): void
    {
        if (!auragold_perf_enabled() || !defined('AURAGOLD_PERF_START')) {
            return;
        }
        $totalMs = round((microtime(true) - AURAGOLD_PERF_START) * 1000, 2);
        $perf    = $GLOBALS['auragold_perf'] ?? ['queries' => [], 'includes' => [], 'conn_ms' => null, 'slow_ms' => 300];
        $queries = $perf['queries'] ?? [];
        $slowMs  = (float) ($perf['slow_ms'] ?? 300);
        $slow    = array_values(array_filter($queries, static function ($q) use ($slowMs) {
            return ($q['ms'] ?? 0) >= $slowMs;
        }));
        $sqlTotal = 0.0;
        foreach ($queries as $q) {
            $sqlTotal += (float) ($q['ms'] ?? 0);
        }

        $isHtml = PHP_SAPI !== 'cli'
            && empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && (stripos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') === false);

        if (!$isHtml) {
            fwrite(STDERR, "AURAGOLD PERF: {$totalMs}ms queries=" . count($queries) . " sql_ms=" . round($sqlTotal, 2) . "\n");
            return;
        }

        $esc = static function ($s) {
            return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        };
        echo "\n<!-- auragold perf -->\n";
        echo '<div id="auragold-perf-panel" style="position:fixed;bottom:0;left:0;right:0;max-height:45vh;overflow:auto;z-index:99999;background:#0f172a;color:#e2e8f0;font:12px/1.45 Consolas,monospace;padding:10px 14px;border-top:3px solid #f59e0b;">';
        echo '<strong style="color:#fbbf24;">GoldMatrix perf</strong> — total <b>' . $esc($totalMs) . ' ms</b>';
        if ($perf['conn_ms'] !== null) {
            echo ' · DB connect <b>' . $esc($perf['conn_ms']) . ' ms</b>';
        }
        echo ' · queries <b>' . count($queries) . '</b> · SQL time <b>' . round($sqlTotal, 2) . ' ms</b>';
        echo ' · memory <b>' . $esc(round(memory_get_peak_usage(true) / 1048576, 1)) . ' MB</b>';
        echo ' · includes <b>' . count($perf['includes'] ?? []) . '</b>';
        if ($slow !== []) {
            echo '<div style="margin-top:8px;color:#fca5a5;"><b>Slow queries (&ge;' . $esc($slowMs) . ' ms):</b></div><ol style="margin:4px 0 0 18px;">';
            foreach ($slow as $q) {
                echo '<li><span style="color:#f87171;">' . $esc($q['ms']) . ' ms</span> — ' . $esc(mb_substr($q['sql'], 0, 220)) . '</li>';
            }
            echo '</ol>';
        }
        if ($queries !== []) {
            echo '<details style="margin-top:8px;"><summary>All SQL (' . count($queries) . ')</summary><ol style="margin:4px 0 0 18px;">';
            foreach ($queries as $q) {
                echo '<li>' . $esc($q['ms']) . ' ms — ' . $esc(mb_substr($q['sql'], 0, 280)) . '</li>';
            }
            echo '</ol></details>';
        }
        echo '</div>';
    }
}
