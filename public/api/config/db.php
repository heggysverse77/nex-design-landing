<?php
// public/api/config/db.php
require_once __DIR__ . '/env.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $dbDriver = Env::get('DB_DRIVER', 'mysql');
        $dbHost = Env::get('DB_HOST', '127.0.0.1');
        $dbPort = Env::get('DB_PORT', '3306');
        $dbName = Env::get('DB_DATABASE', '');
        $dbUser = Env::get('DB_USERNAME', '');
        $dbPass = Env::get('DB_PASSWORD', '');

        $configFile = __DIR__ . '/config.local.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $dbDriver = $config['driver'] ?? $dbDriver;
            $dbHost = $config['host'] ?? $dbHost;
            $dbPort = $config['port'] ?? $dbPort;
            $dbName = $config['database'] ?? $dbName;
            $dbUser = $config['username'] ?? $dbUser;
            $dbPass = $config['password'] ?? $dbPass;
        }


        try {
            if ($dbDriver === 'mysql' && !empty($dbName) && !empty($dbUser)) {
                $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                self::$instance = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } else {
                $dataDir = __DIR__ . '/../data';
                if (!is_dir($dataDir)) {
                    mkdir($dataDir, 0755, true);
                }
                
                $htaccess = $dataDir . '/.htaccess';
                if (!file_exists($htaccess)) {
                    file_put_contents($htaccess, "Require all denied\nDeny from all\n");
                }

                $sqlitePath = $dataDir . '/nex_database.sqlite';
                self::$instance = new PDO("sqlite:" . $sqlitePath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$instance->exec("PRAGMA journal_mode = WAL;");
                self::$instance->exec("PRAGMA foreign_keys = ON;");
            }

            self::initTables(self::$instance);

            return self::$instance;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    private static function initTables(PDO $db): void {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $isSqlite = ($driver === 'sqlite');
        
        $autoIncrement = $isSqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
        
        $sql = "CREATE TABLE IF NOT EXISTS users (
            `id` {$autoIncrement},
            `name` VARCHAR(150) NOT NULL,
            `email` VARCHAR(191) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `role` VARCHAR(30) DEFAULT 'user',
            `user_type` VARCHAR(50) NOT NULL,
            `institution` VARCHAR(255) NOT NULL,
            `faculty_major` VARCHAR(255) NOT NULL,
            `graduation_year` INT NULL,
            `student_id_number` VARCHAR(100) NULL,
            `current_role` VARCHAR(150) NULL,
            `portfolio_url` VARCHAR(255) NULL,
            `preferred_os` VARCHAR(50) NOT NULL,
            `primary_use_case` VARCHAR(255) NULL,
            `referral_source` VARCHAR(150) NULL,
            `status` VARCHAR(50) DEFAULT 'pending',
            `waitlist_number` INT DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `last_login_at` DATETIME NULL
        )";

        $db->exec($sql);

        // Seed initial admin if not present
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE `role` = 'admin'");
        $stmt->execute();
        if ((int)$stmt->fetchColumn() === 0) {
            $adminPass = password_hash('NexAdmin2026!', PASSWORD_BCRYPT);
            $adminStmt = $db->prepare("INSERT INTO users (
                `name`, `email`, `password_hash`, `role`, `user_type`, `institution`, `faculty_major`, `graduation_year`, `preferred_os`, `status`, `waitlist_number`
            ) VALUES (
                'Nex Administrator', 'admin@nex-design.online', :pass, 'admin', 'professional', 'Nex Studio HQ', 'Product Design & Engineering', 2024, 'windows', 'active', 0
            )");
            $adminStmt->execute([':pass' => $adminPass]);
        }
    }
}
