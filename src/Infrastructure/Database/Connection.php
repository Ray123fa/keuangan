<?php
declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

require_once __DIR__ . '/../../../database.php';

final class Connection
{
    public static function get(): PDO
    {
        return \Database::getConnection();
    }
}
