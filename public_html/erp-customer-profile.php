<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 1 Customer Profile (internal read-only)
 */

require_once __DIR__ . '/includes/erp-customer-core-helper.php';

$customerIdRaw = customer_core_get_string('customer_id');
$intakeIdRaw = customer_core_get_string('intake_id');
$mobileSearch = customer_core_normalize_mobile(customer_core_get_string('mobile'));

$hasFilter = ($customerIdRaw !== '' && ctype_digit($customerIdRaw))
    || ($intakeIdRaw !== '' && ctype_digit($intakeIdRaw))
    || $mobileSearch !== '';

$connection = false;
$errorMessage = '';
$intakeRows = [];
$customerLegacy = null;
$phonesLegacy = [];
$bindings = [];
$contracts = [];
$jobcards = [];
$paymentsSummary = null;
$crmPlaceholder = true;

function p1cc_badge_class(string $status): string
{
    return match (strtoupper($status)) {
        'OPEN', 'ACTIVE' => 'p1cc-badge-open',
        'DRAFT' => 'p1cc-badge-draft',
        'ACCEPTED' => 'p1cc-badge-accepted',
        'POSSIBLE_DUPLICATE' => 'p1cc-badge-duplicate',
        'NEW' => 'p1cc-badge-new',
        default => 'p1cc-badge-draft',
    };
}

