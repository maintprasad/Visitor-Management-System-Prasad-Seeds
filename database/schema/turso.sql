-- VSM Pasuruan — skema Turso (libSQL / SQLite) untuk deploy Vercel
-- Kolom identik dengan database/schema/mysql.sql; hanya tipe data & sintaks view yang beda.
-- Idempotent: aman dijalankan berulang (php bin/migrate.php dengan DB_DRIVER=turso).

CREATE TABLE IF NOT EXISTS units (
  id     TEXT NOT NULL PRIMARY KEY,
  name   TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'ACTIVE'
);

CREATE TABLE IF NOT EXISTS users (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  username      TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  name          TEXT NOT NULL,
  role          TEXT NOT NULL DEFAULT 'security',
  unit_id       TEXT NULL REFERENCES units(id),
  status        TEXT NOT NULL DEFAULT 'ACTIVE',
  created_at    TEXT NOT NULL,
  created_by    TEXT NULL,
  updated_at    TEXT NULL,
  last_login_at TEXT NULL
);

CREATE TABLE IF NOT EXISTS visitors (
  id               TEXT NOT NULL PRIMARY KEY,
  unit_id          TEXT NOT NULL REFERENCES units(id),
  visitor_type     TEXT NOT NULL,
  nama             TEXT NOT NULL,
  company          TEXT NULL,
  phone            TEXT NULL,
  identity         TEXT NULL,
  purpose          TEXT NULL,
  host             TEXT NULL,
  dept             TEXT NULL,
  notes            TEXT NULL,
  status           TEXT NOT NULL DEFAULT 'Waiting Approval',
  check_in         TEXT NOT NULL,
  check_out        TEXT NULL,
  duration         REAL NULL,
  area             TEXT NULL,
  jenis_pekerjaan  TEXT NULL,
  personil         INTEGER NULL,
  spk              TEXT NULL,
  start_date       TEXT NULL,
  end_date         TEXT NULL,
  equip_list       TEXT NULL,
  sla_start        TEXT NULL,
  sla_duration     REAL NULL,
  plat             TEXT NULL,
  jenis_kendaraan  TEXT NULL,   -- Driver: Wing Box | Container | Colt Diesel | Pick Up (Driver: company = Nama Transporter)
  cargo            TEXT NULL,
  driver_approved  INTEGER NOT NULL DEFAULT 0,
  approved_at      TEXT NULL,
  gate_pass_out    TEXT NULL,
  nik              TEXT NULL,
  jabatan          TEXT NULL,
  audit_type       TEXT NULL,
  scope            TEXT NULL,
  surat_tugas      TEXT NULL,
  nama_personil    TEXT NULL,
  created_at       TEXT NOT NULL,
  updated_at       TEXT NOT NULL,
  user_name        TEXT NULL,
  source           TEXT NULL
);
CREATE INDEX IF NOT EXISTS idx_visitors_unit_status ON visitors (unit_id, status);
CREATE INDEX IF NOT EXISTS idx_visitors_unit_created ON visitors (unit_id, created_at);

CREATE TABLE IF NOT EXISTS equipment (
  id             TEXT NOT NULL PRIMARY KEY,
  unit_id        TEXT NOT NULL REFERENCES units(id),
  nama           TEXT NOT NULL,
  serial         TEXT NULL,
  owner          TEXT NULL,
  owner_company  TEXT NULL,
  visitor_id     TEXT NULL,
  visitor_type   TEXT NULL,
  gate_pass      TEXT NULL,
  item_condition TEXT NULL,
  qty            INTEGER NOT NULL DEFAULT 1,
  status         TEXT NOT NULL DEFAULT 'Inside',
  notes          TEXT NULL,
  date_in        TEXT NULL,
  date_out       TEXT NULL,
  source         TEXT NULL,
  user_name      TEXT NULL,
  updated_at     TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_equipment_unit_status ON equipment (unit_id, status);
CREATE INDEX IF NOT EXISTS idx_equipment_visitor ON equipment (visitor_id);

CREATE TABLE IF NOT EXISTS absen_kontraktor (
  id             TEXT NOT NULL PRIMARY KEY,
  unit_id        TEXT NOT NULL REFERENCES units(id),
  tanggal        TEXT NOT NULL,
  nama_pt        TEXT NOT NULL,
  supervisi      TEXT NULL,
  visitor_id     TEXT NULL,
  source         TEXT NULL,
  jumlah_pekerja INTEGER NOT NULL DEFAULT 0,
  nama_pekerja   TEXT NULL,
  jumlah_alat    INTEGER NOT NULL DEFAULT 0,
  daftar_alat    TEXT NULL,
  user_name      TEXT NULL,
  updated_at     TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_absen_unit_tanggal ON absen_kontraktor (unit_id, tanggal);

CREATE TABLE IF NOT EXISTS logs (
  id        INTEGER PRIMARY KEY AUTOINCREMENT,
  unit_id   TEXT NOT NULL REFERENCES units(id),
  ts        TEXT NOT NULL,
  action    TEXT NOT NULL,
  entity    TEXT NULL,
  ref_id    TEXT NULL,
  detail    TEXT NULL,
  user_name TEXT NULL
);
CREATE INDEX IF NOT EXISTS idx_logs_unit_ts ON logs (unit_id, ts);

DROP VIEW IF EXISTS contractors;
CREATE VIEW contractors AS
SELECT v.id AS ID, v.id AS VisitorID, v.unit_id AS UnitID, v.nama AS Nama, v.company AS Company,
       v.phone AS Phone, v.identity AS Identity, v.area AS Area, v.jenis_pekerjaan AS JenisPekerjaan,
       v.spk AS SPK, v.start_date AS StartDate, v.end_date AS EndDate, v.host AS Host, v.dept AS Dept,
       v.notes AS Notes, v.status AS Status, v.check_in AS CheckIn, v.check_out AS CheckOut,
       v.duration AS Duration, v.sla_start AS SLAStart, v.sla_duration AS SLADuration,
       v.personil AS JumlahPersonil, v.nama_personil AS NamaPersonil,
       (SELECT COUNT(*) FROM equipment e WHERE e.visitor_id = v.id) AS JumlahEquipment,
       v.equip_list AS DaftarEquipment, v.created_at AS CreatedAt, v.user_name AS User
FROM visitors v WHERE v.visitor_type = 'Contractor';

DROP VIEW IF EXISTS drivers;
CREATE VIEW drivers AS
SELECT v.id AS ID, v.id AS VisitorID, v.unit_id AS UnitID, v.nama AS Nama, v.company AS Company,
       v.company AS Transporter, v.phone AS Phone, v.identity AS Identity, v.plat AS Plat, v.jenis_kendaraan AS JenisKendaraan,
       v.cargo AS Cargo, v.purpose AS Tujuan, v.host AS Host, v.dept AS Dept, v.notes AS Notes,
       v.status AS Status, v.check_in AS CheckIn, v.check_out AS CheckOut, v.duration AS Duration,
       v.driver_approved AS DriverApproved, v.approved_at AS ApprovedAt, v.gate_pass_out AS GatePassOut,
       v.created_at AS CreatedAt, v.user_name AS User
FROM visitors v WHERE v.visitor_type = 'Driver';
