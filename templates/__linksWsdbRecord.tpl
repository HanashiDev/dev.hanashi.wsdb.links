{if $view->database->enableLinks && $view->record->externalUrl}
	<li class="wsdbLinkListItem">
		<a
			href="{$view->record->externalUrl}"
			class="button buttonPrimary wsdbLink externalURL"
			rel="ugc nofollow{if EXTERNAL_LINK_TARGET_BLANK} noopener{/if}"
			{if EXTERNAL_LINK_TARGET_BLANK} target="_blank"{/if}
		>
			{$view->database->getPhrase($__wcf->getLanguage()->languageID, 'linkButton')}
		</a>
	</li>
{/if}
