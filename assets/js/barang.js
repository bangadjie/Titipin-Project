// assets/js/barang.js

async function tambahBarang(event) {
  event.preventDefault(); 

  const formElemen = document.getElementById("formTambahBarang");

  const formData = new FormData(formElemen);

 
  try {
    const respons = await fetch("../api/barang/create.php", {
      method: "POST",
      body: formData, 
    });

    const hasil = await respons.json();

    if (hasil.status === "sukses") {
      alert("Berhasil: " + hasil.message);
    } else {
      alert("Gagal: " + hasil.message);
    }
  } catch (error) {
    alert("Terjadi kesalahan jaringan: " + error);
  }
}
