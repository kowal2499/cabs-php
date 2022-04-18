<?php

namespace LegacyFighter\Cabs\Service;

use LegacyFighter\Cabs\Common\Clock;
use LegacyFighter\Cabs\Config\AppProperties;
use LegacyFighter\Cabs\DTO\ClaimDTO;
use LegacyFighter\Cabs\Entity\Claim;
use LegacyFighter\Cabs\Entity\ClaimResolver\Result;
use LegacyFighter\Cabs\Entity\ClaimsResolver;
use LegacyFighter\Cabs\Entity\Client;
use LegacyFighter\Cabs\Repository\ClaimRepository;
use LegacyFighter\Cabs\Repository\ClaimsResolverRepository;
use LegacyFighter\Cabs\Repository\ClientRepository;
use LegacyFighter\Cabs\Repository\TransitRepository;
use LegacyFighter\Cabs\TransitDetails\TransitDetailsFacade;

class ClaimService
{
    private Clock $clock;
    private ClientRepository $clientRepository;
    private TransitDetailsFacade $transitDetailsFacade;
    private ClaimRepository $claimRepository;
    private ClaimNumberGenerator $claimNumberGenerator;
    private AppProperties $appProperties;
    private AwardsService $awardService;
    private ClientNotificationService $clientNotificationService;
    private DriverNotificationService $driverNotificationService;
    private ClaimsResolverRepository $claimsResolverRepository;

    public function __construct(
        Clock $clock,
        ClientRepository $clientRepository,
        TransitDetailsFacade $transitDetailsFacade,
        ClaimRepository $claimRepository,
        ClaimNumberGenerator $claimNumberGenerator,
        AppProperties $appProperties,
        AwardsService $awardService,
        ClientNotificationService $clientNotificationService,
        DriverNotificationService $driverNotificationService,
        ClaimsResolverRepository $claimsResolverRepository
    )
    {
        $this->clock = $clock;
        $this->clientRepository = $clientRepository;
        $this->transitDetailsFacade = $transitDetailsFacade;
        $this->claimRepository = $claimRepository;
        $this->claimNumberGenerator = $claimNumberGenerator;
        $this->appProperties = $appProperties;
        $this->awardService = $awardService;
        $this->clientNotificationService = $clientNotificationService;
        $this->driverNotificationService = $driverNotificationService;
        $this->claimsResolverRepository = $claimsResolverRepository;
    }

    public function create(ClaimDTO $claimDTO): Claim
    {
        $claim = new Claim();
        $claim->setCreationDate($this->clock->now());
        $claim->setClaimNo($this->claimNumberGenerator->generate($claim));
        $claim = $this->update($claimDTO, $claim);

        return $claim;
    }

    public function find(int $claimId): Claim
    {
        $claim = $this->claimRepository->getOne($claimId);
        if($claim===null) {
            throw new \InvalidArgumentException('Claim does not exists');
        }

        return $claim;
    }

    public function update(ClaimDTO $claimDTO, Claim $claim): Claim
    {
        $client = $this->clientRepository->getOne($claimDTO->getClientId());
        $transit = $this->transitDetailsFacade->find($claimDTO->getTransitId());
        if($client===null) {
            throw new \InvalidArgumentException('Client does not exists');
        }
        if($claimDTO->isDraft()) {
            $claim->setStatus(Claim::STATUS_DRAFT);
        } else {
            $claim->setStatus(Claim::STATUS_NEW);
        }
        $claim->setOwner($client);
        $claim->setTransitId($transit->transitId);
        $claim->setTransitPrice($transit->price);
        $claim->setCreationDate($this->clock->now());
        $claim->setReason($claimDTO->getReason());
        $claim->setIncidentDescription($claimDTO->getIncidentDescription());
        return $this->claimRepository->save($claim);
    }

    public function setStatus(string $status, int $claimId): Claim
    {
        $claim = $this->find($claimId);
        $claim->setStatus($status);
        return $claim;
    }

    public function tryToResolveAutomatically(int $id): Claim
    {
        $claim = $this->find($id);

        $claimsResolver = $this->findOrCreateResolver($claim->getOwner());
        $transitsDoneByClient = $this->transitDetailsFacade->findByClient($claim->getOwner()->getId());
        $result = $claimsResolver->resolve(
            $claim,
            $this->appProperties->getAutomaticRefundForVipThreshold(),
            count($transitsDoneByClient),
            $this->appProperties->getNoOfTransitsForClaimAutomaticRefund()
        );

        if($result->getDecision() === Claim::STATUS_REFUNDED) {
            $claim->refund();
            $this->clientNotificationService->notifyClientAboutRefund($claim->getClaimNo(), $claim->getOwner()->getId());
            if($claim->getOwner()->getType() === Client::TYPE_VIP) {
                $this->awardService->registerNonExpiringMiles($claim->getOwner()->getId(), 10);
            }
        }

        if($result->getDecision() === Claim::STATUS_ESCALATED) {
            $claim->escalate();
        }

        if($result->getWhoToAsk() === Result::ASK_DRIVER) {
            $this->driverNotificationService->askDriverForDetailsAboutClaim($claim->getClaimNo(), $this->transitDetailsFacade->find($claim->getTransitId())->driverId);
        }
        if($result->getWhoToAsk() === Result::ASK_CLIENT) {
            $this->clientNotificationService->askForMoreInformation($claim->getClaimNo(), $claim->getOwner()->getId());
        }

        return $claim;
    }

    private function findOrCreateResolver(Client $client): ClaimsResolver
    {
        $claimsResolver = $this->claimsResolverRepository->findByClientId($client->getId());
        if($claimsResolver === null) {
            $claimsResolver = $this->claimsResolverRepository->save(new ClaimsResolver($client->getId()));
        }

        return $claimsResolver;
    }
}
