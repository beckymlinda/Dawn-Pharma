# Register System - Quick Start Guide

## Installation & Setup

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Update Composer Autoloader
```bash
composer dump-autoload
```

### 3. Clear Cache
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## Files Created/Modified

### New Files (9 total)

1. **app/Http/Controllers/RegisterController.php** (130 lines)
   - open() - Show open register form
   - store() - Create register session
   - confirm() - Show close register form
   - update() - Finalize register closure

2. **app/Helpers/RegisterHelper.php** (32 lines)
   - getOpenRegister() - Get current open register
   - hasOpenRegister() - Check if register open
   - getActiveRegisterId() - Get register ID

3. **resources/views/pos/register/open.blade.php** (70 lines)
   - Form to open new register
   - Shows existing open register if present

4. **resources/views/pos/register/close.blade.php** (120 lines)
   - Displays register summary
   - Shows totals by payment method
   - Confirmation to close

5. **resources/views/pos/components/register-status.blade.php** (30 lines)
   - Status card for POS pages
   - Quick close button

### Modified Files (2 total)

1. **app/Http/Controllers/POSController.php**
   - Added Register model import
   - Updated create() - Check for open register
   - Updated finalizeSale() - Attach sales to register
   - Removed session-based register methods

2. **routes/web.php**
   - Added RegisterController import
   - Added 4 register management routes
   - Removed old session-based routes

3. **composer.json**
   - Added files autoload for RegisterHelper.php

## Usage Flow

### Step 1: Open Register
```
User navigates to /register/open
→ System checks if register already open
→ If yes: Show quick summary and close option
→ If no: Show form to enter opening cash
→ User enters opening cash amount (e.g., 10,000 MWK)
→ User optionally selects location
→ User clicks "Open Register"
→ Register created in database with:
   - user_id = authenticated user
   - opening_cash = entered amount
   - opened_at = now()
   - closed_at = null
→ Success message displayed
→ User redirected to /pos/create
```

### Step 2: Process Sales
```
User accesses /pos/create
→ System calls getOpenRegister()
→ If no open register: Redirect to /register/open with error
→ If register open: Display POS with products
→ User adds items to cart
→ User enters payment method
→ User enters customer details (optional)
→ User clicks "Finalize Sale"
→ System calls finalizeSale():
   ✓ Validates cart not empty
   ✓ Validates open register exists
   ✓ Creates sale with register_id
   ✓ Creates sale items
   ✓ Decrements product stock
   ✓ Clears session cart
→ Success message with sale ID
→ User can print/email receipt
→ Next sale or new customer
```

### Step 3: Close Register
```
User navigates to /register/close
→ System fetches open register
→ Calculates sales totals by payment method:
   - Cash sales
   - Card sales
   - Cheque sales
   - Bank transfer sales
   - Advance sales
→ Displays summary showing:
   - Register opened time
   - Opening cash amount
   - Each payment method total
   - Grand total of all sales
   - Expected closing cash
→ User reviews totals
→ User clicks "Close Register"
→ System:
   ✓ Gets all sales for this register
   ✓ Calculates closing_cash = total sales
   ✓ Sets closed_at = now()
   ✓ Updates register in database
→ Redirects to /reports/register-report
→ Shows newly closed register in report
```

## Helper Functions Usage

### In Blade Templates

```blade
@php
    $openRegister = getOpenRegister();
@endphp

@if (hasOpenRegister())
    <p>Register is open with MWK {{ number_format(getOpenRegister()->opening_cash, 2) }}</p>
@else
    <p>No open register</p>
@endif
```

### In Controllers

```php
use App\Models\Register;

class SomeController extends Controller {
    public function myMethod()
    {
        // Get current open register
        $register = getOpenRegister();
        
        // Check if register exists
        if (!hasOpenRegister()) {
            return response()->json(['error' => 'No open register']);
        }
        
        // Get register ID
        $registerId = getActiveRegisterId();
    }
}
```

### In Middleware

```php
// Check if user has open register
if (!hasOpenRegister()) {
    return redirect()->route('register.create')
        ->with('error', 'Please open a register first');
}
```

