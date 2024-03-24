<?php

namespace LegacyFighter\Cabs\Service;

use LegacyFighter\Cabs\Entity\DriverFee;
use LegacyFighter\Cabs\Money\Money;
use LegacyFighter\Cabs\Repository\DriverFeeRepository;
use LegacyFighter\Cabs\Repository\TransitRepository;

class DriverFeeService
{
    private DriverFeeRepository $driverFeeRepository;
    private TransitRepository $transitRepository;

    public function __construct(DriverFeeRepository $driverFeeRepository, TransitRepository $transitRepository)
    {
        $this->driverFeeRepository = $driverFeeRepository;
        $this->transitRepository = $transitRepository;
    }

    public function calculateDriverFee(int $transitId): Money
    {
        $transit = $this->transitRepository->getOne($transitId);
        if($transit === null) {
            throw new \InvalidArgumentException('transit does not exist, id = '.$transitId);
        }
        if($transit->getDriversFee() !== null) {
            return $transit->getDriversFee();
        }
        $transitPrice = $transit->getPrice();
        $driverFee = $this->driverFeeRepository->findByDriver($transit->getDriver());
        if($driverFee === null) {
            throw new \InvalidArgumentException('driver Fees not defined for driver, driver id = '.$transit->getDriver()->getId());
        }

        return $driverFee->calculateDriverFee($transitPrice);
    }
}
