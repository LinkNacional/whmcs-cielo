/* globals VMasker $ intlTelInput */

const fields = {
  cardTypeWrapper: document.getElementById('card-type-wrapper'),
  installmentsWrapper: document.getElementById('installments-wrapper'),
  discountInput: document.getElementById('discount-input'),
  paymentAmountWithDiscountCont: document.getElementById('payment-amount-with-amount'),
  enablePartialPayment: document.getElementById('enable-partial-payment-input'),
  partialPayment: document.getElementById('partial-payment-amount'),
  partialPaymentAmountWrapper: document.getElementById('partial-payment-amount-wrapper'),
  enablePartialPaymentAmountWrapper: document.getElementById('enable-partial-payment-wrapper'),
  partialPaymentFeedback: document.getElementById('partial-payment-amount-feedback'),
  minInstallmentValue: document.getElementById('min-installment-value'),
  originalPaymentAmount: document.getElementById('original-payment-amount'),
  btnSendOrder: document.getElementById('btnSendOrder'),
  card: {
    type: document.getElementsByName('bpmpi_paymentmethod')[0],
    number: document.getElementsByName('cardNumber')[0],
    holder: document.getElementsByName('holderName')[0],
    expiration: {
      month: document.querySelector('.bpmpi_cardexpirationmonth'),
      year: document.querySelector('.bpmpi_cardexpirationyear')
    },
    cvv: document.querySelector('#cc_cvv')
  },
  payment: {
    installments: document.querySelector('.bpmpi_installments'),
    amount: document.querySelector('.bpmpi_totalamount'),
    invoiceId: (new URLSearchParams(window.location.search)).get('id')
  },
  merchantOrderId: document.querySelector('.bpmpi_ordernumber'),
  address: {
    billing: {
      zipcode: document.querySelector('.address-zipcode'),
      country: document.querySelector('.address-country'),
      street1: document.querySelector('.address-street1'),
      street2: document.querySelector('.address-street2'),
      complement: document.querySelector('.address-complement'),
      number: document.querySelector('.address-number'),
      state: document.querySelector('.address-state'),
      city: document.querySelector('.address-city'),
      email: document.querySelector('.address-email'),
      phoneNumber: document.querySelector('.address-phonenumber'),
      customerName: document.querySelector('.address-full-name')
    }
  },
  // 3DS hidden address fields.
  ds3: {
    address: {
      billing: {
        zipcode: document.querySelector('.bpmpi_billto_zipcode'),
        country: document.querySelector('.bpmpi_billto_country'),
        street1: document.querySelector('.bpmpi_billto_street1'),
        street2: document.querySelector('.bpmpi_billto_street2'),
        state: document.querySelector('.bpmpi_billto_state'),
        city: document.querySelector('.bpmpi_billto_city'),
        email: document.querySelector('.bpmpi_billto_email'),
        phoneNumber: document.querySelector('.bpmpi_billto_phonenumber'),
        customerName: document.querySelector('.bpmpi_billto_contactname')
      }
    }
  }
}

const lknCielo3dsForm = document.getElementById('lkncielo3ds-form')
const switchCardAddressIsSameAsProfile = document.getElementById('switchCardAddressIsSameAsProfile')

class LknCielo3DSModal {
  constructor () {
    this.modalTitle = document.getElementById('lkncielo3ds-modal-title')
    this.modalBody = document.getElementById('lkncielo3ds-modal-body')
    this.modalSmall = document.getElementById('lkncielo3ds-modal-small')
    this.modalFooter = document.getElementById('lkncielo3ds-modal').querySelector('.modal-footer')
  }

  show (title, body, small = '', showBtn = true) {
    this.modalTitle.innerHTML = title
    this.modalBody.innerHTML = body
    this.modalSmall.innerHTML = small

    this.modalFooter.style.display = showBtn ? 'block' : 'none'

    $('#lkncielo3ds-modal').modal()
  }
}

class LknCielo3DSToast {
  constructor () {
    this.modalTitle = document.getElementById('lkncielo3ds-toast-title')
    this.modalBody = document.getElementById('lkncielo3ds-toast-body')
  }

