<?php
// PERBAIKAN: Memulai sesi hanya jika belum ada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'includes/db.php';

// Pastikan koneksi DB berhasil sebelum melanjutkan
if (!isset($conn)) {
    die("Koneksi ke database gagal. Mohon periksa file 'includes/db.php'.");
}

$user_data = null;
$is_logged_in = isset($_SESSION['user_id']);
$message = '';
$message_type = 'error';

// Proses form (login, register, update)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'register') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['user_name'] = $name;
            header("Location: index.php?status=registered");
            exit();
        } else {
            $message = "Registrasi gagal. Email mungkin sudah terdaftar.";
        }
    } elseif ($action == 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result && password_verify($password, $result['password'])) {
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['user_name'] = $result['name'];
            header("Location: index.php?status=loggedin");
            exit();
        } else {
            $message = "Email atau password salah.";
        }
    } elseif ($action == 'update' && $is_logged_in) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $email, $phone, $address, $_SESSION['user_id']);
        if($stmt->execute()){
            $_SESSION['user_name'] = $name; // Update nama di sesi juga
            $message = "Profil berhasil diperbarui!";
            $message_type = 'success';
        } else {
            $message = "Gagal memperbarui profil. Email mungkin sudah digunakan pengguna lain.";
        }
    }
}

// Jika pengguna sudah login, ambil datanya
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT id, name, email, phone, address FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
}

include_once 'includes/header.php';
?>
<link rel="stylesheet" href="user_profile_style.css">

<main>
    <div class="container profile-page-container">
        <?php if ($is_logged_in): ?>
            <div class="profile-grid">
                <aside class="profile-sidebar">
                    <div class="profile-welcome">
                        <div class="avatar"><?php echo strtoupper(substr($user_data['name'], 0, 1)); ?></div>
                        <h3><?php echo htmlspecialchars($user_data['name']); ?></h3>
                        <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                    </div>
                    <nav class="profile-nav">
                        <ul>
                            <li><a href="#" class="active"><i class="fas fa-user-edit"></i> Edit Profil</a></li>
                            <li><a href="#"><i class="fas fa-history"></i> Riwayat Pesanan</a></li>
                            <li><a href="logout_user.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </nav>
                </aside>
                <main class="profile-main">
                    <h2>Edit Profil</h2>
                    <p>Perbarui informasi akun Anda di sini.</p>
                     <?php if ($message): ?>
                        <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <form action="user_profile.php" method="POST">
                        <input type="hidden" name="action" value="update">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Nama Lengkap</label>
                                <input type="text" id="name" class="form-control" name="name" value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Alamat Email</label>
                                <input type="email" id="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="phone">Nomor HP</label>
                            <input type="tel" id="phone" class="form-control" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="address">Alamat Lengkap</label>
                            <textarea id="address" class="form-control" name="address" rows="5" placeholder="Masukkan alamat untuk pengiriman otomatis"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn-save-profile">Simpan Perubahan</button>
                    </form>
                </main>
            </div>
        <?php else: // Tampilan untuk pengguna yang belum login ?>
            <div class="auth-container">
                <div class="tabs">
                    <button class="tab-link active" onclick="openTab(event, 'login')">Login</button>
                    <button class="tab-link" onclick="openTab(event, 'register')">Daftar</button>
                </div>
                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <div id="login" class="tab-content active">
                      <h2>Selamat Datang Kembali</h2>
                      <form action="user_profile.php" method="POST">
                          <input type="hidden" name="action" value="login">
                          <div class="form-group"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                          <div class="form-group"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
                          <button type="submit" class="btn-checkout">Login</button>
                      </form>
                      <div style="margin-top: 15px; font-size: 0.9em;">
                          <a href="forgot_password.php">Lupa Password?</a>
                      </div>
                  </div>
                <div id="register" class="tab-content">
                    <h2>Buat Akun Baru</h2>
                    <form action="user_profile.php" method="POST">
                        <input type="hidden" name="action" value="register">
                        <div class="form-group"><input type="text" name="name" class="form-control" placeholder="Nama Lengkap" required></div>
                        <div class="form-group"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                        <div class="form-group"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
                        <button type="submit" class="btn-checkout">Daftar</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
<script>
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tab-link");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }
</script>
<?php include_once 'includes/footer.php'; ?>