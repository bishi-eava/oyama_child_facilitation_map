<?php
// 施設フォーム共通関数
// admin_add.php と admin_edit.php で共通使用される処理

// auth_check.phpの関数を使用
require_once 'auth_check.php';

// 時刻をHTML time input用にフォーマットする関数
function formatTimeForInput($time) {
    if (empty($time)) return '';
    // 既に HH:MM 形式の場合はそのまま返す
    if (preg_match('/^\d{2}:\d{2}$/', $time)) return $time;
    // H:MM 形式を HH:MM 形式に変換
    if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
        return sprintf('%02d:%02d', intval($matches[1]), intval($matches[2]));
    }
    return $time;
}

// POSTデータから施設データを取得・サニタイズする関数
function extractFacilityDataFromPost() {
    return [
        'csv_no' => mb_substr($_POST['csv_no'] ?? '', 0, 50),
        'name' => mb_substr($_POST['name'], 0, 100),
        'name_kana' => mb_substr($_POST['name_kana'] ?? '', 0, 100),
        'address' => mb_substr($_POST['address'] ?? '', 0, 200),
        'address_detail' => mb_substr($_POST['address_detail'] ?? '', 0, 200),
        'installation_position' => mb_substr($_POST['installation_position'] ?? '', 0, 200),
        'phone' => mb_substr($_POST['phone'] ?? '', 0, 50),
        'phone_extension' => mb_substr($_POST['phone_extension'] ?? '', 0, 20),
        'corporate_number' => mb_substr($_POST['corporate_number'] ?? '', 0, 50),
        'organization_name' => mb_substr($_POST['organization_name'] ?? '', 0, 100),
        'available_days' => mb_substr($_POST['available_days'] ?? '', 0, 50),
        'start_time' => mb_substr($_POST['start_time'] ?? '', 0, 10),
        'end_time' => mb_substr($_POST['end_time'] ?? '', 0, 10),
        'available_hours_note' => mb_substr($_POST['available_hours_note'] ?? '', 0, 500),
        'pediatric_support' => empty($_POST['pediatric_support']) ? '無' : $_POST['pediatric_support'],
        'website' => mb_substr($_POST['website'] ?? '', 0, 200),
        'note' => mb_substr($_POST['note'] ?? '', 0, 1000),
        'category' => $_POST['category'] ?? '',
        'lat' => floatval($_POST['lat']),
        'lng' => floatval($_POST['lng'])
    ];
}

// 施設データの基本検証
function validateFacilityData($data, $config) {
    $errors = [];
    
    
    // 文字数チェック
    if (mb_strlen($data['note']) > 1000) {
        $errors[] = "{$config['app']['field_labels']['note']}は1000文字以内で入力してください";
        return $errors;
    }

    if (empty($data['name']) || !$data['lat'] || !$data['lng'] || empty($data['category'])) {
        $errors[] = "必要な項目が入力されていません";
    }
    
    return $errors;
}

// 施設名の重複チェック
function checkFacilityNameDuplicate($db, $name, $config, $excludeId = null) {
    if ($excludeId) {
        // 編集時：自分以外で同じ名前があるかチェック
        $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM facilities WHERE name = :name AND id != :id');
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':id', $excludeId, SQLITE3_INTEGER);
    } else {
        // 新規時：同じ名前があるかチェック
        $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM facilities WHERE name = :name');
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    }
    
    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);
    
    if ($row['cnt'] > 0) {
        $errorMsg = "同じ名前の{$config['app']['facility_name']}が既に登録されています";
        return $errorMsg;
    }
    
    return null;
}

