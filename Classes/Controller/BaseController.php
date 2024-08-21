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

use DMK\MkContentAi\Http\Client\ImageApiInterface;
use DMK\MkContentAi\Http\Client\OpenAiClient;
use DMK\MkContentAi\Http\Client\StabilityAiClient;
use DMK\MkContentAi\Http\Client\StableDiffusionClient;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class BaseController extends ActionController
{
    public ImageApiInterface $client;

    public const GENERATOR_ENGINE_KEY = 'image_generator_engine';

    /**
     * @var array<class-string<object>>
     */
    public const GENERATOR_ENGINE = [
        1 => OpenAiClient::class,
        2 => StableDiffusionClient::class,
        3 => StabilityAiClient::class,
    ];

    protected ?PageRenderer $pageRenderer;

    protected ?ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(PageRenderer $pageRenderer, ModuleTemplateFactory $moduleTemplateFactory)
    {
        $this->pageRenderer = $pageRenderer;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    protected function initializeAndAuthorizeAction(): void
    {
        $client = $this->initializeClient();

        if (!isset($this->pageRenderer)) {
            throw new \Exception('PageRenderer is not set');
        }
        $this->pageRenderer->loadJavaScriptModule('@t3docs/mkcontentai/cropper.js');
        $this->pageRenderer->loadJavaScriptModule('@t3docs/mkcontentai/MkContentAi.js');
        $this->pageRenderer->loadJavaScriptModule('@t3docs/mkcontentai/BackendPrompt.js');
        $this->pageRenderer->loadJavaScriptModule('@t3docs/mkcontentai/AltText.js');
        $this->pageRenderer->loadJavaScriptModule('@t3docs/mkcontentai/context-menu-actions.js');
        $this->pageRenderer->loadJavaScriptModule('@t3docs/mkcontentai/ContextMenu.js');
        $this->pageRenderer->addCssFile('EXT:mkcontentai/Resources/Public/Css/base.css');
        if (isset($client['client'])) {
            $this->client = $client['client'];
        }

        $arguments['actionName'] = $this->request->getControllerActionName();
        if (!in_array($arguments['actionName'], $this->client->getAllowedOperations())) {
            $translatedMessage = LocalizationUtility::translate('labelNotAllowed', 'mkcontentai', $arguments) ?? '';
            $this->addFlashMessage($translatedMessage.' '.get_class($this->client), '', ContextualFeedbackSeverity::ERROR, false);
            $this->redirect('filelist');
        }

        $infoMessage2 = LocalizationUtility::translate('labelEuLaw', 'mkcontentai', ['https://artificialintelligenceact.eu/']) ?? '';
        $formattedMessage = str_replace('&#10;', "\n", $infoMessage2);

        $infoMessage = LocalizationUtility::translate('labelEngineInitialized', 'mkcontentai') ?? '';
        if (isset($client['clientClass'])) {
            $infoMessage .= ' '.$client['clientClass'].'. '.$formattedMessage;
        }
        $this->addFlashMessage(
            $infoMessage,
            '',
            ContextualFeedbackSeverity::INFO,
            false
        );
    }

    protected function createRequestModuleTemplate(): ModuleTemplate
    {
        if (!isset($this->moduleTemplateFactory)) {
            $translatedMessage = LocalizationUtility::translate('labelErrorModuleTemplateFactory', 'mkcontentai') ?? '';
            throw new \Exception($translatedMessage, 1623345720);
        }

        return $this->moduleTemplateFactory->create($this->request);
    }

    /**
     * @return array{client?:ImageApiInterface, clientClass?:string, error?:string}
     */
    protected function initializeClient(): array
    {
        try {
            $imageEngineKey = SettingsController::getImageAiEngine();
            $client = GeneralUtility::makeInstance($this::GENERATOR_ENGINE[$imageEngineKey]);
            if (is_a($client, ImageApiInterface::class)) {
                return [
                    'client' => $client,
                    'clientClass' => get_class($client),
                ];
            }
            $errorTranslated = LocalizationUtility::translate('labelError', 'mkcontentai') ?? '';

            return [
                'error' => $errorTranslated,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    protected static function getRegistry(): Registry
    {
        return GeneralUtility::makeInstance(Registry::class);
    }
}
