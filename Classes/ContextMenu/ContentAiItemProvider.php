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

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
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
    ];

    public function canHandle(): bool
    {
        return 'sys_file' === $this->table;
    }

    public function getPriority(): int
    {
        return 55;
    }

    /**
     * @return array<string>
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $typo3Version = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);

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

    private function generateUrl(string $itemName): UriInterface
    {
        $typo3Version = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
        $majorVersion = $typo3Version->getMajorVersion();
        if (11 === $majorVersion) {
            $parameters = $this->getParametersForVersion11($itemName);
        } elseif (12 === $majorVersion) {
            $parameters = $this->getParametersForVersion12($itemName);
        }
        $pathInfo = $this->getPathInfo($itemName, $majorVersion);

        $this->updateParametersForItemName($parameters, $itemName, $majorVersion);

        /**
         * @var UriBuilder $uriBuilder
         */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $extendUrl = $uriBuilder->buildUriFromRoutePath(
            $pathInfo,
            $parameters
        );

        return $extendUrl;
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
        ];

        if (11 === $version) {
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['action'] = $actionMapping[$itemName] ?? '';
        }

        if (('fileAlt' === $itemName || 'folderAltTexts' === $itemName) && 11 === $version) {
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['controller'] = 'AiText';
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
        ];

        return $pathInfoMapping[$itemName][$version] ?? '';
    }

    /**
     * This method is called for each item this provider adds and checks if given item can be added.
     */
    protected function canRender(string $itemName, string $type): bool
    {
        if ('item' !== $type) {
            return false;
        }
        $canRender = false;
        switch ($itemName) {
            case 'fileUpscale':
            case 'fileExtend':
            case 'fileAlt':
                $canRender = $this->isImage();
                break;
            case 'folderAltTexts':
                $canRender = $this->isFolder();
                break;
        }

        return $canRender;
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
}
