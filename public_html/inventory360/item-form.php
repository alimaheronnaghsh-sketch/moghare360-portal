<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/inv360-bootstrap.php';
inv360_require_login();

$conn = inv360_db();
$user = inv360_current_user();
$msg = '';
$ok = false;
$errors = [];
$openStep = 1;
$completenessInfo = null;

$drawerMode = isset($_GET['drawer']) && (string)$_GET['drawer'] === '1';
if (!$drawerMode && isset($_POST['drawer']) && (string)$_POST['drawer'] === '1') {
    $drawerMode = true;
}

$editId = 0;
if (isset($_GET['id'])) {
    $editId = (int)$_GET['id'];
} elseif (isset($_GET['part_id'])) {
    $editId = (int)$_GET['part_id'];
} elseif (isset($_POST['part_id'])) {
    $editId = (int)$_POST['part_id'];
}

$existing = ($editId > 0) ? inv360_items_get($conn, $editId) : null;
$isEdit = is_array($existing);

$itemTypesNew = inv360_i1r2a_item_types_new();
$marketGrades = inv360_i1r2a_market_grades();
$categories = inv360_i1r2a_categories($conn, 'spare_part');

$stockAuthOpts = ['original' => 'اصلی', 'company' => 'شرکتی', 'unknown' => 'نامشخص'];
$qualityOpts = ['A' => 'A', 'B' => 'B', 'C' => 'C'];
$testOpts = [
    'tested_pass' => 'تست‌شده — قبول',
    'not_tested' => 'تست‌نشده',
    'conditional' => 'مشروط',
    'defective' => 'معیوب',
];
$lifecycleLabels = [
    'DRAFT' => inv360_lifecycle_fa('DRAFT'),
    'NEEDS_COMPLETION' => inv360_lifecycle_fa('NEEDS_COMPLETION'),
    'PENDING_MANAGER_APPROVAL' => inv360_lifecycle_fa('PENDING_MANAGER_APPROVAL'),
    'ACTIVE' => inv360_lifecycle_fa('ACTIVE'),
    'REJECTED' => inv360_lifecycle_fa('REJECTED'),
    'INACTIVE' => inv360_lifecycle_fa('INACTIVE'),
];

/**
 * @param array<string,mixed> $src
 * @return array<string,string>
 */
function inv360_i1r2a_form_values(array $src, bool $fromPost): array
{
    $g = static function (array $src, string $key, string $alt = '') use ($fromPost): string {
        if ($fromPost) {
            return trim((string)($src[$key] ?? ''));
        }
        if ($key === 'country') {
            return trim((string)($src['country_of_origin'] ?? ''));
        }
        if ($key === 'tech_specs') {
            return trim((string)($src['tech_specs'] ?? ''));
        }

        return trim((string)($src[$key] ?? ($alt !== '' ? ($src[$alt] ?? '') : '')));
    };

    return [
        'item_type' => $g($src, 'item_type'),
        'category_id' => $fromPost ? trim((string)($src['category_id'] ?? '')) : (string)(int)($src['category_id'] ?? 0),
        'market_grade' => $g($src, 'market_grade'),
        'part_condition' => $g($src, 'part_condition'),
        'authenticity_grade' => $g($src, 'authenticity_grade'),
        'stock_authenticity' => $g($src, 'stock_authenticity'),
        'quality_grade' => $g($src, 'quality_grade'),
        'test_status' => $g($src, 'test_status'),
        'warranty_days' => $fromPost ? trim((string)($src['warranty_days'] ?? '')) : (isset($src['warranty_days']) && $src['warranty_days'] !== null ? (string)$src['warranty_days'] : ''),
        'donor_vehicle_info' => $g($src, 'donor_vehicle_info'),
        'physical_condition_notes' => $g($src, 'physical_condition_notes'),
        'item_name_fa' => $g($src, 'item_name_fa', 'ItemName'),
        'item_name_en' => $g($src, 'item_name_en'),
        'common_name' => $g($src, 'common_name'),
        'brand' => $g($src, 'brand', 'ManufacturerBrand'),
        'manufacturer' => $g($src, 'manufacturer'),
        'country' => $g($src, 'country'),
        'item_code' => $g($src, 'item_code'),
        'workshop_code' => $g($src, 'workshop_code', 'WorkshopCode'),
        'part_number' => $g($src, 'part_number', 'PartNumber'),
        'manufacturer_part_number' => $g($src, 'manufacturer_part_number'),
        'barcode' => $g($src, 'barcode'),
        'alternative_codes' => $g($src, 'alternative_codes'),
        'supplier_code' => $g($src, 'supplier_code'),
        'oem_code' => $g($src, 'oem_code', 'OEMCode'),
        'technical_code' => $g($src, 'technical_code', 'TechnicalCode'),
        'item_notes' => $g($src, 'item_notes'),
        'tech_specs' => $g($src, 'tech_specs'),
        'min_stock' => $fromPost ? trim((string)($src['min_stock'] ?? '0')) : (string)($src['min_stock'] ?? '0'),
        'max_stock' => $fromPost ? trim((string)($src['max_stock'] ?? '')) : (isset($src['max_stock']) && $src['max_stock'] !== null && $src['max_stock'] !== '' ? (string)$src['max_stock'] : ''),
        'reorder_point' => $fromPost ? trim((string)($src['reorder_point'] ?? '0')) : (string)($src['reorder_point'] ?? '0'),
        'item_status' => $g($src, 'item_status') !== '' ? $g($src, 'item_status') : 'inactive',
        'lifecycle_status' => $g($src, 'lifecycle_status') !== '' ? $g($src, 'lifecycle_status') : 'DRAFT',
    ];
}

