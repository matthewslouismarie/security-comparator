<?php

namespace App\Repository;

use App\Entity\PageMetric;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Exception;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PageMetricRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PageMetric::class);
    }

    public function getArray(string $participantId): array
    {
        $timeSpentArray = [];
        $pageMetrics = $this->findBy([
            "participantId" => $participantId,
        ]);
        $nResponseMetrics = count($pageMetrics) - 1;
        for ($i = 0; $i < $nResponseMetrics; ++$i) {
            if (PageMetric::RESPONSE === $pageMetrics[$i]->getType() &&
            PageMetric::REQUEST === $pageMetrics[$i + 1]->getType()) {
                $timeSpentArray[] = [
                    'timeSpent' => $pageMetrics[$i + 1]->getMicrotime() - $pageMetrics[$i]->getMicrotime(),
                    'localPath' => $pageMetrics[$i]->getLocalPath(),
                ];
            }
        }

        return $timeSpentArray;
    }
}