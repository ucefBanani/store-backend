<?php 
 
namespace App\Security\Voter;;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AdminVoter extends Voter
{
    // Define supported actions
    const CREATE = 'create';
    const UPDATE = 'update';
    const DELETE = 'delete';
    const SHOW = 'show';

    protected function supports(string $attribute, $subject): bool
    {
        // Check if the attribute is one of the defined actions
        return in_array($attribute, [self::CREATE, self::UPDATE, self::DELETE, self::SHOW])
            && ($subject instanceof Product || $subject instanceof  Category);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Ensure the user is authenticated
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Allow only users with ROLE_ADMIN for all actions
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Optional: Define finer-grained logic for each action (e.g., based on ownership)
        switch ($attribute) {
            case self::CREATE:
            case self::UPDATE:
            case self::DELETE:
            case self::SHOW:
                // For now, restrict all these actions to admins
                return false;
        }

        return false;
    }
}