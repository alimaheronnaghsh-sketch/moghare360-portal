<?php
declare(strict_types=1);

/**
 * Task-oriented operational permission catalog for the personnel access matrix.
 * Keys are stable English identifiers; UI uses Persian titles.
 *
 * @return list<array{
 *   permission_key:string,module_key:string,page_key:string,action_key:string,
 *   title_fa:string,description:string,risk_level:string,
 *   is_base_self_service:int,contains_financial_data:int,contains_private_hr_data:int,
 *   requires_maker_checker:int,is_owner_only:int,sort_order:int,
 *   route_patterns:list<string>,
 *   group_key:string,group_title_fa:string,
 *   enforcement_state:string,is_assignable:int
 * }>
 */
function m360_access_matrix_catalog_definitions(): array
{
    $rows = [];
    $add = static function (
        array &$rows,
        string $key,
        string $module,
        string $page,
        string $action,
        string $title,
        string $desc,
        array $opts = []
    ): void {
        $enforcement = strtoupper((string)($opts['enforcement'] ?? 'NOT_YET_ENFORCED'));
        if (!in_array($enforcement, ['ENFORCED', 'NOT_YET_ENFORCED', 'MODULE_LOCAL', 'LEGACY_QUARANTINED'], true)) {
            $enforcement = 'NOT_YET_ENFORCED';
        }
        $assignable = array_key_exists('assignable', $opts)
            ? (!empty($opts['assignable']) ? 1 : 0)
            : ($enforcement === 'ENFORCED' ? 1 : 0);
        $rows[] = [
            'permission_key' => $key,
            'module_key' => $module,
            'page_key' => $page,
            'action_key' => $action,
            'title_fa' => $title,
            'description' => $desc,
            'risk_level' => (string)($opts['risk'] ?? 'MEDIUM'),
            'is_base_self_service' => !empty($opts['base']) ? 1 : 0,
            'contains_financial_data' => !empty($opts['fin']) ? 1 : 0,
            'contains_private_hr_data' => !empty($opts['hr']) ? 1 : 0,
            'requires_maker_checker' => !empty($opts['mc']) ? 1 : 0,
            'is_owner_only' => !empty($opts['owner']) ? 1 : 0,
            'sort_order' => (int)($opts['sort'] ?? 100),
            'route_patterns' => $opts['routes'] ?? [],
            'group_key' => (string)($opts['group'] ?? $page),
            'group_title_fa' => (string)($opts['group_fa'] ?? ''),
            'enforcement_state' => $enforcement,
            'is_assignable' => $assignable,
        ];
    };

    // —— Personnel profile (locked base self-service) ——
    $base = [
        ['hr.self.cartable.VIEW', 'میز کار من', 'peopleos360/my-cartable.php'],
        ['hr.self.dossier.VIEW', 'پرونده پرسنلی من', 'peopleos360/my-dossier.php'],
        ['hr.self.payslips.VIEW', 'فیش‌های حقوقی من', 'peopleos360/my-payslips.php'],
        ['hr.self.contracts.VIEW', 'قراردادهای من', 'peopleos360/my-contracts.php'],
        ['hr.self.attendance.VIEW', 'حضور و غیاب من', 'peopleos360/my-attendance.php'],
        ['hr.self.documents.VIEW', 'مدارک فردی من', 'peopleos360/my-documents.php'],
        ['hr.self.password.EDIT', 'تغییر رمز عبور', 'peopleos360/my-password.php'],
        ['hr.self.requests.VIEW', 'درخواست‌های من', 'peopleos360/my-requests.php'],
        ['hr.self.benefits.VIEW', 'عیدی، سنوات و مزایای من', 'peopleos360/my-benefits.php'],
        ['hr.self.occ_med.VIEW', 'گزارش طب کار من', 'peopleos360/my-dossier.php'],
    ];
    $i = 10;
    foreach ($base as [$k, $t, $r]) {
        $add($rows, $k, 'hr_self', 'self', 'VIEW', $t, 'پروفایل پرسنلی — فقط رکورد خود', [
            'base' => 1, 'hr' => 1, 'risk' => 'LOW', 'sort' => $i++, 'routes' => [$r],
            'enforcement' => 'ENFORCED', 'assignable' => 0, 'group' => 'base', 'group_fa' => 'پروفایل پرسنلی',
        ]);
    }

    // Attendance employee requests (create only — not base approval authority)
    foreach ([
        ['peopleos.attendance.leave_request.create', 'CREATE', 'درخواست مرخصی',
            'درخواست مرخصی از جدول ماهانه حضور — فقط برای خود؛ بدون تأیید خودکار',
            ['peopleos360/my-attendance.php', 'peopleos360/leave-requests.php']],
        ['peopleos.attendance.overtime_request.create', 'CREATE', 'درخواست اضافه‌کاری',
            'درخواست اضافه‌کاری از جدول ماهانه حضور — فقط برای خود؛ بدون ایجاد اضافه‌کار قابل‌پرداخت مستقیم',
            ['peopleos360/my-attendance.php', 'peopleos360/overtime-requests.php']],
        ['peopleos.attendance.correction_request.create', 'CREATE', 'درخواست اصلاح تردد',
            'اصلاح تردد برای ورود/خروج ثبت‌نشده یا اشتباه — فقط درخواست؛ اعمال پس از تأیید maker-checker',
            ['peopleos360/my-attendance.php']],
    ] as $idx => [$k, $act, $t, $d, $routes]) {
        $add($rows, $k, 'hr_self', 'attendance_request', $act, $t, $d, [
            'hr' => 1, 'mc' => 1, 'risk' => 'MEDIUM', 'sort' => 40 + $idx, 'routes' => $routes,
            'enforcement' => 'NOT_YET_ENFORCED', 'assignable' => 0,
            'group' => 'att_req', 'group_fa' => 'درخواست‌های حضور و غیاب',
        ]);
    }

    // —— Access administration ——
    $add($rows, 'access.matrix.manage', 'access', 'matrix', 'PERMISSION_MANAGE', 'مدیریت ماتریس دسترسی پرسنل', 'مشاهده و پیشنهاد/اعمال ماتریس', ['mc' => 1, 'sort' => 20, 'routes' => ['erp-personnel-access-matrix.php', 'erp-user-role-admin.php'], 'enforcement' => 'ENFORCED', 'group' => 'access', 'group_fa' => 'دسترسی‌ها']);
    $add($rows, 'access.matrix.apply_direct', 'access', 'matrix', 'APPROVE', 'اعمال مستقیم ماتریس', 'اعمال بدون تأیید مالک', ['owner' => 1, 'mc' => 1, 'sort' => 21, 'enforcement' => 'ENFORCED', 'group' => 'access', 'group_fa' => 'دسترسی‌ها']);
    $add($rows, 'access.audit.view', 'access', 'audit', 'AUDIT_VIEW', 'مشاهده تاریخچه دسترسی', 'گزارش تغییرات دسترسی', ['sort' => 22, 'routes' => ['erp-access-change-history.php'], 'enforcement' => 'ENFORCED', 'group' => 'access', 'group_fa' => 'دسترسی‌ها']);

    // —— Management dashboards (Owner taxonomy; view-only concepts) ——
    $add($rows, 'central.home.VIEW', 'management', 'home', 'VIEW', 'خانه پرسنل / محصول', 'ورود به فضای مرکزی', [
        'risk' => 'LOW', 'sort' => 30, 'routes' => ['erp-staff-home.php', 'erp-product-home.php'],
        'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'mgmt_home', 'group_fa' => 'داشبورد مدیریتی',
    ]);

    $mgmtSort = 50;
    foreach ([
        ['dashboard.hr.payroll.view', 'حقوق و دستمزد', 'داشبورد منابع انسانی — مشاهده خلاصه حقوق (نه ویرایش فیش)'],
        ['dashboard.hr.attendance.view', 'حضور و غیاب', 'داشبورد منابع انسانی — مشاهده حضور واحدها'],
        ['dashboard.hr.unit_performance.view', 'عملکرد هر واحد', 'داشبورد منابع انسانی — عملکرد واحدها'],
    ] as [$k, $t, $d]) {
        $add($rows, $k, 'management', 'dash_hr', 'VIEW', $t, $d, [
            'hr' => 1, 'sort' => $mgmtSort++, 'routes' => ['erp-hr-dashboard.php', 'erp-management-dashboard.php'],
            'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'dash_hr', 'group_fa' => 'داشبورد منابع انسانی',
        ]);
    }
    foreach ([
        ['dashboard.finance.cash.view', 'صندوق', 'مشاهده داشبورد صندوق — بدون ثبت/تأیید پرداخت'],
        ['dashboard.finance.term_credit_debt.view', 'بستانکاری/بدهکاری مدت‌دار', 'مطالبات و بدهی‌های مدت‌دار، سررسید و معوق'],
        ['dashboard.finance.purchase_sales.view', 'خرید/فروش', 'خلاصه عملکرد خرید و فروش — بدون ویرایش تراکنش'],
        ['dashboard.finance.collections_receivables.view', 'وصول و مطالبات', 'وصول، مانده مطالبات، سررسید و وضعیت وصول'],
    ] as [$k, $t, $d]) {
        $add($rows, $k, 'management', 'dash_finance', 'VIEW', $t, $d, [
            'fin' => 1, 'sort' => $mgmtSort++, 'routes' => ['erp-management-dashboard.php', 'erp-finance-preview-workbench.php'],
            'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'dash_finance', 'group_fa' => 'داشبورد مالی',
        ]);
    }
    foreach ([
        ['dashboard.reception.time_inout.view', 'ورودی و خروجی زمانی', 'داشبورد پذیرش/ترخیص — ورودی و خروجی زمانی'],
        ['dashboard.reception.return_same_fault.view', 'برگشت بابت همان خرابی', 'داشبورد پذیرش/ترخیص — برگشت بابت همان خرابی'],
        ['dashboard.reception.return_service_quality.view', 'برگشت بابت کیفیت خدمات', 'داشبورد پذیرش/ترخیص — برگشت بابت کیفیت خدمات'],
    ] as [$k, $t, $d]) {
        $add($rows, $k, 'management', 'dash_reception', 'VIEW', $t, $d, [
            'sort' => $mgmtSort++, 'routes' => ['erp-reception-board.php', 'erp-delivery-control.php'],
            'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'dash_reception', 'group_fa' => 'داشبورد پذیرش/ترخیص',
        ]);
    }
    foreach ([
        ['dashboard.services.periodic.view', 'سرویس‌های دوره‌ای', 'داشبورد تحلیلی سرویس‌های دوره‌ای — نه اجرای فنی'],
        ['dashboard.services.engine.view', 'خدمات موتور', 'داشبورد تحلیلی خدمات موتور'],
        ['dashboard.services.transmission.view', 'خدمات گیربکس', 'داشبورد تحلیلی خدمات گیربکس'],
        ['dashboard.services.suspension.view', 'زیروبند و تعلیق', 'داشبورد تحلیلی زیروبند و تعلیق'],
        ['dashboard.services.electrical.view', 'برق', 'داشبورد تحلیلی برق'],
        ['dashboard.services.options.view', 'آپشن', 'داشبورد تحلیلی آپشن'],
        ['dashboard.services.prepurchase_inspection.view', 'کارشناسی خرید/فروش', 'کارشناسی خودرو برای تصمیم خرید/فروش — جدا از تعمیر و فروش'],
    ] as [$k, $t, $d]) {
        $add($rows, $k, 'management', 'dash_services', 'VIEW', $t, $d, [
            'sort' => $mgmtSort++, 'routes' => ['erp-management-dashboard.php', 'erp-operations-home.php'],
            'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'dash_services', 'group_fa' => 'داشبورد خدمات',
        ]);
    }
    $add($rows, 'dashboard.inventory_purchase.view', 'management', 'dash_inventory', 'VIEW', 'داشبورد انبار و خرید',
        'مشاهده خلاصه انبار و خرید — بدون ویرایش موجودی/سفارش', [
            'sort' => $mgmtSort++, 'routes' => ['inventory360/dashboard.php', 'erp-stock-board.php'],
            'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'dash_inventory', 'group_fa' => 'داشبورد انبار و خرید',
        ]);

    // —— Customer communication (ارتباط با مشتریان) ——
    foreach ([
        ['reception.board.VIEW', 'VIEW', 'تابلوی پذیرش', 'بسته پذیرش', ['erp-reception-board.php', 'erp-reception-workbench.php'], 'crm_reception', 'پذیرش'],
        ['reception.intake.CREATE', 'CREATE', 'ثبت پذیرش/ورودی', 'بسته پذیرش', ['erp-reception-jobcards.php'], 'crm_reception', 'پذیرش'],
        ['reception.jobcard.EDIT', 'EDIT', 'ویرایش پذیرش پرونده تعمیر', 'بسته پذیرش', ['erp-reception-jobcard-detail.php'], 'crm_reception', 'پذیرش'],
        ['customer.delivery.board.VIEW', 'VIEW', 'تابلوی ترخیص', 'بسته ترخیص', ['erp-delivery-control.php', 'erp-jobcard-delivery-clearance.php'], 'crm_delivery', 'ترخیص'],
        ['crm.customer.profile.VIEW', 'VIEW', 'پروفایل مشتریان', 'بسته پروفایل مشتریان', ['erp-customer-vehicle-workbench.php', 'erp-customer-core-dashboard.php'], 'crm_profile', 'پروفایل مشتریان'],
        ['crm.customer.search.VIEW', 'VIEW', 'جست‌وجوی مشتری', 'بسته پروفایل مشتریان', ['erp-customer-core-dashboard.php', 'erp-reception-board.php'], 'crm_profile', 'پروفایل مشتریان'],
        ['crm.followup.VIEW', 'VIEW', 'پیگیری ارتباط با مشتری', 'پیگیری در بسته ارتباط با مشتریان (نه بازاریابی انبوه)', ['erp-crm-followup-board.php'], 'crm_profile', 'پروفایل مشتریان'],
        ['customer.phone.access.VIEW', 'VIEW', 'دسترسی به شماره تماس مشتریان', 'بسته تماس — جدا از تبلیغات انبوه/موبایل', [], 'crm_phone', 'دسترسی به شماره تماس مشتریان'],
    ] as $idx => [$k, $act, $t, $d, $routes, $gk, $gfa]) {
        $add($rows, $k, 'customer_reception', 'customer', $act, $t, $d, [
            'sort' => 100 + $idx, 'routes' => $routes, 'enforcement' => 'NOT_YET_ENFORCED',
            'group' => $gk, 'group_fa' => $gfa,
        ]);
    }

    // —— Workshop R0B taxonomy (Owner-defined operational process) ——
    $wsGroups = m360_access_matrix_workshop_groups();
    $wsSort = 200;

    foreach ([
        ['workshop.operations.home.view', 'VIEW', 'مشاهده خانه عملیات تعمیرگاه', 'خانه عملیات فقط خلاصه است و دسترسی چهار خانواده خدمات را نمی‌دهد', ['erp-operations-home.php', 'erp-operation-control-center.php'], 'ENFORCED'],
        ['workshop.periodic_service.view', 'VIEW', 'مشاهده عملیات سرویس‌های دوره‌ای', 'خانواده خدمات PERIODIC_SERVICE — تابلوی تکنسین مشترک با مکانیک (OR)', ['erp-technician-work-board.php', 'erp-technician-workflow-ux.php'], 'ENFORCED'],
        ['workshop.inspection.view', 'VIEW', 'مشاهده عملیات خدمات کارشناسی', 'خانواده خدمات INSPECTION — برد فنی مشترک با برق/آپشن (OR)', ['erp-technical-board.php', 'erp-technical-jobcard-detail.php'], 'ENFORCED'],
        ['workshop.electrical_options.view', 'VIEW', 'مشاهده عملیات برق و آپشن', 'خانواده خدمات ELECTRICAL_OPTIONS', ['erp-technical-board.php', 'erp-technical-jobcard-detail.php', 'erp-unit-work-board.php'], 'ENFORCED'],
        ['workshop.mechanical.view', 'VIEW', 'مشاهده عملیات مکانیکی', 'خانواده خدمات MECHANICAL', ['erp-technician-work-board.php', 'erp-technician-board.php', 'erp-technician-workflow-ux.php', 'erp-unit-work-board.php'], 'ENFORCED'],
        ['workshop.jobcard.view', 'VIEW', 'مشاهده پرونده تعمیر خودرو', 'مشاهده پرونده تعمیر (نه تخصیص خودکار به یک تکنسین)', ['erp-jobcard-detail.php', 'erp-jobcard-readonly-list.php', 'erp-jobcard-workbench.php', 'erp-jobcard-detail-ux.php', 'erp-hall-jobcard-detail.php'], 'ENFORCED'],
    ] as [$k, $act, $t, $d, $routes, $enf]) {
        $add($rows, $k, 'workshop', 'view', $act, $t, $d, [
            'sort' => $wsSort++, 'routes' => $routes, 'enforcement' => $enf,
            'group' => 'ws_view', 'group_fa' => $wsGroups['ws_view'],
        ]);
    }

    foreach ([
        ['workshop.assign.mechanical', 'ASSIGN', 'تخصیص کار مکانیکی به مکانیک', 'ENFORCED'],
        ['workshop.assign.electrical', 'ASSIGN', 'تخصیص کار برق به برق‌کار', 'ENFORCED'],
        ['workshop.assign.options', 'ASSIGN', 'تخصیص کار آپشن به مسئول آپشن', 'ENFORCED'],
        ['workshop.assign.periodic_service', 'ASSIGN', 'تخصیص سرویس دوره‌ای به مکانیک', 'ENFORCED'],
        ['workshop.assign.inspection_mechanical', 'ASSIGN', 'تخصیص کارشناسی به مکانیک', 'ENFORCED'],
        ['workshop.assign.inspection_electrical', 'ASSIGN', 'تخصیص کارشناسی به برق‌کار', 'ENFORCED'],
    ] as [$k, $act, $t, $enf]) {
        $add($rows, $k, 'workshop', 'assign', $act, $t, 'تخصیص روی آیتم کاری مستقل داخل پرونده تعمیر — نه کل پرونده به یک نفر', [
            'sort' => $wsSort++, 'routes' => ['erp-hall-jobcard-detail.php', 'erp-unit-work-board.php', 'erp-workshop-work-item-assign.php'],
            'enforcement' => $enf, 'group' => 'ws_assign', 'group_fa' => $wsGroups['ws_assign'],
        ]);
    }

    foreach ([
        ['workshop.diagnosis_report.create', 'CREATE', 'ثبت گزارش تشخیص', 'گزارش پیش از کار: عیب، ارزیابی، علت محتمل، کار پیشنهادی، قطعات پیشنهادی، ریسک، مدارک', 'ENFORCED'],
        ['workshop.diagnosis_report.approve', 'APPROVE', 'تأیید گزارش تشخیص', 'تأیید گزارش تشخیص پیش از کار — maker-checker', 'ENFORCED'],
        ['workshop.diagnosis_report.return', 'RETURN', 'برگشت گزارش تشخیص برای اصلاح', 'برگشت گزارش تشخیص با دلیل فارسی و حفظ تاریخچه', 'ENFORCED'],
    ] as [$k, $act, $t, $d, $enf]) {
        $add($rows, $k, 'workshop', 'diagnosis', $act, $t, $d, [
            'sort' => $wsSort++, 'mc' => $act === 'APPROVE' ? 1 : 0, 'enforcement' => $enf,
            'group' => 'ws_diagnosis', 'group_fa' => $wsGroups['ws_diagnosis'],
            'routes' => ['erp-workshop-diagnosis-report.php', 'erp-technical-jobcard-action.php', 'erp-technical-jobcard-detail.php'],
        ]);
    }

    foreach ([
        ['workshop.part_request.create', 'CREATE', 'درخواست قطعه', 'درخواست قطعه توسط تکنسین', 'ENFORCED'],
        ['workshop.part_request.view_status', 'VIEW', 'مشاهده وضعیت درخواست قطعه', 'وضعیت درخواست قطعه', 'ENFORCED'],
        ['workshop.part_request.technical_approve', 'APPROVE', 'تأیید نیاز قطعه', 'تأیید فنی ضرورت قطعه — جدا از تحویل انبار', 'ENFORCED'],
        ['workshop.part_request.reject_return', 'RETURN', 'رد یا برگشت درخواست قطعه', 'رد یا برگشت درخواست قطعه', 'ENFORCED'],
        ['workshop.part_issue.allocate', 'ASSIGN', 'تخصیص و تحویل قطعه از انبار', 'تخصیص/صدور فیزیکی از انبار', 'ENFORCED'],
        ['workshop.part_issue.receive_confirm', 'SUBMIT', 'تأیید دریافت قطعه', 'تأیید دریافت قطعه توسط تکنسین', 'ENFORCED'],
        ['workshop.billable_parts.view', 'VIEW', 'مشاهده قطعات مصرف‌شده خودرو', 'قطعات قابل‌صورتحساب خودرو — بدون قیمت خرید/پرداخت', 'ENFORCED'],
    ] as [$k, $act, $t, $d, $enf]) {
        $add($rows, $k, 'workshop', 'parts', $act, $t, $d, [
            'sort' => $wsSort++, 'mc' => $act === 'APPROVE' ? 1 : 0, 'enforcement' => $enf,
            'group' => 'ws_parts', 'group_fa' => $wsGroups['ws_parts'],
            'routes' => [
                'erp-jobcard-part-use.php', 'erp-parts-request-handoff.php', 'erp-jobcard-part-readonly-list.php',
                'erp-technical-request-center.php', 'erp-technical-request-detail.php',
                'api/staff/technical-request-create.php', 'api/staff/technical-request-review.php',
                'erp-work-execution-action.php',
            ],
        ]);
    }

    foreach ([
        ['workshop.work_report.create', 'CREATE', 'ثبت گزارش امور انجام‌شده', 'گزارش پس از کار — متفاوت از گزارش تشخیص', 'ENFORCED'],
        ['workshop.work_report.approve', 'APPROVE', 'تأیید گزارش امور انجام‌شده', 'تأیید گزارش انجام کار — بدون تأیید قیمت مشتری', 'ENFORCED'],
        ['workshop.work_report.return', 'RETURN', 'برگشت گزارش انجام‌شده برای اصلاح', 'برگشت گزارش انجام کار با دلیل و حفظ تاریخچه', 'ENFORCED'],
        ['workshop.service_line.create_no_price', 'CREATE', 'ثبت خدمات انجام‌شده بدون مبلغ', 'ثبت نوع خدمت، شرح، تعداد، مدت، مجری، نتیجه — بدون مبلغ مشتری', 'ENFORCED'],
    ] as [$k, $act, $t, $d, $enf]) {
        $add($rows, $k, 'workshop', 'work_report', $act, $t, $d, [
            'sort' => $wsSort++, 'mc' => $act === 'APPROVE' ? 1 : 0, 'enforcement' => $enf,
            'group' => 'ws_work', 'group_fa' => $wsGroups['ws_work'],
            'routes' => ['erp-workshop-work-report.php', 'erp-work-execution-detail.php', 'erp-work-execution-action.php'],
        ]);
    }

    foreach ([
        ['workshop.internal_consumable.create', 'CREATE', 'ثبت مواد و ملزومات مصرفی داخلی', 'مصرف داخلی غیرقابل‌صورتحساب مشتری'],
        ['workshop.internal_consumable.approve', 'APPROVE', 'تأیید مواد و ملزومات مصرفی داخلی', 'تأیید مصرف داخلی + کسر انبار بدون خط صورتحساب'],
        ['workshop.internal_consumable.return', 'RETURN', 'برگشت مصرف داخلی برای اصلاح', 'برگشت ثبت مصرف داخلی بدون حرکت انبار'],
        ['workshop.internal_consumable.cost_view', 'FINANCIAL_VIEW', 'مشاهده هزینه مواد مصرفی داخلی', 'هزینه داخلی — پنهان از تکنسین عادی'],
        ['workshop.internal_consumable.reversal_request', 'REQUEST', 'درخواست ابطال مصرف داخلی', 'درخواست ابطال مصرف صادرشده — بدون تغییر موجودی تا تأیید'],
        ['workshop.internal_consumable.reversal_approve', 'APPROVE', 'تأیید ابطال و برگشت موجودی', 'تأیید ابطال + حرکت مخالف انبار — maker-checker'],
    ] as [$k, $act, $t, $d]) {
        $routes = [
            'erp-workshop-internal-consumable-create.php',
            'erp-workshop-internal-consumable-queue.php',
            'erp-workshop-internal-consumable-history.php',
            'erp-workshop-internal-consumable-cost.php',
        ];
        if (str_contains($k, 'reversal')) {
            $routes = [
                'erp-workshop-internal-consumable-reversal-request.php',
                'erp-workshop-internal-consumable-reversal-queue.php',
                'erp-workshop-internal-consumable-cost.php',
            ];
        }
        $add($rows, $k, 'workshop', 'internal_consumable', $act, $t, $d, [
            'sort' => $wsSort++, 'fin' => $act === 'FINANCIAL_VIEW' ? 1 : 0, 'mc' => 1,
            'enforcement' => 'ENFORCED',
            'group' => 'ws_consumable', 'group_fa' => $wsGroups['ws_consumable'],
            'routes' => $routes,
        ]);
    }

    foreach ([
        ['workshop.qc.queue.view', 'VIEW', 'مشاهده کارتابل کنترل کیفیت', 'کارتابل کنترل کیفیت', ['erp-qc-board.php', 'erp-qc-detail.php'], 'ENFORCED'],
        ['workshop.qc.result.create', 'CREATE', 'ثبت نتیجه کنترل کیفیت', 'ثبت نتیجه کنترل کیفیت', ['erp-qc-detail.php', 'erp-qc-action.php'], 'ENFORCED'],
        ['workshop.qc.approve_return', 'APPROVE', 'تأیید یا برگشت از کنترل کیفیت', 'تأیید یا برگشت کنترل کیفیت', ['erp-qc-detail.php', 'erp-qc-action.php', 'submit-qc-decision.php'], 'ENFORCED'],
        ['workshop.delivery.queue.view', 'VIEW', 'مشاهده کارتابل آماده تحویل', 'کارتابل آماده تحویل خودرو', ['erp-delivery-control-closure-dashboard.php', 'erp-delivery-control.php', 'erp-jobcard-delivery-clearance.php'], 'ENFORCED'],
        ['workshop.delivery.final_confirm', 'CLOSE', 'تأیید تحویل نهایی خودرو', 'تأیید تحویل نهایی خودرو به مشتری — بدون دور زدن گیت‌های تسویه/مدارک', ['erp-jobcard-delivery-clearance.php', 'erp-delivery-control.php'], 'ENFORCED'],
    ] as [$k, $act, $t, $d, $routes, $enf]) {
        $add($rows, $k, 'workshop', 'qc_delivery', $act, $t, $d, [
            'sort' => $wsSort++, 'mc' => in_array($act, ['APPROVE', 'CLOSE'], true) ? 1 : 0,
            'enforcement' => $enf,
            'group' => 'ws_qc_delivery', 'group_fa' => $wsGroups['ws_qc_delivery'],
            'routes' => $routes,
        ]);
    }

    // Legacy ambiguous workshop permissions — preserved, quarantined, not Owner-assignable columns
    $legacyWs = [
        ['workshop.operations.VIEW', 'VIEW', 'میراث: خانه عملیات (قدیمی)', ['erp-operations-home.php']],
        // workshop.jobcard.VIEW / workshop.mechanical.VIEW collide (CI collation) with R0B keys — updated in place above, not listed again
        ['workshop.jobcard.CREATE', 'CREATE', 'میراث: ایجاد پرونده تعمیر', ['erp-jobcard-create.php', 'erp-jobcard-create-v2.php']],
        ['workshop.jobcard.EDIT', 'EDIT', 'میراث: ویرایش پرونده تعمیر', ['erp-jobcard-detail-ux.php']],
        ['workshop.jobcard.ASSIGN', 'ASSIGN', 'میراث: تخصیص کل پرونده (مبهم)', ['erp-jobcard-operation-flow.php']],
        ['workshop.hall.VIEW', 'VIEW', 'میراث: کارتابل سالن', ['erp-hall-jobcard-detail.php']],
        ['workshop.mechanical.EDIT', 'EDIT', 'میراث: ثبت کار مکانیک', ['erp-technician-workflow-ux.php']],
        ['workshop.electrical.VIEW', 'VIEW', 'میراث: تابلوی برق/آپشن ترکیبی', ['erp-technical-board.php']],
        ['workshop.electrical.EDIT', 'EDIT', 'میراث: ثبت کار برق/آپشن ترکیبی', ['erp-technical-jobcard-detail.php']],
        ['workshop.qc.VIEW', 'VIEW', 'میراث: کنترل کیفیت', ['erp-qc-board.php']],
        ['workshop.qc.APPROVE', 'APPROVE', 'میراث: تأیید کنترل کیفیت', ['erp-qc-detail.php']],
        ['workshop.delivery.VIEW', 'VIEW', 'میراث: تحویل', ['erp-delivery-control-closure-dashboard.php']],
        ['workshop.delivery.CLOSE', 'CLOSE', 'میراث: بستن تحویل', ['erp-jobcard-delivery-clearance.php']],
        ['workshop.media.VIEW', 'VIEW', 'میراث: مدارک/عکس پرونده', ['erp-jobcard-media-preview.php']],
        ['workshop.parts_use.EDIT', 'EDIT', 'میراث: مصرف قطعه ترکیبی', ['erp-jobcard-part-use.php']],
    ];
    foreach ($legacyWs as $idx => [$k, $act, $t, $routes]) {
        $add($rows, $k, 'workshop_legacy', 'legacy', $act, $t, 'مجوز میراثی مبهم — بدون تخصیص جدید؛ نگاشت به مدل R0B در حال بررسی', [
            'sort' => 900 + $idx, 'routes' => $routes, 'enforcement' => 'LEGACY_QUARANTINED', 'assignable' => 0,
            'group' => 'ws_legacy', 'group_fa' => 'میراث (غیرقابل تخصیص)',
        ]);
    }

    // —— Inventory ——
    $inv = [
        ['inventory.items.VIEW', 'VIEW', 'فهرست کالا', 0],
        ['inventory.items.CREATE', 'CREATE', 'ثبت کالا', 0],
        ['inventory.items.EDIT', 'EDIT', 'ویرایش کالا', 0],
        ['inventory.docs.DOCUMENT_MANAGE', 'DOCUMENT_MANAGE', 'مدارک کالا', 0],
        ['inventory.receipt.CREATE', 'CREATE', 'ورود انبار', 0],
        ['inventory.issue.CREATE', 'CREATE', 'خروج انبار', 0],
        ['inventory.stock.VIEW', 'VIEW', 'موجودی', 0],
        ['inventory.adjust.EDIT', 'EDIT', 'تعدیل موجودی', 0],
        ['inventory.alert.VIEW', 'VIEW', 'هشدار حداقل موجودی', 0],
        ['inventory.export.EXPORT', 'EXPORT', 'خروجی Excel انبار', 0],
        ['inventory.financial.VIEW', 'FINANCIAL_VIEW', 'مشاهده قیمت/فاکتور خرید', 1],
        ['inventory.financial.EDIT', 'FINANCIAL_EDIT', 'ویرایش مالی خرید انبار', 1],
    ];
    foreach ($inv as $idx => [$k, $act, $t, $fin]) {
        $add($rows, $k, 'inventory', 'stock', $act, $t, $t, [
            'fin' => $fin, 'sort' => 300 + $idx,
            'routes' => $fin ? [] : ['erp-stock-board.php', 'erp-part-use.php', 'erp-parts-request.php'],
            'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'inventory', 'group_fa' => 'انبار',
        ]);
    }

    foreach ([
        ['inventory.internal_cost.view', 'FINANCIAL_VIEW', 'مشاهده ارزیابی بهای داخلی انبار', 'مشاهده ارزیابی بهای مصرف داخلی'],
        ['inventory.internal_cost.edit', 'EDIT', 'ثبت/ویرایش ارزیابی بهای داخلی', 'پیش‌نویس و ارسال ارزیابی بهای داخلی'],
        ['inventory.internal_cost.approve', 'APPROVE', 'تأیید ارزیابی بهای داخلی انبار', 'تأیید maker-checker ارزیابی بهای داخلی'],
    ] as $idx => [$k, $act, $t, $d]) {
        $add($rows, $k, 'inventory', 'internal_cost', $act, $t, $d, [
            'fin' => 1,
            'mc' => $act === 'APPROVE' ? 1 : 0,
            'sort' => 320 + $idx,
            'routes' => ['erp-inventory-internal-cost.php', 'erp-workshop-internal-consumable-cost.php'],
            'enforcement' => 'ENFORCED',
            'group' => 'inventory', 'group_fa' => 'انبار',
        ]);
    }

    foreach ([
        ['purchase.request.VIEW', 'VIEW', 'مشاهده درخواست خرید'],
        ['purchase.request.CREATE', 'CREATE', 'ثبت درخواست خرید'],
        ['purchase.request.SUBMIT', 'SUBMIT', 'ارسال درخواست خرید'],
        ['purchase.request.APPROVE', 'APPROVE', 'تأیید خرید'],
        ['purchase.order.EDIT', 'EDIT', 'سفارش خرید'],
        ['purchase.receive.EDIT', 'EDIT', 'رسید خرید'],
        ['purchase.docs.DOCUMENT_MANAGE', 'DOCUMENT_MANAGE', 'مدارک خرید'],
        ['purchase.financial.VIEW', 'FINANCIAL_VIEW', 'مشاهده مالی خرید'],
        ['purchase.financial.EDIT', 'FINANCIAL_EDIT', 'تکمیل مالی خرید'],
    ] as $idx => [$k, $act, $t]) {
        $add($rows, $k, 'purchase', 'purchase', $act, $t, $t, [
            'fin' => str_contains($act, 'FINANCIAL') ? 1 : 0,
            'mc' => $act === 'APPROVE' ? 1 : 0,
            'sort' => 350 + $idx,
            'routes' => ['erp-purchase-board.php', 'erp-supplier-board.php'],
            'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'purchase', 'group_fa' => 'خرید',
        ]);
    }

    foreach ([
        ['finance.customer_payment.VIEW', 'VIEW', 'پرداخت مشتری'],
        ['finance.customer_payment.EDIT', 'EDIT', 'ثبت پرداخت مشتری'],
        ['finance.supplier_payment.VIEW', 'VIEW', 'پرداخت تأمین‌کننده'],
        ['finance.supplier_payment.EDIT', 'EDIT', 'ثبت پرداخت تأمین‌کننده'],
        ['finance.petty_cash.VIEW', 'VIEW', 'تنخواه'],
        ['finance.petty_cash.EDIT', 'EDIT', 'ثبت تنخواه'],
        ['finance.external_settle.VIEW', 'VIEW', 'تسویه خدمات بیرونی'],
        ['finance.external_settle.APPROVE', 'APPROVE', 'تأیید تسویه خدمات بیرونی'],
        ['finance.reports.VIEW', 'VIEW', 'گزارش‌های مالی'],
        ['finance.payroll.REVIEW', 'VIEW', 'بازبینی حقوق (مالی)'],
        ['finance.payroll.APPROVE', 'APPROVE', 'تأیید حقوق'],
    ] as $idx => [$k, $act, $t]) {
        $add($rows, $k, 'finance', 'finance', $act, $t, $t, [
            'fin' => 1, 'mc' => in_array($act, ['APPROVE'], true) ? 1 : 0, 'sort' => 400 + $idx,
            'routes' => ['erp-finance-preview-workbench.php', 'erp-payment-board.php', 'erp-final-invoice-board.php'],
            'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'finance', 'group_fa' => 'مالی',
        ]);
    }

    foreach ([
        ['logistics.external.VIEW', 'VIEW', 'خدمات بیرونی'],
        ['logistics.external.CREATE', 'CREATE', 'درخواست خدمات بیرونی'],
        ['logistics.external.EDIT', 'EDIT', 'هماهنگی خدمات بیرونی'],
        ['logistics.freight.VIEW', 'VIEW', 'اسناد حمل/بارنامه'],
        ['logistics.settle.SUBMIT', 'SUBMIT', 'ارسال تسویه به مالی'],
    ] as $idx => [$k, $act, $t]) {
        $add($rows, $k, 'logistics', 'external', $act, $t, $t, [
            'sort' => 450 + $idx, 'routes' => ['erp-external-service.php'],
            'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'logistics', 'group_fa' => 'تدارکات',
        ]);
    }

    foreach ([
        ['hr.admin.personnel.VIEW', 'VIEW', 'مدیریت پرسنل', ['peopleos360/hr-personnel-list.php', 'peopleos360/hr-personnel-form.php']],
        ['hr.admin.personnel.EDIT', 'EDIT', 'ویرایش پرونده پرسنلی', ['peopleos360/hr-personnel-form.php']],
        ['hr.admin.occ_med.EDIT', 'EDIT', 'ثبت طب کار', ['peopleos360/hr-personnel-form.php']],
        ['hr.admin.contracts.VIEW', 'VIEW', 'فهرست قراردادها', ['peopleos360/hr-contract-list.php']],
        ['hr.admin.contracts.EDIT', 'EDIT', 'ثبت قرارداد', ['peopleos360/hr-contract-register.php']],
        ['hr.admin.contracts.APPROVE', 'APPROVE', 'نهایی‌سازی/تمدید قرارداد', ['peopleos360/hr-contract-reminders.php', 'peopleos360/hr-exit-form.php']],
        ['hr.admin.documents.DOCUMENT_MANAGE', 'DOCUMENT_MANAGE', 'مدارک پرسنلی دیگران', []],
        ['hr.admin.password.RESET', 'USER_MANAGE', 'بازنشانی رمز پرسنل', []],
        ['hr.manager.reminders.VIEW', 'VIEW', 'هشدارهای مدیریتی قرارداد', ['peopleos360/my-cartable.php']],
        ['hr.manager.recommend.EDIT', 'EDIT', 'ثبت توصیه مدیر', ['peopleos360/my-cartable.php']],
        ['hr.payroll.admin.VIEW', 'VIEW', 'مدیریت فیش‌ها', [], 1],
        ['hr.payroll.admin.EDIT', 'EDIT', 'ویرایش/قفل فیش', [], 1],
    ] as $idx => $row) {
        [$k, $act, $t, $routes] = [$row[0], $row[1], $row[2], $row[3] ?? []];
        $fin = !empty($row[4]);
        $add($rows, $k, 'hr_admin', 'hr', $act, $t, $t, [
            'hr' => 1, 'fin' => $fin ? 1 : 0, 'mc' => $act === 'APPROVE' ? 1 : 0,
            'sort' => 500 + $idx, 'routes' => $routes,
            'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'hr_admin', 'group_fa' => 'منابع انسانی',
        ]);
    }

    // Attendance request review (manager/HR — never via base self-service)
    foreach ([
        ['peopleos.attendance.leave_request.review', 'بررسی درخواست مرخصی', 'مشاهده/تأیید/رد/برگشت درخواست مرخصی — maker-checker'],
        ['peopleos.attendance.overtime_request.review', 'بررسی درخواست اضافه‌کاری', 'مشاهده/تأیید/رد/برگشت درخواست اضافه‌کاری — maker-checker'],
        ['peopleos.attendance.correction_request.review', 'بررسی درخواست اصلاح تردد', 'مشاهده/تأیید/رد/برگشت اصلاح تردد — فقط APPROVED اعمال می‌شود'],
    ] as $idx => [$k, $t, $d]) {
        $add($rows, $k, 'hr_admin', 'attendance_review', 'REVIEW', $t, $d, [
            'hr' => 1, 'mc' => 1, 'sort' => 520 + $idx,
            'routes' => ['peopleos360/approvals.php'],
            'enforcement' => 'NOT_YET_ENFORCED', 'assignable' => 0,
            'group' => 'att_review', 'group_fa' => 'بررسی درخواست‌های حضور',
        ]);
    }

    // Legacy CRM/marketing — hidden from Owner matrix (quarantined; historical rows retained)
    $add($rows, 'marketing.campaign.VIEW', 'marketing', 'campaign', 'VIEW', 'بازاریابی دیجیتال (میراث)', 'میراث — از ماتریس اصلی حذف شده؛ تبلیغات انبوه ≠ پروفایل مشتری', [
        'sort' => 600, 'enforcement' => 'LEGACY_QUARANTINED', 'assignable' => 0,
        'group' => 'marketing', 'group_fa' => 'بازاریابی (میراث)',
    ]);
    $add($rows, 'marketing.campaign.EDIT', 'marketing', 'campaign', 'EDIT', 'مدیریت کمپین (میراث)', 'میراث — از ماتریس اصلی حذف شده', [
        'sort' => 601, 'enforcement' => 'LEGACY_QUARANTINED', 'assignable' => 0,
        'group' => 'marketing', 'group_fa' => 'بازاریابی (میراث)',
    ]);
    $add($rows, 'marketing.campaign', 'marketing', 'campaign', 'VIEW', 'کمپین بازاریابی (میراث)', 'کلید قدیمی — قرنطینه', [
        'sort' => 602, 'enforcement' => 'LEGACY_QUARANTINED', 'assignable' => 0,
        'group' => 'marketing', 'group_fa' => 'بازاریابی (میراث)',
    ]);
    $add($rows, 'marketing.view', 'marketing', 'campaign', 'VIEW', 'مشاهده بازاریابی (میراث)', 'کلید قدیمی — قرنطینه', [
        'sort' => 603, 'enforcement' => 'LEGACY_QUARANTINED', 'assignable' => 0,
        'group' => 'marketing', 'group_fa' => 'بازاریابی (میراث)',
    ]);

    $add($rows, 'reports.ops.VIEW', 'reports', 'ops', 'VIEW', 'گزارش عملیات', 'گزارش‌های عملیاتی', ['sort' => 700, 'routes' => ['erp-operational-kpi.php', 'erp-management-dashboard.php'], 'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'reports', 'group_fa' => 'گزارش']);
    $add($rows, 'reports.audit.VIEW', 'reports', 'audit', 'AUDIT_VIEW', 'گزارش ممیزی', 'مشاهده ممیزی', ['sort' => 701, 'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'reports', 'group_fa' => 'گزارش']);

    $add($rows, 'admin.users.USER_MANAGE', 'software', 'users', 'USER_MANAGE', 'مدیریت کاربران', 'ایجاد/غیرفعال کاربران', ['mc' => 1, 'sort' => 800, 'routes' => ['erp-access-user-create.php', 'erp-access-user-edit.php', 'erp-user-role-admin.php'], 'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'software', 'group_fa' => 'نرم‌افزار']);
    $add($rows, 'admin.roles.PERMISSION_MANAGE', 'software', 'roles', 'PERMISSION_MANAGE', 'مدیریت نقش‌ها', 'تخصیص نقش', ['mc' => 1, 'sort' => 801, 'routes' => ['erp-access-role-assign.php'], 'enforcement' => 'NOT_YET_ENFORCED', 'group' => 'software', 'group_fa' => 'نرم‌افزار']);

    return $rows;
}

/** Workshop matrix column groups (Owner-facing). @return array<string,string> */
function m360_access_matrix_workshop_groups(): array
{
    return [
        'ws_view' => 'مشاهده عملیات',
        'ws_assign' => 'تخصیص کار',
        'ws_diagnosis' => 'تشخیص و گزارش',
        'ws_parts' => 'درخواست و تخصیص قطعه',
        'ws_work' => 'گزارش انجام کار و خدمات',
        'ws_consumable' => 'مواد و ملزومات مصرفی داخلی',
        'ws_qc_delivery' => 'کنترل کیفیت و تحویل',
    ];
}

/** @return list<string> */
function m360_access_matrix_base_permission_keys(): array
{
    $keys = [];
    foreach (m360_access_matrix_catalog_definitions() as $row) {
        if (!empty($row['is_base_self_service'])) {
            $keys[] = $row['permission_key'];
        }
    }
    return $keys;
}

/** @return array<string,string> module_key => Persian tab title */
function m360_access_matrix_module_tabs(): array
{
    return [
        'hr_self' => 'پروفایل پرسنلی',
        'management' => 'مدیریت',
        'customer_reception' => 'ارتباط با مشتریان',
        'workshop' => 'عملیات تعمیرگاه',
        'inventory' => 'انبار',
        'purchase' => 'خرید',
        'logistics' => 'تدارکات و خدمات بیرونی',
        'finance' => 'مالی',
        'hr_admin' => 'منابع انسانی',
        'access' => 'نرم‌افزار و دسترسی‌ها',
        'reports' => 'گزارش و ممیزی',
        'software' => 'مدیریت کاربران',
    ];
}
