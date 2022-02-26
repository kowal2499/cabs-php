<?php

namespace LegacyFighter\Cabs\DTO;

use LegacyFighter\Cabs\Entity\Driver;

class DriverDTO implements \JsonSerializable
{
    private int $id;
    private string $firstName;
    private string $lastName;
    private string $driverLicense;
    private ?string $photo;
    private string $status;
    private string $type;

    private function __construct(int $id, string $firstName, string $lastName, string $driverLicense, ?string $photo, string $status, string $type)
    {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->driverLicense = $driverLicense;
        $this->photo = $photo;
        $this->status = $status;
        $this->type = $type;
    }

    public static function from(Driver $driver): self
    {
        return new self(
            $driver->getId(),
            $driver->getFirstName(),
            $driver->getLastName(),
            $driver->getDriverLicense()->asString(),
            $driver->getPhoto(),
            $driver->getStatus(),
            $driver->getType()
        );
    }

    public static function with(int $id, string $firstName, string $lastName, string $driverLicense, ?string $photo, string $status, string $type): self
    {
        return new self($id, $firstName, $lastName, $driverLicense, $photo, $status, $type);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getDriverLicense(): string
    {
        return $this->driverLicense;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'driverLicense' => $this->driverLicense,
            'photo' => $this->photo,
            'status' => $this->status,
            'type' => $this->type
        ];
    }


}
