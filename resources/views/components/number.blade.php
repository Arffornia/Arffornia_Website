@props(['value', 'decimals' => 0])
{{ number_format((float) $value, $decimals, __('common.number.decimal'), __('common.number.thousands')) }}
