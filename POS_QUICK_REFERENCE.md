# POS System - Quick Reference

## Architecture Overview

```
User Interface (Blade + Bootstrap)
         ↓
   JavaScript (AJAX)
         ↓
   Laravel Routes
         ↓
   Controllers (Logic)
         ↓
   Eloquent Models (Data)
         ↓
   Database (Persistence)
```

## Data Flow

### Adding a Product to Cart
```
1. User clicks product in grid
2. onClick="addToCartClick(productId, 1)"
3. AJAX POST to /pos/cart/add
4. POSController@addToCart validates & stores in session()
5. Returns JSON: { cart: [...], success: true }
6. renderCart() updates UI with new total
```

### Finalizing a Sale
```
1. User clicks PAY → shows payment modal
2. User enters amount & selects method
3. User clicks "Finalize Payment"
4. finalizePayment() builds receipt HTML
5. AJAX POST to /pos/finalize with details
6. POSController@finalizeSale:
   - Creates Sale record
   - Creates SaleItem records (one per cart item)
   - Decrements product stock
   - Clears session cart
   - Returns JSON with success
7. Receipt modal appears
8. User closes receipt → page reloads
```

### Filtering Products
```
Category/Brand Tab Click
         ↓
setTab(tab) function
         ↓
Load filter buttons from categoriesData/brandsData
         ↓
filterByCategory(catId) or filterByBrand(brandId)
         ↓
renderProductGrid(filtered) updates display
```

## Key Files & Their Roles

| File | Purpose | Primary Logic |
|------|---------|---------------|
| `POSController.php` | Backend POS logic | Cart operations, sale finalization |
| `SaleController.php` | Sales management | CRUD for completed sales |
| `pos.blade.php` | POS UI | Register open/close, cart, payment |
| `Product.php` | Product model | Relationships to Category, Brand |
| `Sale.php` | Sale model | Stores transaction records |
| `SaleItem.php` | Line item model | Stores cart items as permanent records |
| `web.php` | Routes | Maps URLs to controllers |
| `app.blade.php` | Master layout | Sidebar with navigation |

## Database Schema

### Sales Table
```sql
CREATE TABLE sales (
  id INT PRIMARY KEY,
  invoice_no VARCHAR UNIQUE,
  customer_name VARCHAR,
  contact_number VARCHAR,
  location VARCHAR,
  payment_status VARCHAR,
  payment_method VARCHAR,
  total_amount DECIMAL,
  total_paid DECIMAL,
  sell_due DECIMAL,
  total_items INT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### Sale_Items Table
```sql
CREATE TABLE sale_items (
  id INT PRIMARY KEY,
  sale_id INT FOREIGN KEY,
  product_id INT FOREIGN KEY,
  quantity DECIMAL,
  price DECIMAL,
  subtotal DECIMAL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

## API Endpoints

```
POST /pos/cart/add
├ Input: { product_id, quantity }
└ Output: { cart: [...], success: true }

POST /pos/cart/remove
├ Input: { index }
└ Output: { cart: [...], totalItems, totalAmount }

GET /pos/cart/get
├ Input: (none)
└ Output: { cart: [...], totalItems, totalAmount }

POST /pos/finalize
├ Input: { invoice_no, customer_name, payment_method, payment_status }
└ Output: { success: true, sale_id, invoice_no, redirect }
```

## JavaScript State

### Global Variables
- `productsData`: Array of all products (from @json)
- `categoriesData`: Array of all categories (from @json)
- `brandsData`: Array of all brands (from @json)
- `cart`: Current shopping cart (array)
- `currentFilter`: Filter mode ('all', 'category-X', 'brand-X')

### Key Functions
- `openRegister()` - Start POS session
- `closeRegister()` - End POS session
- `addToCartClick(id, qty)` - Add product via AJAX
- `removeFromCart(idx)` - Remove item via AJAX
- `filterByCategory(id)` - Filter grid by category
- `filterByBrand(id)` - Filter grid by brand
- `filterProducts()` - Search by name/SKU
- `finalizePayment()` - Process transaction

## Common Workflows

### Opening a Register
```
1. Click "POS Screen" in sidebar
2. See "Open Cash Register" modal
3. Enter opening cash: 5000
4. Click "Open Register"
5. POS screen loads with products
```

### Selling Products
```
1. Register is open
2. Search for "Paracetamol" or browse categories
3. Click product → adds to cart
4. See cart update on left side
5. Repeat for all items
6. Click PAY button
7. Modal shows total: MWK 1,250
8. Enter cash: 2000
9. See change: MWK 750
10. Click "Finalize Payment"
11. Receipt shows INV-123456
12. Click "Print" to print receipt
13. Close receipt → cart clears
```

### Checking Sales
```
1. Click "All Sales" in sidebar
2. See list of completed transactions
3. Click sale to view details
4. See itemized list with prices
5. Return to POS to continue selling
```

## Common Issues & Solutions

### Products not showing
**Check**: 
- Database has products: `SELECT COUNT(*) FROM products;`
- categoriesData and brandsData in browser console
- Network tab shows data in initial page load

**Fix**: Clear browser cache, reload page

### Cart not adding items
**Check**:
- Browser console for JS errors
- Network tab shows POST to /pos/cart/add
- Response is valid JSON

**Fix**: Check Product ID in database, verify CSRF token

### Stock not decremented
**Check**:
- Product has current_stock column
- finalizeSale completes without error
- Sale created in database

**Fix**: Run migrations, check product exists

### Receipt not printing
**Check**:
- Browser popup blocker disabled
- Receipt modal appears before print dialog
- Browser console for JS errors

**Fix**: Allow popups, try different browser

## Performance Tips

1. **Load Time**: Initial page load includes all products - acceptable for ~1000 items
2. **Search**: Client-side filtering - no server round trip, instant results
3. **Cart**: Stored in session - survives page refresh
4. **Stock**: Updated in batch (per sale) - efficient database writes
5. **Queries**: Eager load relationships in POSController

## Security Notes

- All CSRF tokens validated server-side
- Stock decrements prevent overselling
- Session per user prevents data leakage
- Eloquent prevents SQL injection
- Validation on all inputs
- Auth middleware on all routes

## Future Enhancements

- [ ] Customer database lookup
- [ ] Multiple payment methods per transaction
- [ ] Discount application
- [ ] Tax calculation
- [ ] Barcode scanning
- [ ] Product images
- [ ] Offline mode with sync
- [ ] Receipt email/SMS
- [ ] Sales analytics dashboard

## Testing Commands

```bash
# Check syntax
php -l resources/views/pos/pos.blade.php
php -l app/Http/Controllers/POSController.php

# View routes
php artisan route:list --name="pos"
php artisan route:list --name="sales"

# Check database
php artisan tinker
>>> Product::count()
>>> Sale::count()
>>> SaleItem::count()

# Run tests
php artisan test

# View logs
tail -f storage/logs/laravel.log
```

## Support

For issues, refer to:
- [POS_INTEGRATION_COMPLETE.md](POS_INTEGRATION_COMPLETE.md) - Full feature list
- [POS_TESTING.md](POS_TESTING.md) - Testing guide
- [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) - What changed
- Laravel documentation: https://laravel.com/docs
