<?php
declare(strict_types=1);
require_once __DIR__.'/inv360-db.php';
require_once __DIR__.'/inv360-auth.php';
require_once __DIR__.'/inv360-audit.php';
require_once __DIR__.'/inv360-validation.php';
require_once __DIR__.'/inv360-money.php';
require_once __DIR__.'/inv360-search.php';

function inv360_available_qty(array $bal): float {
    return (float)($bal['physical_qty']??$bal['PhysicalQty']??0)
        - (float)($bal['reserved_qty']??$bal['ReservedQty']??0)
        - (float)($bal['quarantine_qty']??$bal['QuarantineQty']??0)
        - (float)($bal['blocked_qty']??$bal['BlockedQty']??0);
}
function inv360_balance_get($conn, int $itemId, ?int $warehouseId, ?int $locationId): array {
    $row=inv360_one($conn,'SELECT TOP 1 * FROM dbo.inv360_stock_balances WHERE item_id=? AND ((? IS NULL AND warehouse_id IS NULL) OR warehouse_id=?) AND ((? IS NULL AND location_id IS NULL) OR location_id=?)',
        [$itemId,$warehouseId,$warehouseId,$locationId,$locationId]);
    if($row) return $row;
    inv360_exec($conn,'INSERT INTO dbo.inv360_stock_balances (item_id,warehouse_id,location_id,physical_qty,reserved_qty,quarantine_qty,in_transit_qty,blocked_qty,consignment_qty) VALUES (?,?,?,0,0,0,0,0,0)',[$itemId,$warehouseId,$locationId]);
    return inv360_one($conn,'SELECT TOP 1 * FROM dbo.inv360_stock_balances WHERE item_id=? AND ((? IS NULL AND warehouse_id IS NULL) OR warehouse_id=?) AND ((? IS NULL AND location_id IS NULL) OR location_id=?)',
        [$itemId,$warehouseId,$warehouseId,$locationId,$locationId]) ?? ['physical_qty'=>0,'reserved_qty'=>0,'quarantine_qty'=>0,'blocked_qty'=>0];
}
function inv360_balance_adjust($conn, int $itemId, ?int $warehouseId, ?int $locationId, float $physDelta, float $resDelta=0, float $quaDelta=0, float $blkDelta=0, float $trnDelta=0): array {
    $bal=inv360_balance_get($conn,$itemId,$warehouseId,$locationId);
    $id=(int)($bal['balance_id']??0);
    $phys=(float)($bal['physical_qty']??0)+$physDelta;
    $res=(float)($bal['reserved_qty']??0)+$resDelta;
    $qua=(float)($bal['quarantine_qty']??0)+$quaDelta;
    $blk=(float)($bal['blocked_qty']??0)+$blkDelta;
    $trn=(float)($bal['in_transit_qty']??0)+$trnDelta;
    if($phys<-0.0001||$res<-0.0001||$qua<-0.0001||$blk<-0.0001) return ['ok'=>false,'message'=>'موجودی منفی مجاز نیست.'];
    if(inv360_available_qty(['physical_qty'=>$phys,'reserved_qty'=>$res,'quarantine_qty'=>$qua,'blocked_qty'=>$blk])<-0.0001) return ['ok'=>false,'message'=>'موجودی آزاد کافی نیست.'];
    inv360_exec($conn,'UPDATE dbo.inv360_stock_balances SET physical_qty=?, reserved_qty=?, quarantine_qty=?, blocked_qty=?, in_transit_qty=?, updated_at=SYSUTCDATETIME() WHERE balance_id=?',[$phys,$res,$qua,$blk,$trn,$id]);
    $tot=(float)(inv360_scalar($conn,'SELECT ISNULL(SUM(physical_qty),0) FROM dbo.inv360_stock_balances WHERE item_id=?',[$itemId])??0);
    inv360_exec($conn,'UPDATE dbo.inv360_items SET quantity=?, updated_at=SYSUTCDATETIME() WHERE item_id=?',[$tot>0?$tot:0.001,$itemId]);
    return ['ok'=>true,'message'=>'موجودی به‌روز شد.'];
}
function inv360_doc_next_no($conn, string $prefix): string { return strtoupper($prefix).'-'.gmdate('YmdHis').'-'.random_int(100,999); }
function inv360_create_document($conn, array $data, int $userId): array {
    $docNo=inv360_doc_next_no($conn,(string)($data['prefix']??'DOC'));
    $ok=inv360_exec($conn,'INSERT INTO dbo.inv360_stock_documents (doc_no,doc_type,doc_status,source_warehouse_id,source_location_id,target_warehouse_id,target_location_id,reason_text,reference_type,reference_no,purpose_type,purpose_ref,future_jobcard_ref,cost_center,notes,created_by)
        VALUES (?,?,N\'draft\',?,?,?,?,?,?,?,?,?,?,?,?,?)',[
        $docNo,(string)$data['doc_type'],$data['source_warehouse_id']??null,$data['source_location_id']??null,$data['target_warehouse_id']??null,$data['target_location_id']??null,
        $data['reason']??null,$data['reference_type']??null,$data['reference_no']??null,$data['purpose_type']??null,$data['purpose_ref']??null,$data['future_jobcard_ref']??null,
        $data['cost_center']??null,$data['notes']??null,$userId]);
    if($ok===false) return ['ok'=>false,'message'=>'ایجاد سند ناموفق بود.','document_id'=>null];
    $id=(int)(inv360_scalar($conn,'SELECT TOP 1 document_id FROM dbo.inv360_stock_documents WHERE doc_no=?',[$docNo])??0);
    inv360_audit($conn,'STOCK_DOC',(string)$id,'CREATED',$docNo,$userId);
    return ['ok'=>true,'message'=>'سند ایجاد شد.','document_id'=>$id,'doc_no'=>$docNo];
}
function inv360_add_document_line($conn, int $docId, int $itemId, float $qty, $unitCost=null, ?string $note=null): array {
    if(is_array($unitCost)){ $note=isset($unitCost['note'])?(string)$unitCost['note']:$note; $unitCost=isset($unitCost['unit_cost'])?(float)$unitCost['unit_cost']:null; }
    elseif($unitCost!==null) $unitCost=(float)$unitCost;
    if($docId<1||$itemId<1||$qty<=0) return ['ok'=>false,'message'=>'قلم سند نامعتبر است.'];
    $doc=inv360_one($conn,'SELECT TOP 1 doc_status FROM dbo.inv360_stock_documents WHERE document_id=?',[$docId]);
    if(!$doc||strtolower((string)$doc['doc_status'])!=='draft') return ['ok'=>false,'message'=>'فقط سند پیش‌نویس قابل ویرایش است.'];
    $ok=inv360_exec($conn,'INSERT INTO dbo.inv360_stock_document_lines (document_id,item_id,qty,unit_cost,line_note) VALUES (?,?,?,?,?)',[$docId,$itemId,$qty,$unitCost,$note]);
    return $ok===false?['ok'=>false,'message'=>'ثبت قلم ناموفق بود.']:['ok'=>true,'message'=>'قلم ثبت شد.'];
}
function inv360_post_document($conn, int $docId, int $userId): array {
    $doc=inv360_one($conn,'SELECT TOP 1 * FROM dbo.inv360_stock_documents WHERE document_id=?',[$docId]);
    if(!$doc) return ['ok'=>false,'message'=>'سند یافت نشد.'];
    $status=strtolower((string)$doc['doc_status']);
    if($status==='posted') return ['ok'=>false,'message'=>'سند ثبت‌شده قابل تغییر نیست.'];
    if(!in_array($status,['draft','approved','submitted'],true)) return ['ok'=>false,'message'=>'وضعیت سند برای ثبت قطعی مجاز نیست.'];
    $lines=inv360_rows($conn,'SELECT * FROM dbo.inv360_stock_document_lines WHERE document_id=?',[$docId]);
    if($lines===[]) return ['ok'=>false,'message'=>'سند بدون قلم است.'];
    $type=strtolower((string)$doc['doc_type']);
    $srcW=isset($doc['source_warehouse_id'])?(int)$doc['source_warehouse_id']:null; $srcL=isset($doc['source_location_id'])?(int)$doc['source_location_id']:null;
    $tgtW=isset($doc['target_warehouse_id'])?(int)$doc['target_warehouse_id']:null; $tgtL=isset($doc['target_location_id'])?(int)$doc['target_location_id']:null;
    if($srcW===0)$srcW=null; if($srcL===0)$srcL=null; if($tgtW===0)$tgtW=null; if($tgtL===0)$tgtL=null;
    $receiptTypes=['purchase_receipt','transfer_receipt','return_from_consumption','customer_return_receipt','opening_receipt'];
    $issueTypes=['consumption_issue','sales_issue','scrap_issue','consignment_issue','direct_delivery','supplier_return_issue','supplier_return'];
    $transferTypes=['transfer','bin_move'];
    foreach($lines as $line){
        $itemId=(int)$line['item_id']; $qty=(float)$line['qty'];
        if(in_array($type,$receiptTypes,true)||$type==='adjustment_increase'){ $r=inv360_balance_adjust($conn,$itemId,$tgtW??$srcW,$tgtL??$srcL,$qty); if(empty($r['ok'])) return $r; }
        elseif(in_array($type,$issueTypes,true)||$type==='adjustment_decrease'){
            if($type==='adjustment_decrease' && trim((string)($doc['reason_text']??''))==='') return ['ok'=>false,'message'=>'تعدیل کاهش بدون دلیل مجاز نیست.'];
            $r=inv360_balance_adjust($conn,$itemId,$srcW??$tgtW,$srcL??$tgtL,-$qty); if(empty($r['ok'])) return $r;
        } elseif(in_array($type,$transferTypes,true)){
            $r1=inv360_balance_adjust($conn,$itemId,$srcW,$srcL,-$qty); if(empty($r1['ok'])) return $r1;
            $r2=inv360_balance_adjust($conn,$itemId,$tgtW,$tgtL,$qty); if(empty($r2['ok'])){ inv360_balance_adjust($conn,$itemId,$srcW,$srcL,$qty); return $r2; }
        } elseif($type==='reserve'){ $r=inv360_balance_adjust($conn,$itemId,$srcW??$tgtW,$srcL??$tgtL,0,$qty); if(empty($r['ok'])) return $r; }
        elseif($type==='release_reserve'){ $r=inv360_balance_adjust($conn,$itemId,$srcW??$tgtW,$srcL??$tgtL,0,-$qty); if(empty($r['ok'])) return $r; }
        else return ['ok'=>false,'message'=>'نوع سند پشتیبانی نشده است.'];
    }
    inv360_exec($conn,'UPDATE dbo.inv360_stock_documents SET doc_status=N\'posted\', posted_by=?, posted_at=SYSUTCDATETIME() WHERE document_id=?',[$userId,$docId]);
    inv360_audit($conn,'STOCK_DOC',(string)$docId,'POSTED',(string)$doc['doc_no'],$userId);
    return ['ok'=>true,'message'=>'سند با موفقیت ثبت قطعی شد.'];
}
function inv360_item_build_search_norm(array $p): string {
    return inv360_normalize_search(implode(' ',[$p['workshop_code']??'',$p['item_code']??'',$p['technical_code']??'',$p['item_name_fa']??'',$p['item_name_en']??'',$p['common_name']??'',$p['part_number']??'',$p['oem_code']??'',$p['alternative_codes']??'',$p['barcode']??'']));
}
function inv360_item_save($conn, array $data, int $userId, ?int $itemId=null): array {
    $name=trim((string)($data['item_name_fa']??'')); if($name==='') return ['ok'=>false,'message'=>'نام قطعه الزامی است.','part_id'=>null];
    $payload=[
        'workshop_code'=>trim((string)($data['workshop_code']??'')),
        'item_code'=>trim((string)($data['item_code']??$data['workshop_code']??'')),
        'technical_code'=>trim((string)($data['technical_code']??'')),
        'item_name_fa'=>$name,
        'item_name_en'=>trim((string)($data['item_name_en']??'')),
        'common_name'=>trim((string)($data['common_name']??'')),
        'part_number'=>trim((string)($data['part_number']??'')),
        'oem_code'=>trim((string)($data['oem_code']??'')),
        'alternative_codes'=>trim((string)($data['alternative_codes']??'')),
        'barcode'=>trim((string)($data['barcode']??'')),
        'brand'=>trim((string)($data['brand']??'')),
        'manufacturer'=>trim((string)($data['manufacturer']??'')),
        'country_of_origin'=>trim((string)($data['country']??'')),
        'item_type'=>trim((string)($data['item_type']??'spare_part')),
        'subcategory'=>trim((string)($data['subcategory']??'')),
        'family_name'=>trim((string)($data['family']??'')),
        'min_stock'=>(float)($data['min_stock']??0),
        'max_stock'=>($data['max_stock']??'')!==''?(float)$data['max_stock']:null,
        'reorder_point'=>(float)($data['reorder_point']??0),
        'item_status'=>trim((string)($data['item_status']??'active')),
    ];
    $payload['search_norm']=inv360_item_build_search_norm($payload);
    if($itemId&&$itemId>0){
        $ok=inv360_exec($conn,'UPDATE dbo.inv360_items SET workshop_code=?,item_code=?,technical_code=?,item_name_fa=?,item_name_en=?,common_name=?,part_number=?,oem_code=?,alternative_codes=?,barcode=?,brand=?,manufacturer=?,country_of_origin=?,item_type=?,subcategory=?,family_name=?,min_stock=?,max_stock=?,reorder_point=?,item_status=?,search_norm=?,updated_at=SYSUTCDATETIME() WHERE item_id=?',
            [$payload['workshop_code'],$payload['item_code'],$payload['technical_code'],$payload['item_name_fa'],$payload['item_name_en'],$payload['common_name'],$payload['part_number'],$payload['oem_code'],$payload['alternative_codes'],$payload['barcode'],$payload['brand'],$payload['manufacturer'],$payload['country_of_origin'],$payload['item_type'],$payload['subcategory'],$payload['family_name'],$payload['min_stock'],$payload['max_stock'],$payload['reorder_point'],$payload['item_status'],$payload['search_norm'],$itemId]);
        if($ok===false) return ['ok'=>false,'message'=>'به‌روزرسانی کالا ناموفق بود.','part_id'=>$itemId];
        inv360_audit($conn,'ITEM',(string)$itemId,'UPDATED',$payload['item_name_fa'],$userId);
        return ['ok'=>true,'message'=>'کالا به‌روز شد.','part_id'=>$itemId];
    }
    $ok=inv360_exec($conn,'INSERT INTO dbo.inv360_items (workshop_code,item_code,technical_code,item_name_fa,item_name_en,common_name,part_number,oem_code,alternative_codes,barcode,brand,manufacturer,country_of_origin,item_type,subcategory,family_name,min_stock,max_stock,reorder_point,item_status,search_norm,quantity,created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0.001,?)',
        [$payload['workshop_code'],$payload['item_code'],$payload['technical_code'],$payload['item_name_fa'],$payload['item_name_en'],$payload['common_name'],$payload['part_number'],$payload['oem_code'],$payload['alternative_codes'],$payload['barcode'],$payload['brand'],$payload['manufacturer'],$payload['country_of_origin'],$payload['item_type'],$payload['subcategory'],$payload['family_name'],$payload['min_stock'],$payload['max_stock'],$payload['reorder_point'],$payload['item_status'],$payload['search_norm'],$userId]);
    if($ok===false) return ['ok'=>false,'message'=>'ثبت کالا ناموفق بود.','part_id'=>null];
    $id=(int)(inv360_scalar($conn,'SELECT TOP 1 item_id FROM dbo.inv360_items WHERE technical_code=? AND workshop_code=? ORDER BY item_id DESC',[$payload['technical_code'],$payload['workshop_code']])??0);
    inv360_audit($conn,'ITEM',(string)$id,'CREATED',$payload['item_name_fa'],$userId);
    return ['ok'=>true,'message'=>'کالا ثبت شد.','part_id'=>$id];
}