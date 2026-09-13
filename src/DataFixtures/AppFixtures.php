<?php

namespace App\DataFixtures;

use App\Entity\AttributeCategory;
use App\Entity\CandidateProfile;
use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserDetails;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}
    public function load(ObjectManager $manager): void
    {
        // 1. Create Permissions
        $permissions = [
            'Manage Attribute Library',
            'Manage Positions',
            'View All CVs',
            'Generate CV',
            'Publish CV',
            'Edit Candidate Profile'
        ];
        $permEntities = [];
        foreach ($permissions as $name) {
            $perm = new Permission();
            $perm->setName($name);
            $manager->persist($perm);
            $permEntities[$perm->getSlug()] = $perm;
        }
        $roleAdmin = new Role();
        $roleAdmin->setName('Administrator');
        $roleAdmin->setSlug('ROLE_ADMIN');
        foreach ($permEntities as $p) {
            $roleAdmin->addPermission($p);
        }
        $manager->persist($roleAdmin);
        $roleRecruiter = new Role();
        $roleRecruiter->setName('Recruiter');
        $roleRecruiter->setSlug('ROLE_RECRUITER');
        $roleRecruiter->addPermission($permEntities['manage_attribute_library']);
        $roleRecruiter->addPermission($permEntities['manage_positions']);
        $roleRecruiter->addPermission($permEntities['view_all_cvs']);
        $manager->persist($roleRecruiter);
        $roleCandidate = new Role();
        $roleCandidate->setName('Candidate');
        $roleCandidate->setSlug('ROLE_CANDIDATE');
        $roleCandidate->addPermission($permEntities['generate_cv']);
        $roleCandidate->addPermission($permEntities['publish_cv']);
        $roleCandidate->addPermission($permEntities['edit_candidate_profile']);
        $manager->persist($roleCandidate);
        $categories = [
            'Personal Information',
            'Domain Knowledge',
            'Soft Skills',
            'Certification',
            'Work Preferences'
        ];
        foreach ($categories as $cat) {
            $category = new AttributeCategory();
            $category->setName($cat);
            $manager->persist($category);
        }
        $admin = new User();
        $admin->setEmail('admin@resumecraft.com');
        $admin->setRole($roleAdmin);
        $admin->setIsVerified(true);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $manager->persist($admin);

        $adminDetails = new UserDetails();
        $adminDetails->setUser($admin);
        $adminDetails->setFirstName('System');
        $adminDetails->setLastName('Administrator');
        $manager->persist($adminDetails);

        $recruiter = new User();
        $recruiter->setEmail('recruiter@resumecraft.com');
        $recruiter->setRole($roleRecruiter);
        $recruiter->setIsVerified(true);
        $recruiter->setPassword($this->passwordHasher->hashPassword($recruiter, 'recruiter123'));
        $manager->persist($recruiter);

         $recruiterDetails = new UserDetails();
        $recruiterDetails->setUser($recruiter);
        $recruiterDetails->setFirstName('Jane');
        $recruiterDetails->setLastName('Recruiter');
        $manager->persist($recruiterDetails);

        $candidate = new User();
        $candidate->setEmail('candidate@resumecraft.com');
        $candidate->setRole($roleCandidate);
        $candidate->setIsVerified(true);
        $candidate->setPassword($this->passwordHasher->hashPassword($candidate, 'candidate123'));
        $manager->persist($candidate);

        $candidateDetails = new UserDetails();
        $candidateDetails->setUser($candidate);
        $candidateDetails->setFirstName('John');
        $candidateDetails->setLastName('Doe');
        $manager->persist($candidateDetails);

        $candidateProfile = new CandidateProfile();
        $candidateProfile->setUser($candidate);
        $candidateProfile->setLocation('New York, NY');
        $manager->persist($candidateProfile);

        $manager->flush();
    }
}