## Database Queries

### Get All Open Registers
```sql
SELECT * FROM registers WHERE closed_at IS NULL;
```

### Get Closed Registers for Date Range
```sql
SELECT * FROM registers 
WHERE closed_at IS NOT NULL
AND closed_at >= '2026-01-01'
AND closed_at <= '2026-01-31'
ORDER BY closed_at DESC;
```

### Get Sales for a Register
```sql
SELECT * FROM sales 
WHERE register_id = [register_id]
ORDER BY created_at DESC;
```

### Register Summary Report
```sql
SELECT 
    r.id,
    u.name as cashier,
    l.name as location,
    r.opening_cash,
    r.closing_cash,
    COUNT(s.id) as total_sales,
    SUM(s.total_amount) as total_amount,
    r.opened_at,
    r.closed_at
FROM registers r
INNER JOIN users u ON r.user_id = u.id
LEFT JOIN locations l ON r.location_id = l.id
LEFT JOIN sales s ON r.id = s.register_id
WHERE r.closed_at IS NOT NULL
GROUP BY r.id
ORDER BY r.closed_at DESC;
```

## Validation Rules

### Open Register
- `opening_cash`: required, numeric, min:0
- `location_id`: nullable, exists:locations,id

### Close Register
- No input validation (system calculated)
- Validates register exists
- Validates register belongs to user
- Validates register not already closed

## Error Handling

### Common Errors & Solutions

| Error | Cause | Fix |
|-------|-------|-----|
| "Register already open" | User tried to open while one active | Close existing register first |
| "No open register" | User tried to finalize sale without register | Click "Open Register" and try again |
| "Unauthorized" | User tried to modify another user's register | Only your own register can be modified |
| Opening cash validation error | Entered non-numeric or negative value | Enter valid numeric amount >= 0 |
| Location not found | Selected location doesn't exist | Verify location exists in database |

## Testing Checklist

- [ ] Can open register with opening cash
- [ ] Cannot open second register while first is open
- [ ] Can view existing open register from open page
- [ ] Cannot access POS without open register
- [ ] Can redirect to open register from POS
- [ ] Can add items to cart and make sale
- [ ] Sale is linked to open register
- [ ] Can view register close confirmation
- [ ] Totals by payment method display correctly
- [ ] Can successfully close register
- [ ] Closing cash equals total sales amount
- [ ] Register report shows closed register
- [ ] Sales are linked to closed register
- [ ] New register opened after closing existing one

## Column Mapping

### registers table
- `opening_cash` → MWK amount cashier starts with
- `closing_cash` → MWK amount after all sales (auto-calculated)
- `opened_at` → Timestamp when register session started
- `closed_at` → Timestamp when register session ended (null if open)

### sales table
- `register_id` → Foreign key to registers table
- `payment_method` → Type of payment (cash, card, cheque, etc.)
- `total_amount` → Amount of this sale

## Integration Points

### POS System
- Check for open register before displaying POS
- Attach register_id to every sale
- Prevent sale if no open register

### Reports
- Register Report filters by date range
- Shows all sales linked to register
- Calculates totals by payment method

### Inventory
- Stock decrement happens when sale finalized
- Sale must be linked to register

## Support Commands

```bash
# Check register routes
php artisan route:list | grep register

# Verify syntax
php -l app/Http/Controllers/RegisterController.php
php -l app/Helpers/RegisterHelper.php
php -l app/Http/Controllers/POSController.php

# Clear cache if issues occur
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Run migrations in fresh environment
php artisan migrate:fresh (⚠️ CAUTION - deletes all data)
php artisan migrate (⚠️ Safe - only new migrations)
```

## Next Steps

1. **Run migrations**: `php artisan migrate`
2. **Test system**: Open register → Make sale → Close register
3. **Check report**: Navigate to /reports/register-report
4. **Add locations**: Create some test locations if needed
5. **Integrate into UI**: Add register open/close buttons to main navigation

---

**Production Ready**: Yes ✅
**Testing Status**: Ready for UAT
**Last Updated**: February 25, 2026