  show (title, body, small = '') {
    this.modalTitle.innerHTML = title
    this.modalBody.innerHTML = body

    $('#lkncielo3ds-toast').toast('show')
  }
}

class LknCielo3DSSendOrderBtn {
  constructor () {
    this.btnSendOrder = document.getElementById('btnSendOrder')
    this.btnSendOrderLoading = document.getElementById('btnSendOrderLoading')
  }

  setProcessingState () {
    this.btnSendOrder.style.display = 'none'
    this.btnSendOrderLoading.style.display = 'block'
  }

  setEnabledState () {
    this.btnSendOrder.style.display = 'block'
    this.btnSendOrder.disabled = false
    this.btnSendOrderLoading.style.display = 'none'
  }
}

const lknCielo3DSToast = new LknCielo3DSToast()
const lknCielo3dsModal = new LknCielo3DSModal()
const lknCielo3DSSendOrderBtn = new LknCielo3DSSendOrderBtn()

let clientProfileAddress = {}
let zipCodeSpinner

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL'
})

if (fields.address.billing.zipcode) {
  // The fields are originally filled with the client profile values.
  clientProfileAddress = {
    zipcode: fields.address.billing.zipcode.value,
    country: fields.address.billing.country.value,
    street1: fields.address.billing.street1.value,
    street2: fields.address.billing.street2.value,
    complement: fields.address.billing.complement.value,
    number: fields.address.billing.number.value,
    state: fields.address.billing.state.value,
    city: fields.address.billing.city.value,
    email: fields.address.billing.email.value,
    phoneNumber: fields.address.billing.phoneNumber.value,
    customerName: fields.address.billing.customerName.value
  }

  fields.address.billing.phoneNumberIntl = new intlTelInput(fields.address.billing.phoneNumber, {
    utilsScript: `${window.location.origin}/lib/resources/form/intlTelInput/js/utils.min.js`,
    formatOnDisplay: false,
    initialCountry: 'BR',
    separateDialCode: true,
    preferredCountries: ['BR', 'PT']
  })

  zipCodeSpinner = document.getElementById('zipcode-spinner')

  switchCardAddressIsSameAsProfile.addEventListener('change', e => handleSwitchCardAddressIsSameAsProfile(e))
  fields.address.billing.zipcode.addEventListener('input', handleZipcodeChange)

  setupIntlTelInput()

  fields.address.billing.phoneNumber.value = ''
  fields.address.billing.phoneNumberIntl.setNumber(clientProfileAddress.phoneNumber)

  fields.address.billing.zipcode.disabled = fields.address.billing.zipcode.value !== ''
  fields.address.billing.country.disabled = fields.address.billing.country.value !== ''
  fields.address.billing.street1.disabled = fields.address.billing.street1.value !== ''
  fields.address.billing.street2.disabled = fields.address.billing.street2.value !== ''
  fields.address.billing.state.disabled = fields.address.billing.state.value !== ''
  fields.address.billing.city.disabled = fields.address.billing.city.value !== ''
  fields.address.billing.email.disabled = fields.address.billing.email.value !== ''
  fields.address.billing.number.disabled = fields.address.billing.number.value !== ''
  fields.address.billing.customerName.disabled = fields.address.billing.customerName.value !== ''
  fields.address.billing.phoneNumber.disabled = fields.address.billing.phoneNumber.value !== ''
  switchCardAddressIsSameAsProfile.checked = true
}

function pre3dsValidatoinHook () {
  if (fields.address.billing.zipcode) {
    fields.ds3.address.billing.zipcode.value = fields.address.billing.zipcode.value
    fields.ds3.address.billing.country.value = fields.address.billing.country.value
    fields.ds3.address.billing.street1.value = `${fields.address.billing.street1.value}, ${fields.address.billing.number.value}`
    fields.ds3.address.billing.street2.value = `${fields.address.billing.complement.value}, ${fields.address.billing.street2.value}`
    fields.ds3.address.billing.state.value = fields.address.billing.state.value
    fields.ds3.address.billing.city.value = fields.address.billing.city.value
    fields.ds3.address.billing.email.value = fields.address.billing.email.value
    fields.ds3.address.billing.phoneNumber.value = fields.address.billing.phoneNumber.value
    fields.ds3.address.billing.customerName.value = fields.address.billing.customerName.value
  }
}

