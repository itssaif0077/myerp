<?php
declare(strict_types=1);

namespace App\Application\Exceptions;

use RuntimeException;

class DuplicateContactException extends RuntimeException
{
    private array $existingContact;

    public function __construct(string $message, array $existingContact)
    {
        parent::__construct($message);
        $this->existingContact = $existingContact;
    }

    public function getExistingContact(): array
    {
        return $this->existingContact;
    }
}
