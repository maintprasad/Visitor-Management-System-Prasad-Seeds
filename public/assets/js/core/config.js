// Konfigurasi dari server (views/layout.php): { base, apiBase, units: [{id,name}] }
const VSM = window.VSM_CONFIG || { base: '', apiBase: '/api', units: [] };

// Penyimpanan aman (mode private / storage diblokir tidak boleh merusak aplikasi).
const Store = {
  get(k)    { try { return localStorage.getItem(k); } catch (e) { return null; } },
  set(k, v) { try { localStorage.setItem(k, v); } catch (e) {} },
  del(k)    { try { localStorage.removeItem(k); } catch (e) {} },
};
