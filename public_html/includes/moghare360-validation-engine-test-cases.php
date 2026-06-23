<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 1A Validation Engine Test Cases (shared)
 *
 * Runtime-safe: no database, session, auth, or config dependency.
 * Used by browser test page and CLI test runner.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-validation-engine.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-critical-form-v2-rules.php';

/**
 * @return list<array{name: string, callable(): bool}>
 */
function wave_1a_validation_test_cases(): array
{
    return [
        [
            'name' => 'Valid Iranian mobile',
            'callable' => static function (): bool {
                return moghare360_validation_mobile_ir('09123456789');
            },
        ],
        [
            'name' => 'Invalid Iranian mobile',
            'callable' => static function (): bool {
                return !moghare360_validation_mobile_ir('08123456789');
            },
        ],
        [
            'name' => 'Valid national ID',
            'callable' => static function (): bool {
                return moghare360_validation_national_id_ir('0010350829');
            },
        ],
        [
            'name' => 'Invalid national ID',
            'callable' => static function (): bool {
                return !moghare360_validation_national_id_ir('1111111111');
            },
        ],
        [
            'name' => 'Valid VIN',
            'callable' => static function (): bool {
                return moghare360_validation_vin('1HGCM82633A004352');
            },
        ],
        [
            'name' => 'Invalid VIN containing I/O/Q',
            'callable' => static function (): bool {
                return !moghare360_validation_vin('1HGCM82633A00435I')
                    && !moghare360_validation_vin('1HGCM82633A00435O')
                    && !moghare360_validation_vin('1HGCM82633A00435Q');
            },
        ],
        [
            'name' => 'Valid money amount',
            'callable' => static function (): bool {
                return moghare360_validation_money_amount('1500000')
                    && moghare360_validation_money_amount('1250.50');
            },
        ],
        [
            'name' => 'Invalid negative money amount',
            'callable' => static function (): bool {
                return !moghare360_validation_money_amount('-100');
            },
        ],
        [
            'name' => 'Valid yyyy-mm-dd date',
            'callable' => static function (): bool {
                return moghare360_validation_date_yyyy_mm_dd('2026-06-23');
            },
        ],
        [
            'name' => 'Invalid date',
            'callable' => static function (): bool {
                return !moghare360_validation_date_yyyy_mm_dd('2026-13-40')
                    && !moghare360_validation_date_yyyy_mm_dd('23-06-2026');
            },
        ],
        [
            'name' => 'Valid customer_create_v2 sample',
            'callable' => static function (): bool {
                $rules = moghare360_critical_form_rules('customer_create_v2');
                $result = moghare360_validation_ok([
                    'customer_name' => 'علی رضایی',
                    'mobile' => '09121234567',
                    'national_id' => '',
                    'customer_channel' => 'walk_in',
                    'customer_class' => 'retail',
                    'notes' => '',
                ], $rules);

                return $result['ok'] === true;
            },
        ],
        [
            'name' => 'Invalid customer_create_v2 sample',
            'callable' => static function (): bool {
                $rules = moghare360_critical_form_rules('customer_create_v2');
                $result = moghare360_validation_ok([
                    'customer_name' => 'John Smith',
                    'mobile' => '08111111111',
                    'national_id' => '1111111111',
                    'customer_channel' => 'walk_in',
                    'customer_class' => 'retail',
                ], $rules);

                return $result['ok'] === false;
            },
        ],
        [
            'name' => 'Valid vehicle_create_v2 sample',
            'callable' => static function (): bool {
                $rules = moghare360_critical_form_rules('vehicle_create_v2');
                $result = moghare360_validation_ok([
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
                ], $rules);

                return $result['ok'] === true;
            },
        ],
        [
            'name' => 'Invalid jobcard_create_v2 sample',
            'callable' => static function (): bool {
                $rules = moghare360_critical_form_rules('jobcard_create_v2');
                $result = moghare360_validation_ok([
                    'customer_id' => '0',
                    'vehicle_id' => '',
                    'reception_date' => 'invalid-date',
                    'complaint_text' => '',
                    'jobcard_type' => 'repair',
                    'service_category' => 'mechanical',
                ], $rules);

                return $result['ok'] === false;
            },
        ],
        [
            'name' => 'Critical form keys registry complete',
            'callable' => static function (): bool {
                $keys = moghare360_critical_form_keys();
                if (count($keys) !== 11) {
                    return false;
                }

                foreach ($keys as $key) {
                    if (moghare360_critical_form_rules($key) === []) {
                        return false;
                    }
                }

                return true;
            },
        ],
        [
            'name' => 'payment_tracking_preview_v2 allowed_value',
            'callable' => static function (): bool {
                $rules = moghare360_critical_form_rules('payment_tracking_preview_v2');
                $ok = moghare360_validation_ok([
                    'jobcard_id' => '100',
                    'amount' => '2500000',
                    'payment_date' => '2026-06-23',
                    'payment_method' => 'cash',
                ], $rules);
                $bad = moghare360_validation_ok([
                    'jobcard_id' => '100',
                    'amount' => '2500000',
                    'payment_date' => '2026-06-23',
                    'payment_method' => 'gateway',
                ], $rules);

                return $ok['ok'] === true && $bad['ok'] === false;
            },
        ],
    ];
}

/**
 * @return array{passed: int, failed: int, total: int, ok: bool, results: list<array{name: string, pass: bool}>}
 */
function wave_1a_run_validation_tests(): array
{
    $passed = 0;
    $failed = 0;
    $results = [];

    foreach (wave_1a_validation_test_cases() as $case) {
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
