<?php

declare(strict_types=1);

namespace Genkgo\Camt\Decoder;

use DateTimeImmutable;
use Genkgo\Camt\Camt053\DTO\Statement;
use Genkgo\Camt\DTO;
use Genkgo\Camt\DTO\RecordWithBalances;
use Genkgo\Camt\Util\MoneyFactory;
use SimpleXMLElement;

class Record
{
    private Entry $entryDecoder;

    private DateDecoderInterface $dateDecoder;

    private MoneyFactory $moneyFactory;

    /**
     * Record constructor.
     */
    public function __construct(Entry $entryDecoder, DateDecoderInterface $dateDecoder)
    {
        $this->entryDecoder = $entryDecoder;
        $this->dateDecoder = $dateDecoder;
        $this->moneyFactory = new MoneyFactory();
    }

    public function addBalances(RecordWithBalances $record, SimpleXMLElement $xmlRecord): void
    {
        $xmlBalances = $xmlRecord->Bal;
        foreach ($xmlBalances as $xmlBalance) {
            $money = $this->moneyFactory->create($xmlBalance->Amt, $xmlBalance->CdtDbtInd);
            $date = $this->fromDateAndDateTimeChoice($xmlBalance->Dt);

            if (!isset($xmlBalance->Tp, $xmlBalance->Tp->CdOrPrtry)) {
                continue;
            }
            $code = (string) $xmlBalance->Tp->CdOrPrtry->Cd;

            switch ($code) {
                case 'OPBD':
                    $record->addBalance(DTO\Balance::opening(
                        $money,
                        $date
                    ));

                    break;
                case 'OPAV':
                    $record->addBalance(DTO\Balance::openingAvailable(
                        $money,
                        $date
                    ));

                    break;
                case 'CLBD':
                case 'PRCD':
                    $record->addBalance(DTO\Balance::closing(
                        $money,
                        $date
                    ));

                    break;
                case 'CLAV':
                    $record->addBalance(DTO\Balance::closingAvailable(
                        $money,
                        $date
                    ));

                    break;
                case 'FWAV':
                    $record->addBalance(DTO\Balance::forwardAvailable(
                        $money,
                        $date
                    ));

                    break;
                case 'INFO':
                    $record->addBalance(DTO\Balance::information(
                        $money,
                        $date
                    ));

                    break;
                case 'ITAV':
                    $record->addBalance(DTO\Balance::interimAvailable(
                        $money,
                        $date
                    ));

                    break;
                case 'ITBD':
                    $record->addBalance(DTO\Balance::interim(
                        $money,
                        $date
                    ));

                    break;

                case 'XPCD':
                    $record->addBalance(DTO\Balance::expectedCredit(
                        $money,
                        $date
                    ));

                    break;
                default:
                    break;
            }
        }
    }

