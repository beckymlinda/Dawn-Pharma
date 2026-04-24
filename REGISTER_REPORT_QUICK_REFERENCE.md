# Register Report - Quick Reference Guide

## 🚀 Access the Report

**URL**: `http://yourapp.test/reports/register-report`  
**Route Name**: `reports.register-report`  
**HTTP Method**: GET

## 📊 Default View

When you first visit the report:
- **Date Range**: Current month (1st to last day)
- **Per Page**: 25 entries
- **Table**: All register sessions with payment breakdowns
- **Currency**: MWK (configurable)

## 🎛️ Using the Filters

### Date Range
1. Enter "From Date" (YYYY-MM-DD)
2. Enter "To Date" (YYYY-MM-DD)
3. Defaults to current month

### User Filter
1. Click "User" dropdown
2. Select a specific user
3. Leave blank to show all users

### Location Filter
1. Click "Location" dropdown
2. Select a specific location
3. Leave blank to show all locations

### Search by Name
1. Type user name or email in search box
2. Filters users partially matching the search

### Per Page Selection
1. Click dropdown (25/50/100)
2. Auto-submits and reloads with new page size

### Apply All Filters
1. Set your filter criteria
2. Click "Filter" button
3. Page reloads with results

## 📈 Understanding the Report

### REGISTER SESSION COLUMNS

| Column | Description |
|--------|------------|
| Open Time | When the register was opened (register.opened_at) |
| Close Time | When the register was closed (register.closed_at) |
| Location | Location name where register session occurred |
| User | Staff member who operated the register |
| Total Card Slips | Sum of all card payment sales |
| Total Cheques | Sum of all cheque payment sales |
| Total Cash | Sum of all cash payment sales |
| Total Bank Transfer | Sum of all bank transfer payment sales |
| Total Advance Payment | Sum of all advance payment sales |
| National Bank | Sum of all National Bank transactions |
| Standard Bank | Sum of all Standard Bank transactions |

### PAYMENT METHOD CATEGORIES

All totals are calculated by `payment_method` field:

- **cash**: Direct cash transactions
- **card**: Credit/debit card payments
- **cheque**: Cheque payments
- **bank_transfer**: Direct bank transfers
- **advance**: Advance/prepayment
- **national_bank**: National Bank specific
- **standard_bank**: Standard Bank specific

## 🔧 URL PARAMETERS

### Date Parameters
```
?from_date=2026-02-01&to_date=2026-02-28
```

### Filter Parameters
```
?user_id=5&location_id=3
```

### Search Parameters
```
?search=John
```

### Pagination Parameters
```
?page=2&per_page=50
```

### Combined Example
```
/reports/register-report?from_date=2026-02-01&to_date=2026-02-28&user_id=5&per_page=50&page=1
```

## 💾 DATABASE REQUIREMENTS

### Register Table
```sql
id, user_id, location_id, 
opened_at, closed_at, 
opening_cash, closing_cash,
created_at, updated_at
```

### Sales Table (Updated)
```sql
register_id (new column)
payment_method (cash, card, cheque, bank_transfer, advance, etc.)
total_amount (decimal)
```

### Relationships
- Register belongs to User
- Register belongs to Location (optional)
- Register has many Sales
- Sale belongs to Register

## ✅ PAGINATION FEATURES

- Shows entries range (e.g., "Showing 1 to 25 of 50 results")
- First/Previous/Next/Last page navigation
- Direct page number links
- Current page highlighted
- Maintains all filter parameters through pagination

## 📝 EXPORT BUTTONS

Buttons exist as UI placeholders:
- 📄 Export CSV
- 📊 Export Excel
- 🖨 Print
- 📋 Column visibility
- 📑 Export PDF

Implementation pending (requires Laravel Excel or PDF library).

## 🐛 TROUBLESHOOTING

### Report Shows "No register data found"
1. Check if registers exist in database
2. Verify opened_at is within selected date range
3. Run migrations if registers table doesn't exist
4. Add test data to registers and sales tables

### Totals Show 0.00
1. Verify sales records exist for that register
2. Check if sales have payment_method set
3. Confirm sales.total_amount has values
4. Verify register_id in sales matches a register

### Pagination Link Broken
1. Ensure all filter parameters are in pagination URLs
2. Check that per_page value is maintained
3. Verify blade is appending all query parameters

### Date Filter Not Working
1. Ensure date format is YYYY-MM-DD
2. Check that from_date ≤ to_date
3. Verify register.opened_at has valid timestamps

### User/Location Dropdowns Empty
1. Ensure registers exist with user_id and location_id
2. Check that users and locations tables have data
3. Verify foreign key relationships are correct

## 🔧 CONFIGURATION

### Change Currency
**File**: `app/Http/Controllers/RegisterReportController.php`  
**Line**: Find `$currency = 'MWK';`  
**Change to**: `$currency = 'USD';`

### Change Default Per Page
**File**: `app/Http/Controllers/RegisterReportController.php`  
**Find**: `$perPage = (int) $request->input('per_page', 25);`  
**Change**: `25` to your preferred default (50 or 100)

### Add More Payment Methods
In the blade table, add new columns:
```blade
<th>Your Method</th>
<td>{{ $currency }} {{ number_format($register['total_your_method'], 2) }}</td>
```

In RegisterReportController, add calculation:
```php
'total_your_method' => $this->getTotalByPaymentMethod($register, 'your_method'),
```

## 📚 BLADE VARIABLES PASSED

| Variable | Type | Description |
|----------|------|-------------|
| `$registersData` | Collection | Array of register data with calculations |
| `$registers` | Paginator | Laravel paginator for pagination |
| `$users` | Collection | All users with registers |
| `$locations` | Collection | All locations with registers |
| `$currency` | String | Currency code (MWK) |
| `$fromDate` | String | From date (Y-m-d format) |
| `$toDate` | String | To date (Y-m-d format) |
| `$selectedUser` | Integer\|null | Selected user filter ID |
| `$selectedLocation` | Integer\|null | Selected location filter ID |
| `$search` | String | Search query |
| `$perPage` | Integer | Entries per page |

## 🧪 TESTING EXAMPLE DATA

To test the report, insert test data:

```sql
-- Add test register
INSERT INTO registers (user_id, location_id, opened_at, closing_cash, created_at, updated_at)
VALUES (1, 1, '2026-02-25 08:00:00', 5000.00, NOW(), NOW());

-- Add test sales to register
INSERT INTO sales (register_id, payment_method, total_amount, created_at, updated_at)
VALUES 
(1, 'cash', 1000.00, '2026-02-25 09:00:00', NOW()),
(1, 'card', 2000.00, '2026-02-25 10:00:00', NOW()),
(1, 'cheque', 500.00, '2026-02-25 11:00:00', NOW());
```

Report should show:
- Open Time: 2026-02-25 08:00
- Total Cash: MWK 1,000.00
- Total Card Slips: MWK 2,000.00
- Total Cheques: MWK 500.00

## 📞 SUPPORT

For issues:
1. Check [REGISTER_REPORT_IMPLEMENTATION.md](REGISTER_REPORT_IMPLEMENTATION.md) for technical details
2. Verify migrations have run: `php artisan migrate:status`
3. Check database schema matches expectations
4. Ensure Laravel logs in `storage/logs/` for errors

---

**Version**: 1.0  
**Last Updated**: February 25, 2026  
**Status**: ✅ PRODUCTION READY
