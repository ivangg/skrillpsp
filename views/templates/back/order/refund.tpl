<br />
<fieldset id="skrillpsprefund">
	<legend><img src="{$base_url}modules/{$module}/logo.gif" alt="" />{l s='SkrillPSP Transaction Refund' mod='skrillpsp'}</legend>
	<form method="post" action="{$smarty.server.REQUEST_URI|escape:htmlall}">
		<input type="hidden" name="id_order" value="{$params.id_order}" />
		<div class="left">By pressing the button "{l s='Refund' mod='skrillpsp'}" you will refund the amount to the customer's credit/debit card or bank account.</div>
		<div class="left"><input type="submit" class="button" name="btnSkrillPSPRefund" value="{l s='Refund' mod='skrillpsp'}" /></div>
	</form>
</fieldset>
