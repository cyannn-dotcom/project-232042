<?php
// ✅ Matikan semua output error PHP agar tidak mengacaukan JSON
error_reporting(0);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, User-Agent, Accept, X-Requested-With");
header("Access-Control-Max-Age: 3600");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

$conn = mysqli_connect(
    "trolley.proxy.rlwy.net",
    "root",
    "KfBUjePOoqIEnipqfxzcnjPBWWUmrTWA",
    "railway",
    23412
);

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "koneksi database gagal"]);
    exit;
}

$baseUrl = "https://project-232042-production.up.railway.app/";

// =========================
// GET DATA
// =========================
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $result = mysqli_query($conn, "SELECT * FROM youtube_232042");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['thumbnail'] = $baseUrl . "Image.php?folder=thumbnail&file=" . basename($row['thumbnail']);
        $row['video']     = $baseUrl . "Image.php?folder=video&file=" . basename($row['video']);
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

// =========================
// POST DATA (INSERT / UPDATE)
// =========================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $method = $_POST['_method'] ?? '';

    // ✅ Buat folder jika belum ada (penting untuk Railway)
    if (!is_dir("thumbnail")) mkdir("thumbnail", 0777, true);
    if (!is_dir("video"))     mkdir("video", 0777, true);

    // ---------- UPDATE ----------
    if ($method === 'PUT') {
        if (!isset($_POST['id']) || !isset($_POST['title'])) {
            echo json_encode(["status" => "error", "message" => "id atau title tidak ada"]);
            exit;
        }

        $id    = mysqli_real_escape_string($conn, $_POST['id']);
        $title = mysqli_real_escape_string($conn, $_POST['title']);

        $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM youtube_232042 WHERE id='$id'"));
        if (!$existing) {
            echo json_encode(["status" => "error", "message" => "data tidak ditemukan"]);
            exit;
        }

        $thumbPath = $existing['thumbnail'];
        $videoPath = $existing['video'];

        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $thumb = basename($_FILES['thumbnail']['name']);
            $thumb = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $thumb); // ✅ sanitasi nama
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], "thumbnail/" . $thumb)) {
                $thumbPath = "thumbnail/" . $thumb;
            } else {
                echo json_encode(["status" => "error", "message" => "gagal upload thumbnail baru"]);
                exit;
            }
        }

        if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $video = basename($_FILES['video']['name']);
            $video = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $video); // ✅ sanitasi nama
            if (move_uploaded_file($_FILES['video']['tmp_name'], "video/" . $video)) {
                $videoPath = "video/" . $video;
            } else {
                echo json_encode(["status" => "error", "message" => "gagal upload video baru"]);
                exit;
            }
        }

        $query = "UPDATE youtube_232042 SET title='$title', thumbnail='$thumbPath', video='$videoPath' WHERE id='$id'";
        if (mysqli_query($conn, $query)) {
            echo json_encode(["status" => "success", "message" => "data berhasil diupdate"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        exit;
    }

    // ---------- INSERT ----------
    if (!isset($_POST['title']) || !isset($_FILES['thumbnail']) || !isset($_FILES['video'])) {
        echo json_encode(["status" => "error", "message" => "data tidak lengkap"]);
        exit;
    }

    $title = mysqli_real_escape_string($conn, $_POST['title']);

    $thumb = preg_replace('/[^a-zA-Z0-9._\-]/', '_', basename($_FILES['thumbnail']['name'])); // ✅
    $video = preg_replace('/[^a-zA-Z0-9._\-]/', '_', basename($_FILES['video']['name']));     // ✅

    if (!move_uploaded_file($_FILES['thumbnail']['tmp_name'], "thumbnail/" . $thumb)) {
        echo json_encode(["status" => "error", "message" => "gagal upload thumbnail"]);
        exit;
    }
    if (!move_uploaded_file($_FILES['video']['tmp_name'], "video/" . $video)) {
        echo json_encode(["status" => "error", "message" => "gagal upload video"]);
        exit;
    }

    $query = "INSERT INTO youtube_232042 (title, thumbnail, video) VALUES ('$title', 'thumbnail/$thumb', 'video/$video')";
    if (mysqli_query($conn, $query)) {
        echo json_encode(["status" => "success", "message" => "data berhasil disimpan"]);
    } else {
        echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
    }
    exit;
}

// =========================
// DELETE DATA
// =========================
if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

    if ($id === '') {
        echo json_encode(["status" => "error", "message" => "id tidak ada"]);
        exit;
    }

    $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM youtube_232042 WHERE id='$id'"));
    if (!$existing) {
        echo json_encode(["status" => "error", "message" => "data tidak ditemukan"]);
        exit;
    }

    if (file_exists($existing['thumbnail'])) unlink($existing['thumbnail']);
    if (file_exists($existing['video']))     unlink($existing['video']);

    $query = "DELETE FROM youtube_232042 WHERE id='$id'";
    if (mysqli_query($conn, $query)) {
        echo json_encode(["status" => "success", "message" => "data berhasil dihapus"]);
    } else {
        echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
    }
    exit;
}
?>