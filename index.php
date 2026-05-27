<?php
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/includes/auragold_require_login.php';

// Redirect if already logged in — keep active session, do not show login again
if (auragold_is_logged_in_session()) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/config.php';

$login_error = '';
if (isset($_GET['login_error']) && is_string($_GET['login_error'])) {
    $login_error = trim($_GET['login_error']);
}
$branch_entry_id = isset($_GET['branch_entry']) ? (int) $_GET['branch_entry'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars(auragold_app_name() . ' – Login', ENT_QUOTES, 'UTF-8'); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,600;0,700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="icon" type="image/jpeg" href="favicon.jpeg">
<style>
:root {
    --gold-orange: #d97706;
    --gold-orange-light: #f59e0b;
    --navy: #0d2a44;
    --navy-light: #1a3d5c;
    --white: #ffffff;
    --beige-left: #faf6f0;
    --beige-right: #f5f0e8;
    --input-border: #e2e8f0;
    --text-dark: #1e293b;
    --text-muted: #64748b;
}

* { box-sizing: border-box; }

html, body {
    margin: 0;
    padding: 0;
    height: 100%;
    min-height: 100vh;
    width: 100%;
    overflow: hidden;
}
/* Mobile: allow vertical scroll so full form (password, Next) stays reachable */
@media (max-width: 900px) {
    html, body {
        height: auto;
        min-height: 100%;
        min-height: 100dvh;
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
}

body {
    font-family: Roboto, 'Segoe UI', sans-serif;
    font-size: 0.875rem; /* 14px — same UI scale as main app (JewelStep reference) */
    font-weight: 400;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Full-width split – fit in viewport, no scroll */
.login-card-wrap {
    width: 100%;
    height: 100vh;
    max-height: 100vh;
    display: flex;
    flex-direction: row;
    overflow: hidden;
}

/* Left: 70% – light beige + logo + image */
.login-left {
    flex: 0 0 70%;
    min-width: 0;
    height: 100%;
    max-height: 100vh;
    background: var(--beige-left);
    /* padding: 24px 32px 32px; */
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.login-left .logo-top-left {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    flex-shrink: 0;
}

.login-left .logo-top-left .logo-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(145deg, var(--navy) 0%, var(--navy-light) 100%);
    color: var(--gold-orange-light);
    font-size: 1.1rem;
    font-weight: 700;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.login-left .logo-top-left .brand-text .brand-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-dark);
    letter-spacing: 0.02em;
}

.login-left .logo-top-left .brand-text .brand-name .gold { color: var(--gold-orange); }
.login-left .logo-top-left .brand-text .brand-name .navy { color: var(--navy); }

.login-left .logo-top-left .brand-text .tagline {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 2px;
}

.login-left .promo-image-wrap {
    flex: 1;
    min-height: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.login-left .promo-image {
    width: 100%;
    max-width: 100%;
    height: 100%;
    max-height: 100%;
    object-fit: contain;
    object-position: center;
    display: block;
}

/* Right: 30% – form, fit in viewport */
.login-right {
    flex: 0 0 30%;
    min-width: 0;
    height: 100%;
    max-height: 100vh;
    background: #fff;
    padding: 24px 32px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    overflow: hidden;
}


.login-right .logo-top {
    text-align: center;
    margin-bottom: 16px;
}
.login-right .login-header-logo {
    display: block;
    max-width: 100%;
    width: 300px;
    height: auto;
    margin: 0 auto;
}

.login-right .logo-top .logo-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 8px;
    background: linear-gradient(145deg, var(--navy) 0%, var(--navy-light) 100%);
    color: var(--gold-orange-light);
    font-size: 1.25rem;
    font-weight: 700;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(13, 42, 68, 0.25);
}

.login-right .logo-top .brand-name {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text-dark);
    letter-spacing: 0.02em;
}

.login-right .logo-top .brand-name .gold { color: var(--gold-orange); }
.login-right .logo-top .brand-name .navy { color: var(--navy); }

.login-right .logo-top .tagline {
    font-size: 0.7rem;
    color: var(--text-muted);
    margin-top: 1px;
}

.login-right .login-title {
    text-align: center;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 18px;
}

.login-right .form-label {
    font-weight: 500;
    color: var(--text-dark);
    font-size: 0.85rem;
    margin-bottom: 6px;
}

.login-right .form-control {
    height: 44px;
    border-radius: 10px;
    border: 1px solid var(--input-border);
    background: var(--white);
    padding: 0 16px 0 44px;
    font-size: 12px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.login-right .form-control:focus {
    border-color: var(--gold-orange-light);
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    outline: none;
    background: var(--white);
}

.login-right .form-control::placeholder {
    color: var(--text-muted);
}

.login-right .input-wrap {
    position: relative;
}

.login-right .input-wrap .input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 1.1rem;
    pointer-events: none;
    display: flex;
    align-items: center;
}

.login-right .input-wrap .input-icon svg {
    flex-shrink: 0;
}

.login-right .form-check-label {
    font-size: 0.9rem;
    color: var(--text-dark);
}

.btn-login {
    height: 44px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 12px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    background: linear-gradient(145deg, var(--gold-orange-light) 0%, var(--gold-orange) 100%);
    border: none;
    color: var(--white);
    box-shadow: 0 4px 14px rgba(217, 119, 6, 0.4);
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.btn-login:hover {
    background: linear-gradient(145deg, #fbbf24 0%, #d97706 100%);
    color: var(--white);
    box-shadow: 0 6px 20px rgba(217, 119, 6, 0.5);
    transform: translateY(-1px);
}

.btn-login:active {
    transform: translateY(0);
}

.login-right .mb-3 { margin-bottom: 12px !important; }
.login-right .mb-3.form-check { margin-bottom: 14px !important; }

.login-branch-select {
    width: 100%;
    padding: 10px 12px 10px 40px;
    border: 1px solid var(--input-border);
    border-radius: 10px;
    font-size: 0.9rem;
    color: var(--text-dark);
    background-color: #fff;
    appearance: auto;
}
.login-branch-wrap {
    position: relative;
}
.login-branch-wrap .input-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    pointer-events: none;
    z-index: 1;
}
.login-branch-wrap .login-branch-select {
    padding-left: 40px;
}

#message .alert {
    border-radius: 10px;
    border: none;
    margin-top: 10px;
    font-size: 0.85rem;
}

.login-step--hidden {
    display: none !important;
}
.login-back-link {
    display: inline-block;
    font-size: 0.8rem;
    color: var(--navy);
    text-decoration: none;
    margin-bottom: 10px;
    font-weight: 500;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
}
.login-back-link:hover { text-decoration: underline; }
#loginNextBtn:disabled { opacity: 0.5; cursor: not-allowed; }

/* Responsive: stack on small screens — form first, compact hero; scrollable */
@media (max-width: 900px) {
    .login-card-wrap {
        flex-direction: column;
        min-height: 0;
        height: auto;
        max-height: none;
        overflow: visible;
    }

    /* Large illustration (desk / promo) — hidden on small screens; logo stays in .login-right */
    .login-left {
        display: none !important;
    }

    .login-right {
        flex: 0 0 auto !important;
        width: 100%;
        min-width: 0;
        min-height: 0;
        max-height: none !important;
        height: auto;
        padding: 20px 18px 24px;
        padding-left: max(18px, env(safe-area-inset-left, 0px));
        padding-right: max(18px, env(safe-area-inset-right, 0px));
        padding-bottom: max(24px, env(safe-area-inset-bottom, 0px));
        order: 0;
        overflow: visible;
        justify-content: flex-start;
    }

    .login-right .logo-top {
        margin-bottom: 12px;
    }

    .login-right .login-header-logo {
        max-width: min(240px, 88vw);
        width: 100%;
    }

    .login-right .login-title {
        font-size: 1rem;
        margin-bottom: 14px;
    }
}

@media (max-width: 480px) {
    .login-right {
        padding: 16px 14px 20px;
    }
}
</style>

</head>
<body>

<div class="login-card-wrap">
    <!-- Left: 50% – logo + image -->
    <div class="login-left">
        
        <div class="promo-image-wrap">
            <img src="login_image1.jpg" alt="" class="promo-image">
        </div>
    </div>

    <!-- Right: 50% – login form -->
    <div class="login-right">
        <div class="logo-top">  
        <img src="logo_login.jpeg" alt="GoldMatrix" class="login-header-logo" width="300" height="90" decoding="async">
        </div>

        <h2 class="login-title">Branch Login</h2>
        <!-- <p class="text-center text-muted small mb-3" style="font-size:0.8rem;margin-top:-8px;">Use your branch login, or a legacy app user if you still have one.</p> -->

        <?php if ($login_error !== ''): ?>
            <div class="alert alert-danger" role="alert" style="font-size:0.85rem;"><?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>

        <form id="loginForm" method="post" action="login_submit.php" autocomplete="on">
            <input type="hidden" name="login_db_name" id="login_db_name" value="">

            <div id="loginStep1" class="login-step">
                <div class="mb-3">
                    <label class="form-label" for="login_target_url">IP address</label>
                    <div class="input-wrap">
                        <span class="input-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></span>
                        <input type="text" name="login_target_url" id="login_target_url" class="form-control" placeholder="e.g. https://main.goldmatrixsoftware.com/" autocomplete="url" inputmode="url" maxlength="500" autocapitalize="none" required aria-required="true">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-wrap">
                        <span class="input-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Username" required autocomplete="username">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <span class="input-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password" required autocomplete="current-password">
                    </div>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <div id="login_step1_err" class="alert alert-danger py-2 px-3 mb-3" style="display:none;font-size:0.85rem;" role="alert"></div>
                <button type="button" class="btn btn-login w-100" id="loginNextBtn">Next</button>
            </div>

            <div id="loginStep2" class="login-step login-step--hidden" aria-hidden="true" style="display:none">
                <button type="button" class="login-back-link" id="loginBackToStep1">← Back to connection</button>
                <div id="login_verify_err" class="alert alert-danger py-2 px-3 mb-3" style="display:none;font-size:0.85rem;" role="alert"></div>
                <div class="mb-3">
                    <label class="form-label" for="login_branch_id">Branch</label>
                    <div class="login-branch-wrap">
                        <span class="input-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></span>
                        <select id="login_branch_id" class="login-branch-select" disabled>
                            <option value="">—</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3" id="login_fy_wrap" style="display:none;">
                    <label class="form-label" for="financial_year_id">Financial Year</label>
                    <div class="login-branch-wrap">
                        <span class="input-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                        <select id="financial_year_id" class="login-branch-select" data-fy-select="1" disabled>
                            <option value="">—</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-login w-100" type="submit" id="login_submit_btn" disabled>Login</button>
            </div>
        </form>

    </div>
</div>

<script>
window.AURAGOLD_DEFAULT_DB_NAME = <?php echo json_encode(defined('DB_NAME') ? (string) DB_NAME : '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.AURAGOLD_ENTRY_BRANCH_ID = <?php echo (int) $branch_entry_id; ?>;
</script>
<script>
(function () {
    var credentialsVerified = false;
    var verifySeq = 0;

    function setStep1Error(msg) {
        var el = document.getElementById('login_step1_err');
        if (!el) return;
        if (msg) {
            el.textContent = msg;
            el.style.display = 'block';
        } else {
            el.textContent = '';
            el.style.display = 'none';
        }
    }
    function setVerifyError(msg) {
        var el = document.getElementById('login_verify_err');
        if (!el) return;
        if (msg) {
            el.textContent = msg;
            el.style.display = 'block';
        } else {
            el.textContent = '';
            el.style.display = 'none';
        }
    }
    function showStep1() {
        var a = document.getElementById('loginStep1');
        var b = document.getElementById('loginStep2');
        if (a) {
            a.classList.remove('login-step--hidden');
            a.style.display = '';
        }
        if (b) {
            b.classList.add('login-step--hidden');
            b.style.display = 'none';
            b.setAttribute('aria-hidden', 'true');
        }
        var nb = document.getElementById('loginNextBtn');
        if (nb) nb.disabled = false;
    }
    function showStep2() {
        var a = document.getElementById('loginStep1');
        var b = document.getElementById('loginStep2');
        if (a) {
            a.classList.add('login-step--hidden');
            a.style.display = 'none';
        }
        if (b) {
            b.classList.remove('login-step--hidden');
            b.style.display = '';
            b.setAttribute('aria-hidden', 'false');
        }
    }

    function resetBranchState() {
        credentialsVerified = false;
        var form = document.getElementById('loginForm');
        if (form) {
            var sh = form.querySelector('input[name="login_branch_id"][data-superadmin-hidden="1"]');
            if (sh) {
                sh.remove();
            }
            var sbd = form.querySelector('input[name="login_branch_id"][data-superbranch-hidden="1"]');
            if (sbd) {
                sbd.remove();
            }
        }
        var s = document.getElementById('login_branch_id');
        var btn = document.getElementById('login_submit_btn');
        if (s) {
            s.innerHTML = '<option value="">—</option>';
            s.disabled = true;
            s.removeAttribute('name');
            s.removeAttribute('required');
        }
        if (btn) btn.disabled = true;
        var hidDb = document.getElementById('login_db_name');
        if (hidDb) {
            hidDb.value = '';
        }
        var fy = document.getElementById('financial_year_id');
        var wrap = document.getElementById('login_fy_wrap');
        if (fy) {
            fy.innerHTML = '<option value="">—</option>';
            fy.disabled = true;
            fy.removeAttribute('name');
            fy.removeAttribute('required');
        }
        if (wrap) wrap.style.display = 'none';
    }

    function applySuperadminDirectLogin() {
        var form = document.getElementById('loginForm');
        var s = document.getElementById('login_branch_id');
        var wrap = document.getElementById('login_fy_wrap');
        var fy = document.getElementById('financial_year_id');
        var btn = document.getElementById('login_submit_btn');
        resetBranchState();
        if (form) {
            var h = document.createElement('input');
            h.type = 'hidden';
            h.name = 'login_branch_id';
            h.value = '0';
            h.setAttribute('data-superadmin-hidden', '1');
            form.appendChild(h);
        }
        if (s) {
            s.innerHTML = '<option value="0">Superadmin — default main (no selection)</option>';
            s.disabled = true;
            s.removeAttribute('name');
            s.removeAttribute('required');
        }
        if (wrap) {
            wrap.style.display = 'none';
        }
        if (fy) {
            fy.innerHTML = '<option value="">—</option>';
            fy.disabled = true;
            fy.removeAttribute('name');
            fy.removeAttribute('required');
        }
        credentialsVerified = true;
        setVerifyError('');
        var hidDbSa = document.getElementById('login_db_name');
        if (hidDbSa && typeof window.AURAGOLD_DEFAULT_DB_NAME === 'string') {
            hidDbSa.value = window.AURAGOLD_DEFAULT_DB_NAME;
        }
        if (btn) {
            btn.disabled = false;
        }
    }

    function applySuperbranchDirectLogin(loginDbName) {
        var form = document.getElementById('loginForm');
        resetBranchState();
        if (form) {
            var h = document.createElement('input');
            h.type = 'hidden';
            h.name = 'login_branch_id';
            h.value = '0';
            h.setAttribute('data-superbranch-hidden', '1');
            form.appendChild(h);
        }
        var hidDb = document.getElementById('login_db_name');
        if (hidDb) {
            hidDb.value = loginDbName && typeof loginDbName === 'string' ? loginDbName : (typeof window.AURAGOLD_DEFAULT_DB_NAME === 'string' ? window.AURAGOLD_DEFAULT_DB_NAME : '');
        }
        setStep1Error('');
        setVerifyError('');
        if (form) {
            form.submit();
        }
    }

    function syncLoginDbHiddenFromBranchSelect() {
        var s = document.getElementById('login_branch_id');
        var hidDb = document.getElementById('login_db_name');
        if (!s || !hidDb) {
            return;
        }
        var opt = s.options[s.selectedIndex];
        if (!opt || opt.value === '') {
            hidDb.value = '';
            return;
        }
        var dn = opt.getAttribute('data-db-name');
        hidDb.value = dn !== null ? String(dn) : '';
    }

    function updateLoginButtonEnabled() {
        var s = document.getElementById('login_branch_id');
        var btn = document.getElementById('login_submit_btn');
        if (!s || !btn) return;
        if (!credentialsVerified) {
            btn.disabled = true;
            return;
        }
        var v = s.value;
        btn.disabled = (v === '' || v === null);
    }

    function pad2(n) { return n < 10 ? '0' + n : String(n); }
    function isoToDisplay(iso) {
        if (!iso || typeof iso !== 'string') return '';
        var p = iso.split('-');
        if (p.length !== 3) return iso;
        return pad2(parseInt(p[2], 10)) + '-' + pad2(parseInt(p[1], 10)) + '-' + p[0];
    }
    function fyOptionLabel(y) {
        var a = isoToDisplay(y.start_date || '');
        var b = isoToDisplay(y.end_date || '');
        if (a && b) return a + ' — ' + b;
        return 'FY #' + y.id;
    }
    function loadFinancialYears() {
        var branchEl = document.getElementById('login_branch_id');
        var wrap = document.getElementById('login_fy_wrap');
        var sel = document.getElementById('financial_year_id');
        if (!branchEl || !wrap || !sel) return;
        var bid = branchEl.value;
        if (bid === '' || bid === null) {
            wrap.style.display = 'none';
            return;
        }
        sel.innerHTML = '<option value="">Loading…</option>';
        sel.disabled = true;
        sel.removeAttribute('name');
        sel.removeAttribute('required');
        wrap.style.display = 'block';
        fetch('ajax/login_financial_years.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'login_branch_id=' + encodeURIComponent(bid),
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var years = (data && data.years) ? data.years : [];
                years = years.filter(function (y) { return y && y.is_active; });
                sel.innerHTML = '';
                if (years.length === 0) {
                    wrap.style.display = 'none';
                    sel.removeAttribute('name');
                    sel.removeAttribute('required');
                    sel.disabled = true;
                    return;
                }
                wrap.style.display = 'block';
                sel.disabled = false;
                sel.setAttribute('name', 'financial_year_id');
                sel.setAttribute('required', 'required');
                var pick = 0;
                for (var i = 0; i < years.length; i++) {
                    if (years[i].is_active) {
                        pick = i;
                        break;
                    }
                }
                years.forEach(function (y, idx) {
                    var opt = document.createElement('option');
                    opt.value = String(y.id);
                    opt.textContent = fyOptionLabel(y);
                    if (idx === pick) opt.selected = true;
                    sel.appendChild(opt);
                });
            })
            .catch(function () {
                sel.innerHTML = '';
                var o = document.createElement('option');
                o.value = '';
                o.textContent = 'Could not load years';
                sel.appendChild(o);
                sel.disabled = true;
                sel.removeAttribute('name');
                sel.removeAttribute('required');
                wrap.style.display = 'block';
            });
    }

    function formatBranchOptionLabel(b) {
        var label = String(b.label || '').trim();
        var dbn = (b.db_name !== undefined && b.db_name !== null) ? String(b.db_name).trim() : '';
        if (dbn === '') {
            return label;
        }
        return label + ' (' + dbn + ')';
    }

    function applyVerifiedBranches(branches) {
        var s = document.getElementById('login_branch_id');
        if (!s || !Array.isArray(branches)) return;
        s.innerHTML = '';
        branches.forEach(function (b) {
            var opt = document.createElement('option');
            opt.value = String(b.id);
            opt.textContent = formatBranchOptionLabel(b);
            if (b.db_name !== undefined && b.db_name !== null) {
                opt.setAttribute('data-db-name', String(b.db_name));
            }
            s.appendChild(opt);
        });
        s.disabled = false;
        s.setAttribute('name', 'login_branch_id');
        s.setAttribute('required', 'required');
        credentialsVerified = true;

        function selectEntryBranchIfAny() {
            var want = typeof window.AURAGOLD_ENTRY_BRANCH_ID === 'number' ? window.AURAGOLD_ENTRY_BRANCH_ID : 0;
            if (want <= 0 || !s) {
                return;
            }
            for (var i = 0; i < s.options.length; i++) {
                if (parseInt(s.options[i].value, 10) === want) {
                    s.selectedIndex = i;
                    syncLoginDbHiddenFromBranchSelect();
                    return;
                }
            }
        }

        if (branches.length === 1) {
            s.selectedIndex = 0;
            selectEntryBranchIfAny();
            syncLoginDbHiddenFromBranchSelect();
            loadFinancialYears();
            updateLoginButtonEnabled();
            showStep2();
            return;
        }

        var ph = document.createElement('option');
        ph.value = '';
        ph.selected = true;
        ph.textContent = '— Select branch —';
        s.insertBefore(ph, s.firstChild);
        selectEntryBranchIfAny();
        var wantBr = typeof window.AURAGOLD_ENTRY_BRANCH_ID === 'number' ? window.AURAGOLD_ENTRY_BRANCH_ID : 0;
        if (s.selectedIndex < 0 || (s.options[s.selectedIndex] && s.options[s.selectedIndex].value === '')) {
            if (wantBr <= 0) {
                s.selectedIndex = 0;
            }
        }
        syncLoginDbHiddenFromBranchSelect();
        updateLoginButtonEnabled();
        if (s.options[s.selectedIndex] && String(s.options[s.selectedIndex].value || '') !== '') {
            loadFinancialYears();
        }
        showStep2();
    }

    function runVerify() {
        setStep1Error('');
        setVerifyError('');
        var uEl = document.getElementById('username');
        var pEl = document.getElementById('password');
        var urlEl = document.getElementById('login_target_url');
        var turl = urlEl ? String(urlEl.value || '').trim() : '';
        if (turl === '') {
            setStep1Error('IP address / server URL is required.');
            return;
        }
        var u = uEl ? String(uEl.value || '').trim() : '';
        var p = pEl ? String(pEl.value || '') : '';
        if (u === '' || p === '') {
            setStep1Error('Enter username and password.');
            return;
        }
        var nextBtn = document.getElementById('loginNextBtn');
        if (nextBtn) {
            nextBtn.disabled = true;
        }
        var reqId = ++verifySeq;
        var be = typeof window.AURAGOLD_ENTRY_BRANCH_ID === 'number' ? window.AURAGOLD_ENTRY_BRANCH_ID : 0;
        var body =
            'username=' + encodeURIComponent(u) +
            '&password=' + encodeURIComponent(p) +
            '&login_target_url=' + encodeURIComponent(turl);
        if (be > 0) {
            body += '&branch_entry=' + encodeURIComponent(String(be));
        }
        fetch('ajax/login_verify_credentials.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body,
            credentials: 'same-origin',
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (nextBtn) {
                    nextBtn.disabled = false;
                }
                if (reqId !== verifySeq) {
                    return;
                }
                if (data && data.success && data.superbranch_direct) {
                    var dbn = data.login_db_name ? String(data.login_db_name) : '';
                    applySuperbranchDirectLogin(dbn);
                    return;
                }
                if (data && data.success && data.is_superadmin) {
                    if (Array.isArray(data.branches) && data.branches.length > 0) {
                        resetBranchState();
                        setVerifyError('');
                        applyVerifiedBranches(data.branches);
                        return;
                    }
                    applySuperadminDirectLogin();
                    showStep2();
                    return;
                }
                if (data && data.success && Array.isArray(data.branches) && data.branches.length > 0) {
                    resetBranchState();
                    setVerifyError('');
                    applyVerifiedBranches(data.branches);
                    return;
                }
                credentialsVerified = false;
                resetBranchState();
                setStep1Error((data && data.message) ? data.message : 'Could not verify. Try again.');
            })
            .catch(function () {
                if (nextBtn) {
                    nextBtn.disabled = false;
                }
                if (reqId !== verifySeq) {
                    return;
                }
                credentialsVerified = false;
                resetBranchState();
                setStep1Error('Network error. Try again.');
            });
    }

    var br = document.getElementById('login_branch_id');
    var un = document.getElementById('username');
    var pw = document.getElementById('password');
    var form = document.getElementById('loginForm');
    var nextBtn = document.getElementById('loginNextBtn');
    var backBtn = document.getElementById('loginBackToStep1');

    function onCredentialInput() {
        setStep1Error('');
    }
    var urlIn = document.getElementById('login_target_url');
    if (urlIn) {
        urlIn.addEventListener('input', onCredentialInput);
    }
    if (un) {
        un.addEventListener('input', onCredentialInput);
    }
    if (pw) {
        pw.addEventListener('input', onCredentialInput);
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            runVerify();
        });
    }
    if (backBtn) {
        backBtn.addEventListener('click', function () {
            verifySeq++;
            resetBranchState();
            setVerifyError('');
            setStep1Error('');
            showStep1();
        });
    }
    if (br) {
        br.addEventListener('change', function () {
            syncLoginDbHiddenFromBranchSelect();
            loadFinancialYears();
            updateLoginButtonEnabled();
        });
    }
    if (form) {
        form.addEventListener('submit', function (e) {
            var st2 = document.getElementById('loginStep2');
            if (st2 && (st2.classList.contains('login-step--hidden') || st2.style.display === 'none')) {
                e.preventDefault();
                setStep1Error('Use Next to sign in, then select branch and year.');
                return;
            }
            if (!credentialsVerified) {
                e.preventDefault();
                setVerifyError('Enter a valid username and password on the first step, then select a branch.');
                return;
            }
            var formEl = document.getElementById('loginForm');
            var superHidden =
                formEl && formEl.querySelector('input[name="login_branch_id"][data-superadmin-hidden="1"]');
            var sbranchHidden =
                formEl && formEl.querySelector('input[name="login_branch_id"][data-superbranch-hidden="1"]');
            if (superHidden || sbranchHidden) {
                return;
            }
            var s = document.getElementById('login_branch_id');
            if (!s || s.value === '') {
                e.preventDefault();
                setVerifyError('Select a branch.');
            }
        });
    }

})();
</script>

</body>
</html>
