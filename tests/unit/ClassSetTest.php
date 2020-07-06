<?php
declare(strict_types=1);


namespace unit;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Analyzer\Events\ClassAnalyzed;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\ClassSet;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ClassSetTest extends TestCase
{
    public function test_can_be_built_from_files()
    {
        $set = ClassSet::fromDir(__DIR__.'/../e2e/fixtures/happy_island');
        $fakeSubscriber = new FakeSubscriber();
        $set->addSubScriber($fakeSubscriber);
        $set->run();
        $this->assertEquals([
            new ClassDescription('HappyIsland', FullyQualifiedClassName::fromString('App\HappyIsland\HappyClass'), [], []),
            new ClassDescription('BadCode', FullyQualifiedClassName::fromString('App\BadCode\BadCode'), [], []),
            new ClassDescription('OtherBadCode', FullyQualifiedClassName::fromString('App\BadCode\OtherBadCode'), [], []),
        ], $fakeSubscriber->getAllClassAnalyzed());
    }

    public function test_can_be_built_from_array()
    {
        $set = ClassSet::fromArray([
            ClassDescription::build('Fruit\Banana', 'my/path')->get(),
            ClassDescription::build('Fruit\Apple', 'my/path')->get(),
        ]);
        $fakeSubscriber = new FakeSubscriber();
        $set->addSubScriber($fakeSubscriber);
        $set->run();
        $this->assertEquals([
            ClassDescription::build('Fruit\Banana', 'my/path')->get(),
            ClassDescription::build('Fruit\Apple', 'my/path')->get()
        ], $fakeSubscriber->getAllClassAnalyzed());
    }
}

class FakeSubscriber implements EventSubscriberInterface
{
    private $allClassAnalyzed;

    public function __construct()
    {
        $this->allClassAnalyzed = [];
    }

    public static function getSubscribedEvents()
    {
        return [
            ClassAnalyzed::class => 'onClassAnalyzed'
        ];
    }

    public function onClassAnalyzed(ClassAnalyzed $classAnalyzed): void
    {
        $this->allClassAnalyzed []= $classAnalyzed->getClassDescription();
    }

    public function getAllClassAnalyzed()
    {
        return $this->allClassAnalyzed;
    }
}
