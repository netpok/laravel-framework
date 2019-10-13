<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\TestCase;

class SupportLazyCollectionTest extends TestCase
{
    public function testCanCreateEmptyCollection()
    {
        $this->assertSame([], LazyCollection::make()->all());
        $this->assertSame([], LazyCollection::empty()->all());
    }

    public function testCanCreateCollectionFromArray()
    {
        $array = [1, 2, 3];

        $data = LazyCollection::make($array);

        $this->assertSame($array, $data->all());

        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $data = LazyCollection::make($array);

        $this->assertSame($array, $data->all());
    }

    public function testCanCreateCollectionFromArrayable()
    {
        $array = [1, 2, 3];

        $data = LazyCollection::make(Collection::make($array));

        $this->assertSame($array, $data->all());

        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $data = LazyCollection::make(Collection::make($array));

        $this->assertSame($array, $data->all());
    }

    public function testCanCreateCollectionFromClosure()
    {
        $data = LazyCollection::make(function () {
            yield 1;
            yield 2;
            yield 3;
        });

        $this->assertSame([1, 2, 3], $data->all());

        $data = LazyCollection::make(function () {
            yield 'a' => 1;
            yield 'b' => 2;
            yield 'c' => 3;
        });

        $this->assertSame([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ], $data->all());
    }

    public function testEager()
    {
        $source = [1, 2, 3, 4, 5];

        $data = LazyCollection::make(function () use (&$source) {
            yield from $source;
        })->eager();

        $source[] = 6;

        $this->assertSame([1, 2, 3, 4, 5], $data->all());
    }

    public function testRemember()
    {
        $source = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $calls = 0;

        $data = LazyCollection::make(function () use (&$source, &$calls) {
            foreach ($source as $item) {
                $calls++;
                yield $item;
            }
        })->remember();

        $this->assertSame([1, 2, 3], $data->take(3)->all());
        $this->assertEquals(3, $calls);

        $this->assertSame([1, 3, 5], $data->filter(function ($item) {
            return $item % 2;
        })->values()->take(3)->all());
        $this->assertEquals(5, $calls);

        $this->assertSame([
            0 => 1,
            2 => 3,
            4 => 5
        ], $data->filter(function ($item) {
            return $item % 2;
        })->take(3)->all());
        $this->assertEquals(5, $calls);

        $this->assertSame($source, $data->all());
        $this->assertEquals(count($source), $calls);
    }

    public function testRememberWithKeys()
    {
        $source = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
        ];
        $calls = 0;

        $data = LazyCollection::make(function () use ($source, &$calls) {
            foreach ($source as $key => $item) {
                $calls++;
                yield $key=>$item;
            }
        })->remember();

        $this->assertSame([
            'a' => 1,
            'b' => 2,
        ], $data->take(2)->all());
        $this->assertEquals(2, $calls);

        $this->assertSame([
            'a' => 1,
            'c' => 3,
        ], $data->filter(function ($item, $key) {
            return $key != 'b';
        })->take(2)->all());
        $this->assertEquals(3, $calls);

        $this->assertSame([
            'a' => 1,
            'c' => 3,
        ], $data->filter(function ($item) {
            return $item % 2;
        })->all());
        $this->assertEquals(4, $calls);

        $this->assertSame($source, $data->all());
        $this->assertEquals(count($source), $calls);
    }

    public function testTapEach()
    {
        $data = LazyCollection::times(10);

        $tapped = [];

        $data = $data->tapEach(function ($value, $key) use (&$tapped) {
            $tapped[$key] = $value;
        });

        $this->assertEmpty($tapped);

        $data = $data->take(5)->all();

        $this->assertSame([1, 2, 3, 4, 5], $data);
        $this->assertSame([1, 2, 3, 4, 5], $tapped);
    }
}
