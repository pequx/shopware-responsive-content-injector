{block name='frontend_blog_detail_description_responsive_injection'}
    <div class="responsive-content">
        <div class="container layout-{$layout}">
            {if $type eq 0 or $type eq 1}
                {if $items|@count eq 1}
                    <div class="grid-12">
                        <img class="scale" src="{if $type eq 1}{$items[0]}{else}{media path=$items[0]}{/if}">
                        {if $debug eq true}
                            <pre><code>index {$smarty.section.item.index} | item {$items[0]}</code></pre>
                        {/if}
                    </div>
                {elseif $items|@count eq 2}
                    {section name=item loop=$items}
                        <div class="grid-6">
                            <img class="scale" src="{if $type eq 1}{$items[item]}{else}{media path=$items[item]}{/if}">
                            {if $debug eq true}
                                <pre><code>index {$smarty.section.item.index} | path {$items[item]}</code></pre>
                            {/if}
                        </div>
                    {/section}
                {else}
                    {section name=item loop=$items}
                        <div class="{if $smarty.section.item.index % 3 eq 0}grid-12{else}grid-6{/if}">
                            <img class="scale" src="{if $type eq 1}{$items[item]}{else}{media path=$items[item]}{/if}">
                            {if $debug eq true}
                                <pre><code>index {$smarty.section.item.index} | path {$items[item]}</code></pre>
                            {/if}
                        </div>
                    {/section}
                {/if}
            {elseif $type eq 2}
                {if $items|@count eq 1}
                    <div class="grid-12">
                        {include file="frontend/listing/box_article.tpl" productBoxLayout="emotion" sArticle=$items[0]}
                    </div>
                {elseif $items|@count eq 2}
                    {section name=item loop=$items}
                        <div class="grid-6">
                            {include file="frontend/listing/box_article.tpl" productBoxLayout="emotion" sArticle=$items[item]}
                        </div>
                    {/section}
                {else}
                    {section name=item loop=$items}
                        <div class="{if $items|@count eq 3}grid-4{elseif $items|@count eq 4}grid-3{else}
                            {if $smarty.section.item.index % 3 eq 0}grid-12{else}grid-6{/if}
                        {/if}">
                            {include file="frontend/listing/box_article.tpl" productBoxLayout="emotion" sArticle=$items[item]}
                        </div>
                    {/section}
                {/if}
            {/if}
        </div>
    </div>
{/block}

