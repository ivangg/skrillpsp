<form id="skrillpsp_3dsredirect" name="skrillpsp_3dsredirect" action="{$redirecturl}" method="POST">
    {foreach $redirectparams as $rkey => $rparam}
    <input type="hidden" name="{$rkey}" value="{$rparam}">
    {/foreach}
</form>

<script type="text/javascript">
    var skrillpsp_3dsredirect = document.getElementById('skrillpsp_3dsredirect');
    window.onload = skrillpsp_3dsredirect.submit();
</script>