/**
 * @param {String} type
 */
function handleCardTypeChange (type) {
  if (type === 'debit') {
    $('.bpmpi_installments').parent().parent().slideUp('normal')
    fields.payment.installments.required = false
  } else {
    regenerateInstallments(getPaymentAmount())
    $('.bpmpi_installments').parent().parent().slideDown('normal')
    fields.payment.installments.required = true
  }
}

/**
 * Handles the display of delivery address fields.
 *
 * @param {boolean} display must display them?
 */
function handleSwitchCardAddressIsSameAsProfile (evt) {
  const disabled = evt.target.checked

  fields.address.billing.zipcode.disabled = disabled
  // fields.address.billing.country.disabled = disabled
  fields.address.billing.street1.disabled = disabled
  fields.address.billing.street2.disabled = disabled
  fields.address.billing.state.disabled = disabled
  fields.address.billing.city.disabled = disabled
  fields.address.billing.email.disabled = disabled
  fields.address.billing.phoneNumber.disabled = disabled
  fields.address.billing.customerName.disabled = disabled
  fields.address.billing.number.disabled = !!(disabled && clientProfileAddress.number.length > 0)

  fields.address.billing.zipcode.value = disabled ? clientProfileAddress.zipcode : ''
  // fields.address.billing.country.value = disabled ? clientProfileAddress.country : ''
  fields.address.billing.street1.value = disabled ? clientProfileAddress.street1 : ''
  fields.address.billing.street2.value = disabled ? clientProfileAddress.street2 : ''
  fields.address.billing.number.value = disabled ? clientProfileAddress.number : ''
  fields.address.billing.state.value = disabled ? clientProfileAddress.state : ''
  fields.address.billing.city.value = disabled ? clientProfileAddress.city : ''
  fields.address.billing.email.value = disabled ? clientProfileAddress.email : ''
  fields.address.billing.phoneNumberIntl.setNumber(disabled ? clientProfileAddress.phoneNumber : '')
  fields.address.billing.customerName.value = disabled ? clientProfileAddress.customerName : ''
}

/**
 * @param {string} cavv
 * @param {string} eci
 * @param {string} version
 * @param {string} referenceId
 */
function requestAuthorization (cavv, eci, version, referenceId) {
  if (
    fields.enablePartialPayment &&
     fields.enablePartialPayment.checked &&
     getPaymentAmount() === 0
  ) {
    lknCielo3dsModal.show(
      'Valor do pagamento parcial inválido',
      'Você ativou o pagamento parcial, mas não digitou o valor que deseja pagar.<br>Informe o valor ou desative o pagamento parcial para pagar o valor total.'
    )

    handlePartialPaymentChange()

    return
  }

  lknCielo3DSSendOrderBtn.setProcessingState()

  let address = {}

  if (fields.address.billing.zipcode) {
    address = {
      billing: {
        zipcode: fields.address.billing.zipcode.value,
        country: fields.address.billing.country.value,
        street1: `${fields.address.billing.street1.value}, ${fields.address.billing.number.value}`,
        street2: `${fields.address.billing.street2.value}, ${fields.address.billing.complement.value}`,
        state: fields.address.billing.state.value,
        city: fields.address.billing.city.value,
        email: fields.address.billing.email.value,
        phoneNumber: fields.address.billing.phoneNumber.value,
        customerName: fields.address.billing.customerName.value
      }
    }
  } else {
    address = {}
  }

  fetch(systemURL + '/modules/gateways/lkncielo3ds/authorize.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      externalAuthentication: {
        cavv,
        eci,
        version,
        referenceId
      },
      card: {
        type: fields.card.type.value,
        holder: fields.card.holder.value,
        number: fields.card.number.value,
        expiration: {
          month: fields.card.expiration.month.value,
          year: fields.card.expiration.year.value
        },
        cvv: fields.card.cvv.value
      },
      payment: {
        installments: fields.payment.installments.value,
        amount: getPaymentAmount(),
        invoiceId: fields.payment.invoiceId,
        clientEnabledPartialPayment: fields?.enablePartialPayment?.checked ?? false
      },
      merchantOrderId: fields.merchantOrderId.value,
      ...address
    })
  })
    .then(response => response.json())
    .then(res => {
      if (!res.hasOwnProperty('success') || res.success === false) {
        lknCielo3dsModal.show(
          'Não foi possível efetuar o pagamento',
          'Verifique os dados e tente novamente.'
        )

        lknCielo3DSSendOrderBtn.setEnabledState()
      } else {
        lknCielo3dsModal.show(
          'Pagamento efetuado!',
          `O pagamento de  ${currencyFormatter.format(getPaymentAmount())} foi adicionado à fatura.`,
          '',
          false
        )

        setTimeout(() => {
          window.location.reload()
        }, 1200)
      }
    })
    .catch(() => {
      lknCielo3dsModal.show(
        'Não foi possível efetuar o pagamento',
        'Verifique os dados e tente novamente.'
      )

      lknCielo3DSSendOrderBtn.setEnabledState()
    })
}