try {
    $connection = customer_core_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    customer_core_require_auth_and_guard($connection, 'customer.core.profile.view');

    if ($hasFilter && customer_core_table_exists($connection, 'erp_customer_intakes')) {
        $params = [];
        $where = [];

        if ($customerIdRaw !== '' && ctype_digit($customerIdRaw)) {
            $where[] = 'customer_id = ?';
            $params[] = (int)$customerIdRaw;
        }

        if ($intakeIdRaw !== '' && ctype_digit($intakeIdRaw)) {
            $where[] = 'intake_id = ?';
            $params[] = (int)$intakeIdRaw;
        }

        if ($mobileSearch !== '') {
            $where[] = 'mobile = ?';
            $params[] = $mobileSearch;
        }

        $sql = 'SELECT TOP 20 intake_id, customer_id, full_name, mobile, national_code, license_plate,
                       intake_channel, intake_type, duplicate_status, status, created_at, notes
                FROM dbo.erp_customer_intakes';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' OR ', $where);
        }

        $sql .= ' ORDER BY intake_id DESC';
        $intakeRows = customer_core_fetch_rows($connection, $sql, $params);
    }

    $resolvedCustomerId = null;

    if ($customerIdRaw !== '' && ctype_digit($customerIdRaw)) {
        $resolvedCustomerId = (int)$customerIdRaw;
    } elseif ($intakeRows !== [] && ($intakeRows[0]['customer_id'] ?? '') !== '') {
        $resolvedCustomerId = (int)$intakeRows[0]['customer_id'];
    }

    if ($resolvedCustomerId !== null && customer_core_table_exists($connection, 'Customers_v2')) {
        $nameCol = customer_core_column_exists($connection, 'Customers_v2', 'FullName') ? 'FullName' : null;
        $mobileCol = customer_core_column_exists($connection, 'Customers_v2', 'Mobile') ? 'Mobile' : null;
        $nationalCol = customer_core_column_exists($connection, 'Customers_v2', 'NationalCode') ? 'NationalCode' : null;
        $idCol = customer_core_column_exists($connection, 'Customers_v2', 'CustomerID') ? 'CustomerID' : (
            customer_core_column_exists($connection, 'Customers_v2', 'customer_id') ? 'customer_id' : null
        );

        if ($idCol !== null) {
            $cols = [$idCol . ' AS customer_id'];

            if ($nameCol !== null) {
                $cols[] = $nameCol . ' AS full_name';
            }

            if ($mobileCol !== null) {
                $cols[] = $mobileCol . ' AS mobile';
            }

            if ($nationalCol !== null) {
                $cols[] = $nationalCol . ' AS national_code';
            }

            $legacyRows = customer_core_fetch_rows(
                $connection,
                'SELECT TOP 1 ' . implode(', ', $cols) . ' FROM dbo.Customers_v2 WHERE [' . $idCol . '] = ?',
                [$resolvedCustomerId]
            );
            $customerLegacy = $legacyRows[0] ?? null;
        }
    }

    if ($resolvedCustomerId !== null && customer_core_table_exists($connection, 'CustomerPhones_v2')) {
        $custFk = null;

        foreach (['CustomerID', 'customer_id', 'CustomerId'] as $candidate) {
            if (customer_core_column_exists($connection, 'CustomerPhones_v2', $candidate)) {
                $custFk = $candidate;
                break;
            }
        }

        $phoneCol = customer_core_column_exists($connection, 'CustomerPhones_v2', 'PhoneNumber') ? 'PhoneNumber' : null;

        if ($custFk !== null && $phoneCol !== null) {
            $phonesLegacy = customer_core_fetch_rows(
                $connection,
                'SELECT TOP 10 [' . $phoneCol . '] AS phone_number FROM dbo.CustomerPhones_v2 WHERE [' . $custFk . '] = ?',
                [$resolvedCustomerId]
            );
        }
    }

    if ($hasFilter && customer_core_table_exists($connection, 'erp_customer_vehicle_bindings')) {
        $bindParams = [];
        $bindWhere = [];

        if ($customerIdRaw !== '' && ctype_digit($customerIdRaw)) {
            $bindWhere[] = 'customer_id = ?';
            $bindParams[] = (int)$customerIdRaw;
        }

        if ($intakeIdRaw !== '' && ctype_digit($intakeIdRaw)) {
            $bindWhere[] = 'intake_id = ?';
            $bindParams[] = (int)$intakeIdRaw;
        }

        if ($mobileSearch !== '' && $intakeRows !== []) {
            $intakeIds = array_map(static fn(array $r): int => (int)$r['intake_id'], $intakeRows);

            if ($intakeIds !== []) {
                $placeholders = implode(',', array_fill(0, count($intakeIds), '?'));
                $bindWhere[] = 'intake_id IN (' . $placeholders . ')';
                $bindParams = array_merge($bindParams, $intakeIds);
            }
        }

        if ($bindWhere !== []) {
            $bindings = customer_core_fetch_rows(
                $connection,
                'SELECT TOP 20 binding_id, customer_id, intake_id, vehicle_id, relationship_type,
                        license_plate, vin, brand, model, binding_status, created_at
                 FROM dbo.erp_customer_vehicle_bindings
                 WHERE ' . implode(' OR ', $bindWhere) . '
                 ORDER BY binding_id DESC',
                $bindParams
            );
        }
    }

    if ($hasFilter && customer_core_table_exists($connection, 'erp_customer_contracts')) {
        $contractParams = [];
        $contractWhere = [];

        if ($customerIdRaw !== '' && ctype_digit($customerIdRaw)) {
            $contractWhere[] = 'customer_id = ?';
            $contractParams[] = (int)$customerIdRaw;
        }

        if ($intakeIdRaw !== '' && ctype_digit($intakeIdRaw)) {
            $contractWhere[] = 'intake_id = ?';
            $contractParams[] = (int)$intakeIdRaw;
        }

        if ($contractWhere !== []) {
            $contracts = customer_core_fetch_rows(
                $connection,
                'SELECT TOP 20 contract_id, contract_code, contract_type, authorization_mode, status, accepted_at, created_at
                 FROM dbo.erp_customer_contracts
                 WHERE ' . implode(' OR ', $contractWhere) . '
                 ORDER BY contract_id DESC',
                $contractParams
            );
        }
    }

    if ($resolvedCustomerId !== null) {
        if (customer_core_table_exists($connection, 'erp_jobcards')) {
            $jobcards = customer_core_fetch_rows(
                $connection,
                'SELECT TOP 10 jobcard_id, jobcard_code, lifecycle_state, created_at
                 FROM dbo.erp_jobcards
                 WHERE customer_id = ?
                 ORDER BY jobcard_id DESC',
                [$resolvedCustomerId]
            );
        } elseif (customer_core_table_exists($connection, 'JobCard')) {
            $jcIdCol = customer_core_column_exists($connection, 'JobCard', 'JobCardID') ? 'JobCardID' : null;
            $jcCustCol = customer_core_column_exists($connection, 'JobCard', 'CustomerID') ? 'CustomerID' : null;

            if ($jcIdCol !== null && $jcCustCol !== null) {
                $jobcards = customer_core_fetch_rows(
                    $connection,
                    'SELECT TOP 10 [' . $jcIdCol . '] AS jobcard_id FROM dbo.JobCard WHERE [' . $jcCustCol . '] = ?',
                    [$resolvedCustomerId]
                );
            }
        }

        if (customer_core_table_exists($connection, 'erp_payments')) {
            $payCount = customer_core_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.erp_payments WHERE customer_id = ?',
                [$resolvedCustomerId]
            );
            $paySum = customer_core_scalar(
                $connection,
                'SELECT ISNULL(SUM(payment_amount), 0) FROM dbo.erp_payments WHERE customer_id = ? AND is_active = 1',
                [$resolvedCustomerId]
            );
            $paymentsSummary = [
                'count' => $payCount ?? '0',
                'total' => $paySum ?? '0',
            ];
        } elseif (customer_core_table_exists($connection, 'Payments')) {
            $payCustCol = customer_core_column_exists($connection, 'Payments', 'CustomerID') ? 'CustomerID' : null;

            if ($payCustCol !== null) {
                $payCount = customer_core_scalar(
                    $connection,
                    'SELECT COUNT(*) FROM dbo.Payments WHERE [' . $payCustCol . '] = ?',
                    [$resolvedCustomerId]
                );
                $paymentsSummary = ['count' => $payCount ?? '0', 'total' => '—'];
            }
        }
    }
} catch (Throwable) {
    $errorMessage = 'نمایش پروفایل مشتری با خطا مواجه شد.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

customer_core_render_head('پروفایل مشتری', true);

echo '<div class="p1cc-hero">';
echo '<h1>پروفایل داخلی مشتری</h1>';
echo '<p>نمای فقط خواندنی برای پرسنل — بدون ورود مشتری</p>';
echo '</div>';

echo '<div class="p1cc-card">';
echo '<h2>جستجو</h2>';
echo '<form method="get" class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="customer_id">شناسه مشتری</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="number" id="customer_id" name="customer_id" value="' . customer_core_h($customerIdRaw) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="intake_id">شناسه Intake</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="number" id="intake_id" name="intake_id" value="' . customer_core_h($intakeIdRaw) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="mobile">موبایل</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="text" id="mobile" name="mobile" value="' . customer_core_h($mobileSearch) . '"></div>';
echo '<div class="p1cc-form-group" style="align-self:end"><button class="p1cc-btn p1cc-btn-primary" type="submit">نمایش پروفایل</button></div>';
echo '</form></div>';

if ($errorMessage !== '') {
    echo '<div class="p1cc-card p1cc-error"><p>' . customer_core_h($errorMessage) . '</p></div>';
} elseif (!$hasFilter) {
    echo '<div class="p1cc-card"><p class="p1cc-hint">برای نمایش پروفایل، حداقل یک فیلتر جستجو وارد کنید.</p></div>';
} else {
    echo '<div class="p1cc-card"><h2>اطلاعات Intake</h2>';

    if ($intakeRows === []) {
        echo '<p class="p1cc-section-unavailable">رکورد Intake یافت نشد یا جدول در دسترس نیست.</p>';
    } else {
        echo '<table class="p1cc-table"><thead><tr>';
        echo '<th>شناسه</th><th>نام</th><th>موبایل</th><th>کانال</th><th>وضعیت</th><th>تکراری</th></tr></thead><tbody>';

        foreach ($intakeRows as $row) {
            echo '<tr>';
            echo '<td>' . customer_core_h($row['intake_id'] ?? '') . '</td>';
            echo '<td>' . customer_core_h($row['full_name'] ?? '') . '</td>';
            echo '<td>' . customer_core_h($row['mobile'] ?? '') . '</td>';
            echo '<td>' . customer_core_h($row['intake_channel'] ?? '') . '</td>';
            echo '<td><span class="p1cc-badge ' . p1cc_badge_class($row['status'] ?? '') . '">' . customer_core_h($row['status'] ?? '') . '</span></td>';
            echo '<td><span class="p1cc-badge ' . p1cc_badge_class($row['duplicate_status'] ?? '') . '">' . customer_core_h($row['duplicate_status'] ?? '') . '</span></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';

    echo '<div class="p1cc-card"><h2>مشتری Legacy (Customers_v2)</h2>';

    if ($customerLegacy === null) {
        echo '<p class="p1cc-section-unavailable">در دسترس نیست یا یافت نشد.</p>';
    } else {
        echo '<table class="p1cc-table"><tbody>';
        foreach ($customerLegacy as $key => $value) {
            echo '<tr><th>' . customer_core_h($key) . '</th><td>' . customer_core_h($value) . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    echo '</div>';

    echo '<div class="p1cc-card"><h2>شماره‌ها (CustomerPhones_v2)</h2>';

    if ($phonesLegacy === []) {
        echo '<p class="p1cc-section-unavailable">در دسترس نیست یا یافت نشد.</p>';
    } else {
        echo '<ul>';

        foreach ($phonesLegacy as $phone) {
            echo '<li>' . customer_core_h($phone['phone_number'] ?? '') . '</li>';
        }

        echo '</ul>';
    }

    echo '</div>';

    echo '<div class="p1cc-card"><h2>خودروها (Binding)</h2>';

    if ($bindings === []) {
        echo '<p class="p1cc-section-unavailable">اتصال خودرو یافت نشد.</p>';
    } else {
        echo '<table class="p1cc-table"><thead><tr>';
        echo '<th>شناسه</th><th>پلاک</th><th>VIN</th><th>برند/مدل</th><th>رابطه</th><th>وضعیت</th></tr></thead><tbody>';

        foreach ($bindings as $binding) {
            echo '<tr>';
            echo '<td>' . customer_core_h($binding['binding_id'] ?? '') . '</td>';
            echo '<td>' . customer_core_h($binding['license_plate'] ?? '') . '</td>';
            echo '<td>' . customer_core_h($binding['vin'] ?? '—') . '</td>';
            echo '<td>' . customer_core_h(trim(($binding['brand'] ?? '') . ' ' . ($binding['model'] ?? ''))) . '</td>';
            echo '<td>' . customer_core_h($binding['relationship_type'] ?? '') . '</td>';
            echo '<td><span class="p1cc-badge ' . p1cc_badge_class($binding['binding_status'] ?? '') . '">' . customer_core_h($binding['binding_status'] ?? '') . '</span></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';

    echo '<div class="p1cc-card"><h2>قراردادها</h2>';

    if ($contracts === []) {
        echo '<p class="p1cc-section-unavailable">قراردادی یافت نشد.</p>';
    } else {
        echo '<table class="p1cc-table"><thead><tr>';
        echo '<th>کد</th><th>نوع</th><th>مجوز</th><th>وضعیت</th><th>تاریخ</th></tr></thead><tbody>';

        foreach ($contracts as $contract) {
            echo '<tr>';
            echo '<td>' . customer_core_h($contract['contract_code'] ?? '') . '</td>';
            echo '<td>' . customer_core_h($contract['contract_type'] ?? '') . '</td>';
            echo '<td>' . customer_core_h($contract['authorization_mode'] ?? '') . '</td>';
            echo '<td><span class="p1cc-badge ' . p1cc_badge_class($contract['status'] ?? '') . '">' . customer_core_h($contract['status'] ?? '') . '</span></td>';
            echo '<td>' . customer_core_h($contract['created_at'] ?? '') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';

    echo '<div class="p1cc-card"><h2>JobCard</h2>';

    if ($jobcards === []) {
        echo '<p class="p1cc-section-unavailable">JobCard مرتبط یافت نشد یا جدول در دسترس نیست.</p>';
    } else {
        echo '<table class="p1cc-table"><thead><tr><th>شناسه</th><th>کد</th><th>وضعیت</th></tr></thead><tbody>';

        foreach ($jobcards as $jc) {
            echo '<tr>';
            echo '<td>' . customer_core_h($jc['jobcard_id'] ?? '') . '</td>';
            echo '<td>' . customer_core_h($jc['jobcard_code'] ?? '—') . '</td>';
            echo '<td>' . customer_core_h($jc['lifecycle_state'] ?? '—') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';

    echo '<div class="p1cc-card"><h2>پیش‌نمایش مالی (Payments)</h2>';

    if ($paymentsSummary === null) {
        echo '<p class="p1cc-section-unavailable">خلاصه پرداخت در دسترس نیست.</p>';
    } else {
        echo '<p>تعداد پرداخت: <strong>' . customer_core_h($paymentsSummary['count']) . '</strong></p>';
        echo '<p>جمع مبلغ: <strong>' . customer_core_h($paymentsSummary['total']) . '</strong></p>';
    }

    echo '</div>';

    echo '<div class="p1cc-card"><h2>CRM (آینده)</h2>';
    echo '<p class="p1cc-section-unavailable">ماژول CRM در فازهای بعدی فعال خواهد شد — Placeholder</p>';
    echo '</div>';
}

echo '<p><a class="p1cc-link" href="erp-customer-core-dashboard.php">بازگشت به داشبورد</a></p>';

customer_core_render_foot();
