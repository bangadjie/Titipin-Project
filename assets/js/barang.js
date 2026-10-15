// assets/js/barang.js

async function tambahBarang(event) {
  event.preventDefault(); // Mencegah halaman refresh

  // Tangkap elemen form dari HTML
  const formElemen = document.getElementById("formTambahBarang");

  // FormData akan otomatis mengambil semua input (termasuk file gambar) yang ada di dalam form HTML tersebut
  // Syaratnya: input di HTML wajib punya atribut "name" (misal: name="nama_barang", name="foto")
  const formData = new FormData(formElemen);

  // Tambahkan pelapor_id dari session/local storage jika belum ada di form tersembunyi
  // formData.append('pelapor_id', 'A003'); // Contoh kalau mau diset manual dari JS

  try {
    const respons = await fetch("../api/barang/create.php", {
      method: "POST",
      body: formData, // Langsung kirim formData, TIDAK PERLU headers Content-Type: application/json
    });

    const hasil = await respons.json();

    if (hasil.status === "sukses") {
      alert("Berhasil: " + hasil.message);
      // Opsional: formElemen.reset(); // Kosongkan form setelah sukses
      // Opsional: panggil fungsi ambilDataBarang() untuk refresh tabel
    } else {
      alert("Gagal: " + hasil.message);
    }
  } catch (error) {
    alert("Terjadi kesalahan jaringan: " + error);
  }
}
