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

use DMK\MkContentAi\Backend\Event\AiAltTextGeneratedEvent;
use DMK\MkContentAi\Domain\Model\SysFileMetadataAltTextLog;
use DMK\MkContentAi\Domain\Repository\SysFileMetadataRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

final class AiAltTextGeneratedEventListener
{
    protected SysFileMetadataRepository $sysFileMetaRepository;
    protected PersistenceManager $persistenceManager;

    public function __construct(SysFileMetadataRepository $sysFileMetaRepository, PersistenceManager $persistenceManager)
    {
        $this->sysFileMetaRepository = $sysFileMetaRepository;
        $this->persistenceManager = $persistenceManager;
    }

    public function __invoke(AiAltTextGeneratedEvent $event): void
    {
        switch ('sys_file_metadata' === $event->getTableName()) {
            case SysFileMetadataAltTextLog::TYPE:
                $record = $this->sysFileMetaRepository->findByUid($event->getResourceUid());
                $object = !$record ? null : GeneralUtility::makeInstance(SysFileMetadataAltTextLog::class, $record, $event->getAltText());
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Provided invalid type for alternative text logs: %s', $event->getTableName()));
        }

        if (null === $record || null === $object) {
            return;
        }

        $record->addAltTextLog($object);
        $this->persistenceManager->update($record);
        $this->persistenceManager->persistAll();
    }
}
