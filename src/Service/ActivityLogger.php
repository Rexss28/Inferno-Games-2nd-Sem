<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use App\Entity\Stock;
use App\Entity\GameManagement;
use App\Entity\LicenseKey;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ActivityLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function log(string $action, mixed $target = null): void
    {
        $user = $this->security->getUser();
        
        $log = new ActivityLog();
        
        if ($user instanceof User) {
            $log->setUserId($user->getId());
            $log->setUsername($user->getUsername());
            
            $roles = $user->getRoles();
            $highestRole = $this->getHighestRole($roles);
            $log->setRole($highestRole);
        } else {
            $log->setUserId(0);
            $log->setUsername('System');
            $log->setRole('SYSTEM');
        }
        
        $log->setAction($action);
        $log->setTargetData($this->formatTargetData($target));
        
        $this->em->persist($log);
        $this->em->flush();
    }
    
    private function getHighestRole(array $roles): string
    {
        if (in_array('ROLE_ADMIN', $roles)) {
            return 'ROLE_ADMIN';
        }
        if (in_array('ROLE_STAFF', $roles)) {
            return 'ROLE_STAFF';
        }
        return 'ROLE_USER';
    }
    
    private function formatTargetData(mixed $target): string
    {
        if ($target === null) {
            return '';
        }
        
        if (is_string($target)) {
            return $target;
        }
        
        if ($target instanceof Stock) {
            return $this->formatStock($target);
        }
        
        if ($target instanceof GameManagement) {
            return $this->formatGameManagement($target);
        }
        
        if ($target instanceof LicenseKey) {
            return $this->formatLicenseKey($target);
        }
        
        if ($target instanceof User) {
            return $this->formatUser($target);
        }
        
        if (is_object($target) && method_exists($target, '__toString')) {
            return (string) $target;
        }
        
        if (is_object($target) && method_exists($target, 'getId')) {
            return get_class($target) . ' #' . $target->getId();
        }
        
        return '';
    }
    
    private function formatStock(Stock $stock): string
    {
        $game = $stock->getGame();
        $gameName = $game ? $game->getTitle() : 'No Game';
        
        return sprintf(
            'Stock #%d (Available: %d/%d, Status: %s, Game: %s)',
            $stock->getId(),
            $stock->getAvailableQuantity(),
            $stock->getTotalQuantity(),
            $stock->getStatus(),
            $gameName
        );
    }
    
    private function formatGameManagement(GameManagement $game): string
    {
        return sprintf(
            'Game: %s (ID: %d, Price: $%s)',
            $game->getTitle(),
            $game->getId(),
            $game->getPrice()
        );
    }
    
    private function formatLicenseKey(LicenseKey $licenseKey): string
    {
        $game = $licenseKey->getGame();
        $gameName = $game ? $game->getTitle() : 'No Game';
        
        return sprintf(
            'LicenseKey: %s (ID: %d, Status: %s, Game: %s)',
            $licenseKey->getCode(),
            $licenseKey->getId(),
            $licenseKey->getStatus(),
            $gameName
        );
    }
    
    private function formatUser(User $user): string
    {
        return sprintf(
            'User: %s (ID: %d, Roles: %s)',
            $user->getUsername(),
            $user->getId(),
            implode(', ', $user->getDisplayRoles())
        );
    }
}