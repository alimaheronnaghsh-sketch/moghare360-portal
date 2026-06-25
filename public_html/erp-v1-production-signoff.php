<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-v1-post-run-control-helper.php';

$connection = false;
$errorMessage = '';
$signoffRecord = null;
$liveRows = v1ctrl_live_signoff_rows();
$ownerSigned = false;

try {
    $connection = v1ctrl_db();
    if ($connection !== false) {
        erp_auth_context_start();
        $uid = erp_auth_current_user_id() ?? 0;

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['owner_signoff_confirm'])) {
            v1ctrl_require('erp-csrf.php');
            $token = (string)($_POST['csrf_token'] ?? '');
            if (!erp_csrf_validate_token('v1_owner_signoff', $token)) {
                throw new RuntimeException('توکن CSRF نامعتبر است.');
            }
            $note = trim((string)($_POST['signoff_note'] ?? 'Owner formal signoff after V1 production run.'));
            if (v1ctrl_owner_signoff_action($connection, (int)$uid, $note)) {
                $ownerSigned = true;
            }
        }

        $signoffRecord = v1ctrl_fetch_signoff_record($connection);
    }
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

v1ctrl_render_head('MOGHARE360 V1 — Production Run Signoff');
echo '<div class="v1sig-banner">MOGHARE360 V1 SaaS-enabled Production Release — Post-Run Signoff Control</div>';
echo '<div class="v1sig-hero"><h1>Production Run Signoff</h1>';
echo '<p>ثبت وضعیت نهایی V1 پس از اجرای واقعی — بدون بازگشت به چرخه ساخت بی‌پایان</p></div>';

if ($errorMessage !== '') {
    echo '<div class="v1sig-card" style="border-color:#fca5a5"><strong>خطا:</strong> ' . v1ctrl_h($errorMessage) . '</div>';
}
if ($ownerSigned) {
    echo '<div class="v1sig-card" style="border-color:#86efac"><strong>ثبت شد:</strong> Owner signoff ذخیره شد.</div>';
}

echo '<div class="v1sig-card"><h2>وضعیت زنده Production Stack</h2>';
echo '<table class="v1sig-table"><thead><tr><th>حوزه</th><th>وضعیت</th><th>جزئیات</th></tr></thead><tbody>';
foreach ($liveRows as $row) {
    echo '<tr><td>' . v1ctrl_h($row['label']) . '</td>';
    echo '<td><span class="v1sig-badge ' . v1ctrl_status_badge($row['status']) . '">' . v1ctrl_h($row['status']) . '</span></td>';
    echo '<td>' . v1ctrl_h($row['detail']) . '</td></tr>';
}
echo '</tbody></table></div>';

if (is_array($signoffRecord)) {
    $ownerStatus = (string)($signoffRecord['owner_signoff_status'] ?? 'PENDING');
    echo '<div class="v1sig-card"><h2>رکورد Signoff در پایگاه داده</h2>';
    echo '<p>نسخه: <strong>' . v1ctrl_h((string)($signoffRecord['signoff_version'] ?? 'V1')) . '</strong></p>';
    echo '<p>Owner Signoff: <span class="v1sig-badge ' . v1ctrl_status_badge($ownerStatus) . '">' . v1ctrl_h($ownerStatus) . '</span>';
    if (!empty($signoffRecord['owner_signoff_at'])) {
        echo ' — ' . v1ctrl_h((string)$signoffRecord['owner_signoff_at']);
    }
    echo '</p>';
    if (!empty($signoffRecord['signoff_note'])) {
        echo '<p>' . v1ctrl_h((string)$signoffRecord['signoff_note']) . '</p>';
    }
    echo '</div>';
}

if (($signoffRecord['owner_signoff_status'] ?? 'PENDING') !== 'SIGNED_OFF') {
    v1ctrl_require('erp-csrf.php');
    $csrf = erp_csrf_create_token('v1_owner_signoff');
    echo '<div class="v1sig-card"><h2>تأیید نهایی مالک (Owner Signoff)</h2>';
    echo '<form method="post"><input type="hidden" name="csrf_token" value="' . v1ctrl_h($csrf) . '">';
    echo '<p><label>یادداشت signoff<br><textarea name="signoff_note" rows="3" style="width:100%">V1 production run reviewed — post-run fix register active.</textarea></label></p>';
    echo '<p><button type="submit" name="owner_signoff_confirm" value="1">ثبت Owner Signoff</button></p>';
    echo '<p style="font-size:.85rem;color:#64748b">فقط platform owner (ID 10001) — بدون ایجاد ماژول جدید</p></form></div>';
}

echo '<div class="v1sig-card"><h2>مسیر بعد از Run</h2>';
echo '<ul>';
echo '<li><a href="erp-v1-fix-register.php">Fix / Development Register</a> — BUG / FIX / UI / TRAINING / DATA / SECURITY / V2_BACKLOG</li>';
echo '<li>مستندات: <code>docs/release/MOGHARE360_V1_POST_RUN_FIX_REGISTER.md</code></li>';
echo '<li>مستندات: <code>docs/release/MOGHARE360_V1_OPERATIONAL_ACCEPTANCE.md</code></li>';
echo '</ul></div>';

v1ctrl_render_foot();
