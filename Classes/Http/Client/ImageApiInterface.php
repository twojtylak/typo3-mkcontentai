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
use TYPO3\CMS\Extbase\Domain\Model\File;

interface ImageApiInterface extends ClientInterface
{
    /**
     * @return array<Image>
     */
    public function image(string $text): array;

    /**
     * @return array<Image>
     */
    public function createImageVariation(File $file): array;

    public function upscale(File $file): Image;

    /**
     * @return array<Image>
     */
    public function extend(string $sourceImagePath, string $direction, ?string $promptText): array;

    public function getFolderName(): string;

    /**
     * @return array<string>
     */
    public function getAllowedOperations(): array;

    /**
     * @return array<int,array<string,string>>
     */
    public function getAvailableResolutions(string $actionName): array;

    public function imageToVideo(string $filePath): ?Image;

    public function getClientName(): string;
}
