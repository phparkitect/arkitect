<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Analyzer\Events\ClassAnalyzed;
use Arkitect\ClassSet;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ClassSetTest extends TestCase
{
    public function test_can_be_built_from_files(): void
    {
        $set = ClassSet::fromDir(__DIR__.'/../e2e/fixtures/happy_island');
        $fakeSubscriber = new FakeSubscriber();

        $set->addSubscriber($fakeSubscriber);
        $set->run();

        self::assertEquals([
            ClassDescriptionBuilder::create('App\BadCode\BadCode')->setFilePath('BadCode')->get(),
            ClassDescriptionBuilder::create('App\HappyIsland\HappyClass')->setFilePath('HappyIsland')->get(),
            ClassDescriptionBuilder::create('App\BadCode\OtherBadCode')->setFilePath('OtherBadCode')->get(),
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
            ClassAnalyzed::class => 'onClassAnalyzed',
        ];
    }

    public function onClassAnalyzed(ClassAnalyzed $classAnalyzed): void
    {
        $this->allClassAnalyzed[] = $classAnalyzed->getClassDescription();
    }

    public function getAllClassAnalyzed()
    {
        return $this->allClassAnalyzed;
    }
}
