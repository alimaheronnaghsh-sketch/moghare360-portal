<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 1B Form Validation Bridge Test Cases (shared)
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-form-validation-bridge.php';

/**
 * @return array<string, mixed>
 */
function wave_1b_sample_customer_valid(): array
{
    return [
        'customer_name' => 'علی رضایی',
        'mobile' => '09121234567',
        'national_id' => '',
        'customer_channel' => 'walk_in',
        'customer_class' => 'retail',
        'notes' => '',
    ];
}

/**
 * @return array<string, mixed>
 */
function wave_1b_sample_customer_invalid(): array
{
    return [
        'customer_name' => 'John Smith',
        'mobile' => '08111111111',
        'national_id' => '1111111111',
        'customer_channel' => 'walk_in',
        'customer_class' => 'retail',
    ];
}

/**
 * @return array<string, mixed>
 */
function wave_1b_sample_vehicle_valid(): array
{
    return [
        'plate' => [
            'province' => '12',
            'letter' => 'ب',
            'number' => '345',
            'series' => '67',
        ],
        'vin' => '1HGCM82633A004352',
        'chassis_no' => '',
        'engine_no' => '',
        'brand_id' => '10',
        'model_id' => '25',
        'vehicle_class' => 'sedan',
    ];
}

/**
 * @return array<string, mixed>
 */
function wave_1b_sample_vehicle_invalid(): array
{
    return [
        'plate' => [
            'province' => '',
            'letter' => '',
            'number' => '',
            'series' => '',
        ],
        'brand_id' => '0',
        'model_id' => '',
        'vehicle_class' => 'invalid_class',
    ];
}

/**
 * @return array<string, mixed>
 */
function wave_1b_sample_jobcard_valid(): array
{
    return [
        'customer_id' => '100',
        'vehicle_id' => '200',
        'reception_date' => '2026-06-23',
        'odometer' => '125000',
        'complaint_text' => 'صدای غیرعادی موتور',
        'jobcard_type' => 'repair',
        'service_category' => 'mechanical',
    ];
}

/**
 * @return array<string, mixed>
 */
function wave_1b_sample_jobcard_invalid(): array
{
    return [
        'customer_id' => '0',
        'vehicle_id' => '',
        'reception_date' => 'not-a-date',
        'complaint_text' => '',
        'jobcard_type' => 'repair',
        'service_category' => 'mechanical',
    ];
}

/**
 * @return list<array{name: string, callable(): bool}>
 */
function wave_1b_validation_test_cases(): array
{
    return [
        [
            'name' => 'Valid customer_create_v2 payload',
            'callable' => static function (): bool {
                $result = moghare360_validate_form_payload('customer_create_v2', wave_1b_sample_customer_valid());

                return $result['ok'] === true;
            },
        ],
        [
            'name' => 'Invalid customer_create_v2 payload',
            'callable' => static function (): bool {
                $result = moghare360_validate_form_payload('customer_create_v2', wave_1b_sample_customer_invalid());

                return moghare360_validation_has_failed($result);
            },
        ],
        [
            'name' => 'Valid vehicle_create_v2 payload',
            'callable' => static function (): bool {
                $result = moghare360_validate_form_payload('vehicle_create_v2', wave_1b_sample_vehicle_valid());

                return $result['ok'] === true;
            },
        ],
        [
            'name' => 'Invalid vehicle_create_v2 payload',
            'callable' => static function (): bool {
                $result = moghare360_validate_form_payload('vehicle_create_v2', wave_1b_sample_vehicle_invalid());

                return moghare360_validation_has_failed($result);
            },
        ],
        [
            'name' => 'Valid jobcard_create_v2 payload',
            'callable' => static function (): bool {
                $result = moghare360_validate_form_payload('jobcard_create_v2', wave_1b_sample_jobcard_valid());

                return $result['ok'] === true;
            },
        ],
        [
            'name' => 'Invalid jobcard_create_v2 payload',
            'callable' => static function (): bool {
                $result = moghare360_validate_form_payload('jobcard_create_v2', wave_1b_sample_jobcard_invalid());

                return moghare360_validation_has_failed($result);
            },
        ],
        [
            'name' => 'Error summary rendering',
            'callable' => static function (): bool {
                $result = moghare360_validate_form_payload('customer_create_v2', wave_1b_sample_customer_invalid());
                $summary = moghare360_validation_error_summary($result);

                return $summary !== '' && str_contains($summary, 'نام');
            },
        ],
        [
            'name' => 'HTML error rendering',
            'callable' => static function (): bool {
                $result = moghare360_validate_form_payload('jobcard_create_v2', wave_1b_sample_jobcard_invalid());
                $html = moghare360_validation_errors_as_html($result);

                return str_contains($html, '<ul') && str_contains($html, '<li>');
            },
        ],
        [
            'name' => 'Unknown form key rejection',
            'callable' => static function (): bool {
                $result = moghare360_validate_form_payload('not_a_real_form', ['foo' => 'bar']);

                return moghare360_validation_has_failed($result)
                    && ($result['errors'][0]['rule'] ?? '') === 'unknown_form_key';
            },
        ],
        [
            'name' => 'Optional field behavior',
            'callable' => static function (): bool {
                $payload = wave_1b_sample_customer_valid();
                $payload['national_id'] = '';
                $payload['notes'] = '';

                $result = moghare360_validate_form_payload('customer_create_v2', $payload);

                return $result['ok'] === true;
            },
        ],
    ];
}

/**
 * @return array{passed: int, failed: int, total: int, ok: bool, results: list<array{name: string, pass: bool}>}
 */
function wave_1b_run_validation_tests(): array
{
    $passed = 0;
    $failed = 0;
    $results = [];

    foreach (wave_1b_validation_test_cases() as $case) {
        $pass = (bool)($case['callable'])();
        if ($pass) {
            $passed++;
        } else {
            $failed++;
        }
        $results[] = [
            'name' => $case['name'],
            'pass' => $pass,
        ];
    }

    $total = count($results);

    return [
        'passed' => $passed,
        'failed' => $failed,
        'total' => $total,
        'ok' => $failed === 0,
        'results' => $results,
    ];
}