/**
 * Allows only letters (including accented characters) and some control keys
 * to be pressed inside the input.
 * Usually works for keydown event listener.
 *
 * @param {KeyboardEvent} evt
 */
function restrictInputToLetters (evt) {
  // Allow: backspace, delete, tab, escape, enter
  const allowedKeys = [
    8, // Backspace
    9, // Tab
    13, // Enter
    27, // Escape
    46, // Delete
    32 // Space
  ]

  // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Command+A, Command+C, Command+V
  const allowedKeyCombos = [
    65, // A
    67, // C
    86 // V
  ]

  if (
    (evt.keyCode >= 65 && evt.keyCode <= 90) || // alphabet keys
    (evt.keyCode >= 97 && evt.keyCode <= 122) || // lowercase alphabet keys
    (evt.keyCode >= 192 && evt.keyCode <= 687) || // accented character keys
    allowedKeys.includes(evt.keyCode) ||
    (evt.ctrlKey === true && allowedKeyCombos.includes(evt.keyCode)) ||
    (evt.metaKey === true && allowedKeyCombos.includes(evt.keyCode))
  ) {
    // Allow input
  } else {
    // Restrict input
    evt.preventDefault()
  }

  evt.target.value = evt.target.value.replace(/[^a-zA-ZÀ-ÖØ-öø-ȳ\-\s]/g, '')
}

/**
 * See: https://viacep.com.br/
 *
 * @param {Number} cep
 */
function requestCep (cep) {
  zipCodeSpinner.style.display = 'inline-block'
  fields.address.billing.zipcode.disabled = true
  fields.address.billing.street1.disabled = true
  fields.address.billing.street2.disabled = true
  fields.address.billing.complement.disabled = true
  fields.address.billing.state.disabled = true
  fields.address.billing.city.disabled = true

  fetch(`https://viacep.com.br/ws/${cep}/json/`)
    .then(response => response.json())
    .then(res => {
      if (!res.cep) {
        lknCielo3DSToast.show(
          'CEP não encontrado',
          'Não conseguimos encontrar o seu CEP. Digite o CEP novamente ou informe o endereço manualmente.'
        )

        return
      }

      fields.address.billing.street1.value = res.logradouro ?? ''
      fields.address.billing.street2.value = res.bairro ?? ''
      fields.address.billing.state.value = res.uf ?? ''
      fields.address.billing.city.value = res.localidade
    })
    .catch(() => {
      lknCielo3DSToast.show(
        'CEP não encontrado',
        'Não conseguimos encontrar o seu CEP. Digite o CEP novamente ou informe o endereço manualmente.'
      )
    })
    .finally(() => {
      zipCodeSpinner.style.display = 'none'
      fields.address.billing.zipcode.disabled = false
      fields.address.billing.street1.disabled = false
      fields.address.billing.street2.disabled = false
      fields.address.billing.complement.disabled = false
      fields.address.billing.state.disabled = false
      fields.address.billing.city.disabled = false
    })
}

