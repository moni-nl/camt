<?php

declare(strict_types=1);

namespace Genkgo\Camt\Camt054\DTO;

use DateTimeImmutable;
use Genkgo\Camt\DTO\Account;
use Genkgo\Camt\DTO\Record;

class Notification extends Record
{
    protected ?DateTimeImmutable $createdOn;

    public function __construct(string $id, ?DateTimeImmutable $createdOn, Account $account)
    {
        parent::__construct($id, $account);

        $this->createdOn = $createdOn;
    }

    public function getCreatedOn(): ?DateTimeImmutable
    {
        return $this->createdOn;
    }
}
