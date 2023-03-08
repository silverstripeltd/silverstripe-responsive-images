<picture>
    <% loop $Sizes %>
        <source media="$Query" srcset="$Image.URL">
    <% end_loop %>

    <img src="$DefaultImage.URL"<% if $ExtraClasses %> class="$ExtraClasses"<% end_if %> alt="$Title">
</picture>
