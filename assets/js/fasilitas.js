// Fungsi mengirim laporan kerusakan (ada file foto)
async function laporFasilitas(event) {
  event.preventDefault();

  const formElemen = document.getElementById("formLaporFasilitas");
  const formData = new FormData(formElemen);

  try {
    const respons = await fetch("../api/fasilitas/create.php", {
      method: "POST",
      body: formData,
    });

    const hasil = await respons.json();

    if (hasil.status === "sukses") {
      alert("Berhasil: " + hasil.message);
      formElemen.reset();
      ambilDataFasilitas(); // Refresh tabel
    } else {
      alert("Gagal: " + hasil.message);
    }
  } catch (error) {
    alert("Terjadi kesalahan jaringan: " + error);
  }
}

// Fungsi mengambil daftar laporan kerusakan
async function ambilDataFasilitas() {
  try {
    const respons = await fetch("../api/fasilitas/read.php");
    const hasil = await respons.json();

    if (hasil.status === "sukses") {
      console.log("Data Fasilitas:", hasil.data);
      // Nanti tim UI/UX tinggal me-looping hasil.data ke dalam tabel HTML
    } else {
      console.error("Gagal ambil data:", hasil.message);
    }
  } catch (error) {
    console.error("Kesalahan jaringan:", error);
  }
}

// Fungsi untuk teknisi/admin memperbarui status perbaikan
async function updateStatusFasilitas(id_kerusakan, status, catatan_teknisi) {
  const payload = {
    id_kerusakan: id_kerusakan,
    status: status,
    catatan_teknisi: catatan_teknisi,
  };

  try {
    const respons = await fetch("../api/fasilitas/update_status.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    });

    const hasil = await respons.json();

    if (hasil.status === "sukses") {
      alert("Status diperbarui!");
      ambilDataFasilitas(); // Refresh tabel
    } else {
      alert("Gagal: " + hasil.message);
    }
  } catch (error) {
    alert("Kesalahan jaringan: " + error);
  }
}
