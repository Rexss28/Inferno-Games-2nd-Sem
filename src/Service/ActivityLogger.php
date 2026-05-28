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
        
        $username = 'System';
        if ($user instanceof User) {
            $log->setUserId($user->getId());
            $log->setUsername($user->getUsername());
            $username = $user->getUsername();
            
            $roles = $user->getRoles();
            $highestRole = $this->getHighestRole($roles);
            $log->setRole($highestRole);
        } else {
            $log->setUserId(0);
            $log->setUsername('System');
            $log->setRole('SYSTEM');
        }
        
        $log->setAction($action);
        $targetData = $this->formatTargetData($target);
        $log->setTargetData($targetData);
        
        $this->em->persist($log);
        $this->em->flush();
        
        // ✅ Broadcast to WebSocket for real-time updates (LOGIN, LOGOUT, CHECKOUT)
        if (in_array($action, ['LOGIN', 'LOGOUT', 'CHECKOUT'])) {
            $this->broadcastToWebSocket($action, $username, $targetData);
        }
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
    
    /**
     * Broadcast activity to WebSocket for real-time admin dashboard updates
     */
    private function broadcastToWebSocket(string $action, string $username, string $targetData = ''): void
    {
        // For local development
        $webSocketUrl = 'http://127.0.0.1:8080/broadcast';
        
        // For Railway production (WebSocket runs internally on port 8080)
        // $webSocketUrl = 'http://localhost:8080/broadcast';
        
        $payload = json_encode([
            'type' => 'new_activity',
            'message' => "{$action} activity by {$username}",
            'userId' => 'admin',
            'action' => $action,
            'username' => $username,
            'targetData' => $targetData,
            'timestamp' => (new \DateTime())->format('c'),
        ]);
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webSocketUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Don't block the response
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                error_log('WebSocket broadcast warning: HTTP ' . $httpCode);
            }
        } catch (\Exception $e) {
            // Don't let WebSocket errors break the flow
            error_log('WebSocket broadcast failed: ' . $e->getMessage());
        }
    }
}