<?php

declare(strict_types=1);

use ItkDev\F2ApiClient\Model\Matter;
use PHPUnit\Framework\TestCase;

final class MatterTest extends TestCase
{
    public function testMatterApiSerialize(): void
    {
        $matter = new Matter();
        $expected = [
            'Title' => null,
            'Type' => null,
            'AccessLevel' => null,
            'Status' => null,
            'Archived' => 'false',
        ];
        $actual = $matter->apiSerialize();
        $this->assertSame($expected, $actual);

        $matter = new Matter();
        $matter->title = 'My matter';
        $expected = [
            'Title' => 'My matter',
            'Type' => null,
            'AccessLevel' => null,
            'Status' => null,
            'Archived' => 'false',
        ];
        $actual = $matter->apiSerialize();
        $this->assertSame($expected, $actual);
    }
}
