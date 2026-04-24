# POS Testing Guide

## Quick Start

### 1. Open POS Screen
- Navigate to the sidebar
- Click "POS Screen" under POS menu
- You should see the "Open Cash Register" screen

### 2. Open Register
- Enter a cash amount (e.g., 10000)
- Click "Open Register"
- You should now see the POS screen with products

### 3. Add Products to Cart
- Click on any product in the grid
- Product should appear in the cart (left side)
- Quantity should be 1
- Subtotal should calculate correctly

### 4. Filtering Products
**By Category:**
- Click the "📊 Category" tab
- Click on a category button (e.g., "Medicines (5)")
- Grid should show only products in that category
- Search bar should still work within the filter

**By Brand:**
- Click the "© Brands" tab
- Click on a brand button
- Grid should show only products of that brand

### 5. Process Payment
- Click the "💵 PAY" button
- Modal should show:
  - Total payable amount
  - Payment method dropdown
  - Amount field (pre-filled with total)
  - Change calculation
- Enter payment amount (>= total)
- Select payment method
- Click "Finalize Payment"

### 6. Receipt
- Receipt modal should appear with:
  - Pharmacy details
  - Invoice number
  - Date/time
  - Customer name
  - Itemized list
  - Total and change
- Click "🖨 Print" to print
- Click "Close" to finish

### 7. Verify Database
After closing receipt:

**Check Sales Table:**
```sql
SELECT * FROM sales ORDER BY created_at DESC;
```
- Should see new sale with correct totals

**Check Sale Items:**
```sql
SELECT * FROM sale_items WHERE sale_id = [NEW_SALE_ID];
```
- Should see items with correct prices and quantities

**Check Product Stock:**
```sql
SELECT id, name, current_stock FROM products;
```
- Stock should be decremented for purchased products

### 8. Close Register
- Click "Close Register" button
- Confirm closure
- Should return to "Open Cash Register" screen

## Troubleshooting

### Issue: Products not showing
**Solution**: 
- Check database has products: `SELECT COUNT(*) FROM products;`
- Check POSController@create is passing products
- Check browser console for JS errors

### Issue: Cart not saving on add
**Solution**:
- Check network requests (F12 > Network tab)
- Verify CSRF token is in meta tag
- Check server logs for errors: `php artisan logs`

### Issue: Receipt not printing
**Solution**:
- Check browser popup settings
- Try print with different browser
- Check print preview appears

### Issue: Stock not decremented
**Solution**:
- Check product has `current_stock` column
- Verify finalizeSale runs without errors
- Check sale created successfully in database

## Expected Behavior

| Action | Expected Result |
|--------|-----------------|
| Click product | Add to cart, cart count +1 |
| Search term | Filter grid by name/SKU |
| Change quantity | Subtotal updates |
| Remove item | Item disappears, total updates |
| Pay amount < total | "Balance due" appears |
| Pay amount ≥ total | Can finalize payment |
| Click Finalize | Receipt modal appears |
| Close receipt | Page reloads, cart clears |
| Check sales list | New sale visible |
| Close register | Return to open screen |

## Performance Notes

- Products loaded with relationships (eager loading)
- Category/Brand count calculated client-side
- No N+1 queries on load
- Session cart optimized (serialized array)
- AJAX requests use JSON for efficiency

## Security Checks

✅ CSRF protection on all POST requests
✅ Auth middleware on all routes
✅ SQL injection prevention via Eloquent
✅ Proper validation on cart/payment
✅ Stock decrement prevents overselling
✅ Session isolation per user
