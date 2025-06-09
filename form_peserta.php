<?php

/**
 * Form Pendaftaran Peserta
 * 
 * This page displays the form for registering new participants.
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Start session
session_start();

// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/validation.php';
require_once 'includes/ocr.php';

// Page title
$pageTitle = 'YankesDokpol - Pendaftaran Peserta';

// Hapus data peserta dari session jika halaman di-refresh
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['existing_participant'])) {
    unset($_SESSION['existing_participant']);
}

// Check if NIK is provided in URL for editing
$editMode = false;
$existingData = null;
$existingLayanan = [];

if (isset($_GET['nik']) && !empty($_GET['nik'])) {
    $nikToEdit = sanitizeInput($_GET['nik']);
    $acaraId = isset($_GET['acara_id']) ? (int)$_GET['acara_id'] : null;

    // Get participant data with file fields and layanan data
    $query = "SELECT p.*, 
                     pl.acara_id, 
                     pl.petugas, 
                     pl.satker_id,
                     pl.hasil_pemeriksaan, 
                     pl.foto_kegiatan as acara_foto_kegiatan,  -- Always use foto_kegiatan from peserta_layanan
                     p.file_ktp, 
                     p.file_tanda_anggota, 
                     GROUP_CONCAT(pl.layanan_id) as layanan_ids,
                     a.nama_acara
              FROM peserta p 
              LEFT JOIN peserta_layanan pl ON p.nik = pl.nik 
              LEFT JOIN acara a ON pl.acara_id = a.id
              WHERE p.nik = ? ";

    $params = [$nikToEdit];

    // Add acara_id to query if provided
    if ($acaraId) {
        $query .= " AND pl.acara_id = ?";
        $params[] = $acaraId;
    } else {
        // If no acara_id provided, get the most recent acara
        $query .= " ORDER BY pl.created_at DESC";
    }

    $query .= " LIMIT 1";

    $existingData = fetchRow($query, $params);

    if ($existingData) {
        // If we have layanan_ids, convert them to an array
        if (!empty($existingData['layanan_ids'])) {
            $existingData['layanan_ids'] = explode(',', $existingData['layanan_ids']);
        } else {
            $existingData['layanan_ids'] = [];
        }

        // Always use foto_kegiatan from peserta_layanan for the specific acara_id
        if ($acaraId) {
            $fotoKegiatanData = fetchRow(
                "SELECT foto_kegiatan FROM peserta_layanan 
                 WHERE nik = ? AND acara_id = ? 
                 LIMIT 1",
                [$nikToEdit, $acaraId]
            );
            if (!empty($fotoKegiatanData['foto_kegiatan'])) {
                $existingData['foto_kegiatan'] = $fotoKegiatanData['foto_kegiatan'];
            } elseif (!empty($existingData['acara_foto_kegiatan'])) {
                // Fallback to the one from the initial query if available
                $existingData['foto_kegiatan'] = $existingData['acara_foto_kegiatan'];
            }
        } else if (!empty($existingData['acara_foto_kegiatan'])) {
            // If no specific acara_id, use the one from the initial query
            $existingData['foto_kegiatan'] = $existingData['acara_foto_kegiatan'];
        }
    } else {
        $existingData = [];
        $existingData['layanan_ids'] = [];
    }

    // Debug output
    error_log('Existing Data: ' . print_r($existingData, true));

    if ($existingData) {
        $editMode = true;

        // Get selected services and additional data for this participant
        // If we have acara_id, filter by both nik and acara_id to get the correct foto_kegiatan for this event
        if ($acaraId) {
            $result = executeQuery("SELECT pl.layanan_id, pl.hasil_pemeriksaan, pl.foto_kegiatan, pl.petugas, pl.satker_id
                                   FROM peserta_layanan pl 
                                   WHERE pl.nik = ? AND pl.acara_id = ? 
                                   ORDER BY pl.id ASC", [$nikToEdit, $acaraId]);
        } else {
            // If no acara_id specified, get all records (used for other purposes like collecting layanan IDs)
            $result = executeQuery("SELECT pl.layanan_id, pl.hasil_pemeriksaan, pl.foto_kegiatan, pl.petugas, pl.satker_id
                                   FROM peserta_layanan pl 
                                   WHERE pl.nik = ? 
                                   ORDER BY pl.id ASC", [$nikToEdit]);
        }
        if ($result) {
            $layananRows = fetchRows($result);

            // Get layanan IDs and petugas from the first record
            foreach ($layananRows as $row) {
                $existingLayanan[] = $row['layanan_id'];
                // Get petugas from the first record that has it
                if (!empty($row['petugas']) && empty($existingData['petugas'])) {
                    $existingData['petugas'] = $row['petugas'];
                }
                // Get satker_id from the first record that has it
                if (!empty($row['satker_id']) && empty($existingData['satker_id'])) {
                    $existingData['satker_id'] = $row['satker_id'];
                }
            }

            // Get hasil_pemeriksaan and foto_kegiatan from the first record that has them
            foreach ($layananRows as $row) {
                if (!empty($row['hasil_pemeriksaan']) && empty($existingData['hasil_pemeriksaan'])) {
                    $existingData['hasil_pemeriksaan'] = $row['hasil_pemeriksaan'];
                }
                if (!empty($row['foto_kegiatan']) && empty($existingData['foto_kegiatan'])) {
                    $existingData['foto_kegiatan'] = $row['foto_kegiatan'];
                }
                // If we've found both, no need to continue
                if (!empty($existingData['hasil_pemeriksaan']) && !empty($existingData['foto_kegiatan'])) {
                    break;
                }
            }
        }
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = false;
    $debug = []; // For debugging purposes

    // Get the acara_id from the form or existing data
    $acaraId = isset($_POST['acara_id']) ? (int)$_POST['acara_id'] : null;
    if ($editMode && !$acaraId && isset($existingData['acara_id'])) {
        $acaraId = (int)$existingData['acara_id'];
    }

    // Sanitize input data
    $nik = sanitizeInput($_POST['nik'] ?? '');
    $nama = sanitizeInput($_POST['nama'] ?? '');
    $alamat = sanitizeInput($_POST['alamat'] ?? '');
    $tanggalLahir = sanitizeInput($_POST['tanggal_lahir'] ?? '');
    $nomorHP = sanitizeInput($_POST['nomor_hp'] ?? '');
    $hasilPemeriksaan = sanitizeInput($_POST['hasil_pemeriksaan'] ?? '');
    $layananIds = isset($_POST['layanan']) ? $_POST['layanan'] : [];
    $satkerId = isset($_POST['satker_id']) && $_POST['satker_id'] !== '' ? (int)$_POST['satker_id'] : null;

    // Ensure layananIds is an array
    if (!is_array($layananIds)) {
        $layananIds = [];
    }

    // Validate required fields - NIK is NOT required
    $requiredFields = ['nama', 'alamat', 'tanggal_lahir', 'acara_id'];
    $missingFields = checkRequiredFields($_POST, $requiredFields);

    if (!empty($missingFields)) {
        foreach ($missingFields as $field) {
            $errors[$field] = 'Field ini wajib diisi';
        }
    }

    // Only validate NIK if it's manually entered
    if (!empty($nik)) {
        // Validate manually entered NIK
        // if (!validateNIK($nik)) {
        //     $errors['nik'] = 'NIK harus terdiri dari 16 digit angka';
        // }
    } else {
        // Auto-generate NIK if field is empty and we have name and birth date
        if (!empty($nama) && !empty($tanggalLahir)) {
            // Format: YYYYMMDDyyyymmdd (current date + birth date)
            $currentDate = date('Ymd'); // Current date in YYYYMMDD format
            $birthDate = str_replace('-', '', $tanggalLahir); // Birth date in yyyymmdd format
            $nik = $currentDate . $birthDate;
        }
    }

    // Check if NIK already exists and how to handle it
    if (!empty($nik)) {
        $existingParticipant = fetchRow("SELECT * FROM peserta WHERE nik = ?", [$nik]);

        if ($existingParticipant && !isset($_POST['update_existing'])) {
            // Check if this was an auto-generated NIK (empty in the original form submission)
            if (empty($_POST['nik'])) {
                // For auto-generated NIKs that already exist, make it unique
                $counter = 1;
                $originalNik = $nik;
                while ($existingParticipant) {
                    // Try adding a counter to make it unique (replace last digits)
                    $suffix = str_pad($counter, 2, '0', STR_PAD_LEFT);
                    $nik = substr($originalNik, 0, 14) . $suffix;
                    $existingParticipant = fetchRow("SELECT * FROM peserta WHERE nik = ?", [$nik]);
                    $counter++;

                    // Safety check to avoid infinite loop
                    if ($counter > 99) {
                        break;
                    }
                }
            } else {
                // For manually entered NIKs, check if the same acara_id exists
                $existingWithAcara = fetchRow("SELECT * FROM peserta_layanan WHERE nik = ? AND acara_id = ?", [$nik, $acaraId]);

                if ($existingWithAcara) {
                    // If same NIK and acara_id exist, suggest update
                    $errors['nik'] = 'sudah terdaftar pada acara ini. <button type="button" class="btn btn-sm btn-warning" id="updateExisting" data-nik="' . $nik . '" data-acara-id="' . $acaraId . '">Update data yang ada</button>';
                    $_SESSION['existing_participant_type'] = 'update';
                } else {
                    // If NIK exists but with different acara_id, automatically process it
                    $_SESSION['existing_participant'] = $existingParticipant;
                    $_SESSION['existing_participant_type'] = 'new_layanan';

                    // Instead of showing error, clear existing error if any
                    if (isset($errors['nik'])) {
                        unset($errors['nik']);
                    }

                    // No need to add an error, as we're automatically processing the form
                    // The rest of the form processing will use $_SESSION['existing_participant_type']
                }
            }
        }
    }

    // Process uploaded files
    $uploadedFiles = [];
    $fileFields = ['file_ktp', 'file_tanda_anggota'];

    // Handle foto_kegiatan separately since it's now in peserta_layanan table
    if (isset($_FILES['foto_kegiatan']) && $_FILES['foto_kegiatan']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/uploads/dokumentasi';

        // Generate a unique filename for the uploaded foto_kegiatan
        $ext = pathinfo($_FILES['foto_kegiatan']['name'], PATHINFO_EXTENSION);
        $customFilename = 'kegiatan_' . $nik . '_' . uniqid() . '.' . $ext;

        $uploadResult = uploadFile($_FILES['foto_kegiatan'], $uploadDir, $customFilename);

        if ($uploadResult['success']) {
            // Store just the filename, not the full path
            $uploadedFiles['foto_kegiatan'] = basename($uploadResult['filename']);

            // If we have an existing foto_kegiatan for this acara, mark it for deletion
            if ($acaraId && $existingParticipant) {
                $currentFoto = fetchRow(
                    "SELECT foto_kegiatan FROM peserta_layanan 
                     WHERE nik = ? AND acara_id = ? 
                     LIMIT 1",
                    [$nik, $acaraId]
                );

                if (!empty($currentFoto['foto_kegiatan'])) {
                    $_SESSION['old_foto_kegiatan'] = $currentFoto['foto_kegiatan'];
                }
            }
        } else {
            $errors['foto_kegiatan'] = $uploadResult['error'];
        }
    }

    // Process other file uploads
    foreach ($fileFields as $field) {
        // Skip foto_kegiatan as it's handled separately and we don't want to process it twice
        if ($field === 'foto_kegiatan') continue;

        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'assets/uploads/' . ($field === 'file_ktp' ? 'ktp' : 'tanda_anggota');

            // Handle existing files for the same user
            if ($existingParticipant && !empty($existingParticipant[$field])) {
                $oldFilePath = __DIR__ . '/assets/uploads/' .
                    ($field === 'file_ktp' ? 'ktp/' : 'tanda_anggota/') .
                    basename($existingParticipant[$field]);

                if (file_exists($oldFilePath)) {
                    if (unlink($oldFilePath)) {
                        error_log("Deleted old {$field} file during upload: {$oldFilePath}");

                        // Also delete optimized KTP file if it exists
                        if ($field === 'file_ktp') {
                            $pathInfo = pathinfo($oldFilePath);
                            $optimizedFilePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_optimized.jpg';
                            if (file_exists($optimizedFilePath)) {
                                if (unlink($optimizedFilePath)) {
                                    error_log("Deleted old optimized KTP file during form upload: {$optimizedFilePath}");
                                }
                            }
                        }
                    }
                }
            }

            // Process the file upload based on type
            if ($field === 'file_ktp') {
                // Special handling for KTP files
                $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                $customFilename = 'ktp_' . $nik . '_' . uniqid() . '.' . $ext;
                $uploadResult = uploadFile($_FILES[$field], $uploadDir, $customFilename);

                // Simpan path file KTP di session untuk digunakan oleh OCR
                if ($uploadResult['success']) {
                    $_SESSION['ocr_ktp_file'] = $uploadResult['filename'];
                }
            } else {
                // Standard upload for other files
                $uploadResult = uploadFile($_FILES[$field], $uploadDir);
            }

            // Process upload result
            if ($uploadResult['success']) {
                $uploadedFiles[$field] = basename($uploadResult['filename']);
            } else {
                $errors[$field] = $uploadResult['error'];
            }
        }
    }

    // If no errors, save to database
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert or update participant data
            if ($existingParticipant && (isset($_POST['update_existing']) || (isset($_SESSION['existing_participant_type']) && $_SESSION['existing_participant_type'] === 'new_layanan'))) {
                // Update existing participant
                $updateQuery = "UPDATE peserta SET 
                    nama = ?, 
                    alamat = ?, 
                    tanggal_lahir = ?, 
                    nomor_hp = ?";

                $params = [$nama, $alamat, $tanggalLahir, $nomorHP];

                // Handle file deletions first
                if (isset($_POST['hapus_file_tanda_anggota']) && $_POST['hapus_file_tanda_anggota'] === '1' && !empty($existingParticipant['file_tanda_anggota'])) {
                    $oldFilePath = __DIR__ . '/assets/uploads/tanda_anggota/' . basename($existingParticipant['file_tanda_anggota']);
                    if (file_exists($oldFilePath)) {
                        if (unlink($oldFilePath)) {
                            error_log("Deleted tanda_anggota file: {$oldFilePath}");
                            $updateQuery .= ", file_tanda_anggota = NULL";
                        } else {
                            error_log("Failed to delete tanda_anggota file: {$oldFilePath}");
                        }

                        // Cek dan hapus file optimized jika ada
                        if ($field === 'file_ktp') {
                            $pathInfo = pathinfo($oldFilePath);
                            $optimizedFilePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_optimized.jpg';
                            if (file_exists($optimizedFilePath)) {
                                if (unlink($optimizedFilePath)) {
                                    error_log("Deleted old optimized KTP file during form upload: {$optimizedFilePath}");
                                }
                            }
                        }
                    }
                }

                // Handle foto_kegiatan deletion
                if (isset($_POST['hapus_foto_kegiatan']) && $_POST['hapus_foto_kegiatan'] === '1' && !empty($existingParticipant)) {
                    // Get current foto_kegiatan for this acara
                    $currentFotoQuery = "SELECT foto_kegiatan FROM peserta_layanan WHERE nik = ? AND acara_id = ? LIMIT 1";
                    $currentFoto = fetchRow($currentFotoQuery, [$nik, $acaraId]);

                    if (!empty($currentFoto['foto_kegiatan'])) {
                        $oldFotoPath = __DIR__ . '/assets/uploads/dokumentasi/' . basename($currentFoto['foto_kegiatan']);
                        if (file_exists($oldFotoPath)) {
                            if (unlink($oldFotoPath)) {
                                error_log("Deleted foto_kegiatan file: {$oldFotoPath}");
                            } else {
                                error_log("Failed to delete foto_kegiatan file: {$oldFotoPath}");
                            }
                        }
                    }

                    // Set null for database update
                    $fotoKegiatanValue = null;
                }

                // Add uploaded files to the update query if they exist
                foreach ($uploadedFiles as $field => $filename) {
                    if ($field !== 'foto_kegiatan') { // foto_kegiatan is handled separately in peserta_layanan
                        $updateQuery .= ", {$field} = ?";
                        $params[] = $filename;
                    }
                }

                // Execute the update query for peserta table
                $updateQuery .= " WHERE nik = ?";
                $params[] = $nik;
                $stmt = $conn->prepare($updateQuery);
                $stmt->execute($params);
            } else {
                // Insert new participant
                $insertQuery = "INSERT INTO peserta (nik, nama, alamat, tanggal_lahir, nomor_hp";

                // Add file fields to query if they exist
                foreach ($uploadedFiles as $field => $filename) {
                    if ($field !== 'foto_kegiatan') { // foto_kegiatan is handled in peserta_layanan
                        $insertQuery .= ", {$field}";
                    }
                }

                $insertQuery .= ") VALUES (?, ?, ?, ?, ?";
                $params = [$nik, $nama, $alamat, $tanggalLahir, $nomorHP];

                // Add file values if they exist
                foreach ($uploadedFiles as $field => $filename) {
                    if ($field !== 'foto_kegiatan') {
                        $insertQuery .= ", ?";
                        $params[] = $filename;
                    }
                }

                $insertQuery .= ")";
                $stmt = $conn->prepare($insertQuery);
                $stmt->execute($params);
            }

            // Handle layanan and acara data
            if (!empty($layananIds) && $acaraId) {
                // Get general values for this submission that will be applied to each layanan record
                $petugasValue = $_POST['petugas'] ?? 'System';
                // $satkerId is already defined from form input (e.g., (int)$_POST['satker_id'] : null)
                // $hasilPemeriksaan is already defined from form input (e.g., sanitizeInput($_POST['hasil_pemeriksaan'] ?? ''))

                // Determine foto_kegiatan to save, considering uploads, deletions, and existing photos in edit mode
                $fotoKegiatanToSave = null;
                if (isset($_POST['hapus_foto_kegiatan']) && $_POST['hapus_foto_kegiatan'] === '1') {
                    // If marked for deletion, find current photo to delete physical file
                    $currentFotoDataForDelete = fetchRow(
                        "SELECT foto_kegiatan FROM peserta_layanan 
                         WHERE nik = ? AND acara_id = ? AND foto_kegiatan IS NOT NULL 
                         LIMIT 1", // Assuming one photo per NIK/Acara combination
                        [$nik, $acaraId]
                    );
                    if ($currentFotoDataForDelete && !empty($currentFotoDataForDelete['foto_kegiatan'])) {
                        $oldFotoPath = __DIR__ . '/assets/uploads/dokumentasi/' . basename($currentFotoDataForDelete['foto_kegiatan']);
                        if (file_exists($oldFotoPath)) {
                            if (unlink($oldFotoPath)) {
                                error_log("Deleted foto_kegiatan file due to checkbox: {$oldFotoPath}");
                            } else {
                                error_log("Failed to delete foto_kegiatan file: {$oldFotoPath}");
                            }
                        }
                    }
                    $fotoKegiatanToSave = null; // Ensure it's null in DB
                } elseif (isset($uploadedFiles['foto_kegiatan']) && !empty($uploadedFiles['foto_kegiatan'])) {
                    // If a new photo is uploaded, use it
                    $fotoKegiatanToSave = $uploadedFiles['foto_kegiatan'];
                    // If there was an old photo associated with this NIK/Acara (stored in session during upload process),
                    // and a new one is uploaded, delete the old physical file.
                    if (isset($_SESSION['old_foto_kegiatan']) && $_SESSION['old_foto_kegiatan'] !== $fotoKegiatanToSave) {
                        $oldFotoPathOnNewUpload = __DIR__ . '/assets/uploads/dokumentasi/' . basename($_SESSION['old_foto_kegiatan']);
                        if (file_exists($oldFotoPathOnNewUpload)) {
                            if (unlink($oldFotoPathOnNewUpload)) error_log("Deleted old foto_kegiatan upon new upload: {$oldFotoPathOnNewUpload}");
                            else error_log("Failed to delete old foto_kegiatan upon new upload: {$oldFotoPathOnNewUpload}");
                        }
                    }
                    unset($_SESSION['old_foto_kegiatan']); // Clean up session variable
                } elseif ($editMode) {
                    // If editing, no new photo uploaded, and not marked for deletion, try to retain existing photo
                    $currentFotoDataToKeep = fetchRow(
                        "SELECT foto_kegiatan FROM peserta_layanan 
                         WHERE nik = ? AND acara_id = ? AND foto_kegiatan IS NOT NULL 
                         LIMIT 1",
                        [$nik, $acaraId]
                    );
                    if ($currentFotoDataToKeep && !empty($currentFotoDataToKeep['foto_kegiatan'])) {
                        $fotoKegiatanToSave = $currentFotoDataToKeep['foto_kegiatan'];
                    }
                }
                // At this point, $fotoKegiatanToSave holds the correct filename for the DB or null.

                // First, delete all existing layanan records for this NIK and acara_id.
                // This simplifies logic: always delete then re-insert selected services.
                executeQuery("DELETE FROM peserta_layanan WHERE nik = ? AND acara_id = ?", [$nik, $acaraId]);

                // Then, insert each selected layanan
                foreach ($layananIds as $layanan_id_from_form) {
                    $layanan_id_trimmed = trim($layanan_id_from_form);
                    // Basic validation for layanan_id
                    if (empty($layanan_id_trimmed) || !is_numeric($layanan_id_trimmed) || (int)$layanan_id_trimmed <= 0) {
                        error_log("Invalid or empty layanan_id skipped: '{$layanan_id_from_form}' for NIK: {$nik}, Acara ID: {$acaraId}");
                        continue; // Skip invalid IDs
                    }
                    $valid_layanan_id = (int)$layanan_id_trimmed;

                    // Optional: Defensive check if layanan_id exists in 'layanan' table.
                    // The DB foreign key constraint will enforce this, but explicit check can give better error logging.
                    /*
                    $layananExistsCheck = fetchRow("SELECT id FROM layanan WHERE id = ?", [$valid_layanan_id]);
                    if (!$layananExistsCheck) {
                        error_log("Layanan ID {$valid_layanan_id} does not exist in layanan table. Skipping insertion for NIK: {$nik}, Acara ID: {$acaraId}. This would cause FK violation.");
                        // To make the error visible to the user and trigger a rollback, you might throw an exception:
                        // throw new Exception("Layanan dengan ID {$valid_layanan_id} tidak valid. Silakan periksa kembali pilihan layanan Anda.");
                        continue; 
                    }
                    */

                    $insertLayananQuery = "INSERT INTO peserta_layanan 
                                           (nik, acara_id, layanan_id, petugas, satker_id, foto_kegiatan, hasil_pemeriksaan) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?)";
                    executeQuery($insertLayananQuery, [
                        $nik,
                        $acaraId,
                        $valid_layanan_id, // Crucial: use the validated layanan_id from the loop
                        $petugasValue,
                        $satkerId,
                        $fotoKegiatanToSave,
                        $hasilPemeriksaan
                    ]);
                }
            } else {
                // Insert new participant
                $insertQuery = "INSERT INTO peserta (nik, nama, alamat, tanggal_lahir, nomor_hp";
                $values = "?, ?, ?, ?, ?";
                $params = [$nik, $nama, $alamat, $tanggalLahir, $nomorHP];

                // Add file fields if uploaded
                foreach ($fileFields as $field) {
                    if (isset($uploadedFiles[$field])) {
                        // Gunakan nama field asli untuk kolom di database
                        $insertQuery .= ", {$field}";
                        $values .= ", ?";
                        $params[] = $uploadedFiles[$field];
                    }
                }

                $insertQuery .= ") VALUES ({$values})";

                executeQuery($insertQuery, $params);
                // NIK is the primary key, so we'll use it for relationships
                $participantNik = $nik;
            }

            // Insert/update layanan relationships
            if (!empty($layananIds) && $acaraId) {
                // Get values from form submission or existing data
                $fotoKegiatanValue = !empty($uploadedFiles['foto_kegiatan']) ? $uploadedFiles['foto_kegiatan'] : (!empty($existingData['foto_kegiatan']) ? basename($existingData['foto_kegiatan']) : null);

                // If user checked to delete foto_kegiatan, set it to null and delete the file
                if (isset($_POST['hapus_foto_kegiatan']) && $_POST['hapus_foto_kegiatan'] === '1') {
                    // Delete the file if it exists
                    if (!empty($existingData['foto_kegiatan'])) {
                        $filePath = __DIR__ . '/../assets/uploads/dokumentasi/' . basename($existingData['foto_kegiatan']);
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    $fotoKegiatanValue = null;
                }

                $hasilPemeriksaanValue = !empty($_POST['hasil_pemeriksaan']) ? $_POST['hasil_pemeriksaan'] : (!empty($existingData['hasil_pemeriksaan']) ? $existingData['hasil_pemeriksaan'] : '');
                $petugasValue = !empty($_POST['petugas']) ? $_POST['petugas'] : (!empty($existingData['petugas']) ? $existingData['petugas'] : 'System');

                // Delete existing layanan entries for this participant and event
                $deleteQuery = "DELETE FROM peserta_layanan WHERE nik = ? AND acara_id = ?";
                $stmtDelete = $conn->prepare($deleteQuery);
                $stmtDelete->bind_param("si", $nik, $acaraId);
                $stmtDelete->execute();
                $stmtDelete->close();

                // Insert new layanan entries
                foreach ($layananIds as $layananId) {
                    $insertLayananQuery = "INSERT INTO peserta_layanan (nik, acara_id, layanan_id, petugas, satker_id, foto_kegiatan, hasil_pemeriksaan, created_at, updated_at) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                    $stmtLayanan = $conn->prepare($insertLayananQuery);
                    $stmtLayanan->bind_param("siisiss", $nik, $acaraId, $layananId, $petugasValue, $satkerId, $fotoKegiatanValue, $hasilPemeriksaanValue);
                    $stmtLayanan->execute();
                    $stmtLayanan->close();
                }
            } elseif ($editMode && $acaraId) {
                // If no layanan selected but we're in edit mode, update the existing record
                $updateLayananQuery = "UPDATE peserta_layanan SET 
                                     petugas = ?, 
                                     hasil_pemeriksaan = ?, 
                                     updated_at = NOW()";

                // Add foto_kegiatan to update if it's being updated or removed
                if (!empty($uploadedFiles['foto_kegiatan'])) {
                    $updateLayananQuery .= ", foto_kegiatan = ?";
                    $params = [$_POST['petugas'] ?? 'System', $hasilPemeriksaan, $uploadedFiles['foto_kegiatan']];
                } elseif (isset($_POST['hapus_foto_kegiatan']) && $_POST['hapus_foto_kegiatan'] === '1') {
                    $updateLayananQuery .= ", foto_kegiatan = NULL";
                    $params = [$_POST['petugas'] ?? 'System', $hasilPemeriksaan];
                } else {
                    $params = [$_POST['petugas'] ?? 'System', $hasilPemeriksaan];
                }

                $updateLayananQuery .= " WHERE nik = ? AND acara_id = ?";
                $params[] = $nik;
                $params[] = $acaraId;

                $stmtUpdate = $conn->prepare($updateLayananQuery);
                $paramTypes = str_repeat('s', count($params) - 2) . 'si'; // All strings except the last two (nik, acara_id)
                $stmtUpdate->bind_param($paramTypes, ...$params);
                $stmtUpdate->execute();
                $stmtUpdate->close();
            }

            // Commit transaction
            $conn->commit();

            // Set success message based on the scenario
            if ($existingParticipant && isset($_POST['update_existing'])) {
                setFlashMessage('Data peserta berhasil diupdate', 'success');
            } elseif (isset($_SESSION['existing_participant_type']) && $_SESSION['existing_participant_type'] === 'new_layanan') {
                setFlashMessage('Data peserta diupdate dan berhasil didaftarkan pada acara baru', 'success');
            } else {
                setFlashMessage('Data peserta berhasil disimpan', 'success');
            }

            // Clear the session variable if it exists
            if (isset($_SESSION['existing_participant_type'])) {
                unset($_SESSION['existing_participant_type']);
            }

            // Redirect to prevent form resubmission
            if ($existingParticipant && isset($_POST['update_existing'])) {
                // After update, redirect back to the participant list
                header('Location: daftar_peserta.php');
            } else {
                // After new registration, stay on the form
                header('Location: form_peserta.php');
            }
            exit;
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors['db'] = 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage();
        }
    }
}

