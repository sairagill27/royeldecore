<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ViewSanityTest extends TestCase
{
    // A code formatter can split {!! ... !!} across lines, which breaks the page.
    // The broken form leaves a line that starts with "!!". This test finds it.
    public function test_no_view_has_a_split_blade_echo(): void
    {
        foreach (File::allFiles(resource_path('views')) as $file) {
            $this->assertSame(
                0,
                preg_match('/^\s*!!/m', $file->getContents()),
                $file->getRelativePathname() . ' has a split Blade echo. Fix the line and turn off format-on-save.'
            );
        }
    }
}
