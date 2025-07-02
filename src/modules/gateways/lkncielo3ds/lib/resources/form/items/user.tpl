<!-- dados do usuário/conta -->
<select
    style="display: none;"
    name="newCustomer"
    class="bpmpi_merchant_newcustomer"
>
    <option
        value="false"
        selected="selected"
    >false</option>
</select>

<input
    style="display: none;"
    type="text"
    class="bpmpi_useraccount_guest"
    value="{$data->user->accountGuest}"
/>

<input
    style="display: none;"
    type="text"
    class="bpmpi_useraccount_createddate"
    value="{$data->user->createdDate}"
/>

<input
    style="display: none;"
    type="text"
    class="bpmpi_useraccount_changeddate"
    value="{$data->user->changedDate}"
/>

<input
    style="display: none;"
    type="text"
    class="bpmpi_useraccount_authenticationmethod"
    value="{$data->user->authenticationMethod}"
/>

<input
    style="display: none;"
    type="text"
    class="bpmpi_useraccount_authenticationtimestamp"
    value="{$data->user->authenticationTimestamp}"
/>