// 施設データをデータベースに保存
function saveFacilityData($db, $data, $config, $facilityId = null) {
    $japanTime = date('Y-m-d H:i:s', time());
 
    // SQLiteデータベースロック対策
    $db->busyTimeout(30000); // 30秒のタイムアウト
    $db->exec('PRAGMA journal_mode = WAL;'); // WALモードでロック問題を軽減
    
    if ($facilityId) {
        // 更新（csv_noは更新しない - 元のコードと同じ動作）
        $sql = 'UPDATE facilities SET name = :name, name_kana = :name_kana, lat = :lat, lng = :lng, address = :address, address_detail = :address_detail, installation_position = :installation_position, phone = :phone, phone_extension = :phone_extension, corporate_number = :corporate_number, organization_name = :organization_name, available_days = :available_days, start_time = :start_time, end_time = :end_time, available_hours_note = :available_hours_note, pediatric_support = :pediatric_support, website = :website, note = :note, category = :category, updated_at = :updated_at WHERE id = :id';
        $stmt = $db->prepare($sql);
    } else {
        // 新規作成（csv_noは空で作成）
        $sql = 'INSERT INTO facilities (name, name_kana, lat, lng, address, address_detail, installation_position, phone, phone_extension, corporate_number, organization_name, available_days, start_time, end_time, available_hours_note, pediatric_support, website, note, category, updated_at) VALUES (:name, :name_kana, :lat, :lng, :address, :address_detail, :installation_position, :phone, :phone_extension, :corporate_number, :organization_name, :available_days, :start_time, :end_time, :available_hours_note, :pediatric_support, :website, :note, :category, :updated_at)';
        $stmt = $db->prepare($sql);
    }
    
    // prepare()が失敗していないかチェック
    if (!$stmt) {
        $errorInfo = $db->lastErrorMsg();
        $errorCode = $db->lastErrorCode();
        return false;
    }
    
    // データバインディング
    $stmt->bindValue(':name', $data['name'], SQLITE3_TEXT);
    $stmt->bindValue(':name_kana', $data['name_kana'], SQLITE3_TEXT);
    $stmt->bindValue(':lat', $data['lat'], SQLITE3_FLOAT);
    $stmt->bindValue(':lng', $data['lng'], SQLITE3_FLOAT);
    $stmt->bindValue(':address', $data['address'], SQLITE3_TEXT);
    $stmt->bindValue(':address_detail', $data['address_detail'], SQLITE3_TEXT);
    $stmt->bindValue(':installation_position', $data['installation_position'], SQLITE3_TEXT);
    $stmt->bindValue(':phone', $data['phone'], SQLITE3_TEXT);
    $stmt->bindValue(':phone_extension', $data['phone_extension'], SQLITE3_TEXT);
    $stmt->bindValue(':corporate_number', $data['corporate_number'], SQLITE3_TEXT);
    $stmt->bindValue(':organization_name', $data['organization_name'], SQLITE3_TEXT);
    $stmt->bindValue(':available_days', $data['available_days'], SQLITE3_TEXT);
    $stmt->bindValue(':start_time', $data['start_time'], SQLITE3_TEXT);
    $stmt->bindValue(':end_time', $data['end_time'], SQLITE3_TEXT);
    $stmt->bindValue(':available_hours_note', $data['available_hours_note'], SQLITE3_TEXT);
    $stmt->bindValue(':pediatric_support', $data['pediatric_support'], SQLITE3_TEXT);
    $stmt->bindValue(':website', $data['website'], SQLITE3_TEXT);
    $stmt->bindValue(':note', $data['note'], SQLITE3_TEXT);
    $stmt->bindValue(':category', $data['category'], SQLITE3_TEXT);
    $stmt->bindValue(':updated_at', $japanTime, SQLITE3_TEXT);
    
    if ($facilityId) {
        $stmt->bindValue(':id', $facilityId, SQLITE3_INTEGER);
    }
    
    $result = $stmt->execute();
    
    
    if ($result) {
        if ($facilityId) {
            // 編集の場合、元のIDを返す
            return $facilityId;
        } else {
            // 新規作成の場合、新しいIDを返す
            return $db->lastInsertRowID();
        }
    }
    
    return false;
}

// 画像ファイルの処理（新規・追加）
function processFacilityImages($db, $facilityId, $config, $isNewImages = false) {
    $fileKey = $isNewImages ? 'new_images' : 'images';
    
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'][0] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'message' => ''];
    }
    
    $uploadDir = __DIR__ . '/' . $config['storage']['images_dir'] . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $files = $_FILES[$fileKey];
    $fileCount = count($files['name']);
    
    // 画像数制限チェック
    if ($isNewImages) {
        // 編集時：既存画像数を取得
        $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM facility_images WHERE facility_id = :facility_id');
        $stmt->bindValue(':facility_id', $facilityId, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        $currentImageCount = $row['cnt'];
        
        if (($currentImageCount + $fileCount) > 10) {
            return [
                'success' => false, 
                'message' => "{$config['app']['field_labels']['images']}は合計で最大10枚まで登録可能です"
            ];
        }
    } else {
        // 新規時
        if ($fileCount > 10) {
            return [
                'success' => false, 
                'message' => "{$config['app']['field_labels']['images']}は最大10枚まで選択可能です"
            ];
        }
    }
    
    $processedCount = 0;
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $fileName = $files['name'][$i];
            $fileTmpName = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            
            // ファイルサイズチェック（5MB）
            if ($fileSize > 5 * 1024 * 1024) {
                return [
                    'success' => false, 
                    'message' => "{$fileName} のサイズが5MBを超えています"
                ];
            }
            
            // ファイル形式チェック
            $imageInfo = getimagesize($fileTmpName);
            if ($imageInfo === false) {
                return [
                    'success' => false, 
                    'message' => "{$fileName} は有効な画像ファイルではありません"
                ];
            }
            
            // ファイル名生成（重複防止）
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = $facilityId . '_' . uniqid() . '.' . $extension;
            $filePath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmpName, $filePath)) {
                // データベースに画像情報を保存
                $stmt = $db->prepare('INSERT INTO facility_images (facility_id, filename, original_name) VALUES (:facility_id, :filename, :original_name)');
                $stmt->bindValue(':facility_id', $facilityId, SQLITE3_INTEGER);
                $stmt->bindValue(':filename', $newFileName, SQLITE3_TEXT);
                $stmt->bindValue(':original_name', $fileName, SQLITE3_TEXT);
                $stmt->execute();
                $processedCount++;
            }
        }
    }
    
    return [
        'success' => true, 
        'message' => $processedCount > 0 ? "{$processedCount}枚の画像を処理しました" : ''
    ];
}

