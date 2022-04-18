<?php

namespace LegacyFighter\Cabs\Service;

use LegacyFighter\Cabs\Entity\DriverFee;
use LegacyFighter\Cabs\Money\Money;
use LegacyFighter\Cabs\Repository\DriverFeeRepository;
use LegacyFighter\Cabs\Repository\TransitRepository;

class DriverFeeService
{
    public function __construct(
        private DriverFeeRepository $driverFeeRepository
    )
    {}

    public function calculateDriverFee(Money $transitPrice, int $driverId): Money
    {
        $driverFee = $this->driverFeeRepository->findByDriverId($driverId);
        if($driverFee === null) {
            throw new \InvalidArgumentException('driver Fees not defined for driver, driver id = '.$driverId);
        }
        if($driverFee->getType() === DriverFee::TYPE_FLAT) {
            $finalFee = $transitPrice->subtract(Money::from($driverFee->getAmount()));
        } else {
            $finalFee = $transitPrice->percentage($driverFee->getAmount());
        }

        return Money::from((int) max($finalFee->toInt(), $driverFee->getMin() === null ? 0 : $driverFee->getMin()->toInt()));
    }
}
