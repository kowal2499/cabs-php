<?php

namespace LegacyFighter\Cabs\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;
use LegacyFighter\Cabs\Common\BaseEntity;
use LegacyFighter\Cabs\Money\Money;
use LegacyFighter\Cabs\Service\DriverFeeService;

#[Entity]
class DriverFee extends BaseEntity
{
    public const TYPE_FLAT = 'flat';
    public const TYPE_PERCENTAGE = 'percentage';

    #[Column]
    private string $type;

    #[OneToOne(targetEntity: Driver::class)]
    private Driver $driver;

    #[Column(type: 'integer')]
    private int $amount;

    #[Column(type: 'money', nullable: true)]
    private ?Money $min;

    public function __construct(string $type, Driver $driver, int $amount, ?Money $min = null)
    {
        $this->type = $type;
        $this->driver = $driver;
        $this->amount = $amount;
        $this->min = $min;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getDriver(): Driver
    {
        return $this->driver;
    }

    public function setDriver(Driver $driver): void
    {
        $this->driver = $driver;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function setMin(?Money $min): void
    {
        $this->min = $min;
    }

    public function calculateDriverFee(Money $transitPrice): Money
    {
        if ($this->type === DriverFee::TYPE_FLAT) {
            $finalFee = $transitPrice->subtract(Money::from($this->amount));
        } else {
            $finalFee = $transitPrice->percentage($this->amount);
        }
        return Money::from((int)max($finalFee->toInt(), $this->min === null ? 0 : $this->min->toInt()));
    }
}
