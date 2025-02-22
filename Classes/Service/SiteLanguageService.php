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

use DMK\MkContentAi\Http\Client\ClientInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class SiteLanguageService
{
    private const SELECTED_LANGUAGE = 'language';

    private Registry $registry;

    private SiteFinder $siteFinder;

    public function __construct()
    {
        $this->registry = GeneralUtility::makeInstance(Registry::class);
        $this->siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
    }

    public function getLanguage(): ?string
    {
        return $this->registry->get(__CLASS__, self::SELECTED_LANGUAGE);
    }

    public function getFullLanguageName(): ?string
    {
        $allSites = $this->siteFinder->getAllSites();

        foreach ($allSites as $site) {
            $siteLanguages = $site->getAllLanguages();

            foreach ($siteLanguages as $siteLanguage) {
                /** @var array<string, string> $language */
                $language = $siteLanguage->toArray();
                if ($language['twoLetterIsoCode'] === $this->getLanguage()) {
                    return $language['title'];
                }
            }
        }

        return $this->getLanguage();
    }

    public function setLanguage(string $language): void
    {
        $this->registry->set(__CLASS__, self::SELECTED_LANGUAGE, $language);
    }

    /**
     * @return array<string, string>
     */
    public function getAllAvailableLanguages(): array
    {
        $allSites = $this->siteFinder->getAllSites();
        $languageCode = [];

        foreach ($allSites as $site) {
            $siteLanguages = $site->getAllLanguages();

            foreach ($siteLanguages as $siteLanguage) {
                /** @var array<string, string> $language */
                $language = $siteLanguage->toArray();
                $languageCode[$language['twoLetterIsoCode']] = $language['title'];
            }
        }

        return $languageCode;
    }

    public function getLanguageIsoCodeByUid(?int $uid): ?string
    {
        $allSites = $this->siteFinder->getAllSites();
        $languages = [];

        foreach ($allSites as $site) {
            $siteLanguages = $site->getAllLanguages();

            foreach ($siteLanguages as $siteLanguage) {
                /** @var array<string, string> $language */
                $language = $siteLanguage->toArray();
                $languages[$language['languageId']] = $language;
            }
        }

        if (isset($languages[$uid])) {
            return $languages[$uid]['twoLetterIsoCode'];
        }

        return null;
    }

    public function setLanguageAltTextWithTestApiCall(string $language, ClientInterface $client): void
    {
        if ($language) {
            $this->setLanguage($language);
            try {
                $client->getTestApiCall();
            } catch (\Exception $e) {
                (403 === $e->getCode()) ?
                    $translatedMessage = LocalizationUtility::translate('labelErrorSavedLanguage', 'mkcontentai') ?? '' :
                    $translatedMessage = $e->getMessage();
                $this->sendFlashMessage($translatedMessage);
            }
        }
    }

    public function sendFlashMessage(string $message): void
    {
        $message = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            '',
            FlashMessage::ERROR,
            true
        );
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $messageQueue->addMessage($message);
    }
}
