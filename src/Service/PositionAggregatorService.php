<?php

namespace App\Service;

use App\Entity\Position;

class PositionAggregatorService
{
    /**
     * Compute aggregated results for a position across its submitted CVs and candidate attribute values.
     */
    public function getAggregatedData(Position $position): array
    {
        $cvs = $position->getCvs();
        $totalCvs = $cvs->count();

        $attributesData = [];

        foreach ($position->getAttributes() as $attribute) {
            $attrId = $attribute->getId();
            $attrName = $attribute->getName();
            $attrType = $attribute->getType();

            // Collect all values for this attribute from candidates who submitted CVs for this position
            $rawValues = [];
            foreach ($cvs as $cv) {
                $candidate = $cv->getCandidate();
                if (!$candidate) {
                    continue;
                }

                foreach ($candidate->getAttributeValues() as $attrVal) {
                    if ($attrVal->getAttribute() && $attrVal->getAttribute()->getId() === $attrId) {
                        $val = $attrVal->getValue();
                        if ($val !== null && $val !== '') {
                            $rawValues[] = $val;
                        }
                    }
                }
            }

            $isNumeric = in_array(strtolower((string) $attrType), ['numeric', 'number', 'integer', 'float', 'decimal']);

            if ($isNumeric) {
                $numericValues = [];
                foreach ($rawValues as $v) {
                    if (is_numeric($v)) {
                        $numericValues[] = (float) $v;
                    }
                }

                if (count($numericValues) > 0) {
                    $min = min($numericValues);
                    $max = max($numericValues);
                    $avg = round(array_sum($numericValues) / count($numericValues), 2);
                    $count = count($numericValues);
                    $summary = sprintf('Avg: %s | Min: %s | Max: %s (Count: %d)', $avg, $min, $max, $count);
                } else {
                    $min = null;
                    $max = null;
                    $avg = null;
                    $count = 0;
                    $summary = 'No numeric data available';
                }

                $attributesData[] = [
                    'id' => $attrId,
                    'title' => $attrName,
                    'type' => $attrType,
                    'aggregation_type' => 'numeric',
                    'count' => $count,
                    'min' => $min,
                    'max' => $max,
                    'avg' => $avg,
                    'popular_values' => [],
                    'summary' => $summary,
                ];
            } else {
                // Non-numeric (String, Text, Dropdown, Boolean, Date, Period, etc.)
                $freq = [];
                foreach ($rawValues as $v) {
                    if (is_array($v)) {
                        $v = implode(', ', $v);
                    } elseif (is_bool($v)) {
                        $v = $v ? 'Yes' : 'No';
                    } else {
                        $v = trim((string) $v);
                    }

                    if ($v === '') {
                        continue;
                    }

                    $freq[$v] = ($freq[$v] ?? 0) + 1;
                }

                arsort($freq);

                $popular = [];
                $topStrings = [];
                $rank = 0;
                foreach ($freq as $valKey => $freqCount) {
                    $popular[] = [
                        'value' => (string) $valKey,
                        'count' => $freqCount,
                    ];
                    if ($rank < 4) {
                        $topStrings[] = sprintf('%s (%d)', $valKey, $freqCount);
                        $rank++;
                    }
                }

                $count = count($rawValues);
                $summary = count($topStrings) > 0 ? implode(', ', $topStrings) : 'No data available';

                $attributesData[] = [
                    'id' => $attrId,
                    'title' => $attrName,
                    'type' => $attrType,
                    'aggregation_type' => 'text',
                    'count' => $count,
                    'min' => null,
                    'max' => null,
                    'avg' => null,
                    'popular_values' => $popular,
                    'summary' => $summary,
                ];
            }
        }

        $cvList = [];
        foreach ($cvs as $c) {
            $candName = 'Candidate #' . $c->getId();
            if ($c->getCandidate() && $c->getCandidate()->getUser()) {
                $u = $c->getCandidate()->getUser();
                $details = $u->getUserDetails();
                if ($details && ($details->getFirstName() || $details->getLastName())) {
                    $candName = trim($details->getFirstName() . ' ' . $details->getLastName());
                } else {
                    $candName = $u->getUsername() ?? $u->getEmail() ?? ('Candidate #' . $c->getId());
                }
            }
            $cvList[] = [
                'id' => $c->getId(),
                'candidateName' => $candName,
                'status' => $c->getStatus(),
                'likes' => $c->getLikes()->count(),
                'createdAt' => $c->getCreatedAt() ? $c->getCreatedAt()->format('Y-m-d H:i') : null,
            ];
        }

        return [
            'status' => 'success',
            'position' => [
                'id' => $position->getId(),
                'title' => $position->getTitle(),
                'company' => $position->getCompany(),
                'level' => $position->getLevel(),
                'shortDescription' => $position->getShortDescription(),
                'isPublic' => $position->isPublic(),
                'totalCvs' => $totalCvs,
                'projectTags' => $position->getProjectTags() ?? [],
                'apiToken' => $position->getApiToken(),
                'cvs' => $cvList,
            ],
            'attributes' => $attributesData,
        ];
    }
}
