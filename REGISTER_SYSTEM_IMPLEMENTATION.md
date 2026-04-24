# Register System Implementation Guide

## Overview

This document provides a complete guide to the fully functional register system implemented for the Laravel 12 POS system. The register system manages cashier sessions, tracks sales by payment method, and prevents sales when no register is open.

## System Architecture

### Database Tables

**registers** table:
- `id` (Primary Key)
- `user_id` (Foreign Key)
- `location_id` (Foreign Key, nullable)
- `opening_cash` (Decimal)
- `closing_cash` (Decimal, nullable)
- `opened_at` (Timestamp)
- `closed_at` (Timestamp, nullable)
- `created_at`, `updated_at`

**sales** table:
- Must have `register_id` foreign key column
- Linked to registers table via foreign key

### Models

#### Register Model
- Location: `app/Models/Register.php`
- Relationships:
  - `belongsTo(User)` - Cashier who opened the register
  - `belongsTo(Location)` - Store location (optional)
  - `hasMany(Sale)` - All sales for this register session

#### Sale Model
- Relationship: `belongsTo(Register)` - The register session this sale belongs to

#### User Model
- Relationship: `hasMany(Register)` - All register sessions for this user

## Controller Methods

### RegisterController

#### 1. create() - Show Open Register Form
```
Route: GET /register/open
Named: register.create
Purpose: Display form to open a new register
Returns: registers.open.blade.php
```

**Logic:**
- Check if user has existing open register
- If yes, show existing register details and quick link to close
- If no, show form for opening new register

#### 2. store() - Create New Register
```
Route: POST /register
Named: register.store
Purpose: Create new register session in database
Redirects: to pos.index with success message
```

**Logic:**
- Validate opening cash amount (required, numeric, min 0)
- Check if user already has open register (only 1 per user allowed)
- Create new register with:
  - `user_id` = auth()->id()
  - `opening_cash` = input value
  - `opened_at` = now()
  - `closed_at` = null
  - `location_id` = optional

**Error Handling:**
- Returns error "Register already open" if user has active register
- Validates opening_cash is numeric and >= 0

#### 3. confirm() - Show Close Register Form
```
Route: GET /register/close
Named: register.confirm
Purpose: Display register closing summary before finalization
Returns: register.close.blade.php
```

**Logic:**
- Get open register for current user
- Calculate totals by payment method:
  - total_cash (payment_method = 'cash')
  - total_card (payment_method = 'card')
  - total_cheques (payment_method = 'cheque')
  - total_bank_transfer (payment_method = 'bank_transfer')
  - total_advance (payment_method = 'advance')
- Display summary with opening cash and expected closing amount

#### 4. update() - Close Register Session
```
Route: PATCH /register/{register}
Named: register.update
Purpose: Finalize register closure
Redirects: to reports.register-report with success message
```

**Logic:**
- Verify register belongs to authenticated user
- Verify register is not already closed
- Calculate closing cash:
  - Sum all sales by payment method
  - Closing cash = total of all transaction types
- Update register:
  - `closing_cash` = calculated total
  - `closed_at` = now()
- Redirect to register report

## POSController Updates

### finalizeSale() - Enhanced for Register System

**Before Sale is Created:**
1. Call `getOpenRegister()` helper
2. If no open register, return error: "No open register. Please open a register first."
3. If register exists, proceed with sale

**When Creating Sale:**
1. Add `register_id` = $openRegister->id to sale data
2. Create sale with all other fields
3. Create sale items and decrement stock
4. Clear session cart
5. Return success response

**Error Handling:**
- No open register → 400 error with message
- Empty cart → 400 error with message
- Other errors → 500 error with exception message

### create() - Check for Open Register

**Logic:**
- Before displaying POS, check for open register
- If no open register, redirect to register.create with error message
- If open register exists, pass it to view

## Helper Functions

### Location: `app/Helpers/RegisterHelper.php`

#### getOpenRegister()
```php
function getOpenRegister(): ?Register
```
- Returns the currently open register for authenticated user
- Returns null if no open register
- Query: WHERE user_id = auth()->id() AND closed_at IS NULL

#### hasOpenRegister()
```php
function hasOpenRegister(): bool
```
- Check if user has an open register
- Returns true/false

