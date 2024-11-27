<?php

declare(strict_types=1);

namespace Genkgo\Camt;

use Exception;
use Iban\Validation\Iban as IbanDetails;
use Iban\Validation\Validator;
use InvalidArgumentException;

class Iban
{
    private string $iban;

    public function __construct(string $iban)
    {
        $ibanDetails = new IbanDetails($iban);

        if (!(new Validator())->validate($iban)) {
            try {
                $this->iban = $ibanDetails->getNormalizedIban();
            } catch (Exception) {
                throw new InvalidArgumentException("Unknown IBAN {$ibanDetails}");
            }
        }
        $this->iban = $ibanDetails->getNormalizedIban();

        preg_match_all('/([A-Z]{2,2}[0-9]{2,2}[a-zA-Z0-9]{1,30})/m', $this->iban, $matches, PREG_SET_ORDER, 0);
        if (count($matches) === 0) {
            throw new InvalidArgumentException("Unknown IBAN {$ibanDetails}");
        }
    }

    public function getIban(): string
    {
        return $this->iban;
    }

    public function __toString(): string
    {
        return $this->iban;
    }

    public function equals(string $iban): bool
    {
        return (new IbanDetails($iban))->getNormalizedIban() === $this->iban;
    }
}
