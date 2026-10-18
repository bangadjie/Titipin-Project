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
      ambilDataFasilitas();
    } else {
      alert("Gagal: " + hasil.message);
    }
  } catch (error) {
    alert("Terjadi kesalahan jaringan: " + error);
  }
}

async function ambilDataFasilitas() {
  try {
    const respons = await fetch("../api/fasilitas/read.php");
    const hasil = await respons.json();
    if (hasil.status === "sukses") {
      console.log("Data Fasilitas:", hasil.data);
      // Nanti data dilooping
      // ke tabel HTML
    } else {
      console.error("Gagal ambil data:", hasil.message);
    }
  } catch (error) {
    console.error("Kesalahan jaringan:", error);
  }
}

async function updateFasilitas(dataFasilitas) {
  try {
    const formData = new FormData();

    formData.append("id_kerusakan", dataFasilitas.id_kerusakan);
    formData.append("nama_fasilitas", dataFasilitas.nama_fasilitas);
    formData.append("kategori_id", dataFasilitas.kategori_id);
    formData.append("lokasi_id", dataFasilitas.lokasi_id);
    formData.append("deskripsi_kerusakan", dataFasilitas.eskripsi_kerusakan);
    formData.append("status", dataFasilitas.status);
    formData.append("catatan_teknisi", dataFasilitas.catatan_teknisi);

    if (dataFasilitas.foto) {
      formData.append("foto", dataFasilitas.foto);
    }
    const respons = await fetch("../api/fasilitas/update.php", {
      method: "POST",
      body: formData,
    });
    const hasil = await respons.json();

    if (hasil.status === "sukses") {
      alert("Data fasilitas berhasil diperbarui!");

      ambilDataFasilitas();
    } else {
      alert("Gagal: " + hasil.message);
    }
  } catch (error) {
    alert("Kesalahan jaringan: " + error);
  }
}

async function hapusFasilitas(id_kerusakan) {
  const konfirmasi = confirm(
    "Apakah kamu yakin ingin menghapus laporan fasilitas ini?",
  );
  if (!konfirmasi) {
    return;
  }

  try {
    const respons = await fetch("../api/fasilitas/delete.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        id_kerusakan: id_kerusakan,
      }),
    });
    const hasil = await respons.json();

    if (hasil.status === "sukses") {
      alert("Data fasilitas berhasil dihapus!");

      ambilDataFasilitas();
    } else {
      alert("Gagal: " + hasil.message);
    }
  } catch (error) {
    alert("Kesalahan jaringan: " + error);
  }
}
