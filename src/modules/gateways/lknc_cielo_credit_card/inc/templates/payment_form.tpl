{if isset($maxAttemptsReached)}
    <p class="lead lkn-cc-payment-limit">{$maxAttemptsReachedFeedback}</p>
{elseif isset($allowPaymentOnlyForBrlErrorFeedback)}
    <p class="lead lkn-cc-brl-only">
        {($allowPaymentOnlyForBrlErrorFeedback !== '') ? $allowPaymentOnlyForBrlErrorFeedback : 'Não é possível realizar o pagamento por esse meio, pois a fatura não está em Reais (R$).'}
    </p>
{else}
    {include file="dialogs.tpl"}
    <form
        id="lknc-credit-card-payform"
        method="POST"
        action="{$formTarget}"
        class="text-left mt-4"
    >
        <h5
            class="mb-4 text-center"
            {if $isUsingLagomTheme}style="color: #fffc;"
            {/if}
        >
            <i
                class="fal fa-lock"
                style="color: #22de54;"
                title="Transação segura por certificado SSL 256bits"
            ></i>
            Pagamento Seguro.
        </h5>

        {if !empty($paymentMethods)}

            <div
                id="customer-cards-container"
                class="form-row mb-2"
            >
                <div class="col form-group">
                    <label for="customer-card">Meus cartões</label>

                    <select
                        id="customer-card"
                        class="form-control custom-select"
                        name="card-token"
                    >
                        <option value="normal">Usar novo cartão de crédito</option>
                        {foreach $paymentMethods as $card}
                            <option
                                value="tokenized"
                                data-token="{$card['token']}"
                                data-brand="{$card['brand']}"
                                data-type="{$card['type']}"
                                data-exp="{$card['expDate']}"
                            >
                                ************{$card['lastFourDigits']} - {$card['brand']|capitalize}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>

        {/if}

        <div
            id="divider-1"
            class="dropdown-divider mb-4"
        ></div>

        <div
            id="card-holder-name-container"
            class="form-row"
        >
            {* CARD HOLDER NAME FIELD *}
            <div class="col form-group">
                <label for="card-holder-name">Titular do cartão</label>
                <input
                    id="card-holder-name"
                    class="form-control"
                    name="card-holder-name"
                    type="text"
                    maxlength="255"
                    minlength="5"
                    required
                    autofocus
                    {if $isUsingLagomTheme}
                        style="height: 44px; padding: .375rem .75rem;"
                    {/if}
                >
            </div>
        </div>

        <div
            id="card-cvv-numer-container"
            class="form-row"
        >

            {* CARD NUMBER FIELD *}
            <div
                id="card-number-container"
                class="col form-group {if $isUsingLagomTheme}mr-3{/if}"
            >
                <label for="card-number">Número do cartão</label>
                <input
                    id="card-number"
                    class="form-control"
                    name="card-number"
                    type="text"
                    minlength="5"
                    maxlength="21"
                    required
                    {if $isUsingLagomTheme}
                        style="height: 44px; padding: .375rem .75rem;"
                    {/if}
                >
            </div>

            {* CVV FIELD *}
            <div class="col-4 form-group">
                <label for="card-cvv">CVV</label>
                <div class="input-group">
                    <div
                        id="card-cvv-tip-btn"
                        class="input-group-prepend"
                        {if $isUsingLagomTheme}
                            style="height: 44px;"
                        {/if}
                    >
                        <div class="input-group-text"><i class="fas fa-info"></i></div>
                        <img
                            id="card-cvv-tip-img"
                            style="display: none; position: absolute; right: 0px; top: 0px; min-width: 260px; z-index: 99; margin-top: 45px;"
                            src="https://cliente.linknacional.com.br/assets/img/ccv.gif"
                        >
                    </div>

                    <input
                        id="card-cvv"
                        class="form-control"
                        name="card-cvv"
                        type="text"
                        maxlength="4"
                        minlength="3"
                        required
                        {if $isUsingLagomTheme}
                            style="height: 44px; padding: .375rem .75rem;"
                        {/if}
                    >
                </div>
            </div>

        </div>

        {* CARD EXPIRY DATE *}
        <div
            id="card-expiration-container"
            class="form-row"
        >

            <label class="col-12">Vencimento do cartão</label>

            <div class="col form-group {if $isUsingLagomTheme}mr-3{/if}">
                <select
                    id="card-expiration-month"
                    class="form-control custom-select"
                    name="card-expiration-month"
                    required
                    {if $isUsingLagomTheme}
                        style="height: 44px; padding: .375rem .75rem;"
                    {/if}
                >
                    <option value="">Mês</option>
                    {for $month=1 to 12}
                        <option value="{$month}">{$month}</option>
                    {/for}
                </select>
            </div>

            <div class="col form-group">
                <select
                    id="card-expiration-year"
                    class="form-control custom-select"
                    name="card-expiration-year"
                    required
                >
                    <option value="">Ano</option>
                    {for $year=$smarty.now|date_format:"%Y" to $smarty.now|date_format:"%Y" + 20}
                        <option value="{$year}">{$year}</option>
                    {/for}
                </select>
            </div>

        </div>

        <div
            id="divider-2"
            class="dropdown-divider mb-4"
        ></div>

        {* PARTIAL PAYMENT FIELD *}
        {if $partialPayment && $invoiceBalance >= $partialPaymentMinimumAmount}

            <div
                id="partial-payment-amount-container"
                class="form-row"
            >
                <div class="col form-group">
                    <div
                        id="enable-partial-payment-container"
                        class="form-row mt-2"
                    >
                        <div class="col form-group">
                            <div
                                id="partialPaymentTooltip"
                                onmouseleave="$('#partialPaymentTooltip').tooltip('hide')"
                                class="custom-control custom-checkbox"
                                title="O pagamento parcial é utilizado para dividir o pagamento em outras formas de pagamento ou em outro número de cartão. O valor para pagar informado no campo será o total que deseja pagar com o cartão. Se for pagar todo o valor da fatura, deixe o campo sem preencher."
                                data-toggle="tooltip"
                                data-placement="left"
                            >
                                <input
                                    id="enable-partial-payment-input"
                                    class="custom-control-input"
                                    type="checkbox"
                                    name="enable-partial-payment"
                                    style="cursor: pointer;"
                                >
                                <label
                                    class="custom-control-label"
                                    for="enable-partial-payment-input"
                                    style="cursor: pointer;"
                                >Pagamento com valor parcial</label>
                            </div>
                        </div>
                    </div>

                    {* <label for="partial-payment-amount">Quanto da fatura você deseja parcelar?</label> *}

                    <div
                        id="partial-payment-input-container"
                        class="input-group"
                        style="display: none;"
                    >
                        <div class="input-group-prepend">
                            <span class="input-group-text">R$</span>
                        </div>

                        <input
                            id="partial-payment-amount"
                            onmouseleave="$('#partial-payment-amount').tooltip('hide')"
                            class="form-control"
                            name="partial-payment-amount"
                            type="text"
                            {* placeholder="Valor total: R${$invoiceBalance}" *}
                            placeholder="Valor para pagar, total R${$invoiceBalance|replace:'.':','}"
                            {* value="{$invoiceBalance|replace:'.':','}" *}
                            maxlength="8"
                            minlength="1"
                            data-toggle="tooltip"
                            data-placement="left"
                            title="Deixe vazio para parcelar o valor total disponível que é R${$invoiceBalance|replace:'.':','}."
                            {if $isUsingLagomTheme}
                                style="height: 44px; padding: .375rem .75rem; border-top-right-radius: 3px !important; border-bottom-right-radius: 3px !important;"
                            {/if}
                        >

                        <div
                            id="partial-pay-minimum-amount-error"
                            class="invalid-feedback"
                            style="display: none;s"
                        >
                            O valor deve ser maior ou igual a {$minimumInstallmentAmount}.
                        </div>

                        <div
                            id="partial-pay-maximum-amount-error"
                            class="invalid-feedback"
                            style="display: none;s"
                        >
                            O valor deve menor ou igual a {$invoiceBalance}.
                            Deixe vazio para o valor máximo.
                        </div>
                    </div>
                </div>
            </div>

        {/if}

        {* CARD INSTALLMENTS FIELD *}
        {if $enableInstallment}
            <div
                id="installment-container"
                class="form-row text-left mt-2"
            >
                <div class="col form-group">
                    <label for="installment">Parcelamento</label>
                    <select
                        id="installment"
                        class="form-control custom-select"
                        name="installment"
                        required
                    >
                        {if $invoiceBalance < $minimumInstallmentAmount}
                            <option value="1">1 x R$ {$invoiceBalance} sem juros</option>
                        {else}
                            {for $parcelDivisor=1 to $installmentsPerBrand['outras']}
                                {$parcelValue=($invoiceBalance / $parcelDivisor)|string_format:"%.2f"}
                                {if $parcelValue >= $minimumInstallmentAmount}
                                    <option value="{$parcelDivisor}">
                                        {$parcelDivisor} x R${$parcelValue|replace:'.':','} sem juros
                                    </option>
                                {/if}
                            {/for}
                        {/if}
                    </select>
                </div>
            </div>
        {/if}

        {* SAVE CARD CHECKBOX *}
        {if $cardSaveMode === 2}
            <div
                id="save-card-container"
                class="form-row text-center mt-2"
                style="visibility: hidden;"
            >
                <div class="col form-group">
                    <div
                        id="saveCardTooltip"
                        onmouseleave="$('#saveCardTooltip').tooltip('hide')"
                        class="custom-control custom-checkbox"
                        title="Armazenar com segurança o número do cartão de crédito."
                        data-toggle="tooltip"
                        data-placement="left"
                    >
                        <input
                            id="save-card-input"
                            class="custom-control-input"
                            type="checkbox"
                            name="save-card"
                            style="cursor: pointer;"
                            {($paymentMethods === []) ? 'checked' : ''}
                        >
                        <label
                            class="custom-control-label"
                            for="save-card-input"
                            style="cursor: pointer;"
                        >Salvar cartão no meu perfil</label>
                    </div>
                </div>
            </div>
        {/if}

        {* PAY BUTTON *}
        <div class="form-row mt-4">
            <div class="col form-group">
                <button
                    id="pay-btn"
                    type="button"
                    class="btn btn-primary btn-lg btn-block bg-success"
                >
                    <i
                        class="fa fa-money-bill-wave"
                        style="transform: scale(0.8);"
                    ></i>
                    Pagar
                </button>
            </div>
        </div>

        {* HIDDEN INPUTS *}
        <input
            id="invoice-id-hidden"
            name="invoice-id"
            value="{$invoiceId}"
            type="hidden"
        >
        <input
            id="invoice-amount-hidden"
            name="invoice-amount"
            value="{$invoiceBalance}"
            type="hidden"
        >
        <input
            id="customer-id-hidden"
            name="customer-id"
            value="{$customerId}"
            type="hidden"
        >
        <input
            id="max-attempts-reached-feedback"
            name="max-attempts-reached-feedback"
            value="{$maxAttemptsReachedFeedback}"
            type="hidden"
        >
    </form>

    {include file="payment_form_js.tpl"}
{/if}
