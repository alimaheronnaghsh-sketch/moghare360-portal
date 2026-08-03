<?php
declare(strict_types=1);

/**
 * Contract wage calculation engine — no hardcoded sample amounts.
 */

/** @return list<array{row_no:int,component_code:string,title_fa:string}> */
function p360hr_wage_clause9_rows(): array
{
    return [
        ['row_no' => 1, 'component_code' => 'BASE_WAGE', 'title_fa' => 'مزد ثابت / مبنا ساعتی'],
        ['row_no' => 2, 'component_code' => 'SENIORITY', 'title_fa' => 'پایه سنوات'],
        ['row_no' => 3, 'component_code' => 'HOUSING', 'title_fa' => 'حق مسکن'],
        ['row_no' => 4, 'component_code' => 'FOOD_BASKET', 'title_fa' => 'بن و خواروبار'],
        ['row_no' => 5, 'component_code' => 'MARRIAGE', 'title_fa' => 'حق تأهل'],
        ['row_no' => 6, 'component_code' => 'CHILD_ALLOWANCE', 'title_fa' => 'حق اولاد'],
        ['row_no' => 7, 'component_code' => 'EID_BONUS', 'title_fa' => 'عیدی و پاداش'],
        ['row_no' => 8, 'component_code' => 'SEVERANCE', 'title_fa' => 'سنوات (ماده ۲۴ ق.ک)'],
        ['row_no' => 9, 'component_code' => 'TRANSPORT', 'title_fa' => 'حق ایاب و ذهاب'],
        ['row_no' => 10, 'component_code' => 'TECHNICAL', 'title_fa' => 'حق فنی'],
        ['row_no' => 11, 'component_code' => 'OTHER', 'title_fa' => 'سایر مزایا'],
    ];
}

function p360hr_contract_type_fa(string $type): string
{
    return match (strtoupper($type)) {
        'PERMANENT' => 'دائم',
        'TEMPORARY' => 'موقت',
        'HOURLY' => 'ساعتی',
        'SPECIFIC_WORK' => 'کار معین',
        'CONTRACTUAL' => 'پیمانی',
        default => $type,
    };
}

function p360hr_money_fa(?float $n): string
{
    if ($n === null || abs($n) < 0.00001) {
        return '—';
    }
    return number_format($n, 0, '.', ',');
}

/**
 * @param array<string,mixed> $input
 * @return array{rows:list<array<string,mixed>>,total_daily:float,total_monthly:float,model:string}
 */
