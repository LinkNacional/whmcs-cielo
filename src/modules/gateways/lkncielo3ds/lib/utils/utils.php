<?php

use WHMCS\Database\Capsule;

function lkncielo3ds_system_url(): string
{
    $url = Capsule::table('tblconfiguration')
            ->where('setting', 'SystemURL')
            ->first(['value'])
            ->value;

    return rtrim($url, '/');
}

function lkncielo3ds_create_custom_field_select(): array
{
    $fields = Capsule::table('tblcustomfields')
        ->where('type', 'client')
        ->get(['id', 'fieldname']);

    $selectData = ['' => 'Selecionar opção'];

    foreach ($fields as $field) {
        $selectData[$field->id] = $field->fieldname;
    }

    return $selectData;
}