    public function addEntries(DTO\Record $record, SimpleXMLElement $xmlRecord): void
    {
        $index = 0;
        $xmlEntries = $xmlRecord->Ntry;
        foreach ($xmlEntries as $xmlEntry) {
            $money = $this->moneyFactory->create($xmlEntry->Amt, $xmlEntry->CdtDbtInd);
            $bookingDate = $xmlEntry->BookgDt;
            $valueDate = $xmlEntry->ValDt;
            $additionalInfo = ((string) $xmlEntry->AddtlNtryInf) ?: (string) $xmlEntry->AddtlNtryInf;

            $entry = new DTO\Entry(
                $record,
                $index,
                $money
            );

            if ($bookingDate) {
                $entry->setBookingDate($this->fromDateAndDateTimeChoice($bookingDate));
            }

            if ($valueDate) {
                $entry->setValueDate($this->fromDateAndDateTimeChoice($valueDate));
            }

            $entry->setAdditionalInfo($additionalInfo);

            if (isset($xmlEntry->RvslInd) && (string) $xmlEntry->RvslInd === 'true') {
                $entry->setReversalIndicator(true);
            }

            if (isset($xmlEntry->NtryRef) && (string) $xmlEntry->NtryRef) {
                $entry->setReference((string) $xmlEntry->NtryRef);
            }

            if (isset($xmlEntry->AcctSvcrRef) && (string) $xmlEntry->AcctSvcrRef) {
                $entry->setAccountServicerReference((string) $xmlEntry->AcctSvcrRef);
            }

            if (isset($xmlEntry->NtryDtls->Btch->PmtInfId) && (string) $xmlEntry->NtryDtls->Btch->PmtInfId) {
                $entry->setBatchPaymentId((string) $xmlEntry->NtryDtls->Btch->PmtInfId);
            }

            if (isset($xmlEntry->NtryDtls->TxDtls->Refs->PmtInfId) && (string) $xmlEntry->NtryDtls->TxDtls->Refs->PmtInfId) {
                $entry->setBatchPaymentId((string) $xmlEntry->NtryDtls->TxDtls->Refs->PmtInfId);
            }

            if (isset($xmlEntry->CdtDbtInd) && in_array((string) $xmlEntry->CdtDbtInd, ['CRDT', 'DBIT'], true)) {
                $entry->setCreditDebitIndicator((string) $xmlEntry->CdtDbtInd);
            }

            $entry->setStatus($this->readStatus($xmlEntry));

            if (isset($xmlEntry->BkTxCd)) {
                $bankTransactionCode = new DTO\BankTransactionCode();

                if (isset($xmlEntry->BkTxCd->Prtry)) {
                    $proprietaryBankTransactionCode = new DTO\ProprietaryBankTransactionCode(
                        (string) $xmlEntry->BkTxCd->Prtry->Cd,
                        (string) $xmlEntry->BkTxCd->Prtry->Issr
                    );

                    $bankTransactionCode->setProprietary($proprietaryBankTransactionCode);
                }

                if (isset($xmlEntry->BkTxCd->Domn)) {
                    $domainBankTransactionCode = new DTO\DomainBankTransactionCode(
                        (string) $xmlEntry->BkTxCd->Domn->Cd
                    );

                    if (isset($xmlEntry->BkTxCd->Domn->Fmly)) {
                        $domainFamilyBankTransactionCode = new DTO\DomainFamilyBankTransactionCode(
                            (string) $xmlEntry->BkTxCd->Domn->Fmly->Cd,
                            (string) $xmlEntry->BkTxCd->Domn->Fmly->SubFmlyCd
                        );

                        $domainBankTransactionCode->setFamily($domainFamilyBankTransactionCode);
                    }

                    $bankTransactionCode->setDomain($domainBankTransactionCode);
                }

                $entry->setBankTransactionCode($bankTransactionCode);
            }

            if (isset($xmlEntry->Chrgs)) {
                $charges = new DTO\Charges();

                if (isset($xmlEntry->Chrgs->TtlChrgsAndTaxAmt)) {
                    $money = $this->moneyFactory->create($xmlEntry->Chrgs->TtlChrgsAndTaxAmt, null);
                    $charges->setTotalChargesAndTaxAmount($money);
                }

                $chargesRecords = $xmlEntry->Chrgs->Rcrd;
                if ($chargesRecords) {
                    /** @var SimpleXMLElement $chargesRecord */
                    foreach ($chargesRecords as $chargesRecord) {
                        $chargesDetail = new DTO\ChargesRecord();

                        if (isset($chargesRecord->Amt)) {
                            $money = $this->moneyFactory->create($chargesRecord->Amt, $chargesRecord->CdtDbtInd);

                            $chargesDetail->setAmount($money);
                        }
                        if (isset($chargesRecord->CdtDbtInd) && (string) $chargesRecord->CdtDbtInd === 'true') {
                            $chargesDetail->setChargesIncludedIndicator(true);
                        }
                        if (isset($chargesRecord->Tp->Prtry->Id) && (string) $chargesRecord->Tp->Prtry->Id) {
                            $chargesDetail->setIdentification((string) $chargesRecord->Tp->Prtry->Id);
                        }
                        $charges->addRecord($chargesDetail);
                    }
                }
                $entry->setCharges($charges);
            }

            $this->entryDecoder->addTransactionDetails($entry, $xmlEntry);

            $record->addEntry($entry);
            ++$index;
        }
    }

    public function addSummary(DTO\Record $statement, SimpleXMLElement $xmlStatement): void
    {
        if (!$statement instanceof Statement) {
            return;
        }
        if (isset($xmlStatement->TxsSummry)) {
            $xmlSummary = $xmlStatement->TxsSummry;
            if (isset($xmlSummary->TtlNtries)) {
                $totals = $xmlSummary->TtlNtries;
                if (isset($totals->NbOfNtries)) {
                    $statement->setTotalEntries((int) $totals->NbOfNtries);
                }
                if (isset($totals->Sum)) {
                    $statement->setTotalSum((string) $totals->Sum);
                }
                if (isset($totals->TtlNetNtryAmt)) {
                    $statement->setTotalNetAmount((string) $totals->TtlNetNtryAmt);
                }
                if (isset($totals->CdtDbtInd)) {
                    $statement->setTotalCreditDebitIndicator((string) $totals->CdtDbtInd);
                }

                if (isset($xmlStatement->TxsSummry->TtlDbtNtries)) {
                    $debit = $xmlStatement->TxsSummry->TtlDbtNtries;
                    if (isset($debit->NbOfNtries)) {
                        $statement->setTotalDebitEntries((int) $debit->NbOfNtries);
                    }
                    if (isset($debit->Sum)) {
                        $statement->setDebitSum((string) $debit->Sum);
                    }
                }
                if (isset($xmlStatement->TxsSummry->TtlCdtNtries)) {
                    $credit = $xmlStatement->TxsSummry->TtlCdtNtries;
                    if (isset($credit->NbOfNtries)) {
                        $statement->setTotalCreditEntries((int) $credit->NbOfNtries);
                    }
                    if (isset($credit->Sum)) {
                        $statement->setCreditSum((string) $credit->Sum);
                    }
                }
            }
        }
    }

    private function readStatus(SimpleXMLElement $xmlEntry): ?string
    {
        $xmlStatus = $xmlEntry->Sts;

        // CAMT v08 uses substructure, so we check for its existence or fallback to the element itself to keep compatibility with CAMT v04
        return (string) $xmlStatus?->Cd
            ?: (string) $xmlStatus?->Prtry
                ?: (string) $xmlStatus
                    ?: null;
    }

    private function fromDateAndDateTimeChoice(SimpleXMLElement $xmlEntry): DateTimeImmutable
    {
        $date = ((string) $xmlEntry->Dt) ?: (string) $xmlEntry->DtTm;

        return $this->dateDecoder->decode($date);
    }
}