$values = $isEdit ? inv360_i1r2a_form_values($existing, false) : inv360_i1r2a_form_values([], true);
if (!$isEdit) {
    $values['item_type'] = ''; // no default on create
    $values['item_status'] = 'inactive';
    $values['lifecycle_status'] = 'DRAFT';
    $values['min_stock'] = '0';
}

$legacyType = $values['item_type'] !== '' && !isset($itemTypesNew[$values['item_type']]);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    inv360_csrf_require();
    $values = inv360_i1r2a_form_values($_POST, true);
    $saveAction = trim((string)($_POST['save_action'] ?? 'draft'));
    if (!in_array($saveAction, ['draft', 'submit'], true)) {
        $saveAction = 'draft';
    }

    if ($isEdit && is_array($existing)) {
        if ($values['oem_code'] === '') {
            $values['oem_code'] = trim((string)($existing['oem_code'] ?? ''));
        }
        if ($values['technical_code'] === '') {
            $values['technical_code'] = trim((string)($existing['technical_code'] ?? ''));
        }
        $values['max_stock'] = isset($existing['max_stock']) && $existing['max_stock'] !== null && $existing['max_stock'] !== ''
            ? (string)$existing['max_stock'] : '';
        $values['reorder_point'] = (string)($existing['reorder_point'] ?? '0');
    } else {
        $values['max_stock'] = '';
        $values['reorder_point'] = '0';
        $values['technical_code'] = ''; // system-generated on create
    }

    if ($values['item_type'] === '') {
        $errors['item_type'] = 'نوع کالا را انتخاب کنید.';
        $openStep = 1;
    } elseif (!isset($itemTypesNew[$values['item_type']]) && !($isEdit && $legacyType)) {
        // allow legacy type only when editing existing
        if (!($isEdit && isset($existing['item_type']))) {
            $errors['item_type'] = 'نوع کالا معتبر نیست.';
            $openStep = 1;
        }
    }

    if ($values['item_type'] === 'spare_part') {
        if ((int)$values['category_id'] <= 0) {
            $errors['category_id'] = 'گروه اصلی را انتخاب کنید.';
            $openStep = 2;
        }
        if ($values['market_grade'] === '' || !isset($marketGrades[$values['market_grade']])) {
            $errors['market_grade'] = 'رده قطعه را انتخاب کنید.';
            $openStep = 2;
        }
        if ($values['market_grade'] === 'used_stock') {
            if ($values['stock_authenticity'] === '') {
                $errors['stock_authenticity'] = 'اصالت استوک الزامی است.';
                $openStep = 2;
            }
            if ($values['quality_grade'] === '') {
                $errors['quality_grade'] = 'درجه کیفیت الزامی است.';
                $openStep = 2;
            }
            if ($values['test_status'] === '') {
                $errors['test_status'] = 'وضعیت تست الزامی است.';
                $openStep = 2;
            }
        }
    } else {
        $values['category_id'] = '';
        $values['market_grade'] = '';
        $values['stock_authenticity'] = '';
        $values['quality_grade'] = '';
        $values['test_status'] = '';
        $values['warranty_days'] = '';
        $values['donor_vehicle_info'] = '';
        $values['physical_condition_notes'] = '';
    }

    if ($values['item_name_fa'] === '' && $saveAction === 'submit') {
        $errors['item_name_fa'] = 'نام فارسی کالا الزامی است.';
        $openStep = 3;
    }
    if ($values['min_stock'] !== '' && (!is_numeric($values['min_stock']) || (float)$values['min_stock'] < 0)) {
        $errors['min_stock'] = 'حداقل موجودی هشدار باید عدد صفر یا بزرگ‌تر باشد.';
        $openStep = 3;
    }

    if ($errors === []) {
        if ($values['item_name_fa'] === '') {
            $errors['item_name_fa'] = 'برای ذخیره، نام فارسی کالا الزامی است.';
            $openStep = 3;
        }
    }

    if ($errors === []) {
        $post = $values;
        $post['save_action'] = $saveAction;
        $post['country'] = $values['country'];
        $post['family'] = '';
        $post['subcategory'] = '';
        inv360_i1r2a_apply_market_defaults($post);

        $res = inv360_item_save($conn, $post, (int)$user['user_id'], $isEdit ? $editId : null);
        $ok = !empty($res['ok']);
        $msg = (string)($res['message'] ?? '');
        $savedId = (int)($res['part_id'] ?? 0);
        $completenessInfo = $res['completeness'] ?? null;

        if ($ok && $savedId > 0) {
            if ($drawerMode) {
                header('Content-Type: text/html; charset=UTF-8');
                echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><title>ذخیره</title>';
                echo '<link rel="stylesheet" href="../assets/css/m360-suite-theme.css"></head><body style="background:transparent;margin:0">';
                echo '<div style="padding:2rem;text-align:center;color:#e8f5ef">' . inv360_h($msg) . '</div>';
                echo '<script>(function(){var p={source:"moghare360-inv360-item-form",type:"inv360-item-saved",ok:true,itemId:' . $savedId . ',message:' . json_encode($msg, JSON_UNESCAPED_UNICODE) . '};';
                echo 'try{if(window.parent&&window.parent!==window)window.parent.postMessage(p,window.location.origin);}catch(e){}})();</script></body></html>';
                exit;
            }
            header('Location: item-view.php?id=' . $savedId);
            exit;
        }
        if (!$ok) {
            $errors['_form'] = $msg !== '' ? $msg : 'ذخیره ناموفق بود.';
            $openStep = 4;
            if (is_array($completenessInfo) && !empty($completenessInfo['missing'])) {
                foreach ($completenessInfo['missing'] as $m) {
                    $errors['miss_' . ($m['code'] ?? '')] = (string)($m['label'] ?? '');
                }
            }
        }
    } else {
        $ok = false;
        $msg = 'لطفاً موارد الزامی را تکمیل کنید.';
    }
}

