<?php
include 'koneksi.php';

function select($query) {
    global $db;
    $result = mysqli_query($db, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
      $rows[] = $row;
    }
    return $rows;
}
function create_barang($post)
{
  global $db;
  $Nama = mysqli_real_escape_string($db, $post['Nama']);
  $Kondisi = mysqli_real_escape_string($db, $post['Kondisi']);
  $Jumlah = (int) $post['Jumlah'];


  $query = "INSERT INTO barang VALUES (null, '$Nama', '$Kondisi', '$Jumlah')";
  mysqli_query($db, $query);
  return mysqli_affected_rows($db);
}

function update_barang($post)
{
  global $db;
  $id = $post['id'];
  $Nama = $post['Nama'];
  $Kondisi = $post['Kondisi'];
  $Jumlah = $post['Jumlah'];

  $query = "UPDATE barang SET Nama='$Nama', Kondisi='$Kondisi', Jumlah='$Jumlah' WHERE id=$id";
  mysqli_query($db, $query);
  return mysqli_affected_rows($db);
}

function delete_barang($id)
{
  global $db;
  mysqli_query($db, "DELETE FROM barang WHERE id = $id");
  return mysqli_affected_rows($db);
}


// KATEGORI
function create_kategori($post)
{
  global $db;
  $kategori = mysqli_real_escape_string($db, $post['kategori']);
  $query = "INSERT INTO kategori (kategori) VALUES ('$kategori')";
  mysqli_query($db, $query);
  return mysqli_affected_rows($db);
}

function update_kategori($post)
{
  global $db;
  $id = (int)$post['id_kategori'];
  $kategori = mysqli_real_escape_string($db, $post['kategori']);
  $query = "UPDATE kategori SET kategori='$kategori' WHERE id_kategori='$id'";
  mysqli_query($db, $query);
  return mysqli_affected_rows($db);
}

function delete_kategori($id)
{
  global $db;
  mysqli_query($db, "DELETE FROM kategori WHERE id_kategori = $id");
  return mysqli_affected_rows($db);
}


// RUANGAN
function create_ruangan($post)
{
  global $db;
  $nama_ruangan = mysqli_real_escape_string($db, $post['nama_ruangan']);
  $lantai = (int)$post['lantai'];

  $query = "INSERT INTO ruangan (nama_ruangan, lantai) VALUES ('$nama_ruangan', $lantai)";
  mysqli_query($db, $query);
  return mysqli_affected_rows($db);
}

function update_ruangan($post)
{
  global $db;
  $id = (int)$post['id_ruangan'];
  $nama_ruangan = mysqli_real_escape_string($db, $post['nama_ruangan']);
  $lantai = (int)$post['lantai'];

  $query = "UPDATE ruangan SET nama_ruangan='$nama_ruangan', lantai=$lantai WHERE id_ruangan=$id";
  mysqli_query($db, $query);
  return mysqli_affected_rows($db);
}

function delete_ruangan($id)
{
  global $db;
  mysqli_query($db, "DELETE FROM ruangan WHERE id_ruangan = $id");
  return mysqli_affected_rows($db);
}
?>