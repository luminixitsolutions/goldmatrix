# Sale Invoice & Database Study

Study of `sale-invoice.php`, `gm.sql`, and the save flow for making changes safely.

---

## 1. Sale Invoice Page (`sale-invoice.php`)

### Purpose
- **Create/Edit** sale invoices (SI-1, SI-2, …).
- Full-screen billing UI with customer, items (product list), payments, and totals.

### Data Loaded on Page Load (from `config.php` + DB)
| Source | Data |
|--------|------|
| `tbl_metal` | Metals for category tabs (Gold, Silver, Diamond & Stones, etc.) |
| `tbl_carat` | Karat/purity master |
| `tbl_location` | Location master |
| `tbl_categories` | Product categories |
| `tbl_branches` | Branches |
| `tbl_calculation_modes` | Calculation modes (Product Amount, Invoice Amount, etc.) |
| `tbl_customers` (sundry_debtors_id=29) | Bank accounts (excludes UPI/wallet names) |
| `tbl_sale_invoices` | Last invoice_no → next number SI-(n+1) |
| **Edit mode** (`?id=<invoice_id>`) | `tbl_sale_invoices` (full row), `tbl_sale_invoice_items` (with metal_id from product_characteristics), `tbl_sale_invoice_payments` |

### Key UI → Backend Mapping
- **Save** → POST to `ajax/save-sale-invoice.php` with: `order_no`, `customer_id`, `customer_name`, `order_date`, `items` (JSON), payments, totals, etc.
- **Print** → `sale-invoice-print.php?id=<invoice_id>` (only after save).

### Edit Flow
- URL: `sale-invoice.php?id=<id>`
- Invoice: `getRecord("SELECT * FROM tbl_sale_invoices WHERE id = ...")`
- Items: JOIN `tbl_sale_invoice_items` + `tbl_product_characteristics` for `metal_id`
- Payments: `getList("SELECT * FROM tbl_sale_invoice_payments WHERE invoice_id = ...")`
- Form fields normalized: `order_no` ← `invoice_no`, `supplier_name`/`supplier_id` ← `customer_name`/`customer_id`

---

## 2. Database: Sale Invoice Tables (`gm.sql`)

### 2.1 `tbl_sale_invoices`
| Column | Type | Notes |
|--------|------|--------|
| id | int(11) PK | AUTO_INCREMENT |
| invoice_no | varchar(50) | SI-1, SI-2, … |
| customer_id | int(11) | FK to tbl_customers (optional) |
| customer_name | varchar(255) | Required |
| against_of | varchar(100) | Optional ref |
| currency | varchar(10) | Default AED |
| ref_no | varchar(100) | |
| sales_person | varchar(255) | |
| invoice_date | date | |
| due_date | date | |
| layaways_id | int(11) | |
| fixing_type | varchar(50) | Standard / Hedging etc. |
| previous_balance | decimal(15,2) | |
| previous_gold | decimal(15,2) | |
| previous_silver | decimal(15,2) | |
| subtotal, additional_amt, net_total | decimal(15,2) | |
| reward_points, coupon_code, coupon_discount | | |
| discount_amt, discount_percent | decimal | discount_percent added by save script if missing |
| redeem_points | decimal(15,2) | |
| grand_total | decimal(15,2) | |
| advance_payment, metal_amt, round_off | decimal(15,2) | |
| paid_amt, balance_amt | decimal(15,2) | |
| adjusted_balance_used | decimal(14,2) | Optional column |
| group_name | varchar(255) | |
| comment | text | |
| payment_comments | text | JSON array |
| status | varchar(20) | Default 'draft' |
| created_by | int(11) | |
| created_at, updated_at | datetime | |
| use_previous_balance | tinyint(1) | 1 = used previous balance |
| previous_balance_used_amt | decimal(15,2) | Amount used from previous balance |

**Optional columns** (added by save script if missing):  
`previous_diamond`, `previous_gemstone`, `discount_percent`, `invoice_type` (used for SI numbering filter).

