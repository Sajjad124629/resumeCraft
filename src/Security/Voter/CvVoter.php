<?php

namespace App\Security\Voter;

use App\Entity\Cv;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CvVoter extends Voter
{
    public const VIEW = 'CV_VIEW';
    public const EDIT = 'CV_EDIT';
    public const PUBLISH = 'CV_PUBLISH';
    public const DELETE = 'CV_DELETE';
    public const LIKE = 'CV_LIKE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::PUBLISH, self::DELETE, self::LIKE])
            && $subject instanceof Cv;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Cv $cv */
        $cv = $subject;
        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles);
        $isRecruiter = in_array('ROLE_RECRUITER', $roles);
        $isOwner = $user->getCandidateProfile() && $user->getCandidateProfile() === $cv->getCandidate();

        if ($isAdmin) {
            return true;
        }

        return match ($attribute) {
            self::VIEW => $isOwner || ($isRecruiter && $cv->getStatus() === 'published'),
            self::EDIT, self::PUBLISH, self::DELETE => $isOwner,
            self::LIKE => $isRecruiter,
            default => false,
        };
    }
}
