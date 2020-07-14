@extends('pages.layout')
@section('title','GTrend - Fecthing data for '.collect($queue->keywords)->join(', ',' and '))
@section('css')

@endsection

@section('js')
    <script>
        const detailProgress = $('#detail-progress');
        const note = $('#note');
        const jobsRemaining = $('#jobs-remaining');
        check();
        async function check(){
            try{
                let response = await $.get('{{route("queue",[$queue->id])}}')
                note.addClass('d-none')
                switch(response.status){
                    case 0:
                        detailProgress.html('Error').addClass('text-danger');
                        window.location.replace('{{route("home")}}');
                        break;
                    case 1:
                        detailProgress.html('1. Adding job to queue');
                        if(response.jobs_a_head != 0){
                            note.removeClass('d-none')
                            jobsRemaining.html(response.jobs_a_head+' a head');
                        }
                        break;
                    case 2:
                        detailProgress.html('2. Fetching data from Google Trend');
                        break;
                    case 3:
                        detailProgress.html('3. Preparing Result');
                        window.location.replace('{{route("results",[$queue->id])}}');
                        break
                }
                updateLoading(response.status)
                if(response.status != 3 && response.status != 0 ){
                    check()
                }
            }catch(error){
                console.log(error);
                detailProgress.html('Error');
            }
        }
        function updateLoading(status){
            var timeout = 0;
            for (let i = 0; i <= status ; i++) {
                var width = parseFloat($('#progress-'+i).css('width'))
                if(width < 100){
                    setTimeout(function(){
                        $('#progress-'+i).width(100+'%')
                    }, timeout);
                    timeout += 600;                        
                }
            }
        }
    </script>
@endsection

@section('content')
<div class="container">
    <div class="d-flex align-items-center" style="min-height: 100vh">
        <div class="container">
            <div class="card">
                <div class="card-header py-2">
                    <h4 id="detail-progress" class="card-title">Please Wait</h4>
                </div>
                
                <div class="card-body py-0" >
                    <div id="note" class="py-1 d-none">
                        Sorry we are a little busy right now. We will process your request after <strong id="jobs-remaining"></strong>
                    </div>
                </div>

                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <div style="flex-basis: 32%">
                        <div class="progress" style="height: 0.625rem;">
                            <div id="progress-1" class="progress-bar " role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div style="flex-basis: 32%">
                        <div class="progress" style="height: 0.625rem;">
                            <div id="progress-2" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div style="flex-basis: 32%">
                        <div class="progress" style="height: 0.625rem;">
                            <div id="progress-3" class="progress-bar " role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    
@endsection

