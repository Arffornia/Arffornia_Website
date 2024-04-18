@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{asset("css/pages/stages.css")}}">
@endsection

@section('content')
    <div class="bg">
        <div class="canvas">
        </div>
    </div>   
@endsection
@section('script')
    <script>
        const stages = @json($stages);
        const milestones = @json($milestones);
        const milestone_closure = @json($milestone_closure);
    </script>

    <script src="{{asset("js/mod_icons.js")}}"></script>
    <script src="{{asset("js/stages.js")}}"></script>

@endsection