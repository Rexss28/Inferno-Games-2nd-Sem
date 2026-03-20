<?php
namespace App\Security\Voter;

use App\Entity\Stock;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class StockVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        // Only vote on EDIT and DELETE attributes for Stock objects
        return in_array($attribute, [self::EDIT, self::DELETE], true)
            && $subject instanceof Stock;
    }

    protected function voteOnAttribute(string $attribute, $stock, TokenInterface $token): bool
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

        // Get the creator of the stock
        $creator = $stock->getCreatedBy();

        // If stock has no creator, deny access
        if (!$creator instanceof User) {
            return false;
        }

        // Compare the IDs - this is the safe way to compare
        return $user->getId() === $creator->getId();
    }
}