// Get available services
$layananList = fetchRows("SELECT * FROM layanan ORDER BY nama_layanan", []);

// Get active events
$eventList = fetchRows("SELECT * FROM acara WHERE status = 'aktif' ORDER BY tanggal_mulai DESC", []);

// Get active satker for dropdown
$satkerList = fetchRows("SELECT id, nama_satker FROM satker WHERE is_active = 1 ORDER BY nama_satker", []);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <?php include 'includes/pwa_head.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .floating-admin-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            background-color: white;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            text-decoration: none;
            /* Ensure no underline */
            cursor: pointer;
            /* Show pointer cursor */
        }

        .floating-admin-btn:hover {
            transform: rotate(10deg);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            background-color: var(--primary-color);
            color: white;
        }

        .floating-admin-btn i {
            font-size: 1.2rem;
            pointer-events: none;
            /* Prevent icon from capturing clicks */
        }

        /* Logout button styles */
        .floating-logout-btn {
            background-color: #dc3545 !important;
            /* Red color */
            border-color: #dc3545 !important;
            color: white !important;
        }

        .floating-logout-btn:hover {
            background-color: #bb2d3b !important;
            /* Darker red on hover */
            border-color: #b02a37 !important;
            color: white !important;
        }

        @media (max-width: 768px) {
            .floating-admin-btn {
                width: 40px;
                height: 40px;
            }

            .floating-admin-btn i {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php
    // Define $isAdmin for admin_nav.php
    $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    if ($isAdmin):
        include 'includes/admin_nav.php';
    endif;
    ?>
    <div class="<?php echo (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'main-content-admin' : ''; ?>">

        <div class="container py-4">

            <!-- Floating Admin Button (hanya tampil jika bukan admin) -->
            <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'): ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" class="floating-admin-btn floating-logout-btn" title="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                <?php else: ?>
                    <a href="dashboard.php" class="floating-admin-btn floating-dashboard-btn" title="Lihat Dashboard" style="right: 75px;">
                        <i class="bi bi-speedometer2"></i>
                    </a>
                    <a href="login.php" class="floating-admin-btn" title="Login Admin">
                        <i class="bi bi-lock"></i>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php
            // Display flash messages if any
            $flashMessage = getFlashMessage();
            if ($flashMessage) {
                echo '<div class="alert alert-' . $flashMessage['type'] . ' alert-dismissible fade show" role="alert">';
                echo $flashMessage['message'];
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div>';
            }
            ?>

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="h5 mb-0"><?php echo $editMode ? 'Edit Data Peserta' : 'Form Pendaftaran Peserta'; ?></h2>
                </div>
                <div class="card-body">
                    <form id="registrationForm" action="form_peserta.php" method="post" enctype="multipart/form-data">
                        <?php if ($editMode): ?>
                            <input type="hidden" name="update_existing" value="1">
                            <?php if (!empty($existingData['acara_id'])): ?>
                                <input type="hidden" name="acara_id" value="<?php echo htmlspecialchars($existingData['acara_id']); ?>">
                            <?php endif; ?>
                        <?php endif; ?>
                        <!-- Pilih Acara -->
                        <div class="mb-3">
                            <label for="acaraId" class="form-label">Pilih Acara <span class="text-danger">*</span></label>
                            <select class="form-select <?php echo isset($errors['acara_id']) ? 'is-invalid' : ''; ?>" id="acaraId" name="acara_id" required data-last-selected="">
                                <option value="">-- Pilih Acara --</option>
                                <?php
                                $lastAcaraId = isset($_COOKIE['yankesdokpol_last_acara_id']) ? (int)$_COOKIE['yankesdokpol_last_acara_id'] : 0;
                                foreach ($eventList as $event):
                                    $isSelected = false;
                                    if ($editMode && isset($existingData['acara_id']) && $existingData['acara_id'] == $event['id']) {
                                        $isSelected = true;
                                    } elseif (!$editMode && $lastAcaraId && $event['id'] == $lastAcaraId) {
                                        $isSelected = true;
                                    }
                                ?>
                                    <option value="<?php echo $event['id']; ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event['nama_acara']); ?> - <?php echo date('d/m/Y', strtotime($event['tanggal_mulai'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['acara_id'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['acara_id']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Petugas dan Satker yang mengisi data -->
                        <div class="alert alert-info mb-3">
                            <div class="row align-items-center">
                                <div class="col-md-2 fw-bold">Petugas:</div>
                                <div class="col-md-5">
                                    <input type="text" class="form-control" id="petugasInput" name="petugas"
                                        value="<?php echo $editMode ? htmlspecialchars($existingData['petugas'] ?? '') : ''; ?>"
                                        list="petugasList" required autocomplete="off">
                                    <datalist id="petugasList">
                                        <!-- Options will be populated by JavaScript -->
                                    </datalist>
                                </div>
                                <div class="col-md-2 fw-bold">Satker:</div>
                                <div class="col-md-3">
                                    <select class="form-select" id="satkerId" name="satker_id">
                                        <option value="">-- Pilih Satker --</option>
                                        <?php foreach ($satkerList as $satker): ?>
                                            <option value="<?php echo $satker['id']; ?>" <?php echo ($editMode && isset($existingData['satker_id']) && $existingData['satker_id'] == $satker['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($satker['nama_satker']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Debug info untuk melihat error -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h5>Error saat memproses form:</h5>
                                <ul>
                                    <?php foreach ($errors as $field => $error): ?>
                                        <li><?php echo $field; ?>: <?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Debug information -->
                        <?php if (isset($debug) && !empty($debug)): ?>
                            <div class="alert alert-info">
                                <h5>Debug Information:</h5>
                                <pre><?php print_r($debug); ?></pre>
                            </div>
                        <?php endif; ?>
                        <!-- Form fields will be implemented here -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h3 class="h5">Upload KTP untuk OCR</h3>
                                <div class="mb-3">
                                    <label for="fileKTP" class="form-label">Foto KTP</label>
                                    <div class="input-group mb-2">
                                        <input type="file" class="form-control" id="fileKTP" name="file_ktp" accept="image/*" style="display: none;">
                                        <button type="button" class="btn btn-outline-secondary" id="chooseFileBtn">
                                            <i class="bi bi-upload"></i> Pilih File
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" id="takePhotoBtn">
                                            <i class="bi bi-camera"></i> Ambil Foto
                                        </button>
                                    </div>
                                    <div class="form-text">Upload foto KTP untuk mengisi data otomatis dengan OCR</div>
                                    <div id="cameraContainer" class="mt-2 d-none">
                                        <div class="camera-overlay text-center py-1 bg-dark text-white mb-1 rounded">
                                            <small><i class="bi bi-info-circle"></i> Harap arahkan perangkat dalam posisi landscape</small>
                                        </div>
                                        <video id="cameraPreview" autoplay playsinline class="img-fluid border rounded" style="max-height: 300px;"></video>
                                        <div class="btn-group w-100 mt-2">
                                            <button type="button" id="captureBtn" class="btn btn-primary">Ambil Foto</button>
                                            <button type="button" id="cancelCaptureBtn" class="btn btn-secondary">Batal</button>
                                        </div>
                                    </div>
                                    <canvas id="canvas" class="d-none"></canvas>
                                    <canvas id="processCanvas" class="d-none"></canvas>
                                </div>
                                <button type="button" id="processOCR" class="btn btn-secondary mb-3">Proses OCR</button>
                            </div>
                            <div class="col-md-6">
                                <div id="ktpPreview" class="text-center d-none">
                                    <h5>Preview KTP</h5>
                                    <img id="ktpImage" src="#" alt="Preview KTP" class="img-fluid img-thumbnail">
                                </div>
                            </div>
                        </div>

                        <?php
                        // Get the selected services for this participant and event
                        $selectedLayanan = [];
                        if ($editMode && !empty($existingData['layanan_ids'])) {
                            $selectedLayanan = $existingData['layanan_ids'];
                        }
                        ?>

                        <h3 class="h5">Data Peserta</h3>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nik" class="form-label">NIK</label>
                                    <input type="text" class="form-control" id="nik" name="nik" maxlength="16" value="<?php echo $editMode ? htmlspecialchars($existingData['nik'] ?? '') : ''; ?>" <?php echo $editMode ? 'readonly' : ''; ?>>
                                    <div class="form-text">Opsional. Jika kosong, NIK akan dibuat otomatis dari tanggal hari ini dan tanggal lahir.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="nama" name="nama" required value="<?php echo $editMode ? htmlspecialchars($existingData['nama'] ?? '') : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="2" required><?php echo $editMode ? htmlspecialchars($existingData['alamat'] ?? '') : ''; ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tanggalLahir" class="form-label">Tanggal Lahir</label>
                                    <input type="date" class="form-control" id="tanggalLahir" name="tanggal_lahir" required value="<?php echo $editMode ? htmlspecialchars($existingData['tanggal_lahir'] ?? '') : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nomorHP" class="form-label">Nomor HP</label>
                                    <input type="tel" class="form-control" id="nomorHP" name="nomor_hp" value="<?php echo $editMode ? htmlspecialchars($existingData['nomor_hp'] ?? '') : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="hasilPemeriksaan" class="form-label">Hasil Pemeriksaan</label>
                            <textarea class="form-control" id="hasilPemeriksaan" name="hasil_pemeriksaan" rows="3"><?php echo $editMode ? htmlspecialchars($existingData['hasil_pemeriksaan'] ?? '') : ''; ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fileTandaAnggota" class="form-label">Tanda Keanggotaan (Opsional)</label>
                                    <input type="file" class="form-control" id="fileTandaAnggota" name="file_tanda_anggota" accept="image/*">
                                    <?php if ($editMode && !empty($existingData['file_tanda_anggota'])):
                                        $tandaAnggotaFile = basename($existingData['file_tanda_anggota']);
                                        $tandaAnggotaPath = 'tanda_anggota/' . $tandaAnggotaFile;
                                    ?>
                                        <div class="mt-2">
                                            <a href="assets/uploads/<?php echo htmlspecialchars($tandaAnggotaPath); ?>" target="_blank" class="btn btn-sm btn-info mb-1">
                                                <i class="bi bi-eye"></i> Lihat Tanda Anggota
                                            </a>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="hapus_file_tanda_anggota" name="hapus_file_tanda_anggota" value="1">
                                                <label class="form-check-label small text-danger" for="hapus_file_tanda_anggota">
                                                    Hapus file ini
                                                </label>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fotoKegiatan" class="form-label">Foto Dokumentasi Kegiatan</label>
                                    <input type="file" class="form-control" id="fotoKegiatan" name="foto_kegiatan" accept="image/*">
                                    <?php if ($editMode && !empty($existingData['foto_kegiatan'])):
                                        $fotoKegiatanFile = basename($existingData['foto_kegiatan']);
                                        // Always use the correct path for display
                                        $fotoKegiatanPath = 'assets/uploads/dokumentasi/' . $fotoKegiatanFile;
                                        // Check if file exists at the path
                                        if (!file_exists($fotoKegiatanPath)) {
                                            // Try with just the filename in case it's stored that way
                                            $fotoKegiatanPath = 'assets/uploads/dokumentasi/' . $existingData['foto_kegiatan'];
                                        }

                                        // Debug info
                                        error_log("Foto Kegiatan Path: " . $fotoKegiatanPath);
                                        error_log("File exists: " . (file_exists($fotoKegiatanPath) ? 'Yes' : 'No'));
                                    ?>
                                        <div class="mt-2">
                                            <?php if (file_exists($fotoKegiatanPath) || filter_var($fotoKegiatanPath, FILTER_VALIDATE_URL)): ?>
                                                <a href="<?php echo htmlspecialchars($fotoKegiatanPath); ?>" target="_blank" class="btn btn-sm btn-info mb-1">
                                                    <i class="bi bi-eye"></i> Lihat Foto
                                                </a>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="hapus_foto_kegiatan" name="hapus_foto_kegiatan" value="1">
                                                    <label class="form-check-label small text-danger" for="hapus_foto_kegiatan">
                                                        Hapus foto ini (Acara: <?php echo htmlspecialchars($existingData['nama_acara'] ?? 'Tidak Diketahui'); ?>)
                                                    </label>
                                                    <?php if (!empty($existingData['acara_id'])): ?>
                                                        <input type="hidden" name="acara_id_for_foto" value="<?php echo htmlspecialchars($existingData['acara_id']); ?>">
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-warning p-2 small">
                                                    <i class="bi bi-exclamation-triangle"></i> File foto tidak ditemukan di: <?php echo htmlspecialchars($fotoKegiatanPath); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <h3 class="h5">Layanan yang Diikuti</h3>
                        <div class="mb-3">
                            <div class="row">
                                <?php foreach ($layananList as $layanan): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="layanan[]" value="<?php echo $layanan['id']; ?>" id="layanan<?php echo $layanan['id']; ?>" <?php echo ($editMode && in_array($layanan['id'], $selectedLayanan)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="layanan<?php echo $layanan['id']; ?>">
                                                <?php echo htmlspecialchars($layanan['nama_layanan']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-outline-secondary">Reset</button>
                            <button type="submit" class="btn btn-primary"><?php echo $editMode ? 'Update Data' : 'Simpan Data'; ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <footer class="mt-4 text-center">
                <p>&copy; <?php echo date('Y'); ?> RS Bhayangkara Batu - Sistem Pencatatan Peserta Kegiatan</p>
            </footer>
        </div>
    </div> <!-- Closing main-content-admin wrapper -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/pwa.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('fileKTP');
            const chooseFileBtn = document.getElementById('chooseFileBtn');
            const takePhotoBtn = document.getElementById('takePhotoBtn');
            const cameraContainer = document.getElementById('cameraContainer');
            const cameraPreview = document.getElementById('cameraPreview');
            const captureBtn = document.getElementById('captureBtn');
            const cancelCaptureBtn = document.getElementById('cancelCaptureBtn');
            const canvas = document.getElementById('canvas');
            const ktpImage = document.getElementById('ktpImage');
            const ktpPreview = document.getElementById('ktpPreview');
            let stream = null;

            // Handle file selection button
            chooseFileBtn.addEventListener('click', function() {
                fileInput.click();
            });

            // Check if device is in landscape orientation
            function isLandscape() {
                return window.innerWidth > window.innerHeight;
            }

            // Show orientation message
            function checkOrientation() {
                const orientationMsg = document.querySelector('.camera-overlay');
                if (orientationMsg) {
                    if (!isLandscape()) {
                        orientationMsg.classList.add('bg-danger');
                        orientationMsg.innerHTML = '<small><i class="bi bi-exclamation-triangle-fill"></i>Posisikan KTP memenuhi ruang kamera</small>';
                    } else {
                        orientationMsg.classList.remove('bg-danger');
                        orientationMsg.innerHTML = '<small><i class="bi bi-info-circle"></i> Perangkat dalam posisi landscape</small>';
                    }
                }
            }

            // Listen for orientation changes
            window.addEventListener('resize', checkOrientation);

            // Handle take photo button
            takePhotoBtn.addEventListener('click', async function() {
                try {
                    // Request landscape if possible
                    const constraints = {
                        video: {
                            facingMode: 'environment', // Use back camera by default
                            width: {
                                ideal: 1920
                            },
                            height: {
                                ideal: 1080
                            }
                        },
                        audio: false
                    };

                    stream = await navigator.mediaDevices.getUserMedia(constraints);
                    cameraPreview.srcObject = stream;
                    cameraContainer.classList.remove('d-none');
                    takePhotoBtn.disabled = true;
                    chooseFileBtn.disabled = true;

                    // Check orientation after camera is initialized
                    checkOrientation();
                } catch (err) {
                    console.error('Error accessing camera:', err);
                    alert('Tidak dapat mengakses kamera. Pastikan Anda mengizinkan akses kamera.');
                }
            });

            // Handle capture button
            captureBtn.addEventListener('click', function() {
                const context = canvas.getContext('2d');
                canvas.width = cameraPreview.videoWidth;
                canvas.height = cameraPreview.videoHeight;
                context.drawImage(cameraPreview, 0, 0, canvas.width, canvas.height);

                // Check if the image is portrait and needs rotation
                // Force landscape orientation if portrait
                const processCtx = document.getElementById('processCanvas').getContext('2d');
                let finalWidth = canvas.width;
                let finalHeight = canvas.height;

                // If height > width (portrait orientation), rotate to landscape
                if (canvas.height > canvas.width) {
                    // Swap dimensions for rotation
                    finalWidth = canvas.height;
                    finalHeight = canvas.width;

                    // Set canvas to new dimensions
                    processCtx.canvas.width = finalWidth;
                    processCtx.canvas.height = finalHeight;

                    // Translate and rotate to get landscape
                    processCtx.translate(finalWidth, 0);
                    processCtx.rotate(Math.PI / 2); // 90 degrees rotation
                    processCtx.drawImage(canvas, 0, 0);
                } else {
                    // Already in landscape, just copy
                    processCtx.canvas.width = finalWidth;
                    processCtx.canvas.height = finalHeight;
                    processCtx.drawImage(canvas, 0, 0);
                }

                // Use the processed canvas for the blob
                document.getElementById('processCanvas').toBlob(function(blob) {
                    const file = new File([blob], 'ktp_capture_landscape.jpg', {
                        type: 'image/jpeg'
                    });

                    // Create a DataTransfer object and add the file
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);

                    // Set the file input files
                    fileInput.files = dataTransfer.files;

                    // Show preview
                    ktpImage.src = URL.createObjectURL(file);
                    ktpPreview.classList.remove('d-none');

                    // Clean up
                    stopCamera();
                }, 'image/jpeg', 0.9);
            });

            // Handle cancel capture button
            cancelCaptureBtn.addEventListener('click', stopCamera);

            // Stop camera and clean up
            function stopCamera() {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    stream = null;
                }
                cameraPreview.srcObject = null;
                cameraContainer.classList.add('d-none');
                takePhotoBtn.disabled = false;
                chooseFileBtn.disabled = false;
            }

            // Handle file input change
            fileInput.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const file = e.target.files[0];
                    const reader = new FileReader();

                    reader.onload = function(event) {
                        // Check if we need to rotate the uploaded image
                        const img = new Image();
                        img.onload = function() {
                            // If image is in portrait orientation, rotate it
                            if (img.height > img.width) {
                                const processCtx = document.getElementById('processCanvas').getContext('2d');

                                // Set canvas dimensions for landscape orientation
                                processCtx.canvas.width = img.height;
                                processCtx.canvas.height = img.width;

                                // Translate and rotate to get landscape
                                processCtx.translate(img.height, 0);
                                processCtx.rotate(Math.PI / 2); // 90 degrees rotation
                                processCtx.drawImage(img, 0, 0);

                                // Convert to blob and replace file
                                document.getElementById('processCanvas').toBlob(function(blob) {
                                    const rotatedFile = new File([blob], file.name, {
                                        type: 'image/jpeg'
                                    });

                                    // Create a DataTransfer object and add the file
                                    const dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(rotatedFile);

                                    // Replace the file input
                                    fileInput.files = dataTransfer.files;

                                    // Show rotated preview
                                    ktpImage.src = URL.createObjectURL(rotatedFile);
                                    ktpPreview.classList.remove('d-none');
                                }, 'image/jpeg', 0.9);
                            } else {
                                // Already landscape, just show preview
                                ktpImage.src = event.target.result;
                                ktpPreview.classList.remove('d-none');
                            }
                        };
                        img.src = event.target.result;
                    };

                    reader.readAsDataURL(file);
                }
            });

            // Clean up camera when leaving the page
            window.addEventListener('beforeunload', function() {
                if (stream) {
                    stopCamera();
                }
            });
        });
    </script>

    <?php include 'includes/pwa_install_button.php'; ?>
    <script>
        // Fungsi untuk mengelola daftar petugas di localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const petugasInput = document.getElementById('petugasInput');
            const petugasList = document.getElementById('petugasList');
            const satkerSelect = document.getElementById('satkerId');

            // Load daftar petugas dari localStorage
            function loadPetugasList() {
                const savedPetugas = localStorage.getItem('yankesdokpol_petugas_list');
                return savedPetugas ? JSON.parse(savedPetugas) : [];
            }

            // Simpan daftar petugas ke localStorage
            function savePetugasList(list) {
                // Pastikan tidak ada duplikat
                const uniqueList = [...new Set(list)];
                localStorage.setItem('yankesdokpol_petugas_list', JSON.stringify(uniqueList));
            }

            // Tampilkan daftar petugas di datalist
            function displayPetugasList() {
                const petugasList = document.getElementById('petugasList');
                petugasList.innerHTML = '';

                const list = loadPetugasList();
                list.forEach(nama => {
                    const option = document.createElement('option');
                    option.value = nama;
                    petugasList.appendChild(option);
                });
            }

            // Tambahkan nama petugas baru ke daftar
            function addPetugasToList(nama) {
                if (!nama) return;

                const list = loadPetugasList();
                if (!list.includes(nama)) {
                    list.unshift(nama); // Tambahkan di awal array agar muncul paling atas
                    savePetugasList(list);
                    displayPetugasList();
                }
            }

            // Tampilkan daftar saat halaman dimuat
            displayPetugasList();

            // Simpan nama petugas dan acara_id saat form disubmit
            document.getElementById('registrationForm').addEventListener('submit', function() {
                const petugasNama = petugasInput.value.trim();
                if (petugasNama) {
                    addPetugasToList(petugasNama);
                }

                // Simpan satker terakhir yang dipilih ke localStorage
                if (satkerSelect && satkerSelect.value) {
                    localStorage.setItem('yankesdokpol_last_satker_id', satkerSelect.value);
                }

                // Simpan acara terakhir yang dipilih ke cookie (expire 30 hari)
                const acaraSelect = document.getElementById('acaraId');
                if (acaraSelect && acaraSelect.value) {
                    const expiryDate = new Date();
                    expiryDate.setDate(expiryDate.getDate() + 30); // 30 hari kedepan
                    document.cookie = `yankesdokpol_last_acara_id=${acaraSelect.value}; expires=${expiryDate.toUTCString()}; path=/; SameSite=Lax`;
                }
            });

            // Set initial value dari localStorage/cookie jika ada (kecuali sedang edit data)
            if (satkerSelect && !satkerSelect.value) {
                const lastSatkerId = localStorage.getItem('yankesdokpol_last_satker_id');
                if (lastSatkerId) {
                    satkerSelect.value = lastSatkerId;
                }
            }
        });

        // Handler untuk tombol "Update data yang ada"
        document.addEventListener('DOMContentLoaded', function() {
            // Delegasi event untuk tombol yang mungkin ditambahkan secara dinamis
            document.body.addEventListener('click', function(event) {
                if (event.target && (event.target.id === 'updateExisting' || event.target.id === 'updateExistingNewAcara')) {
                    // Ambil NIK dan acara_id dari data attribute
                    const nik = event.target.getAttribute('data-nik');
                    const acaraId = event.target.getAttribute('data-acara-id');
                    if (!nik) return;

                    // Tampilkan loading
                    event.target.innerHTML = 'Loading...';
                    event.target.disabled = true;

                    // Buat AJAX request untuk mendapatkan data peserta
                    // Include acara_id if available for filtering
                    const url = acaraId ?
                        `get_peserta_data.php?nik=${nik}&acara_id=${acaraId}` :
                        `get_peserta_data.php?nik=${nik}`;

                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Tambahkan input hidden berdasarkan jenis tombol yang ditekan
                                const hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';

                                if (event.target.id === 'updateExisting') {
                                    hiddenInput.name = 'update_existing';
                                    hiddenInput.value = '1';
                                }
                                // Untuk updateExistingNewAcara tidak perlu hidden input khusus karena
                                // sudah menyimpan flag di session

                                const form = document.getElementById('registrationForm');
                                form.appendChild(hiddenInput);

                                // Isi form dengan data peserta
                                document.getElementById('nik').value = data.peserta.nik || '';
                                document.getElementById('nik').readOnly = true; // Buat field NIK readonly karena ini adalah primary key
                                document.getElementById('nama').value = data.peserta.nama || '';
                                document.getElementById('alamat').value = data.peserta.alamat || '';
                                document.getElementById('tanggalLahir').value = data.peserta.tanggal_lahir || '';
                                document.getElementById('nomorHP').value = data.peserta.nomor_hp || '';

                                // Set acara_id if available from peserta_layanan
                                if (data.peserta_layanan && data.peserta_layanan.length > 0 && data.peserta_layanan[0].acara_id) {
                                    const acaraSelect = document.getElementById('acaraId');
                                    if (acaraSelect) {
                                        acaraSelect.value = data.peserta_layanan[0].acara_id;
                                    }
                                }
                                // Get hasil_pemeriksaan from peserta_layanan if available
                                if (data.peserta_layanan && data.peserta_layanan.length > 0 && data.peserta_layanan[0].hasil_pemeriksaan) {
                                    document.getElementById('hasilPemeriksaan').value = data.peserta_layanan[0].hasil_pemeriksaan;
                                } else {
                                    document.getElementById('hasilPemeriksaan').value = '';
                                }

                                // Display foto_kegiatan if available
                                if (data.peserta_layanan && data.peserta_layanan.length > 0 && data.peserta_layanan[0].foto_kegiatan) {
                                    const fotoKegiatanPreview = document.createElement('div');
                                    fotoKegiatanPreview.className = 'mt-2';
                                    fotoKegiatanPreview.innerHTML = `
                                    <p>Foto dokumentasi yang tersimpan:</p>
                                    <img src="${data.peserta_layanan[0].foto_kegiatan}" class="img-fluid img-thumbnail" style="max-height: 200px;">
                                `;
                                    const fotoKegiatanContainer = document.getElementById('fotoKegiatan').parentNode;
                                    fotoKegiatanContainer.appendChild(fotoKegiatanPreview);
                                }

                                // Tampilkan gambar KTP jika ada
                                if (data.peserta.file_ktp) {
                                    const ktpPreview = document.getElementById('ktpPreview');
                                    const ktpImage = document.getElementById('ktpImage');
                                    if (ktpPreview && ktpImage) {
                                        ktpImage.src = data.peserta.file_ktp;
                                        ktpPreview.classList.remove('d-none');
                                    }
                                }

                                // Centang layanan yang dipilih
                                if (data.peserta_layanan && data.peserta_layanan.length > 0) {
                                    // Extract all layanan_ids from peserta_layanan
                                    const selectedLayananIds = data.peserta_layanan.map(item => item.layanan_id).filter(id => id);

                                    if (selectedLayananIds.length > 0) {
                                        const checkboxes = document.getElementsByName('layanan[]');
                                        checkboxes.forEach(function(checkbox) {
                                            // Check if this checkbox's value is in the selected layanan IDs
                                            if (selectedLayananIds.includes(parseInt(checkbox.value))) {
                                                checkbox.checked = true;
                                            } else {
                                                checkbox.checked = false;
                                            }
                                        });
                                    }
                                }

                                // Set petugas if available
                                if (data.peserta_layanan && data.peserta_layanan.length > 0 && data.peserta_layanan[0].petugas) {
                                    const petugasInput = document.getElementById('petugasInput');
                                    if (petugasInput) {
                                        petugasInput.value = data.peserta_layanan[0].petugas;
                                    }
                                }

                                // Scroll ke atas form
                                window.scrollTo(0, 0);

                                // Tampilkan pesan sukses
                                const alertDiv = document.createElement('div');
                                alertDiv.className = 'alert alert-success';
                                alertDiv.innerHTML = 'Data peserta berhasil dimuat. Silakan edit dan submit untuk mengupdate.';
                                form.prepend(alertDiv);

                                // Hilangkan pesan error NIK
                                const nikError = document.getElementById('nikError');
                                if (nikError) nikError.innerHTML = '';
                            } else {
                                alert('Gagal memuat data peserta: ' + (data.message || 'Unknown error'));
                            }

                            // Reset tombol
                            event.target.innerHTML = 'Update data yang ada';
                            event.target.disabled = false;
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Terjadi kesalahan saat memuat data peserta');

                            // Reset tombol
                            event.target.innerHTML = 'Update data yang ada';
                            event.target.disabled = false;
                        });
                }
            });
        });
    </script>
</body>

</html>