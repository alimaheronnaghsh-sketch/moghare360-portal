# MOGHARE360 Database Cleanup Plan

## Current Databases

1. moghare360
Legacy / Archive

2. moghare360_StockCenter
Stock and inventory candidate database

3. moghare360D
Development ERP candidate database

## Current Decision

No new table should be created before database audit is completed.

## Required Backup Files

- Site public_html backup
- moghare360 .bak
- moghare360_StockCenter .bak
- moghare360D .bak
- GitHub repository backup

## Required Audit Files

- database_tables_inventory_20260614.csv
- duplicate_tables_20260614.csv
- database_columns_inventory_20260614.csv
- database_indexes_inventory_20260614.csv

## Target Decision

Build one clean ERP database or approve moghare360D as master development database.

## Rule

No table is deleted.
No database is dropped.
No schema is changed.
All decisions must be documented first.
