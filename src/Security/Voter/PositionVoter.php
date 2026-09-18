<?php

namespace App\Security\Voter;

use App\Entity\Position;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PositionVoter extends Voter
{
    public const VIEW = 'POSITION_VIEW';
    public const EDIT = 'POSITION_EDIT';
    public const DELETE = 'POSITION_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Position;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Position $position */
        $position = $subject;
        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles);
        $isRecruiter = in_array('ROLE_RECRUITER', $roles);

        if ($isAdmin) {
            return true;
        }

        return match ($attribute) {
            self::VIEW => true, // Accessible or public positions
            self::EDIT, self::DELETE => $isRecruiter,
            default => false,
        };
    }
}
