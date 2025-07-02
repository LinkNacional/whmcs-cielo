{if $data->sendClientAddressDetailsTo3ds}
    <!-- dados de cobrança -->

    <div class="custom-control custom-switch mb-3 mt-3">
        <input
            type="checkbox"
            class="custom-control-input"
            id="switchCardAddressIsSameAsProfile"
        >
        <label
            class="custom-control-label"
            for="switchCardAddressIsSameAsProfile"
            style="font-size: 0.8em;"
        >Os dados de cobrança do cartão são os mesmos do meu perfil</label>
    </div>

    <div style="height: 540;">
        <h6 class="mb-3">Endereço de cobrança</h6>

        <div
            class="form-row"
            style="transform: scale(0.85); transform-origin: top;"
        >
            <div class="col-12 mb-1">
                <div class="form-group input-group-sm text-left">
                    <input
                        type="text"
                        class="form-control address-full-name"
                        value="{$data->address->billToName}"
                        required
                    >
                    <small class="form-text text-muted">Nome completo</small>
                </div>
            </div>

            <div class="col">
                <div class="form-group input-group-sm text-left">
                    <input
                        class="form-control address-zipcode"
                        value="{$data->address->zipcode}"
                        required
                        maxlength="8"
                    >
                    <small class="form-text text-muted">CEP<div
                            id="zipcode-spinner"
                            class="spinner-border spinner-border-sm"
                            role="status"
                            style="display: none; margin-left: 8px;"
                        >
                        </div>
                    </small>
                </div>
            </div>

            <div class="col">
                <div class="form-group input-group-sm text-left">
                    <input
                        class="form-control address-country"
                        value="BR"
                        disabled
                        required
                    >
                    <small class="form-text text-muted">País</small>
                </div>
            </div>

            <div class="col-12"></div>

            <div class="col">
                <div class="form-group input-group-sm text-left">
                    <select
                        name="state"
                        class="form-control address-state"
                        required
                    >
                        <option value="">Selecionar</option>
                        <option
                            value="AC"
                            {if $data->address->state == 'AC'}selected{/if}
                        >AC</option>
                        <option
                            value="AL"
                            {if $data->address->state == 'AL'}selected{/if}
                        >AL</option>
                        <option
                            value="AP"
                            {if $data->address->state == 'AP'}selected{/if}
                        >AP</option>
                        <option
                            value="AM"
                            {if $data->address->state == 'AM'}selected{/if}
                        >AM</option>
                        <option
                            value="BA"
                            {if $data->address->state == 'BA'}selected{/if}
                        >BA</option>
                        <option
                            value="CE"
                            {if $data->address->state == 'CE'}selected{/if}
                        >CE</option>
                        <option
                            value="DF"
                            {if $data->address->state == 'DF'}selected{/if}
                        >DF</option>
                        <option
                            value="ES"
                            {if $data->address->state == 'ES'}selected{/if}
                        >ES</option>
                        <option
                            value="GO"
                            {if $data->address->state == 'GO'}selected{/if}
                        >GO</option>
                        <option
                            value="MA"
                            {if $data->address->state == 'MA'}selected{/if}
                        >MA</option>
                        <option
                            value="MT"
                            {if $data->address->state == 'MT'}selected{/if}
                        >MT</option>
                        <option
                            value="MS"
                            {if $data->address->state == 'MS'}selected{/if}
                        >MS</option>
                        <option
                            value="MG"
                            {if $data->address->state == 'MG'}selected{/if}
                        >MG</option>
                        <option
                            value="PA"
                            {if $data->address->state == 'PA'}selected{/if}
                        >PA</option>
                        <option
                            value="PB"
                            {if $data->address->state == 'PB'}selected{/if}
                        >PB</option>
                        <option
                            value="PR"
                            {if $data->address->state == 'PR'}selected{/if}
                        >PR</option>
                        <option
                            value="PE"
                            {if $data->address->state == 'PE'}selected{/if}
                        >PE</option>
                        <option
                            value="PI"
                            {if $data->address->state == 'PI'}selected{/if}
                        >PI</option>
                        <option
                            value="RJ"
                            {if $data->address->state == 'RJ'}selected{/if}
                        >RJ</option>
                        <option
                            value="RN"
                            {if $data->address->state == 'RN'}selected{/if}
                        >RN</option>
                        <option
                            value="RS"
                            {if $data->address->state == 'RS'}selected{/if}
                        >RS</option>
                        <option
                            value="RO"
                            {if $data->address->state == 'RO'}selected{/if}
                        >RO</option>
                        <option
                            value="RR"
                            {if $data->address->state == 'RR'}selected{/if}
                        >RR</option>
                        <option
                            value="SC"
                            {if $data->address->state == 'SC'}selected{/if}
                        >SC</option>
                        <option
                            value="SP"
                            {if $data->address->state == 'SP'}selected{/if}
                        >SP</option>
                        <option
                            value="SE"
                            {if $data->address->state == 'SE'}selected{/if}
                        >SE</option>
                        <option
                            value="TO"
                            {if $data->address->state == 'TO'}selected{/if}
                        >TO</option>
                    </select>
                    <small class="form-text text-muted">Estado</small>
                </div>
            </div>

            <div class="col">
                <div class="form-group input-group-sm text-left">
                    <input
                        class="form-control address-city"
                        value="{$data->address->city}"
                        required
                    >
                    <small class="form-text text-muted">Cidade</small>
                </div>
            </div>

            <div class="col-12"></div>

            <div class="col">
                <div class="form-group input-group-sm text-left">
                    <input
                        class="form-control address-street2"
                        value="{$data->address->street2}"
                        maxlength="25"
                        required
                    >
                    <small class="form-text text-muted">Bairro</small>
                </div>
            </div>

            <div class="col-12"></div>

            <div class="col">
                <div class="form-group input-group-sm text-left">
                    <input
                        class="form-control address-street1"
                        value="{$data->address->street1}"
                        required
                    >
                    <small class="form-text text-muted">Rua</small>
                </div>
            </div>

            <div class="col">
                <div class="form-group input-group-sm text-left">
                    <input
                        class="form-control address-number"
                        maxlength="5"
                        required
                        value="{$data->address->number}"
                    >
                    <small class="form-text text-muted">Número</small>
                </div>
            </div>

            <div class="col-12">
                <div class="form-group input-group-sm text-left">
                    <input
                        class="form-control address-complement"
                        maxlength="25"
                    >
                    <small class="form-text text-muted">Complemento</small>
                </div>
            </div>

            <div class="col-12 mt-3">
                <div class="form-group input-group-sm text-left">
                    <input
                        class="form-control address-email"
                        type="email"
                        value="{$data->address->email}"
                        required
                    >
                    <small class="form-text text-muted">Email</small>
                </div>
            </div>

            <div class="col-12">
                <div class="form-group input-group-sm text-left">
                    <input
                        class="form-control address-phonenumber"
                        value="{$data->address->phoneNumber}"
                        required
                        style="width: 100% !important; max-width: 100% !important;"
                    >
                    <small class="form-text text-muted">Telefone</small>
                </div>
            </div>
        </div>
    </div>

    <input
        type="hidden"
        class="bpmpi_billto_zipcode"
    >
    <input
        type="hidden"
        class="bpmpi_billto_country"
    >
    <input
        type="hidden"
        class="bpmpi_billto_street1"
    >
    <input
        type="hidden"
        class="bpmpi_billto_street2"
    >
    <input
        type="hidden"
        class="bpmpi_billto_state"
    >
    <input
        type="hidden"
        class="bpmpi_billto_city"
    >
    <input
        type="hidden"
        class="bpmpi_billto_email"
    >
    <input
        type="hidden"
        class="bpmpi_billto_phonenumber"
    >
    <input
        type="hidden"
        class="bpmpi_billto_contactname"
    >
{/if}
