<?php
namespace App\Services;

use JBadarneh\JoFotara\JoFotaraService;
use App\Services\MyInvoiceInformation;
use ReflectionClass;

class MyJoFotaraService extends JoFotaraService
{
    public function basicInformation(): MyInvoiceInformation
    {
        // If not initialized yet, create our custom instance
        if (!isset($this->basicInfo) || !$this->basicInfo instanceof MyInvoiceInformation) {
            $this->basicInfo = new MyInvoiceInformation();
        }

        return $this->basicInfo;
    }

    public function forceReplaceBasicInfo(): void
    {
        // Make sure the property exists in parent and override it with our object
        $reflection = new ReflectionClass(parent::class);

        if ($reflection->hasProperty('basicInfo')) {
            $property = $reflection->getProperty('basicInfo');
            $property->setAccessible(true);
            $property->setValue($this, $this->basicInfo);
        }
    }
}
