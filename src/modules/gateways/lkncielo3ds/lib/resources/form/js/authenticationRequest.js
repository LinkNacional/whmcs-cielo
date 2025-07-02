function sendOrder () {
  if (!lknCielo3dsForm.reportValidity()) {
    return
  }

  pre3dsValidatoinHook()

  document.getElementById('btnSendOrder').disabled = true

  bpmpi_authenticate()
}

function bpmpi_config () {
  const lknCielo3dsModal = new LknCielo3DSModal()
  const btnSendOrder = document.getElementById('btnSendOrder')
  const env = document.getElementById('lkncielo3ds-env').value === 'prod' ? 'PRD' : 'SDB'

  return {
    onReady: function () {
      // Evento indicando quando a inicialização do script terminou.
      btnSendOrder.disabled = false
    },
    onSuccess: function (e) {
      // Cartão elegível para autenticação, e portador autenticou com sucesso.
      const cavv = e.Cavv
      const eci = e.Eci
      const version = e.Version
      const referenceId = e.ReferenceId

      requestAuthorization(cavv, eci, version, referenceId)
    },
    onFailure: function (e) {
      // Cartão elegível para autenticação, porém o portador finalizou com falha.
      lknCielo3dsModal.show(
        'Não foi possível autenticar',
        'Não foi possível confirmar a autenticidade do cartão.'
      )

      btnSendOrder.disabled = false
    },
    onUnenrolled: function (e) {
      // Cartão não elegível para autenticação (não autenticável).
      lknCielo3dsModal.show(
        'Não foi possível autenticar',
        'Não foi possível confirmar a autenticidade do cartão.'
      )

      btnSendOrder.disabled = false
    },
    onDisabled: function () {
      // Loja não requer autenticação do portador (classe "bpmpi_auth" false -> autenticação desabilitada).
    },
    onError: function (e) {
      // Erro no processo de autenticação.
      const returnCode = e.ReturnCode
      const returnMessage = e.ReturnMessage

      lknCielo3dsModal.show(
        'Não foi possível autenticar',
        'Não foi possível confirmar a autenticidade do cartão.',
        returnCode + ' - ' + returnMessage
      )

      btnSendOrder.disabled = false
    },
    onUnsupportedBrand: function (e) {
      // Bandeira não suportada para autenticação.
      const returnCode = e.ReturnCode
      const returnMessage = e.ReturnMessage

      lknCielo3dsModal.show(
        'Bandeira não suportada',
        'A bandeira não é suportada para autenticação.',
        returnCode + ' - ' + returnMessage
      )

      btnSendOrder.disabled = false
    },
    Environment: env,
    Debug: env === 'SDB'
  }
}