function p360hr_calculate_wage_table(string $contractType, array $input): array
{
    $type = strtoupper($contractType);
    $daysBasis = max(1, (int)($input['month_days_basis'] ?? 30));
    $rowsMeta = p360hr_wage_clause9_rows();
    $out = [];

    $hourly = (float)($input['hourly_rate'] ?? 0);
    $daily = (float)($input['daily_rate'] ?? 0);
    $monthlyFixed = (float)($input['monthly_fixed'] ?? 0);
    $payableDays = (float)($input['payable_days'] ?? $daysBasis);
    $approvedHours = (float)($input['approved_hours'] ?? 0);
    $requiredHours = (float)($input['required_hours'] ?? 0);

    // Derive base daily/monthly from contract type
    $baseDaily = 0.0;
    $baseMonthly = 0.0;
    $baseStatus = 'طبق قرارداد';
    $calcMethod = 'FORMULA';

    if ($type === 'HOURLY') {
        $calcMethod = 'HOURLY_RATE';
        $hours = $approvedHours > 0 ? $approvedHours : $requiredHours;
        $baseMonthly = $hourly * $hours;
        $baseDaily = $daysBasis > 0 ? ($baseMonthly / $daysBasis) : 0.0;
        $baseStatus = 'مشمول';
    } elseif ($type === 'TEMPORARY') {
        $calcMethod = 'DAILY_RATE';
        $baseDaily = $daily;
        $baseMonthly = $daily * $payableDays;
        $baseStatus = 'مشمول';
    } elseif ($type === 'PERMANENT') {
        if ($monthlyFixed > 0) {
            $calcMethod = 'MONTHLY_FIXED';
            $baseMonthly = $monthlyFixed;
            $baseDaily = $daysBasis > 0 ? ($monthlyFixed / $daysBasis) : 0.0;
        } else {
            $calcMethod = 'DAILY_RATE';
            $baseDaily = $daily;
            $baseMonthly = $daily * $daysBasis;
        }
        $baseStatus = 'مشمول';
    } elseif ($type === 'SPECIFIC_WORK') {
        $calcMethod = 'AGREEMENT_MILESTONE';
        $total = (float)($input['specific_total'] ?? 0);
        $baseMonthly = $total;
        $baseDaily = $daysBasis > 0 ? ($total / $daysBasis) : 0.0;
        $baseStatus = 'طبق توافق‌نامه';
    } elseif ($type === 'CONTRACTUAL') {
        $calcMethod = 'AGREEMENT_MILESTONE';
        $total = (float)($input['contractual_total'] ?? 0);
        $stages = max(1, (int)($input['contractual_stages'] ?? 1));
        $stageAmt = (float)($input['contractual_stage_amount'] ?? ($total / $stages));
        $baseMonthly = $stageAmt;
        $baseDaily = $daysBasis > 0 ? ($stageAmt / $daysBasis) : 0.0;
        $baseStatus = 'طبق توافق‌نامه';
    }

    $benefitMap = [
        'SENIORITY' => [(float)($input['seniority_monthly'] ?? 0), (string)($input['seniority_status'] ?? 'طبق قرارداد')],
        'HOUSING' => [(float)($input['housing_monthly'] ?? 0), (string)($input['housing_status'] ?? 'طبق قرارداد')],
        'FOOD_BASKET' => [(float)($input['food_monthly'] ?? 0), (string)($input['food_status'] ?? 'طبق قرارداد')],
        'MARRIAGE' => [(float)($input['marriage_monthly'] ?? 0), (string)($input['marriage_status'] ?? (((int)($input['marriage_eligible'] ?? 0) === 1) ? 'دارد' : 'ندارد'))],
        'CHILD_ALLOWANCE' => [(float)($input['child_monthly'] ?? 0), (string)($input['child_status'] ?? (((int)($input['child_eligible'] ?? 0) === 1) ? 'دارد' : 'ندارد'))],
        'EID_BONUS' => [(float)($input['eid_monthly'] ?? 0), (string)($input['eid_status'] ?? 'طبق قرارداد')],
        'SEVERANCE' => [(float)($input['severance_monthly'] ?? 0), (string)($input['severance_status'] ?? 'طبق قرارداد')],
        'TRANSPORT' => [(float)($input['transport_monthly'] ?? 0), (string)($input['transport_status'] ?? 'طبق قرارداد')],
        'TECHNICAL' => [(float)($input['technical_monthly'] ?? 0), (string)($input['technical_status'] ?? 'طبق قرارداد')],
        'OTHER' => [(float)($input['other_monthly'] ?? 0), (string)($input['other_status'] ?? 'طبق قرارداد')],
    ];

    // Eid/severance monthly inclusion controlled by payment method
    $eidMethod = strtoupper((string)($input['eid_payment_method'] ?? 'ANNUAL'));
    $sevMethod = strtoupper((string)($input['severance_payment_method'] ?? 'END_OF_CONTRACT'));
    if ($eidMethod !== 'MONTHLY') {
        $benefitMap['EID_BONUS'][0] = 0.0;
        if (($benefitMap['EID_BONUS'][1] ?? '') === 'طبق قرارداد') {
            $benefitMap['EID_BONUS'][1] = $eidMethod === 'ANNUAL' ? 'سالانه' : 'در تسویه نهایی';
        }
    }
    if ($sevMethod !== 'MONTHLY') {
        $benefitMap['SEVERANCE'][0] = 0.0;
        if (($benefitMap['SEVERANCE'][1] ?? '') === 'طبق قرارداد') {
            $benefitMap['SEVERANCE'][1] = $sevMethod === 'ANNUAL' ? 'سالانه' : 'در پایان قرارداد';
        }
    }

    $totalDaily = 0.0;
    $totalMonthly = 0.0;

    foreach ($rowsMeta as $meta) {
        $code = $meta['component_code'];
        if ($code === 'BASE_WAGE') {
            $dailyAmt = $baseDaily;
            $monthlyAmt = $baseMonthly;
            $extra = $baseStatus;
            $method = $calcMethod;
            $inputVal = $type === 'HOURLY' ? $hourly : ($type === 'TEMPORARY' || ($type === 'PERMANENT' && $monthlyFixed <= 0) ? $daily : $monthlyFixed);
        } else {
            $monthlyAmt = $benefitMap[$code][0] ?? 0.0;
            $dailyAmt = $daysBasis > 0 ? ($monthlyAmt / $daysBasis) : 0.0;
            $extra = $benefitMap[$code][1] ?? 'طبق قرارداد';
            $method = 'FIXED_AMOUNT';
            $inputVal = $monthlyAmt;
        }
        $totalDaily += $dailyAmt;
        $totalMonthly += $monthlyAmt;
        $out[] = [
            'row_no' => $meta['row_no'],
            'component_code' => $code,
            'title_fa' => $meta['title_fa'],
            'extra_info_fa' => $extra,
            'calc_method' => $method,
            'input_value' => $inputVal,
            'daily_amount' => round($dailyAmt, 2),
            'monthly_amount' => round($monthlyAmt, 2),
            'enabled' => 1,
            'pdf_display' => 1,
        ];
    }

    return [
        'rows' => $out,
        'total_daily' => round($totalDaily, 2),
        'total_monthly' => round($totalMonthly, 2),
        'model' => $type,
    ];
}

