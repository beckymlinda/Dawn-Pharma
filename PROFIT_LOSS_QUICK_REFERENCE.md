# Profit/Loss Report - Quick Reference Guide

## 🚀 Access the Report

**URL**: `http://yourapp.test/reports/profit-loss`  
**Route Name**: `reports.profit-loss`  
**HTTP Method**: GET

## 📊 Default View

When you first visit the report:
- **Date Range**: Current month (1st to last day)
- **Left Side**: Opening stock values
- **Right Side**: Closing stock and profit metrics
- **Currency**: MWK (configurable)

## 🎛️ Using the Date Filter

### Quick Selection (Auto-Submit)
1. Click the dropdown menu
2. Select a preset range:
   - Yesterday
   - Last 7 Days
   - Last 30 Days
   - This Month
   - Last Month
   - This Month Last Year
   - This Year
   - Last Year

### Custom Date Range
1. Click dropdown and select "Custom Range"
2. Input fields appear:
   - **From Date**: YYYY-MM-DD format
   - **To Date**: YYYY-MM-DD format
3. Click "Filter" button to apply

## 📈 Understanding the Report

### LEFT SIDE METRICS
| Metric | Description |
|--------|------------|
| Opening Stock (Purchase Price) | Inventory value at period start (purchase cost) |
| Opening Stock (Sale Price) | Inventory value at period start (retail price) |
| Total Purchase | Total procurement cost (excluding taxes/discounts) |
| Total Expense | Operational expenses (defaults to 0) |
| Total Sell Discount | Customer discounts given |
| Total Sell Return | Products returned by customers |

### RIGHT SIDE METRICS
| Metric | Formula |
|--------|---------|
| Closing Stock (Purchase Price) | Current stock quantity × purchase price |
| Closing Stock (Sale Price) | Current stock quantity × sale price |
| Total Sales | Sum of all sale items in period (excl. tax) |
| Cost of Goods Sold | Total Sales - Gross Profit |
| Gross Profit | Total Sales - Cost of Goods Sold |
| Net Profit | Gross Profit - Expenses - Discounts + Returns |

## 🔧 URL Parameters

### Auto-Submit Parameters
```
?date_range=this_month
?date_range=last_7_days
?date_range=last_30_days
?date_range=this_year
... etc
```

### Custom Date Parameters
```
?date_range=custom&from_date=2026-02-01&to_date=2026-02-28
```

## 💾 Backend Configuration

### To Change Currency
Edit `ProfitLossController@index()`:
```php
$currency = 'USD'; // Change from MWK to USD
```

### To Add Expense Tracking
If you create an `expenses` table:
```php
$totalExpense = Expense::whereBetween('created_at', [$fromDate, $toDate])
    ->sum('amount');
```

### To Track Sales Discounts
If `sales` table has discount column:
```php
$totalSellDiscount = Sale::whereBetween('created_at', [$fromDate, $toDate])
    ->sum('discount');
```

## 📦 Database Requirements

### Required Tables & Columns
```
products:
  - id
  - current_stock (decimal)
  - purchase_price (decimal)
  - selling_price (decimal)

sales:
  - id
  - created_at (timestamp)

sale_items:
  - id
  - sale_id (foreign key)
  - product_id (foreign key)
  - quantity (decimal)
  - price (decimal)
  - subtotal (decimal) [optional]
```

## ✅ Verification Checklist

- [ ] Route is accessible: `/reports/profit-loss`
- [ ] Route is protected by middleware `auth`
- [ ] Blade template uses Blade variables (not JavaScript)
- [ ] Date dropdown auto-submits on selection
- [ ] Custom date inputs appear when "Custom Range" selected
- [ ] Numbers formatted with 2 decimal places
- [ ] Currency symbol displayed correctly
- [ ] Opening stock accounts for period sales
- [ ] Closing stock shows current inventory value
- [ ] Gross/Net profit calculations are correct

## 🐛 Troubleshooting

### Report Shows Zero Values
- Check if sales exist in the selected date range
- Verify product current_stock, purchase_price, selling_price are set
- Verify sale_items are linked to products correctly

### Date Filter Not Working
- Ensure date_range parameter is passed in query string
- Check that from_date and to_date are in YYYY-MM-DD format
- Verify custom date form is visible when "Custom Range" is selected

### Wrong Stock Values
- Opening stock is calculated as: (current_stock + sold_in_period)
- This requires accurate stock tracking in products table
- If you need historical stock data, you'll need to add stock_history table

## 📝 Example Report Output

```
LEFT SIDE:
Opening Stock (By purchase price):    MWK 18,882,589.70
Opening Stock (By sale price):        MWK 38,492,950.00
Total Purchase:                       MWK 0.00
Total Expense:                        MWK 0.00
Total Sell Discount:                  MWK 0.00
Total Sell Return:                    MWK 0.00

RIGHT SIDE:
Closing Stock (By purchase price):    MWK 15,521,191.70
Closing Stock (By sale price):        MWK 31,493,100.00
Total Sales:                          MWK 6,991,800.00
Cost of Goods Sold:                   MWK 3,500,000.00
Gross Profit:                         MWK 3,491,800.00
Net Profit:                           MWK 3,491,800.00
```

## 🔗 Related Routes

```
/products      - Product management (maintain stock values)
/sales         - Sales transactions
/purchases     - Purchase management
/dashboard     - Main dashboard
```

---
**Version**: 1.0  
**Last Updated**: February 25, 2026  
**Status**: ✅ PRODUCTION READY
