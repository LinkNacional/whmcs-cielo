{if isset($errorMsg)}
    <div
        class="alert alert-danger"
        role="alert"
    >
        {$errorMsg}
    </div>
{else}
    <form
        id="lkncielo3ds-form"
        autocomplete="off"
    >
        <input
            type="hidden"
            name="authEnabled"
            class="bpmpi_auth"
            value="{$data->authEnabled}"
        />

        <input
            type="hidden"
            id="min-installment-value"
            value="{$data->minInstallmentValue}"
        >

        <input
            type="hidden"
            id="original-payment-amount"
            value="{$data->paymentAmount}"
        >

        <input
            id="lkncielo3ds-env"
            type="hidden"
            value="{$data->env}"
        />

        <input
            type="hidden"
            name="accessToken"
            class="bpmpi_accesstoken"
            value="{$data->accessToken}"
        />

        <input
            style="display: none;"
            type="text"
            name="orderNumber"
            class="bpmpi_ordernumber"
            value="{$data->orderNumber}"
        />

        <select
            style="display: none;"
            name="currency"
            class="bpmpi_currency"
        >
            <option
                value="BRL"
                selected="selected"
            >BRL</option>
        </select>

        <input
            style="display: none;"
            type="text"
            name="amount"
            class="bpmpi_totalamount"
            value="{$data->cieloPaymentAmountFormat}"
        />

        <div>
            <div class="form-row">
                {if $data->discount->enableDiscount && ($data->discount->debitDiscountAmount || $data->discount->creditDiscountAmount)}
                    <div
                        id="discount-preview"
                        class="col-12 text-left mb-2"
                    >
                        <p style="margin-bottom: 5px; color: grey; font-size: 0.9em;">
                            De <s>R$ {$data->paymentAmount|number_format:2:",":"."}</s>
                        </p>
                        <p style="color: #91960F; font-weight: 500; font-size: 1.2em;">
                            {if $data->discount->debitPaymentAmountWithDiscount}
                                por <span id="payment-amount-with-amount">
                                    R$ {$data->discount->debitPaymentAmountWithDiscount|number_format:2:",":"."}
                                    <span style="font-size: 0.7em; color: grey;">no débito</span>
                                </span>
                            {/if}
                            <br>
                            {if $data->discount->creditPaymentAmountWithDiscount}
                                por <span id="payment-amount-with-amount">
                                    R$ {$data->discount->creditPaymentAmountWithDiscount|number_format:2:",":"."}
                                    <span style="font-size: 0.7em; color: grey;">no crédito</span>
                                </span>
                            {/if}
                        </p>
                        <p style="margin-bottom: 5px; color: grey; font-size: 0.9em;">
                            Descontos válidos apenas para o pagamento total.
                        </p>
                    </div>

                    <input
                        id="discount-input"
                        type="hidden"
                        data-debit-discount-amount="{$data->discount->debitDiscountAmount}"
                        data-debit-discount-percentage="{$data->discount->debitDiscountPercentage}"
                        data-debit-payment-amount-with-discount="{$data->discount->debitPaymentAmountWithDiscount}"
                        data-credit-discount-amount="{$data->discount->creditDiscountAmount}"
                        data-credit-discount-percentage="{$data->discount->creditDiscountPercentage}"
                        data-credit-payment-amount-with-discount="{$data->discount->creditPaymentAmountWithDiscount}"
                    >
                {/if}

                <div class="col-12">
                    <div class="form-group input-group-sm  text-left">
                        <label>Número do cartão</label>

                        <input
                            type="text"
                            class="form-control bpmpi_cardnumber"
                            name="cardNumber"
                            autocomplete="cc-number"
                            minlength="13"
                            maxlength="18"
                            required
                        >
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-group input-group-sm  text-left">
                        <label>Titular</label>
                        <input
                            type="text"
                            class="form-control"
                            name="holderName"
                            autocomplete="cc-name"
                            required
                        >
                    </div>

                </div>

                <div class="col-12 text-left">
                    <label>Vencimento</label>
                </div>

                <div class="col">
                    <div class="form-group input-group-sm text-left">
                        <select
                            name="expMonth"
                            class="custom-select custom-select-sm bpmpi_cardexpirationmonth"
                            required
                        >
                            <option value="">Mês</option>
                            <option value="01">1 - Janeiro</option>
                            <option value="02">2 - Fevereiro</option>
                            <option value="03">3 - Março</option>
                            <option value="04">4 - Abril</option>
                            <option value="05">5 - Maio</option>
                            <option value="06">6 - Junho</option>
                            <option value="07">7 - Julho</option>
                            <option value="08">8 - Agosto</option>
                            <option value="09">9 - Setembro</option>
                            <option value="10">10 - Outubro</option>
                            <option value="11">11 - Novembro</option>
                            <option value="12">12 - Dezembro</option>
                        </select>
                    </div>
                </div>

                <div class="col">
                    <div class="form-group input-group-sm text-left">
                        <select
                            class="custom-select custom-select-sm bpmpi_cardexpirationyear"
                            name="expYear"
                            required
                        >
                            <option value="">Ano</option>
                            <option value="2028">2028</option>
                            {for $year=$smarty.now|date_format:"%Y" to $smarty.now|date_format:"%Y" + 20}
                                <option value="{$year}">{$year}</option>
                            {/for}
                        </select>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-group input-group-sm  text-left">
                        <label>Código de Segurança (CVV)</label>
                        <input
                            type="text"
                            class="form-control"
                            id="cc_cvv"
                            name="cc_cvv"
                            autocomplete="cc-csc"
                            inputmode="numeric"
                            maxlength="4"
                            required
                        >
                    </div>

                </div>

                <div
                    id="card-type-wrapper"
                    class="col-12"
                >
                    <div class="form-group input-group-sm text-left">
                        <label>Pagamento pela função</label>
                        <select
                            class="custom-select custom-select-sm bpmpi_paymentmethod"
                            name="bpmpi_paymentmethod"
                            required
                        >
                            <option value="credit">Cartão de crédito</option>
                            <option value="debit">Cartão de débito</option>
                        </select>
                    </div>
                </div>

                {if $data->enablePartialPayment && $data->paymentAmount >= $data->partialPaymentMinAmount && $data->paymentAmount - $data->partialPaymentMinAmount > $data->partialPaymentMinAmount}
                    <div
                        id="enable-partial-payment-wrapper"
                        class="col-12 mt-4 mb-3 text-left"
                    >
                        <div
                            class="custom-control custom-switch"
                            title="O pagamento parcial é utilizado para dividir o pagamento em outras formas de pagamento ou em outro número de cartão. O valor para pagar informado no campo será o total que deseja pagar com o cartão. Se for pagar todo o valor da fatura, deixe o campo sem preencher."
                            data-toggle="tooltip"
                            data-placement="left"
                            onmouseleave="$('#enable-partial-payment-wrapper').tooltip('hide')"
                        >
                            <input
                                type="checkbox"
                                class="custom-control-input"
                                id="enable-partial-payment-input"
                            >
                            <label
                                class="custom-control-label"
                                for="enable-partial-payment-input"
                            >Pagamento com valor parcial</label>
                        </div>
                    </div>

                    <div
                        id="partial-payment-amount-wrapper"
                        class="col-12 mb-4"
                        style="display: none;"
                    >
                        <div class="form-group input-group-sm  text-left">
                            <input
                                id="partial-payment-amount"
                                class="form-control"
                                type="text"
                                placeholder="Valor para pagar, total R${$data->paymentAmount|number_format:2:",":"."}"
                                data-toggle="tooltip"
                                data-placement="left"
                                data-min-partial-amount="{$data->partialPaymentMinAmount}"
                                value="0.00"
                                title="Deixe vazio para parcelar o valor total disponível que é R${$data->paymentAmount|number_format:2:",":"."}."
                                onmouseleave="$('#partial-payment-amount').tooltip('hide')"
                            >

                            <p
                                id="partial-payment-amount-feedback"
                                class="text-danger mt-1"
                                style="font-size: 90%; opacity: 0; height: 22px !important;"
                            >
                            </p>
                        </div>
                    </div>
                {/if}


                <div
                    id="installments-wrapper"
                    class="col-12"
                >
                    <div
                        class="form-group input-group-sm text-left"
                        style="display: {if $habilitar_parcelas} block {else} none {/if};"
                    >
                        <label>Parcelamento</label>
                        <select
                            class="bpmpi_installments custom-select custom-select-sm"
                            name="installments"
                            required
                        >
                            {for $parcelDivisor=1 to 12}
                                {$parcelValue=($data->paymentAmount / $parcelDivisor)|string_format:"%.2f"}

                                {if $parcelValue >= $data->minInstallmentValue}
                                    <option
                                        {if $parcelDivisor === 1}selected{/if}
                                        value="{$parcelDivisor}"
                                    >
                                        {$parcelDivisor} x R${$parcelValue|replace:'.':','} sem juros
                                    </option>
                                {/if}
                            {/for}
                        </select>
                    </div>
                </div>
            </div>
        </div>


        {include file='./items/address.tpl'}
        {include file='./items/device.tpl'}
        {include file='./items/order.tpl'}
        {include file='./items/user.tpl'}

        <input
            id="btnSendOrder"
            class="btn btn-success btn-block mt-4"
            type="button"
            onclick="sendOrder()"
            value="Pagar"
            disabled
        />

        <button
            id="btnSendOrderLoading"
            class="btn btn-success btn-block mt-4"
            style="display: none;"
            type="button"
            disabled
        >
            <span
                class="spinner-border spinner-border-sm"
                role="status"
                aria-hidden="true"
            ></span>
            Processando...
        </button>
    </form>

    {include file='./modal.tpl'}
    <script>
        const systemURL = "{$systemURL}";
    </script>
    <script
        src="{$systemURL}/modules/gateways/lkncielo3ds/lib/resources/form/intlTelInput/js/intlTelInput.min.js"
        defer
    ></script>
    <script
        src="{$systemURL}/modules/gateways/lkncielo3ds/lib/resources/form/js/vanilla_masker.js"
        defer
    ></script>
    {* Refers to the step 3: https://developercielo.github.io/manual/autorizacao-com-autenticacao *}
    <script
        src="{$systemURL}/modules/gateways/lkncielo3ds/lib/resources/form/js/authorization.js"
        defer
    ></script>
    {* Refers to the step 2.1: https://developercielo.github.io/manual/integracao-javascript#passo-2-implementa%C3%A7%C3%A3o-do-script *}
    <script
        src="{$systemURL}/modules/gateways/lkncielo3ds/lib/resources/form/js/authenticationRequest.js"
        defer
    ></script>
    <script
        src="{$systemURL}/modules/gateways/lkncielo3ds/lib/resources/form/js/authentication.js"
        defer
    ></script>
{/if}