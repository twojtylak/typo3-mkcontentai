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

use DMK\MkContentAi\Service\AiAltTextLogsService;
use TYPO3\CMS\Core\DataHandling\DataHandler;

class CustomDataHandler
{
    private AiAltTextLogsService $altTextLogsService;

    public function __construct(AiAltTextLogsService $altTextLogsService)
    {
        $this->altTextLogsService = $altTextLogsService;
    }

    /**
     * This method is called after the DataHandler has processed the field array.
     *
     * @param string                    $status      The status, such as 'new', 'update', 'delete'
     * @param string                    $table       The database table being processed
     * @param int                       $recordId    The record ID being processed
     * @param array<string, string|int> $fieldArray  The field array being processed
     * @param DataHandler               $dataHandler Reference to the DataHandler object
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return void
     */
    public function processDatamap_postProcessFieldArray($status, $table, $recordId, $fieldArray, DataHandler $dataHandler)
    {
        if ('update' === $status && 'sys_file_metadata' === $table && isset($fieldArray['alternative'])) {
            $fileMetadataUid = $recordId;
            /** @var string $alternativeNewMetadata */
            $alternativeNewMetadata = $fieldArray['alternative'];

            $this->altTextLogsService->deleteLogsByFileMetadata($fileMetadataUid, $alternativeNewMetadata);
        }
    }
}
