@props(['ratio'])
{{ __('common.number.percent', ['value' => number_format((float) $ratio * 100, 0, __('common.number.decimal'), __('common.number.thousands'))]) }}
