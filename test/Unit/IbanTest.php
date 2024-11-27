<?php

declare(strict_types=1);

namespace Genkgo\TestCamt\Unit;

use Generator;
use Genkgo\Camt\Iban;
use InvalidArgumentException;
use PHPUnit\Framework;

class IbanTest extends Framework\TestCase
{
    public function testValidIbanMachineFormat(): void
    {
        $iban = new Iban($expected = 'NL91ABNA0417164300');

        self::assertEquals($expected, $iban->getIban());
        self::assertEquals($expected, $iban);
    }

    public function testValidIbanHumanFormat(): void
    {
        $expected = 'NL91ABNA0417164300';

        $iban = new Iban('IBAN NL91 ABNA 0417 1643 00');

        self::assertEquals($expected, $iban->getIban());
        self::assertEquals($expected, $iban);
    }

    public function testInvalidIban(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Iban('fdsfdsafdsafdasfdsafdsa');
    }

    /**
     * @dataProvider validIbanIso2007DataProvider
     */
    public function testCamtSpecifiedIBAN(string $inputIban, string $expectedIban): void
    {
        $iban = new Iban($inputIban);

        self::assertEquals($expectedIban, $iban->getIban());
    }

    public static function validIbanIso2007DataProvider(): Generator
    {
        yield 'valid iban with text' => ['NL02ABNA0123456789 EXAMPLE', 'NL02ABNA0123456789EXAMPLE'];
        yield 'IBAN2007Identifier with invalid iban' => ['02ABNA0123456789 EXAMPLE', '02ABNA0123456789EXAMPLE'];
        yield 'invalid iban gets formatted' => ['NL 91 ABNA 0417164301', 'NL91ABNA0417164301'];
    }
}
