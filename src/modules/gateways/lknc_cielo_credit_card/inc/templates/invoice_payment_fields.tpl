<div class="form-group row">
  <label class="col-sm-4 text-md-right control-label col-form-label">
    Parcelamento
  </label>
  <div class="col-sm-8">
    <select
      id="installment"
      class="form-control input-inline"
      name="lknc_installment"
      required
    >
      {if $invoiceBalance < $minimumInstallmentAmount}
        <option value="1">1 x R$ {$invoiceBalance} sem juros</option>
      {else}
        {for $parcelDivisor=1 to 12}
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
