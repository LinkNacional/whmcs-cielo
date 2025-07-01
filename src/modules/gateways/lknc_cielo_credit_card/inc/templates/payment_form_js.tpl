<script type="text/javascript">
    {* Add inputs variables (querySelects) *}

    const customerCardCont = document.querySelector('#customer-cards-container')
    const customerCardInput = document.querySelector('#customer-card')

    const partialPayAmountCont = document.querySelector('#partial-payment-amount-container') ?? false
    const partialPayAmountInputCont = document.querySelector('#partial-payment-input-container') ?? false
    const partialPayAmountInput = document.querySelector('#partial-payment-amount') ?? false
    const partialPayMinimumAmountError = document.querySelector('#partial-pay-minimum-amount-error')
    const partialPayMaximumAmountError = document.querySelector('#partial-pay-maximum-amount-error')

    const cardNumberCont = document.querySelector('#card-number-container')
    const cardNumberInput = document.querySelector('#card-number')

    const cardCvvAndNumberCont = document.querySelector('#card-cvv-numer-container')

    const cardCvvCont = document.querySelector('#card-cvv-container')
    const cardCvvInput = document.querySelector('#card-cvv')
    const cardCvvTipBtn = document.querySelector('#card-cvv-tip-btn')
    const cardCvvTipImg = document.querySelector('#card-cvv-tip-img')

    const cardHolderNameCont = document.querySelector('#card-holder-name-container')
    const cardHolderNameInput = document.querySelector('#card-holder-name')

    const cardExpirationDateCont = document.querySelector('#card-expiration-container')
    const cardExpirationMonthInput = document.querySelector('#card-expiration-month')
    const cardExpirationYearInput = document.querySelector('#card-expiration-year')

    const installmentCont = document.querySelector('#installment-container')
    const installmentInput = document.querySelector('#installment')

    const saveCardCont = document.querySelector('#save-card-container')
    const saveCardInput = document.querySelector('#save-card-input')
    const payBtn = document.querySelector('#pay-btn')

    const invoiceIdHidden = document.querySelector('#invoice-id-hidden')
    const invoiceAmountHidden = document.querySelector('#invoice-amount-hidden')
    const customerIdHidden = document.querySelector('#customer-id-hidden')
    const maxAttemptsReachedFeedbackHidden = document.querySelector('#max-attempts-reached-feedback')

    const divider1 = document.querySelector('#divider-1')
    const divider2 = document.querySelector('#divider-2')

    const notifCont = document.querySelector('#lknc-creditpay-notif')
    const notifSuccessIcon = document.querySelector('#lknc-creditpay-notif-icon')
    const notifTitle = document.querySelector('#lknc-creditpay-notif-ttl')
    const notifDesc = document.querySelector('#lknc-creditpay-notif-desc')
    const notifBtn = document.querySelector('#lknc-creditpay-notif-btn')

    const enablePartialPaymentInput = document.querySelector('#enable-partial-payment-input')

    class InstallmentField {
        constructor(containerId, inputId) {
            this.container = document.querySelector('#' + containerId)
            this.input = document.querySelector('#' + inputId)
        }

        isFieldEnabled() {
            return this.container !== null
        }

        getValue() {
            return this.isFieldEnabled() ? this.input.value : 1
        }

        isValid() {
            return this.isFieldEnabled() ? this.input.checkValidity() : true
        }

        setInstallmentOptions(html) {
            if (this.isFieldEnabled()) {
                this.input.innerHTML = html
            }
        }
    }

    const installmentField = new InstallmentField('installment-container', 'installment')

    const configs = {
        paymentMethod: 'normal', // 'tokenized' or 'normal'
        actionUrl: '{$actionUrl}',
        cardSaveMode: {$cardSaveMode},
        invoice: {
            balance: {$invoiceBalance}
        },
        minimumInstallmentAmount: {$minimumInstallmentAmount},
        payment: {
            amount: {$invoiceBalance}
        },
        currentBrand: 'outras',
        installmentsPerBrand: {$installmentsPerBrand|@json_encode nofilter}
    }

    const currencyFormatter = new Intl.NumberFormat('pt-BR', {
        currency: 'BRL',
        maximumFractionDigits: 2,
        minimumFractionDigits: 2,
        useGrouping: false,
    });

    const showDialog = (success, title, messages) => {
        notifSuccessIcon.style.backgroundColor = success ? '#28a745' : '#dc3545'
        notifTitle.innerHTML = title
        notifDesc.innerHTML = ''

        messages = Array.isArray(messages) ? messages : [messages]
        messages.forEach(msg => {
            notifDesc.innerHTML += '<p>' + msg + '</p>'
        })
        notifBtn.onclick = success ? () => { location.reload() } : null
        $('#lknc-creditpay-notif').modal('show')
    }

    const getOnlyNumbersFromValue = val => val.replace(/[^0-9.]/g, '')

    const toggleField = (elements, display, required, value = '') => {
        required = required === false ? '' : true

        const disableContFields = (element) => {
            const inputs = element.getElementsByTagName('input');
            const selects = element.getElementsByTagName('select');

            const childs = [].concat(Array.from(inputs)).concat(Array.from(selects))
            childs.forEach(field => {
                elements.required = required
                elements.value = value
            })
        }

        if (Array.isArray(elements)) {
            elements.forEach(el => {
                if (el !== null) {
                    el.style.display = display
                    disableContFields(el)
                }
            })
        } else {
            if (elements !== null) {
                elements.style.display = display
                disableContFields(elements)
            }
        }
    }

    const switchToTokenizedCardPaymentInterface = () => {
        toggleField([
            cardCvvAndNumberCont,
            cardExpirationDateCont,
            cardHolderNameCont,
            divider2,
            saveCardCont
        ], 'none', false)
    }

    const switchToNormalPaymentInterface = () => {
        checkEnableSaveCardBtn(cardNumberInput.value)
        toggleField([cardExpirationDateCont, cardCvvAndNumberCont, cardHolderNameCont, divider2], 'flex', true)
    }

    const makeRequestBody = () => {
        let body = null

        if (configs.paymentMethod === 'normal') {
            body = {
                card: {
                    holderName: cardHolderNameInput.value,
                    number: cardNumberInput.value,
                    cvv: cardCvvInput.value,
                    expiration: {
                        month: cardExpirationMonthInput.value,
                        year: cardExpirationYearInput.value
                    }
                },
                saveCardInProfile: saveCardCont ? saveCardInput.checked : false
            }
        } else {
            body = {
                card: {
                    token: customerCardInput.selectedOptions['0'].dataset.token,
                    expiry: customerCardInput.selectedOptions['0'].dataset.exp,
                    brand: customerCardInput.selectedOptions['0'].dataset.brand,
                    type: customerCardInput.selectedOptions['0'].dataset.type,
                }
            }
        }

        Object.assign(body, {
            action: 'payment',
            customer: {
                id: customerIdHidden.value
            },
            payment: {
                method: configs.paymentMethod,
                installment: installmentField.getValue(),
            },
            invoice: {
                id: invoiceIdHidden.value
            }
        })

        if (partialPayAmountInput && partialPayAmountInput.value !== '') {
            body.payment.amount = partialPayAmountInput.value.toString().replace(',', '.')
        } else {
            body.payment.amount = {$invoiceBalance}.toString().replace(',', '.')
        }

        return body
    }

    const validateFields = (paymentMethod) => {
        if (paymentMethod === 'normal') {
            if (
                cardNumberInput.checkValidity() &&
                cardCvvInput.checkValidity() &&
                cardHolderNameInput.checkValidity() &&
                cardExpirationMonthInput.checkValidity() &&
                cardExpirationYearInput.checkValidity() &&
                installmentField.isValid()
            ) {
                return true
            } else {
                return false
            }
        } else {
            if (
                customerCardInput &&
                customerCardInput.value === 'tokenized' &&
                installmentField.isValid()
            ) {
                return true
            } else {
                return false
            }
        }
    }

    const checkEnableSaveCardBtn = async (cardNumber) => {
        if (configs.cardSaveMode === 2) {
            return fetch(configs.actionUrl, {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'card-can-be-tokenized',
                        cardNumber: cardNumber,
                        invoice: {
                            id: invoiceIdHidden.value
                        }
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.data.canBeSaved) {
                        saveCardCont.style.visibility = 'visible'
                        saveCardInput.disabled = false
                        saveCardInput.checked = true
                    } else {
                        saveCardCont.style.visibility = 'hidden'
                        saveCardInput.disabled = true
                        // saveCardInput.checked = false
                    }
                })
                .catch((err) => {
                    saveCardCont.style.visibility = 'hidden'
                    saveCardInput.disabled = true
                    // saveCardInput.checked = false
                })
        }
    }

    const handleSwitchFormInterface = (customerChoice) => {
        if (customerChoice === 'tokenized') {
            configs.paymentMethod = 'tokenized'
            switchToTokenizedCardPaymentInterface()
        } else {
            configs.paymentMethod = 'normal'
            switchToNormalPaymentInterface()
        }
    }

    const handleCardNumber = async (event) => {
        const cardNumber = event.target.value.replace(/[^0-9]/g, '')
        const isValid = /^\d+$/.test(cardNumber)

        cardNumberInput.value = cardNumber

        if (cardNumber.length >= 14) {
            requestCardBrand(cardNumber)
        }

        if (configs.cardSaveMode === 2) {
            if (cardNumber.length >= 14) {
                checkEnableSaveCardBtn(cardNumber)
            } else {
                if (cardNumber.length < 14) {
                    saveCardCont.style.visibility = 'hidden'
                    saveCardInput.disabled = true
                }
            }
        }
    }

    const showPartialPaymentInput = (event) => {
        const currentDisplay = partialPayAmountInputCont.style.display
        const newDisplay = currentDisplay === 'flex' ? 'none' : 'flex'

        if (newDisplay === 'none') {
            genereteInstallments(getPaymentAmount())
        }

        partialPayAmountInputCont.style.display = newDisplay
        partialPayAmountInput.value = ''
    }

    // Replaces comma by points and removes all non-number.
    const getFormatableCurrencyString = val => val.toString().replace(',', '.').replace(/[^0-9.]/g, '')

    const getPaymentAmount = () => {
        if (enablePartialPaymentInput && enablePartialPaymentInput.checked) {
            const partialValue = getFormatableCurrencyString(partialPayAmountInput.value)

            return partialValue > 0 ? partialValue : configs.invoice.balance
        } else {
            return configs.invoice.balance
        }
    }

    let partialPaymentFormatTimeout = Function

    const handlePartialPaymentAmount = (event) => {
        clearTimeout(partialPaymentFormatTimeout)

        partialPayMinimumAmountError.style.display = 'none'
        partialPayMaximumAmountError.style.display = 'none'

        let paymentAmount = getFormatableCurrencyString(event.target.value)

        if (paymentAmount === '') {
            configs.payment.amount = configs.invoice.balance
            genereteInstallments(getPaymentAmount())
            return
        }

        partialPaymentFormatTimeout = setTimeout(() => {
            partialPayAmountInput.value = currencyFormatter.format(paymentAmount)

            if (paymentAmount < configs.minimumInstallmentAmount) {
                partialPayMinimumAmountError.style.display = 'flex'
            } else if (paymentAmount > configs.invoice.balance) {
                partialPayMaximumAmountError.style.display = 'flex'
            }

            if (
                paymentAmount < configs.minimumInstallmentAmount ||
                paymentAmount > configs.invoice.balance
            ) {
                partialPayAmountInput.value = ''
            } else {
                partialPayAmountInput.value = currencyFormatter.format(paymentAmount)
            }

            configs.payment.amount = paymentAmount

            genereteInstallments(getPaymentAmount())
        }, 700)
    }

    function allowOnlyNumbers(evt) {
        evt = (evt) ? evt : window.event;

        const charCode = (evt.which) ? evt.which : evt.keyCode;
        const ctrlPressed = evt.ctrlKey || evt.metaKey

        if (
            (charCode >= 48 && charCode <= 57 && !evt.shiftKey) || // up keyboard number
            (charCode >= 96 && charCode <= 105) || // numpad keys
            (charCode === 67 && ctrlPressed) || // CTRL + C
            (charCode === 86 && ctrlPressed) || // CTRL + V
            (charCode === 65 && ctrlPressed) || // CTRL + A
            (charCode === 88 && ctrlPressed) || // CTRL + A
            (charCode === 9) ||
            (charCode === 8) // Backspace
        ) {
            return true;
        } else {
            evt.preventDefault()
            return false;
        }
    }

    const requestCardBrand = (cardNumber) => {
        payBtn.disabled = true
        const requestBody = {
            action: 'get-card-brand',
            cardNumber: cardNumber,
            invoice: {
                id: invoiceIdHidden.value
            }
        }

        fetch(configs.actionUrl, {
                method: 'POST',
                body: JSON.stringify(requestBody)
            })
            .then(res => res.json())
            .then(res => {
                defineCurrentBrand(res.data.brand.replaceAll(' ', ''))
            })
            .catch((err) => {
                showDialog(false, 'Não foi possível capturar a bandeira')
            })
            .finally(() => {
                payBtn.disabled = false
            })
    }

    const defineCurrentBrand = (brand) => {
        configs.currentBrand = brand
        genereteInstallments(getPaymentAmount())
    }

    const genereteInstallments = (paymentAmount) => {
        {literal}
            let installments = ''
        {/literal}
        const maxInstallment = configs.installmentsPerBrand[configs.currentBrand] ?? configs.installmentsPerBrand[
            'outras']

        if (paymentAmount < configs.minimumInstallmentAmount) {
            {literal}
                installments += `
<option value="1">
1 x R$${currencyFormatter.format(paymentAmount)} sem juros
</option>
`
            {/literal}
        } else {
            for (parcelDivisor = 1; parcelDivisor <= maxInstallment; parcelDivisor++) {
                const parcelValue = paymentAmount / parcelDivisor

                if (parcelValue >= configs.minimumInstallmentAmount) {
                    {literal}
                        installments += `
<option value="${parcelDivisor}">
${parcelDivisor} x R$${currencyFormatter.format(parcelValue)} sem juros
</option>
          `
                    {/literal}
                }
            }
        }

        installmentField.setInstallmentOptions(installments)
    }

    const submit = () => {
        if (validateFields(configs.paymentMethod)) {
            payBtn.disabled = true

            const requestBody = makeRequestBody()

            fetch(configs.actionUrl, {
                    method: 'POST',
                    body: JSON.stringify(requestBody)
                })
                .then(res => res.json())
                .then(res => {
                    if (res?.payment?.success) {
                        showDialog(true,
                            'Pagamento efetuado com sucesso!',
                            res.payment.message
                        )
                        setTimeout(() => {
                            location.reload()
                        }, 2000)
                    } else {
                        payBtn.disabled = false
                        let msg
                        if (res?.payment?.message) {
                            msg = res.payment.message
                        } else if (
                            res?.reason === 'attempts' &&
                            maxAttemptsReachedFeedbackHidden.value.length > 0
                        ) {
                            msg = maxAttemptsReachedFeedbackHidden.value
                        } else {
                            msg =
                                'O meio de pagamento não esta mais disponível pois atingiu o limite de tentativas de pagamento.'
                        }

                        showDialog(
                            false,
                            'Pagamento não efetuado',
                            msg
                        )

                        if (res?.reason === 'attempts') {
                            setTimeout(() => {
                                location.reload()
                            }, 3500)
                        }
                    }
                })
                .catch((err) => {
                    payBtn.disabled = false
                    showDialog(
                        false,
                        'Pagamento não efetuado',
                        'Erro ao se comunicar com o servidor. O pagamento não foi efetuado.'
                    )
                })
        } else {
            showDialog(false, 'Campos inválidos', 'Preencha todos os campos do formulário.')
        }
    }

    {* EVENT LISTENERS *}
    if (customerCardCont) {
        customerCardInput.addEventListener('change', e => handleSwitchFormInterface(e.target.value))
    }

    if (cardNumberInput) {
        cardNumberInput.addEventListener('keydown', allowOnlyNumbers)
        cardNumberInput.addEventListener('input', handleCardNumber)
    }

    if (cardCvvInput) {
        cardCvvInput.addEventListener('keydown', allowOnlyNumbers)
    }

    payBtn.addEventListener('click', submit)

    if (partialPayAmountCont) {
        partialPayAmountInput.addEventListener('input', handlePartialPaymentAmount)
        partialPayAmountInput.addEventListener('keydown', () => clearTimeout(partialPaymentFormatTimeout))
        partialPayAmountInput.addEventListener('focusout', () => {
            partialPayMaximumAmountError.style.display = 'none'
            partialPayMinimumAmountError.style.display = 'none'
        })
    }

    {* CVV tip *}
    if (cardCvvTipBtn) {
        cardCvvTipBtn.addEventListener('mouseenter', () => cardCvvTipImg.style.display = 'block')
        cardCvvTipBtn.addEventListener('mouseleave', () => cardCvvTipImg.style.display = 'none')
    }

    if (enablePartialPaymentInput) {
        enablePartialPaymentInput.addEventListener('change', showPartialPaymentInput)
    }

    if (customerCardInput) {
        customerCardInput.addEventListener('change', () => {
            if (customerCardInput.value === 'tokenized') {
                const brand = customerCardInput.selectedOptions['0'].dataset.brand
                defineCurrentBrand(brand.replaceAll(' ', '').toLowerCase())
            } else {
                defineCurrentBrand('outras')
            }
        })
    }
</script>
