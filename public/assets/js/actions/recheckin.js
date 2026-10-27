function reCheckIn(id) {
  const visitors = DB.visitors();
  const v = visitors.find(x => x.id === id);
  if (!v) return;
  if (!confirm(`Re-check In "${v.name}"?\nTimer akan dilanjutkan dari waktu checkout terakhir (${fmtDateTime(v.checkOut || v.checkIn)})`)) return;

  // Simpan waktu checkout lama sebagai base checkIn baru, agar durasi akumulatif
  const prevCheckOut = v.checkOut || v.checkIn;
  v.status = 'Inside';
  v.checkIn = prevCheckOut;  // lanjutkan dari titik terakhir
  v.checkOut = null;

  // Jika punya equipment yang sudah di-Outside karena checkout, kembalikan ke Inside
  const equipment = DB.equipment();
  let reactivatedCount = 0;
  equipment.forEach(e => {
    if (e.visitorId === id && (e.status === 'Outside')) {
      e.status = 'Inside';
      e.dateOut = null;
      reactivatedCount++;
    }
  });
  DB.setEquipment(equipment);

  DB.setVisitors(visitors);
  markLocalWrite();
  addAudit('RE_CHECKIN', 'VISITOR', `${v.name} — dikembalikan ke Inside${reactivatedCount > 0 ? `, ${reactivatedCount} equipment direaktivasi` : ''}`);
  sendLogToGAS('RE_CHECKIN', 'VISITOR', `${v.name} — re-check in dari ${fmtDateTime(prevCheckOut)}`);
  sendToGAS('UPDATE_STATUS', { sheet: 'Visitors', id, status: 'Inside', user: document.getElementById('sb-uname').textContent });
  toast(`${v.name} kembali Inside — timer dilanjutkan`, 'success');
  checkAlerts();
  renderPage(currentPage);
}
