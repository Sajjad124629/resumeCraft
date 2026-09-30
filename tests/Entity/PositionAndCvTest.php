<?php

namespace App\Tests\Entity;

use App\Entity\Attribute;
use App\Entity\CandidateAttributeValue;
use App\Entity\CandidateProfile;
use App\Entity\Cv;
use App\Entity\Position;
use App\Entity\PositionAccessRule;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class PositionAndCvTest extends TestCase
{
    public function testCvBelongsToCandidateAndPosition(): void
    {
        $candidate = new CandidateProfile();
        $user = new User();
        $candidate->setUser($user);

        $position = new Position();
        $position->setTitle('Software Engineer');

        $cv = new Cv();
        $cv->setCandidate($candidate);
        $cv->setPosition($position);
        $cv->setStatus('draft');

        $this->assertSame($candidate, $cv->getCandidate());
        $this->assertSame($position, $cv->getPosition());
        $this->assertSame('draft', $cv->getStatus());
        $this->assertSame(1, $cv->getVersion());
        $this->assertCount(0, $cv->getLikes());
    }

    public function testPositionAccessRuleOperators(): void
    {
        $position = new Position();
        $position->setTitle('Senior DevOps');
        $position->setIsPublic(false);

        $attribute = new Attribute();
        $attribute->setName('IELTS Score');
        $attribute->setType('number');

        $rule = new PositionAccessRule();
        $rule->setPosition($position);
        $rule->setAttribute($attribute);
        $rule->setOperator('>');
        $rule->setValue('7.0');

        $position->getAccessRules()->add($rule);

        $this->assertFalse($position->isPublic());
        $this->assertCount(1, $position->getAccessRules());
        $this->assertSame('>', $rule->getOperator());
        $this->assertSame('7.0', $rule->getValue());
    }
}
