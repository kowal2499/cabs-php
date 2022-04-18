<?php

namespace LegacyFighter\Cabs\Tests\Common;

use Doctrine\ORM\EntityManagerInterface;
use LegacyFighter\Cabs\Common\Clock;
use LegacyFighter\Cabs\Distance\Distance;
use LegacyFighter\Cabs\DTO\AddressDTO;
use LegacyFighter\Cabs\DTO\CarTypeDTO;
use LegacyFighter\Cabs\DTO\ClaimDTO;
use LegacyFighter\Cabs\DTO\TransitDTO;
use LegacyFighter\Cabs\Entity\Address;
use LegacyFighter\Cabs\Entity\CarType;
use LegacyFighter\Cabs\Entity\Claim;
use LegacyFighter\Cabs\Entity\Client;
use LegacyFighter\Cabs\Entity\Driver;
use LegacyFighter\Cabs\Entity\DriverAttribute;
use LegacyFighter\Cabs\Entity\DriverFee;
use LegacyFighter\Cabs\Entity\Transit;
use LegacyFighter\Cabs\Money\Money;
use LegacyFighter\Cabs\Repository\AddressRepository;
use LegacyFighter\Cabs\Repository\ClientRepository;
use LegacyFighter\Cabs\Repository\DriverAttributeRepository;
use LegacyFighter\Cabs\Repository\DriverFeeRepository;
use LegacyFighter\Cabs\Repository\TransitRepository;
use LegacyFighter\Cabs\Service\AwardsService;
use LegacyFighter\Cabs\Service\CarTypeService;
use LegacyFighter\Cabs\Service\ClaimService;
use LegacyFighter\Cabs\Service\DriverService;
use LegacyFighter\Cabs\Service\DriverSessionService;
use LegacyFighter\Cabs\Service\DriverTrackingService;
use LegacyFighter\Cabs\Service\TransitService;
use LegacyFighter\Cabs\TransitDetails\TransitDetails;
use LegacyFighter\Cabs\TransitDetails\TransitDetailsDTO;
use LegacyFighter\Cabs\TransitDetails\TransitDetailsFacade;

class Fixtures
{
    public function __construct(
        private AddressFixture $addressFixture,
        private AwardsAccountFixture $awardsAccountFixture,
        private CarTypeFixture $carTypeFixture,
        private ClaimFixture $claimFixture,
        private ClientFixture $clientFixture,
        private DriverFixture $driverFixture,
        private RideFixture $rideFixture,
        private TransitFixture $transitFixture,
        private EntityManagerInterface $em
    )
    {
    }

    public function anAddress(string $country = 'Polska', string $city = 'Warszawa', string $street = 'Młynarska', int $buildingNumber = 20): Address
    {
        return $this->addressFixture->anAddress($country, $city, $street, $buildingNumber);
    }

    public function aClient(string $type = Client::TYPE_NORMAL): Client
    {
        return $this->clientFixture->aClient($type);
    }

    public function aDriver(
        string $status = Driver::STATUS_ACTIVE,
        string $name = 'Janusz',
        string $lastName = 'Kowalski',
        string $license = 'FARME100165AB5EW'
    ): Driver
    {
        return $this->driverFixture->aDriver($status, $name, $lastName, $license);
    }

    public function aNearbyDriver(string $plateNumber = 'WU DAMIAN'): Driver
    {
        return $this->driverFixture->aNearbyDriver($plateNumber);
    }

    public function driverHasFee(Driver $driver, string $feeType, int $amount, ?int $min = null): DriverFee
    {
        return $this->driverFixture->driverHasFee($driver, $feeType, $amount, $min);
    }

    public function transitDetails(Driver $driver, int $price, \DateTimeImmutable $when, ?Client $client = null): Transit
    {
        return $this->transitFixture->transitDetails($driver, $price, $when, $client ?? $this->aClient(), $this->anAddress(), $this->anAddress());
    }

    public function anActiveCarCategory(string $carClass): CarType
    {
        return $this->carTypeFixture->anActiveCarCategory($carClass);
    }

    public function aTransitDTOWith(Client $client, AddressDTO $from, AddressDTO $to): TransitDTO
    {
        $transit = new Transit($client, new \DateTimeImmutable(), Distance::zero());
        PrivateProperty::setId(1, $transit);
        $transitDetails = new TransitDetails(new \DateTimeImmutable(), 1, $from->toAddressEntity(), $to->toAddressEntity(), Distance::zero(), $client, CarType::CAR_CLASS_VAN, Money::zero(), $transit->getTariff());
        PrivateProperty::setId(1, $transitDetails);

        return TransitDTO::from($transit, TransitDetailsDTO::from($transitDetails));
    }

    public function aTransitDTO(AddressDTO $from, AddressDTO $to): TransitDTO
    {
        return $this->aTransitDTOWith($this->aClient(), $from, $to);
    }

    public function clientHasDoneTransits(Client $client, int $noOfTransits): void
    {
        foreach (range(1, $noOfTransits) as $_) {
            $this->aJourney(10, $client, $this->aNearbyDriver(), $this->anAddress(), $this->anAddress());
        }
    }

    public function aJourney(int $price, Client $client, Driver $driver, Address $from, Address $destination): Transit
    {
        return $this->rideFixture->aRide($price, $client, $driver, $from, $destination);
    }

    public function aJourneyWithFixedClock(int $price, \DateTimeImmutable $publishedAt, \DateTimeImmutable $completedAt, Client $client, Driver $driver, Address $from, Address $destination, FixedClock $clock): Transit
    {
        return $this->rideFixture->aRideWithFixedClock($price, $publishedAt, $completedAt, $client, $driver, $from, $destination, $clock);
    }

    public function createClaim(Client $client, Transit $transit, string $reason = '$$$'): Claim
    {
        return $this->claimFixture->createClaim($client, $transit, $reason);
    }

    public function createAndResolveClaim(Client $client, Transit $transit): Claim
    {
        return $this->claimFixture->createAndResolveClaim($client, $transit);
    }

    public function clientHasDoneClaims(Client $client, int $howMany): void
    {
        foreach (range(1, $howMany) as $_) {
            $this->createAndResolveClaim($client, $this->aJourney(20, $client, $this->aNearbyDriver(), $this->anAddress(), $this->anAddress()));
        }
        $this->em->refresh($client);
    }

    public function aClientWithClaims(string $type, int $howManyClaims): Client
    {
        $client = $this->aClient($type);
        $this->awardsAccount($client);
        $this->clientHasDoneClaims($client, $howManyClaims);
        return $client;
    }

    public function anAddressDTO(string $country, string $city, string $street, int $buildingNumber): AddressDTO
    {
        $address = new Address($country, $city, $street, $buildingNumber);
        $address->setPostalCode('11-111');
        $address->setName('name');
        $address->setDistrict('district');
        return AddressDTO::from($address);
    }

    public function awardsAccount(Client $client): void
    {
        $this->awardsAccountFixture->awardsAccount($client);
    }

    public function activeAwardsAccount(Client $client): void
    {
        $this->awardsAccountFixture->activeAwardsAccount($client);
    }

    public function driverHasAttribute(Driver $driver, string $name, string $value): void
    {
        $this->driverFixture->driverHasAttribute($driver, $name, $value);
    }
}
