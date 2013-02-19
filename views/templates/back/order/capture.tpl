<br />
<fieldset id="skrillpspcapture">
	<legend><img src="{$base_url}modules/{$module}/logo.gif" alt="" />{l s='SkrillPSP Transaction Capture' mod='skrillpsp'}</legend>
	<form method="post" action="{$smarty.server.REQUEST_URI|escape:htmlall}">
		<input type="hidden" name="id_order" value="{$params.id_order}" />
		<div class="left">By pressing the button "{l s='Capture' mod='skrillpsp'}" you will debit the preauthorized amount from the customer's credit/debit card.</div>
		<div class="left"><input type="submit" class="button" name="btnSkrillPSPCapture" value="{l s='Capture' mod='skrillpsp'}" /></div>
	</form>
</fieldset>
