<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 1 Submit Vehicle Binding (controlled write)
 */

require_once __DIR__ . '/includes/erp-customer-core-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    customer_core_render_error_page('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('customer_core_vehicle_binding', $_POST['erp_csrf_token'] ?? null);

$customerIdRaw = customer_core_post_string('customer_id');
$intakeIdRaw = customer_core_post_string('intake_id');
$vehicleIdRaw = customer_core_post_string('vehicle_id');
$relationshipType = customer_core_post_string('relationship_type');
$licensePlate = customer_core_normalize_plate(customer_core_post_string('license_plate'));
$vin = strtoupper(trim(customer_core_post_string('vin')));
$brand = customer_core_post_string('brand');
$model = customer_core_post_string('model');
$modelYearRaw = customer_core_post_string('model_year');
$color = customer_core_post_string('color');
$mileageRaw = customer_core_post_string('mileage_km');
$notes = customer_core_post_string('notes');

$allowedRelationships = ['OWNER', 'DRIVER', 'REPRESENTATIVE', 'FLEET_CONTACT', 'PREVIOUS_OWNER'];
$photoTypes = isset($_POST['photo_types']) && is_array($_POST['photo_types']) ? $_POST['photo_types'] : ERP_PHASE1_PHOTO_TYPES;

$errors = [];

if ($licensePlate === '') {
    $errors[] = 'پلاک الزامی است.';
}

if ($relationshipType === '' || !in_array($relationshipType, $allowedRelationships, true)) {
    $errors[] = 'نوع رابطه نامعتبر است.';
}

$customerId = $customerIdRaw !== '' && ctype_digit($customerIdRaw) ? (int)$customerIdRaw : null;
$intakeId = $intakeIdRaw !== '' && ctype_digit($intakeIdRaw) ? (int)$intakeIdRaw : null;
$vehicleId = $vehicleIdRaw !== '' && ctype_digit($vehicleIdRaw) ? (int)$vehicleIdRaw : null;
$modelYear = null;
$mileage = null;

if ($modelYearRaw !== '') {
    if (!ctype_digit($modelYearRaw)) {
        $errors[] = 'سال مدل باید عدد صحیح باشد.';
    } else {
        $modelYear = (int)$modelYearRaw;
    }
}

if ($mileageRaw !== '') {
    if (!ctype_digit($mileageRaw)) {
        $errors[] = 'کیلومتر باید عدد صحیح باشد.';
    } else {
        $mileage = (int)$mileageRaw;
    }
}

$validPhotoTypes = [];

foreach ($photoTypes as $photoType) {
    $photoType = trim((string)$photoType);

    if (in_array($photoType, ERP_PHASE1_PHOTO_TYPES, true)) {
        $validPhotoTypes[] = $photoType;
    }
}

if ($validPhotoTypes === []) {
    $validPhotoTypes = ERP_PHASE1_PHOTO_TYPES;
}

if ($errors !== []) {
    customer_core_render_error_page('خطای اعتبارسنجی', implode(' ', $errors));
}

$connection = false;

try {
    $connection = customer_core_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    customer_core_require_auth_and_guard($connection, 'customer.core.vehicle.binding.create');

    if (!customer_core_table_exists($connection, 'erp_customer_vehicle_bindings')) {
        throw new RuntimeException('جدول erp_customer_vehicle_bindings یافت نشد.');
    }

    $duplicate = customer_core_duplicate_check_vehicle($connection, $licensePlate, $vin);
    $createdBy = customer_core_safe_current_user();

    if (!@odbc_autocommit($connection, false)) {
        throw new RuntimeException('ثبت اتصال خودرو انجام نشد.');
    }

    $insertOk = customer_core_execute(
        $connection,
        'INSERT INTO dbo.erp_customer_vehicle_bindings (
            customer_id,
            intake_id,
            vehicle_id,
            relationship_type,
            license_plate,
            vin,
            brand,
            model,
            model_year,
            color,
            mileage_km,
            binding_status,
            notes,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $customerId,
            $intakeId,
            $vehicleId,
            $relationshipType,
            $licensePlate,
            $vin !== '' ? $vin : null,
            $brand !== '' ? $brand : null,
            $model !== '' ? $model : null,
            $modelYear,
            $color !== '' ? $color : null,
            $mileage,
            'ACTIVE',
            $notes !== '' ? $notes : null,
            $createdBy,
        ]
    );

    if ($insertOk === false) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت اتصال خودرو انجام نشد.');
    }

    $bindingId = customer_core_scope_identity($connection);

    if ($bindingId === null) {
        @odbc_rollback($connection);
        throw new RuntimeException('شناسه Binding دریافت نشد.');
    }

    if (customer_core_table_exists($connection, 'erp_vehicle_photo_records')) {
        foreach ($validPhotoTypes as $photoType) {
            $photoOk = customer_core_execute(
                $connection,
                'INSERT INTO dbo.erp_vehicle_photo_records (
                    binding_id,
                    vehicle_id,
                    photo_type,
                    placeholder_label,
                    storage_status,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $bindingId,
                    $vehicleId,
                    $photoType,
                    'Placeholder — ' . $photoType,
                    'PLACEHOLDER',
                    $createdBy,
                ]
            );

            if ($photoOk === false) {
                @odbc_rollback($connection);
                throw new RuntimeException('ثبت متادیتای عکس انجام نشد.');
            }
        }
    }

    $summary = 'ثبت اتصال خودرو — پلاک: ' . $licensePlate;

    if ($duplicate['status'] === 'POSSIBLE_DUPLICATE') {
        $summary .= ' — هشدار تکراری: ' . $duplicate['reason'];
    }

    if (!customer_core_insert_history(
        $connection,
        'erp_customer_vehicle_bindings',
        $bindingId,
        'VEHICLE_BINDING_CREATE',
        $summary,
        null,
        json_encode([
            'license_plate' => $licensePlate,
            'vin' => $vin,
            'relationship_type' => $relationshipType,
        ], JSON_UNESCAPED_UNICODE)
    )) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت تاریخچه انجام نشد.');
    }

    if (!@odbc_commit($connection)) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت اتصال خودرو انجام نشد.');
    }

    @odbc_autocommit($connection, true);
    customer_core_redirect('erp-customer-core-dashboard.php?phase1=vehicle_binding_ok');
} catch (Throwable) {
    if ($connection !== false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }

    customer_core_render_error_page('خطا در ثبت', 'ثبت اتصال خودرو انجام نشد. لطفاً دوباره تلاش کنید.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}
