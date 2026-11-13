<?php
/**
 * Modul Master Departemen / Unit Kampus WMS CWE
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_admin();

$page_title = 'Kelola Departemen / Unit Kampus';
$current_page = 'departemen';

// Tambah & Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $kode_departemen = strtoupper(trim($_POST['kode_departemen'] ?? ''));
    $nama_departemen = trim($_POST['nama_departemen'] ?? '');
    $penanggung_jawab = trim($_POST['penanggung_jawab'] ?? '');
    $kontak = trim($_POST['kontak'] ?? '');

    if (empty($kode_departemen) || empty($nama_departemen)) {
        set_flash('error', 'Kode dan Nama Departemen wajib diisi.');
    } else {
        try {
            if ($action === 'create') {
                $cek = $pdo->prepare("SELECT id FROM departemen WHERE kode_departemen = ?");
                $cek->execute([$kode_departemen]);
                if ($cek->fetch()) {
                    set_flash('error', "Kode departemen '{$kode_departemen}' sudah digunakan.");
                } else {
                    $ins = $pdo->prepare("INSERT INTO departemen (kode_departemen, nama_departemen, penanggung_jawab, kontak) VALUES (?, ?, ?, ?)");
                    $ins->execute([$kode_departemen, $nama_departemen, $penanggung_jawab, $kontak]);
                    set_flash('success', "Departemen '{$nama_departemen}' berhasil ditambahkan.");
                }
            } elseif ($action === 'update' && $id > 0) {
                $upd = $pdo->prepare("UPDATE departemen SET kode_departemen = ?, nama_departemen = ?, penanggung_jawab = ?, kontak = ? WHERE id = ?");
                $upd->execute([$kode_departemen, $nama_departemen, $penanggung_jawab, $kontak, $id]);
                set_flash('success', "Data departemen '{$nama_departemen}' berhasil diperbarui.");
            }
        } catch (PDOException $e) {
            set_flash('error', 'Gagal memproses data departemen: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_URL . 'modules/departemen/index.php');
    exit;
}

// Hapus
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $del = $pdo->prepare("DELETE FROM departemen WHERE id = ?");
        $del->execute([$id]);
        set_flash('success', "Departemen berhasil dihapus.");
    }
    header('Location: ' . BASE_URL . 'modules/departemen/index.php');
    exit;
}

$stmt = $pdo->query("
    SELECT d.*, COUNT(bk.id) as total_pengeluaran
    FROM departemen d
    LEFT JOIN barang_keluar bk ON d.id = bk.id_departemen
    GROUP BY d.id
    ORDER BY d.nama_departemen ASC
");
$dept_list = $stmt->fetchAll();

require_once __DIR__ . '/../../layout/header.php';
require_once __DIR__ . '/../../layout/sidebar.php';
?>

<div id="content-wrapper">
    <?php require_once __DIR__ . '/../../layout/navbar.php'; ?>

    <main class="container-fluid px-4 py-4">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-building-check text-success me-2"></i> Departemen & Unit Kampus CWE</h4>
                <p class="text-muted small mb-0">Daftar unit, laboratorium, workshop pabrik mini, dan program studi pemohon barang keluar.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <button type="button" class="btn btn-cwe-primary rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahDept">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Departemen
                </button>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Daftar Program Studi & Unit Kerja</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle datatable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Kode</th>
                                <th>Nama Unit / Program Studi</th>
                                <th>Penanggung Jawab (PIC)</th>
                                <th>Kontak Telepon</th>
                                <th class="text-center">Total Pengeluaran</th>
                                <th class="text-end" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($dept_list as $d): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><span class="badge bg-secondary-subtle text-secondary font-monospace px-2 py-1"><?= htmlspecialchars($d['kode_departemen']) ?></span></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($d['nama_departemen']) ?></td>
                                    <td><?= htmlspecialchars($d['penanggung_jawab'] ?? '-') ?></td>
                                    <td><i class="bi bi-telephone text-muted me-1"></i> <?= htmlspecialchars($d['kontak'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-danger-subtle text-danger"><?= $d['total_pengeluaran'] ?> Transaksi</span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-circle me-1 btn-edit-dept"
                                                data-id="<?= $d['id'] ?>"
                                                data-kode="<?= htmlspecialchars($d['kode_departemen']) ?>"
                                                data-nama="<?= htmlspecialchars($d['nama_departemen']) ?>"
                                                data-pic="<?= htmlspecialchars($d['penanggung_jawab'] ?? '') ?>"
                                                data-kontak="<?= htmlspecialchars($d['kontak'] ?? '') ?>"
                                                title="Ubah Departemen">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="<?= BASE_URL ?>modules/departemen/index.php?action=delete&id=<?= $d['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger rounded-circle btn-delete-confirm" 
                                           data-name="Departemen <?= htmlspecialchars($d['nama_departemen']) ?>" 
                                           title="Hapus Departemen">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

<!-- Modal Tambah Departemen -->
<div class="modal fade" id="modalTambahDept" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-success me-2"></i> Tambah Departemen / Prodi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Kode Singkat <span class="text-danger">*</span></label>
                    <input type="text" name="kode_departemen" class="form-control font-monospace" placeholder="Contoh: BKS, TPHP, LAB" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Lengkap Unit / Program Studi <span class="text-danger">*</span></label>
                    <input type="text" name="nama_departemen" class="form-control" placeholder="Contoh: Prodi Budidaya Kelapa Sawit" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Penanggung Jawab (PIC / Ka. Prodi / Ka. Lab)</label>
                    <input type="text" name="penanggung_jawab" class="form-control" placeholder="Nama dosen atau pejabat unit">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nomor Telepon / WA</label>
                    <input type="text" name="kontak" class="form-control" placeholder="08xxxxxxxxxx">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-save me-1"></i> Simpan Unit</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Departemen -->
<div class="modal fade" id="modalEditDept" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_dept_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i> Edit Unit / Prodi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Kode Singkat <span class="text-danger">*</span></label>
                    <input type="text" name="kode_departemen" id="edit_dept_kode" class="form-control font-monospace" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Lengkap Unit / Program Studi <span class="text-danger">*</span></label>
                    <input type="text" name="nama_departemen" id="edit_dept_nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Penanggung Jawab (PIC)</label>
                    <input type="text" name="penanggung_jawab" id="edit_dept_pic" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nomor Telepon / WA</label>
                    <input type="text" name="kontak" id="edit_dept_kontak" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-save me-1"></i> Perbarui Unit</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.btn-edit-dept');
    const modalEdit = new bootstrap.Modal(document.getElementById('modalEditDept'));

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_dept_id').value = this.getAttribute('data-id');
            document.getElementById('edit_dept_kode').value = this.getAttribute('data-kode');
            document.getElementById('edit_dept_nama').value = this.getAttribute('data-nama');
            document.getElementById('edit_dept_pic').value = this.getAttribute('data-pic');
            document.getElementById('edit_dept_kontak').value = this.getAttribute('data-kontak');
            modalEdit.show();
        });
    });
});
</script>

