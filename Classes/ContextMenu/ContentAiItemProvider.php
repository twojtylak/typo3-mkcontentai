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

namespace DMK\MkContentAi\ContextMenu;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use DMK\MkContentAi\Controller\AiImageController;
use DMK\MkContentAi\Controller\SettingsController;
use DMK\MkContentAi\Http\Client\OpenAiClient;
use DMK\MkContentAi\Http\Client\StabilityAiClient;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentAiItemProvider extends AbstractProvider
{
    /**
     * @var array<string, array{
     *     type: string,
     *     label: string,
     *     iconIdentifier: string,
     *     callbackAction: string
     * }>
     */
    protected $itemsConfiguration = [
        'fileUpscale' => [
            'type' => 'item',
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelContextMenuUpscale',
            'iconIdentifier' => 'actions-rocket',
            'callbackAction' => 'upscale',
        ],
        'fileExtend' => [
            'type' => 'item',
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelContextMenuExtend',
            'iconIdentifier' => 'actions-rocket',
            'callbackAction' => 'extend',
        ],
        'fileAlt' => [
            'type' => 'item',
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelContextMenuAlttext',
            'iconIdentifier' => 'actions-rocket',
            'callbackAction' => 'alt',
        ],
        'folderAltTexts' => [
            'type' => 'item',
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelContextMenuAlttext',
            'iconIdentifier' => 'actions-rocket',
            'callbackAction' => 'altTexts',
        ],
        'filePrepareImageToVideo' => [
            'type' => 'item',
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelContextMenuImageToVideo',
            'iconIdentifier' => 'actions-rocket',
            'callbackAction' => 'prepareImageToVideo',
        ],
    ];

    public function setContext(string $table, string $identifier, string $context = ''): void
    {
        $this->table = $table;
        $this->identifier = $identifier;
        $this->context = $context;
    }

    public function canHandle(): bool
    {
        return 'sys_file' === $this->table;
    }

    public function getPriority(): int
    {
        return 55;
    }

    public function generateUrl(string $itemName): UriInterface
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        $majorVersion = $typo3Version->getMajorVersion();
        $parameters = (11 === $majorVersion) ? $this->getParametersForVersion11($itemName) : $this->getParametersForVersion12($itemName);
        $pathInfo = $this->getPathInfo($itemName, $majorVersion);

        $this->updateParametersForItemName($parameters, $itemName, $majorVersion);

        /**
         * @var UriBuilder $uriBuilder
         */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $extendedUrl = $uriBuilder->buildUriFromRoutePath(
            $pathInfo,
            $parameters
        );

        return $extendedUrl;
    }

    /**
     * This method is called for each item this provider adds and checks if given item can be added.
     */
    public function canRender(string $itemName, string $type): bool
    {
        if ('item' !== $type) {
            return false;
        }
        $imageAiEngine = SettingsController::getImageAiEngine();
        $canRender = false;

        if (
            (('fileUpscale' === $itemName || 'fileExtend' === $itemName || 'filePrepareImageToVideo' === $itemName) && true === $this->checkAllowedOperationsByClient($itemName, $imageAiEngine))
            || 'fileAlt' === $itemName
        ) {
            return $this->isImage();
        }

        if ('folderAltTexts' === $itemName) {
            return $this->isFolder();
        }

        return $canRender;
    }

    /**
     * @return array<string, array{
     *     type: string,
     *     label: string,
     *     iconIdentifier: string,
     *     callbackAction: string
     * }>
     */
    public function getItemsConfiguration(): array
    {
        return $this->itemsConfiguration;
    }

    /**
     * @return array<string>
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);

        $extendUrl = $this->generateUrl($itemName);

        switch ($typo3Version->getMajorVersion()) {
            case 12:
                return [
                    'data-callback-module' => '@t3docs/mkcontentai/context-menu-actions',
                    'data-navigate-uri' => $extendUrl->__toString(),
                ];
            case 11:
                return [
                    'data-callback-module' => 'TYPO3/CMS/Mkcontentai/ContextMenu',
                    'data-navigate-uri' => $extendUrl->__toString(),
                ];
            default:
                throw new \RuntimeException('TYPO3 version not supported');
        }
    }

    /**
     * Helper method implementing e.g. access check for certain item.
     */
    protected function isImage(): bool
    {
        return 'sys_file' === $this->table && preg_match('/\.(png|jpg)$/', $this->identifier);
    }

    /**
     * Helper method checking if resource is a folder and exist in the storage.
     */
    protected function isFolder(): bool
    {
        $resourceStorage = GeneralUtility::makeInstance(ResourceFactory::class);
        $object = $resourceStorage->retrieveFileOrFolderObject($this->identifier);

        return $object instanceof Folder;
    }

    /**
     * @param array<string, mixed> &$parameters
     */
    private function updateParametersForItemName(array &$parameters, string $itemName, int $version): void
    {
        $actionMapping = [
            'fileUpscale' => 'upscale',
            'fileExtend' => 'cropAndExtend',
            'fileAlt' => 'altText',
            'folderAltTexts' => 'altTexts',
            'filePrepareImageToVideo' => 'prepareImageToVideo',
        ];

        if (11 === $version) {
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['action'] = $actionMapping[$itemName] ?? '';
        }

        if (('fileAlt' === $itemName || 'folderAltTexts' === $itemName) && 11 === $version) {
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['controller'] = 'AiText';
        }

        if (('filePrepareImageToVideo' === $itemName) && 11 === $version) {
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['controller'] = 'AiVideo';
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getParametersForVersion11(string $itemName): array
    {
        if ('folderAltTexts' === $itemName) {
            return [
                'tx_mkcontentai_system_mkcontentaicontentai' => [
                    'controller' => 'AiImage',
                    'folderName' => $this->identifier,
                ],
            ];
        }

        return [
            'tx_mkcontentai_system_mkcontentaicontentai' => [
                'controller' => 'AiImage',
                'file' => $this->identifier,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getParametersForVersion12(string $itemName): array
    {
        if ('folderAltTexts' === $itemName) {
            return ['folderName' => $this->identifier];
        }

        return ['file' => $this->identifier];
    }

    private function getPathInfo(string $itemName, int $version): string
    {
        $pathInfoMapping = [
            'fileUpscale' => [
                12 => '/module/mkcontentai/AiImage/upscale',
                11 => '/module/system/MkcontentaiContentai',
            ],
            'fileExtend' => [
                12 => '/module/mkcontentai/AiImage/cropAndExtend',
                11 => '/module/system/MkcontentaiContentai',
            ],
            'fileAlt' => [
                12 => '/module/mkcontentai/AiText/altText',
                11 => '/module/system/MkcontentaiContentai',
            ],
            'folderAltTexts' => [
                12 => '/module/mkcontentai/AiText/altTexts',
                11 => '/module/system/MkcontentaiContentai',
            ],
            'filePrepareImageToVideo' => [
                12 => '/module/mkcontentai/AiVideo/prepareImageToVideo',
                11 => '/module/system/MkcontentaiContentai',
            ],
        ];

        return $pathInfoMapping[$itemName][$version] ?? '';
    }

    /**
     *  Helper method checking if current AI Client has permissions for a given operation.
     */
    private function checkAllowedOperationsByClient(string $itemName, int $imageAiEngine): bool
    {
        $stabilityAiClient = GeneralUtility::makeInstance(StabilityAiClient::class);
        $openAiClient = GeneralUtility::makeInstance(OpenAiClient::class);

        foreach ([$stabilityAiClient, $openAiClient] as $aiClient) {
            if (get_class($aiClient) === AiImageController::GENERATOR_ENGINE[$imageAiEngine] && in_array(lcfirst(str_replace('file', '', $itemName)), $aiClient->getAllowedOperations())) {
                return true;
            }
        }

        return false;
    }
}
