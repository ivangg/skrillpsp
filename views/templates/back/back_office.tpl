<div id="skrillmain">
    <div id="skrilllogo"></div>
    <div id="skrillabout">
        Skrill is one of the world's largest online payment providers currently used by 30 million customers and 135,000 merchants.
        Our payment network provides 100 payment options, with 40 currencies covering 200 countries and territories.
        With a single integration you can instantly enter new markets and grow your business.
    </div>
    <div id="skrillbenefits"></div>
    {if $isConfigFail}
    <div id="skrillerrors">
        {foreach from=$errorMsgs item=error}
            <div class="skrillerrormessage">{$error}</div>
        {/foreach}
    </div>
    {/if}
    
    <div id="skrillconfig">
        <form id="skrillconfigform" name="" action="{$smarty.server.REQUEST_URI|escape:'htmlall'}" method="POST">
            <div id="tabs-nobg">
                <ul>
                    <li><a href="#skrillconfigchannel"><span>Channel configuration</span></a></li>
                    <li><a href="#skrillcurrencychannels"><span>Configure additional currencies</span></a></li>
                    <li><a href="#skrillpaymentmethods"><span>Payment options</span></a></li>
                </ul>
                <div id="skrillconfigchannel">
                    <div class="skrillformfield">
                        <label for="channel">Channel :</label>
                        <input type='text' name="channel" id="channel" value="{$channel}" autocomplete="off" />
                    </div>
                    <div class="skrillformfield">
                        <label for="sender">Sender :</label>
                        <input type='text' name="sender" id="sender" value="{$sender}" autocomplete="off" />
                    </div>
                    <div class="skrillformfield">
                        <label for="login">Login :</label>
                        <input type='text' name="login" id="login" value="{$login}" autocomplete="off" />
                    </div>
                    <div class="skrillformfield">
                        <label for="password">Password :</label>
                        <input type='text' name="password" id="password" value="{$password}" autocomplete="off" />
                    </div>
                    <div class="skrillformfield">
                        <label class="transactionmodewrapper" for="transactionmode">Transaction mode :
                            <select name="transactionmode" id="transactionmode">
                                {foreach from=$tmodes item=tmode}
                                    <option value="{$tmode.value}" {if $tmode.value == $transactionmode}selected="selected"{/if}>{$tmode.label}</option>
                                {/foreach}
                            </select>
                        </label>
                    </div>
                    <div class="skrillformfield">
                        <label class="skrillquestion">Do you want this configuration to operate in LIVE mode?</label>
                        <span class="skrillyesno">
                            <input type="radio" class="testmode" name="testmode" id="testmode_no" value="0" {if $testmode == 0}checked{/if} /><label class="skrillradio" for="testmode_no">No</label>
                            <input type="radio" class="testmode" name="testmode" id="testmode_yes" value="1" {if $testmode == 1}checked{/if} /><label class="skrillradio" for="testmode_yes">Yes</label>
                        </span>
                    </div>
                </div>
            
                <div id="skrillcurrencychannels">
                    {foreach from=$Currencies item=currency}
                        <div class="skrillchannelconfig" id="skrillchannelconfigdiv_{$currency.iso_code}">
                            <div class="skrillformfield">
                                <label class="skrillquestion">Channel configuration for currency <strong>{$currency.iso_code}</strong></label>
                                <div class="skrillhideshow">
                                    <a href="#" class="skrilldelchannel" id="skrilldelchannel_{$currency.iso_code}">Hide Channel/Currency Configuration</a>
                                </div>
                            </div>
                            <span id="skrillchannelconfig_{$currency.iso_code}">
                                <div class="skrillformfield">
                                    <label for="channel_{$currency.iso_code}">Channel :</label>
                                    <input type='text' name="channel_{$currency.iso_code}" id="channel_{$currency.iso_code}" value="{$channels[$currency.iso_code].channel}" autocomplete="off" />
                                </div>
                                <div class="skrillformfield">
                                    <label for="sender_{$currency.iso_code}">Sender :</label>
                                    <input type='text' name="sender_{$currency.iso_code}" id="sender_{$currency.iso_code}" value="{$channels[$currency.iso_code].sender}" autocomplete="off" />
                                </div>
                                <div class="skrillformfield">
                                    <label for="login_{$currency.iso_code}">Login :</label>
                                    <input type='text' name="login_{$currency.iso_code}" id="login_{$currency.iso_code}" value="{$channels[$currency.iso_code].login}" autocomplete="off" />
                                </div>
                                <div class="skrillformfield">
                                    <label for="password_{$currency.iso_code}">Password :</label>
                                    <input type='text' name="password_{$currency.iso_code}" id="password_{$currency.iso_code}" value="{$channels[$currency.iso_code].password}" autocomplete="off" />
                                </div>
                                <div class="skrillformfield">
                                    <label class="skrillquestion">Do you want this configuration to operate in LIVE mode?</label>
                                    <span class="skrillyesno">
                                        <input type="radio" class="testmode" name="testmode_{$currency.iso_code}" id="testmode_{$currency.iso_code}_no" value="0" {if $channels[$currency.iso_code].testmode == 0}checked{/if} /><label class="skrillradio" for="testmode_{$currency.iso_code}_no">No</label>
                                        <input type="radio" class="testmode" name="testmode_{$currency.iso_code}" id="testmode_{$currency.iso_code}_yes" value="1" {if $channels[$currency.iso_code].testmode == 1}checked{/if} /><label class="skrillradio" for="testmode_{$currency.iso_code}_yes">Yes</label>
                                    </span>
                                </div>
                            </span>
                        </div>
                    {/foreach}
                    <br />
                </div>
                
                <div id="skrillpaymentmethods">
                    {foreach from=$paymentmethods item=pmethod}
                        <div class="skrillpaymentmethod" id="skrillpaymentmethoddiv_{$pmethod.pmethodshort}">
                            <div class="skrillformfield">
                                <div class="skrillpmethod">
                                    <input type="checkbox" class="skrillcheckbox" name="{$pmethod.pmethodshort}_enabled" id="{$pmethod.pmethodshort}_enabled" {if $pmethod.pmethodenabled}checked{/if} value="1"/>
                                    <label class="skrillradio" for="{$pmethod.pmethodshort}_enabled">{$pmethod.pmethodlabel}</label>
                                </div>
                                <label class="skrillradio transactionmodewrapper" for="{$pmethod.pmethodshort}_order">Order:
                                    <select class="skrillnumericselect" name="{$pmethod.pmethodshort}_order" id="{$pmethod.pmethodshort}_order">
                                        {for $i=1 to $paymentmethodssz}
                                            <option value="{$i}"{if $i == $pmethod.pmethodorder} selected="selected"{/if}>{$i}</option>
                                        {/for}
                                    </select>
                                </label>
                            </div>
                            <div class="skrillformfield">
                                <label class="skrillfiltercountry"><a href="#" class="skrillshowcountries" id="skrillshowcountries_{$pmethod.pmethodshort}">Restrain by Countries</a></label>
                            </div>
                            <span id="skrillpaymentmethodcountry_{$pmethod.pmethodshort}">
                                <div class="skrillformfield">
                                    <select class="skrillcountries" name="{$pmethod.pmethodshort}_countries[]" id="{$pmethod.pmethodshort}_countries" multiple='multiple'>
                                        {foreach from=$Countries item=country}
                                            <option value="{$country.id_country}">{$country.name}</option>
                                        {/foreach}
                                    </select>
                                    <script>
                                        jQuery('#{$pmethod.pmethodshort}_countries').multiSelect({
                                                selectableHeader: "<div class='custom-header'>Select country:</div>",
                                                selectionHeader: "<div class='custom-header'>Selected countries</div>"});
                                        jQuery('#{$pmethod.pmethodshort}_countries').multiSelect('select',[{foreach from=$pmethod.pmethodcountries item=pmethodcountry}'{$pmethodcountry}'{if !$pmethodcountry@last},{/if}{/foreach}]);   
                                    </script>
                                </div>
                            </span>
                        </div>
                    {/foreach}
                </div>
            </div>    
            <div id="skrillformbuttons">
                <button class="skrillbuttonconfirm" id="skrillbuttoncancel" name="skrillbuttonconfirm" value="0">Cancel</button>
                <button class="skrillbuttonconfirm" id="skrillbuttonsave" name="skrillbuttonconfirm" value="1">Save</button>
            </div>
        </form>
    </div>
</div>


<script>
    jQuery("#tabs-nobg").tabs();
</script>
