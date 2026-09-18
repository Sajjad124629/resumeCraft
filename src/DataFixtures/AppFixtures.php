<?php

namespace App\DataFixtures;

use App\Entity\AttributeCategory;
use App\Entity\CandidateProfile;
use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\User;
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
            'manage_attributes' => 'Manage Attribute Library',
            'manage_positions' => 'Manage Positions',
            'view_all_cvs' => 'View All CVs',
            'generate_cv' => 'Generate CV',
            'publish_cv' => 'Publish CV',
            'edit_profile' => 'Edit Candidate Profile'
        ];

        $permEntities = [];
        foreach ($permissions as $slug => $name) {
            $perm = new Permission();
            $perm->setSlug($slug);
            $perm->setName($name);
            $manager->persist($perm);
            $permEntities[$slug] = $perm;
        }

        // 2. Create Roles
        $roleAdmin = new Role();
        $roleAdmin->setName('Administrator');
        $roleAdmin->setSlug('ROLE_ADMIN');
        // Give Admin all permissions
        foreach ($permEntities as $p) {
            $roleAdmin->addPermission($p);
        }
        $manager->persist($roleAdmin);

        $roleRecruiter = new Role();
        $roleRecruiter->setName('Recruiter');
        $roleRecruiter->setSlug('ROLE_RECRUITER');
        $roleRecruiter->addPermission($permEntities['manage_attributes']);
        $roleRecruiter->addPermission($permEntities['manage_positions']);
        $roleRecruiter->addPermission($permEntities['view_all_cvs']);
        $manager->persist($roleRecruiter);

        $roleCandidate = new Role();
        $roleCandidate->setName('Candidate');
        $roleCandidate->setSlug('ROLE_CANDIDATE');
        $roleCandidate->addPermission($permEntities['generate_cv']);
        $roleCandidate->addPermission($permEntities['publish_cv']);
        $roleCandidate->addPermission($permEntities['edit_profile']);
        $manager->persist($roleCandidate);

        // 3. Create Attribute Categories
        $categories = [
            'Personal Information',
            'Domain Knowledge',
            'Soft Skills',
            'Certification',
            'Work Preferences'
        ];

        foreach ($categories as $catName) {
            $cat = new AttributeCategory();
            $cat->setName($catName);
            $manager->persist($cat);
        }

        // 4. Create Admin
        $admin = new User();
        $admin->setEmail('admin@resumecraft.com');
        $admin->setRole($roleAdmin);
        $admin->setIsVerified(true);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // Admin Profile (UserDetails)
        $adminDetails = new \App\Entity\UserDetails();
        $adminDetails->setUser($admin);
        $adminDetails->setFirstName('System');
        $adminDetails->setLastName('Administrator');
        $manager->persist($adminDetails);

        // 5. Create Recruiter
        $recruiter = new User();
        $recruiter->setEmail('recruiter@resumecraft.com');
        $recruiter->setRole($roleRecruiter);
        $recruiter->setIsVerified(true);
        $recruiter->setPassword($this->passwordHasher->hashPassword($recruiter, 'recruiter123'));
        $manager->persist($recruiter);

        // Recruiter Profile (UserDetails)
        $recruiterDetails = new \App\Entity\UserDetails();
        $recruiterDetails->setUser($recruiter);
        $recruiterDetails->setFirstName('Jane');
        $recruiterDetails->setLastName('Recruiter');
        $manager->persist($recruiterDetails);

        // 6. Create Candidate
        $candidate = new User();
        $candidate->setEmail('candidate@resumecraft.com');
        $candidate->setRole($roleCandidate);
        $candidate->setIsVerified(true);
        $candidate->setPassword($this->passwordHasher->hashPassword($candidate, 'candidate123'));
        $manager->persist($candidate);

        // Candidate UserDetails
        $candidateDetails = new \App\Entity\UserDetails();
        $candidateDetails->setUser($candidate);
        $candidateDetails->setFirstName('John');
        $candidateDetails->setLastName('Doe');
        $manager->persist($candidateDetails);

        // Candidate Profile (Job specific)
        $candidateProfile = new CandidateProfile();
        $candidateProfile->setUser($candidate);
        $candidateProfile->setLocation('New York, NY');
        $manager->persist($candidateProfile);

        $manager->flush();
    }
}
