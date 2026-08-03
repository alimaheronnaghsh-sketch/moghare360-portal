<?php
declare(strict_types=1);

/**
 * MOGHARE360 — single 8-stage intake spine + channel actor matrix.
 * One format / one stage keys / one data model. Only Stage-1 label + actor differs by channel.
 */

if (defined('M360_INTAKE_ACTOR_MATRIX_LOADED')) {
    return;
}
define('M360_INTAKE_ACTOR_MATRIX_LOADED', true);

const M360_INTAKE_ACTOR_CUSTOMER = 'CUSTOMER';
const M360_INTAKE_ACTOR_RECEPTION = 'RECEPTION';

const M360_INTAKE_CHANNEL_ONLINE = 'PUBLIC_SITE';
const M360_INTAKE_CHANNEL_WALKIN = 'STAFF_ASSISTED_WALKIN';

/**
 * Canonical stage keys (runtime stepper keys). Always exactly 8 operational stages.
 *
 * @return list<string>
 */
function m360_intake_stage_keys(): array
{
    return ['otp', 'customer', 'vehicle', 'service', 'condition', 'documents', 'signature', 'referral'];
}

/**
 * @param string $channel PUBLIC_SITE|STAFF_ASSISTED_WALKIN|empty
 */
function m360_intake_normalize_channel(string $channel): string
{
    $channel = strtoupper(trim($channel));
    if ($channel === '' || $channel === 'ONLINE' || $channel === 'ONLINE_PORTAL' || $channel === 'PUBLIC') {
        return M360_INTAKE_CHANNEL_ONLINE;
    }
    if (
        $channel === M360_INTAKE_CHANNEL_WALKIN
        || $channel === 'WALKIN'
        || $channel === 'STAFF_WALKIN'
        || $channel === 'OFFLINE'
        || str_contains($channel, 'WALKIN')
    ) {
        return M360_INTAKE_CHANNEL_WALKIN;
    }

    return M360_INTAKE_CHANNEL_ONLINE;
}

/**
 * @param array<string, mixed>|null $requestRow
 */
function m360_intake_channel_from_request(?array $requestRow): string
{
    if ($requestRow === null) {
        return M360_INTAKE_CHANNEL_ONLINE;
    }
    if (function_exists('m360_online_req_is_staff_walkin') && m360_online_req_is_staff_walkin($requestRow)) {
        return M360_INTAKE_CHANNEL_WALKIN;
    }
    $src = '';
    if (function_exists('m360_online_req_source_channel')) {
        $src = m360_online_req_source_channel($requestRow);
    } else {
        $src = (string)($requestRow['source_channel'] ?? $requestRow['source'] ?? '');
    }

    return m360_intake_normalize_channel($src);
}

/**
 * Single stage registry. Stage-1 label is channel-specific; keys/nums are shared.
 *
 * @return array<string, array{num:int,key:string,label:string,actor:string,hash:string,base_title:string}>
 */
