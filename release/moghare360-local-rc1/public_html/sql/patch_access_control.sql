SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS access_profiles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  profile_name VARCHAR(120) NOT NULL UNIQUE,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS access_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  permission_key VARCHAR(120) NOT NULL UNIQUE,
  module_key VARCHAR(80) NOT NULL,
  action_key VARCHAR(80) NOT NULL,
  permission_label VARCHAR(180) NOT NULL,
  sort_order INT NOT NULL DEFAULT 100,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_access_perm_module (module_key),
  INDEX idx_access_perm_action (action_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS access_profile_permissions (
  profile_id INT NOT NULL,
  permission_key VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (profile_id, permission_key),
  INDEX idx_app_permission_key (permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_user_access_profiles (
  staff_user_id INT NOT NULL,
  access_profile_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (staff_user_id, access_profile_id),
  INDEX idx_suap_profile (access_profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO access_permissions (permission_key, module_key, action_key, permission_label, sort_order) VALUES
('dashboard.view','dashboard','view','مشاهده داشبورد',1),
('customer.view','customer','view','مشاهده مشتریان',10),
('customer.create','customer','create','ثبت مشتری',11),
('customer.edit','customer','edit','ویرایش مشتری',12),
('customer.report','customer','report','گزارش مشتریان',13),
('reception.view','reception','view','مشاهده پذیرش',20),
('reception.create','reception','create','ثبت پذیرش/درخواست خدمات',21),
('reception.edit','reception','edit','ویرایش پذیرش',22),
('reception.report','reception','report','گزارش پذیرش',23),
('inventory.view','inventory','view','مشاهده انبار',30),
('inventory.create','inventory','create','ثبت کالا',31),
('inventory.inbound','inventory','inbound','ثبت ورود کالا/رسید',32),
('inventory.outbound','inventory','outbound','ثبت خروج کالا',33),
('inventory.edit','inventory','edit','ویرایش کالا',34),
('inventory.price','inventory','price','مشاهده/ثبت مبلغ و ریال',35),
('inventory.counting','inventory','counting','انبارگردانی',36),
('inventory.report','inventory','report','گزارش انبار',37),
('purchase_domestic.view','purchase_domestic','view','مشاهده خرید داخلی',40),
('purchase_domestic.create','purchase_domestic','create','ثبت خرید داخلی',41),
('purchase_domestic.approve','purchase_domestic','approve','تایید خرید داخلی',42),
('purchase_foreign.view','purchase_foreign','view','مشاهده خرید خارجی',50),
('purchase_foreign.create','purchase_foreign','create','ثبت خرید خارجی',51),
('purchase_foreign.approve','purchase_foreign','approve','تایید خرید خارجی',52),
('sales_accounting.view','sales_accounting','view','مشاهده حسابداری فروش',60),
('sales_accounting.create','sales_accounting','create','ثبت مالی فروش',61),
('sales_accounting.report','sales_accounting','report','گزارش حسابداری فروش',62),
('hr.view','hr','view','مشاهده منابع انسانی',70),
('hr.attendance','hr','attendance','ورود و خروج/حضور و غیاب',71),
('hr.payroll','hr','payroll','حقوق/وام/بدهی/قرارداد',72),
('hr.report','hr','report','گزارش منابع انسانی',73),
('admin.users','admin','users','مدیریت کاربران',80),
('admin.access','admin','access','تعریف سطح دسترسی',81),
('admin.settings','admin','settings','تنظیمات مدیریتی',82),
('reports.view','reports','view','مشاهده همه گزارش‌ها',90),
('reports.export','reports','export','خروجی گزارش‌ها',91);

INSERT IGNORE INTO access_profiles (profile_name, description, is_active) VALUES
('مدیر کل - فقط مشاهده و گزارش','دسترسی مشاهده داشبورد و گزارش‌ها بدون ویرایش',1),
('مدیر ارشد کامل','دسترسی کامل مدیریتی تا تکمیل سطح دسترسی نهایی',1),
('ثبت اطلاعات پذیرش','ثبت و مشاهده مشتری/پذیرش بدون دسترسی مالی و تنظیمات',1),
('انبار - ورود اطلاعات بدون مبلغ','ثبت اطلاعات کالا و رسید بدون مشاهده/ثبت مبلغ و ریال',1),
('انبار - کنترل قیمت خرید','دسترسی انبار همراه با مشاهده/ثبت قیمت خرید',1),
('ثبت ورود کالا با رسید','فقط ثبت ورود کالا با دریافت رسید/فاکتور',1);

DELETE FROM access_profile_permissions
WHERE profile_id IN (SELECT id FROM access_profiles WHERE profile_name IN (
'مدیر کل - فقط مشاهده و گزارش','مدیر ارشد کامل','ثبت اطلاعات پذیرش','انبار - ورود اطلاعات بدون مبلغ','انبار - کنترل قیمت خرید','ثبت ورود کالا با رسید'
));

INSERT IGNORE INTO access_profile_permissions (profile_id, permission_key)
SELECT p.id, x.permission_key
FROM access_profiles p
JOIN (
  SELECT 'مدیر کل - فقط مشاهده و گزارش' profile_name, 'dashboard.view' permission_key UNION ALL
  SELECT 'مدیر کل - فقط مشاهده و گزارش','customer.view' UNION ALL
  SELECT 'مدیر کل - فقط مشاهده و گزارش','reception.view' UNION ALL
  SELECT 'مدیر کل - فقط مشاهده و گزارش','inventory.view' UNION ALL
  SELECT 'مدیر کل - فقط مشاهده و گزارش','inventory.report' UNION ALL
  SELECT 'مدیر کل - فقط مشاهده و گزارش','reports.view' UNION ALL
  SELECT 'مدیر ارشد کامل','dashboard.view' UNION ALL
  SELECT 'مدیر ارشد کامل','customer.view' UNION ALL SELECT 'مدیر ارشد کامل','customer.create' UNION ALL SELECT 'مدیر ارشد کامل','customer.edit' UNION ALL SELECT 'مدیر ارشد کامل','customer.report' UNION ALL
  SELECT 'مدیر ارشد کامل','reception.view' UNION ALL SELECT 'مدیر ارشد کامل','reception.create' UNION ALL SELECT 'مدیر ارشد کامل','reception.edit' UNION ALL SELECT 'مدیر ارشد کامل','reception.report' UNION ALL
  SELECT 'مدیر ارشد کامل','inventory.view' UNION ALL SELECT 'مدیر ارشد کامل','inventory.create' UNION ALL SELECT 'مدیر ارشد کامل','inventory.inbound' UNION ALL SELECT 'مدیر ارشد کامل','inventory.outbound' UNION ALL SELECT 'مدیر ارشد کامل','inventory.edit' UNION ALL SELECT 'مدیر ارشد کامل','inventory.price' UNION ALL SELECT 'مدیر ارشد کامل','inventory.counting' UNION ALL SELECT 'مدیر ارشد کامل','inventory.report' UNION ALL
  SELECT 'مدیر ارشد کامل','purchase_domestic.view' UNION ALL SELECT 'مدیر ارشد کامل','purchase_domestic.create' UNION ALL SELECT 'مدیر ارشد کامل','purchase_domestic.approve' UNION ALL
  SELECT 'مدیر ارشد کامل','purchase_foreign.view' UNION ALL SELECT 'مدیر ارشد کامل','purchase_foreign.create' UNION ALL SELECT 'مدیر ارشد کامل','purchase_foreign.approve' UNION ALL
  SELECT 'مدیر ارشد کامل','sales_accounting.view' UNION ALL SELECT 'مدیر ارشد کامل','sales_accounting.create' UNION ALL SELECT 'مدیر ارشد کامل','sales_accounting.report' UNION ALL
  SELECT 'مدیر ارشد کامل','hr.view' UNION ALL SELECT 'مدیر ارشد کامل','hr.attendance' UNION ALL SELECT 'مدیر ارشد کامل','hr.payroll' UNION ALL SELECT 'مدیر ارشد کامل','hr.report' UNION ALL
  SELECT 'مدیر ارشد کامل','admin.users' UNION ALL SELECT 'مدیر ارشد کامل','admin.access' UNION ALL SELECT 'مدیر ارشد کامل','admin.settings' UNION ALL SELECT 'مدیر ارشد کامل','reports.view' UNION ALL SELECT 'مدیر ارشد کامل','reports.export' UNION ALL
  SELECT 'ثبت اطلاعات پذیرش','dashboard.view' UNION ALL SELECT 'ثبت اطلاعات پذیرش','customer.view' UNION ALL SELECT 'ثبت اطلاعات پذیرش','customer.create' UNION ALL SELECT 'ثبت اطلاعات پذیرش','reception.view' UNION ALL SELECT 'ثبت اطلاعات پذیرش','reception.create' UNION ALL
  SELECT 'انبار - ورود اطلاعات بدون مبلغ','dashboard.view' UNION ALL SELECT 'انبار - ورود اطلاعات بدون مبلغ','inventory.view' UNION ALL SELECT 'انبار - ورود اطلاعات بدون مبلغ','inventory.create' UNION ALL SELECT 'انبار - ورود اطلاعات بدون مبلغ','inventory.inbound' UNION ALL
  SELECT 'انبار - کنترل قیمت خرید','dashboard.view' UNION ALL SELECT 'انبار - کنترل قیمت خرید','inventory.view' UNION ALL SELECT 'انبار - کنترل قیمت خرید','inventory.create' UNION ALL SELECT 'انبار - کنترل قیمت خرید','inventory.inbound' UNION ALL SELECT 'انبار - کنترل قیمت خرید','inventory.price' UNION ALL SELECT 'انبار - کنترل قیمت خرید','inventory.report' UNION ALL
  SELECT 'ثبت ورود کالا با رسید','dashboard.view' UNION ALL SELECT 'ثبت ورود کالا با رسید','inventory.view' UNION ALL SELECT 'ثبت ورود کالا با رسید','inventory.inbound'
) x ON x.profile_name = p.profile_name;

DELETE FROM staff_user_access_profiles WHERE staff_user_id IN (SELECT id FROM staff_users WHERE username IN ('manager','amir','reception1','reception2','reception3','warehouse_price','inbound_receipt1','inbound_receipt2','inbound_receipt3','jafar','yazdani','soheil','omid'));

INSERT IGNORE INTO staff_user_access_profiles (staff_user_id, access_profile_id)
SELECT u.id, p.id FROM staff_users u JOIN access_profiles p ON p.profile_name = 'مدیر کل - فقط مشاهده و گزارش' WHERE u.username = 'manager';
INSERT IGNORE INTO staff_user_access_profiles (staff_user_id, access_profile_id)
SELECT u.id, p.id FROM staff_users u JOIN access_profiles p ON p.profile_name = 'مدیر ارشد کامل' WHERE u.username IN ('amir','admin');
INSERT IGNORE INTO staff_user_access_profiles (staff_user_id, access_profile_id)
SELECT u.id, p.id FROM staff_users u JOIN access_profiles p ON p.profile_name = 'ثبت اطلاعات پذیرش' WHERE u.username IN ('reception1','reception2','reception3');
INSERT IGNORE INTO staff_user_access_profiles (staff_user_id, access_profile_id)
SELECT u.id, p.id FROM staff_users u JOIN access_profiles p ON p.profile_name = 'انبار - کنترل قیمت خرید' WHERE u.username = 'warehouse_price';
INSERT IGNORE INTO staff_user_access_profiles (staff_user_id, access_profile_id)
SELECT u.id, p.id FROM staff_users u JOIN access_profiles p ON p.profile_name = 'ثبت ورود کالا با رسید' WHERE u.username IN ('inbound_receipt1','inbound_receipt2','inbound_receipt3');
INSERT IGNORE INTO staff_user_access_profiles (staff_user_id, access_profile_id)
SELECT u.id, p.id FROM staff_users u JOIN access_profiles p ON p.profile_name = 'انبار - ورود اطلاعات بدون مبلغ' WHERE u.username IN ('jafar','yazdani','soheil','omid');
