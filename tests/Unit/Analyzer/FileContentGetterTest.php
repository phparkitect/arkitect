<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\FileContentGetter;
use Arkitect\Rules\NotParsedClasses;
use PHPUnit\Framework\TestCase;

class FileContentGetterTest extends TestCase
{
    public function test_it_should_get_content(): void
    {
        $fileContentGetter = new FileContentGetter();
        $fileContentGetter->open('Arkitect\Tests\E2E\Fixtures\MvcExample\Controller\BaseController');

        $expected = '<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E\Fixtures\MvcExample\Controller;

class BaseController
{
}
';
        $this->assertTrue($fileContentGetter->isContentAvailable());
        $this->assertEquals($expected, $fileContentGetter->getContent());
        $this->assertStringContainsString(
            'arkitect/tests/E2E/Fixtures/MvcExample/Controller/BaseController.php',
            $fileContentGetter->getFileName()
        );
    }

    public function test_it_should_return_false_if_content_not_found(): void
    {
        $fileContentGetter = new FileContentGetter();
        $fileContentGetter->open('Arkitect\Dir\NotExistingFile');

        $notParsedClasses = new NotParsedClasses();
        $notParsedClasses->add('Arkitect\Dir\NotExistingFile');

        $this->assertFalse($fileContentGetter->isContentAvailable());
        $this->assertNull($fileContentGetter->getError());
        $this->assertEquals($notParsedClasses, $fileContentGetter->getNotParsedClasses());
    }

    public function test_it_should_with_core_file(): void
    {
        $fileContentGetter = new FileContentGetter();
        $fileContentGetter->open('\Exception');

        $this->assertFalse($fileContentGetter->isContentAvailable());
        $this->assertNull($fileContentGetter->getError());
    }

    public function test_it_should_parse_interface(): void
    {
        $fileContentGetter = new FileContentGetter();
        $fileContentGetter->open('Arkitect\Analyzer\Parser');

        $this->assertTrue($fileContentGetter->isContentAvailable());
        $this->assertNull($fileContentGetter->getError());
    }
}
