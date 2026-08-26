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

        // 1. Create plans table
        $plansSql = "CREATE TABLE IF NOT EXISTS plans (
            `id` {$autoIncrement},
            `slug` VARCHAR(50) NOT NULL UNIQUE,
            `name` VARCHAR(100) NOT NULL,
            `price_monthly` DECIMAL(10,2) DEFAULT 0.00,
            `max_devices` INT DEFAULT 1,
            `max_resolution` VARCHAR(20) DEFAULT '1080p',
            `ai_features` TINYINT(1) DEFAULT 0,
            `cloud_rendering` TINYINT(1) DEFAULT 0,
            `team_collaboration` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($plansSql);

        // Seed 3 default packages if empty
        $planCountStmt = $db->query("SELECT COUNT(*) FROM plans");
        if ((int)$planCountStmt->fetchColumn() === 0) {
            $seedPlans = [
                ['slug' => 'starter', 'name' => 'Starter Package', 'price' => 0.00, 'devices' => 1, 'res' => '1080p', 'ai' => 0, 'cloud' => 0, 'team' => 0],
                ['slug' => 'professional', 'name' => 'Professional Package', 'price' => 29.00, 'devices' => 3, 'res' => '4K', 'ai' => 1, 'cloud' => 1, 'team' => 0],
                ['slug' => 'teams', 'name' => 'Teams Studio Package', 'price' => 79.00, 'devices' => 10, 'res' => '8K', 'ai' => 1, 'cloud' => 1, 'team' => 1]
            ];
            $insertPlanStmt = $db->prepare("INSERT INTO plans (`slug`, `name`, `price_monthly`, `max_devices`, `max_resolution`, `ai_features`, `cloud_rendering`, `team_collaboration`) VALUES (:slug, :name, :price, :devices, :res, :ai, :cloud, :team)");
            foreach ($seedPlans as $p) {
                $insertPlanStmt->execute([
                    ':slug' => $p['slug'],
                    ':name' => $p['name'],
                    ':price' => $p['price'],
                    ':devices' => $p['devices'],
                    ':res' => $p['res'],
                    ':ai' => $p['ai'],
                    ':cloud' => $p['cloud'],
                    ':team' => $p['team']
                ]);
            }
        }
        
        // 2. Create users table with plan_id Foreign Key
        $sql = "CREATE TABLE IF NOT EXISTS users (
            `id` {$autoIncrement},
            `name` VARCHAR(150) NOT NULL,
            `email` VARCHAR(191) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `role` VARCHAR(30) DEFAULT 'user',
            `user_type` VARCHAR(50) NOT NULL,
            `plan_id` INT DEFAULT 1,
            `plan_expires_at` DATETIME NULL,
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
            `restriction_reason` VARCHAR(255) NULL,
            `license_key` VARCHAR(100) NULL,
            `waitlist_number` INT DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `last_login_at` DATETIME NULL
        )";

        $db->exec($sql);

        // Safe Auto-migrations for existing databases
        try {
            if ($isSqlite) {
                $cols = $db->query("PRAGMA table_info(users)")->fetchAll();
                $existingCols = array_column($cols, 'name');
                if (!in_array('plan_id', $existingCols)) {
                    $db->exec("ALTER TABLE users ADD COLUMN `plan_id` INT DEFAULT 1");
                }
                if (!in_array('plan_expires_at', $existingCols)) {
                    $db->exec("ALTER TABLE users ADD COLUMN `plan_expires_at` DATETIME NULL");
                }
                if (!in_array('restriction_reason', $existingCols)) {
                    $db->exec("ALTER TABLE users ADD COLUMN `restriction_reason` VARCHAR(255) NULL");
                }
                if (!in_array('license_key', $existingCols)) {
                    $db->exec("ALTER TABLE users ADD COLUMN `license_key` VARCHAR(100) NULL");
                }
            } else {
                try { $db->exec("ALTER TABLE users ADD COLUMN `password_hash` VARCHAR(255) NULL"); } catch (Exception $e) {}
                try { $db->exec("UPDATE users SET `password_hash` = `password` WHERE `password_hash` IS NULL AND `password` IS NOT NULL"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `user_type` VARCHAR(50) DEFAULT 'student'"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `plan_id` INT DEFAULT 1"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `plan_expires_at` DATETIME NULL"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `institution` VARCHAR(255) DEFAULT ''"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `faculty_major` VARCHAR(255) DEFAULT ''"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `graduation_year` INT NULL"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `student_id_number` VARCHAR(100) NULL"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `current_role` VARCHAR(150) NULL"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `portfolio_url` VARCHAR(255) NULL"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `preferred_os` VARCHAR(50) DEFAULT 'windows'"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `primary_use_case` VARCHAR(255) NULL"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `referral_source` VARCHAR(150) NULL"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `status` VARCHAR(50) DEFAULT 'active'"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `restriction_reason` VARCHAR(255) NULL"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `license_key` VARCHAR(100) NULL"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `waitlist_number` INT DEFAULT 0"); } catch (Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN `last_login_at` DATETIME NULL"); } catch (Exception $e) {}
            }
        } catch (Exception $migEx) {
            // Migration already applied or silent skip
        }

        // Upsert initial admin@nex-design.online user
        $adminPass = password_hash('NexAdmin2026!', PASSWORD_BCRYPT);
        $checkAdmin = $db->prepare("SELECT id FROM users WHERE `email` = 'admin@nex-design.online'");
        $checkAdmin->execute();
        if (!$checkAdmin->fetch()) {
            $adminStmt = $db->prepare("INSERT INTO users (
                `name`, `email`, `password_hash`, `role`, `user_type`, `plan_id`, `institution`, `faculty_major`, `graduation_year`, `preferred_os`, `status`, `waitlist_number`
            ) VALUES (
                'Nex Administrator', 'admin@nex-design.online', :pass, 'admin', 'professional', 3, 'Nex Studio HQ', 'Product Design & Engineering', 2024, 'windows', 'active', 0
            )");
            $adminStmt->execute([':pass' => $adminPass]);
        } else {
            $updateAdmin = $db->prepare("UPDATE users SET `password_hash` = :pass, `role` = 'admin', `status` = 'active' WHERE `email` = 'admin@nex-design.online'");
            $updateAdmin->execute([':pass' => $adminPass]);
        }
    }
}
