<?php
// 施設マップ設定ファイル
// このファイルはWeb外に配置されているため直接アクセス不可

// 直接アクセス防止
if (!defined('CONFIG_ACCESS_ALLOWED')) {
    die('Direct access to this file is not allowed.');
}

return [
    // データベース設定
    'database' => [
        'path' => __DIR__ . '/facilities.db',
        'tables' => [
            'facilities' => [
                'columns' => [
                    'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                    'csv_no' => 'TEXT',  // CSVの識別番号
                    'name' => 'TEXT NOT NULL',  // 名称
                    'name_kana' => 'TEXT',  // 名称_カナ
                    'lat' => 'REAL NOT NULL',  // 緯度
                    'lng' => 'REAL NOT NULL',  // 経度
                    'address' => 'TEXT',  // 住所
                    'address_detail' => 'TEXT',  // 方書
                    'installation_position' => 'TEXT',  // アクセス方法
                    'phone' => 'TEXT',  // 電話番号
                    'phone_extension' => 'TEXT',  // 内線番号
                    'corporate_number' => 'TEXT',  // 法人番号
                    'organization_name' => 'TEXT',  // 団体名
                    'available_days' => 'TEXT',  // 利用可能曜日
                    'start_time' => 'TEXT',  // 開始時間
                    'end_time' => 'TEXT',  // 終了時間
                    'available_hours_note' => 'TEXT',  // 利用可能日時特記事項
                    'pediatric_support' => 'TEXT',  // 一時預かりの有無
                    'website' => 'TEXT',  // URL
                    'note' => 'TEXT',  // 備考
                    'category' => 'TEXT',  // カテゴリ（認可公立保育所、認定こども園等）
                    'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
                ],
                'indexes' => [
                    'idx_facilities_location' => ['lat', 'lng'],
                    'idx_facilities_updated_at' => ['updated_at'],
                    'idx_facilities_category' => ['category'],
                    'idx_facilities_csv_no' => ['csv_no']
                ]
            ],
            'facility_images' => [
                'columns' => [
                    'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                    'facility_id' => 'INTEGER NOT NULL',
                    'filename' => 'TEXT NOT NULL',
                    'original_name' => 'TEXT NOT NULL',
                    'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
                ],
                'foreign_keys' => [
                    'facility_id' => [
                        'references' => 'facilities(id)',
                        'on_delete' => 'CASCADE'
                    ]
                ],
                'indexes' => [
                    'idx_facility_images_facility_id' => ['facility_id'],
                    'idx_facility_images_created_at' => ['created_at']
                ]
            ],
            'admin_settings' => [
                'columns' => [
                    'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                    'setting_key' => 'TEXT UNIQUE NOT NULL',
                    'setting_value' => 'TEXT NOT NULL',
                    'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                    'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
                ],
                'indexes' => [
                    'idx_admin_settings_key' => ['setting_key'],
                    'idx_admin_settings_updated_at' => ['updated_at']
                ]
            ]
        ],
        'drop_order' => ['facility_images', 'facilities', 'admin_settings']
    ],
    
    // 管理者設定
    'admin' => [
        'password' => 'admin123',  // 初期パスワード（初回設定後に変更推奨）
        'session_timeout' => 1800  // 30分（秒）
    ],
    
    // アプリケーション設定
    'app' => [
        'name' => 'おやま子ども施設マップ',
        'version' => '1.0.0',
        'timezone' => 'Asia/Tokyo',
        'facility_name' => '子育て支援施設',  // 施設の呼称
        'categories' => [
            '認可公立保育所',
            '認可私立保育所',
            '認定こども園（幼保連携型）',
            '私立幼稚園',
            '認可外保育所',
            '放課後児童クラブ',
            '児童館'
        ],
        'field_labels' => [
            'name' => '施設名',
            'name_kana' => '施設名（カナ）',
            'category' => '種別',
            'address' => '住所',
            'address_detail' => '方書',
            'installation_position' => 'アクセス方法',
            'phone' => '電話番号',
            'phone_extension' => '内線番号',
            'corporate_number' => '法人番号',
            'organization_name' => '団体名',
            'available_days' => '利用可能曜日',
            'start_time' => '開始時間',
            'end_time' => '終了時間',
            'available_hours_note' => '利用可能日時特記事項',
            'pediatric_support' => '一時預かりの有無',
            'website' => 'ウェブサイト',
            'note' => '備考',
            'images' => '画像',
            'location' => '位置情報'
        ]
    ],
    
    // 地図設定
    'map' => [
        'initial_latitude' => 36.3141,   // 初期表示緯度（小山市中心）
        'initial_longitude' => 139.8006, // 初期表示経度（小山市中心）
        'initial_zoom' => 14             // 初期ズームレベル
    ],
    
    // セキュリティ設定
    'security' => [
        'max_image_size' => 5 * 1024 * 1024,  // 5MB
        'max_images_per_facility' => 10,
        'max_review_length' => 2000
    ],
    
    // ストレージ設定
    'storage' => [
        'images_dir' => 'facility_images',
        'database_file' => 'facilities.db'
    ],
    
    // CSVインポート設定
    'csv_import' => [
        'encoding' => 'UTF-8',
        'has_header' => true,
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'allowed_extensions' => ['csv'],
        'allowed_mime_types' => ['text/csv', 'text/plain', 'application/csv'],
        'field_mapping' => [
            // フィールド名 => CSV列番号（0ベース）
            'csv_no' => 1,
            'name' => 4,
            'name_kana' => 5,
            'category' => 6,
            'address' => 7,
            'address_detail' => 8,
            'lat' => 9,
            'lng' => 10,
            'installation_position' => 11,
            'phone' => 13,
            'phone_extension' => 14,
            'corporate_number' => 16,
            'organization_name' => 17,
            'available_days' => 20,
            'start_time' => 22,
            'end_time' => 23,
            'available_hours_note' => 24,
            'pediatric_support' => 25,
            'website' => 26,
            'note' => 27
        ],
        'required_fields' => ['name', 'lat', 'lng'],
        'default_values' => [
            'pediatric_support' => '無'
        ],
        'validation' => [
            'lat_min' => 24,
            'lat_max' => 46,
            'lng_min' => 123,
            'lng_max' => 146,
            'expected_columns' => 28
        ]
    ],
    
    // サンプルデータ設定
    'sample_data' => [
        [
            'csv_no' => '0107000001',
            'name' => 'やはた保育所',
            'name_kana' => 'ヤハタホイクショ',
            'category' => '認可公立保育所',
            'lat' => 36.308707,
            'lng' => 139.797489,
            'address' => '栃木県小山市八幡町2-8-8',
            'address_detail' => '',
            'installation_position' => '',
            'phone' => '(0285)21-2725',
            'phone_extension' => '',
            'corporate_number' => '',
            'organization_name' => '',
            'available_days' => '月火水木金土',
            'start_time' => '07:30',
            'end_time' => '19:30',
            'available_hours_note' => '日曜・祝祭日・年末年始はお休み。',
            'pediatric_support' => '有',
            'website' => '',
            'note' => ''
        ],
        [
            'csv_no' => '0107000034',
            'name' => '認定とまとこども園',
            'name_kana' => 'ニンテイトマトコドモエン',
            'category' => '認定こども園（幼保連携型）',
            'lat' => 36.304240,
            'lng' => 139.738431,
            'address' => '栃木県小山市下泉488-3',
            'address_detail' => '',
            'installation_position' => '',
            'phone' => '(0285)38-3121',
            'phone_extension' => '',
            'corporate_number' => '',
            'organization_name' => '',
            'available_days' => '月火水木金土',
            'start_time' => '07:00',
            'end_time' => '19:00',
            'available_hours_note' => '日曜・祝祭日・年末年始はお休み。',
            'pediatric_support' => '有',
            'website' => '',
            'note' => ''
        ],
        [
            'csv_no' => '0107000124',
            'name' => '駅南児童センター',
            'name_kana' => 'エキミナミジドウセンター',
            'category' => '児童館',
            'lat' => 36.302710,
            'lng' => 139.805660,
            'address' => '栃木県小山市駅南町2-11-5',
            'address_detail' => '',
            'installation_position' => '',
            'phone' => '(0285)27-0594',
            'phone_extension' => '',
            'corporate_number' => '',
            'organization_name' => '',
            'available_days' => '火水木金土日',
            'start_time' => '09:30',
            'end_time' => '18:00',
            'available_hours_note' => '月曜・祝祭日等・年末年始はお休み',
            'pediatric_support' => '無',
            'website' => '',
            'note' => ''
        ]
    ]
];
?>