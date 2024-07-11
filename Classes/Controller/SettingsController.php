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
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\TemplateView;

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
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @param string               $openAiApiKeyValue     API key for OpenAI client
     * @param array<string, mixed> $stableDiffusionValues Array with specific keys and values
     * @param string               $stabilityAiApiValue   API key for Stability AI client
     * @param string               $altTextAiApiValue     API key for Alt Text AI client
     * @param string               $summAiApiValue        API key for SummAI client
     * @param int                  $imageAiEngine         Indicator of which AI engine to use for image processing
     * @param string|null          $summAiUserEmail       Email used with SummAI account
     */
    public function settingsAction(string $openAiApiKeyValue = '', array $stableDiffusionValues = [], string $stabilityAiApiValue = '', string $altTextAiApiValue = '', string $summAiApiValue = '', int $imageAiEngine = 0, ?string $summAiUserEmail = null): ResponseInterface
    {
        $validateSumAiEmail = 'POST' === $this->request->getMethod();
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:mkcontentai/Resources/Public/Css/base.css');

        $this->moduleTemplateFactory = GeneralUtility::makeInstance(ModuleTemplateFactory::class);
        /** @var TemplateView $view */
        $view = $this->view;
        if (false === $this->permissionsUtility->userHasAccessToSettings()) {
            $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
            $translatedMessage = LocalizationUtility::translate('labelErrorSettingsPermissions', 'mkcontentai') ?? '';
            $this->addFlashMessage($translatedMessage, '', AbstractMessage::WARNING, false);
            $moduleTemplate->setContent($view->renderSection('InsufficientPermissions'));

            return $this->htmlResponse($moduleTemplate->renderContent());
        }

        $openAi = AiClientUtility::createOpenAiClient();
        $stableDiffusion = AiClientUtility::createStableDiffusionClient();
        $stabilityAi = AiClientUtility::createStabilityAiClient();
        $altTextAi = AiClientUtility::createAltTextClient();
        $summAi = AiClientUtility::createSummAiClient();
        $this->setApiKey($openAiApiKeyValue, $openAi);
        $this->setApiKey($stableDiffusionValues['api'] ?? '', $stableDiffusion);
        $this->setApiKey($stabilityAiApiValue, $stabilityAi);
        $this->setApiKey($altTextAiApiValue, $altTextAi);
        $this->setApiKey($summAiApiValue, $summAi);
        $summAi->setEmail($summAi->checkEmailFromRequest($summAiUserEmail), $validateSumAiEmail);

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

        $this->view->assignMultiple(
            [
                'openAiApiKey' => $openAi->getMaskedApiKey(),
                'stableDiffusionApiKey' => $stableDiffusion->getMaskedApiKey(),
                'stableDiffusionModel' => $stableDiffusion->getCurrentModel(),
                'stabilityAiApiValue' => $stabilityAi->getMaskedApiKey(),
                'altTextAiApiValue' => $altTextAi->getMaskedApiKey(),
                'imageAiEngine' => SettingsController::getImageAiEngine(),
                'altTextAiLanguage' => $siteLanguageService->getAllAvailableLanguages(),
                'selectedAltTextAiLanguage' => $siteLanguageService->getLanguage(),
                'validateApiKeyOpenAi' => $openAi->validateApiKey(),
                'validateApiKeyStabilityAi' => $stabilityAi->validateApiKey(),
                'validateApiKeyStableDiffusionAi' => $stableDiffusion->validateApiKey(),
                'validateApiKeyAltTextAi' => $altTextAi->validateApiKey(),
                'validateApiKeySummAi' => $summAi->validateApiKey(),
                'summAiApiValue' => $summAi->getMaskedApiKey(),
                'summAiUserEmail' => $summAi->getUserEmail(),
            ]
        );

        try {
            $this->view->assignMultiple(
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
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR, false);
        }
        if (null === $this->moduleTemplateFactory) {
            $translatedMessage = LocalizationUtility::translate('labelErrorModuleTemplateFactory', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage, 1623345720);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    private function setLanguage(string $language, ClientInterface $client, SiteLanguageService $siteLanguageService): void
    {
        if ($language) {
            $siteLanguageService->setLanguage($language);
            $translatedMessage = LocalizationUtility::translate('labelSavedLanguage', 'mkcontentai') ?? '';

            $this->addFlashMessage($translatedMessage);
            try {
                $client->getTestApiCall();
            } catch (\Exception $e) {
                (403 === $e->getCode()) ?
                    $translatedMessage = LocalizationUtility::translate('labelErrorSavedLanguage', 'mkcontentai') ?? '' :
                    $translatedMessage = $e->getMessage();
                $this->addFlashMessage($translatedMessage, '', AbstractMessage::ERROR, false);
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
                $client->getTestApiCall();
            } catch (\Exception $e) {
                $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR, false);
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