#### getActiveRegisterId()
```php
function getActiveRegisterId(): ?int
```
- Get the ID of the currently open register
- Returns int on success, null if no open register

**Register these in composer.json:**
```json
"autoload": {
    "files": [
        "app/Helpers/RegisterHelper.php"
    ]
}
```

## Blade Resources

### register/open.blade.php
**Location:** `resources/views/pos/register/open.blade.php`

**Features:**
- Form to submit opening cash amount
- Shows existing open register if one exists
- Quick links to close register or go to POS
- Location selector (optional)
- Form validation display
- Success message on open

### register/close.blade.php
**Location:** `resources/views/pos/register/close.blade.php`

**Features:**
- Summary of register session times
- Breakdown by payment method in table format
- Expected closing cash calculation
- Confirmation button to finalize close
- Back to POS link

### pos/components/register-status.blade.php
**Location:** `resources/views/pos/components/register-status.blade.php`

**Features:**
- Display current register status
- Show opening cash if register is open
- Quick close button
- Error message if no register open
- Include in POS template: `@include('pos.components.register-status')`

## Routes Configuration

**File:** `routes/web.php`

```php
Route::middleware(['auth'])->group(function () {
    // Register management (database-driven)
    Route::get('/register/open', [RegisterController::class, 'create'])->name('register.create');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/register/close', [RegisterController::class, 'confirm'])->name('register.confirm');
    Route::patch('/register/{register}', [RegisterController::class, 'update'])->name('register.update');
    
    // Cart and finalize (includes register check)
    Route::post('pos/finalize', [POSController::class, 'finalizeSale'])->name('pos.finalize');
});
```

## Workflow

### Opening a Register

1. User accesses `/register/open` (register.create)
2. System checks for existing open register
3. If exists: Show quick summary with close option
4. If not exists: Show form to enter opening cash
5. User submits opening cash amount
6. System validates and creates new register record
7. Success message: "Register opened successfully with MWK [amount]"
8. User redirected to `/pos/create` (POS page)

### Using POS (Sales)

1. User accesses `/pos/create`
2. System checks for open register (getOpenRegister())
3. If no register: Redirect to register.create with error
4. If register exists: Display POS with products, cart, etc.
5. User adds items to cart and enters payment details
6. User clicks "Finalize Sale"
7. System calls finalizeSale()
8. Sales are validated and created with:
   - `register_id` linked to open register
   - Payment method tracked
9. Stock decremented
10. Cart cleared
11. Success response with sale details

### Closing a Register

1. Cashier accesses `/register/close` (register.confirm)
2. System fetches open register for user
3. Calculates totals by payment method
4. Displays summary with:
   - Opening cash
   - Total sales by type
   - Expected closing amount
5. Cashier reviews and clicks "Close Register"
6. System:
   - Gets all sales for register
   - Calculates total from all payment methods
   - Sets closing_cash to total
   - Sets closed_at to now()
   - Updates register in database
7. Redirects to register report with success message

## Data Integrity

### Constraints

1. **One Open Register Per User**
   - User cannot open multiple register sessions simultaneously
   - Before creating new register, check: WHERE user_id = ? AND closed_at IS NULL

2. **Sales Must Have Register**
   - Sales can only be created when register is open
   - finalizeSale() validates open register exists
   - register_id is required field in sale

3. **Register Cannot Be Reopened**
   - Once closed_at is set, register is finalized
   - All sales remain linked to this closed session

4. **Location is Optional**
   - Register can be opened without selecting location
   - Allows for flexible store setups

## Testing the System

### Manual Testing Steps

1. **Login** as a user
2. **Try accessing POS** without opening register
   - Should redirect to register.create with error
3. **Open a Register**
   - Navigate to /register/open
   - Enter opening cash (e.g., 10000)
   - Submit form
   - Should see success message
   - Should be redirected to POS
4. **Verify Register is Open**
   - Check database: `SELECT * FROM registers WHERE closed_at IS NULL`
   - Should see 1 record with open timestamp
5. **Make a Sale**
   - Add products to cart
   - Enter payment method
   - Finalize sale
   - Verify sale record has register_id populated
6. **Try Opening Another Register**
   - Should see error "Register already open"
