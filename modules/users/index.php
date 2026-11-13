<?php
/**
 * Modul Kelola Pengguna Sistem WMS CWE (Khusus Admin)
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_admin();

$page_title = 'Kelola Pengguna Sistem';
$current_page = 'users';

// Tambah & Edit Pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $role = $_POST['role'] ?? 'operator';
    $telepon = trim($_POST['telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($nama) || empty($username)) {
        set_flash('error', 'Nama lengkap dan nama pengguna (username) wajib diisi.');
    } else {
        try {
            if ($action === 'create') {
                if (empty($password)) {
                    set_flash('error', 'Kata sandi awal wajib diisi untuk pengguna baru.');
                } else {
                    $cek = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $cek->execute([$username]);
                    if ($cek->fetch()) {
                        set_flash('error', "Username '{$username}' sudah digunakan.");
                    } else {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $ins = $pdo->prepare("INSERT INTO users (nama, username, password, role, telepon, email) VALUES (?, ?, ?, ?, ?, ?)");
                        $ins->execute([$nama, $username, $hash, $role, $telepon, $email]);
                        set_flash('success', "Pengguna '{$nama}' ({$role}) berhasil didaftarkan.");
                    }
                }
            } elseif ($action === 'update' && $id > 0) {
                $cek = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $cek->execute([$username, $id]);
                if ($cek->fetch()) {
                    set_flash('error', "Username '{$username}' sudah digunakan pengguna lain.");
                } else {
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $upd = $pdo->prepare("UPDATE users SET nama = ?, username = ?, password = ?, role = ?, telepon = ?, email = ? WHERE id = ?");
                        $upd->execute([$nama, $username, $hash, $role, $telepon, $email, $id]);
                    } else {
                        $upd = $pdo->prepare("UPDATE users SET nama = ?, username = ?, role = ?, telepon = ?, email = ? WHERE id = ?");
                        $upd->execute([$nama, $username, $role, $telepon, $email, $id]);
                    }
                    set_flash('success', "Data pengguna '{$nama}' berhasil diperbarui.");
                }
            }
        } catch (PDOException $e) {
            set_flash('error', 'Gagal memproses data pengguna: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_URL . 'modules/users/index.php');
    exit;
}

// Hapus Pengguna
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            set_flash('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        } else {
            // Cek apakah user sudah pernah mencatat transaksi
            $cek_bm = $pdo->prepare("SELECT COUNT(*) FROM barang_masuk WHERE user_id = ?");
            $cek_bm->execute([$id]);
            $count_bm = $cek_bm->fetchColumn();

            $cek_bk = $pdo->prepare("SELECT COUNT(*) FROM barang_keluar WHERE user_id = ?");
            $cek_bk->execute([$id]);
            $count_bk = $cek_bk->fetchColumn();

            if ($count_bm > 0 || $count_bk > 0) {
                set_flash('error', "Akun tidak dapat dihapus karena tercatat dalam histori transaksi audit ({$count_bm} masuk, {$count_bk} keluar). Anda dapat mengubah peran atau password jika ingin menonaktifkannya.");
            } else {
                $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $del->execute([$id]);
                set_flash('success', "Pengguna berhasil dihapus.");
            }
        }
    }
    header('Location: ' . BASE_URL . 'modules/users/index.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY role ASC, nama ASC");
$user_list = $stmt->fetchAll();

require_once __DIR__ . '/../../layout/header.php';
require_once __DIR__ . '/../../layout/sidebar.php';
?>

<div id="content-wrapper">
    <?php require_once __DIR__ . '/../../layout/navbar.php'; ?>

    <main class="container-fluid px-4 py-4">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-people-fill text-success me-2"></i> Manajemen Hak Akses Pengguna</h4>
                <p class="text-muted small mb-0">Kelola akun staf Administrator dan Operator pergudangan Politeknik Kelapa Sawit CWE.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <button type="button" class="btn btn-cwe-primary rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                    <i class="bi bi-person-plus-fill me-1"></i> Tambah Pengguna Baru
                </button>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Daftar Akun Petugas WMS</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle datatable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Hak Akses (Role)</th>
                                <th>Kontak Telepon / Email</th>
                                <th>Terdaftar Sejak</th>
                                <th class="text-end" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($user_list as $u): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($u['nama']) ?></div>
                                        <?php if ($u['id'] === (int)$_SESSION['user_id']): ?>
                                            <span class="badge bg-success-subtle text-success" style="font-size: 0.65rem;">Akun Anda</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-monospace fw-semibold text-secondary"><?= htmlspecialchars($u['username']) ?></td>
                                    <td>
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge bg-primary px-3 py-1 rounded-pill"><i class="bi bi-shield-lock-fill me-1"></i> Administrator</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-3 py-1 rounded-pill"><i class="bi bi-person-badge me-1"></i> Operator</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <div><i class="bi bi-telephone text-muted me-1"></i> <?= htmlspecialchars($u['telepon'] ?? '-') ?></div>
                                        <div><i class="bi bi-envelope text-muted me-1"></i> <?= htmlspecialchars($u['email'] ?? '-') ?></div>
                                    </td>
                                    <td class="small text-muted"><?= format_tanggal($u['created_at']) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-circle me-1 btn-edit-user"
                                                data-id="<?= $u['id'] ?>"
                                                data-nama="<?= htmlspecialchars($u['nama']) ?>"
                                                data-username="<?= htmlspecialchars($u['username']) ?>"
                                                data-role="<?= $u['role'] ?>"
                                                data-telepon="<?= htmlspecialchars($u['telepon'] ?? '') ?>"
                                                data-email="<?= htmlspecialchars($u['email'] ?? '') ?>"
                                                title="Ubah Pengguna">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                            <a href="<?= BASE_URL ?>modules/users/index.php?action=delete&id=<?= $u['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger rounded-circle btn-delete-confirm" 
                                               data-name="Akun <?= htmlspecialchars($u['nama']) ?>" 
                                               title="Hapus Akun">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

<!-- Modal Tambah User -->
<div class="modal fade" id="modalTambahUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill text-success me-2"></i> Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Lengkap Petugas <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" placeholder="Nama staf gudang" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control font-monospace" placeholder="username" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Hak Akses (Role) <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="operator">Operator Gudang</option>
                            <option value="admin">Administrator Penuh</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Kata Sandi Awal <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold small">No. HP / WA</label>
                        <input type="text" name="telepon" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Email Kampus CWE</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@cwe.ac.id">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-save me-1"></i> Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_user_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i> Edit Akun Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Lengkap Petugas <span class="text-danger">*</span></label>
                    <input type="text" name="nama" id="edit_user_nama" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="edit_user_username" class="form-control font-monospace" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Hak Akses (Role) <span class="text-danger">*</span></label>
                        <select name="role" id="edit_user_role" class="form-select" required>
                            <option value="operator">Operator Gudang</option>
                            <option value="admin">Administrator Penuh</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Reset Kata Sandi</label>
                    <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah kata sandi">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold small">No. HP / WA</label>
                        <input type="text" name="telepon" id="edit_user_telepon" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Email Kampus CWE</label>
                        <input type="email" name="email" id="edit_user_email" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-save me-1"></i> Perbarui Akun</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.btn-edit-user');
    const modalEdit = new bootstrap.Modal(document.getElementById('modalEditUser'));

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_user_id').value = this.getAttribute('data-id');
            document.getElementById('edit_user_nama').value = this.getAttribute('data-nama');
            document.getElementById('edit_user_username').value = this.getAttribute('data-username');
            document.getElementById('edit_user_role').value = this.getAttribute('data-role');
            document.getElementById('edit_user_telepon').value = this.getAttribute('data-telepon');
            document.getElementById('edit_user_email').value = this.getAttribute('data-email');
            modalEdit.show();
        });
    });
});
</script>

