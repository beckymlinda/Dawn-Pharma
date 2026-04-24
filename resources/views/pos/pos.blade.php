

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - EDUC Pharmacy</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

<style>
/* CSS HERE */
/* ================================
   GLOBAL
=================================*/
.pos-wrapper{
    height:100vh;
    display:flex;
    flex-direction:column;
    background:#f5f7fb;
    font-family: 'Segoe UI', sans-serif;
}

/* ================================
   HEADER
=================================*/
.pos-header{
    display:flex;
    align-items:center;
    gap:15px;
    padding:10px 20px;
    background:#ffffff;
    border-bottom:1px solid #e5e7eb;
}

.pos-header .badge{
    background:linear-gradient(135deg,#4f46e5,#6366f1);
    font-weight:500;
}

.toolbar-btn{
    border:1px solid #e5e7eb;
    background:#fff;
    padding:6px 12px;
    border-radius:6px;
    font-size:13px;
    transition:.2s;
}

.toolbar-btn:hover{
    background:#f3f4f6;
}

/* ================================
   MAIN BODY
=================================*/
.pos-cart-section{
    width:55%;
    background:#ffffff;
    display:flex;
    flex-direction:column;
    border-right:1px solid #e5e7eb;
}

.pos-product-section{
    width:45%;
    background:#f8fafc;
    display:flex;
    flex-direction:column;
}

/* ================================
   CART HEADER
=================================*/
.pos-cart-header{
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 15px;
    border-bottom:1px solid #e5e7eb;
    background:#fafafa;
}

/* ================================
   CART TABLE
=================================*/
.pos-cart-items{
    flex:1;
    overflow:auto;
}

.pos-cart-table{
    width:100%;
    border-collapse:collapse;
    font-size:14px;
}

.pos-cart-table th{
    background:#f9fafb;
    font-weight:600;
    padding:10px;
    border-bottom:1px solid #e5e7eb;
}

.pos-cart-table td{
    padding:10px;
    border-bottom:1px solid #f1f5f9;
}

.pos-cart-table tr:hover{
    background:#f9fafb;
}

/* ================================
   CART FOOTER
=================================*/
.pos-cart-footer{
    padding:12px 15px;
    border-top:1px solid #e5e7eb;
    background:#ffffff;
    font-size:14px;
}

.pos-cart-footer .row{
    margin-bottom:6px;
}

/* ================================
   PRODUCT SIDE
=================================*/
.pos-product-tabs{
    display:flex;
    gap:10px;
    padding:10px 15px;
    background:#ffffff;
    border-bottom:1px solid #e5e7eb;
}

.pos-product-tabs .btn{
    flex:1;
    font-size:14px;
    border-radius:8px;
    font-weight:600;
}

.pos-product-tabs .active{
    background:#2563eb;
    color:#fff;
    border:none;
}

.btn-purple {
    background: #7c3aed;
    color: #fff;
    border: none;
}

.btn-purple:hover {
    background: #6d28d9;
    color: #fff;
}

/* ================================
   FILTER LIST
=================================*/
.pos-filter-list{
    padding:10px 15px;
    background:#f9fafb;
    border-bottom:1px solid #e5e7eb;
    max-height:90px;
    overflow:auto;
}

/* ================================
   PRODUCT GRID
=================================*/
.pos-product-grid{
    flex:1;
    overflow:auto;
    padding:15px;
}

.pos-product-grid .row {
    margin: -6px;
}

.pos-product-grid .col-3 {
    padding: 6px;
}

.pos-product-grid .col-6 {
    padding: 4px;
}

.pos-product-grid .col-md-4 {
    padding: 6px;
}

.pos-product-grid .col-lg-3 {
    padding: 8px;
}

.pos-product-card{
    background:#ffffff;
    border-radius:10px;
    padding:12px;
    text-align:center;
    box-shadow:0 1px 2px rgba(0,0,0,0.05);
    transition:.2s;
}

.pos-product-card:hover{
    background:#f9fafb;
    transform:translateY(-2px);
    box-shadow:0 2px 4px rgba(0,0,0,0.1);
}

.pos-product-card.selected{
    background:#2563eb;
    color:#fff;
    border-color:#2563eb;
}

/* ================================
   BOTTOM BAR
=================================*/
.pos-bottom-bar{
    padding:10px 15px;
    background:#ffffff;
    border-top:1px solid #e5e7eb;
    display:flex;
    align-items:center;
    gap:10px;
}

.pos-bottom-bar button{
    padding:8px 12px;
    border-radius:6px;
    font-size:14px;
    border:none;
    cursor:pointer;
    transition:.2s;
}

.pos-bottom-bar button:hover{
    background:#f3f4f6;
}

.pos-bottom-bar .btn-primary{
    background:linear-gradient(135deg,#2563eb,#4680ff);
    color:#fff;
    border:none;
}

.pos-bottom-bar .btn-danger{
    background:linear-gradient(135deg,#dc3545,#ff3737);
    color:#fff;
    border:none;
}

.pos-bottom-bar .btn-purple{
    background:linear-gradient(135deg,#7c3aed,#8f4dfc);
    color:#fff;
    border:none;
}

.pos-bottom-bar .btn-light{
    background:#ffffff;
    border:1px solid #e5e7eb;
    color:#333;
}

.pos-bottom-bar .btn-light:hover{
    background:#f3f4f6;
}

.pos-bottom-bar .text-end{
    margin-left:auto;
}

.pos-bottom-bar .fs-4{
    font-size:18px;
}

.pos-bottom-bar .fw-bold{
    font-weight:600;
}

.pos-bottom-bar .ms-2{
    margin-left:8px;
}

/* ================================
   MODALS
=================================*/
.modal-content{
    border-radius:10px;
    box-shadow:0 1px 2px rgba(0,0,0,0.05);
}

.modal-header{
    padding:15px;
    border-bottom:1px solid #e5e7eb;
    background:#ffffff;
}

.modal-header .btn-close{
    margin-top:-10px;
    margin-right:-10px;
}

.modal-body{
    padding:15px;
}

.modal-footer{
    padding:15px;
    border-top:1px solid #e5e7eb;
    background:#ffffff;
}

.modal-footer button{
    padding:8px 12px;
    border-radius:6px;
    font-size:14px;
    border:none;
    cursor:pointer;
    transition:.2s;
}

.modal-footer button:hover{
    background:#f3f4f6;
}

.modal-footer .btn-primary{
    background:linear-gradient(135deg,#2563eb,#4680ff);
    color:#fff;
    border:none;
}

.modal-footer .btn-secondary{
    background:#ffffff;
    border:1px solid #e5e7eb;
    color:#333;
}

.modal-footer .btn-secondary:hover{
    background:#f3f4f6;
}

/* ================================
   PAYMENT MODAL
=================================*/
.payment-modal-content{
    width:500px;
}

.payment-modal-header{
    padding:15px;
    border-bottom:1px solid #e5e7eb;
    background:#ffffff;
}

.payment-modal-body{
    padding:15px;
}

.payment-modal-footer{
    padding:15px;
    border-top:1px solid #e5e7eb;
    background:#ffffff;
}

.payment-modal-footer button{
    padding:8px 12px;
    border-radius:6px;
    font-size:14px;
    border:none;
    cursor:pointer;
    transition:.2s;
}

.payment-modal-footer button:hover{
    background:#f3f4f6;
}

.payment-modal-footer .btn-primary{
    background:linear-gradient(135deg,#2563eb,#4680ff);
    color:#fff;
    border:none;
}

.payment-modal-footer .btn-secondary{
    background:#ffffff;
    border:1px solid #e5e7eb;
    color:#333;
}

.payment-modal-footer .btn-secondary:hover{
    background:#f3f4f6;
}

/* ================================
   RECEIPT MODAL
=================================*/
.receipt-modal-content{
    width:400px;
}

.receipt-modal-header{
    padding:15px;
    border-bottom:1px solid #e5e7eb;
    background:#ffffff;
}

.receipt-modal-body{
    padding:15px;
}

.receipt-modal-footer{
    padding:15px;
    border-top:1px solid #e5e7eb;
    background:#ffffff;
}

.receipt-modal-footer button{
    padding:8px 12px;
    border-radius:6px;
    font-size:14px;
    border:none;
    cursor:pointer;
    transition:.2s;
}

.receipt-modal-footer button:hover{
    background:#f3f4f6;
}

.receipt-modal-footer .btn-primary{
    background:linear-gradient(135deg,#2563eb,#4680ff);
    color:#fff;
    border:none;
}

.receipt-modal-footer .btn-secondary{
    background:#ffffff;
    border:1px solid #e5e7eb;
    color:#333;
}

.receipt-modal-footer .btn-secondary:hover{
    background:#f3f4f6;
}

/* ================================
   EXPENSE MODAL
=================================*/
.expense-modal-content{
    width:500px;
}

.expense-modal-header{
    padding:15px;
    border-bottom:1px solid #e5e7eb;
    background:#ffffff;
}

.expense-modal-body{
    padding:15px;
}

.expense-modal-footer{
    padding:15px;
    border-top:1px solid #e5e7eb;
    background:#ffffff;
}

.expense-modal-footer button{
    padding:8px 12px;
    border-radius:6px;
    font-size:14px;
    border:none;
    cursor:pointer;
    transition:.2s;
}

.expense-modal-footer button:hover{
    background:#f3f4f6;
}

.expense-modal-footer .btn-primary{
    background:linear-gradient(135deg,#2563eb,#4680ff);
    color:#fff;
    border:none;
}

.expense-modal-footer .btn-secondary{
    background:#ffffff;
    border:1px solid #e5e7eb;
    color:#333;
}

.expense-modal-footer .btn-secondary:hover{
    background:#f3f4f6;
}

/* ================================
   PAYMENT ROWS
=================================*/
.payment-rows-container {
  max-height: 150px;
  overflow-y: auto;
  margin-bottom: 10px;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  padding: 8px;
  background: #f9fafb;
}

.payment-row {
  display: flex;
  gap: 5px;
  margin-bottom: 5px;
  align-items: center;
}

.payment-row input, .payment-row select {
  padding: 4px 8px;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  font-size: 12px;
}

.payment-row .amount-input {
  width: 80px;
}

.payment-row .method-select {
  width: 100px;
}

.payment-row .remove-btn {
  width: 30px;
  padding: 4px;
  background: #dc3545;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.payment-row .remove-btn:hover {
  background: #c82333;
}

/* ================================
   REGISTER DETAILS MODAL
=================================*/
.register-details-modal-content{
    width:800px;
}

.register-details-modal-header{
    padding:15px;
    border-bottom:1px solid #e5e7eb;
    background:#ffffff;
}

.register-details-modal-body{
    padding:15px;
}

.register-details-modal-footer{
    padding:15px;
    border-top:1px solid #e5e7eb;
    background:#ffffff;
}

.register-details-modal-footer button{
    padding:8px 12px;
    border-radius:6px;
    font-size:14px;
    border:none;
    cursor:pointer;
    transition:.2s;
}

.register-details-modal-footer button:hover{
    background:#f3f4f6;
}

.register-details-modal-footer .btn-primary{
    background:linear-gradient(135deg,#2563eb,#4680ff);
    color:#fff;
    border:none;
}

.register-details-modal-footer .btn-secondary{
    background:#ffffff;
    border:1px solid #e5e7eb;
    color:#333;
}

.register-details-modal-footer .btn-secondary:hover{
    background:#f3f4f6;
}

/* ================================
   MINIMAL MOBILE RESPONSIVENESS
=================================*/
@media (max-width: 768px) {
    /* Add padding to search button */
    .pos-cart-header .btn {
        padding: 8px 12px;
        margin-left: 8px;
    }
    
    /* Move search bar below customer on small screens */
    .pos-cart-header {
        flex-direction: column;
        gap: 8px;
    }
    
    .pos-cart-header .flex-grow-1 {
        order: 1;
        width: 100%;
    }
    
    /* Make quantity controls more touch-friendly */
    .pos-cart-table td:nth-child(2) .btn {
        width: 28px !important;
        height: 28px !important;
        font-size: 12px;
        font-weight: bold;
        padding: 0;
        border-radius: 6px;
        touch-action: manipulation;
    }
    
    .pos-cart-table td:nth-child(2) input {
        width: 50px !important;
        font-size: 12px;
        font-weight: 700;
        padding: 4px;
        border: 2px solid #2563eb;
        border-radius: 6px;
        text-align: center;
        background: #f8fafc;
    }
    
    /* Reduce cart table font sizes */
    .pos-cart-table {
        font-size: 12px;
    }
    
    .pos-cart-table th,
    .pos-cart-table td {
        padding: 6px 4px;
    }
    
    /* Make quantity column more prominent */
    .pos-cart-table th:nth-child(2),
    .pos-cart-table td:nth-child(2) {
        width: 140px;
        min-width: 140px;
        text-align: center;
        font-weight: 600;
    }
}

/* ================================
   PROFESSIONAL CART STYLING
=================================*/
.pos-cart-table {
    background: #ffffff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border: 1px solid #e5e7eb;
}

.pos-cart-table thead {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: white;
}

.pos-cart-table thead th {
    border: none;
    padding: 12px 16px;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #ffffff !important;
}

.pos-cart-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.2s ease;
}

.pos-cart-table tbody tr:hover {
    background: #f8fafc;
    transform: translateX(2px);
}

.pos-cart-table tbody tr:last-child {
    border-bottom: none;
}

.pos-cart-table tbody td {
    padding: 12px 16px;
    vertical-align: middle;
    border: none;
}

/* Product name styling */
.pos-cart-table td:first-child {
    font-weight: 600;
    color: #000000;
    font-size: 14px;
}

/* Quantity controls - Professional Bootstrap styling */
.quantity-control-group {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 4px;
    transition: all 0.2s ease;
}

.quantity-control-group:hover {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.quantity-control-group .btn {
    width: 28px !important;
    height: 28px !important;
    border-radius: 6px;
    border: none;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    padding: 0;
}

.quantity-control-group .btn-minus {
    background: #fee2e2;
    color: #dc2626;
}

.quantity-control-group .btn-minus:hover {
    background: #dc2626;
    color: white;
    transform: scale(1.05);
}

.quantity-control-group .btn-plus {
    background: #dcfce7;
    color: #16a34a;
}

.quantity-control-group .btn-plus:hover {
    background: #16a34a;
    color: white;
    transform: scale(1.05);
}

.quantity-control-group input {
    width: 50px !important;
    height: 28px;
    border: none;
    background: transparent;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
    color: #1f2937;
    outline: none;
}

/* Price styling */
.pos-cart-table td:nth-child(3),
.pos-cart-table td:nth-child(4) {
    font-weight: 600;
    color: #000000;
    font-size: 14px;
}

/* Remove button - Professional styling */
.btn-remove-item {
    width: 28px !important;
    height: 28px !important;
    border-radius: 6px;
    border: none;
    background: #fef2f2;
    color: #dc2626;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    padding: 0;
}

.btn-remove-item:hover {
    background: #dc2626;
    color: white;
    transform: scale(1.05) rotate(90deg);
}

/* Empty cart state */
#empty-cart {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    padding: 40px 20px;
    text-align: center;
    color: #000000;
    border: 2px dashed #d1d5db;
}

#empty-cart i {
    font-size: 48px;
    color: #9ca3af;
    margin-bottom: 16px;
}

#empty-cart p {
    margin: 0;
    font-size: 16px;
    font-weight: 500;
}

/* Cart footer - Professional styling */
.pos-cart-footer {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    padding: 20px;
    margin-top: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.pos-cart-footer .row {
    margin-bottom: 8px;
}

.pos-cart-footer .col-6 {
    font-size: 13px;
    font-weight: 500;
    color: #000000;
}

.pos-cart-footer .fw-semibold {
    color: #000000;
    font-weight: 700;
}

.pos-cart-footer .text-muted {
    color: #000000;
}

/* Cart items container */
.pos-cart-items {
    background: #ffffff;
    border-radius: 12px;
    padding: 16px;
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Custom scrollbar */
.pos-cart-items::-webkit-scrollbar {
    width: 6px;
}

.pos-cart-items::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.pos-cart-items::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.pos-cart-items::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* ================================
   GLOBAL
=================================*/
.pos-wrapper{
    height:100vh;
    display:flex;
    flex-direction:column;
    background:#f5f7fb;
    font-family: 'Segoe UI', sans-serif;
}

/* ================================
   HEADER
=================================*/
.pos-header{
    display:flex;
    align-items:center;
    gap:15px;
    padding:10px 20px;
    background:#ffffff;
    border-bottom:1px solid #e5e7eb;
}

.pos-header .badge{
    background:linear-gradient(135deg,#4f46e5,#6366f1);
    font-weight:500;
}

/* Main Layout */
.react-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 1.5rem;
  gap: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
  width: 100%;
  margin-left: 0; /* Ensure main content doesn't shift */
}

/* Search Panel */
.react-search-panel {
  background: #1e293b;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #334155;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.search-input-container {
  display: flex;
  align-items: center;
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 8px;
  padding: 0.75rem;
  transition: all 0.2s ease;
}

.search-input-container:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-icon {
  color: #64748b;
  margin-right: 0.75rem;
}

.react-search-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: #e2e8f0;
  font-size: 1rem;
  padding: 0.25rem 0;
}

.react-search-input::placeholder {
  color: #64748b;
}

.react-add-btn {
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.5rem;
  margin-left: 0.75rem;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.react-add-btn:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

.search-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 8px;
  margin-top: 0.5rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  max-height: 300px;
  overflow-y: auto;
  z-index: 1000;
}

/* Cart Section */
.react-cart {
  background: #1e293b;
  border-radius: 12px;
  border: 1px solid #334155;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  flex: 1;
  display: flex;
  flex-direction: column;
}

.cart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #334155;
}

.customer-selector {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.react-select {
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 6px;
  padding: 0.5rem 0.75rem;
  color: #e2e8f0;
  font-size: 0.875rem;
  outline: none;
  cursor: pointer;
}

.cart-stats {
  display: flex;
  gap: 2rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.stat-label {
  font-size: 0.75rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.125rem;
  font-weight: 600;
  color: #f1f5f9;
}

.cart-items {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
  min-height: 300px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: #64748b;
  text-align: center;
}

.empty-state svg {
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
  color: #94a3b8;
}

.empty-state p {
  font-size: 0.875rem;
  margin: 0;
  color: #64748b;
}

.cart-footer {
  padding: 1.5rem;
  border-top: 1px solid #334155;
  background: #0f172a;
}

.total-section {
  margin-bottom: 1.5rem;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  font-size: 0.875rem;
  color: #94a3b8;
}

.total-row.final {
  font-size: 1.125rem;
  font-weight: 600;
  color: #f1f5f9;
  border-top: 1px solid #334155;
  padding-top: 1rem;
  margin-top: 0.75rem;
}

.action-section {
  display: flex;
  gap: 1rem;
}

/* Cart Items */
.cart-item {
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 0.75rem;
  transition: all 0.2s ease;
}

.cart-item:hover {
  border-color: #3b82f6;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.item-info {
  flex: 1;
}

.item-name {
  font-size: 1rem;
  font-weight: 600;
  color: #f1f5f9;
  margin: 0 0 0.25rem 0;
  line-height: 1.4;
}

.item-price {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

.item-remove {
  background: transparent;
  border: none;
  color: #64748b;
  padding: 0.5rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.item-remove:hover {
  background: #dc2626;
  color: white;
}

.item-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.qty-btn {
  background: #334155;
  border: 1px solid #475569;
  color: #e2e8f0;
  border-radius: 6px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.qty-btn:hover {
  background: #475569;
  color: #f1f5f9;
}

.qty-input {
  width: 60px;
  height: 32px;
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 6px;
  color: #e2e8f0;
  text-align: center;
  font-weight: 500;
  outline: none;
  transition: all 0.2s ease;
}

.qty-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.item-total {
  text-align: right;
}

.total-label {
  font-size: 0.75rem;
  color: #64748b;
  display: block;
  margin-bottom: 0.25rem;
}

.total-value {
  font-size: 1.125rem;
  font-weight: 600;
  color: #f1f5f9;
}

/* Search Results */
.search-result-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #334155;
  cursor: pointer;
  transition: all 0.2s ease;
}

.search-result-item:hover {
  background: #334155;
}

.result-left {
  flex: 1;
}

.result-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: #f1f5f9;
  margin-bottom: 0.25rem;
}

.result-meta {
  font-size: 0.75rem;
  color: #64748b;
}

.result-right {
  text-align: right;
}

.result-price {
  font-size: 0.875rem;
  font-weight: 600;
  color: #3b82f6;
}

.search-no-results {
  padding: 1rem;
  text-align: center;
  color: #64748b;
  font-size: 0.875rem;
}

/* Buttons */
.react-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border: none;
  border-radius: 6px;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  outline: none;
}

.react-btn-secondary {
  background: #334155;
  color: #e2e8f0;
}

.react-btn-secondary:hover {
  background: #475569;
}

.react-btn-primary {
  background: #3b82f6;
  color: white;
}

.react-btn-primary:hover {
  background: #2563eb;
}

.react-btn-success {
  background: #10b981;
  color: white;
  flex: 1;
}

.react-btn-success:hover {
  background: #059669;
}

.react-btn-warning {
  background: #f59e0b;
  color: white;
}

.react-btn-warning:hover {
  background: #d97706;
}

.react-btn-large {
  padding: 1rem 1.5rem;
  font-size: 1rem;
}

/* Sidebar */
.react-sidebar {
  position: fixed;
  top: 0;
  left: -300px;
  width: 280px;
  height: 100vh;
  background: #1e293b;
  border-right: 1px solid #334155;
  box-shadow: 4px 0 25px rgba(0, 0, 0, 0.2);
  transition: left 0.3s ease;
  z-index: 1001;
}

.react-sidebar.active {
  left: 0;
}

.sidebar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #334155;
}

.sidebar-header h2 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #f1f5f9;
  margin: 0;
}

.sidebar-close {
  background: transparent;
  border: none;
  color: #64748b;
  padding: 0.5rem;
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.sidebar-close:hover {
  background: #334155;
  color: #e2e8f0;
}

.sidebar-nav {
  padding: 1rem 0;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.5rem;
  color: #e2e8f0;
  text-decoration: none;
  font-size: 0.875rem;
  transition: all 0.2s ease;
  border: none;
  background: transparent;
  width: 100%;
  text-align: left;
  cursor: pointer;
}

.nav-link:hover {
  background: #334155;
  color: #f1f5f9;
}

.nav-link-danger {
  color: #ef4444;
}

.nav-link-danger:hover {
  background: #991b1b;
  color: #fecaca;
}

/* Overlay */
.react-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(2px);
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
  z-index: 1000;
}

.react-overlay.active {
  opacity: 1;
  visibility: visible;
}

/* Responsive Design */
@media (max-width: 768px) {
  .react-main {
    padding: 1rem;
    gap: 1rem;
  }
  
  .cart-header {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
  
  .cart-stats {
    gap: 1rem;
  }
  
  .action-section {
    flex-direction: column;
  }
  
  .react-btn-large {
    padding: 0.875rem 1rem;
  }
}

@media (min-width: 1024px) {
  .react-sidebar {
    left: 0;
    box-shadow: none;
  }
  
  .react-overlay {
    display: none;
  }
}

</style>

  <!-- POS Main Screen -->
  <div id="pos-screen" class="pos-wrapper">
    <!-- POS Header -->
    <div class="pos-header">
      <a href="{{ route('dashboard') }}" class="text-primary fw-semibold small text-decoration-none">← Back</a>
      <span class="text-muted small">Location:</span>
      <span class="fw-semibold">EDUC Pharmacy</span>
      <span class="badge bg-primary" id="headerDate">26-02-2026 08:13 📅</span>
      <div class="flex-grow-1"></div>
      <button class="toolbar-btn fw-semibold" onclick="showModal('registerDetailsModal')"><i class="bi bi-journal-bookmark"></i> Register</button>
      <button class="toolbar-btn fw-semibold" onclick="showModal('expenseModal')"><i class="bi bi-plus-circle"></i> Add Expense</button>
      <a href="{{ route('register.confirm') }}" class="toolbar-btn fw-semibold text-danger"><i class="bi bi-x-lg"></i> Close Register</a>
    </div>

    <div class="d-flex flex-grow-1 overflow-hidden">
      <!-- Left - Cart -->
      <div class="pos-cart-section">
        <div class="pos-cart-header">
          <i class="bi bi-person text-muted"></i>
          <select class="form-select form-select-sm" style="width:auto;flex:0" id="customerSelect"><option value="">Walk-In Customer</option></select>
          <button class="btn btn-primary btn-sm rounded-circle" style="width:28px;height:28px;padding:0;">+</button>
          <div class="flex-grow-1"></div>
          <div style="width:300px; position: relative;">
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control form-control-sm" id="cartSearch" 
                   placeholder="Search products..." 
                   oninput="console.log('Input event triggered'); filterProducts()" 
                   autocomplete="off">
              <span class="input-group-text" id="searchCounter" style="font-size: 11px; min-width: 80px; display: none;">0 results</span>
              <button class="btn btn-primary btn-sm" style="border-radius:0 4px 4px 0;"><i class="bi bi-plus"></i></button>
            </div>
            <!-- Dropdown for search results -->
            <div id="searchDropdown" style="position: absolute; top: 100%; left: 0; right: 0; z-index: 1000; max-height: 300px; overflow-y: auto; display: none; background: white; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 0.375rem 0.375rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
            </div>
          </div>
        </div>

        <div class="pos-cart-items" id="cart-grid-container">
          <div class="cart-grid" id="cart-tbody">
            <div id="empty-cart" class="cart-empty-state text-center text-muted py-5">
              <i class="bi bi-cart3" style="font-size: 48px;"></i>
              <p class="mt-3">No items in cart</p>
            </div>
          </div>
        </div>

        <div class="pos-cart-footer">
          <div class="row">
            <div class="col-6"><span>Items:</span> <span id="totalItems" class="fw-semibold">0.00</span></div>
            <div class="col-6 text-end"><span>Total:</span> <span id="totalAmount" class="fw-semibold">0.00</span></div>
          </div>
          <div class="row small text-muted">
            <div class="col-6"><strong>Discount (-):</strong> <span>0.00</span></div>
            <div class="col-6"><strong>Order Tax(+):</strong> <span>0.00</span></div>
            <div class="col-6"><strong>Shipping(+):</strong> <span>0.00</span></div>
          </div>
        </div>
      </div>

      <!-- Right - Product Grid -->
      <div class="pos-product-section">
        <div class="pos-product-tabs">
          <button class="btn btn-primary active" id="catBtn" onclick="setTab('category')">Category</button>
          <button class="btn btn-purple" id="brandBtn" onclick="setTab('brands')">Brands</button>
        </div>
        
        <!-- Product Search and Display Options -->
        <div class="pos-product-controls mb-3">
          <div class="row align-items-center">
            <div class="col-md-6">
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="productSearch" placeholder="Search products...">
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 me-2">Show:</label>
                <select class="form-select form-select-sm" id="productDisplayLimit" style="width: auto;">
                  <option value="all">All</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                  <option value="200">200</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        
        <div id="categoryFilterList" class="pos-filter-list"></div>
        <div id="brandFilterList" class="pos-filter-list" style="display: none;"></div>
        <div class="pos-product-grid" id="product-grid"></div>
      </div>
    </div>

    <!-- Bottom Bar -->
    <div class="pos-bottom-bar">
      <button class="btn btn-light btn-sm d-flex flex-column align-items-center"><i class="bi bi-file-text"></i><small>Quotation</small></button>
      <button class="btn btn-light btn-sm d-flex flex-column align-items-center"><i class="bi bi-pause-circle"></i><small>Suspend</small></button>
      <button class="btn btn-light btn-sm d-flex flex-column align-items-center"><i class="bi bi-check-circle"></i><small>Credit Sale</small></button>
      <button class="btn btn-light btn-sm d-flex flex-column align-items-center"><i class="bi bi-credit-card"></i><small>Card</small></button>
      <button class="btn btn-primary btn-lg fw-bold px-4" onclick="showModal('paymentModal')">PAY</button>
      <button class="btn btn-danger btn-lg fw-bold px-4" onclick="clearCart()">Cancel</button>
      <button class="btn btn-warning btn-lg fw-bold px-3" onclick="confirmClearCart()" title="Clear cart completely"><i class="bi bi-trash"></i> Clear</button>
      <div class="flex-grow-1"></div>
      <div class="text-end">
        <span class="text-muted small">Total Payable:</span>
        <span class="fs-4 fw-bold ms-2" id="totalPayable">0.00</span>
      </div>
      <button class="btn btn-purple fw-semibold btn-sm ms-3" onclick="loadRecentSales()">Recent Transactions</button>
    </div>
  </div>

  <!-- Payment Modal -->
  <div class="modal fade" id="paymentModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="d-flex">
      <div class="flex-grow-1 p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h5 class="fw-bold mb-0">Payment</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <p class="small"><strong>Advance Balance:</strong> MWK 0.00</p>
        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold small">Amount:*</label>
            <input type="number" class="form-control form-control-sm" id="payAmount">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold small">Payment Method:*</label>
            <select class="form-select form-select-sm" id="payMethod">
              <option value="cash">Cash</option>
              <option value="card">Card</option>
              <option value="cheque">Cheque</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="airtel_money">Airtel Money</option>
              <option value="mpamba">TNM Mpamba</option>
              <option value="national_bank">National Bank</option>
              <option value="standard_bank">Standard Bank</option>
              <option value="advance">Advance</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold small">Payment Account:</label>
          <select class="form-select form-select-sm" style="max-width:250px"><option>None</option></select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold small">Payment note:</label>
          <textarea class="form-control form-control-sm" rows="3"></textarea>
        </div>
        <button class="btn w-100 fw-semibold text-white" style="background:linear-gradient(135deg,#5b4fcf,#7c6fe0)" onclick="addPaymentRow()">Add Payment Row</button>
        <div class="payment-rows-container" id="paymentRows">
          <!-- Payment rows will be added dynamically -->
        </div>
        <div class="row g-3 mt-2">
          <div class="col-6"><label class="form-label fw-semibold small">Sell note:</label><textarea class="form-control form-control-sm" rows="2" placeholder="Sell note"></textarea></div>
          <div class="col-6"><label class="form-label fw-semibold small">Staff note:</label><textarea class="form-control form-control-sm" rows="2" placeholder="Staff note"></textarea></div>
        </div>
      </div>
      <div class="payment-summary">
        <div class="mb-3"><p class="label">Total Items:</p><p class="value" id="payItems">0.00</p></div><hr class="w-100" style="border-color:rgba(0,0,0,0.2)">
        <div class="mb-3"><p class="label">Total Payable:</p><p class="value">MWK</p><p class="value" id="payTotal">0.00</p></div><hr class="w-100" style="border-color:rgba(0,0,0,0.2)">
        <div class="mb-3"><p class="label">Total Paying:</p><p class="value">MWK</p><p class="value" id="payPaying">0.00</p></div><hr class="w-100" style="border-color:rgba(0,0,0,0.2)">
        <div class="mb-3"><p class="label">Change Return:</p><p class="value" id="payChange">MWK 0.00</p></div><hr class="w-100" style="border-color:rgba(0,0,0,0.2)">
        <div><p class="label">Balance:</p><p class="value" id="payBalance">MWK 0.00</p></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">Close</button>
      <button class="btn btn-primary fw-semibold" onclick="finalizePayment()">Finalize Payment</button>
    </div>
  </div></div></div>

  <!-- Receipt Modal -->
  <div class="modal fade" id="receiptModal" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold">Sales Details</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="receipt-print-area" style="font-family:monospace;font-size:12px"></div>
    <div class="modal-footer">
      <button class="btn btn-secondary btn-sm fw-semibold" data-bs-dismiss="modal" onclick="clearCartAfterPayment()">Close</button>
      <button class="btn btn-primary btn-sm fw-semibold" onclick="printReceipt()">🖨 Print</button>
    </div>
  </div></div></div>

  <!-- Add Expense Modal -->
  <div class="modal fade" id="expenseModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold">Add Expense</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="row g-3 mb-3">
        <div class="col-6"><label class="form-label fw-semibold small">Business Location:*</label><select class="form-select form-select-sm"><option>EDUC Pharmacy (BL0001)</option></select></div>
        <div class="col-6"><label class="form-label fw-semibold small">Expense Category:</label><select class="form-select form-select-sm"><option>Please Select</option><option>Transport</option><option>Utilities</option><option>Salaries</option></select></div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-6"><label class="form-label fw-semibold small">Reference No:</label><input type="text" class="form-control form-control-sm"><p class="text-muted small mt-1">Leave empty to autogenerate</p></div>
        <div class="col-6"><label class="form-label fw-semibold small">Date:*</label><input type="text" class="form-control form-control-sm" id="expenseDate" value=""></div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-6"><label class="form-label fw-semibold small">Total amount:*</label><input type="text" class="form-control form-control-sm" placeholder="Total amount"></div>
        <div class="col-6"><label class="form-label fw-semibold small">Expense note:</label><textarea class="form-control form-control-sm" rows="2"></textarea></div>
      </div>
      <h6 class="fw-semibold">Add payment:</h6>
      <div class="row g-3 mb-3">
        <div class="col-4"><label class="form-label fw-semibold small">Amount:*</label><input type="text" class="form-control form-control-sm" value="0.00"></div>
        <div class="col-4"><label class="form-label fw-semibold small">Paid on:*</label><input type="text" class="form-control form-control-sm" value="22-02-2026 07:18"></div>
        <div class="col-4"><label class="form-label fw-semibold small">Payment Method:*</label><select class="form-select form-select-sm"><option>Cash</option><option>Card</option><option>Cheque</option><option>Bank Transfer</option></select></div>
      </div>
      <div class="mb-3"><label class="form-label fw-semibold small">Payment note:</label><textarea class="form-control form-control-sm" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary btn-sm fw-semibold" data-bs-dismiss="modal">Close</button><button class="btn btn-primary btn-sm fw-semibold">Save</button></div>
  </div></div></div>

  <!-- Register Details Modal -->
  <div class="modal fade" id="registerDetailsModal" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold" id="registerDetailsTitle">Register Details</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="registerDetailsContent" style="max-height: 70vh; overflow-y: auto;">
      <div class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading register details...</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-primary btn-sm fw-semibold" onclick="printRegisterDetails()">🖨 Print</button>
      <button class="btn btn-secondary btn-sm fw-semibold" data-bs-dismiss="modal">Cancel</button>
    </div>
  </div></div></div>
 
<style>
/* CSS HERE */
/* ================================
   GLOBAL
=================================*/
.pos-wrapper{
    height:100vh;
    display:flex;
    flex-direction:column;
    background:#f5f7fb;
    font-family: 'Segoe UI', sans-serif;
}

/* ================================
   HEADER
=================================*/
.pos-header{
    display:flex;
    align-items:center;
    gap:15px;
    padding:10px 20px;
    background:#ffffff;
    border-bottom:1px solid #e5e7eb;
}

.pos-header .badge{
    background:linear-gradient(135deg,#4f46e5,#6366f1);
    font-weight:500;
}

.toolbar-btn{
    border:1px solid #e5e7eb;
    background:#fff;
    padding:6px 12px;
    border-radius:6px;
    font-size:13px;
    transition:.2s;
}

.toolbar-btn:hover{
    background:#f3f4f6;
}

/* ================================
   MAIN BODY
=================================*/
.pos-cart-section{
    width:55%;
    background:#ffffff;
    display:flex;
    flex-direction:column;
    border-right:1px solid #e5e7eb;
}

.pos-product-section{
    width:45%;
    background:#f8fafc;
    display:flex;
    flex-direction:column;
}

/* ================================
   CART HEADER
=================================*/
.pos-cart-header{
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 15px;
    border-bottom:1px solid #e5e7eb;
    background:#fafafa;
}

/* ================================
   CART TABLE
=================================*/
.pos-cart-items{
    flex:1;
    overflow:auto;
}

.pos-cart-table{
    width:100%;
    border-collapse:collapse;
    font-size:14px;
}

.pos-cart-table th{
    background:#f9fafb;
    font-weight:600;
    padding:10px;
    border-bottom:1px solid #e5e7eb;
}

.pos-cart-table td{
    padding:10px;
    border-bottom:1px solid #f1f5f9;
}

.pos-cart-table tr:hover{
    background:#f9fafb;
}

/* ================================
   CART FOOTER
=================================*/
.pos-cart-footer{
    padding:12px 15px;
    border-top:1px solid #e5e7eb;
    background:#ffffff;
    font-size:14px;
}

.pos-cart-footer .row{
    margin-bottom:6px;
}

/* ================================
   PRODUCT SIDE
=================================*/
.pos-product-tabs{
    display:flex;
    gap:10px;
    padding:10px 15px;
    background:#ffffff;
    border-bottom:1px solid #e5e7eb;
}

.pos-product-tabs .btn{
    flex:1;
    font-size:14px;
    border-radius:8px;
    font-weight:600;
}

.pos-product-tabs .active{
    background:#2563eb;
    color:#fff;
    border:none;
}

.btn-purple {
    background: #7c3aed;
    color: #fff;
    border: none;
}

.btn-purple:hover {
    background: #6d28d9;
    color: #fff;
}

/* ================================
   FILTER LIST
=================================*/
.pos-filter-list{
    padding:10px 15px;
    background:#f9fafb;
    border-bottom:1px solid #e5e7eb;
    max-height:90px;
    overflow:auto;
}

/* ================================
   PRODUCT GRID
=================================*/
.pos-product-grid{
    flex:1;
    overflow:auto;
    padding:15px;
}

.pos-product-grid .row {
    margin: -6px;
}

.pos-product-grid .col-3 {
    padding: 6px;
}

.pos-product-grid .col-6 {
    padding: 4px;
}

.pos-product-grid .col-md-4 {
    padding: 6px;
}

.pos-product-grid .col-lg-3 {
    padding: 8px;
}

.pos-product-card{
    background:#ffffff;
    border-radius:10px;
    padding:12px;
    text-align:center;
    box-shadow:0 1px 2px rgba(0,0,0,0.05);
    transition:.2s;
    border:1px solid #e5e7eb;
}

.pos-product-card:hover{
    transform:translateY(-3px);
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

.pos-product-img{
    height:70px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:28px;
    color:#9ca3af;
    margin-bottom:6px;
}

/* ================================
   BOTTOM BAR
=================================*/
.pos-bottom-bar{
    display:flex;
    align-items:center;
    gap:15px;
    padding:10px 20px;
    background:#ffffff;
    border-top:1px solid #e5e7eb;
}

.pos-bottom-bar .btn{
    border-radius:8px;
}

.pos-bottom-bar .btn-primary{
    background:#2563eb;
    border:none;
    font-size:16px;
    padding:12px 24px;
}

.pos-bottom-bar .btn-primary:hover{
    background:#1d4ed8;
}

.pos-bottom-bar .btn-danger{
    background:#dc2626;
    border:none;
    font-size:16px;
    padding:12px 24px;
}

.pos-bottom-bar .btn-danger:hover{
    background:#b91c1c;
}

.pos-bottom-bar .btn-light{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    min-width:60px;
    height:50px;
}

.pos-bottom-bar .btn-light:hover{
    background:#e2e8f0;
}

/* ================================
   PAYMENT MODAL SIDE SUMMARY
=================================*/
.payment-summary{
    width:250px;
    background:linear-gradient(135deg,#4f46e5,#6366f1);
    color:#fff;
    padding:20px;
    display:flex;
    flex-direction:column;
    justify-content:center;
}

.payment-summary .label{
    font-size:12px;
    opacity:0.9;
    margin:0;
}

.payment-summary .value{
    font-size:18px;
    font-weight:600;
    margin:0;
}

/* ================================
   SCROLLBAR (modern)
=================================*/
.pos-product-grid::-webkit-scrollbar,
.pos-cart-items::-webkit-scrollbar{
    width:6px;
}
.pos-product-grid::-webkit-scrollbar-thumb,
.pos-cart-items::-webkit-scrollbar-thumb{
    background:#cbd5e1;
    border-radius:10px;
}

</style>

  <!-- Recent Sales Modal -->
  <div class="modal fade" id="recentSalesModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-bold">Recent Sales Transactions</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead class="table-dark">
                <tr>
                  <th>Product Name</th>
                  <th>Quantity</th>
                  <th>Date & Time</th>
                  <th>Customer</th>
                  <th>Payment Method</th>
                  <th class="text-end">Total Amount</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody id="recentSalesTableBody">
                <tr>
                  <td colspan="7" class="text-center">
                    <div class="spinner-border spinner-border-sm" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" onclick="refreshRecentSales()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const productsData = @json($products ?? []);
    const categoriesData = @json($categories ?? []);
    const brandsData = @json($brands ?? []);
    let cart = [];
    let currentFilter = 'all'; // 'all', 'category-X', 'brand-X'
    let preventCartLoad = false; // Flag to prevent cart loading after payment

    // Check register state
    const registerOpen = localStorage.getItem('registerOpen');
    console.log('Page load - registerOpen:', registerOpen);
    
    // Check if cart loading should be prevented (from localStorage)
    const preventCartLoadStorage = localStorage.getItem('preventCartLoad');
    console.log('Page load - preventCartLoad flag (localStorage):', preventCartLoadStorage);
    
    if (registerOpen === 'true') {
      console.log('Calling loadCart() on page load...');
      loadCart();
    }

    function openRegister() {
      const cash = parseFloat(document.getElementById('cashInHand').value);
      if (!cash && cash !== 0) {
        alert('Please enter cash in hand amount');
        return;
      }
      // send to server
      // Use DB-backed register open route
      fetch('{{ route("register.store") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ cash_in_hand: cash })
      }).then(res => {
        if (!res.ok) throw new Error('Failed to open register');
        return res.json();
      }).then(() => {
        localStorage.setItem('cashInHand', cash);
        localStorage.setItem('registerOpen', 'true');
        renderProductGrid(productsData);
        loadCart();
      }).catch(e => { console.error(e); alert('Error opening register'); });
    }

    function closeRegister() {
      if (confirm('Are you sure you want to close the register? Make sure to finalize all transactions first.')) {
         // Redirect to server-side confirmation page
         window.location.href = '{{ route("register.confirm") }}';
      }
    }

    async function addToCartClick(productId, quantity = 1) {
      // Clear search and dropdown when adding from search
      const searchInput = document.getElementById('cartSearch');
      const dropdown = document.getElementById('searchDropdown');
      const counter = document.getElementById('searchCounter');
      
      if (searchInput) searchInput.value = '';
      if (dropdown) dropdown.style.display = 'none';
      if (counter) counter.style.display = 'none';
      
      try {
        // If in edit mode, add locally without server call
        if (editingSaleId) {
          const product = productsData.find(p => p.id == productId);
          if (product) {
            // Check if product already in cart
            const existingIndex = cart.findIndex(item => item.id == productId);
            if (existingIndex >= 0) {
              cart[existingIndex].quantity = Math.round(cart[existingIndex].quantity + quantity);
              cart[existingIndex].subtotal = cart[existingIndex].price * cart[existingIndex].quantity;
            } else {
              cart.push({
                id: product.id,
                name: product.name,
                sku: product.sku,
                quantity: Math.round(quantity),
                price: product.price || 0,
                subtotal: (product.price || 0) * Math.round(quantity),
                current_stock: product.current_stock
              });
            }
            renderCart();
          }
          return;
        }
        
        // Normal mode - add via server
        const res = await fetch('{{ route("pos.cart.add") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ product_id: productId, quantity: quantity })
        });
        
        if (!res.ok) {
          const errorText = await res.text();
          console.error('Server error:', errorText);
          
          // Try to parse error as JSON
          let errorMessage = 'Failed to add item to cart';
          try {
            const errorData = JSON.parse(errorText);
            errorMessage = errorData.error || errorMessage;
          } catch (e) {
            errorMessage = 'Server error: ' + res.status;
          }
          
          alert(errorMessage);
          throw new Error(errorMessage);
        }
        
        const data = await res.json();
        cart = data.cart;
        renderCart();
      } catch (e) {
        console.error('Add to cart error:', e);
        // Only show alert if it's not already shown above
        if (!e.message.includes('Failed to add item to cart') && !e.message.includes('Server error')) {
          alert('Error adding to cart: ' + e.message);
        }
      }
    }

    async function removeFromCart(idx) {
      try {
        // If in edit mode, remove locally without server call
        if (editingSaleId) {
          cart.splice(idx, 1);
          renderCart();
          return;
        }
        
        // Normal mode - remove via server
        const res = await fetch('{{ route("pos.cart.remove") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ index: idx })
        });
        
        if (!res.ok) {
          const errorText = await res.text();
          console.error('Server error:', errorText);
          throw new Error('Failed to remove');
        }
        
        const data = await res.json();
        cart = data.cart;
        renderCart();
      } catch (e) {
        console.error('Remove from cart error:', e);
        alert('Error removing item');
      }
    }

    async function loadCart() {
      // Check if cart loading should be prevented (from localStorage)
      const preventCartLoadStorage = localStorage.getItem('preventCartLoad');
      console.log('loadCart() called - preventCartLoad flag (localStorage):', preventCartLoadStorage);
      
      if (preventCartLoadStorage === 'true') {
        console.log('Cart loading prevented after payment completion');
        localStorage.removeItem('preventCartLoad'); // Clear the flag
        console.log('Removed preventCartLoad flag from localStorage');
        return;
      }
      
      try {
        console.log('Loading cart from server...');
        const res = await fetch('{{ route("pos.cart.get") }}');
        const data = await res.json();
        console.log('Cart data loaded from server:', data);
        cart = data.cart;
        console.log('Cart set to loaded data, length:', cart.length);
        renderCart();
      } catch (e) {
        console.error('Error loading cart:', e);
      }
    }

    async function clearCartServer() {
      try {
        console.log('Attempting to clear cart on server...');
        const res = await fetch('{{ route("pos.cart.clear") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          }
        });
        
        console.log('Server response status:', res.status);
        
        if (!res.ok) {
          console.error('Failed to clear cart on server, status:', res.status);
          return;
        }
        
        const data = await res.json();
        console.log('Server clear response:', data);
        
        // Set flag to prevent cart loading on next page load
        localStorage.setItem('preventCartLoad', 'true');
        console.log('Set preventCartLoad flag to true in localStorage');
        
        // Clear local cart after server clear
        cart = [];
        renderCart();
        console.log('Local cart cleared, cart length:', cart.length);
      } catch (e) {
        console.error('Error clearing cart:', e);
      }
    }

    // React-Style POS Functions
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('overlay');
      
      sidebar.classList.toggle('active');
      overlay.classList.toggle('active');
    }

    function addSearchProduct() {
      const searchInput = document.getElementById('cartSearch');
      const dropdown = document.getElementById('searchDropdown');
      
      if (searchInput.value.trim()) {
        const firstResult = dropdown.querySelector('[onclick]');
        if (firstResult) {
          firstResult.click();
        }
      }
    }

    // Update filterProducts for React-style design
    function filterProducts() {
      console.log('=== REACT-STYLE filterProducts called ===');
      const searchInput = document.getElementById('cartSearch');
      const dropdown = document.getElementById('searchDropdown');
      
      console.log('filterProducts called');
      console.log('productsData:', productsData);
      console.log('productsData length:', productsData ? productsData.length : 'undefined');
      console.log('searchInput:', searchInput);
      console.log('dropdown:', dropdown);
      
      if (!searchInput || !dropdown || !productsData) {
        console.log('Missing elements or data');
        return;
      }
      
      const query = searchInput.value.toLowerCase().trim();
      console.log('Search query:', query);
      
      let results = [];
      if (query) {
        results = productsData.filter(p => 
          (p.name && p.name.toLowerCase().includes(query)) || 
          (p.sku && p.sku.toLowerCase().includes(query))
        );
      }
      
      console.log('Found results:', results.length);
      console.log('Dropdown element exists:', !!dropdown);
      
      if (dropdown) {
        if (query && results.length > 0) {
          let html = '';
          results.slice(0, 8).forEach(product => {
            const price = parseFloat(product.selling_price) || 0;
            html += `
              <div onclick="addToCartClick(${product.id}, 1)" 
                   class="search-result-item">
                <div class="result-left">
                  <div class="result-name">${product.name}</div>
                  <div class="result-meta">${product.sku || product.id} • Stock: ${product.current_stock || 0}</div>
                </div>
                <div class="result-right">
                  <div class="result-price">MWK ${price.toFixed(2)}</div>
                </div>
              </div>
            `;
          });
          console.log('Setting dropdown HTML with', results.length, 'results');
          dropdown.innerHTML = html;
          dropdown.style.display = 'block';
          console.log('Dropdown display set to:', dropdown.style.display);
        } else if (query) {
          console.log('No results found, showing no results message');
          dropdown.innerHTML = '<div class="search-no-results">No products found</div>';
          dropdown.style.display = 'block';
        } else {
          console.log('Empty query, hiding dropdown');
          dropdown.style.display = 'none';
        }
      } else {
        console.error('Dropdown element is null!');
      }
    }

    // Update renderCart for React-style design
    function renderCart() {
      const tbody = document.getElementById('cart-tbody');
      const itemCountElement = document.getElementById('itemCount');
      const cartTotalElement = document.getElementById('cartTotal');
      
      if (!tbody) {
        console.error('Cart tbody element not found');
        return;
      }
      
      if (cart.length === 0) {
        tbody.innerHTML = `
          <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M9 2a1 1 0 0 0-1 1v2a1 1 0 0 0 2 0V3a1 1 0 0 0-1-1zM4 7h16l-1 9a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 7z"/>
            </svg>
            <h3>No items in cart</h3>
            <p>Start by searching for products</p>
          </div>
        `;
        if (itemCountElement) itemCountElement.textContent = '0';
        if (cartTotalElement) cartTotalElement.textContent = 'MWK 0.00';
      } else {
        tbody.innerHTML = '';
        let totalProfit = 0;
        
        cart.forEach((item, idx) => {
          const product = productsData.find(p => p.id == item.product_id || p.id == item.id);
          const purchasePrice = product ? (product.purchase_price || 0) : 0;
          const sellingPrice = parseFloat(item.price) || 0;
          const quantity = parseFloat(item.quantity) || 0;
          const itemProfit = (sellingPrice - purchasePrice) * quantity;
          totalProfit += itemProfit;
          
          tbody.innerHTML += `
            <div class="cart-item">
              <div class="item-header">
                <div class="item-info">
                  <h4 class="item-name">${item.name || 'Unknown'}</h4>
                  <p class="item-price">MWK ${(parseFloat(purchasePrice) || 0).toFixed(2)} each</p>
                </div>
                <button class="item-remove" onclick="removeFromCart(${idx})">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                  </svg>
                </button>
              </div>
              
              <div class="item-controls">
                <div class="quantity-controls">
                  <button class="qty-btn qty-minus" onclick="updateQuantity(${idx}, -1)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                  </button>
                  <input type="number" 
                         class="qty-input" 
                         value="${Math.round(item.quantity)}" 
                         min="1" 
                         step="1"
                         onchange="updateCartQuantity(${idx}, this.value)"
                         onclick="event.stopPropagation()">
                  <button class="qty-btn qty-plus" onclick="updateQuantity(${idx}, 1)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="12" y1="5" x2="12" y2="19"/>
                      <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                  </button>
                </div>
                <div class="item-total">
                  <span class="total-label">Total</span>
                  <span class="total-value">MWK ${(parseFloat(sellingPrice) * quantity || 0).toFixed(2)}</span>
                </div>
              </div>
            </div>
          `;
        });
        
        // Update totals
        const totalItems = cart.reduce((s, i) => s + (parseFloat(i.quantity) || 0), 0);
        const totalAmount = cart.reduce((s, i) => s + (parseFloat(i.subtotal) || 0), 0);
        
        if (itemCountElement) itemCountElement.textContent = totalItems.toString();
        if (cartTotalElement) cartTotalElement.textContent = `MWK ${totalAmount.toFixed(2)}`;
        
        // Update footer totals
        const subtotalElement = document.getElementById('subtotal');
        const totalPayableElement = document.getElementById('totalPayable');
        
        if (subtotalElement) subtotalElement.textContent = `MWK ${totalAmount.toFixed(2)}`;
        if (totalPayableElement) totalPayableElement.textContent = `MWK ${totalAmount.toFixed(2)}`;
        
        // Update total profit display
        const totalProfitElement = document.getElementById('totalProfit');
        const totalProfitRowElement = document.getElementById('totalProfitRow');
        if (totalProfitElement && totalProfitRowElement) {
          totalProfitElement.textContent = totalProfit.toFixed(2);
          totalProfitRowElement.style.display = 'table-row';
        }
      }
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      const searchContainer = document.querySelector('.react-search-panel');
      const dropdown = document.getElementById('searchDropdown');
      
      if (searchContainer && !searchContainer.contains(event.target)) {
        if (dropdown) dropdown.style.display = 'none';
      }
    });

    function selectProduct(productId) {
      if (productId) {
        const product = productsData.find(p => p.id == productId);
        if (product) {
          addToCartClick(productId, 1);
        }
      }
      // Clear search after selection
      const searchInput = document.getElementById('cartSearch');
      const dropdown = document.getElementById('searchDropdown');
      const counter = document.getElementById('searchCounter');
      
      if (searchInput) searchInput.value = '';
      if (dropdown) dropdown.style.display = 'none';
      if (counter) counter.style.display = 'none';
      
      filterProducts(); // Reset to show all products
    }

    // Update search counter
    function updateSearchCounter(resultCount = 0) {
      const searchInput = document.getElementById('cartSearch');
      const counter = document.getElementById('searchCounter');
      if (searchInput && counter) {
        const length = searchInput.value.length;
        counter.textContent = `${resultCount} results`;
        counter.style.display = length > 0 ? 'block' : 'none';
        
        // Update counter color based on results
        if (resultCount > 0) {
          counter.style.color = '#28a745'; // Green
        } else if (length > 0) {
          counter.style.color = '#ffc107'; // Yellow
        }
      }
    }

    // Simple search function (REMOVED - duplicate of React-style version)
    // function filterProducts() {
    //   const searchInput = document.getElementById('cartSearch');
    //   const dropdown = document.getElementById('searchDropdown');
    //   const counter = document.getElementById('searchCounter');
    //   
    //   if (!searchInput || !productsData) {
    //     console.log('Missing elements or data');
    //     return;
    //   }
    //   
    //   const query = searchInput.value.toLowerCase().trim();
    //   console.log('Search query:', query);
    //   
    //   // Filter products
    //   let results = [];
    //   if (query) {
    //     results = productsData.filter(p => 
    //       (p.name && p.name.toLowerCase().includes(query)) || 
    //       (p.sku && p.sku.toLowerCase().includes(query))
    //     );
    //   }
    //   
    //   console.log('Found results:', results.length);
    //   
    //   // Update counter
    //   if (counter) {
    //     counter.textContent = `${results.length} results`;
    //     counter.style.display = query ? 'block' : 'none';
    //     counter.style.color = results.length > 0 ? '#28a745' : '#ffc107';
    //   }
    //   
    //   // Show dropdown
    //   if (dropdown) {
    //     if (query && results.length > 0) {
    //       let html = '';
    //       results.slice(0, 10).forEach(product => {
    //         const price = parseFloat(product.selling_price) || 0;
    //         html += `
    //           <div onclick="addToCartClick(${product.id}, 1)" 
    //                style="padding: 8px 12px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s;"
    //                onmouseover="this.style.background='#f8f9fa'" 
    //                onmouseout="this.style.background='white'">
    //             <div style="font-weight: 600;">${product.name}</div>
    //             <small style="color: #666;">${product.sku} • Stock: ${product.current_stock || 0}</small>
    //             <div style="color: #007bff; font-weight: bold;">MWK ${price.toFixed(2)}</div>
    //           </div>
    //         `;
    //       });
    //       dropdown.innerHTML = html;
    //       dropdown.style.display = 'block';
    //     } else if (query) {
    //       dropdown.innerHTML = '<div style="padding: 12px; text-align: center; color: #666;">No products found</div>';
    //       dropdown.style.display = 'block';
    //     } else {
    //       dropdown.style.display = 'none';
    //     }
    //   }
    //   
    //   // Update main grid
    //   // Skip renderProductGrid for React-style design - we only use search dropdown
    //   // renderProductGrid(query ? results : []);
    //   
    //   // Update mobile search state
    //   updateMobileSearchState(query, results.length);
    // }

    function renderProductGrid(list) {
      const grid = document.getElementById('product-grid');
      if (!grid) {
        console.error('product-grid element not found');
        return;
      }
      
      const searchInput = document.getElementById('productSearch');
      const productSection = document.querySelector('.pos-product-section');
      
      // Check if mobile and never show grid
      if (window.innerWidth <= 768) {
        console.log('Mobile detected - keeping product grid hidden');
        if (grid) {
          grid.innerHTML = '';
          grid.style.display = 'none';
          grid.style.visibility = 'hidden';
        }
        if (productSection) {
          productSection.classList.remove('has-search-results');
        }
        return;
      }
      
      // Handle mobile empty state (for desktop)
      if (list.length === 0) {
        if (searchInput && searchInput.value && searchInput.value.length > 0) {
          grid.innerHTML = '<div class="text-center text-muted p-4"><i class="bi bi-search"></i> No products found matching your search</div>';
        } else {
          grid.innerHTML = '';
        }
        
        // Update mobile state
        if (productSection) {
          if (searchInput && searchInput.value && searchInput.value.length > 0) {
            productSection.classList.add('has-search-results');
          } else {
            productSection.classList.remove('has-search-results');
          }
        }
        return;
      }
      
      // Update mobile state when we have results
      if (productSection) {
        productSection.classList.add('has-search-results');
      }
      
      let html = '<div class="row g-2">';
      list.forEach(p => {
        html += `
          <div class="col-6 col-md-4 col-lg-3">
            <div class="pos-product-card" onclick="addToCartClick(${p.id}, 1)" style="cursor:pointer">
              <div class="pos-product-img"><i class="bi bi-image"></i></div>
              <div class="small fw-medium text-truncate" style="width:100%">${p.name || 'Product'}</div>
              <div class="text-muted" style="font-size:11px">(${p.sku || p.id})</div>
              <div class="text-muted" style="font-size:10px">${p.current_stock || 0} in stock</div>
            </div>
          </div>
        `;
      });
      html += '</div>';
      grid.innerHTML = html;
    }

    // Update mobile search state
    function updateMobileSearchState(query, resultCount) {
      const productSection = document.querySelector('.pos-product-section');
      const productGrid = document.getElementById('product-grid');
      
      if (productSection) {
        if (query && query.length > 0) {
          productSection.classList.add('has-search-results');
          productGrid.classList.add('has-results');
        } else {
          productSection.classList.remove('has-search-results');
          productGrid.classList.remove('has-results');
        }
      }
    }

    // Show search dropdown with filtered products
    function showSearchDropdown(products) {
      const dropdown = document.getElementById('searchDropdown');
      console.log('showSearchDropdown called with products:', products.length);
      
      if (!dropdown) {
        console.log('Dropdown element not found');
        return;
      }
      
      if (products.length === 0) {
        dropdown.innerHTML = '<div class="p-3 text-muted text-center">No products found</div>';
      } else {
        let html = '';
        products.forEach(product => {
          html += `
            <div class="dropdown-item d-flex justify-content-between align-items-center p-2" 
                 style="cursor: pointer; border-bottom: 1px solid #f0f0f0;"
                 onclick="selectProductFromDropdown(${product.id})">
              <div>
                <div class="fw-medium">${product.name}</div>
                <small class="text-muted">${product.sku} • Stock: ${product.current_stock || 0}</small>
              </div>
              <div class="text-end">
                <div class="fw-bold text-primary">MWK ${(product.selling_price || 0).toFixed(2)}</div>
                <small class="text-muted">per unit</small>
              </div>
            </div>
          `;
        });
        dropdown.innerHTML = html;
      }
      
      dropdown.style.display = 'block';
      console.log('Dropdown display set to block');
    }

    // Hide search dropdown
    function hideSearchDropdown() {
      const dropdown = document.getElementById('searchDropdown');
      if (dropdown) {
        dropdown.style.display = 'none';
      }
    }

    // Select product from dropdown
    function selectProductFromDropdown(productId) {
      selectProduct(productId);
      hideSearchDropdown();
    }

    function renderCategoryFilters() {
      const categoryFilterList = document.getElementById('categoryFilterList');
      if (!categoryFilterList) {
        console.error('categoryFilterList element not found');
        return;
      }
      categoryFilterList.innerHTML = '<button class="btn btn-sm btn-outline-primary me-2 mb-2" onclick="setCategory(\'all\')">All Products</button>';
      categoriesData.forEach(cat => {
        categoryFilterList.innerHTML += `<button class="btn btn-sm btn-outline-secondary me-2 mb-2" onclick="setCategory(${cat.id})">${cat.name}</button>`;
      });
    }

    function renderBrandFilters() {
      const brandFilterList = document.getElementById('brandFilterList');
      brandFilterList.innerHTML = '<button class="btn btn-sm btn-outline-primary me-2 mb-2" onclick="setBrand(\'all\')">All Products</button>';
      brandsData.forEach(brand => {
        brandFilterList.innerHTML += `<button class="btn btn-sm btn-outline-secondary me-2 mb-2" onclick="setBrand(${brand.id})">${brand.name}</button>`;
      });
    }

    function setCategory(catId) {
      currentFilter = catId === 'all' ? 'all' : 'category-' + catId;
      document.getElementById('cartSearch').value = '';
      hideSearchDropdown();
      filterProducts();
    }

    function setBrand(brandId) {
      currentFilter = brandId === 'all' ? 'all' : 'brand-' + brandId;
      document.getElementById('cartSearch').value = '';
      hideSearchDropdown();
      filterProducts();
    }

    function setTab(tab) {
      const catBtn = document.getElementById('catBtn');
      const brandBtn = document.getElementById('brandBtn');
      const categoryFilterList = document.getElementById('categoryFilterList');
      const brandFilterList = document.getElementById('brandFilterList');
      const searchInput = document.getElementById('cartSearch');
      
      if (tab === 'category') {
        currentFilter = 'all';
        catBtn.style.background = '#2563eb';
        catBtn.style.color = '#fff';
        catBtn.className = 'btn flex-fill fw-semibold btn-sm';
        brandBtn.style.background = '';
        brandBtn.style.color = '';
        brandBtn.className = 'btn btn-outline-secondary flex-fill fw-semibold btn-sm';
        
        // Show category filters
        categoryFilterList.style.display = 'block';
        brandFilterList.style.display = 'none';
      } else if (tab === 'brands') {
        currentFilter = 'all';
        brandBtn.style.background = '#2563eb';
        brandBtn.style.color = '#fff';
        brandBtn.className = 'btn flex-fill fw-semibold btn-sm';
        catBtn.style.background = '';
        catBtn.style.color = '';
        catBtn.className = 'btn btn-outline-secondary flex-fill fw-semibold btn-sm';
        
        // Show brand filters
        brandFilterList.style.display = 'block';
        categoryFilterList.style.display = 'none';
      }
      
      // Clear search when switching tabs and update display
      searchInput.value = '';
      hideSearchDropdown();
      filterProducts();
    }

    async function updateQuantity(idx, change) {
      try {
        const currentQty = parseFloat(cart[idx]?.quantity || 0);
        const newQty = Math.max(1, currentQty + change);
        
        // If in edit mode, update locally without server call
        if (editingSaleId) {
          cart[idx].quantity = Math.round(newQty);
          cart[idx].subtotal = cart[idx].price * cart[idx].quantity;
          renderCart();
          return;
        }
        
        // Normal mode - update via server
        const res = await fetch('{{ route("pos.cart.update") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ index: idx, quantity: Math.round(newQty) })
        });
        
        if (!res.ok) {
          const errorText = await res.text();
          console.error('Server error:', errorText);
          throw new Error('Failed to update quantity');
        }
        
        const data = await res.json();
        cart = data.cart;
        renderCart();
      } catch (e) {
        console.error('Update quantity error:', e);
        alert('Error updating quantity');
      }
    }

    // New function to handle direct quantity input
    async function updateCartQuantity(idx, newQuantity) {
      try {
        const qty = parseFloat(newQuantity);
        
        // Validate quantity
        if (isNaN(qty) || qty < 1) {
          alert('Please enter a valid quantity (minimum 1)');
          renderCart(); // Reset to original value
          return;
        }
        
        // If in edit mode, update locally without server call
        if (editingSaleId) {
          cart[idx].quantity = qty;
          cart[idx].subtotal = cart[idx].price * cart[idx].quantity;
          renderCart();
          return;
        }
        
        // Normal mode - update via server
        const res = await fetch('{{ route("pos.cart.update") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ index: idx, quantity: qty })
        });
        
        if (!res.ok) {
          const errorText = await res.text();
          console.error('Server error:', errorText);
          throw new Error('Failed to update quantity');
        }
        
        const data = await res.json();
        cart = data.cart;
        renderCart();
      } catch (e) {
        console.error('Update cart quantity error:', e);
        alert('Error updating quantity');
      }
    }

    function clearCart() {
      cart = [];
      renderCart();
    }

    function confirmClearCart() {
      if (confirm('Are you sure you want to clear the entire cart? This action cannot be undone.')) {
        clearCartServer();
      }
    }

    function clearCartAfterPayment() {
      // Clear cart on server first
      clearCartServer();
      
      // Clear search field and dropdown
      const searchInput = document.getElementById('cartSearch');
      const dropdown = document.getElementById('searchDropdown');
      const counter = document.getElementById('searchCounter');
      if (searchInput) searchInput.value = '';
      if (dropdown) dropdown.style.display = 'none';
      if (counter) counter.style.display = 'none';
      
      // Reset product grid to show all products
      renderProductGrid(productsData);
      
      // Reload page to ensure fresh state
      setTimeout(() => {
        location.reload();
      }, 100);
    }

    function showModal(id) {
      if (id === 'paymentModal') {
        const total = cart.reduce((s, i) => s + (parseFloat(i.subtotal) || 0), 0);
        const items = cart.reduce((s, i) => s + (parseFloat(i.quantity) || 0), 0);
        
        // Update payment summary with current cart totals
        document.getElementById('payItems').textContent = items.toFixed(2);
        document.getElementById('payTotal').textContent = `MWK ${total.toFixed(2)}`;
        document.getElementById('payPaying').textContent = `MWK ${total.toFixed(2)}`;
        document.getElementById('payChange').textContent = 'MWK 0.00';
        document.getElementById('payBalance').textContent = 'MWK 0.00';
        
        // Set the payment amount input
        const payAmountInput = document.getElementById('payAmount');
        const payMethodSelect = document.getElementById('payMethod');
        
        payAmountInput.value = total.toFixed(2);
        payAmountInput.removeAttribute('readonly');
        payAmountInput.style.backgroundColor = '#fff';
        payAmountInput.placeholder = 'Enter amount';
        
        // Initialize payment rows
        initializePaymentRows(total);
        
        // Add event listeners for original inputs to work with our system
        payAmountInput.addEventListener('input', function(e) {
          console.log('Original payAmount input changed:', e.target.value);
          // Create or update first payment row if it exists, or create new one
          if (paymentRowsData.length === 0) {
            // Create first payment row when user starts typing
            createPaymentRow(parseFloat(e.target.value) || 0, payMethodSelect.value);
          } else {
            // Update existing first row
            updateRowData(paymentRowsData[0].id, 'amount', parseFloat(e.target.value) || 0);
          }
        });
        
        payMethodSelect.addEventListener('change', function(e) {
          console.log('Original payMethod changed:', e.target.value);
          // Create or update first payment row if it exists, or create new one
          if (paymentRowsData.length === 0) {
            createPaymentRow(parseFloat(payAmountInput.value) || 0, e.target.value);
          } else {
            updateRowData(paymentRowsData[0].id, 'method', e.target.value);
          }
        });
      }
      new bootstrap.Modal(document.getElementById(id)).show();
    }

    // Payment rows state management - completely isolated
    let paymentRowsData = [];
    let paymentRowIdCounter = 0;

    function generateUniqueId() {
      return 'pay_' + Date.now() + '_' + (++paymentRowIdCounter);
    }

    function initializePaymentRows(totalAmount) {
      // Clear everything
      const container = document.getElementById('paymentRows');
      container.innerHTML = '';
      paymentRowsData = [];
      paymentRowIdCounter = 0;
      
      // Don't create first row automatically - only create when user clicks "Add Payment Row"
      console.log('Payment rows initialized, no automatic row created');
    }

    function createPaymentRow(amount = 0, method = 'cash') {
      console.log('createPaymentRow called with:', { amount, method });
      try {
        const rowId = generateUniqueId();
        const container = document.getElementById('paymentRows');
        
        console.log('Generated rowId:', rowId);
        console.log('Container found:', !!container);
        
        // Store in state
        paymentRowsData.push({
          id: rowId,
          amount: amount,
          method: method
        });
        
        console.log('Payment rows data now:', paymentRowsData);
        
        // Create DOM element
        const rowDiv = document.createElement('div');
        rowDiv.className = 'payment-row';
        rowDiv.id = rowId;
        rowDiv.innerHTML = `
          <input type="number" class="amount-input" placeholder="Amount" value="${amount.toFixed(2)}" step="0.01">
          <select class="method-select">
            <option value="cash" ${method === 'cash' ? 'selected' : ''}>Cash</option>
            <option value="card" ${method === 'card' ? 'selected' : ''}>Card</option>
            <option value="airtel_money" ${method === 'airtel_money' ? 'selected' : ''}>Airtel Money</option>
            <option value="mpamba" ${method === 'mpamba' ? 'selected' : ''}>TNM Mpamba</option>
            <option value="bank_transfer" ${method === 'bank_transfer' ? 'selected' : ''}>Bank Transfer</option>
            <option value="national_bank" ${method === 'national_bank' ? 'selected' : ''}>National Bank</option>
            <option value="standard_bank" ${method === 'standard_bank' ? 'selected' : ''}>Standard Bank</option>
            <option value="cheque" ${method === 'cheque' ? 'selected' : ''}>Cheque</option>
            <option value="advance" ${method === 'advance' ? 'selected' : ''}>Advance</option>
          </select>
          <button type="button" class="btn btn-sm btn-outline-danger remove-btn" onclick="removePaymentRow('${rowId}')">
            <i class="bi bi-x"></i>
          </button>
        `;
        
        container.appendChild(rowDiv);
        console.log('Row added to DOM');
        
        // Add isolated event listeners
        const amountInput = rowDiv.querySelector('.amount-input');
        const methodSelect = rowDiv.querySelector('.method-select');
        
        console.log('Found inputs:', { amountInput: !!amountInput, methodSelect: !!methodSelect });
        
        amountInput.addEventListener('input', function(e) {
          console.log('Amount input changed:', e.target.value, 'for row:', rowId);
          const value = parseFloat(e.target.value) || 0;
          updateRowData(rowId, 'amount', value);
        });
        
        methodSelect.addEventListener('change', function(e) {
          console.log('Method select changed:', e.target.value, 'for row:', rowId);
          updateRowData(rowId, 'method', e.target.value);
        });
        
        updatePaymentDisplay();
        console.log('Payment row created successfully');
      } catch (error) {
        console.error('Error in createPaymentRow:', error);
      }
    }

    function updateRowData(rowId, field, value) {
      const row = paymentRowsData.find(r => r.id === rowId);
      if (row) {
        row[field] = value;
        updatePaymentDisplay();
      }
    }

    function removePaymentRow(rowId) {
      paymentRowsData = paymentRowsData.filter(r => r.id !== rowId);
      const element = document.getElementById(rowId);
      if (element) element.remove();
      updatePaymentDisplay();
    }

    function updatePaymentDisplay() {
      let totalPaying = 0;
      
      // Calculate total from our state array
      paymentRowsData.forEach(row => {
        totalPaying += parseFloat(row.amount) || 0;
      });
      
      // Get total payable from cart total
      const totalPayable = parseFloat(document.getElementById('totalAmount').textContent.replace(',', '')) || 0;
      const change = Math.max(0, totalPaying - totalPayable);
      const balance = Math.max(0, totalPayable - totalPaying);
      
      // Update payment summary
      document.getElementById('payItems').textContent = document.getElementById('totalItems').textContent;
      document.getElementById('payTotal').textContent = `MWK ${totalPayable.toFixed(2)}`;
      document.getElementById('payPaying').textContent = `MWK ${totalPaying.toFixed(2)}`;
      document.getElementById('payChange').textContent = `MWK ${Math.max(0, change).toFixed(2)}`;
      document.getElementById('payBalance').textContent = `MWK ${Math.max(0, balance).toFixed(2)}`;
      
      // Sync original inputs with first payment row if exists
      const payAmountInput = document.getElementById('payAmount');
      const payMethodSelect = document.getElementById('payMethod');
      
      if (paymentRowsData.length > 0) {
        const firstRow = paymentRowsData[0];
        if (payAmountInput && parseFloat(payAmountInput.value) !== parseFloat(firstRow.amount)) {
          payAmountInput.value = firstRow.amount.toFixed(2);
        }
        if (payMethodSelect && payMethodSelect.value !== firstRow.method) {
          payMethodSelect.value = firstRow.method;
        }
      } else {
        // No payment rows exist, clear original inputs
        if (payAmountInput) payAmountInput.value = '0.00';
        if (payMethodSelect) payMethodSelect.value = 'cash';
      }
      
      // Add validation
      validatePaymentDisplay(totalPayable, totalPaying, balance);
      
      console.log('Payment display updated:', {
        totalPayable,
        totalPaying,
        change,
        balance,
        rows: paymentRowsData.map(r => ({ id: r.id, amount: r.amount, method: r.method }))
      });
    }

    function validatePaymentDisplay(totalPayable, totalPaying, balance) {
      const finalizeBtn = document.querySelector('button[onclick="finalizePayment()"]');
      if (!finalizeBtn) return;
      
      // Remove existing validation classes
      document.querySelectorAll('.amount-input').forEach(input => {
        input.classList.remove('is-valid', 'is-invalid');
      });
      
      if (balance > 0) {
        // Underpaid
        finalizeBtn.disabled = true;
        finalizeBtn.textContent = 'Finalize Payment (Balance Due)';
        finalizeBtn.classList.add('btn-secondary');
        finalizeBtn.classList.remove('btn-primary');
      } else if (totalPaying > totalPayable * 1.1) {
        // Overpaid by more than 10%
        document.querySelectorAll('.amount-input').forEach(input => {
          if (parseFloat(input.value) > 0) {
            input.classList.add('is-invalid');
          }
        });
        finalizeBtn.disabled = false;
        finalizeBtn.textContent = 'Finalize Payment (Overpayment Alert)';
        finalizeBtn.classList.add('btn-warning');
        finalizeBtn.classList.remove('btn-primary', 'btn-secondary');
      } else {
        // Perfect payment
        finalizeBtn.disabled = false;
        finalizeBtn.textContent = 'Finalize Payment';
        finalizeBtn.classList.add('btn-primary');
        finalizeBtn.classList.remove('btn-secondary', 'btn-warning');
        document.querySelectorAll('.amount-input').forEach(input => {
          if (parseFloat(input.value) > 0) {
            input.classList.add('is-valid');
          }
        });
      }
    }

    // Payment row functions
    function addPaymentRow() {
      console.log('addPaymentRow called');
      try {
        // Calculate remaining balance and suggest it
        const totalPayable = parseFloat(document.getElementById('totalAmount').textContent.replace(',', '')) || 0;
        const currentTotal = paymentRowsData.reduce((sum, row) => sum + (parseFloat(row.amount) || 0), 0);
        const remainingBalance = Math.max(0, totalPayable - currentTotal);
        
        console.log('Creating new payment row with remaining balance:', remainingBalance);
        
        // Add new row with suggested remaining balance
        createPaymentRow(remainingBalance, 'cash');
      } catch (error) {
        console.error('Error in addPaymentRow:', error);
      }
    }

    async function finalizePayment() {
      console.log('finalizePayment called, cart:', cart);
      
      if (cart.length === 0) {
        alert('Cart is empty');
        return;
      }
      
      // If in edit mode, call edit finalize instead
      if (editingSaleId) {
        await finalizeEditedSale();
        return;
      }
      
      const total = cart.reduce((s, i) => s + (parseFloat(i.subtotal) || 0), 0);
      
      // Get total paying from payment rows state or original inputs
      let paying = 0;
      let primaryMethod = 'cash';
      
      if (paymentRowsData.length === 0) {
        // Use original payment inputs if no payment rows exist
        const payAmountInput = document.getElementById('payAmount');
        const payMethodSelect = document.getElementById('payMethod');
        
        if (payAmountInput) {
          paying = parseFloat(payAmountInput.value) || 0;
        }
        if (payMethodSelect) {
          primaryMethod = payMethodSelect.value || 'cash';
        }
        
        // Create payment data from original inputs
        paymentRowsData = [{
          id: 'original-payment',
          amount: paying,
          method: primaryMethod
        }];
      } else {
        // Use payment rows data
        paymentRowsData.forEach(row => {
          paying += parseFloat(row.amount) || 0;
        });
        
        // Get primary payment method from first row
        primaryMethod = paymentRowsData[0].method;
      }
      
      if (paying < total) {
        alert('Payment amount must be at least MWK ' + total.toFixed(2) + '. Current payment: MWK ' + paying.toFixed(2));
        return;
      }
      
      const invoiceNo = 'INV-' + Date.now().toString().slice(-6);
      const change = Math.max(paying - total, 0);
      const malawiTime = getMalawiTime();
      const dateStr = malawiTime.toLocaleDateString('en-GB', {day:'2-digit',month:'2-digit',year:'numeric'});
      const timeStr = malawiTime.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'});

      // Build receipt HTML
      let html = `<div class="text-center mb-3">
          <h3 class="fw-bold mb-2">EDUC Pharmacy</h3>
          <p class="mb-1" style="font-size:14px">Area 47, Lilongwe, Malawi</p>
          <p class="mb-0" style="font-size:14px">+265 999672775</p>
        </div>
        <hr style="border:1px solid #000;margin:8px 0">
        <div style="font-size:13px" class="mb-2">Customer: ${document.getElementById('customerSelect').value || 'Walk-In'}</div>
        <div style="font-size:13px" class="mb-2">Date: ${dateStr} ${timeStr}</div>
        <hr style="border:1px solid #000;margin:8px 0">
        <table style="font-size:13px;width:100%;border-collapse:collapse">
          <thead>
            <tr>
              <th style="text-align:left;padding:2px">Item</th>
              <th style="text-align:center;padding:2px">Qty</th>
              <th style="text-align:right;padding:2px">Price</th>
              <th style="text-align:right;padding:2px">Total</th>
            </tr>
          </thead>
          <tbody>`;
      cart.forEach(item => {
        html += `<tr>
          <td style="text-align:left;padding:4px">${item.name || 'Unknown'}</td>
          <td style="text-align:center;padding:4px">${item.quantity}</td>
          <td style="text-align:right;padding:4px">${(parseFloat(item.price) || 0).toFixed(2)}</td>
          <td style="text-align:right;padding:4px">${(parseFloat(item.subtotal) || 0).toFixed(2)}</td>
        </tr>`;
      });
      html += `</tbody></table>
        <hr style="border:1px solid #000;margin:8px 0">
        <div class="d-flex justify-content-between fw-bold" style="font-size:14px">
          <span>Total:</span>
          <span>MWK ${total.toFixed(2)}</span>
        </div>`;
      
      // Add all payment methods to receipt
      paymentRowsData.forEach((row, index) => {
        const methodLabel = row.method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        html += `<div class="d-flex justify-content-between" style="font-size:13px">
          <span>Payment ${index + 1} (${methodLabel}):</span>
          <span>MWK ${(parseFloat(row.amount) || 0).toFixed(2)}</span>
        </div>`;
      });
      
      html += `<div class="d-flex justify-content-between" style="font-size:13px">
          <span>Total Paid:</span>
          <span>MWK ${paying.toFixed(2)}</span>
        </div>
        <div class="d-flex justify-content-between" style="font-size:13px">
          <span>Change:</span>
          <span>MWK ${change.toFixed(2)}</span>
        </div>
        <hr style="border:1px solid #000;margin:8px 0">
        <p class="text-center text-muted mb-0" style="font-size:13px">Thank you for your purchase!</p>
        <p class="text-center text-muted mb-0" style="font-size:12px">Powered by EDUC Consultancy</p>`;

      document.getElementById('receipt-print-area').innerHTML = html;

      // Submit finalize to backend
      try {
        const totalAmount = cart.reduce((s, i) => s + (parseFloat(i.subtotal) || 0), 0);
        const totalItems = cart.reduce((s, i) => s + (parseFloat(i.quantity) || 0), 0);
        
        // Create payments array for ONE sale
        const payments = paymentRowsData.map(row => ({
          method: row.method,
          amount: parseFloat(row.amount) || 0
        }));
        
        const payloadData = {
          invoice_no: invoiceNo,
          customer_name: document.getElementById('customerSelect').value || 'Walk-In Customer',
          contact_number: document.getElementById('customerSelect').value || null,
          payment_status: 'Paid',
          payments: payments,
          is_split_payment: payments.length > 1
        };
        
        // Only add payment_method for single payments
        if (payments.length === 1) {
          payloadData.payment_method = payments[0].method;
        }
        
        console.log('Sending payload to pos.finalize:', payloadData);
        
        const res = await fetch('{{ route("pos.finalize") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify(payloadData)
        });
        
        console.log('Response status:', res.status);
        
        if (!res.ok) {
          const errorText = await res.text();
          console.error('Server error response:', errorText);
          throw new Error('Server returned ' + res.status + ': ' + errorText);
        }
        
        const data = await res.json();
        console.log('Response data:', data);
        
        if (!data.success) {
          throw new Error(data.error || 'Failed to finalize');
        }

        // Handle multiple sales (split payments) or single sale
        let salesData;
        if (data.sales && Array.isArray(data.sales)) {
          // Multiple sales created for different payment methods
          salesData = data.sales;
          console.log('Multiple sales created:', salesData);
        } else if (data.sale_id) {
          // Single sale created (backward compatibility)
          let paymentMethod = 'cash'; // default fallback
          try {
            const paymentMethodSelect = document.getElementById('payMethod');
            if (paymentMethodSelect) {
              paymentMethod = paymentMethodSelect.value || 'cash';
            }
          } catch (e) {
            console.warn('Could not get payment method from dropdown, using default:', e);
          }
          
          salesData = [{
            id: data.sale_id,
            invoice_no: data.invoice_no,
            payment_method: paymentMethod,
            total_amount: data.total_amount || totalAmount
          }];
        } else {
          throw new Error('Invalid response format');
        }

        // Validate sales data before processing
        if (!Array.isArray(salesData) || salesData.length === 0) {
          throw new Error('No sales data received');
        }

        // Show receipt with all sales information
        let receiptHtml = '';
        salesData.forEach((sale, index) => {
          // Validate each sale object
          if (!sale || typeof sale !== 'object') {
            console.error('Invalid sale object at index', index, sale);
            return;
          }
          
          // No additional text or separators for multiple sales
        });
        
        // Update receipt content
        const existingReceipt = document.getElementById('receipt-print-area').innerHTML;
        document.getElementById('receipt-print-area').innerHTML = receiptHtml + existingReceipt;

        // Close payment modal and print directly
        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
        setTimeout(() => {
          printReceipt();
        }, 300);

        // Clear cart immediately after successful payment
        clearCartServer();
        
        // Clear search field and dropdown
        const searchInput = document.getElementById('cartSearch');
        const dropdown = document.getElementById('searchDropdown');
        const counter = document.getElementById('searchCounter');
        if (searchInput) searchInput.value = '';
        if (dropdown) dropdown.style.display = 'none';
        if (counter) counter.style.display = 'none';
        
        // Reset product grid to show all products
        renderProductGrid(productsData);

        // Reload page after successful payment to ensure fresh state
        setTimeout(() => {
          location.reload();
        }, 2000);
      } catch (e) {
        console.error('finalize error:', e);
        alert('Error finalizing sale: ' + e.message);
      }
    }

    function printReceipt() {
      const content = document.getElementById('receipt-print-area').innerHTML;
      
      // Create a hidden iframe for printing
      const iframe = document.createElement('iframe');
      iframe.style.position = 'fixed';
      iframe.style.right = '0';
      iframe.style.bottom = '0';
      iframe.style.width = '0';
      iframe.style.height = '0';
      iframe.style.border = 'none';
      iframe.style.visibility = 'hidden';
      
      document.body.appendChild(iframe);
      
      const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
      iframeDoc.write('<html><head><title>Receipt</title><style>body{font-family:monospace;font-size:11px;padding:8px;line-height:1.2}table{width:100%;border-collapse:collapse}td,th{padding:1px 2px}.text-center{text-align:center}.text-end{text-align:right}.fw-bold{font-weight:bold}.text-muted{color:#666}hr{border:none;border-top:1px dashed #000;margin:4px 0}.d-flex{display:flex}.justify-content-between{justify-content:space-between}.mb-0{margin-bottom:0}.mb-1{margin-bottom:2px}.mb-2{margin-bottom:4px}@media print{body{margin:0;padding:8px}}</style></head><body>' + content + '</body></html>');
      iframeDoc.close();
      
      // Wait for content to load, then print
      iframe.onload = function() {
        try {
          iframe.contentWindow.focus();
          iframe.contentWindow.print();
          
          // Remove iframe after printing (with delay to ensure print dialog is shown)
          setTimeout(() => {
            document.body.removeChild(iframe);
          }, 1000);
        } catch (e) {
          console.error('Print error:', e);
          document.body.removeChild(iframe);
        }
      };
    }

    // Recent Sales Functions
    async function loadRecentSales() {
      try {
        const res = await fetch('{{ route("pos.recent.sales") }}');
        const data = await res.json();
        
        if (data.success) {
          renderRecentSales(data.sales);
          const modal = new bootstrap.Modal(document.getElementById('recentSalesModal'));
          modal.show();
        } else {
          alert('Failed to load recent sales');
        }
      } catch (e) {
        console.error('Error loading recent sales:', e);
        alert('Error loading recent sales');
      }
    }

    function renderRecentSales(sales) {
      const tbody = document.getElementById('recentSalesTableBody');
      
      if (sales.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No recent sales found</td></tr>';
        return;
      }
      
      tbody.innerHTML = '';
      sales.forEach((sale, index) => {
        // Validate sale object
        if (!sale || typeof sale !== 'object') {
          console.error('Invalid sale object at index', index, sale);
          return;
        }
        
        // Get product names and calculate total items sold
        let productNames = [];
        let totalItems = 0;
        
        if (sale.items && sale.items.length > 0) {
          sale.items.forEach(item => {
            if (item.product && item.product.name) {
              productNames.push(item.product.name);
              totalItems += parseFloat(item.quantity || 0);
            }
          });
        }
        
        const productDisplay = productNames.length > 0 ? 
          productNames.slice(0, 2).join(', ') + (productNames.length > 2 ? '...' : '') : 
          'Unknown Products';
        
        // Handle payment method display
        let paymentMethodDisplay = getPaymentMethodBadge(sale.payment_method);
        
        const editIndicator = sale.is_edited ? 
          '<span class="badge bg-danger ms-1" title="This sale was edited"><i class="bi bi-pencil-fill"></i> Edited</span>' : '';
        
        // Safely get and format total amount
        const totalAmount = parseFloat(sale.total_amount) || 0;
        
        tbody.innerHTML += `
          <tr>
            <td>${productDisplay}</td>
            <td class="text-center">${totalItems}</td>
            <td>${sale.created_at ? formatMalawiDateTime(new Date(sale.created_at)) : 'Unknown'}</td>
            <td>${sale.customer_name || 'Walk-In Customer'}</td>
            <td>${paymentMethodDisplay}</td>
            <td class="text-end fw-bold">MWK ${totalAmount.toLocaleString()}</td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary" onclick="viewSaleDetails(${sale.id})">
                <i class="bi bi-eye"></i>
              </button>
              <button class="btn btn-sm btn-outline-secondary" onclick="printSaleReceipt(${sale.id})">
                <i class="bi bi-printer"></i>
              </button>
              <button class="btn btn-sm btn-outline-warning" onclick="editSale(${sale.id})">
                <i class="bi bi-pencil"></i>
              </button>
            </td>
          </tr>
        `;
      });
    }

    function getPaymentMethodBadge(method) {
      const badges = {
        'cash': '<span class="badge bg-primary">Cash</span>',
        'card': '<span class="badge bg-info">Card</span>',
        'cheque': '<span class="badge bg-secondary">Cheque</span>',
        'bank_transfer': '<span class="badge bg-success">Bank Transfer</span>',
        'airtel_money': '<span class="badge bg-warning">Airtel Money</span>',
        'mpamba': '<span class="badge bg-danger">TNM Mpamba</span>',
        'national_bank': '<span class="badge bg-dark">National Bank</span>',
        'standard_bank': '<span class="badge bg-light text-dark">Standard Bank</span>',
        'advance': '<span class="badge bg-secondary">Advance</span>'
      };
      return badges[method] || '<span class="badge bg-secondary">Unknown</span>';
    }

    function refreshRecentSales() {
      loadRecentSales();
    }

    function viewSaleDetails(saleId) {
      // Open sale details in new tab or modal
      window.open(`/sales/${saleId}`, '_blank');
    }

    function printSaleReceipt(saleId) {
      // Print receipt for specific sale
      window.open(`/sales/${saleId}/print`, '_blank');
    }

    async function deleteSale(saleId) {
      if (confirm('Are you sure you want to delete this sale? This action cannot be undone and will restore the stock quantities.')) {
        try {
          const res = await fetch(`/sales/${saleId}`, {
            method: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            }
          });
          
          const data = await res.json();
          
          if (data.success) {
            // Show success message
            alert('Sale deleted successfully and stock has been restored.');
            
            // Refresh the recent sales table
            loadRecentSales();
          } else {
            // Show error message
            alert('Failed to delete sale. Please try again.');
          }
        } catch (error) {
          console.error('Error deleting sale:', error);
          alert('Error deleting sale. Please try again.');
        }
      }
    }

    // Edit Sale Functionality
    let editingSaleId = null;
    let originalSaleData = null;

    async function editSale(saleId) {
      try {
        const res = await fetch(`/pos/sale/${saleId}/edit-data`);
        const data = await res.json();
        
        if (data.success) {
          // Store original data for comparison
          originalSaleData = data.sale;
          editingSaleId = saleId;
          
          // Close recent sales modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('recentSalesModal'));
          modal.hide();
          
          // Load sale data into POS
          await loadSaleIntoPOS(data.sale);
          
          // Show edit indicator
          showEditIndicator();
        } else {
          alert('Failed to load sale data');
        }
      } catch (e) {
        console.error('Error loading sale for edit:', e);
        alert('Error loading sale for edit');
      }
    }

    async function loadSaleIntoPOS(saleData) {
      // Clear current cart
      cart = [];
      
      // Load sale items into cart
      for (const item of saleData.items) {
        cart.push({
          id: item.product_id,
          name: item.product.name,
          sku: item.product.sku,
          quantity: item.quantity,
          price: item.price,
          subtotal: item.subtotal,
          current_stock: item.product.current_stock
        });
      }
      
      // Set customer
      document.getElementById('customerSelect').value = saleData.customer_name || 'Walk-In Customer';
      
      // Set payment method
      document.getElementById('payMethod').value = saleData.payment_method;
      
      // Render cart
      renderCart();
      
      // Show edit notification
      showEditNotification();
    }

    function showEditIndicator() {
      // Add edit indicator to POS header
      const header = document.querySelector('.pos-cart-header');
      const existingIndicator = document.getElementById('editIndicator');
      
      if (!existingIndicator) {
        const indicator = document.createElement('div');
        indicator.id = 'editIndicator';
        indicator.className = 'alert alert-warning alert-sm mb-2';
        indicator.innerHTML = `
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <i class="bi bi-pencil-square"></i>
              <strong>Editing Sale #${originalSaleData.invoice_no}</strong>
              <small class="text-muted ms-2">Original: MWK ${parseFloat(originalSaleData.total_amount).toFixed(2)}</small>
            </div>
            <button class="btn btn-sm btn-outline-danger" onclick="cancelEdit()">
              Cancel Edit
            </button>
          </div>
        `;
        header.insertBefore(indicator, header.firstChild);
      }
    }

    function showEditNotification() {
      // Show notification that sale is being edited
      const notification = document.createElement('div');
      notification.className = 'alert alert-info alert-sm';
      notification.innerHTML = `
        <i class="bi bi-info-circle"></i>
        <strong>Sale Edit Mode:</strong> You can modify items, quantities, and payment method. 
        Changes will be flagged when saved.
      `;
      
      const cartSection = document.querySelector('.pos-cart-section');
      cartSection.insertBefore(notification, cartSection.children[1]);
      
      // Auto-hide after 5 seconds
      setTimeout(() => {
        if (notification.parentNode) {
          notification.remove();
        }
      }, 5000);
    }

    function cancelEdit() {
      editingSaleId = null;
      originalSaleData = null;
      
      // Remove edit indicator
      const indicator = document.getElementById('editIndicator');
      if (indicator) {
        indicator.remove();
      }
      
      // Clear cart
      cart = [];
      renderCart();
    }

    // Override the finalizeSale function to handle edits
    async function finalizeSaleWithEdit() {
      if (editingSaleId) {
        await finalizeEditedSale();
      } else {
        await finalizeSale();
      }
    }

    async function finalizeEditedSale() {
      try {
        const total = cart.reduce((s, i) => s + (parseFloat(i.subtotal) || 0), 0);
        const paying = parseFloat(document.getElementById('payAmount').value) || total;
        const method = document.getElementById('payMethod').value;
        const change = Math.max(paying - total, 0);
        const invoiceNo = originalSaleData.invoice_no; // Keep original invoice number

        // Calculate changes
        const originalTotal = parseFloat(originalSaleData.total_amount);
        const newTotal = total;
        const totalChange = newTotal - originalTotal;

        // Create edit log
        const editLog = {
          original_total: originalTotal,
          new_total: newTotal,
          total_change: totalChange,
          original_payment_method: originalSaleData.payment_method,
          new_payment_method: method,
          original_items: originalSaleData.items.map(item => ({
            product_id: item.product_id,
            product_name: item.product.name,
            original_quantity: item.quantity,
            original_price: item.price,
            original_subtotal: item.subtotal
          })),
          new_items: cart.map(item => ({
            product_id: item.id,
            product_name: item.name,
            new_quantity: item.quantity,
            new_price: item.price,
            new_subtotal: item.subtotal
          })),
          edited_by: {{ auth()->user()->id }},
          edited_at: new Date().toISOString()
        };

        const payloadData = {
          invoice_no: invoiceNo,
          customer_name: document.getElementById('customerSelect').value || 'Walk-In Customer',
          contact_number: document.getElementById('customerSelect').value || null,
          payment_method: method,
          payment_status: 'Paid',
          is_edit: true,
          original_sale_id: editingSaleId,
          edit_log: editLog,
          cart: cart
        };

        const res = await fetch('{{ route("pos.edit.sale") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify(payloadData)
        });

        if (!res.ok) {
          const errorText = await res.text();
          throw new Error('Server returned ' + res.status + ': ' + errorText);
        }

        const data = await res.json();
        
        if (data.success) {
          // Show success message with edit details
          showEditSuccessMessage(editLog);
          
          // Reset edit state
          cancelEdit();
          
          // Close payment modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
          modal.hide();
          
          // Refresh recent sales
          loadRecentSales();
        } else {
          alert('Failed to update sale: ' + data.error);
        }
      } catch (e) {
        console.error('Edit finalize error:', e);
        alert('Error updating sale: ' + e.message);
      }
    }

    function showEditSuccessMessage(editLog) {
      const message = document.createElement('div');
      message.className = 'alert alert-success';
      message.innerHTML = `
        <h6><i class="bi bi-check-circle"></i> Sale Updated Successfully!</h6>
        <div class="mt-2">
          <strong>Changes Made:</strong>
          <ul class="mb-0">
            <li>Total Amount: MWK ${editLog.original_total.toFixed(2)} → MWK ${editLog.new_total.toFixed(2)} 
                (${editLog.total_change >= 0 ? '+' : ''}MWK ${editLog.total_change.toFixed(2)})</li>
            <li>Payment Method: ${editLog.original_payment_method} → ${editLog.new_payment_method}</li>
            <li>Items Modified: ${editLog.original_items.length} → ${editLog.new_items.length}</li>
          </ul>
          <small class="text-muted">This sale has been flagged as edited. Original data preserved for audit.</small>
        </div>
      `;
      
      // Insert at top of page
      document.body.insertBefore(message, document.body.firstChild);
      
      // Auto-hide after 10 seconds
      setTimeout(() => {
        if (message.parentNode) {
          message.remove();
        }
      }, 10000);
    }

     // Init - register is guaranteed to be open
     // Skip rendering old category/brand filters and product grid for React-style design
     // renderCategoryFilters();
     // renderBrandFilters();
     // renderProductGrid(productsData);

    // Register Details Functions
    // Helper function for number formatting (JavaScript equivalent of PHP's number_format)
    function number_format(number, decimals) {
      return parseFloat(number).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // Helper function to get Malawi time (CAT - UTC+2)
    function getMalawiTime() {
      const now = new Date();
      // Convert to Malawi time (UTC+2)
      const malawiTime = new Date(now.getTime() + (now.getTimezoneOffset() * 60000) + (2 * 3600000));
      return malawiTime;
    }

    // Helper function to format date/time in Malawi timezone
    function formatMalawiDateTime(date = null) {
      const malawiTime = date ? new Date(date.getTime() + (date.getTimezoneOffset() * 60000) + (2 * 3600000)) : getMalawiTime();
      const dateStr = malawiTime.toLocaleDateString('en-GB', {day:'2-digit',month:'2-digit',year:'numeric'});
      const timeStr = malawiTime.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'});
      return `${dateStr} ${timeStr}`;
    }

    // Update header date with Malawi time
    function updateHeaderDate() {
      const headerDateElement = document.getElementById('headerDate');
      if (headerDateElement) {
        headerDateElement.textContent = formatMalawiDateTime() + ' 📅';
      }
    }

    // Update header date immediately and then every minute
    updateHeaderDate();
    setInterval(updateHeaderDate, 60000);

    // Update expense date fields with current Malawi time
    function updateExpenseDates() {
      const expenseDate = document.getElementById('expenseDate');
      const paidOnField = document.querySelector('input[value="22-02-2026 07:18"]');
      
      if (expenseDate) {
        expenseDate.value = formatMalawiDateTime();
      }
      if (paidOnField) {
        paidOnField.value = formatMalawiDateTime();
      }
    }

    // Update expense dates when modal is shown
    document.addEventListener('shown.bs.modal', function (e) {
      if (e.target.id === 'expenseModal') {
        updateExpenseDates();
      }
    });

    async function loadRegisterDetails() {
      try {
        const res = await fetch('{{ route("pos.register.details") }}');
        const data = await res.json();
        
        if (data.success) {
          renderRegisterDetails(data.register);
        } else {
          document.getElementById('registerDetailsContent').innerHTML = 
            `<div class="alert alert-danger">${data.error}</div>`;
        }
      } catch (e) {
        console.error('Error loading register details:', e);
        document.getElementById('registerDetailsContent').innerHTML = 
          `<div class="alert alert-danger">Error loading register details: ${e.message}</div>`;
      }
    }

    function renderRegisterDetails(register) {
      const openedAt = new Date(register.opened_at);
      const title = `Register Details (${formatMalawiDateTime(openedAt)} - Present)`;
      document.getElementById('registerDetailsTitle').textContent = title;

      const paymentMethodsHtml = `
        <div class="row">
          <div class="col-md-6">
            <h6 class="fw-bold">Details of products sold</h6>
            <div style="max-height: 300px; overflow-y: auto;">
              <table class="table table-sm small">
                <thead><tr><th>#</th><th>SKU</th><th>Product</th><th>Quantity</th><th>Total amount</th></tr></thead>
                <tbody>
                  ${register.products.map((product, index) => `
                    <tr>
                      <td>${index + 1}.</td>
                      <td>${product.sku}</td>
                      <td>${product.name}</td>
                      <td>${number_format(product.quantity, 2)}</td>
                      <td>MWK ${number_format(product.total_amount, 2)}</td>
                    </tr>
                  `).join('')}
                  <tr style="background:#f0fdf4">
                    <td class="fw-bold">#</td><td></td><td></td>
                    <td class="fw-bold">${number_format(register.total_quantity, 2)}</td>
                    <td class="fw-bold">Grand Total: MWK ${number_format(register.totals.total_sales, 2)}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="col-md-6">
            <h6 class="fw-bold">Details of products sold (By Brand)</h6>
            <div style="max-height: 300px; overflow-y: auto;">
              <table class="table table-sm small">
                <thead><tr><th>#</th><th>Brands</th><th>Quantity</th><th>Total amount</th></tr></thead>
                <tbody>
                  ${register.brands.map((brand, index) => `
                    <tr>
                      <td>${index + 1}.</td>
                      <td>${brand.brand}</td>
                      <td>${number_format(brand.quantity, 2)}</td>
                      <td>MWK ${number_format(brand.total_amount, 2)}</td>
                    </tr>
                  `).join('')}
                  <tr style="background:#f0fdf4">
                    <td class="fw-bold">#</td><td></td>
                    <td class="fw-bold">${number_format(register.total_quantity, 2)}</td>
                    <td class="fw-bold">Grand Total: MWK ${number_format(register.totals.total_sales, 2)}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      `;

      const summaryHtml = `
        <div class="row mt-3">
          <div class="col-md-6">
            <h6 class="fw-bold">Payment Methods</h6>
            <table class="table table-sm small">
              <thead><tr><th>Payment Method</th><th>Sell</th><th>Expense</th></tr></thead>
              <tbody>
                <tr><td>Cash in hand:</td><td>MWK ${number_format(register.totals.cash_in_hand, 2)}</td><td>--</td></tr>
                <tr><td>Cash Payment:</td><td>MWK ${number_format(register.payment_methods.cash, 2)}</td><td>MWK 0.00</td></tr>
                <tr><td>Cheque Payment:</td><td>MWK ${number_format(register.payment_methods.cheque, 2)}</td><td>MWK 0.00</td></tr>
                <tr><td>Card Payment:</td><td>MWK ${number_format(register.payment_methods.card, 2)}</td><td>MWK 0.00</td></tr>
                <tr><td>Bank Transfer:</td><td>MWK ${number_format(register.payment_methods.bank_transfer, 2)}</td><td>MWK 0.00</td></tr>
                <tr><td>Advance payment:</td><td>MWK ${number_format(register.payment_methods.advance, 2)}</td><td>MWK 0.00</td></tr>
                <tr><td>National Bank:</td><td>MWK ${number_format(register.payment_methods.national_bank, 2)}</td><td>MWK 0.00</td></tr>
                <tr><td>Standard Bank:</td><td>MWK ${number_format(register.payment_methods.standard_bank, 2)}</td><td>MWK 0.00</td></tr>
                <tr><td>Airtel Money:</td><td>MWK ${number_format(register.payment_methods.airtel_money, 2)}</td><td>MWK 0.00</td></tr>
                <tr><td>TNM Mpamba:</td><td>MWK ${number_format(register.payment_methods.mpamba, 2)}</td><td>MWK 0.00</td></tr>
                <tr><td>Other Payments:</td><td>MWK ${number_format(register.payment_methods.other, 2)}</td><td>MWK 0.00</td></tr>
              </tbody>
            </table>
          </div>
          <div class="col-md-6">
            <h6 class="fw-bold">Summary Totals</h6>
            <div class="mb-3">
              <div class="d-flex justify-content-between py-1 px-2 small"><span class="fw-semibold">Total Sales:</span><span>MWK ${number_format(register.totals.total_sales, 2)}</span></div>
              <div class="d-flex justify-content-between py-1 px-2 small" style="background:#fef2f2"><span class="fw-semibold">Total Refund</span><span>MWK ${number_format(register.totals.total_refund, 2)}</span></div>
              <div class="d-flex justify-content-between py-1 px-2 small" style="background:#fef2f2"><span class="fw-semibold">Total Payment</span><span>MWK ${number_format(register.totals.total_payment, 2)}</span></div>
              <div class="d-flex justify-content-between py-1 px-2 small" style="background:#fef2f2"><span class="fw-semibold">Credit Sales:</span><span>MWK ${number_format(register.totals.credit_sales, 2)}</span></div>
              <div class="d-flex justify-content-between py-1 px-2 small" style="background:#fef2f2"><span class="fw-semibold">Total Expense:</span><span>MWK ${number_format(register.totals.total_expense, 2)}</span></div>
            </div>
            <div class="small">
              <p><strong>User:</strong> ${register.user.name}</p>
              <p><strong>Email:</strong> ${register.user.email}</p>
              <p><strong>Business Location:</strong> ${register.business_location}</p>
            </div>
          </div>
        </div>
      `;

      document.getElementById('registerDetailsContent').innerHTML = paymentMethodsHtml + summaryHtml;
    }

    function printRegisterDetails() {
      const content = document.getElementById('registerDetailsContent').innerHTML;
      const title = document.getElementById('registerDetailsTitle').textContent;
      
      // Create a hidden iframe for printing
      const iframe = document.createElement('iframe');
      iframe.style.position = 'fixed';
      iframe.style.right = '0';
      iframe.style.bottom = '0';
      iframe.style.width = '0';
      iframe.style.height = '0';
      iframe.style.border = 'none';
      iframe.style.visibility = 'hidden';
      
      document.body.appendChild(iframe);
      
      const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
      iframeDoc.write(`
        <html>
          <head>
            <title>${title}</title>
            <style>
              body{font-family:Arial,sans-serif;font-size:12px;padding:8px;line-height:1.2}
              table{width:100%;border-collapse:collapse}
              td,th{padding:2px 4px;border:1px solid #ddd}
              .text-center{text-align:center}
              .text-end{text-align:right}
              .fw-bold{font-weight:bold}
              .text-muted{color:#666}
              hr{border:none;border-top:1px dashed #000;margin:4px 0}
              .small{font-size:11px}
              @media print{body{margin:0;padding:8px}}
            </style>
          </head>
          <body>
            <h5>${title}</h5>
            ${content}
          </body>
        </html>
      `);
      iframeDoc.close();
      
      // Wait for content to load, then print
      iframe.onload = function() {
        try {
          iframe.contentWindow.focus();
          iframe.contentWindow.print();
          
          // Remove iframe after printing (with delay to ensure print dialog is shown)
          setTimeout(() => {
            document.body.removeChild(iframe);
          }, 1000);
        } catch (e) {
          console.error('Print error:', e);
          document.body.removeChild(iframe);
        }
      };
    }

    // Mobile layout JavaScript
    function initMobileLayout() {
      const desktopLayout = document.querySelector('.d-flex.flex-grow-1');
      const mobileLayout = document.querySelector('.mobile-layout');
      
      if (window.innerWidth <= 768) {
        // Hide desktop layout
        if (desktopLayout) {
          desktopLayout.style.display = 'none';
        }
        
        // Show mobile layout
        if (mobileLayout) {
          mobileLayout.style.display = 'block';
        }
        
        // Initialize mobile search
        const mobileSearchInput = document.getElementById('mobileSearchInput');
        if (mobileSearchInput) {
          mobileSearchInput.addEventListener('input', mobileSearchProducts);
        }
        
        console.log('Mobile layout activated');
      } else {
        // Show desktop layout
        if (desktopLayout) {
          desktopLayout.style.display = 'flex';
        }
        
        // Hide mobile layout
        if (mobileLayout) {
          mobileLayout.style.display = 'none';
        }
        
        console.log('Desktop layout activated');
      }
    }
    
    function mobileSearchProducts() {
      const query = document.getElementById('mobileSearchInput').value.toLowerCase().trim();
      const resultsContainer = document.getElementById('mobileSearchResults');
      const mobileCartGrid = document.getElementById('mobileCartGrid');
      
      if (!query) {
        resultsContainer.innerHTML = '';
        return;
      }
      
      // Filter products
      let results = productsData.filter(p => 
        (p.name && p.name.toLowerCase().includes(query)) || 
        (p.sku && p.sku.toLowerCase().includes(query))
      );
      
      if (results.length > 0) {
        let html = '';
        results.slice(0, 5).forEach(product => {
          const price = parseFloat(product.selling_price) || 0;
          html += `
            <div class="mobile-cart-item" onclick="addToCartClick(${product.id}, 1)">
              <div class="mobile-cart-item-header">
                <div class="mobile-cart-item-name">${product.name}</div>
                <button class="mobile-cart-item-remove" onclick="event.stopPropagation(); removeFromCart(getMobileCartIndex('${product.id}'))">
                  <i class="bi bi-x"></i>
                </button>
              </div>
              <div class="mobile-cart-item-details">
                <span>MWK ${price.toFixed(2)}</span>
                <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); addToCartClick(${product.id}, 1)">
                  <i class="bi bi-plus"></i>
                </button>
              </div>
            </div>
          `;
        });
        resultsContainer.innerHTML = html;
      } else {
        resultsContainer.innerHTML = '<div class="text-center text-muted p-3">No products found</div>';
      }
    }
    
    function getMobileCartIndex(productId) {
      const mobileCart = JSON.parse(localStorage.getItem('mobileCart') || '[]');
      return mobileCart.findIndex(item => item.id === productId);
    }
    
    function addToMobileCart(productId, quantity) {
      const mobileCart = JSON.parse(localStorage.getItem('mobileCart') || '[]');
      const existingIndex = mobileCart.findIndex(item => item.id === productId);
      
      if (existingIndex >= 0) {
        mobileCart[existingIndex].quantity += quantity;
      } else {
        const product = productsData.find(p => p.id === productId);
        if (product) {
          mobileCart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.selling_price) || 0,
            quantity: quantity
          });
        }
      }
      
      localStorage.setItem('mobileCart', JSON.stringify(mobileCart));
      renderMobileCart();
    }
    
    function removeFromMobileCart(productId) {
      let mobileCart = JSON.parse(localStorage.getItem('mobileCart') || '[]');
      mobileCart = mobileCart.filter(item => item.id !== productId);
      localStorage.setItem('mobileCart', JSON.stringify(mobileCart));
      renderMobileCart();
    }
    
    function renderMobileCart() {
      const mobileCartGrid = document.getElementById('mobileCartGrid');
      const mobileCart = JSON.parse(localStorage.getItem('mobileCart') || '[]');
      
      if (mobileCart.length === 0) {
        mobileCartGrid.innerHTML = `
          <div class="cart-empty-state text-center text-muted py-5">
            <i class="bi bi-cart3" style="font-size: 48px;"></i>
            <p class="mt-3">No items in cart</p>
          </div>
        `;
      } else {
        let html = '';
        let total = 0;
        mobileCart.forEach((item, index) => {
          const subtotal = item.price * item.quantity;
          total += subtotal;
          html += `
            <div class="mobile-cart-item">
              <div class="mobile-cart-item-header">
                <div class="mobile-cart-item-name">${item.name}</div>
                <button class="mobile-cart-item-remove" onclick="removeFromMobileCart(${item.id})">
                  <i class="bi bi-x"></i>
                </button>
              </div>
              <div class="mobile-cart-item-quantity">
                <button class="btn btn-sm btn-outline-secondary" onclick="updateMobileQuantity(${item.id}, -1)">
                  <i class="bi bi-dash"></i>
                </button>
                <span class="px-2">${item.quantity}</span>
                <button class="btn btn-sm btn-outline-primary" onclick="updateMobileQuantity(${item.id}, 1)">
                  <i class="bi bi-plus"></i>
                </button>
              </div>
              <div class="mobile-cart-item-details">
                <span>MWK ${item.price.toFixed(2)} × ${item.quantity} = MWK ${subtotal.toFixed(2)}</span>
              </div>
            </div>
          `;
        });
        
        mobileCartGrid.innerHTML = html;
        
        // Update total payable
        const totalPayableElement = document.getElementById('totalPayable');
        if (totalPayableElement) {
          totalPayableElement.textContent = total.toFixed(2);
        }
      }
    }
    
    function updateMobileQuantity(productId, change) {
      let mobileCart = JSON.parse(localStorage.getItem('mobileCart') || '[]');
      const itemIndex = mobileCart.findIndex(item => item.id === productId);
      
      if (itemIndex >= 0) {
        mobileCart[itemIndex].quantity = Math.max(1, mobileCart[itemIndex].quantity + change);
        localStorage.setItem('mobileCart', JSON.stringify(mobileCart));
        renderMobileCart();
      }
    }
    
    // Initialize mobile layout on page load
    document.addEventListener('DOMContentLoaded', function() {
      initMobileLayout();
    });
    
    // Check on window resize
    window.addEventListener('resize', initMobileLayout);

    // Override showModal to load register details when register modal is opened
    const originalShowModal = window.showModal;
    window.showModal = function(modalId) {
      if (modalId === 'registerDetailsModal') {
        loadRegisterDetails();
      }
      return originalShowModal(modalId);
    };

    // Mobile initialization - hide product grid by default
    document.addEventListener('DOMContentLoaded', function() {
      const productGrid = document.getElementById('product-grid');
      const productSection = document.querySelector('.pos-product-section');
      const productControls = document.querySelector('.pos-product-controls');
      const productTabs = document.querySelector('.pos-product-tabs');
      const productSearch = document.getElementById('productSearch');
      
      // Check if mobile
      if (window.innerWidth <= 768) {
        console.log('Mobile detected - hiding product elements by default');
        
        // Clear and hide product grid
        if (productGrid) {
          productGrid.innerHTML = '';
          productGrid.classList.remove('has-results');
          productGrid.style.display = 'none';
          productGrid.style.visibility = 'hidden';
        }
        
        // Hide product controls
        if (productControls) {
          productControls.style.display = 'none';
          productControls.style.visibility = 'hidden';
        }
        
        // Hide product tabs
        if (productTabs) {
          productTabs.style.display = 'none';
          productTabs.style.visibility = 'hidden';
        }
        
        // Hide product search input
        if (productSearch) {
          productSearch.style.display = 'none';
          productSearch.style.visibility = 'hidden';
        }
        
        // Ensure product section doesn't have search results class
        if (productSection) {
          productSection.classList.remove('has-search-results');
        }
      }
      
      // Continuously monitor and hide product elements on mobile
      setInterval(function() {
        if (window.innerWidth <= 768) {
          if (productGrid) {
            productGrid.style.display = 'none';
            productGrid.style.visibility = 'hidden';
          }
          if (productControls) {
            productControls.style.display = 'none';
            productControls.style.visibility = 'hidden';
          }
          if (productTabs) {
            productTabs.style.display = 'none';
            productTabs.style.visibility = 'hidden';
          }
          if (productSearch) {
            productSearch.style.display = 'none';
            productSearch.style.visibility = 'hidden';
          }
        }
      }, 1000); // Check every second
    });
  </script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
