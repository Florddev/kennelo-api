<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Concerns;

use Illuminate\Contracts\View\View;

trait HasSkeleton
{
    protected static bool $isLazy = true;

    public function placeholder(): View
    {
        return view('filament.skeletons.wrapper', [
            'span' => $this->skeletonSpan(),
            'heading' => $this->skeletonHeading(),
            'description' => $this->skeletonDescription(),
            'view' => $this->skeletonView(),
            'data' => $this->skeletonData(),
        ]);
    }

    protected function skeletonView(): string
    {
        return 'filament.skeletons.section';
    }

    /**
     * @return array<string, mixed>
     */
    protected function skeletonData(): array
    {
        return [];
    }

    protected function skeletonHeading(): ?string
    {
        return null;
    }

    protected function skeletonDescription(): ?string
    {
        return null;
    }

    protected function skeletonSpan(): ?string
    {
        $span = $this->getColumnSpan();

        if ($span === 'full') {
            return '1 / -1';
        }

        if (is_int($span)) {
            return "span {$span} / span {$span}";
        }

        return null;
    }
}
