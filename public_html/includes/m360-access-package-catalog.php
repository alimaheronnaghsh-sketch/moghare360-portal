<?php
declare(strict_types=1);

/**
 * Business access package definitions + Owner-approved personnel mapping.
 * Recommendations only — never grant effective access.
 */

require_once __DIR__ . '/m360-access-matrix-catalog.php';

/** Locked Layer A — personnel profile (own-record). */
function m360_access_package_locked_profile_keys(): array
{
    return [
        'hr.self.cartable.VIEW',
        'hr.self.dossier.VIEW',
        'hr.self.payslips.VIEW',
        'hr.self.contracts.VIEW',
        'hr.self.attendance.VIEW',
        'hr.self.documents.VIEW',
        'hr.self.password.EDIT',
        'hr.self.requests.VIEW',
    ];
}

/**
 * @return array<string, array{
 *   package_key:string,title_fa:string,purpose_fa:string,scope:string,authority:string,
 *   sensitive:list<string>,permissions:list<string>,assignable_to_users:bool
 * }>
 */
function m360_access_package_definitions(): array
{
    $wsView = [
        'workshop.operations.home.view', 'workshop.periodic_service.view', 'workshop.inspection.view',
        'workshop.electrical_options.view', 'workshop.mechanical.view', 'workshop.jobcard.view',
    ];
    $wsAssign = [
        'workshop.assign.mechanical', 'workshop.assign.electrical', 'workshop.assign.options',
        'workshop.assign.periodic_service', 'workshop.assign.inspection_mechanical', 'workshop.assign.inspection_electrical',
    ];
    $wsDiag = [
        'workshop.diagnosis_report.create', 'workshop.diagnosis_report.approve', 'workshop.diagnosis_report.return',
    ];
    $wsParts = [
        'workshop.part_request.create', 'workshop.part_request.view_status', 'workshop.part_request.technical_approve',
        'workshop.part_request.reject_return', 'workshop.part_issue.allocate', 'workshop.part_issue.receive_confirm',
        'workshop.billable_parts.view',
    ];
    $wsWork = [
        'workshop.work_report.create', 'workshop.work_report.approve', 'workshop.work_report.return',
        'workshop.service_line.create_no_price',
    ];
    $wsIc = [
        'workshop.internal_consumable.create', 'workshop.internal_consumable.approve', 'workshop.internal_consumable.return',
        'workshop.internal_consumable.cost_view', 'workshop.internal_consumable.reversal_request', 'workshop.internal_consumable.reversal_approve',
    ];
    $wsQc = [
        'workshop.qc.queue.view', 'workshop.qc.result.create', 'workshop.qc.approve_return',
        'workshop.delivery.queue.view', 'workshop.delivery.final_confirm',
    ];

    $pkgs = [];
    $add = static function (
        array &$pkgs,
        string $key,
        string $title,
        string $purpose,
        string $scope,
        string $authority,
        array $perms,
        array $sensitive = [],
        bool $assignable = true
    ): void {
        $pkgs[$key] = [
            'package_key' => $key,
            'title_fa' => $title,
            'purpose_fa' => $purpose,
            'scope' => $scope,
            'authority' => $authority,
            'sensitive' => array_values($sensitive),
            'permissions' => array_values(array_unique($perms)),
            'assignable_to_users' => $assignable,
        ];
    };

    $add($pkgs, 'BUSINESS_OWNER_GENERAL_MANAGER', 'مالک کسب‌وکار / مدیرکل',
        'نظارت کسب‌وکار و داشبوردهای مدیریتی — بدون مالک سیستم و بدون اعتماد به شناسه عددی',
        'COMPANY', 'REVIEW',
        array_merge(
            ['central.home.VIEW', 'dashboard.hr.payroll.view', 'dashboard.hr.attendance.view', 'dashboard.hr.unit_performance.view',
                'dashboard.finance.cash.view', 'dashboard.finance.term_credit_debt.view', 'dashboard.finance.purchase_sales.view',
                'dashboard.finance.collections_receivables.view', 'dashboard.reception.time_inout.view',
                'dashboard.reception.return_same_fault.view', 'dashboard.reception.return_service_quality.view',
                'dashboard.services.periodic.view', 'dashboard.services.engine.view', 'dashboard.services.transmission.view',
                'dashboard.services.suspension.view', 'dashboard.services.electrical.view', 'dashboard.services.options.view',
                'dashboard.services.prepurchase_inspection.view', 'dashboard.inventory_purchase.view',
                'workshop.operations.home.view', 'workshop.jobcard.view'],
            $wsView
        ),
        ['dashboard.hr.payroll.view', 'finance.payroll.REVIEW', 'finance.payroll.APPROVE']
    );

    $add($pkgs, 'INTERNAL_MANAGER_HALL_MANAGER', 'مدیر داخلی / مدیر سالن',
        'دریافت کار از پذیرش، تخصیص، بررسی تشخیص/انجام کار، هماهنگی QC و آمادگی عملیاتی — بدون تسویه مالی و حقوق',
        'WORKSHOP', 'APPROVE',
        array_merge($wsView, $wsAssign, $wsDiag, [
            'workshop.part_request.technical_approve', 'workshop.work_report.approve', 'workshop.work_report.return',
            'workshop.qc.queue.view', 'workshop.delivery.queue.view', 'workshop.operations.home.view',
        ]),
        ['finance.reports.VIEW', 'access.matrix.manage', 'hr.payroll.admin.VIEW']
    );

    $add($pkgs, 'RECEPTION_OPERATOR', 'پذیرشگر',
        'گردش کامل پذیرش وقتی مسیرها ENFORCED باشند',
        'UNIT', 'EXECUTE',
        ['reception.board.VIEW', 'reception.intake.CREATE', 'reception.jobcard.EDIT', 'crm.customer.search.VIEW', 'crm.customer.profile.VIEW'],
        []
    );

    $add($pkgs, 'DELIVERY_OPERATOR', 'مسئول ترخیص و تحویل',
        'صف تحویل، مدارک تحویل، پذیرش مشتری و ترخیص نهایی طبق دروازه‌های موجود',
        'WORKSHOP', 'EXECUTE',
        ['customer.delivery.board.VIEW', 'workshop.delivery.queue.view', 'workshop.delivery.final_confirm', 'workshop.jobcard.view'],
        []
    );

    $add($pkgs, 'CUSTOMER_PROFILE_AGENT', 'کارشناس ارتباط با مشتری',
        'پروفایل مشتری و تماس مجاز — بدون خروجی انبوه یا تبلیغات',
        'COMPANY', 'EXECUTE',
        ['crm.customer.profile.VIEW', 'crm.customer.search.VIEW', 'crm.followup.VIEW', 'customer.phone.access.VIEW'],
        ['customer.phone.access.VIEW']
    );

    $add($pkgs, 'CUSTOMER_PHONE_CAMPAIGN', 'دسترسی تبلیغات پیامکی مشتریان',
        'بسته بسیار حساس — فقط با تأیید صریح مالک؛ مستقل از ارتباط با مشتری',
        'COMPANY', 'ADMINISTER',
        ['customer.phone.access.VIEW', 'marketing.campaign.VIEW', 'marketing.campaign.EDIT'],
        ['customer.phone.access.VIEW', 'marketing.campaign.VIEW', 'marketing.campaign.EDIT'],
        false
    );

    $add($pkgs, 'WORKSHOP_QC', 'کنترل کیفیت',
        'فقط گردش QC دریافتی از مدیر سالن و برگشت به او',
        'WORKSHOP', 'APPROVE',
        ['workshop.qc.queue.view', 'workshop.qc.result.create', 'workshop.qc.approve_return', 'workshop.jobcard.view', 'workshop.operations.home.view'],
        []
    );

    $add($pkgs, 'SENIOR_MECHANIC', 'مکانیک ارشد',
        'کار تخصیص‌یافته مکانیک، تشخیص، گزارش کار، درخواست قطعه، مصرف داخلی — بدون قیمت‌گذاری مشتری/مالی',
        'ASSIGNED_WORK', 'SUBMIT',
        ['workshop.mechanical.view', 'workshop.periodic_service.view', 'workshop.inspection.view', 'workshop.jobcard.view',
            'workshop.diagnosis_report.create', 'workshop.work_report.create', 'workshop.service_line.create_no_price',
            'workshop.part_request.create', 'workshop.part_issue.receive_confirm', 'workshop.internal_consumable.create'],
        ['workshop.internal_consumable.cost_view', 'finance.reports.VIEW']
    );

    $add($pkgs, 'MECHANIC', 'مکانیک',
        'اجرای کار مکانیکی/دوره‌ای/کارشناسی تخصیص‌یافته',
        'ASSIGNED_WORK', 'EXECUTE',
        ['workshop.mechanical.view', 'workshop.periodic_service.view', 'workshop.inspection.view', 'workshop.jobcard.view',
            'workshop.diagnosis_report.create', 'workshop.work_report.create', 'workshop.part_request.create',
            'workshop.part_issue.receive_confirm', 'workshop.internal_consumable.create'],
        []
    );

    $add($pkgs, 'SENIOR_ELECTRICIAN', 'برق‌کار ارشد',
        'کار برق و تشخیص مجاز — بدون مدیریت خودکار آپشن',
        'ASSIGNED_WORK', 'SUBMIT',
        ['workshop.electrical_options.view', 'workshop.inspection.view', 'workshop.jobcard.view',
            'workshop.diagnosis_report.create', 'workshop.work_report.create', 'workshop.part_request.create',
            'workshop.part_issue.receive_confirm', 'workshop.internal_consumable.create'],
        []
    );

    $add($pkgs, 'ELECTRICIAN', 'برق‌کار',
        'اجرای کار برق تخصیص‌یافته',
        'ASSIGNED_WORK', 'EXECUTE',
        ['workshop.electrical_options.view', 'workshop.jobcard.view', 'workshop.diagnosis_report.create',
            'workshop.work_report.create', 'workshop.part_request.create', 'workshop.part_issue.receive_confirm'],
        []
    );

    $add($pkgs, 'ASSISTANT_ELECTRICIAN', 'کمک برق‌کار',
        'فقط کار تخصیص‌یافته — بدون اختیار تأیید',
        'ASSIGNED_WORK', 'EXECUTE',
        ['workshop.electrical_options.view', 'workshop.jobcard.view', 'workshop.work_report.create', 'workshop.part_issue.receive_confirm'],
        []
    );

    $add($pkgs, 'OPTIONS_TECHNICIAN', 'مسئول آپشن',
        'اجرای کارهای آپشن تخصیص‌یافته',
        'ASSIGNED_WORK', 'EXECUTE',
        ['workshop.electrical_options.view', 'workshop.jobcard.view', 'workshop.work_report.create',
            'workshop.part_request.create', 'workshop.part_issue.receive_confirm'],
        []
    );

    $add($pkgs, 'PURCHASE_WAREHOUSE_MANAGER', 'مدیر خرید و انبار',
        'نظارت خرید و انبار — دید مالی انبار فقط با مجوز مستقل',
        'COMPANY', 'APPROVE',
        ['purchase.request.VIEW', 'purchase.request.CREATE', 'purchase.request.SUBMIT', 'purchase.request.APPROVE',
            'purchase.order.EDIT', 'purchase.receive.EDIT', 'inventory.stock.VIEW', 'inventory.issue.CREATE',
            'workshop.part_issue.allocate', 'workshop.part_request.view_status', 'dashboard.inventory_purchase.view'],
        ['purchase.financial.VIEW', 'purchase.financial.EDIT', 'inventory.financial.VIEW', 'workshop.internal_consumable.cost_view']
    );

    $add($pkgs, 'WAREHOUSE_SUPERVISOR', 'سرپرست انبار',
        'صف‌های انبار، تخصیص، صدور، عملیات مصرف داخلی — بدون پرداخت تأمین‌کننده',
        'UNIT', 'APPROVE',
        ['inventory.stock.VIEW', 'inventory.issue.CREATE', 'workshop.part_issue.allocate',
            'workshop.part_request.view_status', 'workshop.internal_consumable.approve', 'workshop.internal_consumable.return'],
        ['finance.supplier_payment.EDIT', 'purchase.financial.VIEW']
    );

    $add($pkgs, 'WAREHOUSE_OPERATOR', 'انباردار',
        'عملیات اجرایی انبار',
        'UNIT', 'EXECUTE',
        ['inventory.stock.VIEW', 'inventory.issue.CREATE', 'workshop.part_issue.allocate',
            'workshop.part_issue.receive_confirm'],
        []
    );

    $add($pkgs, 'ASSISTANT_WAREHOUSE_OPERATOR', 'کمک‌انباردار',
        'محدوده عملیاتی محدود — بدون تأیید',
        'ASSIGNED_WORK', 'EXECUTE',
        ['inventory.stock.VIEW', 'workshop.part_issue.receive_confirm', 'inventory.issue.CREATE'],
        ['purchase.financial.VIEW', 'inventory.financial.VIEW', 'workshop.internal_consumable.cost_view']
    );

    $add($pkgs, 'FINANCE_ADMIN_MANAGER', 'مدیر مالی و اداری',
        'بازبینی و تأیید مالی/اداری طبق مجوزهای ENFORCED — بدون مالک سیستم',
        'COMPANY', 'APPROVE',
        ['finance.customer_payment.VIEW', 'finance.customer_payment.EDIT', 'finance.supplier_payment.VIEW',
            'finance.supplier_payment.EDIT', 'finance.petty_cash.VIEW', 'finance.external_settle.VIEW',
            'finance.external_settle.APPROVE', 'finance.reports.VIEW', 'finance.payroll.REVIEW', 'finance.payroll.APPROVE',
            'dashboard.finance.cash.view', 'dashboard.finance.term_credit_debt.view',
            'dashboard.finance.purchase_sales.view', 'dashboard.finance.collections_receivables.view'],
        ['finance.payroll.APPROVE', 'finance.supplier_payment.EDIT', 'access.matrix.manage', 'admin.users.USER_MANAGE']
    );

    $add($pkgs, 'FINANCE_ADMIN_SUPERVISOR', 'سرپرست مالی و اداری',
        'بازبینی و دسترسی عملیاتی کنترل‌شده — maker-checker مانع تأیید خود',
        'COMPANY', 'REVIEW',
        ['finance.customer_payment.VIEW', 'finance.supplier_payment.VIEW', 'finance.petty_cash.VIEW',
            'finance.external_settle.VIEW', 'finance.reports.VIEW', 'finance.payroll.REVIEW',
            'dashboard.finance.cash.view', 'dashboard.finance.collections_receivables.view'],
        ['finance.payroll.APPROVE', 'finance.supplier_payment.EDIT']
    );

    $add($pkgs, 'FINANCE_ADMIN_OPERATOR', 'کارشناس مالی و اداری',
        'ثبت/ارسال بدون تأیید خودکار',
        'UNIT', 'SUBMIT',
        ['finance.customer_payment.VIEW', 'finance.customer_payment.EDIT', 'finance.petty_cash.VIEW',
            'finance.petty_cash.EDIT', 'finance.external_settle.VIEW', 'dashboard.finance.cash.view'],
        ['finance.payroll.APPROVE', 'finance.supplier_payment.EDIT']
    );

    $add($pkgs, 'LOGISTICS_PROCUREMENT_OPERATOR', 'کارشناس تدارکات و خدمات بیرونی',
        'هماهنگی، بارنامه، پیگیری خدمات بیرونی، ارسال تسویه تنخواه — بدون تأیید مالی',
        'COMPANY', 'SUBMIT',
        ['logistics.external.VIEW', 'logistics.external.CREATE', 'logistics.external.EDIT',
            'logistics.freight.VIEW', 'logistics.settle.SUBMIT', 'finance.petty_cash.VIEW'],
        ['finance.external_settle.APPROVE', 'finance.supplier_payment.EDIT']
    );

    $add($pkgs, 'DIGITAL_COMMUNICATIONS', 'ارتباطات دیجیتال',
        'وظایف محتوا/ارتباط — بدون کمپین پیامکی خودکار، بدون پذیرش/مالی',
        'COMPANY', 'EXECUTE',
        ['crm.followup.VIEW', 'crm.customer.profile.VIEW'],
        ['customer.phone.access.VIEW', 'marketing.campaign.VIEW', 'marketing.campaign.EDIT',
            'reception.board.VIEW', 'finance.reports.VIEW']
    );

    $add($pkgs, 'GENERAL_SERVICES', 'خدمات عمومی',
        'خدمات پشتیبانی/کارواش — بدون تأیید فنی یا مالی',
        'UNIT', 'EXECUTE',
        ['central.home.VIEW', 'workshop.operations.home.view'],
        ['workshop.diagnosis_report.approve', 'finance.reports.VIEW', 'workshop.qc.approve_return']
    );

    $add($pkgs, 'SYSTEM_PRODUCT_OWNER', 'مالک محصول و مدیر سامانه',
        'توصیفی برای M360-100001 — اختیار مالک سیستم ذاتی و تأییدشده در DB است؛ تخصیص بسته ایجاد اختیار نمی‌کند',
        'COMPANY', 'ADMINISTER',
        ['access.matrix.manage', 'access.matrix.apply_direct', 'access.audit.view', 'admin.users.USER_MANAGE',
            'admin.roles.PERMISSION_MANAGE'],
        ['access.matrix.manage', 'access.matrix.apply_direct', 'admin.users.USER_MANAGE', 'admin.roles.PERMISSION_MANAGE'],
        false
    );

    // Limited technical part recognition (secondary for assistant warehouse) — no finance
    $add($pkgs, 'LIMITED_PART_RECOGNITION', 'شناسایی محدود قطعه فنی',
        'قابلیت فرعی محدود برای کمک‌انباردار — بدون دید مالی',
        'ASSIGNED_WORK', 'VIEW',
        ['workshop.part_request.create', 'workshop.jobcard.view', 'inventory.stock.VIEW'],
        ['workshop.internal_consumable.cost_view', 'purchase.financial.VIEW', 'inventory.financial.VIEW']
    );

    $add($pkgs, 'HALL_DEPUTY_ASSISTANT', 'جانشین / دستیار مدیریت سالن',
        'مسئولیت فرعی دستیار مدیرکل / جانشین مدیر سالن — بدون تسویه مالی',
        'WORKSHOP', 'REVIEW',
        array_merge(['workshop.operations.home.view', 'workshop.jobcard.view'], array_slice($wsView, 0, 4), [
            'workshop.assign.mechanical', 'workshop.assign.electrical', 'workshop.work_report.return',
        ]),
        ['finance.reports.VIEW', 'access.matrix.manage']
    );

    return $pkgs;
}

