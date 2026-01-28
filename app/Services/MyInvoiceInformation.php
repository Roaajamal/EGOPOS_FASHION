<?php

namespace App\Services;

use JBadarneh\JoFotara\Sections\BasicInvoiceInformation;

class MyInvoiceInformation extends BasicInvoiceInformation
{
    protected ?string $supplierIncomeSource = null;

    public function setSupplierIncomeSource(string $source): self
    {
        $this->supplierIncomeSource = $source;
        return $this;
    }

    public function getSupplierIncomeSource(): ?string
    {
        return $this->supplierIncomeSource;
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        if ($this->supplierIncomeSource) {
            // match the exact key name used by the SDK internally
            $data['supplier_income_source'] = $this->supplierIncomeSource;
        }

        return $data;
    }
}

