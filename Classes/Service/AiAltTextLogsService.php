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

namespace DMK\MkContentAi\Service;

use DMK\MkContentAi\Domain\Model\AltTextLog;
use DMK\MkContentAi\Domain\Repository\AltTextLogRepository;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

class AiAltTextLogsService
{
    public function __construct(private QueryBuilder $altTextLogsQueryBuilder, private AltTextLogRepository $altTextLogRepository)
    {
    }

    /**
     * Delete related child records based on parent ID.
     */
    public function deleteRelatedChildren(int $parentId): void
    {
        $this->altTextLogsQueryBuilder
            ->delete('tx_mkcontentai_domain_model_alt_text_logs')
            ->where(
                $this->altTextLogsQueryBuilder->expr()->eq('sys_file_metadata', $this->altTextLogsQueryBuilder->createNamedParameter($parentId, Connection::PARAM_INT))
            );

        $this->altTextLogsQueryBuilder->executeStatement();
    }

    /**
     * @param AltTextLog[] $altTextLogs
     *
     * @return AltTextLog[]
     */
    public function showNewestLogs(array $altTextLogs): array
    {
        $logsByNewestVersion = [];
        $newestCreatedAt = [];

        foreach ($altTextLogs as $altTextLog) {
            $fileUid = $altTextLog->getFileUid();
            $createdAt = $altTextLog->getCreatedAt();

            if (isset($newestCreatedAt[$fileUid])) {
                if ($createdAt > $newestCreatedAt[$fileUid]) {
                    $logsByNewestVersion[$fileUid] = $altTextLog;
                    $newestCreatedAt[$fileUid] = $createdAt;
                }
            }
            if (!isset($newestCreatedAt[$fileUid])) {
                $logsByNewestVersion[$fileUid] = $altTextLog;
                $newestCreatedAt[$fileUid] = $createdAt;
            }
        }

        return array_values($logsByNewestVersion);
    }

    public function deleteLogsByFileMetadata(int $fileMetadataUid, string $alternativeNewMetadata): void
    {
        $this->altTextLogRepository->deleteAltTextLog($fileMetadataUid, $alternativeNewMetadata);
    }
}
