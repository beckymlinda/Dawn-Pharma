# System Testing Guide

## Test Products Created ✅

### Product Details:
1. **Paracetamol 500mg** (SKU: TEST-001)
   - Stock: 3 units
   - Purchase Cost: MWK 200.00
   - Selling Price: MWK 250.00
   - Profit per unit: MWK 50.00

2. **Ibuprofen 400mg** (SKU: TEST-002)
   - Stock: 3 units  
   - Purchase Cost: MWK 150.00
   - Selling Price: MWK 200.00
   - Profit per unit: MWK 50.00

3. **Amoxicillin 250mg** (SKU: TEST-003)
   - Stock: 3 units
   - Purchase Cost: MWK 300.00
   - Selling Price: MWK 400.00
   - Profit per unit: MWK 100.00

### Summary:
- **Total Products**: 3
- **Total Stock**: 9 units
- **Total Cost**: MWK 650.00
- **Total Value**: MWK 850.00
- **Expected Profit**: MWK 200.00 (if all sold)

---

## Testing Scenarios

### 1. SALES TESTING 🛒

**Test Case A: Single Payment Sales**
- Open register
- Add 1 unit of each product to cart
- Complete sale with cash payment
- **Expected**: Stock reduces to 6 units, Profit = MWK 200.00

**Test Case B: Split Payment Sales**
- Add all 3 products to cart (total MWK 850.00)
- Split payment: MWK 500.00 cash + MWK 350.00 card
- **Expected**: 2 separate sales created, Stock reduces to 0

**Test Case C: Partial Stock Sale**
- Add 5 units of Paracetamol (should fail - only 3 available)
- **Expected**: "Insufficient stock" error message

### 2. STOCK ADJUSTMENT TESTING 📊

**Test Case A: Stock In**
- Add 2 units to Paracetamol stock
- **Expected**: Stock becomes 5 units, StockAdjustment record created

**Test Case B: Stock Out**
- Remove 1 unit from Ibuprofen stock
- **Expected**: Stock becomes 2 units, StockAdjustment record created

**Test Case C: Price Update**
- Update Amoxicillin selling price to MWK 450.00
- **Expected**: New price reflected in future sales

### 3. PROFIT/LOSS REPORTING 📈

**Test Case A: Daily Report**
- Generate P&L report for today
- **Expected**: Shows sales, COGS, and profit correctly

**Test Case B: Monthly Report**
- Generate P&L report for current month
- **Expected**: Accurate profit calculations using purchase costs

**Test Case C: Date Range Report**
- Generate P&L for custom date range
- **Expected**: Proper date filtering and calculations

### 4. STOCK EXPIRY REPORTING ⏰

**Test Case A: Current Stock Expiry**
- Check stock expiry report
- **Expected**: Shows expiry dates for all 3 products

**Test Case B: Expiring Soon Filter**
- Filter for items expiring in 30 days
- **Expected**: Shows products with upcoming expiry

**Test Case C: Expired Items**
- Filter for expired items
- **Expected**: Should show none (all have future dates)

---

## Expected Results Verification

### ✅ Sales System Should:
- [ ] Prevent sales exceeding available stock
- [ ] Create proper sale records with purchase costs
- [ ] Generate correct invoices
- [ ] Handle split payments correctly
- [ ] Update stock levels accurately

### ✅ Stock System Should:
- [ ] Track all stock movements
- [ ] Prevent negative stock
- [ ] Create audit trails
- [ ] Handle adjustments properly
- [ ] Maintain data integrity

### ✅ Financial System Should:
- [ ] Calculate accurate profit/loss
- [ ] Use correct purchase costs for COGS
- [ ] Generate comprehensive reports
- [ ] Handle date ranges correctly
- [ ] Show proper margins

### ✅ Expiry System Should:
- [ ] Track expiry dates accurately
- [ ] Provide expiry alerts
- [ ] Filter by expiry status
- [ ] Generate expiry reports
- [ ] Export data correctly

---

## Testing Checklist

### Before Testing:
- [ ] Register is open
- [ ] Test products are visible in POS
- [ ] Stock levels are correct

### During Testing:
- [ ] All sales complete successfully
- [ ] Stock updates are immediate
- [ ] No error messages appear
- [ ] Reports generate correctly

### After Testing:
- [ ] Stock levels match expectations
- [ ] Profit calculations are accurate
- [ ] All reports show correct data
- [ ] System performance is good

---

## Troubleshooting

### Common Issues:
1. **Stock Not Updating**: Check StockHistory records
2. **Profit Incorrect**: Verify purchase_cost in SaleItem
3. **Reports Empty**: Check date ranges and filters
4. **Expiry Not Showing**: Verify expiry_date fields

### Debug Commands:
```bash
# Check product stock
php artisan tinker --execute="App\Models\Product::where('sku', 'LIKE', 'TEST-%')->get(['name', 'current_stock', 'purchase_price', 'selling_price'])"

# Check recent sales
php artisan tinker --execute="App\Models\Sale::with('items.product')->latest()->take(5)->get()"

# Check stock history
php artisan tinker --execute="App\Models\StockHistory::with('product')->latest()->take(10)->get()"
```

---

## Success Criteria 🎯

The system is working correctly when:
- ✅ All 3 products appear in POS
- ✅ Sales process without errors
- ✅ Stock levels update immediately
- ✅ Profit calculations are accurate
- ✅ Reports generate correct data
- ✅ No negative stock occurs
- ✅ Audit trails are complete

**Ready to begin testing!** 🚀
