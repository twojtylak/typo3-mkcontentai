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

namespace DMK\MkContentAi\Backend\Hooks;

use DMK\MkContentAi\Domain\Model\TtContent;
use DMK\MkContentAi\Domain\Repository\TtContentRepository;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageContentHandler
{
    private TtContentRepository $ttContentRepository;

    public function __construct(TtContentRepository $ttContentRepository)
    {
        $this->ttContentRepository = $ttContentRepository;
    }

    public function copyContentRecord(int $uid, int $targetPid, string $bodyText, string $targetLanguageType): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $command = [];
        $command['tt_content'][$uid]['copy'] = $targetPid;

        $dataHandler->start([], $command);
        $dataHandler->process_cmdmap();

        $updateData = $this->getUpdatedContentRecord($uid, $bodyText, $targetLanguageType, $dataHandler);

        if ([] === $updateData) {
            return;
        }

        $dataHandler->start($updateData, []);
        $dataHandler->process_datamap();
    }

    /**
     * @return array<string,array<int|string, array<string, int|string>>>
     */
    protected function getUpdatedContentRecord(int $uid, string $bodyText, string $targetLanguageType, DataHandler $dataHandler): array
    {
        /** @var TtContent|null $record */
        $record = $this->ttContentRepository->findByUid($uid);

        if (null === $record) {
            return [];
        }

        $recordHeader = $record->getHeader();
        $recordSortingValue = $record->getSorting();
        $newContentUid = $dataHandler->copyMappingArray['tt_content'][$uid];

        return [
            'tt_content' => [
                $newContentUid => [
                    'bodytext' => $bodyText,
                    'header' => '(Transformed into '.$targetLanguageType.' language) '.$recordHeader,
                    'sorting' => $recordSortingValue + 1,
                ],
            ],
        ];
    }
}
