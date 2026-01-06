<?php

namespace wcf\system\wsdb\database\preset;

use Override;

final class LinkPreset extends AbstractPreset
{
    #[Override]
    public function getIdentifier(): string
    {
        return 'dev.hanashi.links';
    }

    #[Override]
    public function getEnableReviews(): bool
    {
        return true;
    }

    #[Override]
    public function getMicroDataItemType(): string
    {
        return 'Article';
    }

    #[Override]
    public function getEnableCategories(): bool
    {
        return true;
    }

    #[Override]
    public function getEnableComments(): bool
    {
        return true;
    }

    #[Override]
    public function getEnableReactions(): bool
    {
        return true;
    }

    #[Override]
    public function getEnableLabel(): bool
    {
        return true;
    }

    #[Override]
    public function getEnableUgc(): bool
    {
        return true;
    }

    #[Override]
    public function getEnableTagging(): bool
    {
        return true;
    }

    #[Override]
    public function getListingTemplateName(): string
    {
        return 'wsdbRecordListCardItems';
    }

    #[Override]
    public function getShowTeaserInListing(): bool
    {
        return true;
    }

    #[Override]
    public function getEnableCoverPhoto(): bool
    {
        return true;
    }
}
