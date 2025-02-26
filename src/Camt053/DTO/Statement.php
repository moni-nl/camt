<?php

declare(strict_types=1);

namespace Genkgo\Camt\Camt053\DTO;

use Genkgo\Camt\DTO\RecordWithBalances;
use Money\Money;

class Statement extends RecordWithBalances
{
    private ?int $totalEntries = null;

    private ?string $totalSum = null;

    private ?string $totalCreditDebitIndicator = null;

    private ?string $totalNetAmount = null;

    private ?string $debitSum = null;

    private ?int $totalDebitEntries = null;

    private ?string $creditSum = null;

    private ?int $totalCreditEntries = null;

    public function getTotalEntries(): ?int
    {
        return $this->totalEntries;
    }

    public function setTotalEntries(?int $totalEntries): void
    {
        $this->totalEntries = $totalEntries;
    }

    public function getTotalSum(): ?string
    {
        return $this->totalSum;
    }

    public function setTotalSum(?string $totalSum): void
    {
        $this->totalSum = $totalSum;
    }

    public function getTotalCreditDebitIndicator(): ?string
    {
        return $this->totalCreditDebitIndicator;
    }

    public function setTotalCreditDebitIndicator(?string $totalCreditDebitIndicator): void
    {
        $this->totalCreditDebitIndicator = $totalCreditDebitIndicator;
    }

    public function getTotalNetAmount(): ?string
    {
        return $this->totalNetAmount;
    }

    public function setTotalNetAmount(?string $totalNetAmount): void
    {
        $this->totalNetAmount = $totalNetAmount;
    }

    public function getDebitSum(): ?string
    {
        return $this->debitSum;
    }

    public function setDebitSum(?string $debitSum): void
    {
        $this->debitSum = $debitSum;
    }

    public function getTotalDebitEntries(): ?int
    {
        return $this->totalDebitEntries;
    }

    public function setTotalDebitEntries(?int $totalDebitEntries): void
    {
        $this->totalDebitEntries = $totalDebitEntries;
    }

    public function getCreditSum(): ?string
    {
        return $this->creditSum;
    }

    public function setCreditSum(?string $creditSum): void
    {
        $this->creditSum = $creditSum;
    }

    public function getTotalCreditEntries(): ?int
    {
        return $this->totalCreditEntries;
    }

    public function setTotalCreditEntries(?int $totalCreditEntries): void
    {
        $this->totalCreditEntries = $totalCreditEntries;
    }
}
