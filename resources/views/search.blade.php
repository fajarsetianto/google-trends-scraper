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
            <div id="detail-progress" class="text-center"></div>
            <div class="progress">
                <div id="fetch-progress" class="progress-bar progress-bar-striped" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
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

      <script src="https://momentjs.com/downloads/moment-with-locales.min.js"></script>
      <script type="text/javascript" src="{{asset('vendor/bloodhound/bloodhound.js')}}"></script>
      
      
      {{-- <script type="text/javascript" src="https://raw.githubusercontent.com/corejavascript/typeahead.js/master/dist/typeahead.bundle.min.js"></script> --}}
      <script type="text/javascript" src="{{asset('vendor/bootstrap-tags-input/tagsinput.js')}}"></script>
      
       <script type="text/javascript" src="{{asset('custom/custom.js')}}"></script>
       <script>
            const detailProgress = $('#detail-progress');
            var periods = {!!json_encode($formatedPeriods)!!}
            var keywords = {!!json_encode($input['keyword'])!!}
            var dataset = {!!json_encode($dataSet)!!}
            var category = {!!json_encode($input['kategori'])!!}
            var url = "{!!route("fetch")!!}";
            var step = 100 / (periods.length);
            var i = 0;
            fetching();


            async function fetching(){
                try{
                    detailProgress.html('Mengambil data dari Google Trends')
                    for (let index = 0; index < periods.length; index++) {
                        periods[index].timeseries = await $.post(
                            url,
                            {
                                _token : '{{csrf_token()}}',
                                start_date : moment(periods[index].start_date).format('YYYY-MM-DD'),
                                end_date : moment(periods[index].end_date).format('YYYY-MM-DD'),
                                keywords : keywords,
                                category : category
                            })
                        i += step;
                        $('#fetch-progress').width((i) +'%').html((Math.ceil(i)) +'%')
                    }
                }catch(err){
                    console.log(err)
                }
            }

            // function fetch(){
            //     detailProgress.html('Mengambil data dari Google Trends')

            //     var requests = [];
            //     var step = 100 / (periods.length);
            //     var i = 0;
            //     periods.forEach(function(element, index){
            //         var start_date = moment(element.start_date);
            //         var end_date = moment(element.end_date);

            //         $.ajax({
            //             url: '{{route("fetch")}}',
            //             method: 'POST',
            //             data: {
            //                 _token : '{{csrf_token()}}',
            //                 start_date : start_date.format('YYYY-MM-DD'),
            //                 end_date : end_date.format('YYYY-MM-DD'),
            //                 keywords : keywords,
            //                 category : category
            //             },
            //             success: function(response){
            //                 periods[index].timeseries = response['TIMESERIES'];
            //                 i += step;
            //                 $('#fetch-progress').width((i) +'%').html((Math.ceil(i)) +'%')
            //             }
            //         })
            //     });
            // }
            
            // $.when.apply(null,requests).then(function(){
            //     $.each(arguments, function(i,row){
            //         console.log(row)
            //     })
            // });
                
            
       </script>
   </body>
</html>