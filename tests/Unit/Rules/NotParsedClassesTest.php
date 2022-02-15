<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Rules\NotParsedClasses;
use PHPUnit\Framework\TestCase;

class NotParsedClassesTest extends TestCase
{
    public function test_it_should_add_not_parsed_class(): void
    {
        $notParsedClasses = new NotParsedClasses();
        $notParsedClasses->add('Not/Parsed/Class');

        $this->assertCount(1, $notParsedClasses->toArray());
        $this->assertEquals(['Not/Parsed/Class' => 'Not/Parsed/Class'], $notParsedClasses->toArray());

        $this->assertEquals('
Not Parsed class: Not/Parsed/Class
', $notParsedClasses->toString());
    }
}
