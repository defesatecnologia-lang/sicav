<?php
require_once __DIR__ . "/config.php";

class database
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        );
    }

    public function setUsuarioAuditoria(int $usuarioId): void
    {
        $stmt = $this->pdo->prepare("SET @usuario_id = :usuario_id");
        $stmt->execute(['usuario_id' => $usuarioId]);
    }

    public function query(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $command = strtolower(explode(' ', trim($sql))[0]);

            switch ($command) {
                case 'select':
                    $results = $stmt->fetchAll();
                    return [
                        'success' => true,
                        'status' => 'success',
                        'data' => $results,
                        'count' => count($results)
                    ];

                case 'insert':
                    return [
                        'success' => true,
                        'status' => 'success',
                        'insert_id' => $this->pdo->lastInsertId()
                    ];

                case 'update':
                case 'delete':
                    return [
                        'success' => true,
                        'status' => 'success',
                        'affected_rows' => $stmt->rowCount()
                    ];

                default:
                    return [
                        'success' => true,
                        'status' => 'success',
                        'message' => 'Comando executado'
                    ];
            }

        } catch (PDOException $err) {
            error_log('DB query error: ' . $err->getMessage());
            return [
                'success' => false,
                'status' => 'error',
                'error' => 'Erro interno de banco de dados',
                'error_code' => $err->getCode()
            ];
        }
    }
}
