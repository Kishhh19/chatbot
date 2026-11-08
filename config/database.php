<?php
require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                if (DEBUG_MODE) {
                    throw new RuntimeException('Database connection failed: ' . $e->getMessage());
                }
                throw new RuntimeException('Database connection failed. Please check configuration.');
            }
        }
        return self::$instance;
    }

    public static function close(): void {
        self::$instance = null;
    }

    private function __construct() {}
    private function __clone() {}
}

/**
 * Execute a query with prepared statements
 */
function db_query(string $sql, array $params = []): PDOStatement {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Fetch all rows
 */
function db_fetch_all(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

/**
 * Fetch single row
 */
function db_fetch_one(string $sql, array $params = []): ?array {
    $result = db_query($sql, $params)->fetch();
    return $result ?: null;
}

/**
 * Get last insert ID
 */
function db_last_id(): string {
    return Database::getInstance()->lastInsertId();
}

/**
 * Begin transaction
 */
function db_begin(): void {
    Database::getInstance()->beginTransaction();
}

/**
 * Commit transaction
 */
function db_commit(): void {
    Database::getInstance()->commit();
}

/**
 * Rollback transaction
 */
function db_rollback(): void {
    if (Database::getInstance()->inTransaction()) {
        Database::getInstance()->rollBack();
    }
}
