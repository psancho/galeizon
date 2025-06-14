<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Model\Database;

use UnexpectedValueException;

class Paging
{
    public int $offset;

    public function __construct(
        public int $perPage = 1,
        public int $page = 1,
    )
    {
        if ($perPage < 1 || $page < 1) {
            throw new UnexpectedValueException("PAGING: positive values required", 1);
        }
        $this->offset = $this->perPage * ($this->page - 1);
    }
}
