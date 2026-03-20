<?php
namespace App\Security\Voter;

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
        return in_array($attribute, [self::EDIT, self::DELETE], true)
            && $subject instanceof LicenseKey;
    }

    protected function voteOnAttribute(string $attribute, $licenseKey, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        
        if ($isAdmin) {
            return true;
        }

        $creator = $licenseKey->getCreatedBy();

        if (!$creator instanceof User) {
            return false;
        }

        return $user->getId() === $creator->getId();
    }
}