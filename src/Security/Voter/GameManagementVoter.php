<?php
namespace App\Security\Voter;

use App\Entity\GameManagement;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;

class GameManagementVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE], true)
            && $subject instanceof GameManagement;
    }

    protected function voteOnAttribute(string $attribute, $gameManagement, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // ✅ ADMIN BYPASS: If user has ROLE_ADMIN, grant access immediately
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Regular staff: Check ownership
        $creator = $gameManagement->getCreatedBy();
        if (!$creator instanceof User) {
            return false;
        }

        return $user->getId() === $creator->getId();
    }
}