// 画像削除処理
function deleteFacilityImage($db, $imageId, $facilityId, $config) {
    $stmt = $db->prepare('SELECT filename FROM facility_images WHERE id = :id AND facility_id = :facility_id');
    $stmt->bindValue(':id', $imageId, SQLITE3_INTEGER);
    $stmt->bindValue(':facility_id', $facilityId, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $imageRow = $res->fetchArray(SQLITE3_ASSOC);
    
    if ($imageRow) {
        // ファイルを削除
        $filePath = __DIR__ . '/' . $config['storage']['images_dir'] . '/' . $imageRow['filename'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // データベースから削除
        $db->exec("DELETE FROM facility_images WHERE id = $imageId");
        
        return "{$config['app']['field_labels']['images']}を削除しました";
    }
    
    return null;
}

// 施設の既存画像を取得
function getFacilityImages($db, $facilityId) {
    $images = [];
    $stmt = $db->prepare('SELECT id, filename, original_name FROM facility_images WHERE facility_id = :facility_id ORDER BY id');
    $stmt->bindValue(':facility_id', $facilityId, SQLITE3_INTEGER);
    $res = $stmt->execute();
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $images[] = $row;
    }
    return $images;
}

// 完全な施設フォーム処理（新規・編集統合版）
function processFacilityForm($facilityId = null) {
    $config = getConfig();
    $message = '';
    $messageType = '';
    
    // CSRF対策
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        return [
            'success' => false,
            'message' => 'セキュリティエラーが発生しました。再度お試しください。',
            'messageType' => 'error'
        ];
    }
    
    // データ取得・サニタイズ
    $data = extractFacilityDataFromPost();
    
    // データ検証
    $validationErrors = validateFacilityData($data, $config);
    if (!empty($validationErrors)) {
        return [
            'success' => false,
            'message' => implode('<br>', $validationErrors),
            'messageType' => 'error'
        ];
    }
    
    $db = getDatabase();
    
    // 名前重複チェック
    $duplicateError = checkFacilityNameDuplicate($db, $data['name'], $config, $facilityId);
    if ($duplicateError) {
        return [
            'success' => false,
            'message' => $duplicateError,
            'messageType' => 'error'
        ];
    }
    
    // 施設データ保存
    $savedFacilityId = saveFacilityData($db, $data, $config, $facilityId);
    
    if (!$savedFacilityId) {
        return [
            'success' => false,
            'message' => $facilityId ? 
                "{$config['app']['facility_name']}情報の更新に失敗しました" : 
                "{$config['app']['facility_name']}の登録に失敗しました",
            'messageType' => 'error'
        ];
    }
    
    // 画像処理
    $imageKey = $facilityId ? 'new_images' : 'images';
    $imageResult = processFacilityImages($db, $savedFacilityId, $config, $facilityId ? true : false);
    if (!$imageResult['success']) {
        return [
            'success' => false,
            'message' => $imageResult['message'],
            'messageType' => 'error'
        ];
    }
    
    // 成功メッセージ
    $successMessage = $facilityId ? 
        "{$config['app']['facility_name']}情報を正常に更新しました" : 
        "{$config['app']['facility_name']}を正常に登録しました";
    
    if ($imageResult['message']) {
        $successMessage .= '<br>' . $imageResult['message'];
    }
    
    return [
        'success' => true,
        'message' => $successMessage,
        'messageType' => 'success',
        'facilityId' => $savedFacilityId,
        'clearForm' => !$facilityId  // 新規登録時のみフォームをクリア
    ];
}
?>