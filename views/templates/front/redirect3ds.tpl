<form id="skrillpsp_3dsredirect" name="skrillpsp_3dsredirect" action="{$redirecturl}" method="POST">
    {foreach $redirectparams as $rkey => $rparam}
    <input type="hidden" name="{$rkey|escape}" value="{$rparam|escape}">
    {/foreach}
</form>

<script type="text/javascript">
    if (parent.location.protocol === 'https:') {
        var frame = parent.document.getElementById('skrillpsppaymentiframeCC');
        frame.setAttribute('height', '400px');
        frame.setAttribute('style', '');
        frame.setAttribute('width', '700px');
        
        var link = parent.document.getElementById('skrillpsppaymentCC');
        link.setAttribute('onClick', 'javascript: void();');
    }
    
    var skrillpsp_3dsredirect = document.getElementById('skrillpsp_3dsredirect');
    window.onload = skrillpsp_3dsredirect.submit();
</script>
