<?php

namespace App\Command;

use App\Entity\Attribute;
use App\Entity\AttributeCategory;
use App\Entity\CandidateAttributeValue;
use App\Entity\CandidateProfile;
use App\Entity\Cv;
use App\Entity\CvLike;
use App\Entity\DiscussionPost;
use App\Entity\Permission;
use App\Entity\Position;
use App\Entity\PositionAccessRule;
use App\Entity\Project;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserDetails;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed-demo-data',
    description: 'Seeds rich demonstration data including all roles, positions, attributes of all 8 types, candidate profiles, projects, CVs, and discussions.',
)]
class SeedDemoDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Seeding ResumeCraft Comprehensive Demo Data');

        // 1. Roles & Permissions
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
            $perm = $this->em->getRepository(Permission::class)->findOneBy(['slug' => $slug]);
            if (!$perm) {
                $perm = new Permission();
                $perm->setSlug($slug);
                $perm->setName($name);
                $this->em->persist($perm);
            }
            $permEntities[$slug] = $perm;
        }

        $rolesData = [
            'ROLE_ADMIN' => ['Administrator', array_values($permEntities)],
            'ROLE_RECRUITER' => ['Recruiter', [$permEntities['manage_attributes'], $permEntities['manage_positions'], $permEntities['view_all_cvs']]],
            'ROLE_CANDIDATE' => ['Candidate', [$permEntities['generate_cv'], $permEntities['publish_cv'], $permEntities['edit_profile']]],
        ];

        $roleEntities = [];
        foreach ($rolesData as $slug => [$name, $perms]) {
            $role = $this->em->getRepository(Role::class)->findOneBy(['slug' => $slug]);
            if (!$role) {
                $role = new Role();
                $role->setSlug($slug);
                $role->setName($name);
                foreach ($perms as $p) {
                    $role->addPermission($p);
                }
                $this->em->persist($role);
            }
            $roleEntities[$slug] = $role;
        }
        $this->em->flush();

        // 2. Categories
        $categoryNames = [
            'Personal Information',
            'Domain Knowledge',
            'Soft Skills',
            'Certification',
            'Work Preferences'
        ];
        $categoryEntities = [];
        foreach ($categoryNames as $cName) {
            $cat = $this->em->getRepository(AttributeCategory::class)->findOneBy(['name' => $cName]);
            if (!$cat) {
                $cat = new AttributeCategory();
                $cat->setName($cName);
                $this->em->persist($cat);
                $this->em->flush();
            }
            $categoryEntities[$cName] = $cat;
        }

        // 3. Attributes (Covering all 8 supported attribute types + tuning options)
        $attributesDefinition = [
            [
                'name' => 'English Level',
                'type' => 'select',
                'category' => 'Soft Skills',
                'description' => 'Common European Framework of Reference for Languages (CEFR)',
                'options' => ['choices' => ['Beginner (A1)', 'Elementary (A2)', 'Intermediate (B1)', 'Upper-Intermediate (B2)', 'Advanced (C1)', 'Proficient / Native (C2)']]
            ],
            [
                'name' => 'GPA',
                'type' => 'number',
                'category' => 'Personal Information',
                'description' => 'Grade Point Average on a 4.0 or 5.0 scale',
                'options' => ['min' => 1.0, 'max' => 5.0]
            ],
            [
                'name' => 'IELTS Score',
                'type' => 'number',
                'category' => 'Certification',
                'description' => 'Official IELTS Academic overall band score',
                'options' => ['min' => 1.0, 'max' => 9.0]
            ],
            [
                'name' => 'Remote Work Availability',
                'type' => 'boolean',
                'category' => 'Work Preferences',
                'description' => 'Available for full-time remote employment',
                'options' => null
            ],
            [
                'name' => 'Presentation Skills',
                'type' => 'select',
                'category' => 'Soft Skills',
                'description' => 'Experience giving technical talks, stakeholder demos, and client presentations',
                'options' => ['choices' => ['Basic', 'Intermediate', 'Advanced']]
            ],
            [
                'name' => 'Professional Summary',
                'type' => 'text',
                'category' => 'Domain Knowledge',
                'description' => 'Markdown-formatted career summary and core technical expertise',
                'options' => ['maxLength' => 2000]
            ],
            [
                'name' => 'Portfolio URL',
                'type' => 'string',
                'category' => 'Personal Information',
                'description' => 'Link to GitHub, personal blog, or live project showcases',
                'options' => ['maxLength' => 255, 'regex' => '^https?://.+']
            ],
            [
                'name' => 'Available Notice Period',
                'type' => 'period',
                'category' => 'Work Preferences',
                'description' => 'Earliest transition window date range',
                'options' => null
            ],
            [
                'name' => 'Certification Badges',
                'type' => 'image',
                'category' => 'Certification',
                'description' => 'Cloud-hosted credential badge or diploma image',
                'options' => null
            ],
            [
                'name' => 'Career Start Date',
                'type' => 'date',
                'category' => 'Personal Information',
                'description' => 'Date when professional career began',
                'options' => null
            ]
        ];

        $attributeEntities = [];
        foreach ($attributesDefinition as $ad) {
            $attr = $this->em->getRepository(Attribute::class)->findOneBy(['name' => $ad['name']]);
            if (!$attr) {
                $attr = new Attribute();
                $attr->setName($ad['name']);
                $attr->setType($ad['type']);
                $attr->setDescription($ad['description']);
                $attr->setCategory($categoryEntities[$ad['category']]);
                if ($ad['options']) {
                    $attr->setOptions($ad['options']);
                }
                $this->em->persist($attr);
                $this->em->flush();
            }
            $attributeEntities[$ad['name']] = $attr;
        }

        // 4. Create Users (Admin, Recruiter, Candidates)
        $usersConfig = [
            [
                'email' => 'admin@resumecraft.com',
                'password' => 'admin123',
                'role' => 'ROLE_ADMIN',
                'first' => 'System',
                'last' => 'Administrator',
                'location' => 'HQ, San Francisco',
                'isCandidate' => true,
            ],
            [
                'email' => 'recruiter@resumecraft.com',
                'password' => 'recruiter123',
                'role' => 'ROLE_RECRUITER',
                'first' => 'Jane',
                'last' => 'Recruiter',
                'location' => 'London, UK',
                'isCandidate' => false,
            ],
            [
                'email' => 'candidate@resumecraft.com',
                'password' => 'candidate123',
                'role' => 'ROLE_CANDIDATE',
                'first' => 'John',
                'last' => 'Smith',
                'location' => 'New York, NY',
                'isCandidate' => true,
                'attrs' => [
                    'English Level' => 'Advanced (C1)',
                    'GPA' => 3.9,
                    'IELTS Score' => 8.0,
                    'Remote Work Availability' => true,
                    'Presentation Skills' => 'Advanced',
                    'Professional Summary' => "Senior software engineer with **8+ years** of hands-on experience in full-stack architecture, distributed systems, and real-time platforms.\n\n- Expert in **PHP / Symfony**, **Vue.js**, and **TypeScript**\n- Proven track record scaling microservices and data pipelines.",
                    'Portfolio URL' => 'https://github.com/johnsmith-dev',
                    'Available Notice Period' => ['start' => '2026-10-01', 'end' => '2026-10-31'],
                ],
                'projects' => [
                    [
                        'name' => 'Enterprise E-Commerce Microservices',
                        'desc' => "Architected scalable microservices processing over **50k orders/day** with zero downtime deployments.\n\nIntegrated Elasticsearch and Redis caching for sub-100ms product searches.",
                        'start' => '2023-01-15',
                        'end' => '2024-06-30',
                        'tags' => ['PHP', 'Symfony', 'Docker', 'Kubernetes', 'MySQL', 'Redis'],
                    ],
                    [
                        'name' => 'Real-Time Financial Analytics Dashboard',
                        'desc' => "Built high-frequency trade reporting tool using Vue 3 and WebSocket connections for instant portfolio valuation updates.",
                        'start' => '2024-07-01',
                        'end' => null,
                        'tags' => ['Vue.js', 'TypeScript', 'Tailwind', 'Python', 'SQL'],
                    ],
                    [
                        'name' => 'Automated Data Pipeline & ETL',
                        'desc' => "Orchestrated BigQuery pipelines transforming marketing telemetry for ML modeling.",
                        'start' => '2022-03-01',
                        'end' => '2022-12-15',
                        'tags' => ['Python', 'SQL', 'Docker', 'Machine Learning'],
                    ]
                ]
            ],
            [
                'email' => 'paul.king@example.com',
                'password' => 'candidate123',
                'role' => 'ROLE_CANDIDATE',
                'first' => 'Paul',
                'last' => 'King',
                'location' => 'Berlin, Germany',
                'isCandidate' => true,
                'attrs' => [
                    'English Level' => 'Upper-Intermediate (B2)',
                    'GPA' => 3.7,
                    'IELTS Score' => 7.5,
                    'Remote Work Availability' => true,
                    'Presentation Skills' => 'Intermediate',
                    'Professional Summary' => "DevOps and Cloud Infrastructure Specialist with in-depth CI/CD experience.",
                    'Portfolio URL' => 'https://github.com/paulking-cloud',
                ],
                'projects' => [
                    [
                        'name' => 'Multi-Region Kubernetes Cluster Automation',
                        'desc' => "Automated Terraform configurations deploying multi-region EKS clusters with GitOps workflows.",
                        'start' => '2023-04-01',
                        'end' => '2025-01-01',
                        'tags' => ['Kubernetes', 'Docker', 'AWS', 'Linux', 'Terraform'],
                    ],
                    [
                        'name' => 'Continuous Integration Observability Suite',
                        'desc' => "Centralized Prometheus and Grafana metrics across 120+ microservice repositories.",
                        'start' => '2022-01-01',
                        'end' => '2023-03-31',
                        'tags' => ['Docker', 'AWS', 'Linux', 'Prometheus'],
                    ]
                ]
            ],
            [
                'email' => 'lee.morris@example.com',
                'password' => 'candidate123',
                'role' => 'ROLE_CANDIDATE',
                'first' => 'Lee',
                'last' => 'Morris',
                'location' => 'Toronto, Canada',
                'isCandidate' => true,
                'attrs' => [
                    'English Level' => 'Proficient / Native (C2)',
                    'GPA' => 3.8,
                    'IELTS Score' => 8.5,
                    'Remote Work Availability' => false,
                    'Presentation Skills' => 'Advanced',
                    'Professional Summary' => "Lead QA Engineer specializing in automated regression frameworks and stress testing.",
                    'Portfolio URL' => 'https://github.com/leemorris-qa',
                ],
                'projects' => [
                    [
                        'name' => 'End-to-End Test Automation Platform',
                        'desc' => "Designed full test matrix covering 500+ daily test executions with Cypress and Selenium.",
                        'start' => '2023-06-01',
                        'end' => null,
                        'tags' => ['Selenium', 'Cypress', 'TypeScript', 'Jest'],
                    ]
                ]
            ]
        ];

        $candidateEntities = [];
        $recruiterEntity = null;

        foreach ($usersConfig as $uc) {
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $uc['email']]);
            if (!$user) {
                $user = new User();
                $user->setEmail($uc['email']);
                $user->setPassword($this->passwordHasher->hashPassword($user, $uc['password']));
                $user->setRole($roleEntities[$uc['role']]);
                $user->setIsVerified(true);

                $details = new UserDetails();
                $details->setUser($user);
                $details->setFirstName($uc['first']);
                $details->setLastName($uc['last']);
                if (!empty($uc['location'])) {
                    $details->setLocation($uc['location']);
                }
                $this->em->persist($details);

                if ($uc['isCandidate']) {
                    $profile = new CandidateProfile();
                    $profile->setUser($user);
                    $profile->setLocation($uc['location']);
                    $this->em->persist($profile);
                    $candidateEntities[$uc['email']] = $profile;
                }

                $this->em->persist($user);
                $this->em->flush();
            } else {
                if ($uc['isCandidate'] && $user->getCandidateProfile()) {
                    $candidateEntities[$uc['email']] = $user->getCandidateProfile();
                }
            }

            if ($uc['role'] === 'ROLE_RECRUITER') {
                $recruiterEntity = $user;
            }

            // Populate Candidate Attributes & Projects
            if ($uc['isCandidate'] && isset($candidateEntities[$uc['email']])) {
                $candProfile = $candidateEntities[$uc['email']];

                if (!empty($uc['attrs'])) {
                    foreach ($uc['attrs'] as $attrName => $attrVal) {
                        $attrObj = $attributeEntities[$attrName] ?? null;
                        if ($attrObj) {
                            $existingVal = $this->em->getRepository(CandidateAttributeValue::class)->findOneBy([
                                'candidate' => $candProfile,
                                'attribute' => $attrObj
                            ]);
                            if (!$existingVal) {
                                $cav = new CandidateAttributeValue();
                                $cav->setCandidate($candProfile);
                                $cav->setAttribute($attrObj);
                                $cav->setValue($attrVal);
                                $this->em->persist($cav);
                            }
                        }
                    }
                    $this->em->flush();
                }

                if (!empty($uc['projects'])) {
                    foreach ($uc['projects'] as $pj) {
                        $existingPj = $this->em->getRepository(Project::class)->findOneBy([
                            'candidate' => $candProfile,
                            'name' => $pj['name']
                        ]);
                        if (!$existingPj) {
                            $proj = new Project();
                            $proj->setCandidate($candProfile);
                            $proj->setName($pj['name']);
                            $proj->setDescription($pj['desc']);
                            $proj->setDateStart(new \DateTime($pj['start']));
                            if (!empty($pj['end'])) {
                                $proj->setDateEnd(new \DateTime($pj['end']));
                            }
                            $proj->setTags($pj['tags']);
                            $this->em->persist($proj);
                        }
                    }
                    $this->em->flush();
                }
            }
        }

        // 5. Positions (Including matching the prohibited/approved examples in specification)
        $positionsData = [
            [
                'title' => 'Data Scientist',
                'company' => 'TechCorp Global',
                'level' => 'Middle',
                'shortDescription' => 'Seeking an experienced Data Scientist with solid ML background, high English proficiency, and Python expertise.',
                'isPublic' => true,
                'maxProjects' => 2,
                'projectTags' => ['Python', 'SQL', 'Machine Learning'],
                'attributes' => ['English Level', 'GPA', 'IELTS Score', 'Remote Work Availability'],
                'accessRules' => [
                    ['attribute' => 'IELTS Score', 'operator' => '>', 'value' => '7.0']
                ]
            ],
            [
                'title' => 'DevOps Engineer',
                'company' => 'CloudScale Systems',
                'level' => 'Junior',
                'shortDescription' => 'Join our high-availability cloud infrastructure team to streamline Kubernetes pipelines.',
                'isPublic' => true,
                'maxProjects' => 2,
                'projectTags' => ['Docker', 'Kubernetes', 'AWS', 'Linux'],
                'attributes' => ['Remote Work Availability', 'English Level'],
                'accessRules' => [
                    ['attribute' => 'Remote Work Availability', 'operator' => '=', 'value' => '1']
                ]
            ],
            [
                'title' => 'QA Engineer',
                'company' => 'QualityFirst Tech',
                'level' => 'Senior',
                'shortDescription' => 'Lead quality assurance automation, automated regression frameworks, and client demonstrations.',
                'isPublic' => true,
                'maxProjects' => 2,
                'projectTags' => ['Selenium', 'Cypress', 'TypeScript', 'Jest'],
                'attributes' => ['English Level', 'Presentation Skills'],
                'accessRules' => [
                    ['attribute' => 'Presentation Skills', 'operator' => '=', 'value' => 'Advanced']
                ]
            ],
            [
                'title' => 'Business Analyst',
                'company' => 'FinTech Ventures',
                'level' => 'Middle',
                'shortDescription' => 'Bridge stakeholders and engineering teams. Position is restricted to candidates with Advanced English.',
                'isPublic' => false,
                'maxProjects' => 3,
                'projectTags' => ['SQL', 'Jira', 'Agile', 'Vue.js'],
                'attributes' => ['English Level', 'GPA', 'Presentation Skills'],
                'accessRules' => [
                    ['attribute' => 'English Level', 'operator' => '=', 'value' => 'Advanced (C1)']
                ]
            ]
        ];

        $positionEntities = [];
        foreach ($positionsData as $posData) {
            $pos = $this->em->getRepository(Position::class)->findOneBy(['title' => $posData['title']]);
            if (!$pos) {
                $pos = new Position();
                $pos->setTitle($posData['title']);
                $pos->setCompany($posData['company']);
                $pos->setLevel($posData['level']);
                $pos->setShortDescription($posData['shortDescription']);
                $pos->setIsPublic($posData['isPublic']);
                $pos->setMaxProjects($posData['maxProjects']);
                $pos->setProjectTags($posData['projectTags']);

                foreach ($posData['attributes'] as $attrName) {
                    if (isset($attributeEntities[$attrName])) {
                        $pos->addAttribute($attributeEntities[$attrName]);
                    }
                }

                foreach ($posData['accessRules'] as $ruleData) {
                    if (isset($attributeEntities[$ruleData['attribute']])) {
                        $rule = new PositionAccessRule();
                        $rule->setPosition($pos);
                        $rule->setAttribute($attributeEntities[$ruleData['attribute']]);
                        $rule->setOperator($ruleData['operator']);
                        $rule->setValue($ruleData['value']);
                        $this->em->persist($rule);
                    }
                }

                $this->em->persist($pos);
                $this->em->flush();
            }
            $positionEntities[$posData['title']] = $pos;
        }

        // 6. Generate Sample CVs matching the positions
        $cvConfigs = [
            [
                'candidateEmail' => 'candidate@resumecraft.com',
                'positionTitle' => 'Data Scientist',
                'status' => 'published',
            ],
            [
                'candidateEmail' => 'paul.king@example.com',
                'positionTitle' => 'DevOps Engineer',
                'status' => 'published',
            ],
            [
                'candidateEmail' => 'lee.morris@example.com',
                'positionTitle' => 'QA Engineer',
                'status' => 'published',
            ],
            [
                'candidateEmail' => 'candidate@resumecraft.com',
                'positionTitle' => 'Business Analyst',
                'status' => 'draft',
            ],
        ];

        foreach ($cvConfigs as $cvc) {
            $candidate = $candidateEntities[$cvc['candidateEmail']] ?? null;
            $pos = $positionEntities[$cvc['positionTitle']] ?? null;
            if ($candidate && $pos) {
                $existingCv = $this->em->getRepository(Cv::class)->findOneBy([
                    'candidate' => $candidate,
                    'position' => $pos
                ]);
                if (!$existingCv) {
                    $cv = new Cv();
                    $cv->setCandidate($candidate);
                    $cv->setPosition($pos);
                    $cv->setStatus($cvc['status']);
                    $this->em->persist($cv);
                    $this->em->flush();

                    // Add recruiter like if published
                    if ($cvc['status'] === 'published' && $recruiterEntity) {
                        $like = new CvLike();
                        $like->setCv($cv);
                        $like->setRecruiter($recruiterEntity);
                        $this->em->persist($like);
                        $this->em->flush();
                    }
                }
            }
        }

        // 7. Seed Sample Discussions on Positions
        $discussions = [
            [
                'position' => 'Data Scientist',
                'author' => 'candidate@resumecraft.com',
                'content' => "Hello! Are candidates allowed to submit published papers or Kaggle medals as part of the application?\n\nLooking forward to the response!",
            ],
            [
                'position' => 'Data Scientist',
                'author' => 'recruiter@resumecraft.com',
                'content' => "Hi John! Absolutely, feel free to link your arXiv or GitHub portfolio in the **Portfolio URL** field.",
            ],
            [
                'position' => 'DevOps Engineer',
                'author' => 'paul.king@example.com',
                'content' => "Is the team working with **EKS** or self-managed vanilla Kubernetes clusters on bare metal?",
            ],
            [
                'position' => 'DevOps Engineer',
                'author' => 'recruiter@resumecraft.com',
                'content' => "We run primarily on AWS EKS with ArgoCD GitOps pipelines.",
            ],
        ];

        foreach ($discussions as $disc) {
            $pos = $positionEntities[$disc['position']] ?? null;
            $author = $this->em->getRepository(User::class)->findOneBy(['email' => $disc['author']]);
            if ($pos && $author) {
                $existingPost = $this->em->getRepository(DiscussionPost::class)->findOneBy([
                    'position' => $pos,
                    'author' => $author,
                    'content' => $disc['content']
                ]);
                if (!$existingPost) {
                    $post = new DiscussionPost();
                    $post->setPosition($pos);
                    $post->setAuthor($author);
                    $post->setContent($disc['content']);
                    $this->em->persist($post);
                }
            }
        }
        $this->em->flush();

        $io->success('Comprehensive demo data seeded successfully!');
        $io->listing([
            'Admin: admin@resumecraft.com (Password: admin123)',
            'Recruiter: recruiter@resumecraft.com (Password: recruiter123)',
            'Candidate: candidate@resumecraft.com (Password: candidate123)',
            'Candidate: paul.king@example.com (Password: candidate123)',
            'Candidate: lee.morris@example.com (Password: candidate123)',
        ]);

        return Command::SUCCESS;
    }
}
