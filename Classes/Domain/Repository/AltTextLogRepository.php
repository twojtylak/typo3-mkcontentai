<?php

declare(strict_types=1);

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of TYPO3 CMS-based extension "mkcontentai" by DMK E-BUSINESS GmbH.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace DMK\MkContentAi\Domain\Repository;

use DMK\MkContentAi\Domain\Model\AltTextLog;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<AltTextLog>
 */
class AltTextLogRepository extends Repository
{
    private QueryBuilder $altTextLogsQueryBuilder;

    public function __construct(QueryBuilder $altTextLogsQueryBuilder)
    {
        parent::__construct();
        $this->altTextLogsQueryBuilder = $altTextLogsQueryBuilder;
    }

    /**
     * @return AltTextLog[]
     */
    public function getAltTextLogs(int $page, int $limit): array
    {
        $uids = $this->getRecordsUids($page, $limit);

        if (empty($uids)) {
            return [];
        }

        $query = $this->createQuery();
        $query->matching($query->in('uid', $uids));
        $records = $query->execute()->toArray();

        $orderedRecords = [];

        foreach ($uids as $uid) {
            $filteredRecords = array_filter($records, function (AltTextLog $item) use ($uid) {
                return $item->getUid() === $uid;
            });

            $filteredRecord = $filteredRecords[array_key_first($filteredRecords)] ?? null;

            if (null === $filteredRecord) {
                continue;
            }

            $orderedRecords[$filteredRecord->getUid()] = $filteredRecord;
        }

        return $orderedRecords;
    }

    /**
     * @return AltTextLog[]
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getRecordsUids(int $page, int $limit): array
    {
        $offset = $page > 1 ? ($page - 1) * $limit : 0;

        $this->altTextLogsQueryBuilder
            ->select('uid')
            ->from('tx_mkcontentai_domain_model_alt_text_logs')
            ->where(
                $this->altTextLogsQueryBuilder->expr()->eq('deleted', $this->altTextLogsQueryBuilder->createNamedParameter(0, Connection::PARAM_STR))
            )
            ->groupBy('sys_file_metadata')
            ->orderBy('crdate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $this->altTextLogsQueryBuilder->executeQuery()->fetchFirstColumn();
    }

    public function hasNextPage(int $page, int $limit): bool
    {
        return count($this->getRecordsUids($page, $limit)) > 0;
    }

    public function deleteAltTextLog(int $uidMetadataFile, string $alternativeNewMetadata): void
    {
        $this->altTextLogsQueryBuilder
            ->delete('tx_mkcontentai_domain_model_alt_text_logs')
            ->where(
                $this->altTextLogsQueryBuilder->expr()->eq('sys_file_metadata', $this->altTextLogsQueryBuilder->createNamedParameter($uidMetadataFile, Connection::PARAM_STR))
            )
            ->andWhere(
                $this->altTextLogsQueryBuilder->expr()->neq('alternative', $this->altTextLogsQueryBuilder->createNamedParameter($alternativeNewMetadata, Connection::PARAM_STR))
            )
            ->executeStatement();
    }
}
