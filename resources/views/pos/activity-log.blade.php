@extends('layouts.app')
@section('content')

  <div class="app-wrapper">
    <aside class="app-sidebar" id="app-sidebar"></aside>
    <div class="app-main">
      <header class="app-topbar" id="app-topbar"></header>
      <main class="app-content">
        <h1 class="h4 fw-bold mb-3">Activity Log</h1>
        <div class="card border mb-3"><div class="card-body py-2"><button class="btn btn-link text-primary fw-semibold text-decoration-none p-0">🔽 Filters</button></div></div>
        <div class="card border">
          <div class="card-body border-bottom">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2 small">
                <span>Show</span><select class="form-select form-select-sm" style="width:auto"><option>25</option></select><span>entries</span>
                <button class="toolbar-btn">📋 Export CSV</button><button class="toolbar-btn">📊 Export Excel</button><button class="toolbar-btn">🖨 Print</button><button class="toolbar-btn">👁 Column visibility</button><button class="toolbar-btn">📄 Export PDF</button>
              </div>
              <input type="text" class="form-control form-control-sm" style="width:200px" placeholder="Search ...">
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover table-sm small mb-0">
              <thead class="table-light">
                <tr><th>Date ⇅</th><th>Subject Type ⇅</th><th>Action ⇅</th><th>By ⇅</th><th>Note ⇅</th></tr>
              </thead>
              <tbody id="activity-tbody"></tbody>
            </table>
          </div>
        </div>
      </main>
      <footer class="app-footer">LightSuite - V6.4 | Copyright © 2026 All rights reserved.</footer>
    </div>
  </div>

  <script>
    const activityData = [
      { date:"22-02-2026 06:26 AM", subjectType:"Sell", action:"Added", by:"Dr Ishmael Nyirenda", note:{ invoice:"3731", status:"Final", total:"MWK 1,500.00", paymentStatus:"Paid" }},
      { date:"22-02-2026 06:21 AM", subjectType:"User", action:"Login", by:"Dr Ishmael Nyirenda", note:null },
      { date:"21-02-2026 08:06 PM", subjectType:"Sell", action:"Added", by:"Pharmacist", note:{ invoice:"3730", status:"Final", total:"MWK 900.00", paymentStatus:"Paid" }},
      { date:"21-02-2026 08:04 PM", subjectType:"Sell", action:"Added", by:"Pharmacist", note:{ invoice:"3729", status:"Final", total:"MWK 1,000.00", paymentStatus:"Paid" }},
      { date:"21-02-2026 08:04 PM", subjectType:"Sell", action:"Added", by:"Pharmacist", note:{ invoice:"3728", status:"Final", total:"MWK 1,600.00", paymentStatus:"Paid" }},
    ];
    const tbody = document.getElementById('activity-tbody');
    activityData.forEach(item => {
      let noteHtml = '';
      if (item.note) {
        noteHtml = `<div>Invoice No.: ${item.note.invoice}</div>
          <div>Status: <span class="badge-final">${item.note.status}</span></div>
          <div>Total: <span class="badge-paid">${item.note.total}</span></div>
          <div>Payment Status: <span class="badge-paid">${item.note.paymentStatus}</span></div>`;
      }
      tbody.innerHTML += `<tr><td>${item.date}</td><td>${item.subjectType}</td><td>${item.action}</td><td>${item.by}</td><td>${noteHtml}</td></tr>`;
    });
  </script>

@endsection
