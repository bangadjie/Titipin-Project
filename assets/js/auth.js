// Fungsi lempar data register
async function registerUser(event) {
  event.preventDefault();

  const formData = {
    nim: document.getElementById("nim").value,
    username: document.getElementById("username").value,
    nama_lengkap: document.getElementById("nama_lengkap").value,
    email: document.getElementById("email").value,
    password: document.getElementById("password").value,
    konfirm_password: document.getElementById("konfirm_password").value,
    jurusan: document.getElementById("jurusan").value,
    kelas: document.getElementById("kelas").value,
    no_telepon: document.getElementById("no_telepon").value,
  };

  const respons = await fetch("../api/auth/register.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(formData),
  });

  const hasil = await respons.json();

  if (hasil.status === "sukses") {
    alert("Sukses! " + hasil.message);
    window.location.href = "login.html"; // Pindah goa
  } else {
    alert("Gagal: " + hasil.message);
  }
}

async function loginUser(event) {
  event.preventDefault();

  const formData = {
    email: document.getElementById("email").value,
    password: document.getElementById("password").value,
  };

  const respons = await fetch("../api/auth/login.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(formData),
  });

  const hasil = await respons.json();

  if (hasil.status === "sukses") {
    alert("Halo " + hasil.nama + "! You adalah " + hasil.role);
    // Pisah jalan Admin dan Mahasiswa
    if (hasil.role === "Admin") {
      window.location.href = "dashboard_admin.html";
    } else {
      window.location.href = "dashboard_mahasiswa.html";
    }
  } else {
    alert("Ugg! Gagal: " + hasil.message);
  }
}

async function logoutUser(event) {
  if (event) event.preventDefault(); // Mencegah sifat bawaan tombol/link

  try {
    const respons = await fetch("../api/auth/logout.php", {
      method: "POST", // Bisa juga pakai GET, tapi POST lebih aman untuk mengubah state server
    });

    const hasil = await respons.json();

    if (hasil.status === "sukses") {
      alert(hasil.message);
      // Lempar kembali ke halaman login atau halaman utama (index)
      window.location.href = "login.html";
    } else {
      alert("Gagal keluar: " + hasil.message);
    }
  } catch (error) {
    alert("Kesalahan jaringan saat mencoba keluar: " + error);
  }
}
