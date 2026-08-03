<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-contract-engine.php';

p360hr_require_password_changed_for_cartable();
if (!p360hr_can_manage_personnel()) {
    http_response_code(403);
    echo 'دسترسی مجاز نیست.';
    exit;
}

$msg = null;
$ok = false;
$row = p360hr_employer_active();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = (string)($_POST['erp_csrf_token'] ?? '');
    if (!erp_csrf_validate_token('p360hr_employer', $token)) {
        $msg = 'توکن امنیتی نامعتبر است.';
    } else {
        $fields = [
            'trade_name' => trim((string)($_POST['trade_name'] ?? '')),
            'legal_name' => trim((string)($_POST['legal_name'] ?? '')),
            'employer_name' => trim((string)($_POST['employer_name'] ?? '')),
            'representative_name' => trim((string)($_POST['representative_name'] ?? '')),
            'representative_title' => trim((string)($_POST['representative_title'] ?? '')),
            'national_or_reg_id' => trim((string)($_POST['national_or_reg_id'] ?? '')),
            'registration_no' => trim((string)($_POST['registration_no'] ?? '')),
            'address_full' => trim((string)($_POST['address_full'] ?? '')),
            'postal_code' => trim((string)($_POST['postal_code'] ?? '')),
            'phone' => trim((string)($_POST['phone'] ?? '')),
            'default_workplace' => trim((string)($_POST['default_workplace'] ?? '')),
        ];
        $complete = (
            $fields['trade_name'] !== '' &&
            $fields['representative_name'] !== '' &&
            $fields['representative_title'] !== '' &&
            $fields['address_full'] !== '' &&
            $fields['default_workplace'] !== ''
        ) ? 1 : 0;
        if ($row === null) {
            $ok = p360hr_exec(
                'INSERT INTO dbo.p360_hr_employer_profile
                    (trade_name, legal_name, employer_name, representative_name, representative_title, national_or_reg_id, registration_no, address_full, postal_code, phone, default_workplace, profile_complete, is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1)',
                [
                    $fields['trade_name'], $fields['legal_name'] ?: null, $fields['employer_name'] ?: null,
                    $fields['representative_name'], $fields['representative_title'], $fields['national_or_reg_id'] ?: null,
                    $fields['registration_no'] ?: null, $fields['address_full'] ?: null, $fields['postal_code'] ?: null,
                    $fields['phone'] ?: null, $fields['default_workplace'] ?: null, $complete,
                ]
            );
        } else {
            $ok = p360hr_exec(
                'UPDATE dbo.p360_hr_employer_profile SET
                    trade_name=?, legal_name=?, employer_name=?, representative_name=?, representative_title=?,
                    national_or_reg_id=?, registration_no=?, address_full=?, postal_code=?, phone=?, default_workplace=?,
                    profile_complete=?, updated_at=SYSUTCDATETIME()
                 WHERE employer_profile_id=?',
                [
                    $fields['trade_name'], $fields['legal_name'] ?: null, $fields['employer_name'] ?: null,
                    $fields['representative_name'], $fields['representative_title'], $fields['national_or_reg_id'] ?: null,
                    $fields['registration_no'] ?: null, $fields['address_full'] ?: null, $fields['postal_code'] ?: null,
                    $fields['phone'] ?: null, $fields['default_workplace'] ?: null, $complete, (int)$row['employer_profile_id'],
                ]
            );
        }
        $msg = $ok ? 'پروفایل کارفرما ذخیره شد.' : 'ذخیره ناموفق بود.';
        $row = p360hr_employer_active();
    }
}

p360hr_layout_start('پروفایل کارفرما');
if ($msg !== null) {
    echo '<div class="m360-alert ' . ($ok ? 'm360-alert-ok' : 'm360-alert-err') . '">' . p360hr_h($msg) . '</div>';
}
$gaps = p360hr_employer_mandatory_gaps($row);
if ($gaps !== []) {
    echo '<div class="m360-alert m360-alert-err">نقص اطلاعات الزامی (نهایی‌سازی قرارداد مسدود می‌شود): ' . p360hr_h(implode('، ', $gaps)) . '</div>';
}
$csrf = erp_csrf_create_token('p360hr_employer');
$v = static function (?array $r, string $k): string {
    return p360hr_h((string)($r[$k] ?? ''));
};
echo '<form method="post" class="m360-card"><input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '">';
echo '<div class="p360hr-meta">';
echo '<label>نام تجاری<br><input name="trade_name" required value="' . $v($row, 'trade_name') . '"></label>';
echo '<label>نام حقوقی<br><input name="legal_name" value="' . $v($row, 'legal_name') . '"></label>';
echo '<label>نام کارفرما<br><input name="employer_name" value="' . $v($row, 'employer_name') . '"></label>';
echo '<label>نماینده کارفرما<br><input name="representative_name" required value="' . $v($row, 'representative_name') . '"></label>';
echo '<label>سمت نماینده<br><input name="representative_title" required value="' . $v($row, 'representative_title') . '"></label>';
echo '<label>کد/شناسه ملی<br><input name="national_or_reg_id" value="' . $v($row, 'national_or_reg_id') . '"></label>';
echo '<label>شماره ثبت<br><input name="registration_no" value="' . $v($row, 'registration_no') . '"></label>';
echo '<label>کد پستی<br><input name="postal_code" value="' . $v($row, 'postal_code') . '"></label>';
echo '<label>تلفن<br><input name="phone" value="' . $v($row, 'phone') . '"></label>';
echo '<label>محل انجام کار پیش‌فرض<br><input name="default_workplace" required value="' . $v($row, 'default_workplace') . '"></label>';
echo '</div>';
echo '<label>نشانی<br><textarea name="address_full" rows="3" style="width:100%">' . $v($row, 'address_full') . '</textarea></label>';
echo '<p>وضعیت تکمیل: ' . (((int)($row['profile_complete'] ?? 0) === 1) ? 'کامل' : 'ناقص') . '</p>';
echo '<button class="m360-btn" type="submit">ذخیره</button></form>';
p360hr_layout_end();
