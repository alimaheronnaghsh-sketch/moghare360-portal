<?php
declare(strict_types=1);

if (!function_exists('m360_case_stage_h')) {
    function m360_case_stage_h(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('m360_case_stage_owner_label')) {
    function m360_case_stage_owner_label(string $owner): string
    {
        static $labels = [
            'customer' => 'مشتری',
            'reception' => 'پذیرش',
            'owner' => 'مالک / مدیر مجاز',
            'service_manager' => 'مسئول سالن',
            'technician' => 'تکنسین',
            'inventory' => 'انبار',
            'finance' => 'مالی',
            'qc' => 'کنترل کیفیت',
            'delivery' => 'تحویل',
            'system' => 'سیستم',
        ];

        $key = strtolower(trim($owner));

        return $labels[$key] ?? 'نامشخص';
    }
}

if (!function_exists('m360_case_stage_decision_label')) {
    function m360_case_stage_decision_label(array $decision): string
    {
        $type = strtoupper(trim((string)($decision['task_type'] ?? '')));
        $taskId = (int)($decision['task_id'] ?? 0);

        $label = match ($type) {
            'CONTRACT_SIGNATURE' => 'امضای قرارداد مشتری',
            'DELIVERY_CONFIRMATION' => 'تأیید تحویل مشتری',
            default => 'تأیید برآورد مشتری',
        };

        return $taskId > 0 ? $label . ' #' . $taskId : $label;
    }
}

if (!function_exists('m360_case_stage_css')) {
    function m360_case_stage_css(): string
    {
        static $printed = false;
        if ($printed) {
            return '';
        }
        $printed = true;

        return <<<'CSS'
<style>
.m360-case-stage-header{direction:rtl;margin:16px 0;padding:18px;border:1px solid rgba(212,175,55,.25);border-radius:22px;background:linear-gradient(135deg,#07251f,#0c3b30 55%,#10231f);box-shadow:0 18px 45px rgba(0,0,0,.24);color:#f7f1dc;font-family:inherit}
.m360-case-stage-header *{box-sizing:border-box}
.m360-case-stage-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:14px}
.m360-case-stage-kicker{margin:0 0 6px;color:#d9b85f;font-size:.82rem;font-weight:800;letter-spacing:.02em}
.m360-case-stage-title{margin:0;color:#fff8df;font-size:1.2rem;font-weight:900}
.m360-case-stage-meta{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.m360-case-stage-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:7px 11px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);color:#f8f2dc;font-size:.82rem;font-weight:800}
.m360-case-stage-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:14px}
.m360-case-stage-box{border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:10px 12px;background:rgba(255,255,255,.06);min-height:76px}
.m360-case-stage-box dt{margin:0 0 6px;color:#c9d7ce;font-size:.76rem;font-weight:700}
.m360-case-stage-box dd{margin:0;color:#fff7df;font-size:.95rem;font-weight:900;line-height:1.7}
.m360-case-stage-track{display:flex;gap:7px;flex-wrap:wrap;margin-top:8px}
.m360-case-stage-step{display:inline-flex;align-items:center;gap:6px;min-height:34px;border-radius:999px;padding:7px 10px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);color:#dbe8df;font-size:.78rem;font-weight:800}
.m360-case-stage-step b{display:inline-grid;place-items:center;width:20px;height:20px;border-radius:50%;background:rgba(255,255,255,.11);color:#f7d981;font-size:.72rem}
.m360-case-stage-step.is-done{border-color:rgba(94,218,149,.45);background:rgba(25,122,80,.35);color:#e9fff1}
.m360-case-stage-step.is-current{border-color:rgba(244,202,90,.8);background:rgba(166,123,25,.34);color:#fff5ce;box-shadow:0 0 0 2px rgba(244,202,90,.12) inset}
.m360-case-stage-step.is-locked{border-color:rgba(182,91,91,.35);background:rgba(84,31,31,.3);color:#ead0d0}
.m360-case-stage-note{margin:12px 0 0;color:#f6e5ad;font-size:.86rem;font-weight:800}
.m360-case-stage-decisions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.m360-case-stage-decision{border-radius:999px;padding:6px 10px;border:1px solid rgba(244,202,90,.35);background:rgba(244,202,90,.12);color:#fff0c2;font-size:.78rem;font-weight:800}
.m360-case-stage-compact{direction:rtl;display:inline-flex;align-items:center;gap:7px;border-radius:999px;padding:6px 10px;border:1px solid rgba(212,175,55,.35);background:linear-gradient(135deg,rgba(7,37,31,.95),rgba(12,59,48,.92));color:#fff2c8;font-size:.78rem;font-weight:900;white-space:nowrap}
.m360-case-stage-compact small{color:#d9e5db;font-weight:800}
@media (max-width:900px){.m360-case-stage-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:560px){.m360-case-stage-grid{grid-template-columns:1fr}.m360-case-stage-step{width:100%;justify-content:flex-start}}
</style>
CSS;
    }
}

if (!function_exists('m360_render_case_stage_header')) {
    /**
     * @param array<string, mixed> $stageTree
     * @param array<string, mixed> $options
     */
    function m360_render_case_stage_header(array $stageTree, array $options = []): string
    {
        $compact = !empty($options['compact']);
        $stageNumber = (int)($stageTree['current_stage_number'] ?? 0);
        $stageLabel = trim((string)($stageTree['current_stage'] ?? 'نامشخص'));
        $cartable = trim((string)($stageTree['current_cartable'] ?? 'نامشخص'));
        $status = trim((string)($stageTree['current_status'] ?? 'نامشخص'));
        $nextAction = trim((string)($stageTree['next_action'] ?? 'نیازمند بررسی اطلاعات پرونده'));
        $owner = m360_case_stage_owner_label((string)($stageTree['action_owner'] ?? 'system'));
        $blocker = trim((string)($stageTree['blocker_reason'] ?? ''));
        $blockerDisplay = $blocker !== '' ? $blocker : 'بدون مانع';
        $identity = is_array($stageTree['case_identity'] ?? null) ? $stageTree['case_identity'] : [];

        if ($compact) {
            return m360_case_stage_css()
                . '<span class="m360-case-stage-compact" title="' . m360_case_stage_h($nextAction) . '">'
                . '<span>' . m360_case_stage_h($stageNumber > 0 ? $stageNumber . ' — ' . $stageLabel : $stageLabel) . '</span>'
                . '<small>' . m360_case_stage_h($status) . '</small>'
                . '</span>';
        }

        $requestId = (int)($identity['online_request_id'] ?? $identity['request_id'] ?? 0);
        $jobcardId = (int)($identity['jobcard_id'] ?? 0);
        $contractId = (int)($identity['contract_id'] ?? 0);
        $estimateId = (int)($identity['estimate_id'] ?? 0);
        $completed = array_map('intval', is_array($stageTree['completed_stages'] ?? null) ? $stageTree['completed_stages'] : []);
        $locked = array_map('intval', is_array($stageTree['locked_stages'] ?? null) ? $stageTree['locked_stages'] : []);
        $stages = is_array($stageTree['all_stages'] ?? null) ? $stageTree['all_stages'] : [];
        if ($stages === [] && function_exists('m360_cst_stages')) {
            $stages = m360_cst_stages();
        }

        $chips = '';
        foreach ($stages as $number => $stage) {
            $number = (int)$number;
            $label = is_array($stage) ? (string)($stage['label'] ?? '') : (string)$stage;
            $class = 'm360-case-stage-step';
            if ($number === $stageNumber) {
                $class .= ' is-current';
            } elseif (in_array($number, $completed, true)) {
                $class .= ' is-done';
            } elseif (in_array($number, $locked, true)) {
                $class .= ' is-locked';
            }
            $chips .= '<span class="' . m360_case_stage_h($class) . '"><b>' . m360_case_stage_h((string)$number) . '</b>' . m360_case_stage_h($label) . '</span>';
        }

        $decisionHtml = '';
        $decisions = is_array($stageTree['active_decisions'] ?? null) ? $stageTree['active_decisions'] : [];
        foreach ($decisions as $decision) {
            if (!is_array($decision)) {
                continue;
            }
            $decisionHtml .= '<span class="m360-case-stage-decision">' . m360_case_stage_h(m360_case_stage_decision_label($decision)) . '</span>';
        }
        if ($decisionHtml !== '') {
            $decisionHtml = '<div class="m360-case-stage-decisions">' . $decisionHtml . '</div>';
        }

        $identityBits = [];
        if ($requestId > 0) {
            $identityBits[] = 'درخواست #' . $requestId;
        }
        if ($jobcardId > 0) {
            $identityBits[] = 'JobCard #' . $jobcardId;
        }
        if ($contractId > 0) {
            $identityBits[] = 'قرارداد #' . $contractId;
        }
        if ($estimateId > 0) {
            $identityBits[] = 'برآورد #' . $estimateId;
        }
        $identityText = $identityBits !== [] ? implode(' · ', $identityBits) : 'پرونده قابل تشخیص نیست';

        return m360_case_stage_css()
            . '<section class="m360-case-stage-header" aria-label="درخت مرحله پرونده V0">'
            . '<div class="m360-case-stage-head"><div>'
            . '<p class="m360-case-stage-kicker">درخت مرحله پرونده V0 — فقط خواندنی</p>'
            . '<h2 class="m360-case-stage-title">' . m360_case_stage_h($stageNumber > 0 ? 'مرحله ' . $stageNumber . ': ' . $stageLabel : $stageLabel) . '</h2>'
            . '</div><div class="m360-case-stage-meta">'
            . '<span class="m360-case-stage-pill">' . m360_case_stage_h($identityText) . '</span>'
            . '<span class="m360-case-stage-pill">' . m360_case_stage_h($cartable) . '</span>'
            . '</div></div>'
            . '<dl class="m360-case-stage-grid">'
            . '<div class="m360-case-stage-box"><dt>وضعیت فعلی</dt><dd>' . m360_case_stage_h($status) . '</dd></div>'
            . '<div class="m360-case-stage-box"><dt>اقدام بعدی</dt><dd>' . m360_case_stage_h($nextAction) . '</dd></div>'
            . '<div class="m360-case-stage-box"><dt>مسئول اقدام</dt><dd>' . m360_case_stage_h($owner) . '</dd></div>'
            . '<div class="m360-case-stage-box"><dt>مانع</dt><dd>' . m360_case_stage_h($blockerDisplay) . '</dd></div>'
            . '</dl>'
            . '<div class="m360-case-stage-track">' . $chips . '</div>'
            . $decisionHtml
            . '<p class="m360-case-stage-note">این نمایش از داده‌های موجود ساخته شده و هیچ رکورد عملیاتی را تغییر نمی‌دهد.</p>'
            . '</section>';
    }
}
