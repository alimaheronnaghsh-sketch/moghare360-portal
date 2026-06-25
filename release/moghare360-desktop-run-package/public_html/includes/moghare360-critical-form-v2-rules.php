<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Critical Forms v2 Validation Rule Registry (Wave 1A)
 *
 * Rule sets for future form integration. Does not modify existing production forms.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-validation-engine.php';

/**
 * @return list<string>
 */
function moghare360_critical_form_keys(): array
{
    return [
        'customer_create_v2',
        'vehicle_create_v2',
        'jobcard_create_v2',
        'service_operation_v2',
        'part_reservation_v2',
        'part_consumption_v2',
        'purchase_request_v2',
        'payment_tracking_preview_v2',
        'crm_followup_v2',
        'complaint_v2',
        'employee_profile_v2',
    ];
}

/**
 * @return array<string, list<string|array<int, mixed>>>
 */
function moghare360_critical_form_rules(string $formKey): array
{
    $registry = moghare360_critical_form_rules_registry();

    return $registry[$formKey] ?? [];
}

/**
 * @return array<string, array<string, list<string|array<int, mixed>>>>
 */
function moghare360_critical_form_rules_registry(): array
{
    return [
        'customer_create_v2' => [
            'customer_name' => ['required', 'persian_name', ['safe_text', 100]],
            'mobile' => ['required', 'mobile_ir'],
            'national_id' => ['optional', 'national_id_ir'],
            'customer_channel' => ['required', ['allowed_value', ['walk_in', 'phone', 'referral', 'repeat', 'web_placeholder']]],
            'customer_class' => ['required', ['allowed_value', ['retail', 'fleet', 'vip', 'warranty']]],
            'notes' => ['optional', ['safe_text', 500]],
        ],
        'vehicle_create_v2' => [
            'plate' => ['required', 'iran_plate'],
            'vin' => ['optional', 'vin'],
            'chassis_no' => ['optional', 'engine_or_chassis'],
            'engine_no' => ['optional', 'engine_or_chassis'],
            'brand_id' => ['required', 'positive_number'],
            'model_id' => ['required', 'positive_number'],
            'vehicle_class' => ['required', ['allowed_value', ['sedan', 'suv', 'commercial', 'motorcycle']]],
            'vehicle_notes' => ['optional', ['safe_text', 500]],
        ],
        'jobcard_create_v2' => [
            'customer_id' => ['required', 'positive_number'],
            'vehicle_id' => ['required', 'positive_number'],
            'reception_date' => ['required', 'date_yyyy_mm_dd'],
            'odometer' => ['optional', 'kilometer'],
            'complaint_text' => ['required', ['safe_text', 1000]],
            'jobcard_type' => ['required', ['allowed_value', ['repair', 'service', 'inspection', 'bodywork']]],
            'service_category' => ['required', ['allowed_value', ['mechanical', 'electrical', 'body', 'diagnostic', 'maintenance']]],
        ],
        'service_operation_v2' => [
            'jobcard_id' => ['required', 'positive_number'],
            'operation_type' => ['required', ['allowed_value', ['diagnostic', 'repair', 'replace', 'adjust', 'test']]],
            'step_status' => ['required', ['allowed_value', ['pending', 'in_progress', 'done', 'blocked']]],
            'technician_id' => ['required', 'positive_number'],
            'technician_notes' => ['optional', ['safe_text', 2000]],
        ],
        'part_reservation_v2' => [
            'jobcard_id' => ['required', 'positive_number'],
            'part_id' => ['required', 'positive_number'],
            'warehouse_id' => ['required', 'positive_number'],
            'quantity' => ['required', 'positive_number'],
            'reservation_note' => ['optional', ['safe_text', 500]],
        ],
        'part_consumption_v2' => [
            'jobcard_id' => ['required', 'positive_number'],
            'operation_id' => ['required', 'positive_number'],
            'part_id' => ['required', 'positive_number'],
            'warehouse_id' => ['required', 'positive_number'],
            'quantity' => ['required', 'positive_number'],
            'consumption_note' => ['optional', ['safe_text', 500]],
        ],
        'purchase_request_v2' => [
            'purchase_type' => ['required', ['allowed_value', ['internal', 'external']]],
            'part_id' => ['required', 'positive_number'],
            'supplier_id' => ['required', 'positive_number'],
            'quantity' => ['required', 'positive_number'],
            'warehouse_id' => ['required', 'positive_number'],
            'jobcard_id' => ['optional', 'positive_number'],
            'justification_note' => ['required', ['safe_text', 1000]],
        ],
        'payment_tracking_preview_v2' => [
            'jobcard_id' => ['required', 'positive_number'],
            'amount' => ['required', 'money_amount'],
            'payment_date' => ['required', 'date_yyyy_mm_dd'],
            'payment_method' => ['required', ['allowed_value', ['cash', 'card_present', 'transfer', 'other']]],
            'payment_note' => ['optional', ['safe_text', 500]],
        ],
        'crm_followup_v2' => [
            'customer_id' => ['required', 'positive_number'],
            'jobcard_id' => ['optional', 'positive_number'],
            'followup_type' => ['required', ['allowed_value', ['post_delivery', 'reminder', 'complaint', 'upsell', 'other']]],
            'followup_status' => ['required', ['allowed_value', ['pending', 'contacted', 'no_answer', 'issue_reported', 'closed']]],
            'next_action_date' => ['required', 'date_yyyy_mm_dd'],
            'followup_note' => ['optional', ['safe_text', 2000]],
        ],
        'complaint_v2' => [
            'customer_id' => ['required', 'positive_number'],
            'vehicle_id' => ['optional', 'positive_number'],
            'jobcard_id' => ['optional', 'positive_number'],
            'complaint_source' => ['required', ['allowed_value', ['phone', 'in_person', 'message', 'portal_future']]],
            'complaint_category' => ['required', ['allowed_value', ['service_quality', 'parts', 'delay', 'billing_preview', 'staff', 'other']]],
            'complaint_severity' => ['required', ['allowed_value', ['low', 'medium', 'high', 'critical']]],
            'complaint_summary' => ['required', ['safe_text', 2000]],
        ],
        'employee_profile_v2' => [
            'employee_name' => ['required', 'persian_name', ['safe_text', 100]],
            'national_id' => ['required', 'national_id_ir'],
            'mobile' => ['required', 'mobile_ir'],
            'department_id' => ['required', 'positive_number'],
            'position_id' => ['required', 'positive_number'],
            'contract_type' => ['required', ['allowed_value', ['full_time', 'part_time', 'contract', 'apprentice']]],
            'hr_notes' => ['optional', ['safe_text', 1000]],
        ],
    ];
}

/**
 * @return list<string>
 */
function moghare360_payment_tracking_preview_methods(): array
{
    return ['cash', 'card_present', 'transfer', 'other'];
}