let requestDebounceTimoutId

/**
 * @param {Function} func
 * @param {Number} delay
 *
 * @returns {Function}
 */
function debounce (func, delay) {
  return function () {
    const context = this
    const args = arguments

    clearTimeout(requestDebounceTimoutId)

    requestDebounceTimoutId = setTimeout(() => {
      func.apply(context, args)
    }, delay)
  }
}

/**
 * @param {KeyboardEvent} event
 */
function handleZipcodeChange (event) {
  const zipcode = event.target.value.trim()

  if (zipcode.length === 8 && fields.address.billing.country.value === 'BR') {
    debounce(requestCep, 1600)(zipcode)
  }
}

function setupIntlTelInput () {
  const css = document.createElement('link')
  css.rel = 'stylesheet'
  css.type = 'text/css'
  css.href = systemURL + '/modules/gateways/lkncielo3ds/lib/resources/form/intlTelInput/css/intlTelInput.css'

  document.head.appendChild(css)
}

function requestCardType () {
  fetch(systemURL + '/modules/gateways/lkncielo3ds/api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      a: 'bin',
      cardNumber: fields.card.number.value
    })
  })
    .then(response => response.json())
    .then(res => {
      if (!res.success) {
        fields.cardTypeWrapper.style.display = 'block'
        fields.card.type.dispatchEvent(new Event('change'))

        return
      }

      if (res.data.type === 'Multiplo') {
        $('.bpmpi_paymentmethod').parent().parent().slideDown()

        fields.card.type.value = 'credit'

        fields.card.type.dispatchEvent(new Event('change'))
      } else if (res.data.type === 'Crédito') {
        $('.bpmpi_installments').parent().parent().slideDown()
        $('.bpmpi_paymentmethod').parent().parent().slideUp()

        fields.payment.installments.required = true
        fields.card.type.value = 'credit'
      } else {
        $('.bpmpi_installments').parent().parent().slideUp()
        $('.bpmpi_paymentmethod').parent().parent().slideUp()

        fields.payment.installments.required = false
        fields.card.type.value = 'debit'
      }
    })
    .catch(() => {
      fields.cardTypeWrapper.style.display = 'block'
      fields.card.type.dispatchEvent(new Event('change'))
    })
}

/**
 * @param {KeyboardEvent} evt
 */
function handleCardNumberInput (evt) {
  const cardNumber = fields.card.number.value

  if (cardNumber.length > 12) {
    debounce(requestCardType, 1000)(cardNumber)
  }
}

/**
 * Returns the payment amount based on the discount per credit or debit rules and in the partial payment rule.
 *
 * @returns {Number}
 */
function getPaymentAmount () {
  if (fields.enablePartialPayment && fields.enablePartialPayment.checked) {
    return parseFloat(fields.partialPayment.value.replace('.', '').replace(',', '.').replace('R$ ', ''))
  }

  if (fields.discountInput) {
    const debitDiscount = parseFloat(fields.discountInput.dataset.debitPaymentAmountWithDiscount)
    const creditDiscount = parseFloat(fields.discountInput.dataset.creditPaymentAmountWithDiscount)

    if (fields.card.type.value === 'debit' && debitDiscount) {
      return debitDiscount
    } else if (creditDiscount) {
      return creditDiscount
    }
  }

  return fields.originalPaymentAmount.value
}

function partialPaymentFeedback (msg, toolTipMsg) {
  fields.partialPaymentFeedback.innerHTML = msg

  fields.partialPayment.dataset.originalTitle = toolTipMsg

  $('#partial-payment-amount-feedback').fadeTo(0, 1)
}

