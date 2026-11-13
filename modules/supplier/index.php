<?php
/**
 * Modul Master Pemasok / Supplier WMS CWE
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_admin();

$page_title = 'Kelola Pemasok (Supplier)';
$current_page = 'supplier';

// Tambah & Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $nama_supplier = trim($_POST['nama_supplier'] ?? '');
    $kontak_person = trim($_POST['kontak_person'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if (empty($nama_supplier)) {
        set_flash('error', 'Nama Supplier wajib diisi.');
    } else {
        try {
            if ($action === 'create') {
                $ins = $pdo->prepare("INSERT INTO supplier (nama_supplier, kontak_person, telepon, email, alamat) VALUES (?, ?, ?, ?, ?)");
                $ins->execute([$nama_supplier, $kontak_person, $telepon, $email, $alamat]);
                set_flash('success', "Pemasok '{$nama_supplier}' berhasil ditambahkan.");
            } elseif ($action === 'update' && $id > 0) {
                $upd = $pdo->prepare("UPDATE supplier SET nama_supplier = ?, kontak_person = ?, telepon = ?, email = ?, alamat = ? WHERE id = ?");
                $upd->execute([$nama_supplier, $kontak_person, $telepon, $email, $alamat, $id]);
                set_flash('success', "Data pemasok '{$nama_supplier}' berhasil diperbarui.");
            }
        } catch (PDOException $e) {
            set_flash('error', 'Gagal memproses supplier: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_URL . 'modules/supplier/index.php');
    exit;
}

// Hapus
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $del = $pdo->prepare("DELETE FROM supplier WHERE id = ?");
        $del->execute([$id]);
        set_flash('success', "Pemasok berhasil dihapus.");
    }
    header('Location: ' . BASE_URL . 'modules/supplier/index.php');
    exit;
}

$stmt = $pdo->query("
    SELECT s.*, COUNT(bm.id) as total_transaksi
    FROM supplier s
    LEFT JOIN barang_masuk bm ON s.id = bm.id_supplier
    GROUP BY s.id
    ORDER BY s.nama_supplier ASC
");
$supplier_list = $stmt->fetchAll();

require_once __DIR__ . '/../../layout/header.php';
require_once __DIR__ . '/../../layout/sidebar.php';
?>

<div id="content-wrapper">
    <?php require_once __DIR__ . '/../../layout/navbar.php'; ?>

    <main class="container-fluid px-4 py-4">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-truck text-success me-2"></i> Pemasok / Rekanan Gudang (Vendor)</h4>
                <p class="text-muted small mb-0">Kelola data vendor penyedia bibit, pupuk, alat laboratorium, dan suku cadang PKS.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <button type="button" class="btn btn-cwe-primary rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahSupplier">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Pemasok
                </button>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Daftar Pemasok Resmi</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle datatable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Pemasok / PT</th>
                                <th>Kontak Person</th>
                                <th>Telepon / Email</th>
                                <th>Alamat</th>
                                <th class="text-center">Total Pasokan</th>
                                <th class="text-end" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($supplier_list as $s): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($s['nama_supplier']) ?></td>
                                    <td><?= htmlspecialchars($s['kontak_person'] ?? '-') ?></td>
                                    <td class="small">
                                        <div><i class="bi bi-telephone text-muted me-1"></i> <?= htmlspecialchars($s['telepon'] ?? '-') ?></div>
                                        <div><i class="bi bi-envelope text-muted me-1"></i> <?= htmlspecialchars($s['email'] ?? '-') ?></div>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($s['alamat'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info"><?= $s['total_transaksi'] ?> Transaksi</span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-circle me-1 btn-edit-sup"
                                                data-id="<?= $s['id'] ?>"
                                                data-nama="<?= htmlspecialchars($s['nama_supplier']) ?>"
                                                data-kontak="<?= htmlspecialchars($s['kontak_person'] ?? '') ?>"
                                                data-telepon="<?= htmlspecialchars($s['telepon'] ?? '') ?>"
                                                data-email="<?= htmlspecialchars($s['email'] ?? '') ?>"
                                                data-alamat="<?= htmlspecialchars($s['alamat'] ?? '') ?>"
                                                title="Ubah Supplier">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="<?= BASE_URL ?>modules/supplier/index.php?action=delete&id=<?= $s['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger rounded-circle btn-delete-confirm" 
                                           data-name="Pemasok <?= htmlspecialchars($s['nama_supplier']) ?>" 
                                           title="Hapus Supplier">
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

<!-- Modal Tambah Supplier -->
<div class="modal fade" id="modalTambahSupplier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-success me-2"></i> Tambah Pemasok Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Pemasok / Perusahaan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_supplier" class="form-control" placeholder="Contoh: PT Sawit Jaya Abadi" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Kontak Person (PIC)</label>
                    <input type="text" name="kontak_person" class="form-control" placeholder="Nama sales/perwakilan">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Nomor Telepon / WA</label>
                        <input type="text" name="telepon" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="sales@vendor.com">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Alamat Kantor / Gudang Vendor</label>
                    <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-save me-1"></i> Simpan Pemasok</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Supplier -->
<div class="modal fade" id="modalEditSupplier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_sup_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i> Edit Pemasok</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Pemasok / Perusahaan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_supplier" id="edit_sup_nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Kontak Person (PIC)</label>
                    <input type="text" name="kontak_person" id="edit_sup_kontak" class="form-control">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Nomor Telepon / WA</label>
                        <input type="text" name="telepon" id="edit_sup_telepon" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Email</label>
                        <input type="email" name="email" id="edit_sup_email" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Alamat Kantor / Gudang Vendor</label>
                    <textarea name="alamat" id="edit_sup_alamat" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-save me-1"></i> Perbarui Pemasok</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.btn-edit-sup');
    const modalEdit = new bootstrap.Modal(document.getElementById('modalEditSupplier'));

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_sup_id').value = this.getAttribute('data-id');
            document.getElementById('edit_sup_nama').value = this.getAttribute('data-nama');
            document.getElementById('edit_sup_kontak').value = this.getAttribute('data-kontak');
            document.getElementById('edit_sup_telepon').value = this.getAttribute('data-telepon');
            document.getElementById('edit_sup_email').value = this.getAttribute('data-email');
            document.getElementById('edit_sup_alamat').value = this.getAttribute('data-alamat');
            modalEdit.show();
        });
    });
});
</script>

