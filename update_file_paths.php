<?php
/**
 * Script untuk memperbarui path file dari 'uploads/' ke 'assets/uploads/'
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Include database configuration
require_once 'config/database.php';

echo "Memulai proses update path file...\n";

// Get all records with 'uploads/' in file paths for peserta table
$sql = "SELECT nik, file_ktp, file_tanda_anggota FROM peserta 
        WHERE file_ktp LIKE 'uploads/%' 
        OR file_tanda_anggota LIKE 'uploads/%'";

$result = $conn->query($sql);

if ($result) {
    $updateCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $nik = $row['nik'];
        $updates = [];
        $params = [];
        
        // Check and update file_ktp
        if (!empty($row['file_ktp']) && strpos($row['file_ktp'], 'uploads/') === 0) {
            $newPath = str_replace('uploads/', 'assets/uploads/', $row['file_ktp']);
            $updates[] = "file_ktp = ?";
            $params[] = $newPath;
            echo "Memperbarui file_ktp untuk NIK $nik: {$row['file_ktp']} -> $newPath\n";
        }
        
        // Check and update file_tanda_anggota
        if (!empty($row['file_tanda_anggota']) && strpos($row['file_tanda_anggota'], 'uploads/') === 0) {
            $newPath = str_replace('uploads/', 'assets/uploads/', $row['file_tanda_anggota']);
            $updates[] = "file_tanda_anggota = ?";
            $params[] = $newPath;
            echo "Memperbarui file_tanda_anggota untuk NIK $nik: {$row['file_tanda_anggota']} -> $newPath\n";
        }
        
        // foto_kegiatan is now in peserta_layanan table
        
        // If there are updates, execute the update query
        if (!empty($updates)) {
            $updateSql = "UPDATE peserta SET " . implode(", ", $updates) . " WHERE nik = ?";
            $params[] = $nik;
            
            $stmt = $conn->prepare($updateSql);
            if ($stmt) {
                // Determine types for bind_param
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } elseif (is_string($param)) {
                        $types .= 's';
                    } else {
                        $types .= 'b';
                    }
                }
                
                // Bind parameters dynamically
                $bindParams = array_merge([$types], $params);
                $bindParamsRef = [];
                foreach ($bindParams as $key => $value) {
                    $bindParamsRef[$key] = &$bindParams[$key];
                }
                call_user_func_array([$stmt, 'bind_param'], $bindParamsRef);
                
                if ($stmt->execute()) {
                    $updateCount++;
                    echo "Berhasil memperbarui data untuk NIK $nik\n";
                } else {
                    echo "Gagal memperbarui data untuk NIK $nik: " . $stmt->error . "\n";
                }
                
                $stmt->close();
            } else {
                echo "Gagal menyiapkan statement untuk NIK $nik: " . $conn->error . "\n";
            }
        }
    }
    
    echo "Proses update selesai untuk tabel peserta. Total $updateCount record diperbarui.\n";
} else {
    echo "Gagal mengambil data dari tabel peserta: " . $conn->error . "\n";
}

// Now update foto_kegiatan in peserta_layanan table
$sql = "SELECT pl.id, pl.nik, pl.foto_kegiatan FROM peserta_layanan pl 
        WHERE pl.foto_kegiatan LIKE 'uploads/%'";

$result = $conn->query($sql);

if ($result) {
    $updateCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $nik = $row['nik'];
        
        // Check and update foto_kegiatan
        if (!empty($row['foto_kegiatan']) && strpos($row['foto_kegiatan'], 'uploads/') === 0) {
            $newPath = str_replace('uploads/', 'assets/uploads/', $row['foto_kegiatan']);
            
            $updateSql = "UPDATE peserta_layanan SET foto_kegiatan = ? WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            
            if ($stmt) {
                $stmt->bind_param('si', $newPath, $id);
                
                if ($stmt->execute()) {
                    $updateCount++;
                    echo "Berhasil memperbarui foto_kegiatan untuk ID $id (NIK: $nik): {$row['foto_kegiatan']} -> $newPath\n";
                } else {
                    echo "Gagal memperbarui foto_kegiatan untuk ID $id: " . $stmt->error . "\n";
                }
                
                $stmt->close();
            } else {
                echo "Gagal menyiapkan statement untuk ID $id: " . $conn->error . "\n";
            }
        }
    }
    
    echo "Proses update selesai untuk tabel peserta_layanan. Total $updateCount record diperbarui.\n";
} else {
    echo "Gagal mengambil data dari tabel peserta_layanan: " . $conn->error . "\n";
}

$conn->close();
echo "Koneksi database ditutup.\n";
