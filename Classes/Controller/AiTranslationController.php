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

namespace DMK\MkContentAi\Controller;

use DMK\MkContentAi\Backend\Hooks\PageContentHandler;
use DMK\MkContentAi\Service\AiTranslationPageContentService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class AiTranslationController extends BaseController
{
    private AiTranslationPageContentService $aiTranslationService;
    private PageContentHandler $pageContentHandler;

    public function __construct(AiTranslationPageContentService $aiTranslationService, PageContentHandler $pageContentHandler, PageRenderer $pageRenderer)
    {
        $this->aiTranslationService = $aiTranslationService;
        $this->pageContentHandler = $pageContentHandler;
        $pageRenderer->addCssFile('EXT:mkcontentai/Resources/Public/Css/base.css');
    }

    public function translateContentEasyAction(int $uid = 0, string $inputTextType = 'plain_text', string $targetLanguageType = 'easy', string $separator = 'hyphen'): ResponseInterface
    {
        return $this->translateContent($uid, $inputTextType, $targetLanguageType, $separator);
    }

    public function translateContentPlainAction(int $uid = 0, string $inputTextType = 'plain_text', string $targetLanguageType = 'plain', string $separator = 'hyphen'): ResponseInterface
    {
        return $this->translateContent($uid, $inputTextType, $targetLanguageType, $separator);
    }

    private function translateContent(int $uid, string $inputTextType, string $targetLanguageType, string $separator): ResponseInterface
    {
        $record = $this->aiTranslationService->getRecordToTranslate($uid);

        if (null === $record) {
            $response = new ForwardResponse('filelist');
            $translatedMessage = LocalizationUtility::translate('labelErrorRecordSelected', 'mkcontentai') ?? '';

            $this->addFlashMessage($translatedMessage, '', AbstractMessage::ERROR);

            return $response->withControllerName('AiImage');
        }

        $bodyTextToTranslate = $record->getBodytext();

        try {
            $translatedText = $this->aiTranslationService->getTranslation($bodyTextToTranslate, $this->aiTranslationService->getSummAiUserEmail(), $inputTextType, $targetLanguageType, $separator);
            $this->pageContentHandler->copyContentRecord($record->getUid(), $record->getPid(), $translatedText->translated_text, $targetLanguageType);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);

            return $this->handleResponse();
        }

        return $this->buildUrl($record->getPid());
    }

    private function buildUrl(int $recordPid): ResponseInterface
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $recordUrl = $uriBuilder->buildUriFromRoute('web_layout', [
            'id' => $recordPid,
        ]);

        $redirectResponse = GeneralUtility::makeInstance(RedirectResponse::class, $recordUrl);

        return $redirectResponse;
    }

    protected function handleResponse(): ResponseInterface
    {
        if (null === $this->moduleTemplateFactory) {
            $translatedMessage = LocalizationUtility::translate('labelErrorModuleTemplateFactory', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage, 1623345720);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }
}
