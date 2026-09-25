<?php

declare(strict_types=1);

namespace App\Support\Database;

use PDO;

/**
 * SQLite, utilisé en développement et pour les tests, n'a pas les fonctions mathématiques de PostgreSQL.
 * On déclare celles que l'application utilise (calcul de distance) pour que la même requête SQL tourne
 * sur les deux bases. Comme en SQL, une valeur NULL donne NULL.
 */
final class SqliteMathFunctions
{
    public static function register(PDO $pdo): void
    {
        $functions = [
            'acos' => fn (mixed $value): ?float => $value === null ? null : acos((float) $value),
            'cos' => fn (mixed $value): ?float => $value === null ? null : cos((float) $value),
            'sin' => fn (mixed $value): ?float => $value === null ? null : sin((float) $value),
            'radians' => fn (mixed $value): ?float => $value === null ? null : deg2rad((float) $value),
            // Comme sous PostgreSQL, LEAST ignore les NULL.
            'least' => function (mixed ...$values): mixed {
                $values = array_filter($values, fn (mixed $value): bool => $value !== null);

                return $values === [] ? null : min($values);
            },
        ];

        foreach ($functions as $name => $callback) {
            $arguments = $name === 'least' ? -1 : 1;

            // Depuis PHP 8.4, Laravel ouvre un Pdo\Sqlite, dont createFunction() remplace PDO::sqliteCreateFunction().
            method_exists($pdo, 'createFunction')
                ? $pdo->createFunction($name, $callback, $arguments)
                : $pdo->sqliteCreateFunction($name, $callback, $arguments);
        }
    }
}