/** @param list<array<string,mixed>> $rows */
function p360hr_wage_table_html(array $rows, float $totalDaily, float $totalMonthly): string
{
    $html = '<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse:collapse;width:100%;font-family:Vazirmatn,Tahoma,sans-serif;font-size:11pt">';
    $html .= '<thead><tr>';
    $html .= '<th>ردیف</th><th>موضوع</th><th>اطلاعات اضافه</th><th>مبلغ روزانه</th><th>مبلغ ماهانه</th>';
    $html .= '</tr></thead><tbody>';
    foreach ($rows as $r) {
        $html .= '<tr>';
        $html .= '<td style="text-align:center">' . (int)$r['row_no'] . '</td>';
        $html .= '<td>' . htmlspecialchars((string)$r['title_fa'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td style="text-align:center">' . htmlspecialchars((string)($r['extra_info_fa'] ?? '—'), ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td style="text-align:left">' . htmlspecialchars(p360hr_money_fa(isset($r['daily_amount']) ? (float)$r['daily_amount'] : null), ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td style="text-align:left">' . htmlspecialchars(p360hr_money_fa(isset($r['monthly_amount']) ? (float)$r['monthly_amount'] : null), ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '</tr>';
    }
    $html .= '<tr><td></td><td><strong>جمع کل حقوق و مزایا</strong></td><td></td>';
    $html .= '<td style="text-align:left"><strong>' . htmlspecialchars(p360hr_money_fa($totalDaily), ENT_QUOTES, 'UTF-8') . '</strong></td>';
    $html .= '<td style="text-align:left"><strong>' . htmlspecialchars(p360hr_money_fa($totalMonthly), ENT_QUOTES, 'UTF-8') . '</strong></td></tr>';
    $html .= '</tbody></table>';
    return $html;
}
