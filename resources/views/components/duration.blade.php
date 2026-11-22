@props(['seconds'])
@if ($seconds === null){{ '-' }}@else{{ \Carbon\CarbonInterval::seconds($seconds)->cascade()->forHumans(['short' => true, 'parts' => 2]) }}@endif
