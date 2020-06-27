<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="csrf-token" content="{{csrf_token()}}">
      <script> window.Laravel= { crfsToken: '{{csrf_token()}}'}</script>
      <title>GTrends</title>
      <!-- Fonts -->
      <!-- Font Awesome -->
      <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css">
      <!-- Bootstrap core CSS -->
      <link href="{{asset('vendor/mdb/css/bootstrap.min.css')}}" rel="stylesheet">
      <!-- Material Design Bootstrap -->
      <link href="{{asset('vendor/mdb/css/mdb.min.css')}}" rel="stylesheet">
      <link href="{{asset('vendor/bootstrap-tags-input/tagsinput.css')}}" rel="stylesheet">
      {{-- <link href="https://bootstrap-tagsinput.github.io/bootstrap-tagsinput/examples/assets/app.css" rel="stylesheet"> --}}
      
      <!-- Your custom styles (optional) -->
      <link href="{{asset('custom/custom.css')}}" rel="stylesheet">

      <style>
            .bootstrap-tagsinput{
                padding: .375rem .75rem;
                line-height: 1.5;
            }
            .bootstrap-tagsinput .badge {
                margin: 2px 2px;
                padding: 3px 8px;
            }
      </style>
   </head>
   <body>
       <div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 100vh">
        <div class="container">
            <h4 id="detail-progress" class="text-center" data-status="">Adding Job to Queue</h4>
            <div class="d-flex" style="justify-content: space-around">
                <div style="flex-basis: 24%">
                    <div class="progress">
                        <div id="progress-1" class="progress-bar " role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div style="flex-basis: 24%">
                    <div class="progress">
                        <div id="progress-2" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div style="flex-basis: 24%">
                    <div class="progress">
                        <div id="progress-3" class="progress-bar " role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div style="flex-basis: 24%">
                    <div class="progress">
                        <div id="progress-4" class="progress-bar " role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-center">
                <div id="note" class="alert alert-warning m-3 text-small" style="max-width: 350px" role="alert">Sorry we are a little busy right now. We will process your request after '+response.jobs_a_head+' other requests'</div>
            </div>
            
        </div>
       </div>
     
      <!-- JQuery -->
      <script type="text/javascript" src="{{asset('vendor/mdb/js/jquery-3.3.1.min.js')}}"></script>
      <!-- Bootstrap tooltips -->
      <script type="text/javascript" src="{{asset('vendor/mdb/js/popper.min.js')}}"></script>
      <!-- Bootstrap core JavaScript -->
      <script type="text/javascript" src="{{asset('vendor/mdb/js/bootstrap.min.js')}}"></script>
      <!-- MDB core JavaScript -->
      <script type="text/javascript" src="{{asset('vendor/mdb/js/mdb.min.js')}}"></script>
    
      
      
      {{-- <script type="text/javascript" src="https://raw.githubusercontent.com/corejavascript/typeahead.js/master/dist/typeahead.bundle.min.js"></script> --}}
      <script type="text/javascript" src="{{asset('vendor/bootstrap-tags-input/tagsinput.js')}}"></script>
      
       <script type="text/javascript" src="{{asset('custom/custom.js')}}"></script>
       
       <script>
        //    (function(){
            // $('div[id^="progress"]').css('transition','none');
            const detailProgress = $('#detail-progress');
            const note = $('#note');
            // check();
            // async function check(){
            //     try{
            //         let response = await $.get('{{route("queue",[$queue->id])}}')
            //             switch(response.status){
            //                 case 0:
            //                     detailProgress.html('Error');
            //                     break;
            //                 case 1:
            //                     detailProgress.html('1. Adding job to queue');
            //                     note.html('Sorry we are a little busy right now. We will process your request after '+response.jobs_a_head+' other requests').removeClass('d-none')
            //                     break;
            //                 case 2:
            //                     note.addClass('d-none')
            //                     break;
            //                 case 3:
            //                     note.addClass('d-none')
            //                     break;
            //                 case 4:
            //                     note.addClass('d-none')
            //                     detailProgress.html('4. Preparing Result');
            //                     window.location.replace('{{route("results",[$queue->id])}}');
            //                     break
            //             }
            //         if(response.status != 4 && response.status != 0 ){
            //             check()
            //         }
            //     }catch(error){
            //         console.log(error);
            //         detailProgress.html('Error');
            //     }
            // }

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
        //    }())
           
       </script>
    </body>
</html>