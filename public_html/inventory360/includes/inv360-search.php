<?php
declare(strict_types=1);

require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-validation.php';

/**
 * LIKE-based search (Full Text Search not installed on this SQL instance).
 * Exact matches ranked first, then partials.
 *
 * @return list<array<string,mixed>>
 */
function inv360_search_items($conn, string $q, int $limit = 50): array
{
    $q = trim($q);
    if ($q === '') {
        return [];
    }
    $norm = inv360_normalize_search($q);
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
    $likeNorm = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $norm) . '%';
    $limit = max(1, min(200, $limit));

    $sql = "SELECT TOP {$limit}
        p.PartID, p.WorkshopCode, p.InternalCode, p.TechnicalCode, p.ItemName, p.ItemNameEn, p.CommonName,
        p.ManufacturerBrand, p.PartNumber, p.OEMCode, p.AlternativeCodes, p.Barcode, p.ItemStatus, p.IsActive,
        ISNULL((SELECT SUM(b.PhysicalQty) FROM dbo.Inv360StockBalances b WHERE b.PartID = p.PartID), p.Quantity) AS PhysicalQty,
        ISNULL((SELECT SUM(b.ReservedQty) FROM dbo.Inv360StockBalances b WHERE b.PartID = p.PartID), 0) AS ReservedQty,
        ISNULL((SELECT SUM(b.QuarantineQty) FROM dbo.Inv360StockBalances b WHERE b.PartID = p.PartID), 0) AS QuarantineQty,
        ISNULL((SELECT SUM(b.BlockedQty) FROM dbo.Inv360StockBalances b WHERE b.PartID = p.PartID), 0) AS BlockedQty,
        CASE
          WHEN p.WorkshopCode = ? OR p.InternalCode = ? OR p.TechnicalCode = ? OR p.PartNumber = ? OR p.OEMCode = ? OR p.Barcode = ? THEN 0
          WHEN p.ItemName = ? THEN 1
          ELSE 2
        END AS RankScore
      FROM dbo.Parts p
      WHERE ISNULL(p.IsDeleted, 0) = 0
        AND (
          p.WorkshopCode LIKE ? OR p.InternalCode LIKE ? OR p.TechnicalCode LIKE ?
          OR p.ItemName LIKE ? OR p.ItemNameEn LIKE ? OR p.CommonName LIKE ?
          OR p.PartNumber LIKE ? OR p.OEMCode LIKE ? OR p.AlternativeCodes LIKE ? OR p.Barcode LIKE ?
          OR p.SearchNorm LIKE ?
        )
      ORDER BY RankScore ASC, p.PartID DESC";

    $params = [
        $q, $q, $q, $q, $q, $q, $q,
        $like, $like, $like,
        $like, $like, $like,
        $like, $like, $like, $like,
        $likeNorm,
    ];
    $rows = inv360_rows($conn, $sql, $params);
    foreach ($rows as &$r) {
        $phys = (float)($r['PhysicalQty'] ?? 0);
        $res = (float)($r['ReservedQty'] ?? 0);
        $qua = (float)($r['QuarantineQty'] ?? 0);
        $blk = (float)($r['BlockedQty'] ?? 0);
        $r['AvailableQty'] = $phys - $res - $qua - $blk;
    }
    unset($r);
    return $rows;
}
