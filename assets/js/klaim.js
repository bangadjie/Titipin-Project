// Fungsi mahasiswa mengajukan klaim (ada file bukti_kepemilikan)
async function ajukanKlaim(event) {
  event.preventDefault();

  const formElemen = document.getElementById("formAjukanKlaim");
  const formData = new FormData(formElemen);

  try {
    const respons = await fetch("../api/klaim/create.php", {
      method: "POST",
      body: formData,
    });

    const hasil = await respons.json();

    if (hasil.status === "sukses") {
      alert("Klaim diajukan! Kode Tiket: " + hasil.tiket);
      formElemen.reset();
      ambilDataKlaim();
    } else {
      alert("Gagal: " + hasil.message);
    }
  } catch (error) {
    alert("Terjadi kesalahan jaringan: " + error);
  }
}

// Fungsi mengambil daftar klaim
async function ambilDataKlaim() {
  try {
    const respons = await fetch("../api/klaim/read.php");
    const hasil = await respons.json();

    if (hasil.status === "sukses") {
      console.log("Data Klaim:", hasil.data);
      // Nanti dilooping ke HTML oleh tim frontend
    } else {
      console.error("Gagal ambil data klaim:", hasil.message);
    }
  } catch (error) {
    console.error("Kesalahan jaringan:", error);
  }
}

// Fungsi admin menyetujui atau menolak klaim
async function updateStatusKlaim(id_klaim, status_klaim, admin_id) {
  const payload = {
    id_klaim: id_klaim,
    status_klaim: status_klaim,
    admin_id: admin_id,
  };

  try {
    const respons = await fetch("../api/klaim/update_status.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    });

    const hasil = await respons.json();

    if (hasil.status === "sukses") {
      alert("Status klaim menjadi: " + status_klaim);
      ambilDataKlaim();
    } else {
      alert("Gagal: " + hasil.message);
    }
  } catch (error) {
    alert("Kesalahan jaringan: " + error);
  }
}