// Live completeness preview
$previewPayload = $values;
$previewPayload['category_id'] = (int)$values['category_id'];
inv360_i1r2a_apply_market_defaults($previewPayload);
$completenessInfo = $completenessInfo ?? inv360_i1r2a_completeness_result(inv360_i1r2a_missing_fields($previewPayload));

$formAction = 'item-form.php';
if ($isEdit) {
    $formAction .= '?id=' . (int)$editId;
}
if ($drawerMode) {
    $formAction .= (strpos($formAction, '?') !== false ? '&' : '?') . 'drawer=1';
}

$catName = '';
foreach ($categories as $c) {
    if ((string)(int)($c['category_id'] ?? 0) === (string)(int)$values['category_id']) {
        $catName = (string)($c['category_name_fa'] ?? '');
        break;
    }
}

$v = static function (string $key) use ($values): string {
    return inv360_h((string)($values[$key] ?? ''));
};
$hasErr = static function (string $key) use ($errors): bool {
    return isset($errors[$key]);
};

$pageTitle = $isEdit ? 'ویرایش کالا' : 'ثبت کالا';
if ($drawerMode) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . inv360_h($pageTitle) . '</title>';
    echo '<link rel="stylesheet" href="../assets/css/m360-suite-theme.css">';
} else {
    inv360_layout_start($pageTitle, 'items.php');
    inv360_flash_render($msg, $ok);
}

