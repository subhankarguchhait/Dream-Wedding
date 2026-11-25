<?php
/**
 * PDO database layer. Every query in the project goes through these helpers,
 * always with prepared statements and bound parameters.
 * Table/column names passed to db_insert()/db_update() come from code, never from user input.
 */
declare(strict_types=1);
defined('APP_NAME') || exit; // no direct access

final class DatabaseUnavailable extends RuntimeException {}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $c = require CONFIG_PATH . '/database.php';
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        if ($c['driver'] === 'sqlite') {
            $pdo = new PDO('sqlite:' . $c['sqlite_path'], null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], (int)$c['port'], $c['name'], $c['charset']);
            $pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
            $pdo->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
        }
    } catch (PDOException $e) {
        log_error('Database connection failed: ' . $e->getMessage());
        throw new DatabaseUnavailable('Database connection failed. Check config/database.php.', 0, $e);
    }
    return $pdo;
}

function db_driver(): string
{
    return db()->getAttribute(PDO::ATTR_DRIVER_NAME);
}

function db_run(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    if (array_is_list($params)) {
        foreach ($params as $i => $v) $stmt->bindValue($i + 1, is_bool($v) ? (int)$v : $v, db_type($v));
    } else {
        foreach ($params as $k => $v) $stmt->bindValue(':' . ltrim((string)$k, ':'), is_bool($v) ? (int)$v : $v, db_type($v));
    }
    $stmt->execute();
    return $stmt;
}

function db_type(mixed $v): int
{
    return match (true) {
        is_int($v) => PDO::PARAM_INT,
        is_bool($v) => PDO::PARAM_INT,
        $v === null => PDO::PARAM_NULL,
        default => PDO::PARAM_STR,
    };
}

function db_all(string $sql, array $params = []): array { return db_run($sql, $params)->fetchAll(); }
function db_one(string $sql, array $params = []): ?array { $r = db_run($sql, $params)->fetch(); return $r === false ? null : $r; }
function db_val(string $sql, array $params = []): mixed { $r = db_run($sql, $params)->fetchColumn(); return $r === false ? null : $r; }

function db_insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(', ', $cols), implode(', ', array_fill(0, count($cols), '?')));
    db_run($sql, array_map(fn($v) => is_bool($v) ? (int)$v : $v, array_values($data)));
    return (int)db()->lastInsertId();
}

/** db_update('brands', ['name' => 'x'], 'id = ?', [5]) → affected rows */
function db_update(string $table, array $data, string $where, array $whereParams = []): int
{
    $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
    $params = array_merge(array_map(fn($v) => is_bool($v) ? (int)$v : $v, array_values($data)), $whereParams);
    return db_run("UPDATE $table SET $set WHERE $where", $params)->rowCount();
}

function db_tx(callable $fn): mixed
{
    $pdo = db();
    $pdo->beginTransaction();
    try { $r = $fn(); $pdo->commit(); return $r; }
    catch (Throwable $e) { $pdo->rollBack(); throw $e; }
}

function now(): string { return date('Y-m-d H:i:s'); }