function handlePartialPaymentChange () {
  fields.btnSendOrder.disabled = true

  const minPartialAmount = parseFloat(fields.partialPayment.dataset.minPartialAmount)
  const maxPartialAmount = fields.originalPaymentAmount.value - minPartialAmount
  const partialAmount = parseFloat(fields.partialPayment.value.replace('.', '').replace(',', '.').replace('R$ ', ''))

  if (partialAmount < minPartialAmount) {
    fields.btnSendOrder.disabled = false

    partialPaymentFeedback(
      `O valor mínimo é ${currencyFormatter.format(minPartialAmount)} ` +
      `e o máximo é ${currencyFormatter.format(maxPartialAmount)}.`,

      fields.partialPayment.dataset.originalTitle = 'Deixe vazio para parcelar o valor total disponível que é ' + currencyFormatter.format(maxPartialAmount) + '.'
    )

    return
  }

  if (partialAmount > maxPartialAmount) {
    partialPaymentFeedback(
      `Você pode pagar parcialmente até ${currencyFormatter.format(maxPartialAmount)}, ` +
      `pois é necessário que reste no mínimo ${currencyFormatter.format(minPartialAmount)}`,

      `Deixe vazio para parcelar o valor total disponível que é ${currencyFormatter.format(maxPartialAmount)}.`
    )

    return
  }

  $('#partial-payment-amount-feedback').fadeTo(0, 0)

  regenerateInstallments(partialAmount)

  fields.btnSendOrder.disabled = false
}

function handleEnablePartialPaymentChange () {
  fields.partialPayment.required = fields.enablePartialPayment.checked

  if (fields.enablePartialPayment.checked) {
    $('#partial-payment-amount-wrapper').slideDown()
  } else {
    $('#partial-payment-amount-wrapper').slideUp('normal', () => {
      fields.partialPayment.value = 'R$ 0,00'
    })
  }
}

function regenerateInstallments (amount) {
  fields.payment.installments.innerHTML = ''

  const minInstallmentValue = parseFloat(fields.minInstallmentValue.value)

  for (let installment = 1; installment <= 12; installment++) {
    const installmentValue = amount / installment

    if (installmentValue < minInstallmentValue) {
      break
    }

    const option = document.createElement('option')

    option.value = installment
    option.textContent = `${installment} x ${currencyFormatter.format(installmentValue)} sem juros`

    fields.payment.installments.appendChild(option)
  }
}

function togglePartialPaymentFields (show) {
  if (show) {
    // $('#partial-payment-amount-wrapper').fadeTo('normal', 1)
    // $('#enable-partial-payment-wrapper').fadeTo('normal', 1)
    $('#partial-payment-amount-wrapper').slideDown()
    $('#enable-partial-payment-wrapper').slideDown()
  } else {
    // $('#partial-payment-amount-wrapper').fadeTo('normal', 0.5)
    // $('#enable-partial-payment-wrapper').fadeTo('normal', 0.5)
    $('#partial-payment-amount-wrapper').slideUp()
    $('#enable-partial-payment-wrapper').slideUp()
  }

  fields.partialPayment.value = 'R$ 0,00'
  fields.partialPayment.disabled = !show
  fields.enablePartialPayment.disabled = !show
}

$('.bpmpi_installments').parent().parent().hide()
$('.bpmpi_paymentmethod').parent().parent().hide()

const formCssTag = document.createElement('link')

formCssTag.rel = 'stylesheet'
formCssTag.type = 'text/css'
formCssTag.href = systemURL + '/modules/gateways/lkncielo3ds/lib/resources/form/form.css'

document.head.appendChild(formCssTag)

fields.card.number.addEventListener('input', handleCardNumberInput)
fields.card.holder.addEventListener('keydown', restrictInputToLetters)
fields.card.type.addEventListener('change', e => handleCardTypeChange(e.target.value))

if (fields.enablePartialPayment) {
  fields.card.type.addEventListener('change', handlePartialPaymentChange)

  VMasker(fields.partialPayment).maskMoney({
    precision: 2,
    separator: ',',
    delimiter: '.',
    unit: 'R$',
    zeroCents: false
  })

  fields.enablePartialPayment.addEventListener('change', handleEnablePartialPaymentChange)
  fields.partialPayment.addEventListener('keyup', () => {
    debounce(handlePartialPaymentChange, 1000)()
  })
}

VMasker(fields.card.number).maskNumber()
VMasker(fields.card.cvv).maskNumber()

if (fields.address.billing.phoneNumber) {
  VMasker(fields.address.billing.number).maskNumber()
  VMasker(fields.address.billing.phoneNumber).maskNumber()
}
