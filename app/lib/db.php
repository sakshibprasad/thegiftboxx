<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $cfg = config('db', []);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    if (($cfg['driver'] ?? 'mysql') === 'sqlite') {
        $pdo = new PDO('sqlite:' . $cfg['path'], null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL;');
    } else {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $cfg['host'] ?? 'localhost', (int) ($cfg['port'] ?? 3306), $cfg['name'] ?? '');
        $pdo = new PDO($dsn, $cfg['user'] ?? '', $cfg['pass'] ?? '', $options);
        $pdo->exec("SET time_zone = '+05:30'");
    }
    return $pdo;
}

function db_driver(): string
{
    return config('db.driver', 'mysql');
}

function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function one(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function val(string $sql, array $params = [])
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

function insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table,
        implode(', ', $cols), implode(', ', array_fill(0, count($cols), '?')));
    q($sql, array_values($data));
    return (int) db()->lastInsertId();
}

function update(string $table, array $data, string $where, array $params = []): int
{
    $set = implode(', ', array_map(fn($c) => "{$c} = ?", array_keys($data)));
    return q("UPDATE {$table} SET {$set} WHERE {$where}", array_merge(array_values($data), $params))->rowCount();
}

function transaction(callable $fn)
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $result = $fn();
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Build "?, ?, ?" for IN () clauses. */
function in_list(array $values): string
{
    return implode(', ', array_fill(0, max(1, count($values)), '?'));
}

/** Run schema.sql, adapting it for SQLite when needed. */
function run_schema(): void
{
    $sql = (string) file_get_contents(APP_DIR . '/schema/schema.sql');
    if (db_driver() === 'sqlite') {
        $sql = str_replace('INT UNSIGNED AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/\)\s*ENGINE=InnoDB[^;]*;/', ');', $sql);
    }
    $sql = preg_replace('/^--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', explode(';', (string) $sql))) as $statement) {
        db()->exec($statement);
    }
}
