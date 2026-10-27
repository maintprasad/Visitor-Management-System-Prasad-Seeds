-- VSM Pasuruan — skema MySQL / MariaDB (localhost, XAMPP)
-- Berdasarkan database_vsm.xlsx (sheet Visitors, Contractors, Equipment,
-- AbsenKontraktor, Drivers, Logs) + pemisahan data per UNIT (U1 / U2).
-- Waktu disimpan sebagai teks ISO-8601 UTC (sama persis dengan spreadsheet).
-- Idempotent: aman dijalankan berulang (php bin/migrate.php).

CREATE TABLE IF NOT EXISTS units (
  id          VARCHAR(8)   NOT NULL PRIMARY KEY,
  name        VARCHAR(60)  NOT NULL,
  status      VARCHAR(12)  NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id            INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(60)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name          VARCHAR(100) NOT NULL,
  role          VARCHAR(12)  NOT NULL DEFAULT 'security',  -- security | admin
  unit_id       VARCHAR(8)   NULL,                         -- wajib untuk security
  status        VARCHAR(12)  NOT NULL DEFAULT 'ACTIVE',
  created_at    VARCHAR(30)  NOT NULL,
  created_by    VARCHAR(60)  NULL,                         -- username admin pembuat akun
  updated_at    VARCHAR(30)  NULL,
  last_login_at VARCHAR(30)  NULL,
  CONSTRAINT fk_users_unit FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS visitors (
  id               VARCHAR(40)  NOT NULL PRIMARY KEY,
  unit_id          VARCHAR(8)   NOT NULL,
  visitor_type     VARCHAR(20)  NOT NULL,   -- Guest | Contractor | Driver | Employee Test | Auditor
  nama             VARCHAR(255) NOT NULL,
  company          VARCHAR(160) NULL,
  phone            VARCHAR(40)  NULL,
  identity         VARCHAR(60)  NULL,
  purpose          VARCHAR(255) NULL,
  host             VARCHAR(120) NULL,
  dept             VARCHAR(80)  NULL,
  notes            TEXT         NULL,
  status           VARCHAR(20)  NOT NULL DEFAULT 'Waiting Approval',
  check_in         VARCHAR(30)  NOT NULL,
  check_out        VARCHAR(30)  NULL,
  duration         DOUBLE       NULL,       -- hari (pecahan), sama dgn kolom Duration Excel
  area             VARCHAR(80)  NULL,
  jenis_pekerjaan  VARCHAR(120) NULL,
  personil         INT          NULL,
  spk              VARCHAR(80)  NULL,
  start_date       VARCHAR(30)  NULL,
  end_date         VARCHAR(30)  NULL,
  equip_list       TEXT         NULL,       -- "Alat A;Alat B(3)"
  sla_start        VARCHAR(30)  NULL,
  sla_duration     DOUBLE       NULL,
  plat             VARCHAR(30)  NULL,
  jenis_kendaraan  VARCHAR(60)  NULL,   -- Driver: Wing Box | Container | Colt Diesel | Pick Up (Driver: company = Nama Transporter)
  cargo            TEXT         NULL,
  driver_approved  TINYINT(1)   NOT NULL DEFAULT 0,
  approved_at      VARCHAR(30)  NULL,
  gate_pass_out    VARCHAR(40)  NULL,
  nik              VARCHAR(40)  NULL,
  jabatan          VARCHAR(80)  NULL,
  audit_type       VARCHAR(80)  NULL,
  scope            VARCHAR(255) NULL,
  surat_tugas      VARCHAR(120) NULL,
  nama_personil    TEXT         NULL,       -- satu nama per baris
  created_at       VARCHAR(30)  NOT NULL,
  updated_at       VARCHAR(30)  NOT NULL,
  user_name        VARCHAR(100) NULL,
  source           VARCHAR(30)  NULL,
  KEY idx_visitors_unit_status (unit_id, status),
  KEY idx_visitors_unit_created (unit_id, created_at),
  CONSTRAINT fk_visitors_unit FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS equipment (
  id             VARCHAR(40)  NOT NULL PRIMARY KEY,
  unit_id        VARCHAR(8)   NOT NULL,
  nama           VARCHAR(255) NOT NULL,
  serial         VARCHAR(120) NULL,
  owner          VARCHAR(255) NULL,
  owner_company  VARCHAR(160) NULL,
  visitor_id     VARCHAR(40)  NULL,
  visitor_type   VARCHAR(20)  NULL,
  gate_pass      VARCHAR(40)  NULL,
  item_condition VARCHAR(40)  NULL,
  qty            INT          NOT NULL DEFAULT 1,
  status         VARCHAR(20)  NOT NULL DEFAULT 'Inside',   -- Inside | Outside | Pending Approval
  notes          TEXT         NULL,
  date_in        VARCHAR(30)  NULL,
  date_out       VARCHAR(30)  NULL,
  source         VARCHAR(40)  NULL,
  user_name      VARCHAR(100) NULL,
  updated_at     VARCHAR(30)  NOT NULL,
  KEY idx_equipment_unit_status (unit_id, status),
  KEY idx_equipment_visitor (visitor_id),
  CONSTRAINT fk_equipment_unit FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS absen_kontraktor (
  id             VARCHAR(40)  NOT NULL PRIMARY KEY,
  unit_id        VARCHAR(8)   NOT NULL,
  tanggal        VARCHAR(30)  NOT NULL,
  nama_pt        VARCHAR(160) NOT NULL,
  supervisi      VARCHAR(120) NULL,
  visitor_id     VARCHAR(40)  NULL,
  source         VARCHAR(30)  NULL,        -- AUTO_FROM_CHECKIN | SELF_CHECKIN | MANUAL
  jumlah_pekerja INT          NOT NULL DEFAULT 0,
  nama_pekerja   TEXT         NULL,        -- satu nama per baris
  jumlah_alat    INT          NOT NULL DEFAULT 0,
  daftar_alat    TEXT         NULL,        -- "Alat (Qty: 2)" per baris
  user_name      VARCHAR(100) NULL,
  updated_at     VARCHAR(30)  NOT NULL,
  KEY idx_absen_unit_tanggal (unit_id, tanggal),
  CONSTRAINT fk_absen_unit FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS logs (
  id        BIGINT       NOT NULL AUTO_INCREMENT PRIMARY KEY,
  unit_id   VARCHAR(8)   NOT NULL,
  ts        VARCHAR(30)  NOT NULL,
  action    VARCHAR(40)  NOT NULL,
  entity    VARCHAR(40)  NULL,
  ref_id    VARCHAR(60)  NULL,
  detail    TEXT         NULL,
  user_name VARCHAR(100) NULL,
  KEY idx_logs_unit_ts (unit_id, ts),
  CONSTRAINT fk_logs_unit FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sheet "Contractors" & "Drivers" di Excel adalah turunan dari Visitors,
-- jadi dibuat sebagai VIEW agar tidak ada data ganda yang bisa tidak sinkron.
CREATE OR REPLACE VIEW contractors AS
SELECT v.id AS ID, v.id AS VisitorID, v.unit_id AS UnitID, v.nama AS Nama, v.company AS Company,
       v.phone AS Phone, v.identity AS Identity, v.area AS Area, v.jenis_pekerjaan AS JenisPekerjaan,
       v.spk AS SPK, v.start_date AS StartDate, v.end_date AS EndDate, v.host AS Host, v.dept AS Dept,
       v.notes AS Notes, v.status AS Status, v.check_in AS CheckIn, v.check_out AS CheckOut,
       v.duration AS Duration, v.sla_start AS SLAStart, v.sla_duration AS SLADuration,
       v.personil AS JumlahPersonil, v.nama_personil AS NamaPersonil,
       (SELECT COUNT(*) FROM equipment e WHERE e.visitor_id = v.id) AS JumlahEquipment,
       v.equip_list AS DaftarEquipment, v.created_at AS CreatedAt, v.user_name AS User
FROM visitors v WHERE v.visitor_type = 'Contractor';

CREATE OR REPLACE VIEW drivers AS
SELECT v.id AS ID, v.id AS VisitorID, v.unit_id AS UnitID, v.nama AS Nama, v.company AS Company,
       v.company AS Transporter, v.phone AS Phone, v.identity AS Identity, v.plat AS Plat, v.jenis_kendaraan AS JenisKendaraan,
       v.cargo AS Cargo, v.purpose AS Tujuan, v.host AS Host, v.dept AS Dept, v.notes AS Notes,
       v.status AS Status, v.check_in AS CheckIn, v.check_out AS CheckOut, v.duration AS Duration,
       v.driver_approved AS DriverApproved, v.approved_at AS ApprovedAt, v.gate_pass_out AS GatePassOut,
       v.created_at AS CreatedAt, v.user_name AS User
FROM visitors v WHERE v.visitor_type = 'Driver';
