<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-central-bridge.php';

p360hr_require_password_changed_for_cartable();
$emp = p360hr_employee_for_current_user();
p360hr_layout_start('مدارک پرسنلی');
if ($emp === null) {
    echo '<p>پرونده متصل نیست.</p>';
    p360hr_layout_end();
    exit;
}
$eid = (int)($emp['employee_id'] ?? 0);
$conn = p360hr_odbc();
$msg = null;
$ok = false;

$allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
$allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
$categories = [
    'PHOTO' => ['label' => 'عکس پرسنلی', 'max' => 1],
    'NATIONAL_ID' => ['label' => 'کارت ملی', 'max' => 1],
    'BIRTH_CERT' => ['label' => 'شناسنامه', 'max' => 2],
    'MILITARY' => ['label' => 'نظام وظیفه', 'max' => 5],
    'EDUCATION' => ['label' => 'مدارک تحصیلی', 'max' => 2],
    'OCC_MED' => ['label' => 'گزارش طب کار', 'max' => 10],
    'CONTRACT' => ['label' => 'قرارداد پرسنلی', 'max' => 20],
];

$storageRoot = 'C:\\xampp\\htdocs\\moghare360\\storage\\hr-docs';
if (!is_dir($storageRoot)) {
    @mkdir($storageRoot, 0775, true);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_FILES['doc_file'])) {
    $token = (string)($_POST['erp_csrf_token'] ?? '');
    if (!erp_csrf_validate_token('p360hr_doc_upload', $token)) {
        $msg = 'توکن امنیتی نامعتبر است.';
    } else {
        $cat = strtoupper(trim((string)($_POST['doc_category'] ?? '')));
        if (!isset($categories[$cat])) {
            $msg = 'دسته مدرک نامعتبر است.';
        } else {
            $file = $_FILES['doc_file'];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $msg = 'بارگذاری فایل ناموفق بود.';
            } else {
                $name = (string)($file['name'] ?? '');
                $tmp = (string)($file['tmp_name'] ?? '');
                $size = (int)($file['size'] ?? 0);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (substr_count($name, '.') > 1) {
                    $msg = 'نام فایل دارای پسوند دوگانه غیرمجاز است.';
                } elseif (!in_array($ext, $allowedExt, true)) {
                    $msg = 'فرمت فایل مجاز نیست.';
                } elseif ($size < 1 || $size > 8 * 1024 * 1024) {
                    $msg = 'حجم فایل خارج از محدوده مجاز است.';
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = (string)$finfo->file($tmp);
                    if (!in_array($mime, $allowedMime, true)) {
                        $msg = 'نوع محتوای فایل مجاز نیست.';
                    } else {
                        $sha = hash_file('sha256', $tmp);
                        $safe = 'e' . $eid . '_' . $cat . '_' . gmdate('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        $dest = $storageRoot . DIRECTORY_SEPARATOR . $safe;
                        if (!@move_uploaded_file($tmp, $dest)) {
                            $msg = 'ذخیره فایل انجام نشد.';
                        } else {
                            $uid = erp_auth_current_user_id() ?? 0;
                            $ins = @odbc_prepare($conn, 'INSERT INTO dbo.p360_employee_documents (employee_id, doc_title, doc_type, file_ref, doc_category, slot_no, mime_type, sha256, version_no, is_final, storage_path, uploaded_by_user_id)
                                VALUES (?,?,?,?,?,?,?,?,1,0,?,?)');
                            $title = $categories[$cat]['label'];
                            if ($ins && @odbc_execute($ins, [$eid, $title, $cat, $safe, $cat, 1, $mime, $sha, $dest, $uid])) {
                                $ok = true;
                                $msg = 'مدرک با موفقیت بارگذاری شد.';
                            } else {
                                @unlink($dest);
                                $msg = 'ثبت متادیتای مدرک ناموفق بود.';
                            }
                        }
                    }
                }
            }
        }
    }
}

if ($msg !== null) {
    echo '<div class="m360-alert ' . ($ok ? 'm360-alert-ok' : 'm360-alert-err') . '">' . p360hr_h($msg) . '</div>';
}

$csrf = erp_csrf_create_token('p360hr_doc_upload');
echo '<form class="m360-card m360-form" method="post" enctype="multipart/form-data" style="max-width:560px">';
echo '<input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '">';
echo '<label>دسته مدرک</label><select name="doc_category">';
foreach ($categories as $code => $meta) {
    echo '<option value="' . p360hr_h($code) . '">' . p360hr_h($meta['label']) . '</option>';
}
echo '</select>';
echo '<label>فایل (jpg/png/webp/pdf)</label><input type="file" name="doc_file" required>';
echo '<button class="m360-btn" type="submit">بارگذاری امن</button></form>';

echo '<h2>مدارک من</h2><table class="m360-table"><thead><tr><th>دسته</th><th>عنوان</th><th>نسخه</th><th>مشاهده</th></tr></thead><tbody>';
$rs = @odbc_prepare($conn, 'SELECT TOP 100 id, doc_category, doc_title, version_no FROM dbo.p360_employee_documents WHERE employee_id=? ORDER BY id DESC');
$any = false;
if ($rs && @odbc_execute($rs, [$eid])) {
    while ($r = odbc_fetch_array($rs)) {
        $any = true;
        $id = (int)($r['id'] ?? 0);
        echo '<tr><td>' . p360hr_h((string)($r['doc_category'] ?? '')) . '</td><td>' . p360hr_h((string)($r['doc_title'] ?? '')) . '</td><td>' . p360hr_h((string)($r['version_no'] ?? '')) . '</td><td><a href="hr-document-download.php?id=' . $id . '">دانلود محافظت‌شده</a></td></tr>';
    }
}
if (!$any) {
    echo '<tr><td colspan="4" class="m360-empty-state">موردی نیست.</td></tr>';
}
echo '</tbody></table>';
p360hr_layout_end();