### 2.2 `tbl_sale_invoice_items`
| Column | Type | Notes |
|--------|------|------|
| id | int(11) PK | AUTO_INCREMENT |
| invoice_id | int(11) | FK → tbl_sale_invoices.id (ON DELETE CASCADE) |
| product_id | int(11) | FK → tbl_products |
| product_characteristic_id | int(11) | FK → tbl_product_characteristics |
| barcode | varchar(100) | |
| product_name | varchar(255) | |
| carat | varchar(50) | |
| stone_weight | decimal(10,3) | |
| quantity | decimal(10,2) | Default 1 |
| metal_qty | decimal(12,2) | Default 1 (added by save script if missing) |
| metal_weight | decimal(12,4) | (added by save script if missing) |
| gross_weight, less_weight | decimal(10,3) | |
| purity | decimal(10,2) | |
| purity_weight | decimal(10,3) | |
| final_weight, net_weight, pure_weight | decimal(10,3) | |
| rate | decimal(15,2) | |
| metal_value, metal_rate | decimal(15,2) | Optional columns |
| making_amount | decimal(15,2) | |
| amount | decimal(15,2) | |
| stone_amount, diamond_amount | decimal(15,2) | Optional |
| tax_amount | decimal(15,2) | |
| net_amount, net_amt_with_tax | decimal(15,2) | |
| design_no | varchar(100) | |
| calculation_type | varchar(100) | |
| diamond_category | varchar(50) | |
| location_id | int(11) | |
| status | tinyint(1) | Default 1 |
| created_at | datetime | |

**Optional column**: `images` (text/JSON) – save script checks with `SHOW COLUMNS` and stores image paths.

### 2.3 `tbl_sale_invoice_payments`
| Column | Type | Notes |
|--------|------|------|
| id | int(11) PK | AUTO_INCREMENT |
| invoice_id | int(11) | FK → tbl_sale_invoices.id (ON DELETE CASCADE) |
| payment_type | varchar(50) | Cash, Card, etc. |
| deposit_into | varchar(100) | |
| transaction_no | varchar(100) | |
| cheque_date | date | |
| purity_carat | varchar(50) | |
| amount | decimal(15,2) | |
| previous_balance_amount | decimal(15,2) | |
| current_order_amount | decimal(15,2) | |
| diamond_category | varchar(100) | |
| quantity | decimal(10,2) | |
| status | tinyint(1) | Default 1 |
| created_at | datetime | |

---

## 3. Related Tables (used by Sale Invoice)

### 3.1 `tbl_products`
- id, name, alternate_name, article, category_id, is_stock_item, status, created_at, updated_at.

### 3.2 `tbl_product_characteristics`
- Per-product variant: product_id, branch_id, metal_id, barcode, rate, value, opening_weight, purity_sale, diamond_category, location_id, etc.
- Used for: product selection, metal tab, branch/metal in stock.

### 3.3 `tbl_customers`
- id, name, contact/billing/shipping, sundry_debtors_id (29 = Bank Account), item_tax_data (JSON), etc.
- Sale invoice: customer_id, customer_name; bank list from sundry_debtors_id = 29.

### 3.4 `tbl_stock`
- product_id, product_characteristic_id, barcode, branch_id, metal_id, opening_*, final_weight, rate, value, current_weight, current_qty, **stock_type** ('opening','purchase','outward'), transaction_date, stock_journal_id.
- **Optional**: `reference_id`, `reference_type` – used to link outward entries to `sale_invoice` for reversal on edit.

### 3.5 Other referenced tables
- `tbl_metal`, `tbl_carat`, `tbl_location`, `tbl_categories`, `tbl_branches`, `tbl_calculation_modes` (all used in UI/options).

---

## 4. Save Flow (`ajax/save-sale-invoice.php`)

### 4.1 Request
- **Method**: POST only.
- **Session**: `$_SESSION['Admin']['id']` → created_by.
- **Key POST keys**: order_no, customer_id, customer_name, order_date, due_date, items (JSON or array), payments, subtotal, grand_total, paid_amt, balance_amt, previous_balance, use_previous_balance, previous_balance_used_amt, fixing_type, etc.

