<?php
declare(strict_types=1);

namespace FpDbTest;

/**
 * Interface DatabaseInterface
 */
interface DatabaseInterface
{
    /**
     * @param string $query
     * @param array $args
     * @return string
     */
    public function buildQuery(string $query, array $args = []): string;

    /**
     * @return mixed
     */
    public function skip();
}
