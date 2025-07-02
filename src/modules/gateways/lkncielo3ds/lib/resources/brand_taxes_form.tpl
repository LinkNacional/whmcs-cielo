<div class="container">
    <div class="row">
        <div class="col-12">
            <strong>Arquivo de taxas por bandeira de cartão</strong>
        </div>

        <div
            class="col-12"
            style="margin-bottom: 10px;"
        >
            <p>Para atualizar as taxas, basta realizar o download do arquivo de taxas, editá-lo e então enviar o arquivo
                com as edições.</p>
        </div>

        <div
            class="col-12"
            style="margin-bottom: 10px;"
        >
            <button
                id="download-file-json"
                class="btn btn-default btn-sm"
                style="margin-right: 10px;"
                type="button"
            >
                <i class="fas fa-cloud-download"></i> Baixar arquivo de taxas
            </button>
        </div>

        <div
            class="col-12"
            style="margin-bottom: 10px;"
        >
            <div style="display: flex; margin-right: 20px;">
                <button
                    id="upload-file-json"
                    class="btn btn-default btn-sm"
                    style="margin-right: 10px;"
                    type="button"
                >
                    <i class="fas fa-cloud-upload"></i> Enviar novo arquivo de taxas
                </button>
                <input
                    id="json-file-input"
                    class="form-control form-control-sm"
                    style="max-width: 250px; min-height: 34px; margin-right: 5px;"
                    type="file"
                    accept=".json"
                >
            </div>
        </div>

        <div
            class="col-12"
            style="margin-bottom: 10px;"
        >
            <div style="display: flex; margin-right: 20px;">
                <button
                    id="download-default-file-json"
                    name="upload-file-json"
                    class="btn btn-default btn-sm"
                    style="margin-right: 10px;"
                    type="button"
                >
                    <i class="fas fa-file"></i> Baixar arquivo de taxas padrão
                </button>
            </div>
        </div>

        <div class="col-12">
            <p style="display: block; width: 50%; padding: 8px; color: red;">
                <b>ATENÇÃO:</b> tenha certeza de enviar o arquivo com os dados e formatação correta.
                Qualquer alteração incorreta pode resultar na parada do recebimento de pagamentos.
            </p>
        </div>
    </div>
</div>

<script type="text/javascript">
    const uploadFileBtn = document.getElementById('upload-file-json')
    const jsonFileInput = document.getElementById('json-file-input')

    const downloadFileBtn = document.getElementById('download-file-json')
    const downloadDefaultFileBtn = document.getElementById('download-default-file-json')

    const apiUrl = '{$systemUrl}/modules/gateways/lkncielo3ds/api.php';

    uploadFileBtn.addEventListener('click', () => {
        if (!jsonFileInput.files[0]) {
            alert('Primeiro, selecione o arquivo de taxas no campo ao lado.')

            return
        }

        const data = new FormData()
        data.append('lkncielo3ds-taxes-json', jsonFileInput.files[0])
        data.append('a', 'upload-taxes-json')

        fetch(apiUrl, { method: 'POST', body: data })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    alert(res.data.msg)
                } else {
                    alert(res.data.error)
                }
            })
            .catch(error => {
                alert('Não foi possível atualizar o arquiv: ' + error)
            })
    })

    function downloadResponseContent(response) {
        const filenameHeader = response.headers.get('Content-Disposition');

        if (filenameHeader) {
            const filenameMatch = filenameHeader.match(/filename="(.+)"/);

            const filename = filenameMatch[1];

            return response.blob().then(blob => {
                const url = URL.createObjectURL(blob);

                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = filename;

                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            });
        } else {
            alert('O arquivo de taxas não foi encontrado.');
        }
    }

    downloadFileBtn.addEventListener('click', () => {
        fetch(apiUrl, { method: 'POST', body: JSON.stringify({ a: 'download-taxes-json' }) })
            .then(response => {
                downloadResponseContent(response)
            })
            .catch(error => {
                alert('Não foi possível baixar o arquivo de taxas: ' + error)
            });
    })

    downloadDefaultFileBtn.addEventListener('click', () => {
        fetch(apiUrl, { method: 'POST', body: JSON.stringify({ a: 'download-default-taxes-json' }) })
            .then(response => {
                downloadResponseContent(response)
            })
            .catch(error => {
                alert('Não foi possível baixar o arquivo de taxas: ' + error)
            });
    })
</script>
