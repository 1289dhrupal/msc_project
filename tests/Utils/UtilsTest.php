<?php

use PHPUnit\Framework\TestCase;
use MscProject\Utils;

class UtilsTest extends TestCase
{

    public function testGetFileExtension()
    {
        $filename = 'document.pdf';
        $extension = Utils::getFileExtension($filename);

        $this->assertEquals('pdf', $extension, "Expected getFileExtension to return 'pdf'");
    }

    public function testGetFileExtensionWithoutDot()
    {
        $filename = 'documentpdf';
        $extension = Utils::getFileExtension($filename);

        $this->assertEquals('', $extension, "Expected getFileExtension to return an empty string for filenames without an extension");
    }

    public function testGetFileExtensionWithMultipleDots()
    {
        $filename = 'archive.tar.gz';
        $extension = Utils::getFileExtension($filename);

        $this->assertEquals('gz', $extension, "Expected getFileExtension to return 'gz' for filenames with multiple dots");
    }
}
