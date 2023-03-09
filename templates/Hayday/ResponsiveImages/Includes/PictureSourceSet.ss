<picture>
    <% if $SourceIsIterable %>
        <% loop $Source %>
            <source $Me />
        <% end_loop %>
    <% else %>
        <source $Source />
    <% end_if %>
    <img src="{$DefaultImage.Link}" class="{$CssClasses}" />
</picture>
