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

use DMK\MkContentAi\Domain\Repository\AltTextLogRepository;
use DMK\MkContentAi\DTO\AltTextLogDTO;
use DMK\MkContentAi\Service\FileService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class AiTextLogsController extends BaseController
{
    private FileService $fileService;
    private AltTextLogRepository $altTextLogRepository;

    public function __construct(FileService $fileService, AltTextLogRepository $altTextLogsRepository)
    {
        $this->fileService = $fileService;
        $this->altTextLogRepository = $altTextLogsRepository;
    }

    public function showAction(int $page = 1, int $limit = 20): ResponseInterface
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:mkcontentai/Resources/Public/Css/base.css');

        $altTextLogs = $this->altTextLogRepository->getAltTextLogs($page, $limit);
        $altTextLogsDto = [];

        foreach ($altTextLogs as $altTextLog) {
            $file = $this->fileService->getFileById((string) $altTextLog->getFileUid());

            if (null === $file) {
                continue;
            }

            $originalResource = $file->getOriginalResource();
            $altTextLogsDto[] = AltTextLogDTO::fromAltTextLogs($altTextLog, $originalResource->getPublicUrl() ?? '', $originalResource->getName(), $file->getOriginalResource()->getMetaData()->get()['uid']);
        }

        $hasNextPage = $this->altTextLogRepository->hasNextPage($page + 1, $limit) && 0 !== count($altTextLogsDto);
        $this->view->assignMultiple(
            [
                'currentPage' => $page,
                'nextPage' => $hasNextPage ? $page + 1 : false,
                'previousPage' => $page > 1 ? $page - 1 : false,
                'altTextLogs' => $altTextLogsDto,
            ]
        );

        return $this->handleResponse();
    }

    public function redirectToEditAction(int $metaDataUid): ResponseInterface
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $editUrl = $uriBuilder->buildUriFromRoute('record_edit', [
            'edit[sys_file_metadata]['.$metaDataUid.']' => 'edit',
        ], GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));

        return $this->redirectToUri($editUrl);
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
