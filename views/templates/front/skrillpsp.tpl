{foreach from=$payments_config item=pconfig}
<p class="payment_module">
    <a href="#" id="skrillpsppayment{$pconfig.pmethod}" class="skrillpsppayments">
        <img src="{$imgroot}/{$pconfig.logo}" alt="Pay by Skrill {$pconfig.plabel}" /><span>{$pconfig.plabel}</span>
    </a>
    <div class="skrillpspiframe" id="skrillpspiframe{$pconfig.pmethod}">
    </div>
</p>
<script>
    jQuery('#skrillpsppayment{$pconfig.pmethod}').click(function (e) {
        e.preventDefault();
        
        jQuery.each(jQuery('.skrillpspiframe'), function (index, value) {
            jQuery(this).hide();
        });
        
        {if $pconfig.redirect_url}
            {if $pconfig.pmethod == 'CC'}
                jQuery('#skrillpspiframe{$pconfig.pmethod}').html('<iframe id="skrillpsppaymentiframe{$pconfig.pmethod}" src="{$pconfig.redirect_url}" frameborder="0" scrolling="no" ' +
                                                                'style="width:350px; border: none; height: 270px;"></iframe>');
            {else}
        	window.top.location = '{$pconfig.redirect_url}';
            {/if}
        {else}
        jQuery('#skrillpspiframe{$pconfig.pmethod}').html('<iframe id="skrillpsppaymentiframe{$pconfig.pmethod}" src="https://www.moneybookers.com/app/payment.pl?sid={$pconfig.sid}"' +
                                                          ' allowtransparency="true" frameborder="0" scrolling="no" style="width:100%; height:500px; border:none;"></iframe>');
        {/if}
        jQuery('#skrillpspiframe{$pconfig.pmethod}').show();
    });
</script>
{/foreach}