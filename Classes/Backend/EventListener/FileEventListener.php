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

namespace DMK\MkContentAi\Backend\EventListener;

use DMK\MkContentAi\Service\AiAltTextLogsService;
use TYPO3\CMS\Core\Resource\Event\BeforeFileDeletedEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;

final class FileEventListener
{
    private AiAltTextLogsService $altTextLogsService;

    public function __construct(AiAltTextLogsService $altTextLogsService)
    {
        $this->altTextLogsService = $altTextLogsService;
    }

    public function beforeEventDeleted(BeforeFileDeletedEvent $event): void
    {
        /**
         * @var File $file
         */
        $file = $event->getFile();

        if ($file instanceof ProcessedFile) {
            return;
        }

        /** @var int|null $fileMetadataUid */
        $fileMetadataUid = $file->getMetaData()['uid'] ?? null;

        if (null === $fileMetadataUid) {
            return;
        }

        $this->altTextLogsService->deleteRelatedChildren($fileMetadataUid);
    }
}