/**
 * Owner-approved personnel → package mapping (recommendation only).
 *
 * @return array<string, array{primary:list<string>,secondary:list<string>,reason_fa:string,sensitive_pending:list<string>}>
 */
function m360_access_personnel_package_map(): array
{
    return [
        'M360-1007' => [
            'primary' => ['BUSINESS_OWNER_GENERAL_MANAGER'],
            'secondary' => [],
            'reason_fa' => 'مالک کسب‌وکار / مدیرکل — نظارت مدیریتی؛ مالک سیستم نیست',
            'sensitive_pending' => ['CUSTOMER_PHONE_CAMPAIGN'],
        ],
        'M360-1008' => [
            'primary' => ['GENERAL_SERVICES'],
            'secondary' => [],
            'reason_fa' => 'خدمات عمومی بر اساس واحد و عنوان خدمات',
            'sensitive_pending' => [],
        ],
        'M360-1009' => [
            'primary' => ['PURCHASE_WAREHOUSE_MANAGER'],
            'secondary' => [],
            'reason_fa' => 'سرپرست خرید و انبار — دید مالی انبار نیازمند تأیید مستقل مالک',
            'sensitive_pending' => ['purchase.financial.VIEW', 'inventory.financial.VIEW'],
        ],
        'M360-1010' => [
            'primary' => ['WAREHOUSE_OPERATOR'],
            'secondary' => [],
            'reason_fa' => 'انباردار عملیاتی',
            'sensitive_pending' => [],
        ],
        'M360-1011' => [
            'primary' => ['SENIOR_MECHANIC'],
            'secondary' => [],
            'reason_fa' => 'مکانیک ارشد — بدون قیمت‌گذاری مشتری',
            'sensitive_pending' => [],
        ],
        'M360-1012' => [
            'primary' => ['DIGITAL_COMMUNICATIONS'],
            'secondary' => [],
            'reason_fa' => 'ارتباطات دیجیتال — کمپین پیامکی خودکار داده نمی‌شود',
            'sensitive_pending' => ['CUSTOMER_PHONE_CAMPAIGN'],
        ],
        'M360-1013' => [
            'primary' => ['FINANCE_ADMIN_SUPERVISOR'],
            'secondary' => [],
            'reason_fa' => 'سرپرست مالی/اداری — maker-checker برای جلوگیری از تأیید خود',
            'sensitive_pending' => ['finance.payroll.APPROVE'],
        ],
        'M360-1014' => [
            'primary' => ['MECHANIC'],
            'secondary' => [],
            'reason_fa' => 'مکانیک — کار تخصیص‌یافته',
            'sensitive_pending' => [],
        ],
        'M360-1015' => [
            'primary' => ['ELECTRICIAN'],
            'secondary' => [],
            'reason_fa' => 'برق‌کار — کار تخصیص‌یافته',
            'sensitive_pending' => [],
        ],
        'M360-1016' => [
            'primary' => ['LOGISTICS_PROCUREMENT_OPERATOR'],
            'secondary' => [],
            'reason_fa' => 'کارشناس تدارکات — بدون تأیید مالی',
            'sensitive_pending' => [],
        ],
        'M360-1017' => [
            'primary' => ['MECHANIC'],
            'secondary' => [],
            'reason_fa' => 'مکانیک — کار تخصیص‌یافته',
            'sensitive_pending' => [],
        ],
        'M360-1018' => [
            'primary' => ['GENERAL_SERVICES'],
            'secondary' => [],
            'reason_fa' => 'کارواش / خدمات عمومی',
            'sensitive_pending' => [],
        ],
        'M360-1019' => [
            'primary' => ['INTERNAL_MANAGER_HALL_MANAGER'],
            'secondary' => ['HALL_DEPUTY_ASSISTANT'],
            'reason_fa' => 'مدیر داخلی / دستیار مدیرکل با مسئولیت جانشینی سالن',
            'sensitive_pending' => [],
        ],
        'M360-1020' => [
            'primary' => ['FINANCE_ADMIN_OPERATOR'],
            'secondary' => [],
            'reason_fa' => 'کارشناس مالی/اداری — ثبت بدون تأیید خودکار',
            'sensitive_pending' => [],
        ],
        'M360-1021' => [
            'primary' => ['ASSISTANT_WAREHOUSE_OPERATOR'],
            'secondary' => ['LIMITED_PART_RECOGNITION'],
            'reason_fa' => 'کمک‌انباردار + شناسایی محدود قطعه — بدون دید مالی',
            'sensitive_pending' => [],
        ],
        'M360-1022' => [
            'primary' => ['ASSISTANT_ELECTRICIAN'],
            'secondary' => [],
            'reason_fa' => 'کمک برق‌کار — فقط کار تخصیص‌یافته',
            'sensitive_pending' => [],
        ],
        'M360-1023' => [
            'primary' => ['RECEPTION_OPERATOR', 'WORKSHOP_QC', 'DELIVERY_OPERATOR'],
            'secondary' => [],
            'reason_fa' => 'سه مسئولیت تأییدشده پذیرش+QC+ترخیص — هشدار تفکیک وظایف اجباری',
            'sensitive_pending' => [],
        ],
        'M360-1024' => [
            'primary' => ['MECHANIC'],
            'secondary' => [],
            'reason_fa' => 'مکانیک — کار تخصیص‌یافته',
            'sensitive_pending' => [],
        ],
        'M360-1025' => [
            'primary' => ['MECHANIC'],
            'secondary' => [],
            'reason_fa' => 'مکانیک — کار تخصیص‌یافته',
            'sensitive_pending' => [],
        ],
        'M360-1026' => [
            'primary' => ['FINANCE_ADMIN_MANAGER'],
            'secondary' => [],
            'reason_fa' => 'مدیر مالی/اداری — بدون مالک سیستم',
            'sensitive_pending' => ['access.matrix.manage'],
        ],
        'M360-1027' => [
            'primary' => ['WAREHOUSE_SUPERVISOR'],
            'secondary' => [],
            'reason_fa' => 'سرپرست انبار — بدون پرداخت تأمین‌کننده',
            'sensitive_pending' => [],
        ],
        'M360-100001' => [
            'primary' => ['SYSTEM_PRODUCT_OWNER'],
            'secondary' => [],
            'reason_fa' => 'مالک محصول/سامانه — اختیار ذاتی Owner؛ بسته فقط توصیفی است',
            'sensitive_pending' => [],
        ],
        'M360-1029' => [
            'primary' => ['CUSTOMER_PROFILE_AGENT'],
            'secondary' => ['DIGITAL_COMMUNICATIONS'],
            'reason_fa' => 'ارتباط با مشتری + ارتباطات دیجیتال — کمپین پیامکی نیازمند تأیید مالک',
            'sensitive_pending' => ['CUSTOMER_PHONE_CAMPAIGN'],
        ],
        'M360-1030' => [
            'primary' => ['LOGISTICS_PROCUREMENT_OPERATOR'],
            'secondary' => [],
            'reason_fa' => 'کارشناس تدارکات',
            'sensitive_pending' => [],
        ],
        'M360-1031' => [
            'primary' => ['SENIOR_ELECTRICIAN'],
            'secondary' => [],
            'reason_fa' => 'برق‌کار ارشد',
            'sensitive_pending' => [],
        ],
    ];
}
