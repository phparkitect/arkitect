<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Expression\Description;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use PHPUnit\Framework\TestCase;

class ViolationTest extends TestCase
{
    public function test_create_sets_fqcn_error_and_file_path_with_no_line(): void
    {
        $message = ViolationMessage::selfExplanatory(new Description('should be final', ''));
        $violation = Violation::create('App\Domain\Order', $message, 'src/Domain/Order.php');

        self::assertEquals('App\Domain\Order', $violation->getFqcn());
        self::assertEquals('should be final', $violation->getError());
        self::assertNull($violation->getLine());
        self::assertEquals('src/Domain/Order.php', $violation->getFilePath());
    }

    public function test_create_with_error_line_sets_all_fields(): void
    {
        $message = ViolationMessage::selfExplanatory(new Description('should be abstract', 'design reasons'));
        $violation = Violation::createWithErrorLine('App\Service\Foo', $message, 42, 'src/Service/Foo.php');

        self::assertEquals('App\Service\Foo', $violation->getFqcn());
        self::assertEquals('should be abstract because design reasons', $violation->getError());
        self::assertEquals(42, $violation->getLine());
        self::assertEquals('src/Service/Foo.php', $violation->getFilePath());
    }

    public function test_json_serialize_returns_all_properties(): void
    {
        $violation = new Violation('App\Foo', 'some error', 10, 'src/Foo.php');
        $data = $violation->jsonSerialize();

        self::assertArrayHasKey('fqcn', $data);
        self::assertArrayHasKey('error', $data);
        self::assertArrayHasKey('line', $data);
        self::assertArrayHasKey('filePath', $data);
        self::assertEquals('App\Foo', $data['fqcn']);
        self::assertEquals('some error', $data['error']);
        self::assertEquals(10, $data['line']);
        self::assertEquals('src/Foo.php', $data['filePath']);
    }

    public function test_from_json_round_trips_with_file_path(): void
    {
        $original = new Violation('App\Bar', 'must implement interface', 7, 'src/Bar.php');
        $restored = Violation::fromJson($original->jsonSerialize());

        self::assertEquals($original->getFqcn(), $restored->getFqcn());
        self::assertEquals($original->getError(), $restored->getError());
        self::assertEquals($original->getLine(), $restored->getLine());
        self::assertEquals($original->getFilePath(), $restored->getFilePath());
    }

    public function test_from_json_round_trips_without_file_path(): void
    {
        $data = [
            'fqcn' => 'App\Baz',
            'error' => 'should extend BaseClass',
            'line' => null,
        ];
        $violation = Violation::fromJson($data);

        self::assertEquals('App\Baz', $violation->getFqcn());
        self::assertEquals('should extend BaseClass', $violation->getError());
        self::assertNull($violation->getLine());
        self::assertNull($violation->getFilePath());
    }

    public function test_without_line_number_returns_copy_with_null_line(): void
    {
        $violation = new Violation('App\Qux', 'must be final', 99, 'src/Qux.php');
        $stripped = $violation->withoutLineNumber();

        self::assertNull($stripped->getLine());
        self::assertEquals('App\Qux', $stripped->getFqcn());
        self::assertEquals('must be final', $stripped->getError());
        self::assertEquals('src/Qux.php', $stripped->getFilePath());
    }

    public function test_without_line_number_does_not_mutate_original(): void
    {
        $violation = new Violation('App\Qux', 'must be final', 99, 'src/Qux.php');
        $violation->withoutLineNumber();

        self::assertEquals(99, $violation->getLine());
    }
}
