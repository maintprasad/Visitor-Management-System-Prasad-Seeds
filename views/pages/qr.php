    <!-- ── QR SYSTEM ── -->
    <div id="page-qr" style="display:none">
      <div class="row2">
        <div class="card">
          <div class="card-title"><i class="ti ti-qrcode"></i> Generate QR Code</div>
          <div class="form-group mb16">
            <label>Select Visitor</label>
            <select id="qr-visitor-select" onchange="showQR()">
              <option value="">— Select visitor —</option>
            </select>
          </div>
          <div class="qr-box" id="qr-box">
            <div class="qr-img" id="qr-img-wrap">
              <i class="ti ti-qrcode" style="font-size:60px;color:var(--border)"></i>
            </div>
            <div id="qr-visitor-name" style="font-weight:600;font-size:14px;color:var(--text)">—</div>
            <div id="qr-visitor-id" class="mono" style="margin-top:4px;color:var(--text3)">—</div>
            <div style="margin-top:12px;display:flex;gap:8px;justify-content:center">
              <button class="btn btn-sm" onclick="printQR()"><i class="ti ti-printer"></i> Print</button>
              <button class="btn btn-sm btn-primary" onclick="downloadQR()"><i class="ti ti-download"></i> Download</button>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-title"><i class="ti ti-camera"></i> QR Scanner (Simulate)</div>
          <p style="font-size:13px;color:var(--text3);margin-bottom:16px">Masukkan Visitor ID untuk simulasi scan QR.</p>
          <div class="form-group mb16">
            <label>Visitor ID</label>
            <input type="text" id="qr-scan-input" placeholder="VIS-XXXXXXXX">
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
  <button class="btn btn-primary" onclick="qrCheckin()"><i class="ti ti-login"></i> Check In</button>
  <button class="btn btn-success" onclick="qrCheckout()"><i class="ti ti-logout"></i> Check Out</button>
  <button class="btn" onclick="qrLookup()"><i class="ti ti-search"></i> Lookup</button>
</div>
<div style="margin-top:12px;padding:10px;background:var(--green-light);border-radius:var(--radius-sm);font-size:12px;color:var(--green-text)">
  <i class="ti ti-info-circle"></i> <strong>Security:</strong> Scan QR visitor → masukkan ID di atas → klik <strong>Check Out</strong> untuk proses keluar.
</div>
          <div id="qr-result" class="mt12"></div>
        </div>
      </div>
    </div>
