<?php
namespace App\Security;

use App\Entity\LicenseKey;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LicenseKeyVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        // Only vote on EDIT and DELETE attributes for LicenseKey objects
        return in_array($attribute, [self::EDIT, self::DELETE], true)
            && $subject instanceof LicenseKey;
    }

    protected function voteOnAttribute(string $attribute, $licenseKey, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // If user is not logged in, deny access
        if (!$user instanceof User) {
            return false;
        }

        // Check if user is admin
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        
        if ($isAdmin) {
            return true; // Admin can do anything
        }

        // Get the creator of the license key
        $creator = $licenseKey->getCreatedBy();

        // If license key has no creator, deny access
        if (!$creator instanceof User) {
            return false;
        }

        // Compare the IDs - this is the safe way to compare
        return $user->getId() === $creator->getId();
    }
}