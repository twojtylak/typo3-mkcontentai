<?php

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

namespace DMK\MkContentAi\Http\Client;

use DMK\MkContentAi\Domain\Model\Image;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class BaseClient
{
    private const API_KEY = 'apiKey';

    /**
     * @throws \Exception
     */
    public function validate(): void
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            $translatedMessage = LocalizationUtility::translate('labelApiKey', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }
    }

    public function getApiKey(): string
    {
        $registry = $this->getRegistry();
        $class = $this->getClass();

        return strval($registry->get($class, self::API_KEY));
    }

    public function getMaskedApiKey(): string
    {
        $apiKey = $this->getApiKey();
        $length = strlen($apiKey);
        $charsCount = 5;

        if ($length > $charsCount * 2) {
            return substr($apiKey, 0, $charsCount).str_repeat('*', $length - $charsCount * 2).substr($apiKey, -$charsCount, $charsCount);
        }
        if ($length) {
            return $apiKey;
        }

        return '';
    }

    public function setApiKey(string $apiKey): void
    {
        $registry = $this->getRegistry();
        $class = $this->getClass();
        $registry->set($class, self::API_KEY, $apiKey);
    }

    /**
     * @return array<int,array<string,string>>
     */
    public function getAvailableResolutions(string $actionName): array
    {
        $actionName = '';

        return [];
    }

    public function imageToVideo(string $filePath): ?Image
    {
        $filePath = null;

        return $filePath;
    }

    public function validateApiKey(): bool
    {
        try {
            $this->validateApiCall();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function validateApiCall(): \stdClass
    {
        return new \stdClass();
    }

    protected function getRegistry(): Registry
    {
        return GeneralUtility::makeInstance(Registry::class);
    }

    protected function getClass(): string
    {
        return get_class($this);
    }
}
