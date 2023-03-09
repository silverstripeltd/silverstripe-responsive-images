srcset="<% loop $Sources %>
    {$Me.Link} {$Me.Width}w<% if not $IsLast %>,<% end_if %>
<% end_loop %>"
<% if $SizesAsString %>
    sizes="{$SizesAsString}"
<% end_if %>
<% if $MediaAsString %>
    media="{$MediaAsString}"
<% end_if %>
