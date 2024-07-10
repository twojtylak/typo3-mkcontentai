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

namespace DMK\MkContentAi\Controller;

use DMK\MkContentAi\Http\Client\ClientInterface;
use DMK\MkContentAi\Service\SiteLanguageService;
use DMK\MkContentAi\Utility\AiClientUtility;
use DMK\MkContentAi\Utility\PermissionsUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class SettingsController extends BaseController
{
    private PermissionsUtility $permissionsUtility;

    public function injectPermissionsUtility(PermissionsUtility $permissionsUtility): void
    {
        $this->permissionsUtility = $permissionsUtility;
    }

    /**
     * Configure settings for various AI engines.
     *
     * @param string               $openAiApiKeyValue     API key for OpenAI client
     * @param array<string, mixed> $stableDiffusionValues Array with specific keys and values
     * @param string               $stabilityAiApiValue   API key for Stability AI client
     * @param string               $altTextAiApiValue     API key for Alt Text AI client
     * @param int                  $imageAiEngine         Indicator of which AI engine to use for image processing
     */
    public function settingsAction(string $openAiApiKeyValue = '', array $stableDiffusionValues = [], string $stabilityAiApiValue = '', string $altTextAiApiValue = '', int $imageAiEngine = 0): ResponseInterface
    {
        $this->initializeAction();
        $this->moduleTemplateFactory = GeneralUtility::makeInstance(ModuleTemplateFactory::class);
        $moduleTemplate = $this->createRequestModuleTemplate();

        if (false === $this->permissionsUtility->userHasAccessToSettings()) {
            $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
            $translatedMessage = LocalizationUtility::translate('labelErrorSettingsPermissions', 'mkcontentai') ?? '';
            $this->addFlashMessage($translatedMessage, '', ContextualFeedbackSeverity::WARNING, false);

            return $moduleTemplate->renderResponse('InsufficientPermissions');
        }

        $openAi = AiClientUtility::createOpenAiClient();
        $stableDiffusion = AiClientUtility::createStableDiffusionClient();
        $stabilityAi = AiClientUtility::createStabilityAiClient();
        $altTextAi = AiClientUtility::createAltTextClient();
        $this->setApiKey($openAiApiKeyValue, $openAi);
        $this->setApiKey($stableDiffusionValues['api'] ?? '', $stableDiffusion);
        $this->setApiKey($stabilityAiApiValue, $stabilityAi);
        $this->setApiKey($altTextAiApiValue, $altTextAi);

        /** @var SiteLanguageService $siteLanguageService */
        $siteLanguageService = GeneralUtility::makeInstance(SiteLanguageService::class);

        if ($imageAiEngine) {
            $registry = GeneralUtility::makeInstance(Registry::class);
            $registry->set(AiImageController::class, AiImageController::GENERATOR_ENGINE_KEY, $imageAiEngine);
        }

        if ($this->request->hasArgument('stableDiffusionValues')) {
            $stableDiffusionValues = $this->request->getArgument('stableDiffusionValues');
            if (is_array($stableDiffusionValues)) {
                $stableDiffusionModel = $stableDiffusionValues['model'];
                $stableDiffusion->setCurrentModel($stableDiffusionModel);
            }
        }

        if ($this->request->hasArgument('altTextAiLanguage')) {
            /** @var string $altTextAiLanguage */
            $altTextAiLanguage = $this->request->getArgument('altTextAiLanguage');
            if (isset($altTextAiLanguage)) {
                $this->setLanguage($altTextAiLanguage, $altTextAi, $siteLanguageService);
            }
        }

        $moduleTemplate->assignMultiple(
            [
                'openAiApiKey' => $openAi->getMaskedApiKey(),
                'stableDiffusionApiKey' => $stableDiffusion->getMaskedApiKey(),
                'stableDiffusionModel' => $stableDiffusion->getCurrentModel(),
                'stabilityAiApiValue' => $stabilityAi->getMaskedApiKey(),
                'altTextAiApiValue' => $altTextAi->getMaskedApiKey(),
                'imageAiEngine' => SettingsController::getImageAiEngine(),
                'altTextAiLanguage' => $siteLanguageService->getAllAvailableLanguages(),
                'selectedAltTextAiLanguage' => $siteLanguageService->getLanguage(),
            ]
        );

        try {
            $moduleTemplate->assignMultiple(
                [
                    'stableDiffusionModels' => array_merge(
                        [
                            'none' => [
                                'model_id' => '',
                            ],
                        ],
                        $stableDiffusion->modelList()
                    ),
                ]
            );
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', ContextualFeedbackSeverity::ERROR, false);
        }
        if (null === $this->moduleTemplateFactory) {
            $translatedMessage = LocalizationUtility::translate('labelErrorModuleTemplateFactory', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage, 1623345720);
        }

        return $moduleTemplate->renderResponse('Settings');
    }

    private function setLanguage(string $language, ClientInterface $client, SiteLanguageService $siteLanguageService): void
    {
        if ($language) {
            $siteLanguageService->setLanguage($language);
            $translatedMessage = LocalizationUtility::translate('labelSavedLanguage', 'mkcontentai') ?? '';

            $this->addFlashMessage($translatedMessage);
            try {
                $client->validateApiCall();
            } catch (\Exception $e) {
                $translatedMessage = (403 === $e->getCode()) ? (LocalizationUtility::translate('labelErrorSavedLanguage', 'mkcontentai') ?? '') : $e->getMessage();
                $this->addFlashMessage($translatedMessage, '', ContextualFeedbackSeverity::ERROR, false);
            }
        }
    }

    private function setApiKey(string $key, ClientInterface $client): void
    {
        if ($key) {
            $client->setApiKey($key);
            $translatedMessage = LocalizationUtility::translate('labelSavedKey', 'mkcontentai') ?? '';
            $this->addFlashMessage($translatedMessage);
            try {
                $client->validateApiCall();
            } catch (\Exception $e) {
                $this->addFlashMessage($e->getMessage(), '', ContextualFeedbackSeverity::ERROR, false);
            }
        }
    }

    public static function getImageAiEngine(): int
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $imageEngineKey = intval($registry->get(AiImageController::class, AiImageController::GENERATOR_ENGINE_KEY));
        if (!array_key_exists($imageEngineKey, AiImageController::GENERATOR_ENGINE)) {
            $imageEngineKey = array_key_first(AiImageController::GENERATOR_ENGINE);
        }

        return $imageEngineKey;
    }
}
