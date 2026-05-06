<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, User-Agent, Accept, X-Requested-With");
header("Access-Control-Max-Age: 3600");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

$conn = mysqli_connect("sql311.infinityfree.com", "if0_41827143", "nQbTviNYRGAjVK", "if0_41827143_tugas_232042");

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "koneksi database gagal"]);
    exit;
}

$baseUrl = "";

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
// POST DATA (INSERT / UPDATE via _method)
// =========================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Flutter tidak bisa kirim PUT multipart, jadi pakai _method=PUT
    $method = $_POST['_method'] ?? '';

    // ---------- UPDATE ----------
    if ($method === 'PUT') {

        if (!isset($_POST['id']) || !isset($_POST['title'])) {
            echo json_encode(["status" => "error", "message" => "id atau title tidak ada"]);
            exit;
        }

        $id    = mysqli_real_escape_string($conn, $_POST['id']);
        $title = mysqli_real_escape_string($conn, $_POST['title']);

        // Ambil data lama sebagai fallback jika file tidak diganti
        $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM youtube_232042 WHERE id='$id'"));
        if (!$existing) {
            echo json_encode(["status" => "error", "message" => "data tidak ditemukan"]);
            exit;
        }

        $thumbPath = $existing['thumbnail'];
        $videoPath = $existing['video'];

        // Ganti thumbnail jika ada file baru
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $thumb     = $_FILES['thumbnail']['name'];
            $tmp_thumb = $_FILES['thumbnail']['tmp_name'];
            if (move_uploaded_file($tmp_thumb, "thumbnail/" . $thumb)) {
                $thumbPath = "thumbnail/" . $thumb;
            } else {
                echo json_encode(["status" => "error", "message" => "gagal upload thumbnail baru"]);
                exit;
            }
        }

        // Ganti video jika ada file baru
        if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $video     = $_FILES['video']['name'];
            $tmp_video = $_FILES['video']['tmp_name'];
            if (move_uploaded_file($tmp_video, "video/" . $video)) {
                $videoPath = "video/" . $video;
            } else {
                echo json_encode(["status" => "error", "message" => "gagal upload video baru"]);
                exit;
            }
        }

        $query = "UPDATE youtube_232042
                  SET title='$title', thumbnail='$thumbPath', video='$videoPath'
                  WHERE id='$id'";

        if (mysqli_query($conn, $query)) {
            echo json_encode(["status" => "success", "message" => "data berhasil diupdate"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        exit;
    }

    // ---------- INSERT ----------
    if (!isset($_POST['title'])) {
        echo json_encode(["status" => "error", "message" => "title tidak ada"]);
        exit;
    }
    if (!isset($_FILES['thumbnail'])) {
        echo json_encode(["status" => "error", "message" => "thumbnail tidak ada"]);
        exit;
    }
    if (!isset($_FILES['video'])) {
        echo json_encode(["status" => "error", "message" => "video tidak ada"]);
        exit;
    }

    $title     = mysqli_real_escape_string($conn, $_POST['title']);
    $thumb     = $_FILES['thumbnail']['name'];
    $tmp_thumb = $_FILES['thumbnail']['tmp_name'];
    $video     = $_FILES['video']['name'];
    $tmp_video = $_FILES['video']['tmp_name'];

    if (!is_dir("thumbnail")) {
        echo json_encode(["status" => "error", "message" => "folder thumbnail tidak ada"]);
        exit;
    }
    if (!is_dir("video")) {
        echo json_encode(["status" => "error", "message" => "folder video tidak ada"]);
        exit;
    }

    if (!move_uploaded_file($tmp_thumb, "thumbnail/" . $thumb)) {
        echo json_encode(["status" => "error", "message" => "gagal upload thumbnail"]);
        exit;
    }
    if (!move_uploaded_file($tmp_video, "video/" . $video)) {
        echo json_encode(["status" => "error", "message" => "gagal upload video"]);
        exit;
    }

    $query = "INSERT INTO youtube_232042 (title, thumbnail, video)
              VALUES ('$title', 'thumbnail/$thumb', 'video/$video')";

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

    // Ambil path file lama agar bisa dihapus dari server
    $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM youtube_232042 WHERE id='$id'"));
    if (!$existing) {
        echo json_encode(["status" => "error", "message" => "data tidak ditemukan"]);
        exit;
    }

    // Hapus file fisik dari server
    if (file_exists($existing['thumbnail'])) {
        unlink($existing['thumbnail']);
    }
    if (file_exists($existing['video'])) {
        unlink($existing['video']);
    }

    $query = "DELETE FROM youtube_232042 WHERE id='$id'";

    if (mysqli_query($conn, $query)) {
        echo json_encode(["status" => "success", "message" => "data berhasil dihapus"]);
    } else {
        echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
    }
    exit;
}
?>