$stepLabels = [
    1 => 'انتخاب نوع کالا',
    2 => 'طبقه‌بندی قطعه یدکی',
    3 => 'اطلاعات کالا',
    4 => 'بررسی نهایی و ارسال',
];
?>
<style>
.inv360-i1r2{--g:#1f6b4a;--line:rgba(31,107,74,.32);--muted:#9bb5a8;--bg:#0f1f18;--text:#e8f5ef;color:var(--text);font-family:inherit;box-sizing:border-box}
.inv360-i1r2 *,.inv360-i1r2 *::before,.inv360-i1r2 *::after{box-sizing:border-box}
.inv360-i1r2.drawer-embed{min-height:100vh;background:var(--bg);display:flex;flex-direction:column}
.inv360-i1r2.page-mode{max-width:760px;margin:0 auto 2rem;background:#142820;border:1px solid var(--line);border-radius:14px;overflow:hidden}
.inv360-i1r2 .hd{padding:.85rem 1rem;border-bottom:1px solid var(--line)}
.inv360-i1r2 .hd h2{margin:0;font-size:1.05rem;color:#dff3e8}
.inv360-i1r2 .hd p{margin:.25rem 0 0;color:var(--muted);font-size:.86rem}
.inv360-i1r2 .steps{display:flex;gap:.35rem;flex-wrap:wrap;padding:.7rem 1rem;border-bottom:1px solid var(--line)}
.inv360-i1r2 .step-btn{flex:1;min-width:6.5rem;border:1px solid var(--line);background:transparent;color:var(--muted);border-radius:8px;padding:.45rem .5rem;font:inherit;font-size:.8rem;cursor:pointer}
.inv360-i1r2 .step-btn.is-active{background:rgba(31,107,74,.18);color:#dff3e8;border-color:#2f8a60;font-weight:700}
.inv360-i1r2 .step-btn.is-error{border-color:#c45c5c;color:#f0c0c0}
.inv360-i1r2 .step-btn:disabled{opacity:.45;cursor:not-allowed}
.inv360-i1r2 .body{flex:1;overflow:auto;padding:1rem}
.inv360-i1r2 .panel{display:none}.inv360-i1r2 .panel.is-active{display:block}
.inv360-i1r2 .errs{border:1px solid #c45c5c;background:rgba(120,30,30,.25);color:#f5d0d0;padding:.7rem .9rem;border-radius:10px;margin-bottom:.9rem}
.inv360-i1r2 .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem 1rem}
.inv360-i1r2 .field{display:flex;flex-direction:column;gap:.3rem;min-width:0}
.inv360-i1r2 .field.span2{grid-column:1/-1}
.inv360-i1r2 label{font-size:.86rem;color:#c5ddd1;font-weight:600}
.inv360-i1r2 .req{color:#f0a0a0}
.inv360-i1r2 input,.inv360-i1r2 select,.inv360-i1r2 textarea{width:100%;border:1px solid #2a4a3a;border-radius:8px;padding:.55rem .65rem;background:#0c1a14;color:#e8f5ef;font:inherit}
.inv360-i1r2 .field.is-invalid input,.inv360-i1r2 .field.is-invalid select{border-color:#c45c5c}
.inv360-i1r2 .ferr{color:#f0a8a8;font-size:.78rem}
.inv360-i1r2 .type-cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.5rem}
.inv360-i1r2 .type-cards label{display:flex;gap:.5rem;align-items:center;border:1px solid #2a4a3a;border-radius:10px;padding:.65rem .75rem;cursor:pointer;font-weight:600;color:#dff3e8}
.inv360-i1r2 .type-cards label:has(input:checked){border-color:#2f8a60;background:rgba(31,107,74,.2)}
.inv360-i1r2 .type-cards input{width:auto}
.inv360-i1r2 .mkt{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.45rem}
.inv360-i1r2 .mkt label{display:flex;align-items:center;justify-content:center;gap:.35rem;border:1px solid #2a4a3a;border-radius:8px;padding:.55rem;cursor:pointer;text-align:center}
.inv360-i1r2 .mkt label:has(input:checked){border-color:#2f8a60;background:rgba(31,107,74,.2)}
.inv360-i1r2 .mkt input{width:auto}
.inv360-i1r2 .used-box{margin-top:.75rem;padding:.75rem;border:1px solid var(--line);border-radius:10px;background:rgba(0,0,0,.12)}
.inv360-i1r2 .review{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.45rem .8rem;background:rgba(31,107,74,.12);border-radius:10px;padding:.75rem .9rem;margin-bottom:.85rem}
.inv360-i1r2 .review strong{display:block;font-size:.75rem;color:var(--muted);font-weight:600}
.inv360-i1r2 .badge{display:inline-block;padding:.15rem .5rem;border-radius:999px;background:rgba(31,107,74,.25);font-size:.78rem}
.inv360-i1r2 .badge.warn{background:rgba(160,110,20,.35)}
.inv360-i1r2 .foot{position:sticky;bottom:0;display:flex;justify-content:space-between;gap:.65rem;flex-wrap:wrap;padding:.85rem 1rem;border-top:1px solid var(--line);background:rgba(15,31,24,.96)}
.inv360-i1r2 .btn-primary{background:#1f6b4a;color:#fff;border:0;border-radius:8px;padding:.65rem 1.1rem;font:inherit;font-weight:700;cursor:pointer}
.inv360-i1r2 .btn-secondary,.inv360-i1r2 .btn-ghost{background:transparent;color:#9fd0b8;border:1px solid #2f8a60;border-radius:8px;padding:.65rem 1.1rem;font:inherit;font-weight:600;cursor:pointer}
.inv360-i1r2 .btn-ghost{border-color:transparent;color:var(--muted)}
.inv360-i1r2 .btn-primary:disabled,.inv360-i1r2 .btn-secondary:disabled{opacity:.45;cursor:not-allowed}
.inv360-i1r2 .legacy{margin-top:.75rem;border:1px solid var(--line);border-radius:10px;padding:.55rem .75rem}
.inv360-i1r2 .legacy summary{cursor:pointer;color:#b8d0c4;font-size:.86rem;font-weight:600}
.inv360-i1r2 .hint{color:var(--muted);font-size:.78rem}
@media (max-width:720px){.inv360-i1r2 .grid,.inv360-i1r2 .review,.inv360-i1r2 .type-cards,.inv360-i1r2 .mkt{grid-template-columns:1fr}.inv360-i1r2 .field.span2{grid-column:auto}}
</style>

<section class="inv360-i1r2 <?= $drawerMode ? 'drawer-embed' : 'page-mode' ?>" id="inv360_i1r2_root"
  data-step="<?= (int)$openStep ?>"
  data-drawer="<?= $drawerMode ? '1' : '0' ?>"
  data-type="<?= inv360_h($values['item_type']) ?>">
  <div class="hd">
    <h2><?= inv360_h($pageTitle) ?></h2>
    <p><?= $isEdit ? 'ویرایش شناسنامه کالا' : 'ثبت شناسنامه کالای جدید' ?><?php if ($isEdit): ?> · شناسه <?= (int)$editId ?><?php endif; ?>
      · <span class="badge"><?= inv360_h(inv360_lifecycle_fa($values['lifecycle_status'])) ?></span>
      · <span class="badge <?= ($completenessInfo['status'] ?? '') === 'COMPLETE' ? '' : 'warn' ?>"><?= inv360_h(inv360_completeness_badge((string)($completenessInfo['status'] ?? ''), $completenessInfo['score'] ?? null)) ?></span>
    </p>
  </div>

  <div class="steps" role="tablist" aria-label="مراحل">
    <?php foreach ($stepLabels as $n => $lab):
        $show = !($n === 2 && $values['item_type'] !== '' && $values['item_type'] !== 'spare_part');
        if (!$show) {
            continue;
        }
        ?>
      <button type="button" class="step-btn<?= $openStep === $n ? ' is-active' : '' ?>" data-goto="<?= (int)$n ?>" role="tab"><?= (int)$n ?>. <?= inv360_h($lab) ?></button>
    <?php endforeach; ?>
  </div>

  <form method="post" action="<?= inv360_h($formAction) ?>" id="inv360_i1r2_form" style="display:flex;flex-direction:column;flex:1;min-height:0">
    <?= inv360_csrf_field() ?>
    <?php if ($drawerMode): ?><input type="hidden" name="drawer" value="1"><?php endif; ?>
    <?php if ($isEdit): ?><input type="hidden" name="part_id" value="<?= (int)$editId ?>"><?php endif; ?>
    <input type="hidden" name="oem_code" value="<?= $v('oem_code') ?>">
    <input type="hidden" name="technical_code" value="<?= $v('technical_code') ?>">
    <input type="hidden" name="max_stock" value="<?= $v('max_stock') ?>">
    <input type="hidden" name="reorder_point" value="<?= $v('reorder_point') ?>">
    <input type="hidden" name="item_status" value="<?= $v('item_status') ?>">
    <input type="hidden" name="lifecycle_status" value="<?= $v('lifecycle_status') ?>">
    <input type="hidden" name="save_action" id="save_action" value="draft">

    <div class="body">
      <?php if ($errors !== []): ?>
        <div class="errs" role="alert"><strong>لطفاً موارد زیر را بررسی کنید</strong>
          <ul><?php foreach ($errors as $err): ?><li><?= inv360_h((string)$err) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <div class="panel<?= $openStep === 1 ? ' is-active' : '' ?>" data-step-panel="1">
        <div class="type-cards" role="radiogroup" aria-label="نوع کالا">
          <?php foreach ($itemTypesNew as $code => $label): ?>
            <label>
              <input type="radio" name="item_type" value="<?= inv360_h($code) ?>"<?= $values['item_type'] === $code ? ' checked' : '' ?>>
              <?= inv360_h($label) ?>
            </label>
          <?php endforeach; ?>
          <?php if ($isEdit && $legacyType): ?>
            <label>
              <input type="radio" name="item_type" value="<?= $v('item_type') ?>" checked>
              مقدار قبلی: <?= $v('item_type') ?>
            </label>
          <?php endif; ?>
        </div>
        <?php if ($hasErr('item_type')): ?><div class="ferr"><?= inv360_h((string)$errors['item_type']) ?></div><?php endif; ?>
        <p class="hint" style="margin-top:.75rem">تا انتخاب نوع کالا، ادامه ممکن نیست.</p>
      </div>

      <div class="panel<?= $openStep === 2 ? ' is-active' : '' ?>" data-step-panel="2" data-spare-only="1">
        <div class="grid">
          <div class="field span2<?= $hasErr('category_id') ? ' is-invalid' : '' ?>">
            <label for="category_id">گروه اصلی <span class="req">*</span></label>
            <select id="category_id" name="category_id">
              <option value="">انتخاب کنید…</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['category_id'] ?>"<?= (string)(int)$values['category_id'] === (string)(int)$c['category_id'] ? ' selected' : '' ?>>
                  <?= inv360_h((string)$c['category_name_fa']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if ($hasErr('category_id')): ?><span class="ferr"><?= inv360_h((string)$errors['category_id']) ?></span><?php endif; ?>
          </div>
          <div class="field span2<?= $hasErr('market_grade') ? ' is-invalid' : '' ?>">
            <label>رده قطعه <span class="req">*</span></label>
            <div class="mkt" role="radiogroup">
              <?php foreach ($marketGrades as $code => $label): ?>
                <label>
                  <input type="radio" name="market_grade" value="<?= inv360_h($code) ?>"<?= $values['market_grade'] === $code ? ' checked' : '' ?>>
                  <?= inv360_h($label) ?>
                </label>
              <?php endforeach; ?>
            </div>
            <?php if ($hasErr('market_grade')): ?><span class="ferr"><?= inv360_h((string)$errors['market_grade']) ?></span><?php endif; ?>
          </div>
        </div>
        <div class="used-box" id="used_stock_box" hidden>
          <div class="grid">
            <div class="field<?= $hasErr('stock_authenticity') ? ' is-invalid' : '' ?>">
              <label for="stock_authenticity">اصالت استوک <span class="req">*</span></label>
              <select id="stock_authenticity" name="stock_authenticity">
                <option value="">انتخاب…</option>
                <?php foreach ($stockAuthOpts as $c => $l): ?>
                  <option value="<?= inv360_h($c) ?>"<?= $values['stock_authenticity'] === $c ? ' selected' : '' ?>><?= inv360_h($l) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field<?= $hasErr('quality_grade') ? ' is-invalid' : '' ?>">
              <label for="quality_grade">درجه کیفیت <span class="req">*</span></label>
              <select id="quality_grade" name="quality_grade">
                <option value="">انتخاب…</option>
                <?php foreach ($qualityOpts as $c => $l): ?>
                  <option value="<?= inv360_h($c) ?>"<?= $values['quality_grade'] === $c ? ' selected' : '' ?>><?= inv360_h($l) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field<?= $hasErr('test_status') ? ' is-invalid' : '' ?>">
              <label for="test_status">وضعیت تست <span class="req">*</span></label>
              <select id="test_status" name="test_status">
                <option value="">انتخاب…</option>
                <?php foreach ($testOpts as $c => $l): ?>
                  <option value="<?= inv360_h($c) ?>"<?= $values['test_status'] === $c ? ' selected' : '' ?>><?= inv360_h($l) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="warranty_days">گارانتی (روز)</label>
              <input id="warranty_days" name="warranty_days" type="number" min="0" step="1" value="<?= $v('warranty_days') ?>">
            </div>
            <div class="field span2">
              <label for="donor_vehicle_info">اطلاعات خودروی اهداکننده</label>
              <input id="donor_vehicle_info" name="donor_vehicle_info" maxlength="500" value="<?= $v('donor_vehicle_info') ?>">
            </div>
            <div class="field span2">
              <label for="physical_condition_notes">وضعیت فیزیکی / نقص‌ها</label>
              <textarea id="physical_condition_notes" name="physical_condition_notes" rows="2" maxlength="1000"><?= $v('physical_condition_notes') ?></textarea>
            </div>
          </div>
          <p class="hint">الزام تصویر واقعی استوک در فاز رسانه (بعدی) اعمال می‌شود.</p>
        </div>
        <p class="hint">تطبیق خودرو چندردیفه در فاز بعدی اضافه می‌شود.</p>
      </div>

      <div class="panel<?= $openStep === 3 ? ' is-active' : '' ?>" data-step-panel="3">
        <div class="grid">
          <div class="field span2<?= $hasErr('item_name_fa') ? ' is-invalid' : '' ?>">
            <label for="item_name_fa">نام فارسی کالا <span class="req">*</span></label>
            <input id="item_name_fa" name="item_name_fa" maxlength="200" value="<?= $v('item_name_fa') ?>" autocomplete="off">
          </div>
          <div class="field">
            <label for="item_name_en">نام انگلیسی</label>
            <input id="item_name_en" name="item_name_en" maxlength="200" value="<?= $v('item_name_en') ?>" dir="ltr">
          </div>
          <div class="field">
            <label for="common_name">نام رایج</label>
            <input id="common_name" name="common_name" maxlength="200" value="<?= $v('common_name') ?>">
          </div>
          <div class="field">
            <label for="brand">برند قطعه</label>
            <input id="brand" name="brand" maxlength="120" value="<?= $v('brand') ?>">
          </div>
          <div class="field">
            <label for="manufacturer">سازنده</label>
            <input id="manufacturer" name="manufacturer" maxlength="120" value="<?= $v('manufacturer') ?>">
          </div>
          <div class="field">
            <label for="country">کشور سازنده</label>
            <input id="country" name="country" maxlength="80" value="<?= $v('country') ?>">
          </div>
          <div class="field">
            <label for="item_code">کد داخلی مقاره 360</label>
            <input id="item_code" name="item_code" maxlength="80" value="<?= $v('item_code') ?>" dir="ltr">
          </div>
          <div class="field">
            <label for="workshop_code">کد کارگاه</label>
            <input id="workshop_code" name="workshop_code" maxlength="80" value="<?= $v('workshop_code') ?>" dir="ltr">
          </div>
          <div class="field">
            <label for="part_number">شماره فنی اصلی خودرو</label>
            <input id="part_number" name="part_number" maxlength="120" value="<?= $v('part_number') ?>" dir="ltr">
          </div>
          <div class="field">
            <label for="manufacturer_part_number">شماره فنی سازنده قطعه</label>
            <input id="manufacturer_part_number" name="manufacturer_part_number" maxlength="120" value="<?= $v('manufacturer_part_number') ?>" dir="ltr">
          </div>
          <div class="field">
            <label for="barcode">بارکد</label>
            <input id="barcode" name="barcode" maxlength="120" value="<?= $v('barcode') ?>" dir="ltr">
          </div>
          <div class="field">
            <label for="supplier_code">کد تأمین‌کننده</label>
            <input id="supplier_code" name="supplier_code" maxlength="120" value="<?= $v('supplier_code') ?>" dir="ltr">
          </div>
          <div class="field span2">
            <label for="alternative_codes">کدهای جایگزین</label>
            <input id="alternative_codes" name="alternative_codes" maxlength="400" value="<?= $v('alternative_codes') ?>" dir="ltr">
          </div>
          <div class="field span2">
            <label for="item_notes">توضیحات</label>
            <textarea id="item_notes" name="item_notes" rows="2" maxlength="2000"><?= $v('item_notes') ?></textarea>
          </div>
          <div class="field span2">
            <label for="tech_specs">مشخصات فنی</label>
            <textarea id="tech_specs" name="tech_specs" rows="2" maxlength="1000"><?= $v('tech_specs') ?></textarea>
          </div>
          <div class="field<?= $hasErr('min_stock') ? ' is-invalid' : '' ?>">
            <label for="min_stock">حداقل موجودی مورد نیاز برای هشدار</label>
            <input id="min_stock" name="min_stock" type="number" min="0" step="0.001" value="<?= $v('min_stock') ?>">
            <span class="hint">صفر یعنی هشدار غیرفعال. این فیلد موجودی فعلی نیست.</span>
          </div>
        </div>
        <details class="legacy">
          <summary>شناسه‌های سیستمی / قدیمی (فقط مشاهده)</summary>
          <div class="grid" style="margin-top:.65rem">
            <div class="field">
              <label>شناسه فنی سیستم</label>
              <input value="<?= $v('technical_code') ?>" readonly disabled dir="ltr">
            </div>
            <?php if ($values['oem_code'] !== ''): ?>
              <div class="field">
                <label>شناسه قدیمی</label>
                <input value="<?= $v('oem_code') ?>" readonly disabled dir="ltr">
              </div>
            <?php endif; ?>
          </div>
        </details>
        <p class="hint">آپلود تصویر و فایل در فاز رسانه اضافه می‌شود.</p>
      </div>

      <div class="panel<?= $openStep === 4 ? ' is-active' : '' ?>" data-step-panel="4">
        <div class="review">
          <div><strong>نوع کالا</strong><?= inv360_h($itemTypesNew[$values['item_type']] ?? ($values['item_type'] !== '' ? $values['item_type'] : '—')) ?></div>
          <div><strong>گروه اصلی</strong><?= inv360_h($catName !== '' ? $catName : '—') ?></div>
          <div><strong>رده قطعه</strong><?= inv360_h($marketGrades[$values['market_grade']] ?? '—') ?></div>
          <div><strong>نام کالا</strong><?= $v('item_name_fa') !== '' ? $v('item_name_fa') : '—' ?></div>
          <div><strong>برند / سازنده</strong><?= inv360_h(trim($values['brand'] . ' / ' . $values['manufacturer'], ' /') ?: '—') ?></div>
          <div><strong>شناسه‌ها</strong><?= inv360_h(trim(implode(' · ', array_filter([$values['item_code'], $values['workshop_code'], $values['part_number'], $values['manufacturer_part_number'], $values['barcode']]))) ?: '—') ?></div>
          <div><strong>حداقل موجودی هشدار</strong><?= $v('min_stock') ?></div>
          <div><strong>وضعیت تکمیل</strong><?= inv360_h(inv360_completeness_badge((string)($completenessInfo['status'] ?? ''), $completenessInfo['score'] ?? null)) ?></div>
        </div>
        <?php if (!empty($completenessInfo['missing'])): ?>
          <div class="errs" role="status">
            <strong>کسری اطلاعات</strong>
            <ul>
              <?php foreach ($completenessInfo['missing'] as $m): ?>
                <li><?= inv360_h((string)($m['label'] ?? '')) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <p class="hint">تأیید مدیریتی در صف جداگانه انجام می‌شود؛ این مرحله فقط ارسال می‌کند.</p>
      </div>
    </div>

    <div class="foot">
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <button type="button" class="btn-ghost" id="btn_cancel"><?= $drawerMode ? 'انصراف' : 'بازگشت' ?></button>
        <button type="button" class="btn-secondary" id="btn_prev" hidden>قبلی</button>
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <button type="button" class="btn-secondary" id="btn_next">بعدی</button>
        <button type="submit" class="btn-secondary" id="btn_draft" name="inv360_save" value="1" hidden>ذخیره پیش‌نویس</button>
        <button type="submit" class="btn-primary" id="btn_submit" hidden>ارسال برای تأیید مدیریتی</button>
      </div>
    </div>
  </form>
</section>
<script>
(function(){
  var root=document.getElementById('inv360_i1r2_root');
  if(!root) return;
  var form=document.getElementById('inv360_i1r2_form');
  var step=parseInt(root.getAttribute('data-step')||'1',10)||1;
  var drawer=root.getAttribute('data-drawer')==='1';
  var dirty=false;
  var btnPrev=document.getElementById('btn_prev');
  var btnNext=document.getElementById('btn_next');
  var btnDraft=document.getElementById('btn_draft');
  var btnSubmit=document.getElementById('btn_submit');
  var btnCancel=document.getElementById('btn_cancel');
  var saveAction=document.getElementById('save_action');
  var usedBox=document.getElementById('used_stock_box');

  function selectedType(){
    var el=form.querySelector('input[name="item_type"]:checked');
    return el?el.value:'';
  }
  function selectedMarket(){
    var el=form.querySelector('input[name="market_grade"]:checked');
    return el?el.value:'';
  }
  function visibleSteps(){
    var t=selectedType();
    var steps=[1];
    if(t==='spare_part') steps.push(2);
    steps.push(3,4);
    return steps;
  }
  function syncUsed(){
    if(usedBox) usedBox.hidden = selectedMarket()!=='used_stock';
  }
  function syncStepButtons(){
    var vis=visibleSteps();
    root.querySelectorAll('.step-btn').forEach(function(btn){
      var g=parseInt(btn.getAttribute('data-goto'),10);
      btn.hidden = vis.indexOf(g)===-1;
      btn.classList.toggle('is-active', g===step);
    });
  }
  function showStep(n){
    var vis=visibleSteps();
    if(vis.indexOf(n)===-1) n=vis[0];
    step=n;
    root.setAttribute('data-step',String(step));
    root.querySelectorAll('[data-step-panel]').forEach(function(p){
      var id=parseInt(p.getAttribute('data-step-panel'),10);
      p.classList.toggle('is-active', id===step);
    });
    syncStepButtons();
    var idx=vis.indexOf(step);
    if(btnPrev) btnPrev.hidden = idx<=0;
    if(btnNext) btnNext.hidden = idx>=vis.length-1;
    if(btnDraft) btnDraft.hidden = step!==4;
    if(btnSubmit) btnSubmit.hidden = step!==4;
    if(btnNext) btnNext.disabled = (step===1 && !selectedType());
    syncUsed();
  }
  function requestClose(){
    if(dirty && !window.confirm('تغییرات ذخیره نشده است. از فرم خارج می‌شوید؟')) return;
    if(drawer){
      try{window.parent.postMessage({source:'moghare360-inv360-item-form',type:'inv360-item-close',dirty:dirty},window.location.origin);}catch(e){}
      return;
    }
    window.location.href='items.php';
  }

  form.addEventListener('input',function(){dirty=true;});
  form.addEventListener('change',function(){
    dirty=true;
    if(step===1) showStep(1);
    syncUsed();
    if(selectedType() && selectedType()!=='spare_part' && step===2) showStep(3);
  });
  root.querySelectorAll('.step-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
      var g=parseInt(btn.getAttribute('data-goto'),10);
      if(g===1 || selectedType()) showStep(g);
    });
  });
  if(btnPrev) btnPrev.addEventListener('click',function(){
    var vis=visibleSteps(); var i=vis.indexOf(step); if(i>0) showStep(vis[i-1]);
  });
  if(btnNext) btnNext.addEventListener('click',function(){
    if(step===1 && !selectedType()) return;
    var vis=visibleSteps(); var i=vis.indexOf(step); if(i<vis.length-1) showStep(vis[i+1]);
  });
  if(btnCancel) btnCancel.addEventListener('click',requestClose);
  if(btnDraft) btnDraft.addEventListener('click',function(){ if(saveAction) saveAction.value='draft'; dirty=false; });
  if(btnSubmit) btnSubmit.addEventListener('click',function(){ if(saveAction) saveAction.value='submit'; dirty=false; });
  window.addEventListener('message',function(ev){
    if(ev.origin!==window.location.origin) return;
    var d=ev.data||{};
    if(d.source==='moghare360-inv360-items' && d.type==='inv360-request-close') requestClose();
  });
  showStep(step);
})();
</script>
<?php
if ($drawerMode) {
    echo '</body></html>';
} else {
    inv360_layout_end();
}