function m360_intake_stage_registry(string $channel = ''): array
{
    $channel = m360_intake_normalize_channel($channel);
    $isWalkin = $channel === M360_INTAKE_CHANNEL_WALKIN;

    $stage1Label = $isWalkin
        ? 'جستجوی مشتری (موبایل / کد ملی)'
        : 'احراز / شناسایی مشتری (OTP)';
    $stage1Actor = $isWalkin ? M360_INTAKE_ACTOR_RECEPTION : M360_INTAKE_ACTOR_CUSTOMER;

    // Online: reception owns 5/6/8; customer owns 1-4 and 7.
    // Walk-in: reception owns 1-6 and 8; customer owns 7 only.
    $actors = $isWalkin
        ? [
            'otp' => M360_INTAKE_ACTOR_RECEPTION,
            'customer' => M360_INTAKE_ACTOR_RECEPTION,
            'vehicle' => M360_INTAKE_ACTOR_RECEPTION,
            'service' => M360_INTAKE_ACTOR_RECEPTION,
            'condition' => M360_INTAKE_ACTOR_RECEPTION,
            'documents' => M360_INTAKE_ACTOR_RECEPTION,
            'signature' => M360_INTAKE_ACTOR_CUSTOMER,
            'referral' => M360_INTAKE_ACTOR_RECEPTION,
        ]
        : [
            'otp' => M360_INTAKE_ACTOR_CUSTOMER,
            'customer' => M360_INTAKE_ACTOR_CUSTOMER,
            'vehicle' => M360_INTAKE_ACTOR_CUSTOMER,
            'service' => M360_INTAKE_ACTOR_CUSTOMER,
            'condition' => M360_INTAKE_ACTOR_RECEPTION,
            'documents' => M360_INTAKE_ACTOR_RECEPTION,
            'signature' => M360_INTAKE_ACTOR_CUSTOMER,
            'referral' => M360_INTAKE_ACTOR_RECEPTION,
        ];

    $base = [
        'otp' => ['num' => 1, 'base_title' => 'احراز / شناسایی مشتری', 'hash' => 'step-otp'],
        'customer' => ['num' => 2, 'base_title' => 'اطلاعات مشتری', 'hash' => 'step-customer'],
        'vehicle' => ['num' => 3, 'base_title' => 'اطلاعات خودرو', 'hash' => 'step-vehicle'],
        'service' => ['num' => 4, 'base_title' => 'خدمات و مسیر تکمیل', 'hash' => 'step-service'],
        'condition' => ['num' => 5, 'base_title' => 'وضعیت خودرو و عکس‌ها', 'hash' => 'step-condition'],
        'documents' => ['num' => 6, 'base_title' => 'مدارک، دیاگ، بیمه، توافقات، چک‌لیست و مبالغ', 'hash' => 'step-documents'],
        'signature' => ['num' => 7, 'base_title' => 'امضا و تأیید قرارداد', 'hash' => 'step-signature'],
        'referral' => ['num' => 8, 'base_title' => 'ارسال به سالن / JobCard', 'hash' => 'step-referral'],
    ];

    $out = [];
    foreach ($base as $key => $meta) {
        $label = $key === 'otp' ? $stage1Label : (string)$meta['base_title'];
        $out[$key] = [
            'num' => (int)$meta['num'],
            'key' => $key,
            'label' => $label,
            'actor' => (string)($actors[$key] ?? M360_INTAKE_ACTOR_RECEPTION),
            'hash' => (string)$meta['hash'],
            'base_title' => (string)$meta['base_title'],
        ];
        if ($key === 'otp') {
            $out[$key]['actor'] = $stage1Actor;
        }
    }

    return $out;
}

function m360_intake_actor_for_stage(string $channel, string $stageKey): string
{
    $reg = m360_intake_stage_registry($channel);

    return (string)($reg[$stageKey]['actor'] ?? '');
}

function m360_intake_stage_label(string $channel, string $stageKey): string
{
    $reg = m360_intake_stage_registry($channel);

    return (string)($reg[$stageKey]['label'] ?? $stageKey);
}

function m360_intake_stage_allowed_for_actor(string $channel, string $stageKey, string $currentActor): bool
{
    $actor = m360_intake_actor_for_stage($channel, $stageKey);
    $currentActor = strtoupper(trim($currentActor));
    if ($currentActor === '' || $actor === '') {
        return false;
    }
    // Reception may open customer-owned stages in read-only / continuation contexts.
    if ($currentActor === M360_INTAKE_ACTOR_RECEPTION) {
        return true;
    }

    return $actor === $currentActor;
}

function m360_intake_actor_label_fa(string $actor): string
{
    return match (strtoupper(trim($actor))) {
        M360_INTAKE_ACTOR_CUSTOMER => 'مسئول: مشتری',
        M360_INTAKE_ACTOR_RECEPTION => 'مسئول: پذیرش',
        default => 'مسئول: —',
    };
}

/**
 * Customer-facing online keys only (stages 1–4). Full 8-stage registry stays canonical for staff.
 *
 * @return list<string>
 */
function m360_intake_customer_facing_stage_keys(): array
{
    return ['otp', 'customer', 'vehicle', 'service'];
}

/**
 * Public customer labels (presentation only; does not alter registry).
 */
function m360_intake_customer_facing_stage_label(string $stageKey): string
{
    return match ($stageKey) {
        'otp' => 'موبایل و تأیید',
        'customer' => 'اطلاعات مشتری',
        'vehicle' => 'اطلاعات خودرو',
        'service' => 'خدمات و درخواست',
        default => m360_intake_stage_label(M360_INTAKE_CHANNEL_ONLINE, $stageKey),
    };
}

/**
 * @return array<string, array{num:int,key:string,label:string,actor:string,hash:string,base_title:string}>
 */
function m360_intake_customer_facing_stage_registry(): array
{
    $full = m360_intake_stage_registry(M360_INTAKE_CHANNEL_ONLINE);
    $out = [];
    foreach (m360_intake_customer_facing_stage_keys() as $key) {
        if (!isset($full[$key])) {
            continue;
        }
        $row = $full[$key];
        $row['label'] = m360_intake_customer_facing_stage_label($key);
        $out[$key] = $row;
    }

    return $out;
}
