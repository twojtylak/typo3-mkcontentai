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

use DMK\MkContentAi\Backend\Event\AiAltTextGeneratedEvent;
use DMK\MkContentAi\DTO\FileAltTextDTO;
use DMK\MkContentAi\Http\Client\AltTextClient;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Extbase\Domain\Model\File;

class AiAltTextService
{
    public AltTextClient $altTextClient;
    public FileService $fileService;
    protected EventDispatcher $eventDispatcher;
    private int $skippedAltTextForFiles = 0;
    private int $failedProcessedImages = 0;
    private int $generatedAltTexts = 0;
    private int $fileIsNotImage = 0;
    private int $hasAltText = 0;

    public function __construct(AltTextClient $altTextClient, FileService $fileService, EventDispatcher $eventDispatcher)
    {
        $this->altTextClient = $altTextClient;
        $this->fileService = $fileService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws \Exception
     */
    public function getAltText(File $file, ?string $languageIsoCode = null): string
    {
        try {
            $altText = $this->altTextClient->getByAssetId($file->getOriginalResource()->getUid(), $languageIsoCode);
        } catch (\Exception $e) {
            if (404 != $e->getCode()) {
                throw $e;
            }
            $altText = $this->altTextClient->getAltTextForFile($file, $languageIsoCode);

            return $altText;
        }

        return $altText;
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\File[]
     */
    public function getListOfFiles(string $folderName): array
    {
        return $this->fileService->getFilesFromExistingFolder($folderName);
    }

    /**
     * @return array<int|string, FileAltTextDTO>
     */
    public function getEmptyAltTextFiles(string $folderName): array
    {
        return $this->fileService->getFilesWithoutAltText($folderName);
    }

    public function getFileById(string $fileId): ?File
    {
        return $this->fileService->getFileById($fileId);
    }

    /**
     * @return array<int|string, FileAltTextDTO>
     */
    public function getMultipleAltTextsForImages(string $folderName): array
    {
        $finalFilesWithAltText = [];
        $files = $this->getListOfFiles($folderName);
        $emptyAltTextFiles = $this->getEmptyAltTextFiles($folderName);

        foreach ($files as $file) {
            $fileUid = $file->getUid();
            $fileById = $this->getFileById((string) $fileUid);
            if (null == $fileById) {
                continue;
            }
            if (!empty($fileById->getOriginalResource()->getProperty('alternative'))) {
                $this->incrementHasAltText();
                continue;
            }
            if (!$this->isSupportedImage($file->getExtension())) {
                $this->incrementFileIsNotImage();
                continue;
            }
            if (!isset($emptyAltTextFiles[$fileUid])) {
                $this->incrementSkippedAltTextFiles();
                continue;
            }
            try {
                $emptyAltTextFiles[$fileUid]->setAltText($this->getAltText($fileById));
                $emptyAltTextFiles[$fileUid]->setFile($file);
                $finalFilesWithAltText[$fileUid] = $emptyAltTextFiles[$fileUid];
                $this->incrementGeneratedAltText();
            } catch (\Exception $e) {
                $this->incrementFailedProcessedImages();
                continue;
            }
        }

        return $finalFilesWithAltText;
    }

    /**
     * @param FileAltTextDTO[] $altTexts
     */
    public function saveAltTextsMetaData(array $altTexts): void
    {
        foreach ($altTexts as $fileAltTextDTO) {
            if (null == $fileAltTextDTO->getFile()) {
                continue;
            }
            $altText = $fileAltTextDTO->getAltText() ?? '';
            $metadata = $fileAltTextDTO->getFile()->getMetaData();
            $metadata->offsetSet('alternative', $altText);
            $metadata->save();
            $this->processGeneratedAltTextLog('sys_file_metadata', $metadata['uid'], $altText);
        }
    }

    public function getFailedProcessedImages(): int
    {
        return $this->failedProcessedImages;
    }

    public function getGeneratedAltTexts(): int
    {
        return $this->generatedAltTexts;
    }

    public function getFileIsNotImage(): int
    {
        return $this->fileIsNotImage;
    }

    public function getHasAltText(): int
    {
        return $this->hasAltText;
    }

    public function incrementSkippedAltTextFiles(): void
    {
        ++$this->skippedAltTextForFiles;
    }

    public function incrementGeneratedAltText(): void
    {
        ++$this->generatedAltTexts;
    }

    public function incrementFailedProcessedImages(): void
    {
        ++$this->failedProcessedImages;
    }

    public function incrementFileIsNotImage(): void
    {
        ++$this->fileIsNotImage;
    }

    public function incrementHasAltText(): void
    {
        ++$this->hasAltText;
    }

    public function processGeneratedAltTextLog(string $tableName, int $resourceUid, string $altText): void
    {
        $event = new AiAltTextGeneratedEvent($tableName, $resourceUid, $altText);
        $this->eventDispatcher->dispatch($event);
    }

    protected function isSupportedImage(string $fileExtension): bool
    {
        $supportedFormatsImages = ['jpg', 'png', 'gif', 'webp', 'bmp'];

        return in_array($fileExtension, $supportedFormatsImages);
    }
}