7. **Close the Register**
   - Navigate to /register/close
   - Review totals
   - Click "Close Register"
   - Verify closing_cash equals total sales
   - Verify closed_at is set to now
8. **Check Register Report**
   - Navigate to /reports/register-report
   - Should see closed register in list
   - Should see sales linked to register
   - Summary totals should match closing_cash

### Database Verification

**Check registers table:**
```sql
SELECT 
    r.id, 
    u.name, 
    r.opening_cash, 
    r.closing_cash, 
    r.opened_at, 
    r.closed_at,
    COUNT(s.id) as sales_count,
    SUM(s.total_amount) as total_sales
FROM registers r
LEFT JOIN users u ON r.user_id = u.id
LEFT JOIN sales s ON r.id = s.register_id
GROUP BY r.id
ORDER BY r.opened_at DESC;
```

**Check sales from specific register:**
```sql
SELECT 
    s.id, 
    s.invoice_no, 
    s.payment_method, 
    s.total_amount,
    s.created_at
FROM sales s
WHERE s.register_id = [register_id]
ORDER BY s.created_at;
```

## Error Messages

### User-Facing Errors

| Error | Scenario | Resolution |
|-------|----------|-----------|
| "Register already open" | User tries to open register while one is already active | Close current register first |
| "No open register to close" | User tries to close register but none is open | Open a register first |
| "Open register first" | User tries to make sale without open register | Navigate to /register/open |
| "Unauthorized" | User tries to close another user's register | Only cashier can close their own register |
| "Register is already closed" | User tries to close already-closed register | This should not happen in normal flow |

### Validation Errors

- `opening_cash` must be numeric and >= 0
- `location_id` if provided must exist in locations table
- `register_id` on sales must exist in registers table

## Production Considerations

1. **Backup Before Deploying**
   - Run migrations in staging first
   - Backup registers and sales tables

2. **Data Migration**
   - If migrating from session-based to database-driven:
   - Run all existing register migrations
   - Create Location model and table
   - Add register_id to sales if not already present

3. **Monitoring**
   - Monitor for orphaned sales (register_id = null)
   - Check for registers with no closing_cash values
   - Monitor for extended open registers (business logic check)

4. **Performance**
   - Add indexes on:
     - registers.user_id
     - registers.closed_at (for filtering)
     - sales.register_id
   - All done automatically by Laravel

## Future Enhancements

1. **Reconciliation Report**
   - Compare opening_cash + sales with closing_cash
   - Flag discrepancies

2. **Bulk Register Management**
   - Admin can close registers for other users
   - Manager dashboard showing all open registers

3. **Auto-Close Registers**
   - End-of-day automatic register closure
   - Scheduled task to close registers after shift

4. **Audit Trail**
   - Log all register open/close events
   - Track opening/closing user and timestamp

5. **Permissions**
   - Different user roles with specific permissions
   - Manager can view all registers
   - Cashier can only see own register

## Quick Reference

### Key Files

| File | Purpose |
|------|---------|
| app/Http/Controllers/RegisterController.php | Register open/close logic |
| app/Http/Controllers/POSController.php | POS with register integration |
| app/Models/Register.php | Register model with relationships |
| app/Models/Sale.php | Sale model with register relationship |
| app/Helpers/RegisterHelper.php | Helper functions for register checks |
| routes/web.php | All register routes |
| resources/views/pos/register/open.blade.php | Open register form |
| resources/views/pos/register/close.blade.php | Close register summary |
| resources/views/pos/components/register-status.blade.php | Register status component |

### Key Routes

| Route | Method | Name | Purpose |
|-------|--------|------|---------|
| /register/open | GET | register.create | Show open form |
| /register | POST | register.store | Create register |
| /register/close | GET | register.confirm | Show close form |
| /register/{id} | PATCH | register.update | Close register |
| /pos/create | GET | pos.create | POS (checks register) |
| /pos/finalize | POST | pos.finalize | Save sale (checks register) |

### Key Helpers

```php
getOpenRegister()          // Get current open register
hasOpenRegister()          // Check if register is open
getActiveRegisterId()      // Get ID of open register
```

---

**Last Updated:** February 25, 2026
**Version:** 1.0 - Production Ready