### 4.2 Insert vs Update
- **Update**: `order_id` > 0 → UPDATE `tbl_sale_invoices`, then DELETE items and payments for that invoice, then re-insert items and payments. If `tbl_stock` has `reference_id`/`reference_type`, outward rows with `reference_type = 'sale_invoice'` and `reference_id = invoice_id` are **deleted** (reversed) before re-insert.
- **Insert**: New row in `tbl_sale_invoices`; invoice_no generated as SI-(last+1) if not sent. Handles optional columns: adjusted_balance_used, previous_diamond/gemstone, use_previous_balance, previous_balance_used_amt.

### 4.3 Invoice number
- From last `tbl_sale_invoices` (optionally filtered by `invoice_type = 'sale'` if column exists). Duplicate check on insert; on update, allows same number for same id, else uniqueness check.

### 4.4 Items
- Loop over `$_POST['items']` (JSON decoded if string).
- For each: INSERT into `tbl_sale_invoice_items` (with optional columns checked via SHOW COLUMNS: images, diamond_category, metal_rate, calculation_type, diamond_amount, stone_amount, stone_weight, metal_value, metal_qty, metal_weight).
- Item images: `save_sale_invoice_item_images()` → files under `uploads/sale-invoice/{invoice_id}/`, JSON in `images` if column exists.
- **Stock**: If product has weight, insert **outward** row in `tbl_stock` (and set reference_id/reference_type if columns exist).

### 4.5 Payments
- Insert rows into `tbl_sale_invoice_payments` from POST payments array.

### 4.6 Other side effects (summary)
- Customer ledger / balance updates (if applicable).
- Hedging: `making_amount_for_sale_fixing` can create `tbl_purchase_fixing_direct` entry.
- Transaction: single `mysqli_begin_transaction`; on success `mysqli_commit`, on exception `mysqli_rollback`.

### 4.7 Response
- Success: `{ "status": "success", "message": "...", "order_id": <id>, "order_no": "<invoice_no>" }`.
- Error: `{ "status": "error", "message": "..." }`.

---

## 5. Where to Change What

| Goal | Where to change |
|------|------------------|
| Add/rename invoice header field | 1) `gm.sql`: alter `tbl_sale_invoices`. 2) `ajax/save-sale-invoice.php`: INSERT/UPDATE lists and POST mapping. 3) `sale-invoice.php`: form fields and edit load. 4) JS: collect in form and send in POST. |
| Add/rename item line field | 1) `gm.sql`: alter `tbl_sale_invoice_items`. 2) `ajax/save-sale-invoice.php`: item loop (optional columns via SHOW COLUMNS or explicit). 3) `sale-invoice.php`: table columns and edit payload; product modal if needed. |
| Add/rename payment field | 1) `gm.sql`: alter `tbl_sale_invoice_payments`. 2) `ajax/save-sale-invoice.php`: payment insert. 3) `sale-invoice.php`: payment UI and JS. |
| Change invoice number series | `sale-invoice.php` (next number display), `ajax/save-sale-invoice.php` (generation and duplicate check). Optional: `invoice_type` in `tbl_sale_invoices`. |
| Stock behaviour on sale | `ajax/save-sale-invoice.php`: outward insert and optional reference_id/reference_type; on update, delete outward by reference. `tbl_stock` schema in `gm.sql`. |
| Print layout / data | `sale-invoice-print.php` (and any shared print config from `tbl_invoice_print_settings`). |

---

## 6. Important Conventions

- **Escaping**: `esc()` and `mysqli_real_escape_string($conn, ...)` used in save script; never trust POST without escaping for SQL.
- **Optional columns**: Save script often uses `SHOW COLUMNS` and adds columns with `ALTER TABLE` if missing (e.g. discount_percent, previous_diamond/gemstone, metal_qty, metal_weight). For new fields, either add to `gm.sql` or keep same pattern in save script.
- **Edit = delete items/payments then re-insert**: No patch of existing rows; full replace. So any new item/payment field must be in both insert path and UI payload when editing.
- **Foreign keys**: `tbl_sale_invoice_items.invoice_id` → `tbl_sale_invoices.id` (CASCADE); `tbl_sale_invoice_payments.invoice_id` → `tbl_sale_invoices.id` (CASCADE). Product/characteristic/customer IDs are logical FKs; ensure consistency when changing schemas.

Use this document when adding columns, changing validations, or touching invoice/item/payment/stock logic so all layers (DB, PHP save, PHP UI, JS) stay in sync.
